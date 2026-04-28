<?php

namespace App\Support;

use App\Models\Lead;
use App\Notifications\LeadAssignedNotification;

class LeadAssignmentNotifier
{
    public function notifyIfAssigned(Lead $lead, ?int $previousOwnerId = null): void
    {
        if (! $lead->owner_id || $previousOwnerId === $lead->owner_id) {
            return;
        }

        $lead->unsetRelation('owner');
        $lead->load('owner');

        if (! $lead->owner) {
            return;
        }

        $lead->owner->notify(new LeadAssignedNotification($lead));
    }
}
