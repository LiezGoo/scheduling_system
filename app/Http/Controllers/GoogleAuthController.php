<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * GoogleAuthController
 *
 * Handles Google OAuth authentication flow including redirects and callbacks.
 * Supports both login for existing users and registration for new users.
 */
class GoogleAuthController extends Controller
{
    /**
     * Constants for authentication
     */
    private const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const GOOGLE_TOKEN_URL = 'https://www.googleapis.com/oauth2/v4/token';
    private const GOOGLE_USER_INFO_URL = 'https://www.googleapis.com/oauth2/v1/userinfo';

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

        $state = bin2hex(random_bytes(16));
        session(['google_oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->googleRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
        ]);

        return redirect(self::GOOGLE_AUTH_URL . '?' . $query);
    }

    /**
     * Handle Google OAuth Callback
     *
     * Processes the callback from Google OAuth, verifies the state,
     * exchanges authorization code for access token, and retrieves user info.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
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

        // CSRF Protection: Verify state matches
        if ($request->input('state') !== session('google_oauth_state')) {
            Log::warning('Google OAuth state mismatch');
            return redirect()->route('login')
                ->withErrors(['email' => 'Authentication failed. Please try again.']);
        }

        // Prevent replay and stale callback confusion in subsequent attempts.
        session()->forget('google_oauth_state');

        // Check for authorization error
        if ($request->has('error')) {
            Log::warning('Google OAuth error', ['error' => $request->input('error')]);
            return redirect()->route('login')
                ->withErrors(['email' => 'Google OAuth authorization was denied.']);
        }

        // Get authorization code
        $code = $request->input('code');
        if (!$code) {
            Log::warning('Google OAuth missing authorization code');
            return redirect()->route('login')
                ->withErrors(['email' => 'Authorization code not received. Please try again.']);
        }

        try {
            // Exchange authorization code for access token
            $googleUser = $this->getGoogleUser($code);

            // Verify we got valid user data
            if (!$googleUser || !isset($googleUser['email']) || !isset($googleUser['id'])) {
                Log::warning('Invalid Google user data received');
                return redirect()->route('login')
                    ->withErrors(['email' => 'Failed to retrieve your Google information. Please try again.']);
            }

            // Verify email is Gmail (security requirement)
            if (!$this->isValidGmailAddress($googleUser['email'])) {
                Log::warning('Non-Gmail address attempted', ['email' => $googleUser['email']]);
                return redirect()->route('login')
                    ->withErrors(['email' => 'Please use a valid Gmail address to authenticate.']);
            }

            // Find or create user
            $user = User::where('email', $googleUser['email'])->first();

            if ($user) {
                // Existing user - update google_id if needed and log in
                if (!$user->google_id || $user->google_id !== $googleUser['id']) {
                    $user->update([
                        'google_id' => $googleUser['id'],
                        'auth_provider' => 'google',
                    ]);
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
                return $this->redirectToDashboard($user);
            } else {
                // New user - create account with pending approval
                try {
                    DB::beginTransaction();

                    $user = User::create([
                        'first_name' => $googleUser['given_name'] ?? 'User',
                        'last_name' => $googleUser['family_name'] ?? '',
                        'email' => $googleUser['email'],
                        'google_id' => $googleUser['id'],
                        'password' => Str::random(32), // Keep DB constraint satisfied for OAuth-only accounts.
                        'auth_provider' => 'google',
                        'role' => 'student', // Default role
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
                        'email' => $googleUser['email'],
                        'google_id' => $googleUser['id'] ?? null,
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ]);

                    return redirect()->route('login')
                        ->withErrors(['email' => 'An error occurred during registration. Please try again or contact support.']);
                }
            }

        } catch (\Throwable $e) {
            Log::error('Google OAuth callback error', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $request->input('code') ? 'present' : 'missing',
            ]);

            $errorMessage = config('app.debug')
                ? 'OAuth callback error: ' . $e->getMessage()
                : 'An error occurred during authentication. Please try again.';

            return redirect()->route('login')
                ->withErrors(['email' => $errorMessage]);
        }
    }

    /**
     * Exchange authorization code for access token and get user info
     *
     * @param string $code
     * @return array|null
     */
    private function getGoogleUser(string $code): ?array
    {
        try {
            // Exchange code for access token
            $response = Http::post(self::GOOGLE_TOKEN_URL, [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->googleRedirectUri(),
            ]);

            if (!$response->successful()) {
                Log::error('Google token exchange failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $token = $response->json('access_token');
            if (!$token) {
                Log::error('No access token in Google response');
                return null;
            }

            // Get user info using access token
            $userResponse = Http::withToken($token)
                ->get(self::GOOGLE_USER_INFO_URL);

            if (!$userResponse->successful()) {
                Log::error('Google user info retrieval failed', [
                    'status' => $userResponse->status(),
                ]);
                return null;
            }

            return $userResponse->json();

        } catch (\Throwable $e) {
            Log::error('Google OAuth token exchange error', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return null;
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
     * Verify email is a valid Gmail address
     *
     * @param string $email
     * @return bool
     */
    private function isValidGmailAddress(string $email): bool
    {
        return preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email) === 1;
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
