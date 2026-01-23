<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AuthController
 *
 * Handles user authentication including login, logout, and role-based redirects.
 * Uses guest middleware for login routes (applied in web.php).
 */
class AuthController extends Controller
{
    /**
     * Show Login Form
     *
     * Displays the login page using the guest auth layout.
     * Only accessible to unauthenticated users (enforced by guest middleware).
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle Login Request
     *
     * Authenticates user credentials and redirects to role-specific dashboard.
     * Implements Laravel's authentication best practices with session regeneration.
     * Includes security check for deactivated accounts.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // Validate credentials
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Attempt authentication with optional "remember me"
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            // SECURITY: Check if user is active before creating session
            $user = Auth::user();

            // Check both is_active (hard deactivation) and status (soft deactivation)
            if (!$user->is_active || $user->status !== 'active') {
                // Immediately logout the deactivated user
                Auth::logout();

                // Return with clear validation error
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact the administrator.',
                ])->onlyInput('email');
            }

            // Regenerate session to prevent fixation attacks
            $request->session()->regenerate();

            // Redirect to role-specific dashboard
            $redirectPath = match($user->role ?? 'student') {
                'admin' => '/admin/dashboard',
                'department_head' => '/department-head/dashboard',
                'program_head' => '/program-head/dashboard',
                'instructor' => '/instructor/dashboard',
                default => '/student/dashboard',
            };

            return redirect()->intended($redirectPath);
        }

        // Authentication failed - return with errors
        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Handle Logout Request
     *
     * Logs out the user, invalidates session, and redirects to login page.
     * Follows Laravel security best practices.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        // Log out the user
        Auth::logout();

        // Invalidate the session
        $request->session()->invalidate();

        // Regenerate CSRF token
        $request->session()->regenerateToken();

        // Redirect to login page
        return redirect('/login');
    }
}
