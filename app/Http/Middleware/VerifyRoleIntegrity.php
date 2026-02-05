<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * VERIFY ROLE INTEGRITY MIDDLEWARE
 *
 * Validates that authenticated users have properly configured roles according to RBAC rules:
 *
 * - department_head MUST have department_id set, program_id must be NULL
 * - program_head MUST have program_id set
 * - student MUST have program_id set
 *
 * SECURITY: If a user's role is incorrectly configured, they are logged out immediately.
 * This prevents privilege escalation or inconsistent access states.
 */
class VerifyRoleIntegrity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // SECURITY: Validate role configuration
        if (!$this->validateRoleConfiguration($user)) {
            auth()->logout();
            $request->session()->flush();

            return redirect('/login')
                ->with('error', 'Your account configuration is invalid. Please contact your administrator.');
        }

        return $next($request);
    }

    /**
     * Validate user role configuration against RBAC rules.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    private function validateRoleConfiguration($user): bool
    {
        return match ($user->role) {
            'department_head' => $this->validateDepartmentHead($user),
            'program_head' => $this->validateProgramHead($user),
            'student' => $this->validateStudent($user),
            'instructor' => true, // Instructors have flexible assignment
            default => false,
        };
    }

    /**
     * Validate department_head configuration.
     * MUST have: department_id
     * MUST NOT have: program_id
     *
     * @param \App\Models\User $user
     * @return bool
     */
    private function validateDepartmentHead($user): bool
    {
        $hasRequiredDepartment = !is_null($user->department_id);
        $hasInvalidProgram = !is_null($user->program_id);

        return $hasRequiredDepartment && !$hasInvalidProgram;
    }

    /**
     * Validate program_head configuration.
     * MUST have: program_id
     * Department is inferred from program.department_id
     *
     * @param \App\Models\User $user
     * @return bool
     */
    private function validateProgramHead($user): bool
    {
        return !is_null($user->program_id);
    }

    /**
     * Validate student configuration.
     * MUST have: program_id
     * Department is inferred from program.department_id
     *
     * @param \App\Models\User $user
     * @return bool
     */
    private function validateStudent($user): bool
    {
        return !is_null($user->program_id);
    }
}
