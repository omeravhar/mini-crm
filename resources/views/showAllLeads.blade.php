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





 <div class="container mt-4">

  <!-- Table 1: Unassigned Leads -->
  <h3>לידים חדשים (ללא הקצאה)</h3>
  <table class="table table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th scope="col">#</th>
        <th scope="col">שם</th>
        <th scope="col">דוא"ל</th>
        <th scope="col">טלפון</th>
        <th scope="col">מקור</th>
        <th scope="col">הקצאה למשתמש</th>
      </tr>
    </thead>
    <tbody>
      <tr class="lead-row" data-lead-id="1">
        <th scope="row">1</th>
        <td>David Cohen</td>
        <td>david@example.com</td>
        <td>+972-50-1234567</td>
        <td>Website</td>
        <td>
          <form onsubmit="assignLead(event, 1)">
            <select class="form-select" name="user_id" required onchange="this.form.submit()">
              <option value="">בחר משתמש...</option>
              <option value="101">אליס</option>
              <option value="102">בוב</option>
              <option value="103">צ'רלי</option>
            </select>
          </form>
        </td>
      </tr>
    </tbody>
  </table>

  <!-- Table 2: Assigned Leads -->
  <h3 class="mt-5">לידים מוקצים</h3>
  <table class="table table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th>מספר ליד</th>
        <th>שם</th>
        <th>דוא"ל</th>
        <th>משתמש מוקצה</th>
        <th>סטטוס</th>
        <th>שינוי הקצאה</th>
      </tr>
    </thead>
    <tbody>
      <tr class="lead-row" data-lead-id="201">
        <td>201</td>
        <td>נועה ישראלי</td>
        <td>noa@example.com</td>
        <td>אליס</td>
        <td><span class="badge bg-warning text-dark" data-status="Pending">ממתין</span></td>
        <td>
          <form onsubmit="reassignLead(event, 201)">
            <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="1" selected>אליס</option>
              <option value="2">בוב</option>
            </select>
          </form>
        </td>
      </tr>

      <tr class="lead-row" data-lead-id="202">
        <td>202</td>
        <td>מייקל לוי</td>
        <td>mike@example.com</td>
        <td>בוב</td>
        <td><span class="badge bg-success" data-status="Closed">נסגר</span></td>
        <td>
          <form onsubmit="reassignLead(event, 202)">
            <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="1">אליס</option>
              <option value="2" selected>בוב</option>
            </select>
          </form>
        </td>
      </tr>

      <tr class="lead-row" data-lead-id="203">
        <td>203</td>
        <td>דוד ירוק</td>
        <td>david@example.com</td>
        <td>צ'רלי</td>
        <td><span class="badge bg-danger" data-status="Rejected">נדחה</span></td>
        <td>
          <form onsubmit="reassignLead(event, 203)">
            <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="3" selected>צ'רלי</option>
              <option value="1">אליס</option>
              <option value="2">בוב</option>
            </select>
          </form>
        </td>
      </tr>
    </tbody>
  </table>
</div>

<!-- Lead Details Modal -->
<div class="modal fade" id="leadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">פרטי ליד</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <h6>פרטי ליד</h6>
        <div id="leadDetails"></div>
        <hr>
        <h6>היסטוריה</h6>
        <ul class="list-group" id="leadHistory"></ul>
        <hr>
        <h6>פעולות</h6>
        <form id="updateLeadForm" onsubmit="updateLead(event)">
          <input type="hidden" name="lead_id" id="leadIdInput">
          <div class="mb-3">
            <label class="form-label">שינוי סטטוס</label>
            <select class="form-select" name="status" id="leadStatusSelect">
              <option value="Pending">ממתין</option>
              <option value="In Progress">בתהליך</option>
              <option value="Closed">נסגר</option>
              <option value="Rejected">נדחה</option>
              <option value="On Hold">בהמתנה</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">עדכון ליד</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  // Assign new lead to a user
  function assignLead(event, leadId) {
    event.preventDefault();
    const userId = event.target.user_id.value;
    console.log(`Assigning lead ${leadId} to user ${userId}`);
    location.reload(); // simulate refresh
  }

  // Reassign existing lead to another user
  function reassignLead(event, leadId) {
    event.preventDefault();
    const userId = event.target.user_id.value;
    console.log(`Reassigning lead ${leadId} to user ${userId}`);
    location.reload(); // simulate refresh
  }

  // Click row -> open modal
  document.querySelectorAll(".lead-row").forEach(row => {
    row.addEventListener("click", function(e) {
      if (e.target.tagName.toLowerCase() === "select") return; // ignore clicks on dropdowns
      const leadId = this.dataset.leadId;
      fetchLeadDetails(leadId);
    });
  });

  // Fetch lead details (simulate AJAX)
  function fetchLeadDetails(leadId) {
    console.log("Fetching details for lead", leadId);
    // Dummy data
    const data = {
      id: leadId,
      name: "ליד לדוגמה",
      email: "lead@example.com",
      phone: "+972-50-0000000",
      status: "Pending",
      user_name: "אליס",
      history: [
        { date: "2025-08-01", action: "הליד נוצר במערכת" },
        { date: "2025-08-05", action: "הוקצה לאליס" }
      ]
    };

    // Fill modal
    document.getElementById("leadIdInput").value = data.id;
    document.getElementById("leadStatusSelect").value = data.status;
    document.getElementById("leadDetails").innerHTML = `
      <p><strong>מזהה:</strong> ${data.id}</p>
      <p><strong>שם:</strong> ${data.name}</p>
      <p><strong>דוא"ל:</strong> ${data.email}</p>
      <p><strong>טלפון:</strong> ${data.phone}</p>
      <p><strong>סטטוס:</strong> ${renderStatusBadge(data.status)}</p>
      <p><strong>משתמש מטפל:</strong> ${data.user_name}</p>
    `;

    let historyHtml = "";
    if (data.history.length > 0) {
      data.history.forEach(h => {
        historyHtml += `<li class="list-group-item"><strong>${h.date}:</strong> ${h.action}</li>`;
      });
    } else {
      historyHtml = `<li class="list-group-item">אין היסטוריה זמינה.</li>`;
    }
    document.getElementById("leadHistory").innerHTML = historyHtml;

    new bootstrap.Modal(document.getElementById("leadModal")).show();
  }

  // Update lead (simulate AJAX)
  function updateLead(event) {
    event.preventDefault();
    const leadId = document.getElementById("leadIdInput").value;
    const status = document.getElementById("leadStatusSelect").value;
    console.log(`Updating lead ${leadId} with status ${status}`);
    // Example AJAX call here
    location.reload();
  }

  // Render status badge
  function renderStatusBadge(status) {
    let badgeClass = "bg-light text-dark";
    let label = status;
    switch (status.toLowerCase()) {
      case "pending":
        badgeClass = "bg-warning text-dark";
        label = "ממתין";
        break;
      case "in progress":
        badgeClass = "bg-primary";
        label = "בתהליך";
        break;
      case "closed":
        badgeClass = "bg-success";
        label = "נסגר";
        break;
      case "rejected":
        badgeClass = "bg-danger";
        label = "נדחה";
        break;
      case "on hold":
        badgeClass = "bg-secondary";
        label = "בהמתנה";
        break;
      default:
        label = status;
        break;
    }
    return `<span class="badge ${badgeClass}">${label}</span>`;
  }
</script>


@endsection
