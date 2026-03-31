@php
    $status = $status ?? null;
    $statusLabels = [
        'new' => 'חדש',
        'contacted' => 'נוצר קשר',
        'qualified' => 'מאושר',
        'proposal' => 'הצעה',
        'won' => 'נסגר בהצלחה',
        'lost' => 'אבוד',
    ];
    $statusClasses = [
        'new' => 'text-bg-secondary',
        'contacted' => 'text-bg-primary',
        'qualified' => 'text-bg-info',
        'proposal' => 'text-bg-warning',
        'won' => 'text-bg-success',
        'lost' => 'text-bg-danger',
    ];
@endphp

<span class="badge {{ $statusClasses[$status] ?? 'text-bg-secondary' }}">
    {{ $statusLabels[$status] ?? $status ?? 'ללא סטטוס' }}
</span>
