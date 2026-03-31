<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">סה"כ לידים</div>
                <div class="display-6 fw-semibold">{{ $leads->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">ללא שיוך</div>
                <div class="display-6 fw-semibold">{{ $leads->whereNull('owner_id')->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">הומרו ללקוחות</div>
                <div class="display-6 fw-semibold">{{ $leads->filter(fn ($lead) => $lead->customer)->count() }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <div class="fw-semibold">לידים ללא שיוך</div>
    </div>
    <div class="card-body">
        @include('partials.lead-table', ['leads' => $leads->whereNull('owner_id'), 'users' => $users, 'showOwnerForm' => true])
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <div class="fw-semibold">לידים משויכים</div>
    </div>
    <div class="card-body">
        @include('partials.lead-table', ['leads' => $leads->whereNotNull('owner_id'), 'users' => $users, 'showOwnerForm' => true])
    </div>
</div>
