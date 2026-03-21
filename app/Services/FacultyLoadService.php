<?php

namespace App\Services;

use App\Models\InstructorLoad;
use App\Models\User;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use App\Models\AcademicYear;
use App\Models\FacultyWorkloadConfiguration;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Faculty Load Management Service
 *
 * Handles all business logic for managing faculty subject assignments
 * and teaching load constraints. This module operates independently
 * from scheduling logic and prepares data for the Schedule Generation module.
 *
 * @package App\Services
 */
class FacultyLoadService
{
    protected ConstraintValidator $constraintValidator;

    public function __construct(ConstraintValidator $constraintValidator)
    {
        $this->constraintValidator = $constraintValidator;
    }

    /**
     * Normalize and validate assignment hour values.
     */
    private function validateAssignmentHours(int $lectureHours, int $labHours): ?string
    {
        if ($lectureHours <= 0 && $labHours <= 0) {
            return 'Either lecture hours or laboratory hours must be greater than zero.';
        }

        if ($labHours > 0 && $labHours % 3 !== 0) {
            return 'Laboratory hours must be divisible by 3.';
        }

        return null;
    }

    /**
     * Validate assignment against Faculty Workload Configuration limits.
     *
     * @return array<string, mixed>
     */
    public function validateConfigurationDrivenAssignment(
        User $user,
        Subject $subject,
        int $programId,
        int $academicYearId,
        string $semester,
        int $yearLevel,
        string $blockSection,
        int $additionalLectureHours,
        int $additionalLabHours,
        ?int $excludeLoadId = null
    ): array {
        $config = FacultyWorkloadConfiguration::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where(function ($query) use ($programId) {
                $query->where('program_id', $programId)
                    ->orWhereNull('program_id');
            })
            ->orderByRaw('CASE WHEN program_id = ? THEN 0 ELSE 1 END', [$programId])
            ->latest('id')
            ->first();

        if (!$config) {
            return [
                'valid' => false,
                'message' => 'Faculty workload configuration is not set. Please configure workload limits and availability first.',
                'current' => [
                    'lecture_hours' => 0,
                    'lab_hours' => 0,
                    'total_hours' => 0,
                ],
                'new' => [
                    'lecture_hours' => $additionalLectureHours,
                    'lab_hours' => $additionalLabHours,
                    'total_hours' => $additionalLectureHours + $additionalLabHours,
                ],
                'limits' => [
                    'max_lecture_hours' => null,
                    'max_lab_hours' => null,
                    'max_hours_per_day' => null,
                ],
                'availability' => [],
                'availability_conflicts' => [],
                'daily_hours_conflicts' => [],
                'schedule_slots_checked' => false,
            ];
        }

        $current = $user->getInstructorLoadSummaryForTerm($academicYearId, $semester, $excludeLoadId);
        $currentLectureHours = (int) ($current['total_lecture_hours'] ?? 0);
        $currentLabHours = (int) ($current['total_lab_hours'] ?? 0);

        $newLectureHours = $currentLectureHours + $additionalLectureHours;
        $newLabHours = $currentLabHours + $additionalLabHours;

        $maxLectureHours = $config->max_lecture_hours !== null ? (int) $config->max_lecture_hours : null;
        $maxLabHours = $config->max_lab_hours !== null ? (int) $config->max_lab_hours : null;
        $maxHoursPerDay = $config->max_hours_per_day !== null ? (int) $config->max_hours_per_day : null;

        $limits = [
            'max_lecture_hours' => $maxLectureHours,
            'max_lab_hours' => $maxLabHours,
            'max_hours_per_day' => $maxHoursPerDay,
        ];

        $maxTotalLoad = ($maxLectureHours !== null || $maxLabHours !== null)
            ? (int) (($maxLectureHours ?? 0) + ($maxLabHours ?? 0))
            : null;
        $lectureOverload = $maxLectureHours === null ? 0 : max(0, $newLectureHours - $maxLectureHours);
        $labOverload = $maxLabHours === null ? 0 : max(0, $newLabHours - $maxLabHours);
        $overloadHours = $lectureOverload + $labOverload;
        $isOverloaded = $overloadHours > 0;
        $workloadStatus = $isOverloaded ? 'Overloaded' : 'Normal';

        $subjectScheduleSlots = $this->getSubjectScheduleSlotsForTerm(
            $subject->id,
            $programId,
            $academicYearId,
            $semester,
            $yearLevel,
            $blockSection
        );

        $availabilityConflicts = [];
        $dailyHourConflicts = [];

        if (!empty($subjectScheduleSlots)) {
            $subjectHoursByDay = [];
            foreach ($subjectScheduleSlots as $slot) {
                $subjectHoursByDay[$slot['day']] = ($subjectHoursByDay[$slot['day']] ?? 0) + $slot['duration_hours'];

                if (!$this->constraintValidator->isWithinInstructorScheme($user, $slot['start_time'], $slot['end_time'], $slot['day'], $programId)) {
                    $allowedRange = $this->constraintValidator->getAllowedRangeForDay($user->id, $slot['day'], $programId);
                    $availabilityConflicts[] = [
                        'day' => $slot['day'],
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'allowed_range' => $allowedRange,
                        'message' => 'Selected subject schedule conflicts with faculty availability.',
                    ];
                }
            }

            if ($maxHoursPerDay !== null) {
                $currentDailyHours = $this->getInstructorDailyHoursForTerm($user->id, $academicYearId, $semester);

                foreach ($subjectHoursByDay as $day => $subjectDayHours) {
                    $currentHours = (float) ($currentDailyHours[$day] ?? 0);
                    $projectedHours = $currentHours + (float) $subjectDayHours;

                    if ($projectedHours > $maxHoursPerDay) {
                        $dailyHourConflicts[] = [
                            'day' => $day,
                            'current_hours' => round($currentHours, 2),
                            'subject_hours' => round((float) $subjectDayHours, 2),
                            'projected_hours' => round($projectedHours, 2),
                            'max_hours_per_day' => $maxHoursPerDay,
                            'message' => "Daily teaching hour limit exceeded on {$day}.",
                        ];
                    }
                }
            }
        }

        if (!empty($availabilityConflicts)) {
            return [
                'valid' => false,
                'message' => 'Selected subject schedule conflicts with faculty availability.',
                'current' => [
                    'lecture_hours' => $currentLectureHours,
                    'lab_hours' => $currentLabHours,
                    'total_hours' => $currentLectureHours + $currentLabHours,
                ],
                'new' => [
                    'lecture_hours' => $newLectureHours,
                    'lab_hours' => $newLabHours,
                    'total_hours' => $newLectureHours + $newLabHours,
                ],
                'limits' => $limits,
                'max_load' => $maxTotalLoad,
                'total_assigned_hours' => $newLectureHours + $newLabHours,
                'overload_hours' => $overloadHours,
                'is_overloaded' => $isOverloaded,
                'workload_status' => $workloadStatus,
                'availability' => $this->formatAvailabilityRows($config),
                'availability_conflicts' => $availabilityConflicts,
                'daily_hours_conflicts' => [],
                'schedule_slots_checked' => true,
            ];
        }

        if (!empty($dailyHourConflicts)) {
            return [
                'valid' => false,
                'message' => 'Maximum teaching hours per day would be exceeded.',
                'current' => [
                    'lecture_hours' => $currentLectureHours,
                    'lab_hours' => $currentLabHours,
                    'total_hours' => $currentLectureHours + $currentLabHours,
                ],
                'new' => [
                    'lecture_hours' => $newLectureHours,
                    'lab_hours' => $newLabHours,
                    'total_hours' => $newLectureHours + $newLabHours,
                ],
                'limits' => $limits,
                'max_load' => $maxTotalLoad,
                'total_assigned_hours' => $newLectureHours + $newLabHours,
                'overload_hours' => $overloadHours,
                'is_overloaded' => $isOverloaded,
                'workload_status' => $workloadStatus,
                'availability' => $this->formatAvailabilityRows($config),
                'availability_conflicts' => [],
                'daily_hours_conflicts' => $dailyHourConflicts,
                'schedule_slots_checked' => true,
            ];
        }

        return [
            'valid' => true,
            'message' => $isOverloaded
                ? "Faculty load exceeds configured limits by {$overloadHours} hour(s). Overload is allowed."
                : 'Faculty load is within configured workload limits.',
            'current' => [
                'lecture_hours' => $currentLectureHours,
                'lab_hours' => $currentLabHours,
                'total_hours' => $currentLectureHours + $currentLabHours,
            ],
            'new' => [
                'lecture_hours' => $newLectureHours,
                'lab_hours' => $newLabHours,
                'total_hours' => $newLectureHours + $newLabHours,
            ],
            'limits' => $limits,
            'max_load' => $maxTotalLoad,
            'total_assigned_hours' => $newLectureHours + $newLabHours,
            'overload_hours' => $overloadHours,
            'is_overloaded' => $isOverloaded,
            'workload_status' => $workloadStatus,
            'availability' => $this->formatAvailabilityRows($config),
            'availability_conflicts' => [],
            'daily_hours_conflicts' => [],
            'schedule_slots_checked' => !empty($subjectScheduleSlots),
        ];
    }

