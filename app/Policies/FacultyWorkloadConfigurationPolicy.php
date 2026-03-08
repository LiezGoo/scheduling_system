<?php

namespace App\Policies;

use App\Models\FacultyWorkloadConfiguration;
use App\Models\User;

class FacultyWorkloadConfigurationPolicy
{
    /**
     * Determine whether the user can view any faculty workload configurations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('program_head') && $user->is_active && $user->approval_status === 'approved';
    }

    /**
     * Determine whether the user can view the workload configuration.
     */
    public function view(User $user, FacultyWorkloadConfiguration $config): bool
    {
        return $this->belongsToUserProgram($user, $config);
    }

    /**
     * Determine whether the user can create a workload configuration.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('program_head') && $user->is_active && $user->approval_status === 'approved';
    }

    /**
     * Determine whether the user can update the workload configuration.
     */
    public function update(User $user, FacultyWorkloadConfiguration $config): bool
    {
        return $this->belongsToUserProgram($user, $config);
    }

    /**
     * Determine whether the user can delete the workload configuration.
     */
    public function delete(User $user, FacultyWorkloadConfiguration $config): bool
    {
        return $this->belongsToUserProgram($user, $config);
    }

    /**
     * Determine whether the user can restore the workload configuration.
     */
    public function restore(User $user, FacultyWorkloadConfiguration $config): bool
    {
        return $this->belongsToUserProgram($user, $config);
    }

    /**
     * Determine whether the user can permanently delete the workload configuration.
     */
    public function forceDelete(User $user, FacultyWorkloadConfiguration $config): bool
    {
        return $this->belongsToUserProgram($user, $config);
    }

    /**
     * Check if configuration belongs to the user's program
     */
    private function belongsToUserProgram(User $user, FacultyWorkloadConfiguration $config): bool
    {
        $userProgram = $user->program_id;
        return $config->program_id === $userProgram;
    }
}
