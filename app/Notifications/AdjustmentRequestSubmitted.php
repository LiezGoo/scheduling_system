<?php

namespace App\Notifications;

use App\Models\ScheduleAdjustmentRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdjustmentRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ScheduleAdjustmentRequest $request,
        protected User $requester
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New Adjustment Request',
            'message' => "{$this->requester->first_name} {$this->requester->last_name} submitted an adjustment request for schedule {$this->request->schedule->id}",
            'type' => 'schedule_adjustment_request',
            'request_id' => $this->request->id,
            'schedule_id' => $this->request->schedule_id,
            'requester_id' => $this->requester->id,
        ];
    }
}
