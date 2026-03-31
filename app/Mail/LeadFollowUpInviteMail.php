<?php

namespace App\Mail;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class LeadFollowUpInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public User $recipient,
        public Carbon $scheduledAt,
    ) {
    }

    public function build(): static
    {
        return $this
            ->subject('פגישת מעקב לליד: ' . $this->lead->full_name)
            ->view('emails.leads.follow-up-invite')
            ->with([
                'lead' => $this->lead,
                'recipient' => $this->recipient,
                'scheduledAt' => $this->scheduledAt->copy(),
                'leadUrl' => route('leads.edit', $this->lead),
            ])
            ->attachData(
                $this->calendarInvite(),
                'lead-follow-up-' . $this->lead->id . '.ics',
                ['mime' => 'text/calendar; method=REQUEST; charset=UTF-8']
            );
    }

    private function calendarInvite(): string
    {
        $organizerEmail = (string) (config('mail.from.address') ?: 'noreply@example.com');
        $organizerName = (string) (config('mail.from.name') ?: config('app.name', 'CRM'));
        $leadUrl = route('leads.edit', $this->lead);
        $startAtUtc = $this->scheduledAt->copy()->utc();
        $endAtUtc = $this->scheduledAt->copy()->addHour()->utc();

        $description = implode("\n", [
            'פגישת מעקב עבור הליד: ' . $this->lead->full_name,
            'חברה: ' . ($this->lead->company ?: 'ללא חברה'),
            'טלפון: ' . ($this->lead->phone ?: 'לא הוזן'),
            'דוא"ל: ' . $this->lead->email,
            'קישור לליד: ' . $leadUrl,
        ]);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//' . $this->escapeIcsText(config('app.name', 'CRM')) . '//CRM Follow Up//HE',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:' . $this->escapeIcsText(
                'lead-follow-up-' . $this->lead->id . '-' . $this->scheduledAt->format('YmdHis') . '@crm-app.local'
            ),
            'DTSTAMP:' . now('UTC')->format('Ymd\THis\Z'),
            'DTSTART:' . $startAtUtc->format('Ymd\THis\Z'),
            'DTEND:' . $endAtUtc->format('Ymd\THis\Z'),
            'SUMMARY:' . $this->escapeIcsText('מעקב ליד: ' . $this->lead->full_name),
            'DESCRIPTION:' . $this->escapeIcsText($description),
            'LOCATION:' . $this->escapeIcsText('CRM'),
            'ORGANIZER;CN=' . $this->escapeIcsText($organizerName) . ':mailto:' . $organizerEmail,
            'ATTENDEE;CN=' . $this->escapeIcsText($this->recipient->name) . ';RSVP=TRUE:mailto:' . $this->recipient->email,
            'URL:' . $this->escapeIcsText($leadUrl),
            'SEQUENCE:0',
            'STATUS:CONFIRMED',
            'TRANSP:OPAQUE',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines) . "\r\n";
    }

    private function escapeIcsText(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', '\n', '\n'],
            $value
        );
    }
}