    /**
     * Build assignment context details for real-time UI summary.
     *
     * @return array<string, mixed>
     */
    public function getAssignmentContextData(
        int $userId,
        int $subjectId,
        int $programId,
        int $academicYearId,
        string $semester,
        int $yearLevel,
        string $blockSection
    ): array {
        $user = User::findOrFail($userId);
        $subject = Subject::findOrFail($subjectId);

        $lectureHours = (int) round((float) ($subject->lecture_hours ?? 0));
        $labHours = (int) round((float) ($subject->lab_hours ?? 0));

        $validation = $this->validateConfigurationDrivenAssignment(
            $user,
            $subject,
            $programId,
            $academicYearId,
            $semester,
            $yearLevel,
            $blockSection,
            $lectureHours,
            $labHours
        );

        $limits = $validation['limits'] ?? [];
        $current = $validation['current'] ?? [];
        $new = $validation['new'] ?? [];

        $maxLectureHours = $limits['max_lecture_hours'] ?? null;
        $maxLabHours = $limits['max_lab_hours'] ?? null;

        return [
            'subject' => [
                'id' => $subject->id,
                'subject_code' => $subject->subject_code,
                'subject_name' => $subject->subject_name,
                'lecture_hours' => $lectureHours,
                'lab_hours' => $labHours,
            ],
            'load_summary' => [
                'current_lecture_hours' => (int) ($current['lecture_hours'] ?? 0),
                'current_lab_hours' => (int) ($current['lab_hours'] ?? 0),
                'projected_lecture_hours' => (int) ($new['lecture_hours'] ?? 0),
                'projected_lab_hours' => (int) ($new['lab_hours'] ?? 0),
                'total_assigned_hours' => (int) ($validation['total_assigned_hours'] ?? (($new['lecture_hours'] ?? 0) + ($new['lab_hours'] ?? 0))),
                'max_load' => $validation['max_load'] ?? null,
                'overload_hours' => (int) ($validation['overload_hours'] ?? 0),
                'workload_status' => $validation['workload_status'] ?? 'Normal',
                'max_lecture_hours' => $maxLectureHours,
                'max_lab_hours' => $maxLabHours,
                'remaining_lecture_hours' => $maxLectureHours === null ? null : ($maxLectureHours - (int) ($new['lecture_hours'] ?? 0)),
                'remaining_lab_hours' => $maxLabHours === null ? null : ($maxLabHours - (int) ($new['lab_hours'] ?? 0)),
                'max_hours_per_day' => $limits['max_hours_per_day'] ?? null,
            ],
            'availability' => $validation['availability'] ?? [],
            'warnings' => [
                'availability' => $validation['availability_conflicts'] ?? [],
                'daily_hours' => $validation['daily_hours_conflicts'] ?? [],
                'general' => $validation['valid']
                    ? (($validation['is_overloaded'] ?? false)
                        ? [['message' => $validation['message'] ?? 'Assignment is overloaded but allowed.']]
                        : [])
                    : [
                        ['message' => $validation['message'] ?? 'Assignment validation failed.'],
                    ],
            ],
            'can_assign' => (bool) ($validation['valid'] ?? false),
            'validation' => $validation,
        ];
    }

