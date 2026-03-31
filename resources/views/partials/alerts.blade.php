@php
    $today = now(config('app.timezone'))->toDateString();
    $todayFollowUpNotifications = auth()->check()
        ? auth()->user()
            ->unreadNotifications()
            ->where('type', \App\Notifications\LeadFollowUpScheduledNotification::class)
            ->latest()
            ->get()
            ->filter(fn ($notification) => data_get($notification->data, 'scheduled_for_date') === $today)
            ->sortBy(fn ($notification) => data_get($notification->data, 'scheduled_for'))
            ->values()
        : collect();
@endphp

@if ($todayFollowUpNotifications->isNotEmpty())
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div class="fw-semibold">תזכורות מעקב להיום</div>
            @if ($todayFollowUpNotifications->count() > 1)
                <form method="POST" action="{{ route('notifications.readToday') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-dark" type="submit">סמן הכול כנקרא</button>
                </form>
            @endif
        </div>
        <ul class="mb-0">
            @foreach ($todayFollowUpNotifications as $todayNotification)
                <li class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <a href="{{ data_get($todayNotification->data, 'lead_url') }}" class="fw-semibold">
                            {{ data_get($todayNotification->data, 'lead_name') }}
                        </a>
                        @if (data_get($todayNotification->data, 'company'))
                            <span> - {{ data_get($todayNotification->data, 'company') }}</span>
                        @endif
                        <span class="text-muted"> ({{ data_get($todayNotification->data, 'scheduled_for_display', 'היום') }})</span>
                    </div>
                    <form method="POST" action="{{ route('notifications.read', $todayNotification) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary" type="submit">סמן כנקרא</button>
                    </form>
                </li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-2">יש לתקן את השגיאות הבאות:</div>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
