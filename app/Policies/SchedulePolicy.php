<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    /**
     * Determine whether the user can view the schedule.
     */
    public function view(User $user, Schedule $schedule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Department Head can view all schedules in their department
        if ($user->isDepartmentHead()) {
            return (int) $schedule->program?->department_id === (int) $user->department_id;
        }

        // Program Head can view schedules for their program
        if ($user->isProgramHead()) {
            return (int) $schedule->program_id === (int) $user->program_id;
        }

        // Instructors and students can view finalized schedules
        if ($user->role === User::ROLE_INSTRUCTOR || $user->role === User::ROLE_STUDENT) {
            return $schedule->isFinalized() && $user->canAccessProgram($schedule->program_id);
        }

        return false;
    }

    /**
     * Determine whether the user can generate a schedule.
     * Only Department Heads can generate schedules.
     */
    public function generateSchedule(User $user, Schedule $schedule = null): bool
    {
        return $user->isDepartmentHead();
    }

    /**
     * Determine whether the user can edit the schedule.
     * Only Department Heads can edit existing schedules.
     */
    public function editSchedule(User $user, Schedule $schedule): bool
    {
        return $user->isDepartmentHead()
            && (int) $schedule->program?->department_id === (int) $user->department_id;
    }

    /**
     * Determine whether the user can delete the schedule.
     */
    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->isDepartmentHead()
            && (int) $schedule->program?->department_id === (int) $user->department_id
            && $schedule->isDraft();
    }

    /**
     * Determine whether the user can request an adjustment.
     * Program Heads and Instructors can request adjustments.
     */
    public function requestAdjustment(User $user, Schedule $schedule): bool
    {
        return ($user->isProgramHead() || $user->role === User::ROLE_INSTRUCTOR)
            && (int) $schedule->program_id === (int) $user->program_id;
    }

    /**
     * Determine whether the user can approve/reject adjustments.
     * Only Department Heads can approve/reject adjustments.
     */
    public function approveAdjustment(User $user, Schedule $schedule): bool
    {
        return $user->isDepartmentHead()
            && (int) $schedule->program?->department_id === (int) $user->department_id;
    }

    /**
     * Determine whether the user can finalize the schedule.
     * Only Department Heads can finalize schedules.
     */
    public function finalize(User $user, Schedule $schedule): bool
    {
        return $user->isDepartmentHead()
            && (int) $schedule->program?->department_id === (int) $user->department_id
            && ($schedule->isGenerated() || $schedule->isDraft());
    }
}

