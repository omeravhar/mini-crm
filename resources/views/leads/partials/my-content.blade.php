<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <div class="fw-semibold">לידים משויכים</div>
    </div>
    <div class="card-body">
        @include('partials.lead-table', ['leads' => $leads, 'showOwnerForm' => false])
    </div>
</div>
