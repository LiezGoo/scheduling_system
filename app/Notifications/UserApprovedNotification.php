<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class UserApprovedNotification extends Notification
{
    use Queueable;

    protected $approver;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $approver)
    {
        $this->approver = $approver;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SorSU Scheduling System - Account Approved')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Your registration request has been approved by the administrator.')
            ->line('You can now log in to the SorSU Scheduling System and access your dashboard.')
            ->action('Login to System', route('login'))
            ->line('Your registered role: ' . ucfirst(str_replace('_', ' ', $notifiable->role)))
            ->line('If you have any questions, please contact the IT department.')
            ->salutation('Best regards, SorSU Scheduling System Team');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Account Approved',
            'message' => 'Your registration has been approved. You can now access the system.',
            'type' => 'success',
            'approved_by' => $this->approver->first_name . ' ' . $this->approver->last_name,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Account Approved',
            'message' => 'Your registration has been approved.',
        ];
    }
}