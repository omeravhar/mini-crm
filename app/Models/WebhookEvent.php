<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_id',
        'lead_id',
        'platform',
        'event_type',
        'external_event_id',
        'external_form_id',
        'status',
        'headers',
        'payload',
        'error_message',
        'received_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
