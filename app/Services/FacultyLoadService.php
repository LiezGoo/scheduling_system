<?php

namespace App\Services;

use App\Models\InstructorLoad;
use App\Models\User;
use App\Models\Subject;
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
                    'message' => "{$user->full_name} is already assigned to {$subject->subject_name}.",
                ];
            }

            // Validate faculty load limits before assignment
            $loadValidation = $user->validateFacultyLoad($lectureHours, $labHours, $academicYearId, $semester);
            if (!$loadValidation['valid'] && !$forceAssign) {
                return [
                    'success' => false,
                    'message' => $loadValidation['message'],
                    'code' => 'overload',
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
                'warning' => $loadValidation['valid'] ? null : $loadValidation,
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
                        'duplicate' => "{$user->full_name} is already assigned to {$subject->subject_name} (Block {$blockSection}).",
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
                    'code' => 'overload',
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
                'warning' => $loadValidation['valid'] ? null : $loadValidation,
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
        ];
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
