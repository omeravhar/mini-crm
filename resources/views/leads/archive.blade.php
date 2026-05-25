@extends('layouts.crm')

@php
    $statusLabels = $statusLabels ?? [];
    $priorityLabels = [
        'low' => 'נמוכה',
        'medium' => 'בינונית',
        'high' => 'גבוהה',
    ];
    $leadTypeLabels = [
        'new' => 'חדש',
        'returning' => 'חוזר',
    ];
    $visibilityLabels = [
        'team' => 'צוות',
        'private' => 'פרטי',
    ];
    $sourceLabels = [
        'website' => 'אתר אינטרנט',
        'inbound_call' => 'שיחה נכנסת',
        'outbound' => 'פנייה יזומה',
        'referral' => 'הפניה',
        'event' => 'אירוע',
        'social' => 'רשתות חברתיות',
        'partner' => 'שותף',
    ];
    $summaryBaseFilters = collect($filters ?? [])
        ->except('status')
        ->filter(fn ($value) => filled($value))
        ->all();
    $summaryCardUrl = function (?string $status = null) use ($archiveRoute, $summaryBaseFilters): string {
        $query = $summaryBaseFilters;

        if (filled($status)) {
            $query['status'] = $status;
        }

        $queryString = http_build_query($query);

        return $queryString !== '' ? $archiveRoute . '?' . $queryString : $archiveRoute;
    };
    $summaryCards = [
        [
            'label' => 'סה"כ בארכיון',
            'count' => $archiveSummary['total'] ?? 0,
            'href' => $summaryCardUrl(),
            'active' => ($filters['status'] ?? '') === '',
        ],
        [
            'label' => $statusLabels['won'] ?? 'נסגר בהצלחה',
            'count' => $archiveSummary['won'] ?? 0,
            'href' => $summaryCardUrl('won'),
            'active' => ($filters['status'] ?? '') === 'won',
        ],
        [
            'label' => 'אבודים / לא רלוונטיים',
            'count' => $archiveSummary['lost'] ?? 0,
            'href' => $summaryCardUrl('lost'),
            'active' => ($filters['status'] ?? '') === 'lost',
        ],
    ];
@endphp

@section('pageTitle', 'ארכיון לידים')
@section('pageSubtitle', 'כל הלידים שהועברו לארכיון, כולל היסטוריית המידע, זמן הארכוב ומי שביצע אותו')

@section('pageActions')
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <a class="btn btn-outline-secondary" href="{{ $activeLeadsRoute }}">לידים פעילים</a>
        @if ($isAdminArchive)
            <a class="btn btn-primary" href="{{ route('admin.leads.create') }}">יצירת ליד</a>
        @endif
    </div>
@endsection

