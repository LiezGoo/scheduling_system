<?php

namespace App\Services;

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
     * Check if time slot is within instructor's daily scheme
     */
    public function isWithinInstructorScheme(User $instructor, string $startTime, string $endTime): bool
    {
        if (!$instructor->daily_scheme_start || !$instructor->daily_scheme_end) {
            // If no scheme defined, assume standard 7 AM - 7 PM
            return true;
        }

        $schemeStart = Carbon::parse($instructor->daily_scheme_start);
        $schemeEnd = Carbon::parse($instructor->daily_scheme_end);
        $slotStart = Carbon::parse($startTime);
        $slotEnd = Carbon::parse($endTime);

        return $slotStart->greaterThanOrEqualTo($schemeStart) 
            && $slotEnd->lessThanOrEqualTo($schemeEnd);
    }

    /**
     * Calculate penalty if instructor scheme is violated
     */
    public function getSchemeViolationPenalty(User $instructor, string $startTime, string $endTime): int
    {
        if ($this->isWithinInstructorScheme($instructor, $startTime, $endTime)) {
            return 0;
        }

        // Calculate how many minutes the slot extends beyond the scheme
        $schemeStart = Carbon::parse($instructor->daily_scheme_start ?? '07:00');
        $schemeEnd = Carbon::parse($instructor->daily_scheme_end ?? '19:00');
        $slotStart = Carbon::parse($startTime);
        $slotEnd = Carbon::parse($endTime);

        $violationMinutes = 0;

        if ($slotStart->lessThan($schemeStart)) {
            $violationMinutes += $schemeStart->diffInMinutes($slotStart);
        }

        if ($slotEnd->greaterThan($schemeEnd)) {
            $violationMinutes += $slotEnd->diffInMinutes($schemeEnd);
        }

        // Penalty increases with violation severity
        return self::PENALTY_SCHEME_VIOLATION + ($violationMinutes / 10);
    }

    /**
     * Validate faculty load limits based on contract type
     */
    public function validateFacultyLoad(User $instructor, float $lectureHours, float $labHours): array
    {
        $totalHours = $lectureHours + $labHours;
        $violations = [];

        // Determine limits based on contract type
        if ($instructor->contract_type === User::CONTRACT_PERMANENT) {
            // Permanent: Lecture ≤ 18, Lab ≤ 21
            if ($lectureHours > User::MAX_LECTURE_HOURS_PERMANENT) {
                $violations[] = [
                    'type' => 'lecture_overload',
                    'limit' => User::MAX_LECTURE_HOURS_PERMANENT,
                    'actual' => $lectureHours,
                    'excess' => $lectureHours - User::MAX_LECTURE_HOURS_PERMANENT,
                ];
            }

            if ($labHours > User::MAX_LAB_HOURS_PERMANENT) {
                $violations[] = [
                    'type' => 'lab_overload',
                    'limit' => User::MAX_LAB_HOURS_PERMANENT,
                    'actual' => $labHours,
                    'excess' => $labHours - User::MAX_LAB_HOURS_PERMANENT,
                ];
            }
        } elseif ($instructor->employment_type === User::EMPLOYMENT_CONTRACT_27) {
            // Contract 27: Total ≤ 27
            if ($totalHours > User::MAX_HOURS_CONTRACT_27) {
                $violations[] = [
                    'type' => 'total_overload',
                    'limit' => User::MAX_HOURS_CONTRACT_27,
                    'actual' => $totalHours,
                    'excess' => $totalHours - User::MAX_HOURS_CONTRACT_27,
                ];
            }
        } elseif ($instructor->employment_type === User::EMPLOYMENT_CONTRACT_24) {
            // Contract 24: Total ≤ 24
            if ($totalHours > User::MAX_HOURS_CONTRACT_24) {
                $violations[] = [
                    'type' => 'total_overload',
                    'limit' => User::MAX_HOURS_CONTRACT_24,
                    'actual' => $totalHours,
                    'excess' => $totalHours - User::MAX_HOURS_CONTRACT_24,
                ];
            }
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
