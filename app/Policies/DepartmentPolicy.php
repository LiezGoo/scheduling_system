<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Department;

/**
 * DEPARTMENT POLICY
 *
 * Enforces strict department access control:
 * - department_head: Can manage only their assigned department
 * - Others: Cannot manage any department
 *
 * SECURITY: All checks are enforced at policy level.
 * Frontend filtering MUST NOT be trusted.
 */
class DepartmentPolicy
{
    /**
     * Determine if user can view a department (list, show).
     *
     * department_head: Can view their own department
     * Others: Cannot view departments
     *
     * @param \App\Models\User $user
     * @param \App\Models\Department $department
     * @return bool
     */
    public function view(User $user, Department $department): bool
    {
        // department_head: can view their own department
        if ($user->isDepartmentHead()) {
            return $user->department_id === $department->id;
        }

        // program_head: can view their program's department
        if ($user->isProgramHead() && $user->program) {
            return $user->program->department_id === $department->id;
        }

        return false;
    }

    /**
     * Determine if user can create departments.
     *
     * Only system administrators can create departments.
     * In this system, that's none. Departments are pre-configured.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return false; // Departments are system-level, not user-creatable
    }

    /**
     * Determine if user can update a department.
     *
     * department_head: Can update their own department's metadata
     * Others: Cannot update any department
     *
     * @param \App\Models\User $user
     * @param \App\Models\Department $department
     * @return bool
     */
    public function update(User $user, Department $department): bool
    {
        // SECURITY: Only the department head can modify their department
        return $user->isDepartmentHead() && $user->department_id === $department->id;
    }

    /**
     * Determine if user can delete a department.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Department $department
     * @return bool
     */
    public function delete(User $user, Department $department): bool
    {
        // Departments should not be deleted; they're system resources
        return false;
    }

    /**
     * Determine if user can restore a department.
     */
    public function restore(User $user, Department $department): bool
    {
        return false;
    }

    /**
     * Determine if user can permanently delete a department.
     */
    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }
}
