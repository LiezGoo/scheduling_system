<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     */
    public function register(RegisterRequest $request)
    {
        try {
            DB::beginTransaction();

            // Self-registered accounts are always pending admin approval.
            // SECURITY: Status/source are set server-side and never trusted from client input.
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'auth_provider' => 'local',
                'role' => $request->role,
                'is_active' => true,
                'is_approved' => false,
                'approval_status' => User::APPROVAL_PENDING,
                'registration_source' => User::REGISTRATION_SOURCE_SELF,
                'email_verified_at' => now(), // Email is already trusted (Google email domain)
            ]);

            DB::commit();

            // Best-effort admin notifications: registration must remain successful even if queue/broadcast fails.
            try {
                User::query()
                    ->where('role', User::ROLE_ADMIN)
                    ->where('is_active', true)
                    ->each(function (User $admin) use ($user) {
                        $admin->notify(new SystemNotification(
                            'New Registration Request',
                            "{$user->full_name} has registered and is awaiting approval.",
                            'info',
                            route('admin.users.approvals', ['filter' => 'pending'])
                        ));
                    });
            } catch (\Throwable $notificationException) {
                Log::warning('Registration succeeded but admin notification failed.', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $notificationException->getMessage(),
                    'exception' => get_class($notificationException),
                ]);
            }

            // Redirect to login with success message
            return redirect()->route('login')
                ->with('registration_pending', 'Your account is pending approval.');

        } catch (\Throwable $e) {
            DB::rollBack();

            if ((bool) config('app.debug_registration_exceptions', false)) {
                dd($e->getMessage());
            }

            Log::error('Registration failed.', [
                'email' => strtolower((string) $request->input('email', '')),
                'role' => $request->input('role'),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            
            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', 'An error occurred during registration. Please try again.');
        }
    }
}
