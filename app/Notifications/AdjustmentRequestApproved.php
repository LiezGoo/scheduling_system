<?php

namespace App\Notifications;

use App\Models\Schedule;
use App\Models\ScheduleAdjustmentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdjustmentRequestApproved extends Notification implements ShouldQueue
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
            'title' => 'Adjustment Request Approved',
            'message' => "Your adjustment request for schedule {$this->schedule->id} has been approved. You can now make the changes.",
            'type' => 'schedule_adjustment_approved',
            'request_id' => $this->request->id,
            'schedule_id' => $this->schedule->id,
            'action_url' => route('department-head.schedules.edit', $this->schedule),
        ];
    }
}