@section('pageHeaderExtras')
    <form method="GET" action="{{ $archiveRoute }}" class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label" for="q">חיפוש</label>
                    <input class="form-control" id="q" name="q" value="{{ $filters['q'] }}" placeholder="שם, טלפון, מייל, חברה או קמפיין">
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label" for="status">סטטוס</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">כל הסטטוסים</option>
                        @foreach ($options['statuses'] as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </select>
                </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label" for="owner_id">אחראי</label>
                        <select class="form-select" id="owner_id" name="owner_id">
                            <option value="">כל האחראים</option>
                            <option value="unassigned" @selected($filters['owner_id'] === 'unassigned')>ללא שיוך</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected($filters['owner_id'] === (string) $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label" for="campaign">קמפיין</label>
                    <select class="form-select" id="campaign" name="campaign">
                        <option value="">כל הקמפיינים</option>
                        @foreach ($campaignOptions as $campaignValue => $campaignLabel)
                            <option value="{{ $campaignValue }}" @selected($filters['campaign'] === $campaignValue)>{{ $campaignLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2 d-grid gap-2">
                    <button class="btn btn-dark" type="submit">סינון</button>
                    <a class="btn btn-outline-secondary" href="{{ $archiveRoute }}">ניקוי</a>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('content')
    <style>
        .archive-leads-grid {
            display: grid;
            gap: 1rem;
        }

        .archive-lead-card {
            border: 0;
            border-radius: 18px;
            overflow: hidden;
        }

        .archive-lead-card summary {
            list-style: none;
            cursor: pointer;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        }

        .archive-lead-card summary::-webkit-details-marker {
            display: none;
        }

        .archive-lead-summary {
            display: grid;
            gap: 0.85rem;
        }

        .archive-lead-summary__title {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.6rem;
        }

        .archive-lead-summary__meta {
            display: grid;
            gap: 0.35rem;
            color: #475569;
            font-size: 0.92rem;
        }

        .archive-lead-body {
            padding: 1.25rem;
        }

        .archive-lead-body__grid {
            display: grid;
            gap: 1rem;
        }

        .archive-info-grid {
            display: grid;
            gap: 0.85rem;
        }

        .archive-info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1rem;
        }

        .archive-info-card dt {
            color: #64748b;
            font-size: 0.82rem;
            margin-bottom: 0.2rem;
        }

        .archive-info-card dd {
            margin-bottom: 0.75rem;
        }

        .archive-info-card dd:last-child {
            margin-bottom: 0;
        }

        .archive-summary-card {
            display: block;
            height: 100%;
            color: inherit;
            text-decoration: none;
            border-radius: 18px;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .archive-summary-card:hover {
            transform: translateY(-2px);
        }

        .archive-summary-card:focus-visible {
            outline: 3px solid rgba(37, 99, 235, 0.18);
            outline-offset: 3px;
        }

        .archive-summary-card .card-body {
            display: grid;
            gap: 0.45rem;
            min-height: 100%;
        }

        .archive-summary-card__label {
            color: #64748b;
            font-size: 0.92rem;
        }

        .archive-summary-card__value {
            font-size: 2.35rem;
            font-weight: 700;
            line-height: 1;
        }

        .archive-summary-card__hint {
            color: #94a3b8;
            font-size: 0.82rem;
        }

        .archive-summary-card .card {
            height: 100%;
        }

        .archive-summary-card.is-active .card {
            box-shadow: 0 0 0 2px #2563eb, 0 20px 40px -28px rgba(37, 99, 235, 0.75) !important;
        }

        .archive-summary-card.is-active .archive-summary-card__label,
        .archive-summary-card.is-active .archive-summary-card__hint,
        .archive-summary-card.is-active .text-muted.small {
            color: #1d4ed8;
        }

        @media (min-width: 992px) {
            .archive-lead-summary {
                grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
                align-items: center;
            }

            .archive-lead-body__grid {
                grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
            }

            .archive-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <a
                class="archive-summary-card @if($summaryCards[0]['active']) is-active @endif"
                href="{{ $summaryCards[0]['href'] }}"
                @if($summaryCards[0]['active']) aria-current="page" @endif
            >
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">סה"כ בארכיון</div>
                    <div class="archive-summary-card__value">{{ number_format((int) $summaryCards[0]['count']) }}</div>
                    <div class="archive-summary-card__hint">{{ $summaryCards[0]['active'] ? 'הסינון פעיל' : 'לחיצה לסינון לפי הסטטוס' }}</div>
                </div>
            </div>
            </a>
        </div>
        <div class="col-md-4">
            <a
                class="archive-summary-card @if($summaryCards[1]['active']) is-active @endif"
                href="{{ $summaryCards[1]['href'] }}"
                @if($summaryCards[1]['active']) aria-current="page" @endif
            >
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">נסגרו בהצלחה</div>
                    <div class="archive-summary-card__value">{{ number_format((int) $summaryCards[1]['count']) }}</div>
                    <div class="archive-summary-card__hint">{{ $summaryCards[1]['active'] ? 'הסינון פעיל' : 'לחיצה לסינון לפי הסטטוס' }}</div>
                </div>
            </div>
            </a>
        </div>
        <div class="col-md-4">
            <a
                class="archive-summary-card @if($summaryCards[2]['active']) is-active @endif"
                href="{{ $summaryCards[2]['href'] }}"
                @if($summaryCards[2]['active']) aria-current="page" @endif
            >
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">אבודים / לא רלוונטיים</div>
                    <div class="archive-summary-card__value">{{ number_format((int) $summaryCards[2]['count']) }}</div>
                    <div class="archive-summary-card__hint">{{ $summaryCards[2]['active'] ? 'הסינון פעיל' : 'לחיצה לסינון לפי הסטטוס' }}</div>
                </div>
            </div>
            </a>
        </div>
    </div>

    <div class="archive-leads-grid">
        @forelse ($leads as $lead)
            <details class="card shadow-sm archive-lead-card" @if ($loop->first) open @endif>
                <summary>
                    <div class="archive-lead-summary">
                        <div>
                            <div class="archive-lead-summary__title">
                                <span class="h5 mb-0">{{ $lead->full_name }}</span>
                                @include('partials.lead-status-badge', ['status' => $lead->status, 'statusLabels' => $statusLabels])
                            </div>
                            <div class="text-muted small mt-2">
                                {{ $lead->campaign_display ?: 'ללא קמפיין' }}
                                @if ($lead->company)
                                    <span class="mx-1">•</span>{{ $lead->company }}
                                @endif
                                @if ($lead->phone)
                                    <span class="mx-1">•</span>{{ $lead->phone }}
                                @endif
                            </div>
                        </div>
                        <div class="archive-lead-summary__meta">
                            <div><strong>בארכיון מ:</strong> {{ $lead->formatted_archived_at ?: 'לא זמין' }}</div>
                            <div><strong>הועבר על ידי:</strong> {{ $lead->archiver?->name ?: 'לא ידוע' }}</div>
                            <div><strong>אחראי:</strong> {{ $lead->owner?->name ?: 'ללא שיוך' }}</div>
                            @if ($lead->archive_reason)
                                <div><strong>סיבה:</strong> {{ $lead->archive_reason }}</div>
                            @endif
                        </div>
                    </div>
                </summary>
                <div class="archive-lead-body">
                    <div class="archive-lead-body__grid">
                        <div class="archive-info-grid">
                            <dl class="archive-info-card mb-0">
                                <dt>פרטי קשר</dt>
                                <dd>
                                    {{ $lead->full_name }}<br>
                                    {{ $lead->email ?: 'ללא מייל' }}<br>
                                    {{ $lead->phone ?: 'ללא טלפון' }}
                                </dd>
                                <dt>חברה ותפקיד</dt>
                                <dd>{{ $lead->company ?: 'ללא חברה' }} / {{ $lead->job_title ?: 'ללא תפקיד' }}</dd>
                                <dt>מקור</dt>
                                <dd>{{ $sourceLabels[$lead->source] ?? ($lead->source ?: 'לא צוין') }}</dd>
                                <dt>אתר</dt>
                                <dd>{{ $lead->website ?: 'ללא אתר' }}</dd>
                            </dl>

                            <dl class="archive-info-card mb-0">
                                <dt>פרטי ליד</dt>
                                <dd>
                                    קמפיין: {{ $lead->campaign_display ?: 'ללא קמפיין' }}<br>
                                    התעניין ב: {{ $lead->interested_in ?: 'לא צוין' }}<br>
                                    סוג ליד: {{ $leadTypeLabels[$lead->lead_type] ?? $lead->lead_type_label }}<br>
                                    עדיפות: {{ $priorityLabels[$lead->priority] ?? $lead->priority }}
                                </dd>
                                <dt>שיוך</dt>
                                <dd>
                                    אחראי: {{ $lead->owner?->name ?: 'ללא שיוך' }}<br>
                                    יוצר: {{ $lead->creator?->name ?: 'לא ידוע' }}
                                </dd>
                                <dt>צינור</dt>
                                <dd>
                                    Pipeline: {{ $lead->pipeline ?: 'לא צוין' }}<br>
                                    Stage: {{ $lead->stage ?: 'לא צוין' }}<br>
                                    נראות: {{ $visibilityLabels[$lead->visibility] ?? $lead->visibility }}
                                </dd>
                            </dl>

                            <dl class="archive-info-card mb-0">
                                <dt>תאריכים</dt>
                                <dd>
                                    כניסה: {{ $lead->formatted_entry_at ?: 'לא זמין' }}<br>
                                    מעקב: {{ $lead->formatted_follow_up ?: 'לא נקבע' }}<br>
                                    סגירה: {{ optional($lead->closed_at)->format('Y-m-d H:i') ?: 'לא זמין' }}<br>
                                    ארכוב: {{ $lead->formatted_archived_at ?: 'לא זמין' }}
                                </dd>
                                <dt>ערך צפוי</dt>
                                <dd>{{ $lead->expected_value !== null ? number_format((float) $lead->expected_value, 2) : 'לא הוגדר' }}</dd>
                                <dt>הוסב ללקוח</dt>
                                <dd>{{ $lead->customer ? 'כן' : 'לא' }}</dd>
                            </dl>

                            <dl class="archive-info-card mb-0">
                                <dt>כתובת</dt>
                                <dd>
                                    {{ $lead->street ?: 'ללא רחוב' }}<br>
                                    {{ $lead->city ?: 'ללא עיר' }}<br>
                                    {{ $lead->zip ?: 'ללא מיקוד' }}<br>
                                    {{ $lead->country ?: 'ללא מדינה' }}
                                </dd>
                                <dt>תגיות</dt>
                                <dd>{{ implode(', ', $lead->tags ?? []) !== '' ? implode(', ', $lead->tags ?? []) : 'ללא תגיות' }}</dd>
                                <dt>קובץ מצורף</dt>
                                <dd>
                                    @if ($lead->attachment_path)
                                        <a href="{{ asset('storage/' . $lead->attachment_path) }}" target="_blank" rel="noopener">פתיחת הקובץ</a>
                                    @else
                                        ללא קובץ
                                    @endif
                                </dd>
                            </dl>
                        </div>

                        <div class="d-grid gap-3">
                            <div class="archive-info-card">
                                <div class="small text-muted mb-2">סיבת ארכוב</div>
                                <div>{{ $lead->archive_reason ?: 'לא נשמרה סיבה' }}</div>
                            </div>
                            <div class="archive-info-card">
                                <div class="small text-muted mb-2">הערות</div>
                                <div>{{ $lead->notes ?: 'אין הערות שמורות' }}</div>
                            </div>
                            <div class="icon-action-group">
                                <a class="btn btn-outline-primary" href="{{ route('leads.edit', $lead) }}" title="עריכה" aria-label="עריכה"><i class="bi bi-pencil-square icon-action-icon" aria-hidden="true"></i><span class="visually-hidden">עריכה</span></a>
                                @if (! $lead->customer)
                                    <form method="POST" action="{{ route('leads.convert', $lead) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success" title="המרה ללקוח" aria-label="המרה ללקוח"><i class="bi bi-person-check icon-action-icon icon-action-icon--success" aria-hidden="true"></i><span class="visually-hidden">המרה ללקוח</span></button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </details>
        @empty
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5 text-muted">
                    אין לידים בארכיון לפי הסינון שבחרת.
                </div>
            </div>
        @endforelse
    </div>
@endsection