    /**
     * @return array<int, array{day:string,start_time:string,end_time:string}>
     */
    private function formatAvailabilityRows(FacultyWorkloadConfiguration $config): array
    {
        $teachingScheme = is_array($config->teaching_scheme) ? $config->teaching_scheme : [];
        $rows = [];

        foreach ($teachingScheme as $day => $slot) {
            $enabled = (bool) ($slot['enabled'] ?? true);
            $start = isset($slot['start']) ? trim((string) $slot['start']) : '';
            $end = isset($slot['end']) ? trim((string) $slot['end']) : '';

            if (!$enabled || $start === '' || $end === '') {
                continue;
            }

            $rows[] = [
                'day' => (string) $day,
                'start_time' => $this->formatTimeForDisplay($start),
                'end_time' => $this->formatTimeForDisplay($end),
            ];
        }

        return $rows;
    }

    private function formatTimeForDisplay(string $time): string
    {
        try {
            return Carbon::parse($time)->format('g:i A');
        } catch (\Throwable $e) {
            return $time;
        }
    }

    /**
     * @return array<int, array{day:string,start_time:string,end_time:string,duration_hours:float}>
     */
    private function getSubjectScheduleSlotsForTerm(
        int $subjectId,
        int $programId,
        int $academicYearId,
        string $semester,
        int $yearLevel,
        string $blockSection
    ): array {
        $academicYear = AcademicYear::find($academicYearId);
        if (!$academicYear) {
            return [];
        }

        $items = ScheduleItem::query()
            ->select('schedule_items.day_of_week', 'schedule_items.start_time', 'schedule_items.end_time')
            ->join('schedules', 'schedule_items.schedule_id', '=', 'schedules.id')
            ->where('schedule_items.subject_id', $subjectId)
            ->where('schedules.program_id', $programId)
            ->where('schedules.academic_year', $academicYear->name)
            ->where('schedules.semester', $semester)
            ->where('schedules.year_level', $yearLevel)
            ->where('schedules.block', $blockSection)
            ->whereIn('schedules.status', [
                Schedule::STATUS_DRAFT,
                Schedule::STATUS_GENERATED,
                Schedule::STATUS_FINALIZED,
            ])
            ->get();

        return $items->map(function ($item) {
            $start = Carbon::parse((string) $item->start_time);
            $end = Carbon::parse((string) $item->end_time);
            $durationHours = $start->diffInMinutes($end) / 60;

            return [
                'day' => (string) $item->day_of_week,
                'start_time' => $start->format('H:i'),
                'end_time' => $end->format('H:i'),
                'duration_hours' => $durationHours,
            ];
        })->all();
    }

