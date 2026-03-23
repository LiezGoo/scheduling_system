<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subject;
use App\Models\InstructorLoad;
use App\Models\ScheduleItem;
use App\Models\FacultyWorkloadConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * FacultyConstraintValidator
 *
 * Validates faculty-related constraints for schedule generation:
 * - Subject-to-faculty assignment mapping
 * - Faculty availability (time slots and daily scheme)
 * - Faculty workload limits
 * - Time conflicts and overlaps
 */
class FacultyConstraintValidator
{
    // Penalties for constraint violations
    public const PENALTY_WRONG_FACULTY = 100;          // Subject assigned to wrong instructor
    public const PENALTY_OUTSIDE_AVAILABILITY = 80;    // Scheduled outside faculty availability
    public const PENALTY_OVERLAPPING_SCHEDULE = 90;    // Faculty has time conflict
    public const PENALTY_OVERLOAD = 70;                // Exceeds faculty workload limit

    protected ConstraintValidator $baseValidator;

    public function __construct(ConstraintValidator $baseValidator = null)
    {
        $this->baseValidator = $baseValidator ?? new ConstraintValidator();
    }

    /**
     * Get valid instructors assigned to a specific subject.
     *
     * Returns all instructors who are assigned to teach this subject
     * in the given academic year and semester.
     *
     * @param Subject $subject
     * @param int $academicYearId
     * @param string $semester
     * @return Collection
     */
    public function getValidInstructorsForSubject(
        Subject $subject,
        int $academicYearId,
        string $semester
    ): Collection {
        return InstructorLoad::where('subject_id', $subject->id)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->with('instructor')
            ->get()
            ->pluck('instructor')
            ->filter(fn ($instructor) => $instructor && !$instructor->trashed())
            ->values();
    }

    /**
     * Check if an instructor is assigned to teach a specific subject.
     *
     * @param User $instructor
     * @param Subject $subject
     * @param int $academicYearId
     * @param string $semester
     * @return bool
     */
    public function isInstructorAssignedToSubject(
        User $instructor,
        Subject $subject,
        int $academicYearId,
        string $semester
    ): bool {
        return InstructorLoad::where('instructor_id', $instructor->id)
            ->where('subject_id', $subject->id)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->exists();
    }

    /**
     * Check if faculty is available during a specific time slot.
     *
     * Verifies that the time slot falls within the faculty's
     * configured teaching scheme for that day.
     *
     * @param User $instructor
     * @param string $day (Monday, Tuesday, ...)
     * @param string $startTime (HH:mm)
     * @param string $endTime (HH:mm)
     * @param int|null $programId
     * @return bool
     */
    public function isFacultyAvailableAtTimeSlot(
        User $instructor,
        string $day,
        string $startTime,
        string $endTime,
        ?int $programId = null
    ): bool {
        $config = $this->getInstructorWorkloadConfiguration($instructor->id, $programId);

        if (!$config) {
            // No configuration means faculty is available (default behavior)
            return true;
        }

        // Check if day is in available_days
        $availableDays = $config->available_days ?? [];
        if (!empty($availableDays) && !in_array($day, $availableDays)) {
            return false;
        }

        // Check if time falls within teaching scheme
        $scheme = $config->teaching_scheme ?? [];
        if (empty($scheme)) {
            // No scheme defined, assume available any time
            return true;
        }

        // Check the specific day's time range
        if (!isset($scheme[$day])) {
            return false;
        }

        $dayScheme = $scheme[$day];
        $slotStart = $this->normalizeTime($startTime);
        $slotEnd = $this->normalizeTime($endTime);
        $schemeStart = $this->normalizeTime($dayScheme['start'] ?? '07:00');
        $schemeEnd = $this->normalizeTime($dayScheme['end'] ?? '19:00');

        return $slotStart >= $schemeStart && $slotEnd <= $schemeEnd;
    }

