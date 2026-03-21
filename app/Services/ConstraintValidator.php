<?php

namespace App\Services;

use App\Models\FacultyWorkloadConfiguration;
use App\Models\User;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * ConstraintValidator
 * 
 * Validates all academic scheduling constraints for the Genetic Algorithm.
 * Provides penalty scores for constraint violations.
 */
class ConstraintValidator
{
    // Penalty weights for different constraint violations
    public const PENALTY_SCHEME_VIOLATION = 100;
    public const PENALTY_ROOM_CONFLICT = 100;
    public const PENALTY_FACULTY_OVERLOAD = 80;
    public const PENALTY_BREAK_VIOLATION = 50;
    public const PENALTY_TIME_OVERLAP = 100;
    public const PENALTY_SECTION_OVERLAP = 100;

    // Break time configuration
    public const MIN_BREAK_DURATION = 60; // minutes
    public const MAX_CONTINUOUS_HOURS = 4;
    public const LUNCH_BREAK_START = '12:00';
    public const LUNCH_BREAK_END = '13:00';

    /**
     * Check if time slot is within instructor's configured daily scheme.
     */
    public function isWithinInstructorScheme(
        User $instructor,
        string $startTime,
        string $endTime,
        ?string $day = null,
        ?int $programId = null
    ): bool {
        $config = $this->getInstructorWorkloadConfiguration($instructor->id, $programId);

        if (!$config) {
            return true;
        }

        $slotStart = $this->normalizeTime($startTime);
        $slotEnd = $this->normalizeTime($endTime);

        if (!$slotStart || !$slotEnd || $slotStart >= $slotEnd) {
            return false;
        }

        $teachingScheme = is_array($config->teaching_scheme) ? $config->teaching_scheme : [];

        if ($day) {
            if (!empty($teachingScheme)) {
                $dayScheme = $teachingScheme[$day] ?? null;
                if (!$dayScheme || empty($dayScheme['start']) || empty($dayScheme['end'])) {
                    return false;
                }

                $schemeStart = $this->normalizeTime((string) $dayScheme['start']);
                $schemeEnd = $this->normalizeTime((string) $dayScheme['end']);

                if (!$schemeStart || !$schemeEnd || $schemeStart >= $schemeEnd) {
                    return false;
                }

                return $slotStart >= $schemeStart && $slotEnd <= $schemeEnd;
            }

            $availableDays = is_array($config->available_days) ? $config->available_days : [];
            if (!empty($availableDays) && !in_array($day, $availableDays, true)) {
                return false;
            }
        }

        if ($config->start_time && $config->end_time) {
            $schemeStart = $this->normalizeTime((string) $config->start_time);
            $schemeEnd = $this->normalizeTime((string) $config->end_time);

            if ($schemeStart && $schemeEnd && $schemeStart < $schemeEnd) {
                return $slotStart >= $schemeStart && $slotEnd <= $schemeEnd;
            }
        }

        return true;
    }

    /**
     * Calculate penalty if instructor scheme is violated.
     */
    public function getSchemeViolationPenalty(
        User $instructor,
        string $startTime,
        string $endTime,
        ?string $day = null,
        ?int $programId = null
    ): int {
        if ($this->isWithinInstructorScheme($instructor, $startTime, $endTime, $day, $programId)) {
            return 0;
        }

        $allowedRange = $this->getAllowedRangeForDay($instructor->id, $day, $programId);
        if (!$allowedRange) {
            return self::PENALTY_SCHEME_VIOLATION;
        }

        $schemeStart = Carbon::createFromFormat('H:i', $allowedRange['start']);
        $schemeEnd = Carbon::createFromFormat('H:i', $allowedRange['end']);
        $slotStart = Carbon::createFromFormat('H:i', $this->normalizeTime($startTime));
        $slotEnd = Carbon::createFromFormat('H:i', $this->normalizeTime($endTime));

        $violationMinutes = 0;

        if ($slotStart->lessThan($schemeStart)) {
            $violationMinutes += $schemeStart->diffInMinutes($slotStart);
        }

        if ($slotEnd->greaterThan($schemeEnd)) {
            $violationMinutes += $slotEnd->diffInMinutes($schemeEnd);
        }

        return self::PENALTY_SCHEME_VIOLATION + (int) ($violationMinutes / 10);
    }