    /**
     * @return array<string, float>
     */
    private function getInstructorDailyHoursForTerm(int $instructorId, int $academicYearId, string $semester): array
    {
        $academicYear = AcademicYear::find($academicYearId);
        if (!$academicYear) {
            return [];
        }

        $items = ScheduleItem::query()
            ->select('schedule_items.day_of_week', 'schedule_items.start_time', 'schedule_items.end_time')
            ->join('schedules', 'schedule_items.schedule_id', '=', 'schedules.id')
            ->where('schedule_items.instructor_id', $instructorId)
            ->where('schedules.academic_year', $academicYear->name)
            ->where('schedules.semester', $semester)
            ->whereIn('schedules.status', [
                Schedule::STATUS_DRAFT,
                Schedule::STATUS_GENERATED,
                Schedule::STATUS_FINALIZED,
            ])
            ->get();

        $hoursByDay = [];
        foreach ($items as $item) {
            $start = Carbon::parse((string) $item->start_time);
            $end = Carbon::parse((string) $item->end_time);
            $durationHours = $start->diffInMinutes($end) / 60;
            $day = (string) $item->day_of_week;
            $hoursByDay[$day] = ($hoursByDay[$day] ?? 0) + $durationHours;
        }

        return $hoursByDay;
    }

    /**
     * Get all eligible instructors.
     * Eligible roles: instructor, program_head, department_head
     */
    public function getEligibleInstructors(): Collection
    {
        return User::eligibleInstructors()
                   ->active()
                   ->orderBy('first_name')
                   ->orderBy('last_name')
                   ->get();
    }