    /**
     * Check if faculty has time conflicts for a proposed schedule.
     *
     * Verifies that the instructor is not already scheduled
     * at this time on the same day.
     *
     * @param User $instructor
     * @param string $day
     * @param string $startTime
     * @param string $endTime
     * @param Collection $existingGenes (schedule items already assigned)
     * @return bool
     */
    public function hasFacultyTimeConflict(
        User $instructor,
        string $day,
        string $startTime,
        string $endTime,
        Collection $existingGenes
    ): bool {
        $slotStart = Carbon::createFromFormat('H:i', $startTime);
        $slotEnd = Carbon::createFromFormat('H:i', $endTime);

        foreach ($existingGenes as $gene) {
            if ($gene['instructor_id'] !== $instructor->id) {
                continue;
            }

            if (strtolower($gene['day']) !== strtolower($day)) {
                continue;
            }

            $geneStart = Carbon::createFromFormat('H:i', $gene['start_time']);
            $geneEnd = Carbon::createFromFormat('H:i', $gene['end_time']);

            // Check for overlap
            if ($slotStart < $geneEnd && $slotEnd > $geneStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get total assigned hours for a faculty member.
     *
     * Sums up lecture and lab hours from existing genes.
     *
     * @param User $instructor
     * @param Collection $existingGenes
     * @return array ['lecture' => float, 'lab' => float]
     */
    public function getFacultyAssignedHours(
        User $instructor,
        Collection $existingGenes
    ): array {
        $hours = ['lecture' => 0.0, 'lab' => 0.0];

        foreach ($existingGenes as $gene) {
            if ($gene['instructor_id'] !== $instructor->id) {
                continue;
            }

            $type = strtolower($gene['type'] ?? 'lecture');
            $duration = (float) ($gene['duration'] ?? 0);

            if ($type === 'lab') {
                $hours['lab'] += $duration;
            } else {
                $hours['lecture'] += $duration;
            }
        }

        return $hours;
    }

    /**
     * Check if assigning additional hours would exceed faculty workload limit.
     *
     * @param User $instructor
     * @param string $type ('lecture' or 'lab')
     * @param float $additionalHours
     * @param Collection $existingGenes
     * @param int|null $programId
     * @return bool
     */
    public function wouldExceedWorkloadLimit(
        User $instructor,
        string $type,
        float $additionalHours,
        Collection $existingGenes,
        ?int $programId = null
    ): bool {
        $config = $this->getInstructorWorkloadConfiguration($instructor->id, $programId);

        if (!$config) {
            // No limits configured
            return false;
        }

        $assigned = $this->getFacultyAssignedHours($instructor, $existingGenes);
        $type = strtolower($type);

        if ($type === 'lab') {
            $limit = $config->max_lab_hours;
            $current = $assigned['lab'];
        } else {
            $limit = $config->max_lecture_hours;
            $current = $assigned['lecture'];
        }

        if ($limit === null) {
            return false;
        }

        return ($current + $additionalHours) > $limit;
    }

    /**
     * Calculate penalty for faculty constraint violations.
     *
     * @param User $instructor
     * @param Subject $subject
     * @param string $day
     * @param string $startTime
     * @param string $endTime
     * @param Collection $existingGenes
     * @param int $academicYearId
     * @param string $semester
     * @param int|null $programId
     * @return float
     */
    public function calculateFacultyConstraintPenalty(
        User $instructor,
        Subject $subject,
        string $day,
        string $startTime,
        string $endTime,
        Collection $existingGenes,
        int $academicYearId,
        string $semester,
        ?int $programId = null
    ): float {
        $penalty = 0.0;

        // Check 1: Is instructor assigned to this subject?
        if (!$this->isInstructorAssignedToSubject($instructor, $subject, $academicYearId, $semester)) {
            $penalty += self::PENALTY_WRONG_FACULTY;
        }

        // Check 2: Is instructor available at this time?
        if (!$this->isFacultyAvailableAtTimeSlot($instructor, $day, $startTime, $endTime, $programId)) {
            $penalty += self::PENALTY_OUTSIDE_AVAILABILITY;
        }

        // Check 3: Does instructor have time conflicts?
        if ($this->hasFacultyTimeConflict($instructor, $day, $startTime, $endTime, $existingGenes)) {
            $penalty += self::PENALTY_OVERLAPPING_SCHEDULE;
        }

        return $penalty;
    }

    /**
     * Validate a proposed gene assignment against faculty constraints.
     *
     * Returns a validation result array with 'valid' boolean and optional 'penalty' float.
     *
     * @param array $gene
     * @param User $instructor
     * @param Subject $subject
     * @param Collection $existingGenes
     * @param int $academicYearId
     * @param string $semester
     * @param int|null $programId
     * @return array ['valid' => bool, 'penalty' => float, 'reasons' => array]
     */
    public function validateGeneAssignment(
        array $gene,
        User $instructor,
        Subject $subject,
        Collection $existingGenes,
        int $academicYearId,
        string $semester,
        ?int $programId = null
    ): array {
        $reasons = [];
        $penalty = 0.0;

        // Check 1: Instructor assigned to subject
        if (!$this->isInstructorAssignedToSubject($instructor, $subject, $academicYearId, $semester)) {
            $reasons[] = 'Instructor not assigned to this subject';
            $penalty += self::PENALTY_WRONG_FACULTY;
        }

        // Check 2: Availability check
        if (!$this->isFacultyAvailableAtTimeSlot(
            $instructor,
            $gene['day'],
            $gene['start_time'],
            $gene['end_time'],
            $programId
        )) {
            $reasons[] = sprintf(
                'Instructor not available on %s from %s to %s',
                $gene['day'],
                $gene['start_time'],
                $gene['end_time']
            );
            $penalty += self::PENALTY_OUTSIDE_AVAILABILITY;
        }

        // Check 3: Time conflict check
        if ($this->hasFacultyTimeConflict(
            $instructor,
            $gene['day'],
            $gene['start_time'],
            $gene['end_time'],
            $existingGenes
        )) {
            $reasons[] = sprintf(
                'Instructor has conflicting schedule on %s from %s to %s',
                $gene['day'],
                $gene['start_time'],
                $gene['end_time']
            );
            $penalty += self::PENALTY_OVERLAPPING_SCHEDULE;
        }

        // Check 4: Workload limit check
        $type = strtolower($gene['type'] ?? 'lecture');
        $duration = (float) ($gene['duration'] ?? 0);

        if ($this->wouldExceedWorkloadLimit(
            $instructor,
            $type,
            $duration,
            $existingGenes,
            $programId
        )) {
            $reasons[] = sprintf(
                'Assignment would exceed %s workload limit',
                $type
            );
            $penalty += self::PENALTY_OVERLOAD;
        }

        $valid = empty($reasons);

        return [
            'valid' => $valid,
            'penalty' => $penalty,
            'reasons' => $reasons,
        ];
    }

    /**
     * Get instructor workload configuration.
     *
     * Lookup hierarchy:
     * 1. Program-specific configuration (if programId provided)
     * 2. Latest active general configuration
     * 3. None if no config found
     *
     * @param int $instructorId
     * @param int|null $programId
     * @return FacultyWorkloadConfiguration|null
     */
    protected function getInstructorWorkloadConfiguration(
        int $instructorId,
        ?int $programId = null
    ): ?FacultyWorkloadConfiguration {
        $query = FacultyWorkloadConfiguration::where('user_id', $instructorId)
            ->where('is_active', true);

        if ($programId) {
            $config = $query->where('program_id', $programId)->latest()->first();
            if ($config) {
                return $config;
            }
        }

        return $query->whereNull('program_id')->latest()->first();
    }

    /**
     * Normalize time string to Carbon instance.
     *
     * @param string $time
     * @return Carbon
     */
    protected function normalizeTime(string $time): Carbon
    {
        if (Carbon::hasFormat($time, 'H:i') || Carbon::hasFormat($time, 'H:i:s')) {
            return Carbon::createFromFormat('H:i', substr($time, 0, 5));
        }

        return Carbon::createFromFormat('H:i', '00:00');
    }
}
