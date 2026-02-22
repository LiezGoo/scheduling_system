<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Department;

/**
 * DEPARTMENT POLICY
 *
 * Enforces strict department access control:
 * - admin: Full department management access
 * - Others: No department management access
 *
 * SECURITY: All checks are enforced at policy level.
 * Frontend filtering MUST NOT be trusted.
 */
class DepartmentPolicy
{
    /**
     * Determine if user can view departments (list, show).
     *
     * Only Admin can view/manage departments.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Department $department
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can view a specific department.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Department $department
     * @return bool
     */
    public function view(User $user, Department $department): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can create departments.
     *
     * Only administrators can create departments.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can update a department.
     *
     * Only administrators can update departments.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Department $department
     * @return bool
     */
    public function update(User $user, Department $department): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can delete a department.
     *
     * Only administrators can delete departments.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Department $department
     * @return bool
     */
    public function delete(User $user, Department $department): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can restore a department.
     */
    public function restore(User $user, Department $department): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can permanently delete a department.
     */
    public function forceDelete(User $user, Department $department): bool
    {
        return $user->isAdmin();
    }
}
