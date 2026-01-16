<?php

namespace App\Services;

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
                   ->with('facultySubjects')
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
        int $lectureHours = 0,
        int $labHours = 0,
        ?int $maxLoadUnits = null
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

            // Check if assignment already exists
            $exists = DB::table('faculty_subjects')
                        ->where('user_id', $userId)
                        ->where('subject_id', $subjectId)
                        ->exists();

            if ($exists) {
                return [
                    'success' => false,
                    'message' => "{$user->full_name} is already assigned to {$subject->subject_name}.",
                ];
            }

            // Calculate teaching units
            $computedUnits = User::calculateTeachingUnits($lectureHours, $labHours);

            // Create assignment with hours and computed units
            $user->facultySubjects()->attach($subjectId, [
                'lecture_hours' => $lectureHours,
                'lab_hours' => $labHours,
                'computed_units' => $computedUnits,
                'max_load_units' => $maxLoadUnits,
            ]);

            return [
                'success' => true,
                'message' => "{$user->full_name} has been assigned to {$subject->subject_name} ({$computedUnits} units).",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Error assigning subject: {$e->getMessage()}",
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
        int $userId,
        int $subjectId,
        int $lectureHours = 0,
        int $labHours = 0,
        ?int $maxLoadUnits = null
    ): array {
        try {
            $user = User::findOrFail($userId);
            $subject = Subject::findOrFail($subjectId);

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

            // Calculate teaching units
            $computedUnits = User::calculateTeachingUnits($lectureHours, $labHours);

            // Update the pivot table
            $updated = DB::table('faculty_subjects')
                         ->where('user_id', $userId)
                         ->where('subject_id', $subjectId)
                         ->update([
                             'lecture_hours' => $lectureHours,
                             'lab_hours' => $labHours,
                             'computed_units' => $computedUnits,
                             'max_load_units' => $maxLoadUnits,
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
                'message' => "Teaching hours updated for {$user->full_name} - {$subject->subject_name} ({$computedUnits} units).",
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
    public function removeSubjectAssignment(int $userId, int $subjectId): array
    {
        try {
            $user = User::findOrFail($userId);
            $subject = Subject::findOrFail($subjectId);

            $user->facultySubjects()->detach($subjectId);

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
        return User::findOrFail($userId)
                   ->facultySubjects()
                   ->with('program')
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
        return $user->getTeachingLoadSummary();
    }

    /**
     * Get all instructors assigned to a subject.
     *
     * @param int $subjectId The subject ID
     * @return Collection The instructors with load constraints
     */
    public function getSubjectInstructors(int $subjectId): Collection
    {
        return Subject::findOrFail($subjectId)
                      ->facultyInstructors()
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
        $totalAssignments = DB::table('faculty_subjects')->count();
        $assignedInstructors = DB::table('faculty_subjects')
                                  ->distinct('user_id')
                                  ->count('user_id');

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
                   ->whereDoesntHave('facultySubjects')
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
