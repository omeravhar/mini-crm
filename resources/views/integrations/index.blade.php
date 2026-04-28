@extends('layouts.crm')

@php
    $statusBadgeClasses = [
        'draft' => 'text-bg-secondary',
        'active' => 'text-bg-success',
        'disabled' => 'text-bg-danger',
    ];
    $webhookExamples = [
        'meta' => url('/webhooks/meta/WEBHOOK_KEY'),
        'google' => url('/webhooks/google/WEBHOOK_KEY'),
        'tiktok' => url('/webhooks/tiktok/WEBHOOK_KEY'),
        'generic' => url('/webhooks/generic/WEBHOOK_KEY'),
    ];
    $metaFormsLibraryUrl = 'https://business.facebook.com/latest/instant_forms/forms';
    $fieldHelp = [
        'name' => 'שם פנימי אצלכם במערכת. בוחרים אותו ידנית כדי לזהות את החיבור, למשל "Meta Leads Ritzufim".',
        'platform' => 'הפלטפורמה שממנה יגיעו הלידים. הבחירה קובעת איזה Callback URL יווצר ואיך השרת יפרש את ה-webhook.',
        'status' => 'Active = השרת יקבל ויעבד לידים. Draft = הכנה בלבד. Disabled = החיבור שמור אבל כבוי.',
        'verify_token' => 'ב-Meta זה ערך שאתה ממציא בעצמך ומדביק גם כאן וגם במסך ה-Webhooks של Meta. ב-Google Ads Lead Forms השדה הזה לא נדרש.',
        'external_account_id' => 'מזהה החשבון העסקי. ב-Meta זה בדרך כלל Business Manager ID, ובמערכות אחרות זה יכול להיות Advertiser/Account ID.',
        'external_page_id' => 'מזהה העמוד או ה-asset שממנו יוצאים הלידים. ב-Meta זה בדרך כלל Page ID של דף הפייסבוק המחובר לטופס. ב-Google אפשר להשתמש בשדה הזה לצורך Campaign / Customer ID אם אתם רוצים לשמור הקשר נוסף.',
        'callback_url' => 'זו הכתובת שהפלטפורמה צריכה לקרוא אליה כשהגיע ליד חדש. מעתיקים אותה למסך ה-webhook של הפלטפורמה.',
        'verify_url' => 'ב-Meta זו כתובת האימות הראשונית של ה-webhook. משתמשים בה יחד עם ה-Verify Token בזמן החיבור.',
        'access_token' => 'אסימון גישה של הפלטפורמה. ב-Meta אפשר להוציא דרך <a href="https://developers.facebook.com/tools/explorer/" target="_blank" rel="noopener">Graph API Explorer</a> או OAuth של האפליקציה. בלי Access Token המערכת לא תוכל למשוך פרטי ליד מלאים מ-Meta.',
        'refresh_token' => 'אסימון לחידוש Access Token אם הפלטפורמה מספקת אותו. כרגע המערכת שומרת אותו אבל לא מרעננת אוטומטית.',
        'webhook_secret' => 'סוד לאימות webhook. ב-Google Ads Lead Forms זהו ה-Webhook Key: Google שולחת אותו כ-google_key והשרת בודק שהוא תואם לערך השמור.',
        'config_json' => 'שדה מתקדם להגדרות נוספות שלא קיבלו שדה נפרד, למשל שם עמוד, מזהים מיוחדים או הערות טכניות.',
        'notes' => 'הערות פנימיות לצוות: מי יצר את החיבור, מאיפה הגיע ה-token, ומה עוד צריך לזכור.',
        'edit_sensitive' => 'במסך עריכה אפשר להשאיר שדות סודיים ריקים כדי לשמור את הערך שכבר קיים במערכת.',
        'external_form_id' => 'מזהה הטופס בפלטפורמה. ב-Meta רואים אותו בתוך Instant Forms או באירוע שמגיע מה-webhook. ב-Google Ads Lead Forms זהו form_id.',
        'external_form_name' => 'שם ידידותי לטופס בתוך המערכת. אפשר לרשום ידנית כדי לזהות מהר לאיזה קמפיין הוא שייך.',
        'default_owner_id' => 'מי יקבל בעלות על לידים שמגיעים מהטופס הזה אם לא מגיע owner אחר מתוך המידע החיצוני.',
        'field_map_json' => 'מיפוי בין שמות השדות ב-CRM לבין הנתיב שממנו להביא את הערך ב-payload. צד שמאל = שדה אצלכם, צד ימין = נתיב במידע החיצוני. ב-Google אפשר למפות גם שדות מתוך google_fields.COLUMN_ID.',
        'mapping_notes' => 'הערות פנימיות על הטופס או על המיפוי.',
        'mapping_active' => 'רק מיפוי פעיל ישמש לעיבוד לידים מהטופס הזה.',
        'connection_test' => 'כפתור הבדיקה בודק את ההגדרות מול הפלטפורמה שנבחרה. ב-Meta הוא גם בודק token, גישה לעמוד וטפסים. ב-Google הוא בודק שיש Webhook Key והגדרות מקומיות מוכנות.',
    ];
    $renderFieldHelp = function (string $key) use ($fieldHelp): \Illuminate\Support\HtmlString {
        return new \Illuminate\Support\HtmlString('<div class="form-text small">'.$fieldHelp[$key].'</div>');
    };
