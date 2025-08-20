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
      <a class="navbar-brand fw-semibold" href="#"><i class="bi bi-boxes me-2"></i>CRM</a>
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
                <h1 class="h4 mb-2">New Lead</h1>
                <div class="text-muted">Add a potential customer to your pipeline</div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label for="firstName" class="form-label required">First Name</label>
                <input type="text" class="form-control" id="firstName" name="first_name" required>
                <div class="invalid-feedback">Please enter the first name.</div>
              </div>
              <div class="col-md-6">
                <label for="lastName" class="form-label required">Last Name</label>
                <input type="text" class="form-control" id="lastName" name="last_name" required>
                <div class="invalid-feedback">Please enter the last name.</div>
              </div>
              <div class="col-md-8">
                <label for="email" class="form-label required">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div class="invalid-feedback">Please enter a valid email.</div>
              </div>
              <div class="col-md-4">
                <label for="phone" class="form-label">Phone</label>
                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+972-50-123-4567">
              </div>
              <div class="col-md-12">
                <label for="company" class="form-label">Company</label>
                <input type="text" class="form-control" id="company" name="company" placeholder="Company Ltd.">
              </div>
              <div class="col-md-6">
                <label for="title" class="form-label">Job Title</label>
                <input type="text" class="form-control" id="title" name="job_title" placeholder="Procurement Manager">
              </div>
              <div class="col-md-6">
                <label for="website" class="form-label">Website</label>
                <input type="url" class="form-control" id="website" name="website" placeholder="https://example.com">
              </div>
            </div>

            <hr class="my-4">
            <div class="form-section-title mb-2">Lead Details</div>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="leadSource" class="form-label">Lead Source</label>
                <select class="form-select" id="leadSource" name="source">
                  <option value="">Choose...</option>
                  <option>Website</option>
                  <option>Inbound Call</option>
                  <option>Outbound</option>
                  <option>Referral</option>
                  <option>Event</option>
                  <option>Social</option>
                  <option>Partner</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                  <option>New</option>
                  <option>Contacted</option>
                  <option>Qualified</option>
                  <option>Unqualified</option>
                  <option>Disqualified</option>
                </select>
              </div>
              <div class="col-md-4">
                <label for="priority" class="form-label">Priority</label>
                <select class="form-select" id="priority" name="priority">
                  <option>Medium</option>
                  <option>High</option>
                  <option>Low</option>
                </select>
              </div>
              <div class="col-md-4">
                <label for="expectedValue" class="form-label">Expected Value ($)</label>
                <input type="number" min="0" step="1" class="form-control" id="expectedValue" name="expected_value" placeholder="0">
              </div>
              <div class="col-md-4">
                <label for="followUp" class="form-label">Follow-up Date</label>
                <input type="date" class="form-control" id="followUp" name="follow_up">
              </div>

              <!-- Tags (chips) -->
              <div class="col-12">
                <label class="form-label">Tags</label>
                <div class="chip-input">
                  <input id="tagsInput" type="text" class="form-control" placeholder="Type a tag and press Enter">
                  <div id="tagsArea" class="mt-2"></div>
                </div>
                <!-- hidden container for tags[] -->
                <div id="tagsHiddenContainer"></div>
              </div>
            </div>

            <hr class="my-4">
            <div class="form-section-title mb-2">Address</div>
            <div class="row g-3">
              <div class="col-md-8">
                <label for="street" class="form-label">Street</label>
                <input type="text" class="form-control" id="street" name="street">
              </div>
              <div class="col-md-4">
                <label for="zip" class="form-label">ZIP</label>
                <input type="text" class="form-control" id="zip" name="zip">
              </div>
              <div class="col-md-6">
                <label for="city" class="form-label">City</label>
                <input type="text" class="form-control" id="city" name="city">
              </div>
              <div class="col-md-6">
                <label for="country" class="form-label">Country</label>
                <input type="text" class="form-control" id="country" name="country" placeholder="Israel">
              </div>
            </div>

            <hr class="my-4">
            <div class="form-section-title mb-2">Notes</div>
            <div class="mb-3">
              <label for="notes" class="form-label">Internal Notes</label>
              <textarea id="notes" name="notes" rows="5" class="form-control" placeholder="Key pains, budget, timeline, competitors…"></textarea>
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
              <label for="attachment" class="form-label">Attachment (optional)</label>
              <input class="form-control" type="file" id="attachment" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx">
            </div>
          </div>
        </div>
      </div>

      <!-- Right column -->
      <div class="col-12 col-lg-4">
        <div class="card mb-4">
          <div class="card-body">
            <div class="form-section-title mb-2">Assignment</div>
            <div class="mb-3">
              <label for="owner" class="form-label">Owner</label>
              <select id="owner" name="owner" class="form-select">
                <option value="">Unassigned</option>
                <option value="me">Me</option>
                <option value="sales-1">Sales – North</option>
                <option value="sales-2">Sales – EMEA</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="pipeline" class="form-label">Pipeline</label>
              <select id="pipeline" name="pipeline" class="form-select">
                <option>Default</option>
                <option>Enterprise</option>
                <option>SMB</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="stage" class="form-label">Stage</label>
              <select id="stage" name="stage" class="form-select">
                <option>Lead</option>
                <option>MQL</option>
                <option>SQL</option>
              </select>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="form-section-title mb-2">Visibility</div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="visibility" id="vis-team" value="team" checked>
              <label class="form-check-label" for="vis-team">Team</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="visibility" id="vis-private" value="private">
              <label class="form-check-label" for="vis-private">Private</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sticky actions -->
    <div class="sticky-actions mt-4 p-3 border-top">
      <div class="container">
        <div class="d-flex gap-2 justify-content-end">
          <button type="reset" class="btn btn-outline-secondary">
            Reset
          </button>

          <!-- שולח עם פרמטר save_new=1 -->
          <button type="submit" name="save_new" value="1" class="btn btn-outline-primary">
            Save & New
          </button>

          <button type="submit" class="btn btn-primary">
            Save Lead
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
