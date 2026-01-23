<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnforceActiveUser Middleware
 *
 * SECURITY-CRITICAL MIDDLEWARE
 *
 * This global middleware runs on EVERY web request and enforces
 * immediate access blocking for deactivated users.
 *
 * REQUIREMENTS:
 * - Confirm authenticated user exists
 * - Check is_active === true
 * - If false: immediately logout, invalidate session, regenerate CSRF, redirect
 * - This must trigger even if user is already logged in with valid session
 * - This must apply regardless of user role
 *
 * DESIGN PHILOSOPHY:
 * - Server-side only enforcement (no frontend reliance)
 * - Proactive checking on every request (not passive)
 * - Hard access block with no exceptions
 * - Immediate session termination
 */
class EnforceActiveUser
{
    /**
     * Routes excluded from deactivation enforcement.
     * These routes should NOT trigger the middleware's deactivation check.
     *
     * @var array<string>
     */
    protected $except = [
        'account-deactivated',
        'logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for excluded routes
        if ($this->shouldExclude($request)) {
            return $next($request);
        }

        // Only check if user is authenticated
        if (Auth::check()) {
            $user = Auth::user();

            // SECURITY: Check both is_active and status fields
            // If user is deactivated via either mechanism, immediately block access
            if (!$user->is_active || $user->status !== 'active') {
                // Immediately logout the user
                Auth::logout();

                // Invalidate the session
                $request->session()->invalidate();

                // Regenerate CSRF token for security
                $request->session()->regenerateToken();

                // Redirect to account deactivation notice
                // Use intended redirect to capture original URL for audit logs if needed
                return redirect('/account-deactivated');
            }
        }

        return $next($request);
    }

    /**
     * Determine if the request should be excluded from the middleware.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldExclude(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($request->routeIs($except)) {
                return true;
            }
        }

        return false;
    }
}

