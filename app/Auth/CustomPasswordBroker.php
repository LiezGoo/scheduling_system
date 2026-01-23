<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\PasswordBroker;

/**
 * CustomPasswordBroker
 *
 * Extends Laravel's default password broker to enforce security checks
 * for the password reset flow, particularly for deactivated users.
 *
 * SECURITY: Ensures deactivated users cannot request or complete password resets.
 */
class CustomPasswordBroker extends PasswordBroker
{
    /**
     * Send a password reset link to a user
     *
     * SECURITY: Checks if the user is active before sending reset link.
     * Does NOT reveal whether a user is deactivated (returns generic response).
     *
     * @param array $credentials
     * @param \Closure|null $callback
     * @return string
     */
    public function sendResetLink(array $credentials, ?\Closure $callback = null)
    {
        // Find user by email
        $user = $this->getUser($credentials);

        // Check if user exists
        if (!$user) {
            return static::INVALID_USER;
        }

        // SECURITY: Check if user is active before sending reset link
        // This prevents deactivated users from gaining access
        if (!$user->is_active || $user->status !== 'active') {
            // Return generic response to prevent account enumeration
            return static::INVALID_USER;
        }

        // Send reset link using parent implementation
        return parent::sendResetLink($credentials);
    }

    /**
     * Reset the user's password
     *
     * SECURITY: Validates that the user is still active before resetting.
     * This prevents a deactivated user from completing a reset mid-flow.
     *
     * @param array $credentials
     * @param \Closure $callback
     * @return string
     */
    public function reset(array $credentials, \Closure $callback): string
    {
        // Find user by email
        $user = $this->getUser($credentials);

        // Check if user exists and is active
        if (!$user || !$user->is_active || $user->status !== 'active') {
            // Return generic error to prevent account enumeration
            return static::INVALID_USER;
        }

        // Proceed with password reset using parent implementation
        return parent::reset($credentials, $callback);
    }
}