    /**
     * Get eligible instructors with their assigned subjects.
     */
    public function getEligibleInstructorsWithSubjects(): Collection
    {
        return User::eligibleInstructors()
                   ->active()
                   ->with('instructorLoads')
                   ->orderBy('first_name')
                   ->orderBy('last_name')
                   ->get();
    }

    /**
     * Assign a subject to an instructor with lecture and lab hours.
     *
     * @param int $userId The user (instructor) ID
     * @param int $subjectId The subject ID
     * @param int $lectureHours Number of lecture hours per week
     * @param int $labHours Number of laboratory hours per week
     * @param int|null $maxLoadUnits Optional override for max load units
     * @return array Status and message
     */
    public function assignSubjectToInstructor(
        int $userId,
        int $subjectId,
        int $programId,
        int $academicYearId,
        string $semester,
        int $yearLevel,
        string $blockSection,
        int $lectureHours = 0,
        int $labHours = 0,
        bool $forceAssign = false
    ): array {
        try {
            // Validate instructor eligibility
            $user = User::findOrFail($userId);
            if (!$user->isEligibleInstructor()) {
                return [
                    'success' => false,
                    'message' => "User {$user->full_name} is not an eligible instructor.",
                ];
            }

            // Validate subject exists
            $subject = Subject::findOrFail($subjectId);

            $hoursValidationError = $this->validateAssignmentHours($lectureHours, $labHours);
            if ($hoursValidationError) {
                return [
                    'success' => false,
                    'message' => $hoursValidationError,
                ];
            }

            // Check if assignment already exists
            $exists = InstructorLoad::query()
                ->where('instructor_id', $userId)
                ->where('subject_id', $subjectId)
                ->where('program_id', $programId)
                ->where('academic_year_id', $academicYearId)
                ->where('semester', $semester)
                ->where('year_level', $yearLevel)
                ->where('block_section', $blockSection)
                ->exists();

            if ($exists) {
                return [
                    'success' => false,
                    'message' => 'This subject is already assigned to this instructor.',
                ];
            }

            // Validate assignment against configured faculty workload limits and availability
            $loadValidation = $this->validateConfigurationDrivenAssignment(
                $user,
                $subject,
                $programId,
                $academicYearId,
                $semester,
                $yearLevel,
                $blockSection,
                $lectureHours,
                $labHours
            );
            if (!$loadValidation['valid'] && !$forceAssign) {
                return [
                    'success' => false,
                    'message' => $loadValidation['message'],
                    'code' => 'validation_failed',
                    'validation_details' => $loadValidation,
                ];
            }

            $totalHours = $lectureHours + $labHours;

            InstructorLoad::create([
                'instructor_id' => $userId,
                'program_id' => $programId,
                'subject_id' => $subjectId,
                'academic_year_id' => $academicYearId,
                'semester' => $semester,
                'year_level' => $yearLevel,
                'block_section' => $blockSection,
                'lec_hours' => $lectureHours,
                'lab_hours' => $labHours,
                'total_hours' => $totalHours,
            ]);

            return [
                'success' => true,
                'message' => "{$user->full_name} has been assigned to {$subject->subject_name} ({$totalHours} total hours).",
                'warning' => ($loadValidation['is_overloaded'] ?? false) ? $loadValidation : null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Error assigning subject: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Assign multiple subjects to an instructor in a single transaction.
     *
     * All-or-nothing behavior: if any assignment is invalid, no record is saved.
     *
     * @param array<int, array<string, mixed>> $subjects
     */
    public function assignMultipleSubjectsToInstructor(
        int $userId,
        int $programId,
        int $academicYearId,
        string $semester,
        int $yearLevel,
        array $subjects,
        bool $forceAssign = false
    ): array {
        try {
            $user = User::findOrFail($userId);
            if (!$user->isEligibleInstructor()) {
                return [
                    'success' => false,
                    'message' => "User {$user->full_name} is not an eligible instructor.",
                ];
            }

            if (empty($subjects)) {
                return [
                    'success' => false,
                    'message' => 'At least one subject must be selected.',
                    'errors' => ['subjects' => ['At least one subject must be selected.']],
                ];
            }

            $errors = [];
            $payloadDuplicateKeys = [];
            $preparedAssignments = [];
            $runningLectureHours = 0;
            $runningLabHours = 0;

            foreach ($subjects as $index => $subjectData) {
                $subjectId = (int) ($subjectData['subject_id'] ?? 0);
                $blockSection = trim((string) ($subjectData['block'] ?? $subjectData['block_section'] ?? ''));
                $lectureHours = (int) ($subjectData['lecture_hours'] ?? 0);
                $labHours = (int) ($subjectData['lab_hours'] ?? 0);

                if ($subjectId <= 0) {
                    $errors[$index] = [
                        'subject_id' => 'Invalid subject selected.',
                    ];
                    continue;
                }

                if ($blockSection === '') {
                    $errors[$index] = [
                        'block' => 'Block/Section is required.',
                    ];
                    continue;
                }

                $subject = Subject::find($subjectId);
                if (!$subject) {
                    $errors[$index] = [
                        'subject_id' => 'Subject not found.',
                    ];
                    continue;
                }

                $hoursValidationError = $this->validateAssignmentHours($lectureHours, $labHours);
                if ($hoursValidationError) {
                    $errors[$index] = [
                        'hours' => $hoursValidationError,
                    ];
                    continue;
                }

                $payloadKey = implode('|', [$subjectId, strtolower($blockSection)]);
                if (isset($payloadDuplicateKeys[$payloadKey])) {
                    $errors[$index] = [
                        'duplicate' => "Duplicate subject/block detected for {$subject->subject_code} (Block {$blockSection}).",
                    ];
                    continue;
                }
                $payloadDuplicateKeys[$payloadKey] = true;

                $exists = InstructorLoad::query()
                    ->where('instructor_id', $userId)
                    ->where('subject_id', $subjectId)
                    ->where('program_id', $programId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('semester', $semester)
                    ->where('year_level', $yearLevel)
                    ->where('block_section', $blockSection)
                    ->exists();

                if ($exists) {
                    $errors[$index] = [
                        'duplicate' => 'This subject is already assigned to this instructor.',
                    ];
                    continue;
                }

                $runningLectureHours += $lectureHours;
                $runningLabHours += $labHours;

                $loadValidation = $user->validateFacultyLoad(
                    $runningLectureHours,
                    $runningLabHours,
                    $academicYearId,
                    $semester
                );

                if (!$loadValidation['valid'] && !$forceAssign) {
                    $errors[$index] = [
                        'overload' => $loadValidation['message'],
                        'validation_details' => $loadValidation,
                    ];
                    continue;
                }

                $preparedAssignments[] = [
                    'instructor_id' => $userId,
                    'program_id' => $programId,
                    'subject_id' => $subjectId,
                    'academic_year_id' => $academicYearId,
                    'semester' => $semester,
                    'year_level' => $yearLevel,
                    'block_section' => $blockSection,
                    'lec_hours' => $lectureHours,
                    'lab_hours' => $labHours,
                    'total_hours' => $lectureHours + $labHours,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'Some selected subjects have validation errors.',
                    'errors' => $errors,
                ];
            }

            DB::transaction(function () use ($preparedAssignments): void {
                InstructorLoad::insert($preparedAssignments);
            });

            return [
                'success' => true,
                'message' => count($preparedAssignments) . ' subject assignment(s) saved successfully.',
                'assigned_count' => count($preparedAssignments),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Error assigning multiple subjects: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Update teaching hours for an instructor-subject assignment.
     *
     * @param int $userId The user ID
     * @param int $subjectId The subject ID
     * @param int $lectureHours Updated lecture hours
     * @param int $labHours Updated laboratory hours
     * @param int|null $maxLoadUnits Updated max load units override
     * @return array Status and message
     */
    public function updateLoadConstraints(
        int $facultyLoadId,
        int $lectureHours = 0,
        int $labHours = 0,
        bool $forceAssign = false
    ): array {
        try {
            $load = InstructorLoad::findOrFail($facultyLoadId);
            $user = User::findOrFail($load->instructor_id);
            $subject = Subject::findOrFail($load->subject_id);

            // Validate at least one type of hours is provided
            if ($lectureHours <= 0 && $labHours <= 0) {
                return [
                    'success' => false,
                    'message' => 'Either lecture hours or laboratory hours must be greater than zero.',
                ];
            }

            // Validate lab hours divisibility by 3
            if ($labHours > 0 && $labHours % 3 !== 0) {
                return [
                    'success' => false,
                    'message' => 'Laboratory hours must be divisible by 3.',
                ];
            }

            // Get current assignment to calculate net change
            // Calculate net change (new hours - old hours)
            $lectureChange = $lectureHours - $load->lec_hours;
            $labChange = $labHours - $load->lab_hours;

            // Validate faculty load limits with the change
            $loadValidation = $user->validateFacultyLoad(
                $lectureChange,
                $labChange,
                $load->academic_year_id,
                $load->semester,
                $load->id
            );
            if (!$loadValidation['valid'] && !$forceAssign) {
                return [
                    'success' => false,
                    'message' => $loadValidation['message'],
                    'code' => 'validation_failed',
                    'validation_details' => $loadValidation,
                ];
            }

            $totalHours = $lectureHours + $labHours;

            $updated = InstructorLoad::query()
                ->where('id', $load->id)
                ->update([
                    'lec_hours' => $lectureHours,
                    'lab_hours' => $labHours,
                    'total_hours' => $totalHours,
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                return [
                    'success' => false,
                    'message' => 'Assignment not found.',
                ];
            }

            return [
                'success' => true,
                'message' => "Teaching hours updated for {$user->full_name} - {$subject->subject_name} ({$totalHours} total hours).",
                'warning' => ($loadValidation['is_overloaded'] ?? false) ? $loadValidation : null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Error updating teaching hours: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Remove a subject assignment from an instructor.
     *
     * @param int $userId The user ID
     * @param int $subjectId The subject ID
     * @return array Status and message
     */
    public function removeSubjectAssignment(int $facultyLoadId): array
    {
        try {
            $load = InstructorLoad::findOrFail($facultyLoadId);
            $user = User::findOrFail($load->instructor_id);
            $subject = Subject::findOrFail($load->subject_id);

            $load->delete();

            return [
                'success' => true,
                'message' => "{$user->full_name} has been removed from {$subject->subject_name}.",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Error removing assignment: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Get all subjects assigned to an instructor.
     *
     * @param int $userId The user ID
     * @return Collection The subjects with load constraints
     */
    public function getInstructorSubjects(int $userId): Collection
    {
        return InstructorLoad::query()
            ->with(['subject', 'program', 'academicYear'])
            ->where('instructor_id', $userId)
            ->get();
    }

    /**
     * Get aggregated teaching load summary for an instructor.
     * Returns total lecture hours, lab hours, and teaching units.
     *
     * @param int $userId The user ID
     * @return array Aggregated load summary
     */
    public function getInstructorLoadSummary(int $userId): array
    {
        $user = User::findOrFail($userId);

        $totalLectureHours = (int) $user->instructorLoads()->sum('lec_hours');
        $totalLabHours = (int) $user->instructorLoads()->sum('lab_hours');
        $totalHours = $totalLectureHours + $totalLabHours;

        return [
            'total_lecture_hours' => $totalLectureHours,
            'total_lab_hours' => $totalLabHours,
            'total_teaching_units' => $totalHours,
            'assignment_count' => $user->instructorLoads()->count(),
        ];
    }

    /**
     * Get all instructors assigned to a subject.
     *
     * @param int $subjectId The subject ID
     * @return Collection The instructors with load constraints
     */
    public function getSubjectInstructors(int $subjectId): Collection
    {
        return InstructorLoad::query()
            ->with('instructor')
            ->where('subject_id', $subjectId)
            ->get();
    }

    /**
     * Get faculty load summary (useful for reporting).
     *
     * @return array Summary statistics
     */
    public function getFacultyLoadSummary(): array
    {
        $eligibleInstructors = User::eligibleInstructors()->active()->count();
        $totalAssignments = DB::table('instructor_loads')->count();
        $assignedInstructors = DB::table('instructor_loads')
                      ->distinct('instructor_id')
                      ->count('instructor_id');

        return [
            'total_eligible_instructors' => $eligibleInstructors,
            'instructors_with_assignments' => $assignedInstructors,
            'instructors_without_assignments' => $eligibleInstructors - $assignedInstructors,
            'total_faculty_assignments' => $totalAssignments,
            'overloaded_faculty_count' => $this->countOverloadedFaculty(),
        ];
    }

    private function countOverloadedFaculty(): int
    {
        $termLoads = InstructorLoad::query()
            ->select(
                'instructor_id',
                'academic_year_id',
                'semester',
                DB::raw('SUM(COALESCE(lec_hours, 0)) as total_lecture_hours'),
                DB::raw('SUM(COALESCE(lab_hours, 0)) as total_lab_hours')
            )
            ->groupBy('instructor_id', 'academic_year_id', 'semester')
            ->get();

        $overloadedInstructors = [];

        foreach ($termLoads as $termLoad) {
            $instructor = User::find((int) $termLoad->instructor_id);
            if (!$instructor) {
                continue;
            }

            $validation = $instructor->validateFacultyLoad(
                0,
                0,
                (int) $termLoad->academic_year_id,
                (string) $termLoad->semester
            );

            if (($validation['is_overloaded'] ?? false) === true) {
                $overloadedInstructors[$instructor->id] = true;
            }
        }

        return count($overloadedInstructors);
    }

    /**
     * Get instructors without any subject assignments.
     * Useful for identifying instructors who need to be assigned subjects.
     */
    public function getUnassignedInstructors(): Collection
    {
        return User::eligibleInstructors()
                   ->active()
                   ->whereDoesntHave('instructorLoads')
                   ->orderBy('first_name')
                   ->orderBy('last_name')
                   ->get();
    }

    /**
     * Validate if an instructor can take additional subject assignments.
     * This is a placeholder for future business rules.
     *
     * @param int $userId The user ID
     * @return array Validation result
     */
    public function validateInstructorCapacity(int $userId): array
    {
        $user = User::findOrFail($userId);

        if (!$user->isEligibleInstructor()) {
            return [
                'valid' => false,
                'message' => 'User is not an eligible instructor.',
            ];
        }

        // Future: Add business logic for maximum assignments per instructor
        // based on department policies, workload calculations, etc.

        return [
            'valid' => true,
            'message' => 'Instructor is eligible for additional assignments.',
        ];
    }

}
