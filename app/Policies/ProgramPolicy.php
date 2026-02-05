<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Program;

/**
 * PROGRAM POLICY
 *
 * Enforces strict program access control within department hierarchy:
 *
 * department_head: Can manage all programs in their department
 * program_head: Can manage only their assigned program (NOT siblings)
 * Others: Cannot manage any program
 *
 * SECURITY: All checks enforce department → program hierarchy.
 * Cross-program access MUST be prevented at policy level.
 */
class ProgramPolicy
{
    /**
     * Determine if user can view a program.
     *
     * department_head: Can view all programs in their department
     * program_head: Can view only their assigned program
     * instructor/student: Can view only their assigned program
     *
     * @param \App\Models\User $user
     * @param \App\Models\Program $program
     * @return bool
     */
    public function view(User $user, Program $program): bool
    {
        // SECURITY: Only accessible users in hierarchy
        return $user->canAccessProgram($program);
    }

    /**
     * Determine if user can create a program.
     *
     * Only department heads can create programs (in their department).
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // Only department heads can create programs
        return $user->isDepartmentHead();
    }

    /**
     * Determine if user can update a program.
     *
     * department_head: Can update all programs in their department
     * program_head: Can update only their own program
     * Others: Cannot update any program
     *
     * @param \App\Models\User $user
     * @param \App\Models\Program $program
     * @return bool
     */
    public function update(User $user, Program $program): bool
    {
        // SECURITY: Strict enforcement of department → program hierarchy

        // department_head: all programs in their department
        if ($user->isDepartmentHead()) {
            return $program->department_id === $user->department_id;
        }

        // program_head: only their assigned program
        if ($user->isProgramHead()) {
            return $program->id === $user->program_id;
        }

        return false;
    }

    /**
     * Determine if user can delete a program.
     *
     * Only department heads can delete programs in their department.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Program $program
     * @return bool
     */
    public function delete(User $user, Program $program): bool
    {
        // Only department heads can delete programs
        if ($user->isDepartmentHead()) {
            return $program->department_id === $user->department_id;
        }

        return false;
    }

    /**
     * Determine if user can restore a program.
     */
    public function restore(User $user, Program $program): bool
    {
        return $this->delete($user, $program);
    }

    /**
     * Determine if user can permanently delete a program.
     */
    public function forceDelete(User $user, Program $program): bool
    {
        return false;
    }
}
