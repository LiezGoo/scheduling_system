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

        if ($user->isProgramHead()) {
            return (int) $schedule->program_id === (int) $user->program_id;
        }

        if ($user->isDepartmentHead()) {
            return (int) $schedule->program?->department_id === (int) $user->department_id;
        }

        if ($user->role === User::ROLE_INSTRUCTOR || $user->role === User::ROLE_STUDENT) {
            return $schedule->isApproved() && $user->canAccessProgram($schedule->program_id);
        }

        return false;
    }

    /**
     * Determine whether the user can update the schedule.
     */
    public function update(User $user, Schedule $schedule): bool
    {
        return $user->isProgramHead()
            && (int) $schedule->program_id === (int) $user->program_id
            && ($schedule->isDraft() || $schedule->isRejected());
    }

    /**
     * Determine whether the user can submit the schedule.
     */
    public function submit(User $user, Schedule $schedule): bool
    {
        return $this->update($user, $schedule);
    }

    /**
     * Determine whether the user can delete the schedule.
     */
    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->isProgramHead()
            && (int) $schedule->program_id === (int) $user->program_id
            && $schedule->isDraft();
    }

    /**
     * Determine whether the user can review the schedule.
     */
    public function review(User $user, Schedule $schedule): bool
    {
        return $user->isDepartmentHead()
            && (int) $schedule->program?->department_id === (int) $user->department_id
            && $schedule->isPending();
    }
}
