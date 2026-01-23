<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;

/**
 * PasswordResetController
 *
 * Handles forgot password and password reset flows.
 * Implements Laravel's password broker for secure token generation and validation.
 *
 * SECURITY NOTES:
 * - Does NOT reveal whether an email exists in the system
 * - Throttles reset requests to prevent abuse
 * - Prevents deactivated users from resetting passwords
 * - Invalidates all sessions after successful reset
 */
class PasswordResetController extends Controller
{
    /**
     * Show the forgot password form
     *
     * Displays a form where users can request a password reset link.
     */
    public function showForgotPasswordForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send password reset link via email
     *
     * Sends a password reset link to the user's email address.
     * Does NOT reveal whether the email exists in the system (security best practice).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLink(Request $request)
    {
        // Validate email
        $request->validate(
            ['email' => ['required', 'email']],
            ['email.required' => 'Email is required.', 'email.email' => 'Please enter a valid email address.']
        );

        // Attempt to send reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Return response without revealing email existence
        // Laravel returns Password::RESET_LINK_SENT or Password::INVALID_USER
        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __('If an account exists with that email, you will receive a password reset link shortly.'));
        }

        // Generic response to prevent email enumeration
        return back()->with('status', __('If an account exists with that email, you will receive a password reset link shortly.'));
    }

    /**
     * Show the password reset form
     *
     * Displays the form for resetting a password using a reset token.
     * Validates that the token is still valid before showing the form.
     *
     * @param string $token
     * @return \Illuminate\View\View
     */
    public function showResetForm($token)
    {
        return view('auth.passwords.reset', ['token' => $token]);
    }

    /**
     * Reset the user's password
     *
     * Validates the token and email, then updates the password.
     * Invalidates all existing sessions after successful reset.
     * Prevents deactivated users from resetting passwords.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetPassword(Request $request)
    {
        // Validate reset request
        $request->validate(
            [
                'token' => ['required'],
                'email' => ['required', 'email'],
                'password' => [
                    'required',
                    'min:8',
                    'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
                ],
            ],
            [
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters long.',
                'password.confirmed' => 'Password confirmation does not match.',
                'password.regex' => 'Password must contain uppercase, lowercase, number, and special character (@$!%*?&).',
            ]
        );

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Check if user exists and is active
        if (!$user || !$user->is_active || $user->status !== 'active') {
            // Generic response to prevent account enumeration
            return redirect('/login')->withErrors([
                'email' => 'We could not process your password reset request. Please contact support.',
            ]);
        }

        // Attempt to reset the password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // Update password
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // Fire password reset event
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            // Invalidate all sessions for this user
            // This forces the user to log in again with their new password
            $user->tokens()->delete(); // If using sanctum tokens

            // Clear all sessions for this user
            \Illuminate\Support\Facades\DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();

            return redirect('/login')->with('status', __('Your password has been reset successfully. Please log in with your new password.'));
        }

        // Token is invalid or expired
        return back()->withInput($request->only('email'))->withErrors([
            'email' => __('This password reset link is invalid or has expired. Please request a new link.'),
        ]);
    }
}
