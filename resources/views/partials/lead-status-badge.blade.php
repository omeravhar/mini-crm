@php
    $status = $status ?? null;
    $statusLabels = $statusLabels ?? \App\Models\LeadStatus::labels();
    $statusClasses = $statusClasses ?? \App\Models\LeadStatus::badgeClasses();
@endphp

<span class="badge {{ $statusClasses[$status] ?? 'text-bg-secondary' }}">
    {{ $statusLabels[$status] ?? $status ?? 'ללא סטטוס' }}
</span>
