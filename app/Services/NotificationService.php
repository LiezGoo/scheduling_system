<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send a notification to a single user
     *
     * @param User $user
     * @param string $title
     * @param string $message
     * @param string $type (info, success, warning, error)
     * @param string|null $relatedUrl
     * @return void
     */
    public function sendToUser(User $user, string $title, string $message, string $type = 'info', ?string $relatedUrl = null): void
    {
        try {
            $user->notify(new SystemNotification($title, $message, $type, $relatedUrl));
        } catch (\Exception $e) {
            Log::error('Failed to send notification to user: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'title' => $title,
            ]);
        }
    }

    /**
     * Send a notification to multiple users
     *
     * @param array|\Illuminate\Support\Collection $users
     * @param string $title
     * @param string $message
     * @param string $type
     * @param string|null $relatedUrl
     * @return void
     */
    public function sendToUsers($users, string $title, string $message, string $type = 'info', ?string $relatedUrl = null): void
    {
        foreach ($users as $user) {
            $this->sendToUser($user, $title, $message, $type, $relatedUrl);
        }
    }

    /**
     * Notify instructor about new schedule assignment
     *
     * @param User $instructor
     * @param array $scheduleData
     * @return void
     */
    public function notifyScheduleAssigned(User $instructor, array $scheduleData): void
    {
        $title = 'New Schedule Assigned';
        $message = sprintf(
            'You have been assigned to teach %s on %s at %s',
            $scheduleData['subject'] ?? 'a subject',
            $scheduleData['day'] ?? 'weekday',
            $scheduleData['time'] ?? 'scheduled time'
        );

        $this->sendToUser($instructor, $title, $message, 'info', '/instructor/dashboard#schedule-generation');
    }

    /**
     * Notify admin about schedule creation
     *
     * @param User $admin
     * @param string $createdBy
     * @return void
     */
    public function notifyScheduleCreated(User $admin, string $createdBy): void
    {
        $title = 'Schedule Created';
        $message = sprintf('A new schedule has been created by %s', $createdBy);

        $this->sendToUser($admin, $title, $message, 'success', '/admin/dashboard#schedule-generation');
    }

    /**
     * Notify about request approval
     *
     * @param User $user
     * @param string $requestType
     * @param bool $approved
     * @return void
     */
    public function notifyRequestStatus(User $user, string $requestType, bool $approved): void
    {
        $title = $approved ? 'Request Approved' : 'Request Rejected';
        $message = sprintf('Your %s request has been %s', $requestType, $approved ? 'approved' : 'rejected');
        $type = $approved ? 'success' : 'error';

        $this->sendToUser($user, $title, $message, $type);
    }

    /**
     * Notify about role assignment
     *
     * @param User $user
     * @param string $newRole
     * @return void
     */
    public function notifyRoleChanged(User $user, string $newRole): void
    {
        $title = 'Role Updated';
        $message = sprintf('Your role has been updated to %s', ucwords(str_replace('_', ' ', $newRole)));

        $this->sendToUser($user, $title, $message, 'info', '/dashboard');
    }

    /**
     * Notify about schedule update
     *
     * @param User $user
     * @param array $scheduleData
     * @return void
     */
    public function notifyScheduleUpdated(User $user, array $scheduleData): void
    {
        $title = 'Schedule Updated';
        $message = sprintf(
            'Your schedule for %s has been updated',
            $scheduleData['subject'] ?? 'a subject'
        );

        $this->sendToUser($user, $title, $message, 'warning', '/instructor/dashboard#schedule-generation');
    }

    /**
     * Send system-wide notification to all users with specific roles
     *
     * @param array $roles
     * @param string $title
     * @param string $message
     * @param string $type
     * @param string|null $relatedUrl
     * @return void
     */
    public function sendToRoles(array $roles, string $title, string $message, string $type = 'info', ?string $relatedUrl = null): void
    {
        $users = User::whereIn('role', $roles)->get();
        $this->sendToUsers($users, $title, $message, $type, $relatedUrl);
    }

    /**
     * Notify about upcoming deadline
     *
     * @param User $user
     * @param string $deadlineType
     * @param string $date
     * @return void
     */
    public function notifyDeadline(User $user, string $deadlineType, string $date): void
    {
        $title = 'Deadline Reminder';
        $message = sprintf('Reminder: %s deadline is on %s', $deadlineType, $date);

        $this->sendToUser($user, $title, $message, 'warning');
    }
}