@endphp

@section('pageTitle', 'אינטגרציות ולוגי Webhook')
@section('pageSubtitle', 'ניהול חיבורים, מיפוי טפסים, כתובות callback ולוגי שגיאות')

@section('content')
    <style>
        .integration-required-markers div:has(> label.form-label + input[required]) > label.form-label::after,
        .integration-required-markers div:has(> label.form-label + select[required]) > label.form-label::after,
        .integration-required-markers div:has(> label.form-label + textarea[required]) > label.form-label::after {
            content: " *";
            color: var(--bs-danger);
            font-weight: 700;
        }
    </style>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">יש לתקן את השדות הבאים:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-secondary py-2 small">
        שדות שמסומנים ב-<span class="text-danger">*</span> הם חובה. שדות ללא כוכבית הם אופציונליים כרגע לפי הוולידציה בשרת.
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <div class="fw-semibold">חיבור חדש</div>
                </div>
                <div class="card-body integration-required-markers">
                    <div class="alert alert-info py-2 small mb-3">
                        שדות חובה בחיבור חדש: שם חיבור, פלטפורמה, סטטוס.
                        שדות חובה במיפוי טופס: External Form ID.
                    </div>
                    <form method="POST" action="{{ route('admin.integrations.store') }}" data-integration-config-form data-test-url="{{ route('admin.integrations.test') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="integration_name">שם חיבור</label>
                                <input class="form-control" id="integration_name" name="name" value="{{ old('name') }}" required>
                                {{ $renderFieldHelp('name') }}
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="integration_platform">פלטפורמה</label>
                                <select class="form-select" id="integration_platform" name="platform" required>
                                    @foreach ($platformOptions as $platform => $label)
                                        <option value="{{ $platform }}" @selected(old('platform', 'meta') === $platform)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                {{ $renderFieldHelp('platform') }}
                                <div class="form-text">Facebook ו-Instagram משתמשים ב-<span class="font-monospace">meta</span>, Google Ads Lead Forms משתמש ב-<span class="font-monospace">google</span>, ו-TikTok משתמש ב-<span class="font-monospace">tiktok</span>.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="integration_status">סטטוס</label>
                                <select class="form-select" id="integration_status" name="status" required>
                                    @foreach ($statusOptions as $status => $label)
                                        <option value="{{ $status }}" @selected(old('status', 'draft') === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                {{ $renderFieldHelp('status') }}
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="integration_verify_token">Verify Token</label>
                                <input class="form-control" id="integration_verify_token" name="verify_token" value="{{ old('verify_token') }}">
                                {{ $renderFieldHelp('verify_token') }}
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="integration_external_account_id">Account / BM ID</label>
                                <input class="form-control" id="integration_external_account_id" name="external_account_id" value="{{ old('external_account_id') }}">
                                {{ $renderFieldHelp('external_account_id') }}
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="integration_external_page_id">Page / Asset ID</label>
                                <input class="form-control" id="integration_external_page_id" name="external_page_id" value="{{ old('external_page_id') }}">
                                {{ $renderFieldHelp('external_page_id') }}
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="integration_access_token">Access Token</label>
                                <textarea class="form-control" id="integration_access_token" name="access_token" rows="2">{{ old('access_token') }}</textarea>
                                {{ $renderFieldHelp('access_token') }}
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="integration_refresh_token">Refresh Token</label>
                                <textarea class="form-control" id="integration_refresh_token" name="refresh_token" rows="2">{{ old('refresh_token') }}</textarea>
                                {{ $renderFieldHelp('refresh_token') }}
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="integration_webhook_secret">Webhook Secret</label>
                                <input class="form-control" id="integration_webhook_secret" name="webhook_secret" value="{{ old('webhook_secret') }}">
                                {{ $renderFieldHelp('webhook_secret') }}
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="integration_config_json">Config JSON</label>
                                <textarea class="form-control font-monospace" id="integration_config_json" name="config_json" rows="2" placeholder='{"page_name":"My Page"}'>{{ old('config_json') }}</textarea>
                                {{ $renderFieldHelp('config_json') }}
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="integration_notes">הערות</label>
                                <textarea class="form-control" id="integration_notes" name="notes" rows="2"></textarea>
                                {{ $renderFieldHelp('notes') }}
                            </div>
                            <div class="col-12">
                                <div class="alert alert-light border mb-0">
                                    <div class="fw-semibold mb-2">Webhook URL לדוגמה לפי פלטפורמה</div>
                                    <div class="small mb-1">Meta / Facebook / Instagram: <span class="font-monospace">{{ $webhookExamples['meta'] }}</span></div>
                                    <div class="small mb-1">Google Ads Lead Forms: <span class="font-monospace">{{ $webhookExamples['google'] }}</span></div>
                                    <div class="small mb-1">TikTok: <span class="font-monospace">{{ $webhookExamples['tiktok'] }}</span></div>
                                    <div class="small mb-2">Generic: <span class="font-monospace">{{ $webhookExamples['generic'] }}</span></div>
                                    <div class="small text-muted">הנתיב לא תמיד כולל <span class="font-monospace">meta</span>. לפייסבוק ואינסטגרם משתמשים ב-<span class="font-monospace">/webhooks/meta/...</span>, ל-Google ב-<span class="font-monospace">/webhooks/google/...</span>, ולטיקטוק ב-<span class="font-monospace">/webhooks/tiktok/...</span>. אחרי יצירת החיבור המערכת תציג לך את ה-Callback URL המדויק עם ה-<span class="font-monospace">Webhook Key</span> האמיתי.</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-end gap-2 flex-wrap">
                            <button class="btn btn-outline-secondary" type="button" data-integration-test-button>
                                בדיקת חיבור
                            </button>
                            <button class="btn btn-primary" type="submit">יצירת חיבור</button>
                        </div>
                        <div class="mt-3">
                            {{ $renderFieldHelp('connection_test') }}
                        </div>
                        <div class="mt-3" data-integration-test-result hidden></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @forelse ($integrations as $integration)
            @php
                $callbackUrl = match ($integration->platform) {
                    'meta' => route('webhooks.meta.receive', ['integration' => $integration->webhook_key]),
                    'google' => route('webhooks.google.receive', ['integration' => $integration->webhook_key]),
                    'tiktok' => route('webhooks.tiktok.receive', ['integration' => $integration->webhook_key]),
                    default => route('webhooks.generic.receive', ['integration' => $integration->webhook_key]),
                };
                $verifyUrl = $integration->platform === 'meta'
                    ? route('webhooks.meta.verify', ['integration' => $integration->webhook_key])
                    : null;
            @endphp

            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="fw-semibold">{{ $integration->name }}</div>
                            <span class="badge {{ $statusBadgeClasses[$integration->status] ?? 'text-bg-secondary' }}">
                                {{ $statusOptions[$integration->status] ?? $integration->status }}
                            </span>
                            <span class="badge text-bg-light border">{{ $platformOptions[$integration->platform] ?? $integration->platform }}</span>
                        </div>
                        <div class="small text-muted">
                            {{ $integration->webhook_events_count }} אירועים,
                            {{ $integration->failed_webhook_events_count }} כשלים
                        </div>
                    </div>
                    <div class="card-body integration-required-markers">
                        <div class="alert alert-info py-2 small mb-3">
                            שדות חובה בעריכת חיבור: שם חיבור, פלטפורמה, סטטוס.
                            שדה חובה במיפוי טופס: External Form ID.
                        </div>
                        @if ($integration->platform === 'meta')
                            <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div class="small">
                                    כדי להגיע לטופס ב-Meta פתח את ספריית ה-Instant Forms ואתר אותו לפי ה-Form ID שמופיע כאן.
                                </div>
                                <a class="btn btn-sm btn-outline-primary" href="{{ $metaFormsLibraryUrl }}" target="_blank" rel="noopener">
                                    פתיחת ספריית הטפסים
                                </a>
                            </div>
                        @elseif ($integration->platform === 'google')
                            <div class="alert alert-light border mb-3 small">
                                ב-Google Ads Lead Forms מזינים את <span class="font-monospace">Callback URL</span> בשדה Webhook URL
                                ואת הערך של <span class="font-monospace">Webhook Secret</span> בשדה Webhook Key.
                                הלידים יגיעו בפורמט Google Lead Form Webhook והשרת יאמת את <span class="font-monospace">google_key</span>.
                            </div>
                        @endif
                        <form method="POST" action="{{ route('admin.integrations.update', $integration) }}" data-integration-config-form data-test-url="{{ route('admin.integrations.test.saved', $integration) }}">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">שם חיבור</label>
                                    <input class="form-control" name="name" value="{{ $integration->name }}" required>
                                    {{ $renderFieldHelp('name') }}
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">פלטפורמה</label>
                                    <select class="form-select" name="platform" required>
                                        @foreach ($platformOptions as $platform => $label)
                                            <option value="{{ $platform }}" @selected($integration->platform === $platform)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    {{ $renderFieldHelp('platform') }}
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">סטטוס</label>
                                    <select class="form-select" name="status" required>
                                        @foreach ($statusOptions as $status => $label)
                                            <option value="{{ $status }}" @selected($integration->status === $status)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    {{ $renderFieldHelp('status') }}
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Verify Token</label>
                                    <input class="form-control" name="verify_token" value="{{ $integration->verify_token }}">
                                    {{ $renderFieldHelp('verify_token') }}
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Account / BM ID</label>
                                    <input class="form-control" name="external_account_id" value="{{ $integration->external_account_id }}">
                                    {{ $renderFieldHelp('external_account_id') }}
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Page / Asset ID</label>
                                    <input class="form-control" name="external_page_id" value="{{ $integration->external_page_id }}">
                                    {{ $renderFieldHelp('external_page_id') }}
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Callback URL</label>
                                    <input class="form-control font-monospace" value="{{ $callbackUrl }}" readonly>
                                    {{ $renderFieldHelp('callback_url') }}
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Verify URL</label>
                                    {{ $renderFieldHelp('verify_url') }}
                                    <input class="form-control font-monospace" value="{{ $verifyUrl ?: 'לא נדרש עבור פלטפורמה זו' }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Access Token</label>
                                    {{ $renderFieldHelp('access_token') }}
                                    <textarea class="form-control" name="access_token" rows="2" placeholder="השאר ריק כדי לשמור את הערך הקיים"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Refresh Token</label>
                                    {{ $renderFieldHelp('refresh_token') }}
                                    <textarea class="form-control" name="refresh_token" rows="2" placeholder="השאר ריק כדי לשמור את הערך הקיים"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Webhook Secret</label>
                                    {{ $renderFieldHelp('webhook_secret') }}
                                    <input class="form-control" name="webhook_secret" placeholder="השאר ריק כדי לשמור את הערך הקיים">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Config JSON</label>
                                    {{ $renderFieldHelp('config_json') }}
                                    <textarea class="form-control font-monospace" name="config_json" rows="2">{{ $integration->config ? json_encode($integration->config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '' }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">הערות</label>
                                    <textarea class="form-control" name="notes" rows="2">{{ $integration->notes }}</textarea>
                                    {{ $renderFieldHelp('notes') }}
                                </div>
                            </div>
                            <div class="mt-3 d-flex justify-content-between flex-wrap gap-2">
                                <div class="small text-muted">
                                    Webhook Key: <span class="font-monospace">{{ $integration->webhook_key }}</span>
                                    @if ($integration->last_webhook_at)
                                        <span class="ms-2">עודכן לאחרונה: {{ $integration->last_webhook_at->format('Y-m-d H:i') }}</span>
                                    @endif
                                    @if ($integration->last_error_message)
                                        <span class="d-block text-danger mt-1">{{ $integration->last_error_message }}</span>
                                    @endif
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="btn btn-outline-secondary" type="button" data-integration-test-button>
                                        בדיקת חיבור
                                    </button>
                                    <button class="btn btn-primary" type="submit">שמירת שינויים</button>
                                </div>
                            </div>
                            <div class="mt-3">
                                {{ $renderFieldHelp('edit_sensitive') }}
                                {{ $renderFieldHelp('connection_test') }}
                            </div>
                            <div class="mt-3" data-integration-test-result hidden></div>
                        </form>
                                    <form method="POST" action="{{ route('admin.integrations.destroy', $integration) }}" onsubmit="return confirm('למחוק את החיבור הזה?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger" type="submit">מחיקה</button>
                                    </form>
                                </div>
                            </div>

                        <hr class="my-4">

                        <div class="row g-4">
                            <div class="col-xl-5">
                                <h2 class="h6 mb-3">מיפוי טפסים</h2>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <thead>
                                            <tr>
                                                <th>Form ID <span class="text-danger">*</span></th>
                                                <th>שם</th>
                                                <th>אחראי</th>
                                                <th>פעיל</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($integration->formMappings as $mapping)
                                                <tr>
                                                    <td class="font-monospace">{{ $mapping->external_form_id }}</td>
                                                    <td>{{ $mapping->external_form_name ?: '-' }}</td>
                                                    <td>{{ $mapping->defaultOwner?->name ?: 'ללא שיוך' }}</td>
                                                    <td>
                                                        <span class="badge {{ $mapping->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                            {{ $mapping->is_active ? 'כן' : 'לא' }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                                            @if ($integration->platform === 'meta')
                                                                <a class="btn btn-sm btn-outline-primary" href="{{ $metaFormsLibraryUrl }}" target="_blank" rel="noopener" title="פתח את ספריית Instant Forms של Meta וחפש לפי Form ID {{ $mapping->external_form_id }}">
                                                                    פתח ב-Meta
                                                                </a>
                                                            @endif
                                                            <form method="POST" action="{{ route('admin.integrations.mappings.destroy', $mapping) }}" onsubmit="return confirm('למחוק את מיפוי הטופס?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger" type="submit">מחיקה</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="5">
                                                        <form method="POST" action="{{ route('admin.integrations.mappings.update', $mapping) }}" class="row g-2">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="col-md-3">
                                                                <input class="form-control form-control-sm" name="external_form_id" value="{{ $mapping->external_form_id }}" required>
                                                                {{ $renderFieldHelp('external_form_id') }}
                                                            </div>
                                                            <div class="col-md-3">
                                                                <input class="form-control form-control-sm" name="external_form_name" value="{{ $mapping->external_form_name }}">
                                                                {{ $renderFieldHelp('external_form_name') }}
                                                            </div>
                                                            <div class="col-md-2">
                                                                <select class="form-select form-select-sm" name="default_owner_id">
                                                                    <option value="">ללא שיוך</option>
                                                                    @foreach ($users as $user)
                                                                        <option value="{{ $user->id }}" @selected($mapping->default_owner_id === $user->id)>{{ $user->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                                {{ $renderFieldHelp('default_owner_id') }}
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-check pt-2">
                                                                    <input class="form-check-input" id="mapping_active_{{ $mapping->id }}" name="is_active" type="checkbox" value="1" @checked($mapping->is_active)>
                                                                    {{ $renderFieldHelp('mapping_active') }}
                                                                    <label class="form-check-label small" for="mapping_active_{{ $mapping->id }}">פעיל</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-12">
                                                                <textarea class="form-control form-control-sm font-monospace" name="field_map_json" rows="2" placeholder='{"email":"email","phone":"phone"}'>{{ $mapping->field_map ? json_encode($mapping->field_map, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '' }}</textarea>
                                                                {{ $renderFieldHelp('field_map_json') }}
                                                            </div>
                                                            <div class="col-md-10">
                                                                {{ $renderFieldHelp('mapping_notes') }}
                                                                <input class="form-control form-control-sm" name="notes" value="{{ $mapping->notes }}" placeholder="הערות">
                                                            </div>
                                                            <div class="col-md-2 d-grid">
                                                                <button class="btn btn-sm btn-outline-primary" type="submit">עדכון</button>
                                                            </div>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-3">אין עדיין מיפויים לחיבור זה.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="col-xl-7">
                                <h2 class="h6 mb-3">הוספת מיפוי חדש</h2>
                                <div class="alert alert-info py-2 small">
                                    שדה חובה במיפוי טופס: External Form ID.
                                </div>
                                <form method="POST" action="{{ route('admin.integrations.mappings.store', $integration) }}">
                                    @csrf
                                    <input type="hidden" name="integration_context" value="{{ $integration->id }}">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">External Form ID</label>
                                            <input class="form-control" name="external_form_id" value="{{ (string) old('integration_context') === (string) $integration->id ? old('external_form_id') : '' }}" required>
                                            {{ $renderFieldHelp('external_form_id') }}
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">שם טופס</label>
                                            <input class="form-control" name="external_form_name" value="{{ (string) old('integration_context') === (string) $integration->id ? old('external_form_name') : '' }}">
                                            {{ $renderFieldHelp('external_form_name') }}
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">אחראי ברירת מחדל</label>
                                            <select class="form-select" name="default_owner_id">
                                                <option value="">ללא שיוך</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}" @selected((string) old('integration_context') === (string) $integration->id && (string) old('default_owner_id') === (string) $user->id)>{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                            {{ $renderFieldHelp('default_owner_id') }}
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Field Map JSON</label>
                                            <textarea class="form-control font-monospace" name="field_map_json" rows="3" placeholder='{"email":"email","phone":"phone","company":"company_name"}'>{{ (string) old('integration_context') === (string) $integration->id ? old('field_map_json') : '' }}</textarea>
                                            {{ $renderFieldHelp('field_map_json') }}
                                            <div class="form-text">הפורמט המומלץ: שדה CRM משמאל, מפתח/נתיב payload מימין.</div>
                                        </div>
                                        <div class="col-md-9">
                                            <label class="form-label">הערות</label>
                                            <input class="form-control" name="notes" value="{{ (string) old('integration_context') === (string) $integration->id ? old('notes') : '' }}">
                                            {{ $renderFieldHelp('mapping_notes') }}
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check mt-4 pt-2">
                                                <input class="form-check-input" id="create_mapping_active_{{ $integration->id }}" name="is_active" type="checkbox" value="1" @checked((string) old('integration_context') === (string) $integration->id ? old('is_active', '1') : '1')>
                                                {{ $renderFieldHelp('mapping_active') }}
                                                <label class="form-check-label" for="create_mapping_active_{{ $integration->id }}">מיפוי פעיל</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 d-flex justify-content-end">
                                        <button class="btn btn-outline-primary" type="submit">שמירת מיפוי</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center text-muted py-5">אין עדיין חיבורים מוגדרים.</div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <div class="fw-semibold">לוגי Webhook אחרונים</div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>זמן</th>
                            <th>חיבור</th>
                            <th>פלטפורמה</th>
                            <th>אירוע</th>
                            <th>Form ID</th>
                            <th>סטטוס</th>
                            <th>ליד</th>
                            <th>שגיאה / הערה</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($webhookEvents as $event)
                            <tr>
                                <td>{{ $event->id }}</td>
                                <td>{{ optional($event->received_at)->format('Y-m-d H:i') ?: $event->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $event->integration?->name ?: 'לא משויך' }}</td>
                                <td>{{ $platformOptions[$event->platform] ?? $event->platform }}</td>
                                <td>{{ $event->event_type ?: '-' }}</td>
                                <td class="font-monospace">{{ $event->external_form_id ?: '-' }}</td>
                                <td>
                                    <span class="badge {{
                                        match($event->status) {
                                            'processed' => 'text-bg-success',
                                            'failed' => 'text-bg-danger',
                                            'pending_fetch' => 'text-bg-warning',
                                            'processing' => 'text-bg-info',
                                            default => 'text-bg-secondary',
                                        }
                                    }}">
                                        {{ $event->status }}
                                    </span>
                                </td>
                                <td>
                                    @if ($event->lead)
                                        <a href="{{ route('leads.edit', $event->lead) }}">#{{ $event->lead->id }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $event->error_message ?: '-' }}

                                    @if (! empty($event->payload))
                                        <details class="mt-2">
                                            <summary class="text-primary" style="cursor: pointer;">הצג payload שהתקבל</summary>
                                            <pre class="mt-2 mb-0 p-2 bg-light border rounded small text-wrap">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">אין עדיין אירועי webhook.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const statusClasses = {
                success: 'success',
                warning: 'warning',
                error: 'danger',
                info: 'info',
            };
            const statusLabels = {
                success: 'תקין',
                warning: 'לתשומת לב',
                error: 'שגיאה',
                info: 'מידע',
            };
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const escapeHtml = (value) => {
                const element = document.createElement('div');
                element.textContent = value ?? '';
                return element.innerHTML;
            };

            const renderChecks = (checks = []) => {
                if (!Array.isArray(checks) || checks.length === 0) {
                    return '';
                }

                return `
                    <ul class="list-group list-group-flush mt-3">
                        ${checks.map((check) => {
                            const statusClass = statusClasses[check.status] || 'secondary';
                            const statusLabel = statusLabels[check.status] || 'מידע';

                            return `
                                <li class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <div class="fw-semibold">${escapeHtml(check.label)}</div>
                                            <div class="small text-muted">${escapeHtml(check.message)}</div>
                                        </div>
                                        <span class="badge text-bg-${statusClass}">${escapeHtml(statusLabel)}</span>
                                    </div>
                                </li>
                            `;
                        }).join('')}
                    </ul>
                `;
            };

            const renderResult = (container, payload) => {
                const statusClass = statusClasses[payload.status] || 'secondary';
                const statusLabel = statusLabels[payload.status] || 'מידע';

                container.hidden = false;
                container.innerHTML = `
                    <div class="card border-${statusClass}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <div class="fw-semibold">תוצאת בדיקה</div>
                                    <div class="small text-muted">${escapeHtml(payload.checked_at || '')}</div>
                                </div>
                                <span class="badge text-bg-${statusClass}">${escapeHtml(statusLabel)}</span>
                            </div>
                            <div class="mt-2">${escapeHtml(payload.summary || '')}</div>
                            ${renderChecks(payload.checks)}
                        </div>
                    </div>
                `;
            };

            const renderError = (container, message, errors = {}) => {
                const validationLines = Object.values(errors)
                    .flat()
                    .map((item) => `<li>${escapeHtml(item)}</li>`)
                    .join('');

                container.hidden = false;
                container.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        <div class="fw-semibold mb-2">${escapeHtml(message || 'בדיקת החיבור נכשלה.')}</div>
                        ${validationLines ? `<ul class="mb-0 ps-3">${validationLines}</ul>` : ''}
                    </div>
                `;
            };

            document.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-integration-test-button]');
                if (!button) {
                    return;
                }

                const form = button.closest('form[data-integration-config-form]');
                const testResult = form?.querySelector('[data-integration-test-result]');
                const testUrl = form?.dataset.testUrl;

                if (!form || !testResult || !testUrl) {
                    return;
                }

                const originalLabel = button.textContent;
                const formData = new FormData(form);
                formData.delete('_method');

                button.disabled = true;
                button.textContent = 'בודק...';
                testResult.hidden = false;
                testResult.innerHTML = '<div class="alert alert-secondary mb-0">המערכת בודקת את ההגדרות מול השרת...</div>';

                try {
                    const response = await fetch(testUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken ?? '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    });

                    let payload = null;

                    try {
                        payload = await response.json();
                    } catch (error) {
                        payload = null;
                    }

                    if (!response.ok) {
                        renderError(
                            testResult,
                            payload?.message || 'בדיקת החיבור נכשלה.',
                            payload?.errors || {}
                        );

                        return;
                    }

                    renderResult(testResult, payload);
                } catch (error) {
                    console.error(error);
                    renderError(testResult, 'לא הצלחתי לבדוק את החיבור כרגע. בדוק שהשרת יכול לצאת לאינטרנט ונסה שוב.');
                } finally {
                    button.disabled = false;
                    button.textContent = originalLabel;
                }
            });
        })();
    </script>
@endpush
