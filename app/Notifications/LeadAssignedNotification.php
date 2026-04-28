<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeadAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Lead $lead,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'lead_assigned',
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->full_name,
            'lead_email' => $this->lead->email,
            'lead_phone' => $this->lead->phone,
            'company' => $this->lead->company,
            'interested_in' => $this->lead->interested_in,
            'campaign' => $this->lead->campaign_display,
            'source_channel' => $this->lead->source_channel,
            'entry_at_display' => $this->lead->formatted_entry_at,
            'lead_url' => route('leads.edit', $this->lead),
            'message' => 'התווסף לך ליד חדש: ' . $this->lead->full_name,
        ];
    }
}