    /**
     * Get the allowed range for display or conflict details.
     */
    public function getAllowedRangeForDay(int $instructorId, ?string $day = null, ?int $programId = null): ?array
    {
        $config = $this->getInstructorWorkloadConfiguration($instructorId, $programId);
        if (!$config) {
            return null;
        }

        $teachingScheme = is_array($config->teaching_scheme) ? $config->teaching_scheme : [];
        if ($day && !empty($teachingScheme) && isset($teachingScheme[$day])) {
            $start = $this->normalizeTime((string) ($teachingScheme[$day]['start'] ?? ''));
            $end = $this->normalizeTime((string) ($teachingScheme[$day]['end'] ?? ''));

            if ($start && $end) {
                return ['start' => $start, 'end' => $end];
            }
        }

        $start = $config->start_time ? $this->normalizeTime((string) $config->start_time) : null;
        $end = $config->end_time ? $this->normalizeTime((string) $config->end_time) : null;

        if ($start && $end) {
            return ['start' => $start, 'end' => $end];
        }

        return null;
    }

    private function getInstructorWorkloadConfiguration(int $instructorId, ?int $programId = null): ?FacultyWorkloadConfiguration
    {
        $query = FacultyWorkloadConfiguration::query()
            ->where('user_id', $instructorId)
            ->where('is_active', true);

        if ($programId !== null) {
            $programConfig = (clone $query)
                ->where('program_id', $programId)
                ->first();

            if ($programConfig) {
                return $programConfig;
            }
        }

        return $query->latest('id')->first();
    }

