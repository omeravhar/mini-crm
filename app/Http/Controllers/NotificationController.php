<?php

namespace App\Http\Controllers;

use App\Notifications\LeadFollowUpScheduledNotification;
use App\Notifications\LeadAssignedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(DatabaseNotification $notification): RedirectResponse
    {
        $user = $this->user();

        abort_unless(
            $notification->notifiable_type === $user?->getMorphClass()
            && (int) $notification->notifiable_id === $user?->id,
            403
        );

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return back()->with('success', 'ההתראה סומנה כנקראה.');
    }

    public function markTodayLeadRemindersAsRead(Request $request): RedirectResponse
    {
        $user = $this->user();
        $today = now(config('app.timezone'))->toDateString();

        $notifications = $user?->unreadNotifications()
            ->where('type', LeadFollowUpScheduledNotification::class)
            ->get()
            ->filter(fn (DatabaseNotification $notification) => data_get($notification->data, 'scheduled_for_date') === $today);

        $notifications->each->markAsRead();

        return back()->with('success', 'התזכורות של היום סומנו כנקראו.');
    }

    public function leadAssignmentPopups(Request $request): JsonResponse
    {
        $user = $this->user();

        $notifications = $user?->unreadNotifications()
            ->where('type', LeadAssignedNotification::class)
            ->oldest()
            ->limit(5)
            ->get() ?? collect();

        $payload = $notifications
            ->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'lead_id' => data_get($notification->data, 'lead_id'),
                'lead_name' => data_get($notification->data, 'lead_name'),
                'lead_email' => data_get($notification->data, 'lead_email'),
                'lead_phone' => data_get($notification->data, 'lead_phone'),
                'company' => data_get($notification->data, 'company'),
                'interested_in' => data_get($notification->data, 'interested_in'),
                'campaign' => data_get($notification->data, 'campaign'),
                'entry_at_display' => data_get($notification->data, 'entry_at_display'),
                'lead_url' => data_get($notification->data, 'lead_url'),
                'message' => data_get($notification->data, 'message'),
            ])
            ->values();

        $notifications->each->markAsRead();

        return response()->json([
            'notifications' => $payload,
        ]);
    }
}
