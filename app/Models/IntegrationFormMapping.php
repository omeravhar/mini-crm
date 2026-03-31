<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationFormMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_id',
        'external_form_id',
        'external_form_name',
        'default_owner_id',
        'is_active',
        'field_map',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'field_map' => 'array',
        ];
    }

    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }

    public function defaultOwner()
    {
        return $this->belongsTo(User::class, 'default_owner_id');
    }
}
