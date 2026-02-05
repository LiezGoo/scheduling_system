<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

/**
 * LOGIN REDIRECTION LOGIC
 *
 * After successful authentication, users are redirected to their role-specific dashboard.
 * This controller implements the authentication flow with proper role-based redirection.
 *
 * ROUTING:
 * This logic should be integrated into your LoginController or used in a custom authentication listener.
 */
class AuthRedirectController
{
    /**
     * Redirect authenticated user to their role-specific dashboard.
     *
     * This method should be called immediately after successful authentication.
     *
     * FLOW:
     * 1. Check user's role
     * 2. Validate role configuration
     * 3. Redirect to appropriate dashboard
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function redirectBasedOnRole(): RedirectResponse
    {
        $user = Auth::user();

        // SECURITY: Validate role integrity before redirecting
        if (!self::validateRoleConfiguration($user)) {
            Auth::logout();
            return redirect('/login')->with('error', 'Your account configuration is invalid.');
        }

        return match ($user->role) {
            'department_head' => self::redirectDepartmentHead($user),
            'program_head' => self::redirectProgramHead($user),
            'instructor' => self::redirectInstructor($user),
            'student' => self::redirectStudent($user),
            default => redirect('/dashboard'), // Fallback
        };
    }

    /**
     * Redirect department_head to their admin dashboard scoped to their department.
     *
     * Example: CICT Admin Dashboard
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    private static function redirectDepartmentHead($user): RedirectResponse
    {
        // Load department relationship for efficiency
        $user->load('department');

        return redirect()->route('admin.dashboard', [
            'department' => $user->department_id,
        ])->with('welcome', "Welcome back, {$user->first_name}! You are viewing the {$user->department->department_name} dashboard.");
    }

    /**
     * Redirect program_head to their program-scoped dashboard.
     *
     * Example: BSCS Program Head Dashboard
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    private static function redirectProgramHead($user): RedirectResponse
    {
        // Load program relationship for efficiency
        $user->load('program');

        return redirect()->route('program-head.dashboard', [
            'program' => $user->program_id,
        ])->with('welcome', "Welcome back, {$user->first_name}! You are viewing the {$user->program->program_name} program.");
    }

    /**
     * Redirect instructor to their instructor dashboard.
     *
     * Instructors can view their assigned teaching load,
     * schedule, and manage their teaching subjects.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    private static function redirectInstructor($user): RedirectResponse
    {
        return redirect()->route('instructor.dashboard')
            ->with('welcome', "Welcome back, {$user->first_name}!");
    }

    /**
     * Redirect student to their student schedule viewer.
     *
     * Students can view their class schedule for their assigned program.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    private static function redirectStudent($user): RedirectResponse
    {
        // Load program relationship for efficiency
        $user->load('program');

        return redirect()->route('student.schedule', [
            'program' => $user->program_id,
        ])->with('welcome', "Welcome back, {$user->first_name}! Your schedule is ready to view.");
    }

    /**
     * Validate role configuration.
     *
     * Ensures user's role matches their department/program assignment.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    private static function validateRoleConfiguration($user): bool
    {
        return match ($user->role) {
            'department_head' => !is_null($user->department_id) && is_null($user->program_id),
            'program_head' => !is_null($user->program_id),
            'student' => !is_null($user->program_id),
            'instructor' => true, // Flexible
            default => false,
        };
    }
}
