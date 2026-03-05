<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
                'role' => $request->role,
                'is_active' => true,
                'is_approved' => false,
                'approval_status' => User::APPROVAL_PENDING,
                'registration_source' => User::REGISTRATION_SOURCE_SELF,
                'email_verified_at' => now(), // Email is already trusted (university domain)
            ]);

            DB::commit();

            // Notify admins about new self-registration request (best-effort, non-blocking)
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

            // Redirect to login with success message
            return redirect()->route('login')
                ->with('registration_pending', 'Your account is pending approval.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', 'An error occurred during registration. Please try again.');
        }
    }
}
