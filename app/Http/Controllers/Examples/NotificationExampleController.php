<?php

namespace App\Http\Controllers\Examples;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Example Controller demonstrating notification usage
 *
 * This controller shows various ways to send notifications
 * throughout the scheduling system.
 */
class NotificationExampleController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Example: Send notification when schedule is created
     */
    public function scheduleCreated(Request $request)
    {
        // Example: Admin creates a schedule and instructor gets notified
        $instructor = User::where('role', 'instructor')->first();

        if ($instructor) {
            $scheduleData = [
                'subject' => 'Computer Science 101',
                'day' => 'Monday',
                'time' => '8:00 AM - 10:00 AM',
            ];

            $this->notificationService->notifyScheduleAssigned($instructor, $scheduleData);

            return response()->json([
                'message' => 'Instructor notified about new schedule',
            ]);
        }

        return response()->json(['message' => 'No instructor found'], 404);
    }

    /**
     * Example: Send notification when request is approved
     */
    public function requestApproved(Request $request)
    {
        // Example: Program head approves a request
        $user = User::find($request->input('user_id'));

        if ($user) {
            $this->notificationService->notifyRequestStatus(
                $user,
                'Faculty Load',
                true // approved
            );

            return response()->json([
                'message' => 'User notified about approval',
            ]);
        }

        return response()->json(['message' => 'User not found'], 404);
    }

    /**
     * Example: Send notification to multiple users
     */
    public function notifyAllInstructors(Request $request)
    {
        $instructors = User::where('role', 'instructor')->get();

        $this->notificationService->sendToUsers(
            $instructors,
            'Important Announcement',
            'Please check your schedule for upcoming semester',
            'info',
            '/instructor/dashboard'
        );

        return response()->json([
            'message' => sprintf('Notified %d instructors', $instructors->count()),
        ]);
    }

    /**
     * Example: Send notification by role
     */
    public function notifyByRole(Request $request)
    {
        $this->notificationService->sendToRoles(
            ['admin', 'department_head'],
            'System Update',
            'A new feature has been added to the scheduling system',
            'success',
            '/admin/dashboard'
        );

        return response()->json([
            'message' => 'Admins and department heads notified',
        ]);
    }

    /**
     * Example: Send custom notification
     */
    public function sendCustomNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|in:info,success,warning,error',
            'url' => 'nullable|url',
        ]);

        $user = User::find($request->input('user_id'));

        $this->notificationService->sendToUser(
            $user,
            $request->input('title'),
            $request->input('message'),
            $request->input('type', 'info'),
            $request->input('url')
        );

        return response()->json([
            'message' => 'Notification sent successfully',
        ]);
    }

    /**
     * Example: Test notification for current user
     */
    public function testNotification(Request $request)
    {
        $user = auth()->user();

        $this->notificationService->sendToUser(
            $user,
            'Test Notification',
            'This is a test notification to verify the real-time notification system is working correctly.',
            'info',
            '/dashboard'
        );

        return response()->json([
            'message' => 'Test notification sent! Check your notification bell.',
        ]);
    }
}
