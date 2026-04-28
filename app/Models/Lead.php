<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    private const ENTRY_TIMEZONE = 'Asia/Jerusalem';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'job_title',
        'website',
        'source',
        'source_platform',
        'source_channel',
        'external_lead_id',
        'external_form_id',
        'external_campaign_id',
        'external_campaign_name',
        'external_ad_id',
        'status',
        'priority',
        'expected_value',
        'interested_in',
        'lead_type',
        'follow_up',
        'follow_up_time',
        'tags',
        'street',
        'zip',
        'city',
        'country',
        'notes',
        'attachment_path',
        'raw_payload',
        'owner_id',
        'created_by',
        'pipeline',
        'stage',
        'visibility',
        'converted_to_customer_at',
        'closed_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_value' => 'decimal:2',
            'follow_up' => 'date',
            'tags' => 'array',
            'raw_payload' => 'array',
            'converted_to_customer_at' => 'datetime',
            'closed_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getFollowUpAtAttribute(): ?Carbon
    {
        if (! $this->follow_up) {
            return null;
        }

        $followUpAt = $this->follow_up instanceof Carbon
            ? $this->follow_up->copy()
            : Carbon::parse($this->follow_up, config('app.timezone'));

        if (! $this->follow_up_time) {
            return $followUpAt->startOfDay();
        }

        [$hours, $minutes] = explode(':', substr($this->follow_up_time, 0, 5));

        return $followUpAt->setTime((int) $hours, (int) $minutes);
    }

    public function getFormattedFollowUpAttribute(): ?string
    {
        $followUpAt = $this->follow_up_at;

        if (! $followUpAt) {
            return null;
        }

        return $this->follow_up_time
            ? $followUpAt->format('Y-m-d H:i')
            : $followUpAt->format('Y-m-d');
    }

    public function getEntryAtAttribute(): ?Carbon
    {
        $entryAt = $this->received_at ?? $this->created_at;

        if (! $entryAt) {
            return null;
        }

        $entryAt = $entryAt instanceof Carbon
            ? $entryAt->copy()
            : Carbon::parse($entryAt, config('app.timezone'));

        return $entryAt->timezone(self::ENTRY_TIMEZONE);
    }

    public function getFormattedEntryAtAttribute(): ?string
    {
        return $this->entry_at?->format('Y-m-d H:i');
    }

    public function getCampaignDisplayAttribute(): ?string
    {
        $campaignName = trim((string) ($this->external_campaign_name ?? ''));

        if ($campaignName !== '') {
            return $campaignName;
        }

        $campaignId = trim((string) ($this->external_campaign_id ?? ''));

        return $campaignId !== '' ? $campaignId : null;
    }

    public function getLeadTypeLabelAttribute(): string
    {
        return match ($this->lead_type) {
            'returning' => 'חוזר',
            default => 'חדש',
        };
    }
}
