<?php

namespace App\Http\Controllers;

use App\Notifications\LeadFollowUpScheduledNotification;
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
}
