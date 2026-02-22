<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    /**
     * Determine if the user can view any subjects.
     * Only Department Heads can manage subjects.
     */
    public function viewAny(User $user): bool
    {
        return $user->isDepartmentHead();
    }

    /**
     * Determine if the user can view the subject.
     */
    public function view(User $user, Subject $subject): bool
    {
        $department = $user->getInferredDepartment();

        if (!$department) {
            return false;
        }

        // Can view if subject belongs to their department
        return $subject->department_id === $department->id;
    }

    /**
     * Determine if the user can create subjects.
     * ONLY Department Heads can create subjects.
     */
    public function create(User $user): bool
    {
        return $user->isDepartmentHead();
    }

    /**
     * Determine if the user can update the subject.
     * ONLY Department Heads can update subjects in their department.
     */
    public function update(User $user, Subject $subject): bool
    {
        if (!$user->isDepartmentHead()) {
            return false;
        }

        $department = $user->getInferredDepartment();

        if (!$department) {
            return false;
        }

        return $subject->department_id === $department->id;
    }

    /**
     * Determine if the user can delete/deactivate the subject.
     * ONLY Department Heads can deactivate subjects in their department.
     */
    public function delete(User $user, Subject $subject): bool
    {
        if (!$user->isDepartmentHead()) {
            return false;
        }

        $department = $user->getInferredDepartment();

        if (!$department) {
            return false;
        }

        return $subject->department_id === $department->id;
    }
}
