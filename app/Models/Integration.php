<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'platform',
        'status',
        'webhook_key',
        'external_account_id',
        'external_page_id',
        'access_token',
        'refresh_token',
        'verify_token',
        'webhook_secret',
        'config',
        'notes',
        'last_webhook_at',
        'last_error_at',
        'last_error_message',
    ];

    protected static function booted(): void
    {
        static::creating(function (Integration $integration) {
            if (! $integration->webhook_key) {
                $integration->webhook_key = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'last_webhook_at' => 'datetime',
            'last_error_at' => 'datetime',
        ];
    }

    public function formMappings()
    {
        return $this->hasMany(IntegrationFormMapping::class);
    }

    public function webhookEvents()
    {
        return $this->hasMany(WebhookEvent::class);
    }
}
