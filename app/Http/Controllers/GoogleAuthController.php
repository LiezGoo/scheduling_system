<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

/**
 * GoogleAuthController
 *
 * Handles Google OAuth authentication flow including redirects and callbacks.
 * Supports both login for existing users and registration for new users.
 */
class GoogleAuthController extends Controller
{
    /**
     * Redirect user to Google OAuth
     *
     * Initiates the Google OAuth flow by redirecting to Google's authorization endpoint.
     * Stores state in session for CSRF protection.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToGoogle()
    {
        if (!$this->hasGoogleCredentials()) {
            Log::error('Google OAuth credentials are missing.');

            return redirect()->route('login')
                ->withErrors(['email' => 'Google authentication is not configured. Please contact support.']);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth Callback
     *
     * Processes the callback from Google OAuth, verifies the state,
     * exchanges authorization code for access token, and retrieves user info.
     */
    public function handleGoogleCallback(Request $request)
    {
        if (!$this->hasGoogleAuthColumns()) {
            Log::error('Google OAuth required columns are missing from users table.', [
                'missing_columns' => [
                    'google_id' => !Schema::hasColumn('users', 'google_id'),
                    'auth_provider' => !Schema::hasColumn('users', 'auth_provider'),
                ],
            ]);

            return redirect()->route('login')
                ->withErrors(['email' => 'Google login is not fully configured. Please contact support.']);
        }

        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $socialiteGoogle */
            $socialiteGoogle = Socialite::driver('google');
            if ((bool) config('services.google.stateless', true)) {
                $socialiteGoogle = $socialiteGoogle->stateless();
            }

            $googleUser = $socialiteGoogle->user();
            $rawGoogleUser = method_exists($googleUser, 'getRaw') ? $googleUser->getRaw() : [];

            $email = $googleUser->getEmail();
            $googleId = $googleUser->getId();
            $avatar = $googleUser->getAvatar();

            if (!filled($email) || !filled($googleId)) {
                Log::warning('Invalid Google user data received');
                return redirect()->route('login')
                    ->withErrors(['email' => 'Failed to retrieve your Google information. Please try again.']);
            }

            if (!$this->isVerifiedGoogleEmail($rawGoogleUser)) {
                Log::warning('Google OAuth email is not verified', ['email' => $email]);
                return redirect()->route('login')
                    ->withErrors(['email' => 'Google account email is not verified. Please verify your Google email and try again.']);
            }

            // Unified flow: find existing by provider ID or email, otherwise create.
            $user = User::query()
                ->where('google_id', $googleId)
                ->orWhere('email', $email)
                ->first();

            if ($user) {
                if (filled($user->google_id) && $user->google_id !== $googleId) {
                    Log::warning('Google OAuth account mismatch for existing user', [
                        'user_id' => $user->id,
                        'email' => $email,
                    ]);

                    return redirect()->route('login')
                        ->withErrors(['email' => 'Google authentication failed due to account mismatch. Please contact support.']);
                }

                // Link provider metadata and refresh avatar/email verification on Google login.
                $updates = [];

                if (!$user->google_id) {
                    $updates['google_id'] = $googleId;
                }

                if ($user->auth_provider !== 'google') {
                    $updates['auth_provider'] = 'google';
                }

                if ($this->hasGoogleAvatarColumn() && filled($avatar) && $user->google_avatar !== $avatar) {
                    $updates['google_avatar'] = $avatar;
                }

                // Skip verification workflow for Google-verified users.
                if (is_null($user->email_verified_at)) {
                    $updates['email_verified_at'] = now();
                }

                if (!empty($updates)) {
                    $user->update($updates);
                }

                // Check account status before login
                if (!$user->is_active || $user->status !== 'active') {
                    return redirect()->route('login')
                        ->withErrors(['email' => 'Your account has been deactivated. Please contact the administrator.']);
                }

                // Check approval status (skip for admin)
                if ($user->approval_status !== User::APPROVAL_APPROVED && $user->role !== User::ROLE_ADMIN) {
                    $errorMessage = match($user->approval_status) {
                        User::APPROVAL_PENDING => 'Your account is awaiting administrator approval.',
                        User::APPROVAL_REJECTED => 'Your registration request was not approved.',
                        default => 'Your account is not approved for system access.',
                    };

                    return redirect()->route('login')
                        ->withErrors(['email' => $errorMessage]);
                }

                // Log user in
                Auth::login($user, remember: false);
                session()->regenerate();

                // Redirect to dashboard
                return $this->redirectToDashboard($user)
                    ->with('success', 'Logged in successfully using Google.');
            } else {
                // New user - create account with pending approval
                try {
                    DB::beginTransaction();

                    $user = User::create([
                        'first_name' => $rawGoogleUser['given_name'] ?? $googleUser->getName() ?? 'User',
                        'last_name' => $rawGoogleUser['family_name'] ?? '',
                        'email' => $email,
                        'google_id' => $googleId,
                        'google_avatar' => $this->hasGoogleAvatarColumn() ? $avatar : null,
                        'password' => Str::random(32), // Keep DB constraint satisfied for OAuth-only accounts.
                        'auth_provider' => 'google',
                        'role' => $this->resolveGoogleRole($email),
                        'status' => User::STATUS_ACTIVE,
                        'is_active' => true,
                        'is_approved' => false,
                        'approval_status' => User::APPROVAL_PENDING,
                        'registration_source' => User::REGISTRATION_SOURCE_SELF,
                        'email_verified_at' => now(), // Google email is verified
                    ]);

                    DB::commit();

                    // Notify admins about new Google registration
                    User::query()
                        ->where('role', User::ROLE_ADMIN)
                        ->where('is_active', true)
                        ->each(function (User $admin) use ($user) {
                            $admin->notify(new SystemNotification(
                                'New Registration Request (Google)',
                                "{$user->full_name} has registered via Google and is awaiting approval.",
                                'info',
                                route('admin.users.approvals', ['filter' => 'pending'])
                            ));
                        });

                    // Redirect to login with pending approval message
                    return redirect()->route('login')
                        ->with('registration_pending', 'Your registration has been submitted and is pending administrator approval. You will receive an email notification when your account has been reviewed.');

                } catch (\Throwable $e) {
                    DB::rollBack();
                    Log::error('Google OAuth user creation failed', [
                        'email' => $email,
                        'google_id' => $googleId,
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ]);

                    return redirect()->route('login')
                        ->withErrors(['email' => 'An error occurred during registration. Please try again or contact support.']);
                }
            }
        } catch (InvalidStateException $e) {
            if ((bool) config('services.google.debug_exceptions', false)) {
                dd($e->getMessage());
            }

            Log::warning('Google OAuth state validation failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->withErrors(['email' => 'Authentication session expired. Please try again.']);
        } catch (\Throwable $e) {
            if ((bool) config('services.google.debug_exceptions', false)) {
                dd($e->getMessage());
            }

            Log::error('Google OAuth callback error', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $errorMessage = config('app.debug')
                ? $e->getMessage()
                : 'Authentication failed. Please try again.';

            return redirect()->route('login')
                ->withErrors(['email' => $errorMessage]);
        }
    }

    /**
     * Verify required OAuth columns exist in users table.
     */
    private function hasGoogleAuthColumns(): bool
    {
        return Schema::hasColumn('users', 'google_id')
            && Schema::hasColumn('users', 'auth_provider');
    }

    /**
     * Check whether the optional Google avatar column exists.
     */
    private function hasGoogleAvatarColumn(): bool
    {
        return Schema::hasColumn('users', 'google_avatar');
    }

    /**
     * Validate that the Google account email is verified.
     */
    private function isVerifiedGoogleEmail(array $rawUser): bool
    {
        return filter_var($rawUser['verified_email'] ?? false, FILTER_VALIDATE_BOOL);
    }

    /**
     * Resolve role for first-time Google registrations using domain-based rules.
     *
     * Privileged roles are intentionally blocked from automatic assignment.
     */
    private function resolveGoogleRole(string $email): string
    {
        $defaultRole = (string) config('services.google.default_role', User::ROLE_STUDENT);
        $domainRoleMap = config('services.google.domain_role_map', []);

        $safeDefault = $this->sanitizeAutoAssignedRole($defaultRole);
        $domain = strtolower((string) Str::after($email, '@'));

        if (!is_array($domainRoleMap) || !isset($domainRoleMap[$domain])) {
            return $safeDefault;
        }

        return $this->sanitizeAutoAssignedRole((string) $domainRoleMap[$domain]);
    }

    /**
     * Restrict automatic assignment to non-privileged roles.
     */
    private function sanitizeAutoAssignedRole(string $role): string
    {
        $normalizedRole = strtolower(trim($role));

        $allowedAutoRoles = [
            User::ROLE_STUDENT,
            User::ROLE_INSTRUCTOR,
        ];

        return in_array($normalizedRole, $allowedAutoRoles, true)
            ? $normalizedRole
            : User::ROLE_STUDENT;
    }

    /**
     * Redirect authenticated user to appropriate dashboard
     *
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    private function redirectToDashboard(User $user): \Illuminate\Http\RedirectResponse
    {
        return redirect()->to(match($user->role ?? 'student') {
            User::ROLE_ADMIN => '/admin/dashboard',
            User::ROLE_DEPARTMENT_HEAD => '/department-head/dashboard',
            User::ROLE_PROGRAM_HEAD => '/program-head/dashboard',
            User::ROLE_INSTRUCTOR => '/instructor/dashboard',
            default => '/student/dashboard',
        });
    }

    /**
     * Resolve configured callback URI with a safe fallback.
     */
    private function googleRedirectUri(): string
    {
        return (string) (config('services.google.redirect') ?: route('google.callback'));
    }

    /**
     * Ensure Google OAuth credentials exist before redirecting.
     */
    private function hasGoogleCredentials(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled($this->googleRedirectUri());
    }
}
