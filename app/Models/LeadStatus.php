<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Throwable;

class LeadStatus extends Model
{
    public const DEFAULT_STATUSES = [
        [
            'slug' => 'new',
            'name' => 'חדש',
            'badge_class' => 'text-bg-secondary',
            'sort_order' => 10,
            'is_system' => true,
            'is_closed' => false,
        ],
        [
            'slug' => 'contacted',
            'name' => 'נוצר קשר',
            'badge_class' => 'text-bg-primary',
            'sort_order' => 20,
            'is_system' => true,
            'is_closed' => false,
        ],
        [
            'slug' => 'qualified',
            'name' => 'מאושר',
            'badge_class' => 'text-bg-info',
            'sort_order' => 30,
            'is_system' => true,
            'is_closed' => false,
        ],
        [
            'slug' => 'proposal',
            'name' => 'הצעה',
            'badge_class' => 'text-bg-warning',
            'sort_order' => 40,
            'is_system' => true,
            'is_closed' => false,
        ],
        [
            'slug' => 'won',
            'name' => 'נסגר בהצלחה',
            'badge_class' => 'text-bg-success',
            'sort_order' => 50,
            'is_system' => true,
            'is_closed' => true,
        ],
        [
            'slug' => 'lost',
            'name' => 'אבוד',
            'badge_class' => 'text-bg-danger',
            'sort_order' => 60,
            'is_system' => true,
            'is_closed' => true,
        ],
    ];

    protected $fillable = [
        'slug',
        'name',
        'badge_class',
        'sort_order',
        'is_system',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_system' => 'boolean',
            'is_closed' => 'boolean',
        ];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'status', 'slug');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function labels(): array
    {
        return self::statusRows()
            ->pluck('name', 'slug')
            ->all();
    }

    public static function values(): array
    {
        return array_keys(self::labels());
    }

    public static function badgeClasses(): array
    {
        return self::statusRows()
            ->pluck('badge_class', 'slug')
            ->filter()
            ->all();
    }

    public static function closedValues(): array
    {
        $statuses = self::statusRows()
            ->where('is_closed', true)
            ->pluck('slug')
            ->values()
            ->all();

        return $statuses !== [] ? $statuses : ['won', 'lost'];
    }

    public static function normalizeSlug(?string $slug, string $name): string
    {
        $candidate = Str::slug((string) $slug, '_');

        if ($candidate === '') {
            $candidate = Str::slug($name, '_');
        }

        return $candidate !== ''
            ? $candidate
            : 'custom_' . Str::lower(Str::random(8));
    }

    private static function fallbackRows()
    {
        return collect(self::DEFAULT_STATUSES)->map(fn (array $status) => (object) $status);
    }

    private static function statusRows()
    {
        try {
            $statuses = self::query()->ordered()->get([
                'slug',
                'name',
                'badge_class',
                'sort_order',
                'is_closed',
            ]);

            return $statuses->isNotEmpty() ? $statuses : self::fallbackRows();
        } catch (Throwable) {
            return self::fallbackRows();
        }
    }
}
