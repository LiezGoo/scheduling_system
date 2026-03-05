<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRejectedNotification extends Notification
{
    use Queueable;

    protected $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(?string $reason = null)
    {
        $this->reason = $reason;
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
        $mail = (new MailMessage)
            ->subject('SorSU Scheduling System - Registration Rejected')
            ->greeting('Hello ' . $notifiable->first_name . ',')
            ->line('We regret to inform you that your registration request has been rejected by the administrator.')
            ->line('Registered email: ' . $notifiable->email)
            ->line('Requested role: ' . ucfirst(str_replace('_', ' ', $notifiable->role)));

        if ($this->reason) {
            $mail->line('Reason: ' . $this->reason);
        }

        $mail->line('If you believe this is an error, please contact the IT department or system administrator.')
            ->salutation('Best regards, SorSU Scheduling System Team');

        return $mail;
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Registration Rejected',
            'message' => 'Your registration request has been rejected.',
            'type' => 'error',
            'reason' => $this->reason,
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
            'title' => 'Registration Rejected',
            'message' => 'Your registration request has been rejected.',
        ];
    }
}