<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ACCOUNT ACTIVE MIDDLEWARE
 *
 * Enforces that only active accounts can access protected resources.
 * SECURITY: This is a first-line security check that should run on all protected routes.
 *
 * Any user with is_active = false is immediately logged out.
 */
class EnsureAccountActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return $next($request);
        }

        // SECURITY: Check if account is active
        if (!$request->user()->isAccountActive()) {
            auth()->logout();
            $request->session()->flush();

            return redirect('/login')
                ->with('error', 'Your account has been deactivated. Please contact your administrator.');
        }

        return $next($request);
    }
}
