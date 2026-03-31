<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class LeadFollowUpScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Lead $lead,
        public Carbon $scheduledAt,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'lead_follow_up',
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->full_name,
            'lead_email' => $this->lead->email,
            'company' => $this->lead->company,
            'scheduled_for' => $this->scheduledAt->copy()->toIso8601String(),
            'scheduled_for_date' => $this->scheduledAt->copy()->toDateString(),
            'scheduled_for_display' => $this->lead->formatted_follow_up,
            'lead_url' => route('leads.edit', $this->lead),
            'message' => 'נקבע מעקב לליד ' . $this->lead->full_name,
        ];
    }
}
