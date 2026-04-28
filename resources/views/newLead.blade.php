@extends('main')
@section('content')

<style>
    body { background: #f6f7fb; }
    .card { border: 0; box-shadow: 0 6px 24px rgba(0,0,0,.06); }
    .form-section-title { font-size: .9rem; letter-spacing: .08em; text-transform: uppercase; color: #6c757d; }
    .required::after { content: " *"; color: #dc3545; }
    .chip-input .badge { margin: .15rem; }
    .sticky-actions { position: sticky; bottom: 0; background: #fff; box-shadow: 0 -6px 24px rgba(0,0,0,.06); z-index: 5; }
  </style>
  <!-- <nav class="navbar navbar-expand-lg bg-white border-bottom small">
    <div class="container-fluid">
      <a class="navbar-brand fw-semibold" href="#"><i class="bi bi-boxes me-2"></i>EeasyCRM</a>
      <span class="navbar-text text-muted">Create New Lead</span>
    </div>
  </nav> -->

  <main class="container py-4">

 <!-- Success message -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

   <form id="leadForm"
        class="needs-validation"
        method="POST"
        action="{{ route('admin.saveNewLead') }}"
        enctype="multipart/form-data"
        novalidate>
    @csrf

    <div class="row g-4">
      <!-- Left column -->
      <div class="col-12 col-lg-8">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center mb-3">
              <div>
                <h1 class="h4 mb-2">ליד חדש</h1>
                <div class="text-muted">הוסיפו לקוח פוטנציאלי למשפך המכירות</div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label for="firstName" class="form-label required">שם פרטי</label>
                <input type="text" class="form-control" id="firstName" name="first_name" required>
                <div class="invalid-feedback">נא להזין שם פרטי.</div>
              </div>
              <div class="col-md-6">
                <label for="lastName" class="form-label required">שם משפחה</label>
                <input type="text" class="form-control" id="lastName" name="last_name" required>
                <div class="invalid-feedback">נא להזין שם משפחה.</div>
              </div>
              <div class="col-md-8">
                <label for="email" class="form-label required">דוא"ל</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div class="invalid-feedback">נא להזין דוא"ל תקין.</div>
              </div>
              <div class="col-md-4">
                <label for="phone" class="form-label">טלפון</label>
                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+972-50-123-4567">
              </div>
              <div class="col-md-12">
                <label for="company" class="form-label">חברה</label>
                <input type="text" class="form-control" id="company" name="company" placeholder="חברת דוגמה בע\"מ">
              </div>
              <div class="col-md-6">
                <label for="title" class="form-label">תפקיד</label>
                <input type="text" class="form-control" id="title" name="job_title" placeholder="מנהל/ת רכש">
              </div>
              <div class="col-md-6">
                <label for="website" class="form-label">אתר אינטרנט</label>
                <input type="url" class="form-control" id="website" name="website" placeholder="https://example.com">
              </div>
            </div>

            <hr class="my-4">
            <div class="form-section-title mb-2">פרטי ליד</div>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="leadSource" class="form-label">מקור ליד</label>
                <select class="form-select" id="leadSource" name="source">
                  <option value="">בחר...</option>
                  <option value="website">אתר אינטרנט</option>
                  <option value="inbound_call">שיחה נכנסת</option>
                  <option value="outbound">פנייה יוצאת</option>
                  <option value="referral">הפניה</option>
                  <option value="event">אירוע</option>
                  <option value="social">רשת חברתית</option>
                  <option value="partner">שותף</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="status" class="form-label">סטטוס</label>
                <select class="form-select" id="status" name="status">
                  <option value="New">חדש</option>
                  <option value="Contacted">בוצע קשר</option>
                  <option value="Qualified">אושר</option>
                  <option value="Unqualified">לא הוגדר</option>
                  <option value="Disqualified">פסול</option>
                </select>
              </div>
              <div class="col-md-4">
                <label for="priority" class="form-label">עדיפות</label>
                <select class="form-select" id="priority" name="priority">
                  <option value="Medium">בינונית</option>
                  <option value="High">גבוהה</option>
                  <option value="Low">נמוכה</option>
                </select>
              </div>
              <div class="col-md-4">
                <label for="expectedValue" class="form-label">שווי צפוי ($)</label>
                <input type="number" min="0" step="1" class="form-control" id="expectedValue" name="expected_value" placeholder="0">
              </div>
              <div class="col-md-4">
                <label for="followUp" class="form-label">תאריך מעקב</label>
                <input type="date" class="form-control" id="followUp" name="follow_up">
              </div>

              <!-- Tags (chips) -->
              <div class="col-12">
                <label class="form-label">תגיות</label>
                <div class="chip-input">
                  <input id="tagsInput" type="text" class="form-control" placeholder="הקלידו תג ולחצו אנטר">
                  <div id="tagsArea" class="mt-2"></div>
                </div>
                <!-- hidden container for tags[] -->
                <div id="tagsHiddenContainer"></div>
              </div>
            </div>

            <hr class="my-4">
            <div class="form-section-title mb-2">כתובת</div>
            <div class="row g-3">
              <div class="col-md-8">
                <label for="street" class="form-label">רחוב</label>
                <input type="text" class="form-control" id="street" name="street">
              </div>
              <div class="col-md-4">
                <label for="zip" class="form-label">מיקוד</label>
                <input type="text" class="form-control" id="zip" name="zip">
              </div>
              <div class="col-md-6">
                <label for="city" class="form-label">עיר</label>
                <input type="text" class="form-control" id="city" name="city">
              </div>
              <div class="col-md-6">
                <label for="country" class="form-label">מדינה</label>
                <input type="text" class="form-control" id="country" name="country" placeholder="ישראל">
              </div>
            </div>

            <hr class="my-4">
            <div class="form-section-title mb-2">הערות</div>
            <div class="mb-3">
              <label for="notes" class="form-label">הערות פנימיות</label>
              <textarea id="notes" name="notes" rows="5" class="form-control" placeholder="כאבים מרכזיים, תקציב, ציר זמן, מתחרים..."></textarea>
            </div>

            <!-- GDPR (אופציונלי) -->
            {{--
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" value="1" id="gdpr" name="gdpr" required>
              <label class="form-check-label required" for="gdpr">I have consent to store this contact information</label>
              <div class="invalid-feedback">Consent is required.</div>
            </div>
            --}}

            <div class="mb-3">
              <label for="attachment" class="form-label">קובץ מצורף (אופציונלי)</label>
              <input class="form-control" type="file" id="attachment" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx">
            </div>
          </div>
        </div>
      </div>

      <!-- Right column -->
      <div class="col-12 col-lg-4">
        <div class="card mb-4">
          <div class="card-body">
            <div class="form-section-title mb-2">הקצאה</div>
            <div class="mb-3">
              <label for="owner" class="form-label">אחראי</label>
              <select id="owner" name="owner" class="form-select">
                <option value="">ללא הקצאה</option>
                <option value="me">אני</option>
                <option value="sales-1">מכירות – צפון</option>
                <option value="sales-2">מכירות – EMEA</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="pipeline" class="form-label">צינור מכירה</label>
              <select id="pipeline" name="pipeline" class="form-select">
                <option value="default">ברירת מחדל</option>
                <option value="enterprise">ארגונים</option>
                <option value="smb">SMB</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="stage" class="form-label">שלב</label>
              <select id="stage" name="stage" class="form-select">
                <option value="lead">ליד</option>
                <option value="mql">MQL (ליד שיווקי)</option>
                <option value="sql">SQL (ליד מכירתי)</option>
              </select>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="form-section-title mb-2">נראות</div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="visibility" id="vis-team" value="team" checked>
              <label class="form-check-label" for="vis-team">צוות</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="visibility" id="vis-private" value="private">
              <label class="form-check-label" for="vis-private">פרטי</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sticky actions -->
    <div class="sticky-actions mt-4 p-3 border-top">
      <div class="container">
        <div class="d-flex gap-2 justify-content-start">
          <button type="reset" class="btn btn-outline-secondary">
            איפוס
          </button>

          <!-- שולח עם פרמטר save_new=1 -->
          <button type="submit" name="save_new" value="1" class="btn btn-outline-primary">
            שמירה וליד חדש
          </button>

          <button type="submit" class="btn btn-primary">
            שמירת ליד
          </button>
        </div>
      </div>
    </div>
  </form>
</main>

{{-- אין כאן JS שמבטל שליחה. רק ולידציה ותגיות. --}}
<script>
  // Bootstrap validation: לא מבטלים שליחה כאשר תקין
  (function () {
    'use strict';
    const form = document.getElementById('leadForm');
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  })();

  // Tags (chips) -> שולח כ-tags[]
  (function initTags(){
    const input = document.getElementById('tagsInput');
    const area  = document.getElementById('tagsArea');
    const hiddenContainer = document.getElementById('tagsHiddenContainer');

    function rebuildHiddenInputs() {
      // נקה ישנים
      hiddenContainer.innerHTML = '';
      // בנה inputs חדשים
      const badges = area.querySelectorAll('.badge');
      badges.forEach(badge => {
        const val = badge.getAttribute('data-tag');
        if (!val) return;
        const h = document.createElement('input');
        h.type = 'hidden';
        h.name = 'tags[]';
        h.value = val;
        hiddenContainer.appendChild(h);
      });
    }

    function makeBadge(text){
      const badge = document.createElement('span');
      badge.className = 'badge text-bg-secondary';
      badge.setAttribute('data-tag', text);
      badge.textContent = text;
      const close = document.createElement('button');
      close.type = 'button';
      close.className = 'btn-close btn-close-white btn-sm ms-2';
      close.ariaLabel = 'Remove';
      close.addEventListener('click', () => { badge.remove(); rebuildHiddenInputs(); });
      badge.appendChild(close);
      return badge;
    }

    input.addEventListener('keydown', function(e){
      if (e.key === 'Enter' && this.value.trim() !== '') {
        e.preventDefault();
        const tagText = this.value.trim();
        // הימנע מכפילויות
        const exists = Array.from(area.querySelectorAll('.badge'))
          .some(b => b.getAttribute('data-tag')?.toLowerCase() === tagText.toLowerCase());
        if (exists) { this.value=''; return; }
        area.appendChild(makeBadge(tagText));
        this.value = '';
        rebuildHiddenInputs();
      }
    });
  })();
</script>
@endsection
