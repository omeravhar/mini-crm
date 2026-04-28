@extends('layouts.crm')

@php
    $sourceLabels = [
        'website' => 'אתר אינטרנט',
        'inbound_call' => 'שיחה נכנסת',
        'outbound' => 'פנייה יזומה',
        'referral' => 'הפניה',
        'event' => 'אירוע',
        'social' => 'רשתות חברתיות',
        'partner' => 'שותף',
    ];
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
    $pipelineLabels = [
        'default' => 'ברירת מחדל',
        'enterprise' => 'ארגוני',
        'smb' => 'עסקים קטנים',
    ];
    $stageLabels = [
        'lead' => 'ליד',
        'mql' => 'ליד שיווקי',
        'sql' => 'ליד מכירתי',
        'negotiation' => 'משא ומתן',
        'won' => 'נסגר',
    ];
    $visibilityLabels = [
        'team' => 'צוות',
        'private' => 'פרטי',
    ];
@endphp

@section('pageTitle', $isEditing ? 'עריכת ליד' : 'יצירת ליד')
@section('pageSubtitle', $isEditing ? 'עדכון פרטי הליד בבסיס הנתונים' : 'הוספת ליד חדש ל-EeasyCRM')

@section('content')
    <form method="POST" action="{{ $isEditing ? route('leads.update', $lead) : route('admin.saveNewLead') }}" enctype="multipart/form-data">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">פרטי קשר</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="first_name">שם פרטי</label>
                                <input class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $lead->first_name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_name">שם משפחה</label>
                                <input class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $lead->last_name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">דוא"ל</label>
                                <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $lead->email) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">טלפון</label>
                                <input class="form-control" id="phone" name="phone" value="{{ old('phone', $lead->phone) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="company">חברה</label>
                                <input class="form-control" id="company" name="company" value="{{ old('company', $lead->company) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="job_title">תפקיד</label>
                                <input class="form-control" id="job_title" name="job_title" value="{{ old('job_title', $lead->job_title) }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="website">אתר אינטרנט</label>
                                <input class="form-control" id="website" name="website" type="url" value="{{ old('website', $lead->website) }}">
                            </div>
                        </div>

                        <hr class="my-4">

                        <h2 class="h5 mb-3">פרטי ליד</h2>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="source">מקור</label>
                                <select class="form-select" id="source" name="source">
                                    <option value="">בחר מקור</option>
                                    @foreach ($options['sources'] as $source)
                                        <option value="{{ $source }}" @selected(old('source', $lead->source) === $source)>{{ $sourceLabels[$source] ?? $source }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="status">סטטוס</label>
                                <select class="form-select" id="status" name="status" required>
                                    @if ($lead->status && ! array_key_exists($lead->status, $statusLabels))
                                        <option value="{{ $lead->status }}" selected>{{ $lead->status }}</option>
                                    @endif
                                    @foreach ($options['statuses'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $lead->status) === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="priority">עדיפות</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    @foreach ($options['priorities'] as $priority)
                                        <option value="{{ $priority }}" @selected(old('priority', $lead->priority) === $priority)>{{ $priorityLabels[$priority] ?? $priority }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="expected_value">שווי צפוי</label>
                                <input class="form-control" id="expected_value" name="expected_value" type="number" min="0" step="0.01" value="{{ old('expected_value', $lead->expected_value) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="follow_up">תאריך מעקב</label>
                                <input class="form-control" id="follow_up" name="follow_up" type="date" value="{{ old('follow_up', optional($lead->follow_up)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="follow_up_time">שעת מעקב</label>
                                <input class="form-control" id="follow_up_time" name="follow_up_time" type="time" value="{{ old('follow_up_time', $lead->follow_up_time ? substr($lead->follow_up_time, 0, 5) : '') }}">
                                <div class="form-text">אם נקבע תאריך מעקב, יש לבחור גם שעה להזמנת היומן.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="interested_in">במה התעניינ/ה?</label>
                                <input class="form-control" id="interested_in" name="interested_in" value="{{ old('interested_in', $lead->interested_in) }}" placeholder="לדוגמה: מטבח חדש, דלתות פנים, ארונות אמבטיה">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="lead_type">סוג ליד</label>
                                <select class="form-select" id="lead_type" name="lead_type">
                                    @foreach ($options['lead_types'] as $leadType)
                                        <option value="{{ $leadType }}" @selected(old('lead_type', $lead->lead_type) === $leadType)>{{ $leadTypeLabels[$leadType] ?? $leadType }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="external_campaign_name">שם הקמפיין</label>
                                <input class="form-control" id="external_campaign_name" name="external_campaign_name" value="{{ old('external_campaign_name', $lead->external_campaign_name) }}" placeholder="לדוגמה: Spring Sale / Meta April">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="tags_text">תגיות</label>
                                <input class="form-control" id="tags_text" name="tags_text" value="{{ old('tags_text', implode(', ', $lead->tags ?? [])) }}" placeholder="לדוגמה: ארגוני, דחוף, הדגמה">
                            </div>
                        </div>

                        <hr class="my-4">

                        <h2 class="h5 mb-3">כתובת והערות</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="street">רחוב</label>
                                <input class="form-control" id="street" name="street" value="{{ old('street', $lead->street) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="city">עיר</label>
                                <input class="form-control" id="city" name="city" value="{{ old('city', $lead->city) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="zip">מיקוד</label>
                                <input class="form-control" id="zip" name="zip" value="{{ old('zip', $lead->zip) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="country">מדינה</label>
                                <input class="form-control" id="country" name="country" value="{{ old('country', $lead->country) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="notes">הערות</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4">{{ old('notes', $lead->notes) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="attachment">קובץ מצורף</label>
                                <input class="form-control" id="attachment" name="attachment" type="file">
                                @if ($lead->attachment_path)
                                    <div class="form-text">
                                        קובץ נוכחי:
                                        <a href="{{ asset('storage/' . $lead->attachment_path) }}" target="_blank" rel="noopener">פתיחת הקובץ</a>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" id="remove_attachment" name="remove_attachment" type="checkbox" value="1">
                                        <label class="form-check-label" for="remove_attachment">הסר את הקובץ הנוכחי</label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">שיוך וצינור מכירה</h2>
                        <div class="row g-3">
                            @if (auth()->user()->isAdmin())
                                <div class="col-12">
                                    <label class="form-label" for="owner_id">אחראי</label>
                                    <select class="form-select" id="owner_id" name="owner_id">
                                        <option value="">ללא שיוך</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" @selected(old('owner_id', $lead->owner_id) == $user->id)>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="col-12">
                                <label class="form-label" for="pipeline">צינור מכירה</label>
                                <select class="form-select" id="pipeline" name="pipeline" required>
                                    @foreach ($options['pipelines'] as $pipeline)
                                        <option value="{{ $pipeline }}" @selected(old('pipeline', $lead->pipeline) === $pipeline)>{{ $pipelineLabels[$pipeline] ?? $pipeline }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="stage">שלב</label>
                                <select class="form-select" id="stage" name="stage" required>
                                    @foreach ($options['stages'] as $stage)
                                        <option value="{{ $stage }}" @selected(old('stage', $lead->stage) === $stage)>{{ $stageLabels[$stage] ?? $stage }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="visibility">נראות</label>
                                <select class="form-select" id="visibility" name="visibility" required>
                                    @foreach ($options['visibility'] as $visibility)
                                        <option value="{{ $visibility }}" @selected(old('visibility', $lead->visibility) === $visibility)>{{ $visibilityLabels[$visibility] ?? $visibility }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary flex-fill" type="submit">{{ $isEditing ? 'עדכון ליד' : 'שמירת ליד' }}</button>
                    @if (! $isEditing)
                        <button class="btn btn-outline-primary" name="save_new" type="submit" value="1">שמירה ויצירת נוסף</button>
                    @endif
                </div>
            </div>
        </div>
    </form>
@endsection
