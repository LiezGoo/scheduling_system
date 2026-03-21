<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Notifications\UserApprovedNotification;
use App\Notifications\UserRejectedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * UserApprovalController
 * 
 * Handles admin approval/rejection of pending user accounts.
 * Only accessible to admin users.
 */
class UserApprovalController extends Controller
{
    /**
     * Show users for approval (with filters)
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'pending'); // Default to pending

        $query = User::query()
            ->where('registration_source', User::REGISTRATION_SOURCE_SELF)
            ->orderBy('created_at', 'desc');

        // Apply filter based on approval status
        switch ($filter) {
            case 'approved':
                $query->where('approval_status', User::APPROVAL_APPROVED);
                break;
            case 'rejected':
                $query->where('approval_status', User::APPROVAL_REJECTED);
                break;
            case 'pending':
            default:
                $query->where('approval_status', User::APPROVAL_PENDING);
                break;
        }

        $users = $query->paginate(15)->withQueryString();

        // Get counts for all statuses
        $pendingCount = User::where('registration_source', User::REGISTRATION_SOURCE_SELF)
            ->where('approval_status', User::APPROVAL_PENDING)
            ->count();
        $approvedCount = User::where('registration_source', User::REGISTRATION_SOURCE_SELF)
            ->where('approval_status', User::APPROVAL_APPROVED)
            ->count();
        $rejectedCount = User::where('registration_source', User::REGISTRATION_SOURCE_SELF)
            ->where('approval_status', User::APPROVAL_REJECTED)
            ->count();

        return view('admin.users.approvals', [
            'users' => $users,
            'currentFilter' => $filter,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
        ]);
    }

    /**
     * Approve a user account
     */
    public function approve(User $user, Request $request)
    {
        if ($user->registration_source !== User::REGISTRATION_SOURCE_SELF) {
            return back()->with('warning', 'Only self-registered accounts can be approved from this page.');
        }

        // Check if user is already approved
        if ($user->isApproved()) {
            return back()->with('info', 'This account has already been approved.');
        }

        // Approve user
        $user->approve(auth()->user());

        // Notification/mail are best-effort and should not roll back approval.
        try {
            $user->notify(new UserApprovedNotification(auth()->user()));
            Mail::to($user->email)->send(new AccountApprovedMail($user));
        } catch (\Throwable $e) {
            Log::warning('User approved but notification delivery failed.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
        }

        return back()->with('success', "{$user->first_name} {$user->last_name} has been approved and can now access the system.");
    }

    /**
     * Reject a user account
     */
    public function reject(User $user, Request $request)
    {
        if ($user->registration_source !== User::REGISTRATION_SOURCE_SELF) {
            return back()->with('warning', 'Only self-registered accounts can be rejected from this page.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        // Check if user is already approved
        if ($user->isApproved()) {
            return back()->with('warning', 'Cannot reject an already approved account. Please deactivate the user instead.');
        }

        // Reject user with reason
        $user->reject($request->rejection_reason);

        // Notification/mail are best-effort and should not roll back rejection.
        try {
            $user->notify(new UserRejectedNotification($request->rejection_reason));
            Mail::to($user->email)->send(new AccountRejectedMail($user, $request->rejection_reason));
        } catch (\Throwable $e) {
            Log::warning('User rejected but notification delivery failed.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
        }

        return back()->with('success', "{$user->first_name} {$user->last_name} has been rejected.");
    }

    /**
     * Get pending users count (for dashboard widget)
     */
    public function getPendingCount()
    {
        return response()->json([
            'pending_count' => User::where('registration_source', User::REGISTRATION_SOURCE_SELF)
                ->where('approval_status', User::APPROVAL_PENDING)
                ->count(),
        ]);
    }
}