    private function normalizeTime(string $time): ?string
    {
        $trimmed = trim($time);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $trimmed)) {
            return substr($trimmed, 0, 5);
        }

        if (preg_match('/^\d{2}:\d{2}$/', $trimmed)) {
            return $trimmed;
        }

        $parsed = strtotime($trimmed);
        return $parsed !== false ? date('H:i', $parsed) : null;
    }

    /**
     * Validate faculty load limits using lecture/lab maxima only.
     */
    public function validateFacultyLoad(User $instructor, float $lectureHours, float $labHours): array
    {
        $violations = [];

        $limits = $instructor->getWorkloadLimits();
        $maxLecture = $limits['max_lecture_hours'];
        $maxLab = $limits['max_lab_hours'];

        if ($maxLecture !== null && $lectureHours > $maxLecture) {
            $violations[] = [
                'type' => 'lecture_overload',
                'limit' => $maxLecture,
                'actual' => $lectureHours,
                'excess' => $lectureHours - $maxLecture,
            ];
        }

        if ($maxLab !== null && $labHours > $maxLab) {
            $violations[] = [
                'type' => 'lab_overload',
                'limit' => $maxLab,
                'actual' => $labHours,
                'excess' => $labHours - $maxLab,
            ];
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations,
            'penalty' => $this->calculateOverloadPenalty($violations),
        ];
    }

    /**
     * Calculate penalty for faculty overload
     */
    private function calculateOverloadPenalty(array $violations): int
    {
        $penalty = 0;

        foreach ($violations as $violation) {
            // Penalty increases with excess hours
            $penalty += self::PENALTY_FACULTY_OVERLOAD * $violation['excess'];
        }

        return (int) $penalty;
    }

    /**
     * Check if room is available for a specific time slot
     */
    public function checkRoomAvailability(
        int $roomId,
        string $day,
        string $startTime,
        string $endTime,
        Collection $existingSchedule
    ): bool {
        foreach ($existingSchedule as $gene) {
            if ($gene['room_id'] == $roomId && $gene['day'] == $day) {
                if ($this->timeSlotsOverlap($startTime, $endTime, $gene['start_time'], $gene['end_time'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check for room conflicts and return penalty
     */
    public function getRoomConflictPenalty(
        int $roomId,
        string $day,
        string $startTime,
        string $endTime,
        Collection $existingSchedule
    ): int {
        if ($this->checkRoomAvailability($roomId, $day, $startTime, $endTime, $existingSchedule)) {
            return 0;
        }

        return self::PENALTY_ROOM_CONFLICT;
    }

    /**
     * Check if two time slots overlap
     */
    public function timeSlotsOverlap(
        string $start1,
        string $end1,
        string $start2,
        string $end2
    ): bool {
        $s1 = Carbon::parse($start1);
        $e1 = Carbon::parse($end1);
        $s2 = Carbon::parse($start2);
        $e2 = Carbon::parse($end2);

        return $s1->lessThan($e2) && $e1->greaterThan($s2);
    }

    /**
     * Check for mandatory break violations for an instructor
     */
    public function hasBreakConflict(User $instructor, Collection $instructorSchedule, string $day): array
    {
        $violations = [];
        
        // Get all schedule items for this instructor on this day
        $daySchedule = $instructorSchedule->where('day', $day)
            ->where('instructor_id', $instructor->id)
            ->sortBy('start_time')
            ->values();

        if ($daySchedule->isEmpty()) {
            return ['valid' => true, 'violations' => [], 'penalty' => 0];
        }

        // Check for continuous teaching blocks
        $continuousHours = 0;
        $previousEnd = null;

        foreach ($daySchedule as $item) {
            $start = Carbon::parse($item['start_time']);
            $end = Carbon::parse($item['end_time']);
            $duration = $start->diffInHours($end, true);

            if ($previousEnd) {
                $breakDuration = Carbon::parse($previousEnd)->diffInMinutes($start);

                if ($breakDuration < self::MIN_BREAK_DURATION) {
                    // No adequate break, continue counting continuous hours
                    $continuousHours += $duration;
                } else {
                    // Adequate break found, reset counter
                    $continuousHours = $duration;
                }
            } else {
                $continuousHours = $duration;
            }

            // Check if exceeding max continuous hours
            if ($continuousHours > self::MAX_CONTINUOUS_HOURS) {
                $violations[] = [
                    'type' => 'continuous_hours_exceeded',
                    'day' => $day,
                    'continuous_hours' => $continuousHours,
                    'max_allowed' => self::MAX_CONTINUOUS_HOURS,
                ];
            }

            $previousEnd = $item['end_time'];
        }

        // Check for lunch break (optional but recommended)
        $hasLunchBreak = $this->hasAdequateLunchBreak($daySchedule);
        if (!$hasLunchBreak && $daySchedule->count() >= 4) {
            $violations[] = [
                'type' => 'missing_lunch_break',
                'day' => $day,
            ];
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations,
            'penalty' => count($violations) * self::PENALTY_BREAK_VIOLATION,
        ];
    }

    /**
     * Check if there's an adequate lunch break
     */
    private function hasAdequateLunchBreak(Collection $daySchedule): bool
    {
        $lunchStart = Carbon::parse(self::LUNCH_BREAK_START);
        $lunchEnd = Carbon::parse(self::LUNCH_BREAK_END);

        foreach ($daySchedule as $item) {
            $itemStart = Carbon::parse($item['start_time']);
            $itemEnd = Carbon::parse($item['end_time']);

            // Check if class overlaps with lunch time
            if ($this->timeSlotsOverlap(
                $itemStart->format('H:i'),
                $itemEnd->format('H:i'),
                $lunchStart->format('H:i'),
                $lunchEnd->format('H:i')
            )) {
                return false; // Lunch break violated
            }
        }

        return true;
    }

    /**
     * Check for instructor time conflicts on same day
     */
    public function checkInstructorTimeConflict(
        int $instructorId,
        string $day,
        string $startTime,
        string $endTime,
        Collection $existingSchedule
    ): bool {
        foreach ($existingSchedule as $gene) {
            if ($gene['instructor_id'] == $instructorId && $gene['day'] == $day) {
                if ($this->timeSlotsOverlap($startTime, $endTime, $gene['start_time'], $gene['end_time'])) {
                    return true; // Conflict found
                }
            }
        }

        return false; // No conflict
    }

    /**
     * Check for section time conflicts (students can't be in two places at once)
     */
    public function checkSectionTimeConflict(
        string $section,
        string $day,
        string $startTime,
        string $endTime,
        Collection $existingSchedule
    ): bool {
        foreach ($existingSchedule as $gene) {
            if ($gene['section'] == $section && $gene['day'] == $day) {
                if ($this->timeSlotsOverlap($startTime, $endTime, $gene['start_time'], $gene['end_time'])) {
                    return true; // Conflict found
                }
            }
        }

        return false; // No conflict
    }

    /**
     * Calculate total penalty for a gene (schedule item)
     */
    public function calculateGenePenalty(
        array $gene,
        Collection $existingSchedule,
        array $facultyLoads
    ): int {
        $penalty = 0;

        // Load instructor
        $instructor = User::find($gene['instructor_id']);
        if (!$instructor) {
            return 1000; // Invalid instructor
        }

        // Check scheme violation
        $penalty += $this->getSchemeViolationPenalty(
            $instructor,
            $gene['start_time'],
            $gene['end_time']
        );

        // Check room conflict
        $penalty += $this->getRoomConflictPenalty(
            $gene['room_id'],
            $gene['day'],
            $gene['start_time'],
            $gene['end_time'],
            $existingSchedule
        );

        // Check instructor time conflict
        if ($this->checkInstructorTimeConflict(
            $gene['instructor_id'],
            $gene['day'],
            $gene['start_time'],
            $gene['end_time'],
            $existingSchedule
        )) {
            $penalty += self::PENALTY_TIME_OVERLAP;
        }

        // Check section time conflict
        if ($this->checkSectionTimeConflict(
            $gene['section'],
            $gene['day'],
            $gene['start_time'],
            $gene['end_time'],
            $existingSchedule
        )) {
            $penalty += self::PENALTY_SECTION_OVERLAP;
        }

        return $penalty;
    }
}
