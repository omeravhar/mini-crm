@php
    $summaryBaseFilters = collect($filters ?? [])
        ->except('summary_scope')
        ->filter(fn ($value) => filled($value))
        ->all();
    $summaryCardUrl = function (?string $scope = null) use ($summaryBaseFilters): string {
        $query = $summaryBaseFilters;

        if ($scope === 'unassigned') {
            unset($query['owner_id']);
        }

        if (filled($scope)) {
            $query['summary_scope'] = $scope;
        }

        $queryString = http_build_query($query);

        return $queryString !== '' ? route('admin.leads.index') . '?' . $queryString : route('admin.leads.index');
    };
    $summaryScope = $filters['summary_scope'] ?? '';
@endphp

<div class="row g-4 mb-4 admin-leads-summary">
    <div class="col-md-4">
        <a class="admin-leads-summary__link @if($summaryScope === '') is-active @endif" href="{{ $summaryCardUrl() }}" @if($summaryScope === '') aria-current="page" @endif>
            <div class="card card-stat card-leads h-100 admin-leads-summary__card admin-leads-summary__card--leads">
                <div class="card-body dashboard-stat-card">
                    <div class="dashboard-stat-card__content">
                        <div class="text-muted small dashboard-stat-card__label">סה"כ לידים</div>
                        <div class="display-6 fw-semibold dashboard-stat-card__value">{{ number_format((int) ($leadSummary['total'] ?? 0)) }}</div>
                    </div>
                    <div class="dashboard-stat-icon dashboard-stat-icon--leads" aria-hidden="true">
                        <svg viewBox="0 0 48 48">
                            <circle cx="24" cy="13.5" r="6.5" />
                            <path d="M13 35v-2.5c0-5.8 4.8-10.5 10.7-10.5s10.7 4.7 10.7 10.5V35" />
                            <circle cx="10.5" cy="17" r="4.8" />
                            <path d="M3.5 35v-1.8c0-4.6 3.7-8.3 8.3-8.3" />
                            <circle cx="37.5" cy="17" r="4.8" />
                            <path d="M44.5 35v-1.8c0-4.6-3.7-8.3-8.3-8.3" />
                        </svg>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a class="admin-leads-summary__link @if($summaryScope === 'unassigned') is-active @endif" href="{{ $summaryCardUrl('unassigned') }}" @if($summaryScope === 'unassigned') aria-current="page" @endif>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">ללא שיוך</div>
                    <div class="display-6 fw-semibold">{{ number_format((int) ($leadSummary['unassigned'] ?? 0)) }}</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a class="admin-leads-summary__link @if($summaryScope === 'converted') is-active @endif" href="{{ $summaryCardUrl('converted') }}" @if($summaryScope === 'converted') aria-current="page" @endif>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">הומרו ללקוחות</div>
                    <div class="display-6 fw-semibold">{{ number_format((int) ($leadSummary['converted'] ?? 0)) }}</div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 admin-leads-panel">
    <div class="card-header bg-white">
        <div class="fw-semibold">לידים ללא שיוך</div>
    </div>
    <div class="card-body">
        @include('partials.lead-table', [
            'leads' => $leads->whereNull('owner_id'),
            'users' => $users,
            'showOwnerForm' => true,
            'enableBulkSelection' => true,
            'hiddenColumns' => ['company', 'interested_in', 'customer'],
        ])
    </div>
</div>

<div class="card shadow-sm border-0 admin-leads-panel">
    <div class="card-header bg-white">
        <div class="fw-semibold">לידים משויכים</div>
    </div>
    <div class="card-body">
        @include('partials.lead-table', [
            'leads' => $leads->whereNotNull('owner_id'),
            'users' => $users,
            'showOwnerForm' => true,
            'enableBulkSelection' => true,
            'hiddenColumns' => ['company', 'interested_in', 'customer'],
        ])
    </div>
</div>
