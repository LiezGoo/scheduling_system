<?php

namespace App\Notifications;

use App\Models\Schedule;
use App\Models\ScheduleAdjustmentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdjustmentRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ScheduleAdjustmentRequest $request,
        protected Schedule $schedule
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Adjustment Request Rejected',
            'message' => "Your adjustment request for schedule {$this->schedule->id} has been rejected. Reason: {$this->request->review_remarks}",
            'type' => 'schedule_adjustment_rejected',
            'request_id' => $this->request->id,
            'schedule_id' => $this->schedule->id,
        ];
    }
}
