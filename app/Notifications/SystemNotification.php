<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class SystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $title;
    public $message;
    public $type;
    public $relatedUrl;

    /**
     * Create a new notification instance.
     *
     * @param string $title
     * @param string $message
     * @param string $type (info, success, warning, error)
     * @param string|null $relatedUrl
     */
    public function __construct(string $title, string $message, string $type = 'info', ?string $relatedUrl = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->relatedUrl = $relatedUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'related_url' => $this->relatedUrl,
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param mixed $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'related_url' => $this->relatedUrl,
            'created_at' => now()->toDateTimeString(),
        ]);
    }
}
