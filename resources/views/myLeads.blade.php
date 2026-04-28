@extends('main')
@section('content')

<style>
    body { background: #f6f7fb; }
    .card { border: 0; box-shadow: 0 6px 24px rgba(0,0,0,.06); }
    .form-section-title { font-size: .9rem; letter-spacing: .08em; text-transform: uppercase; color: #6c757d; }
    .required::after { content: " *"; color: #dc3545; }
    .chip-input .badge { margin: .15rem; }
    .sticky-actions { position: sticky; bottom: 0; background: #fff; box-shadow: 0 -6px 24px rgba(0,0,0,.06); z-index: 5; }
    table tbody {
  min-height: 50px; /* so you can drop into empty table */
}
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

  <!-- Table 1: New Leads -->
  <h3>לידים חדשים (ללא הקצאה)</h3>
  <table class="table table-bordered" id="unassignedLeads">
    <thead>
      <tr>
        <th>מספר ליד</th>
        <th>שם</th>
        <th>דוא"ל</th>
        <th>הקצאה</th>
      </tr>
    </thead>
    <tbody>
      <tr draggable="true" data-lead-id="101" onclick="openLeadModal(this)">
        <td>101</td>
        <td>אמיר כהן</td>
        <td>amir@example.com</td>
        <td>
          <form method="POST" action="/assign-lead">
            <input type="hidden" name="lead_id" value="101">
            <select name="user_id" class="form-select" required onchange="this.form.submit()">
              <option value="">בחר משתמש...</option>
              <option value="1">אליס</option>
              <option value="2">בוב</option>
            </select>
          </form>
        </td>
      </tr>
      <tr draggable="true" data-lead-id="97" onclick="openLeadModal(this)">
        <td>97</td>
        <td>יעל ישראלי</td>
        <td>yael@example.com</td>
        <td>
          <form method="POST" action="/assign-lead">
            <input type="hidden" name="lead_id" value="97">
            <select name="user_id" class="form-select" required onchange="this.form.submit()">
              <option value="">בחר משתמש...</option>
              <option value="1">אליס</option>
              <option value="2">בוב</option>
            </select>
          </form>
        </td>
      </tr>
      <tr draggable="true" data-lead-id="98" onclick="openLeadModal(this)">
        <td>98</td>
        <td>רוני לוי</td>
        <td>roni@example.com</td>
        <td>
          <form method="POST" action="/assign-lead">
            <input type="hidden" name="lead_id" value="98">
            <select name="user_id" class="form-select" required onchange="this.form.submit()">
              <option value="">בחר משתמש...</option>
              <option value="1">אליס</option>
              <option value="2">בוב</option>
            </select>
          </form>
        </td>
      </tr>
      <tr draggable="true" data-lead-id="102" onclick="openLeadModal(this)">
        <td>102</td>
        <td>שחר דן</td>
        <td>shahar@example.com</td>
        <td>
          <form method="POST" action="/assign-lead">
            <input type="hidden" name="lead_id" value="102">
            <select name="user_id" class="form-select" required onchange="this.form.submit()">
              <option value="">בחר משתמש...</option>
              <option value="1">אליס</option>
              <option value="2">בוב</option>
            </select>
          </form>
        </td>
      </tr>
    </tbody>
  </table>

  <!-- Table 2: Assigned Leads -->
  <h3 class="mt-5">לידים מוקצים</h3>
  <table class="table table-striped" id="assignedLeads">
    <thead>
      <tr>
        <th>מספר ליד</th>
        <th>שם</th>
        <th>דוא"ל</th>
        <th>משתמש מטפל</th>
        <th>סטטוס</th>
      </tr>
    </thead>
    <tbody>
      <tr class="placeholder">
        <td colspan="5" style="text-align:center;color:#aaa;">גררו לידים לכאן</td>
      </tr>
      <tr draggable="true" data-lead-id="201" onclick="openLeadModal(this)">
        <td>201</td>
        <td>נועה בר</td>
        <td>noa@example.com</td>
        <td>אליס</td>
        <td><span class="badge bg-success" data-status="contacted">בוצע קשר</span></td>
      </tr>
    </tbody>
  </table>
</div>


<!-- Lead Modal -->
<div class="modal fade" id="leadModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">פרטי ליד</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="leadForm">
          <input type="hidden" id="leadId">

          <div class="mb-3">
            <label>שם</label>
            <input type="text" id="leadName" class="form-control">
          </div>

          <div class="mb-3">
            <label>דוא"ל</label>
            <input type="email" id="leadEmail" class="form-control">
          </div>

          <div class="mb-3">
            <label>טלפון</label>
            <input type="text" id="leadPhone" class="form-control">
          </div>

          <div class="mb-3">
            <label>סטטוס</label>
            <select id="leadStatus" class="form-select">
              <option value="new">חדש</option>
              <option value="contacted">בוצע קשר</option>
              <option value="in-progress">בתהליך</option>
              <option value="closed">נסגר</option>
            </select>
          </div>

          <div class="mb-3">
            <label>משתמש מטפל</label>
            <select id="leadUser" class="form-select">
              <option value="1">אליס</option>
              <option value="2">בוב</option>
            </select>
          </div>

          <div class="mb-3">
            <label>היסטוריה</label>
            <textarea id="leadHistory" class="form-control" rows="4" readonly>
- 2025-08-01: הליד נוצר
- 2025-08-02: בוצע קשר על ידי אליס
            </textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">סגירה</button>
        <button class="btn btn-primary" onclick="updateLead()">עדכון</button>
      </div>
    </div>
  </div>
</div>

<script>
const statusDisplayMap = {
  new: "חדש",
  contacted: "בוצע קשר",
  "in-progress": "בתהליך",
  closed: "נסגר"
};

const userDisplayMap = {
  "1": "אליס",
  "2": "בוב"
};

const userValueByLabel = Object.fromEntries(
  Object.entries(userDisplayMap).map(([value, label]) => [label, value])
);

function getStatusLabel(status) {
  if (!status) return "";
  const normalized = status.toLowerCase();
  return statusDisplayMap[normalized] || status;
}

function buildStatusBadge(status) {
  const normalized = (status || "").toLowerCase();
  let badgeClass = "bg-secondary";
  switch (normalized) {
    case "contacted":
      badgeClass = "bg-success";
      break;
    case "in-progress":
      badgeClass = "bg-primary";
      break;
    case "closed":
      badgeClass = "bg-dark";
      break;
    case "new":
      badgeClass = "bg-warning text-dark";
      break;
  }
  return `<span class="badge ${badgeClass}" data-status="${normalized || status}">${getStatusLabel(status)}</span>`;
}

function getUserLabel(value) {
  return userDisplayMap[value] || value;
}

// --- Function to add placeholder if table is empty ---
function addPlaceholderIfEmpty(tbody) {
  const dataRows = tbody.querySelectorAll('tr[data-lead-id]');
  if (dataRows.length === 0 && !tbody.querySelector('.placeholder')) {
    const tableId = tbody.closest('table').id;
    const colspan = (tableId === 'unassignedLeads') ? 4 : 5;
    const placeholder = document.createElement('tr');
    placeholder.classList.add('placeholder');
    placeholder.innerHTML = `<td colspan="${colspan}" style="text-align:center;color:#aaa;">גררו לידים לכאן</td>`;
    tbody.appendChild(placeholder);
  }
}

// --- Initialize draggable and clickable rows ---
function initDraggableRows() {
  document.querySelectorAll("tr[data-lead-id]").forEach(row => {
    row.setAttribute("draggable", "true");

    row.addEventListener("dragstart", e => {
      e.dataTransfer.setData("leadId", row.dataset.leadId);
    });

    row.onclick = () => openLeadModal(row);
  });
}

// --- Enable drop zones ---
["unassignedLeads", "assignedLeads"].forEach(tableId => {
  const tableBody = document.getElementById(tableId).querySelector("tbody");

  // Drag over
  tableBody.addEventListener("dragover", e => e.preventDefault());

  // Drop
  tableBody.addEventListener("drop", e => {
    e.preventDefault();
    const leadId = e.dataTransfer.getData("leadId");
    const row = document.querySelector(`tr[data-lead-id='${leadId}']`);
    const sourceTable = row.closest("table").id;
    const targetTable = tableId;

    if (sourceTable === targetTable) return; // Same table

    // Remove placeholder from target if present
    if (tableBody.querySelector(".placeholder")) {
      tableBody.querySelector(".placeholder").remove();
    }

    // Remove from old table
    row.remove();

    // Add placeholder to source if now empty
    const sourceTbody = document.querySelector(`#${sourceTable} tbody`);
    addPlaceholderIfEmpty(sourceTbody);

    // Extract data safely
    const statusElement = row.querySelector("[data-status]");
    const statusValue = statusElement ? statusElement.dataset.status : (row.cells[4]?.innerText || "new").toLowerCase();
    const userText = row.cells[3]?.innerText?.trim() || "";
    const userValue = row.cells[3]?.dataset.userId || userValueByLabel[userText] || "";

    const leadData = {
      id: row.dataset.leadId,
      name: row.cells[1]?.innerText || "",
      email: row.cells[2]?.innerText || "",
      userValue,
      statusValue
    };

    // Create new row
    const newRow = document.createElement("tr");
    newRow.dataset.leadId = leadData.id;

    if (targetTable === "unassignedLeads") {
      newRow.innerHTML = `
        <td>${leadData.id}</td>
        <td>${leadData.name}</td>
        <td>${leadData.email}</td>
        <td>
          <form method="POST" action="/assign-lead">
            <input type="hidden" name="lead_id" value="${leadData.id}">
            <select name="user_id" class="form-select" required onchange="this.form.submit()">
              <option value="">בחר משתמש...</option>
              <option value="1">אליס</option>
              <option value="2">בוב</option>
            </select>
          </form>
        </td>
      `;
    } else {
      // Assign default values if moving from unassigned
      const user = leadData.userValue || "1";
      const status = leadData.statusValue || "new";
      newRow.innerHTML = `
        <td>${leadData.id}</td>
        <td>${leadData.name}</td>
        <td>${leadData.email}</td>
        <td>${getUserLabel(user)}</td>
        <td>${buildStatusBadge(status)}</td>
      `;
    }

    tableBody.appendChild(newRow);

    // Reinitialize draggable
    initDraggableRows();

    console.log(`Lead ${leadData.id} moved from ${sourceTable} to ${targetTable}`);
    // TODO: AJAX update backend
  });
});

// --- Initialize everything on page load ---
initDraggableRows();

// --- Modal functions ---
function openLeadModal(row) {
  const leadId = row.dataset.leadId;
  document.getElementById("leadId").value = leadId;
  document.getElementById("leadName").value = row.cells[1].innerText;
  document.getElementById("leadEmail").value = row.cells[2].innerText;
  document.getElementById("leadPhone").value = "050-1234567"; // simulate
  const statusElement = row.querySelector("[data-status]");
  const statusValue = statusElement ? statusElement.dataset.status : "new";
  document.getElementById("leadStatus").value = statusValue;

  const userText = row.cells[3]?.innerText?.trim() || "";
  const userValue = row.cells[3]?.dataset.userId || userValueByLabel[userText] || "1";
  document.getElementById("leadUser").value = userValue;
  document.getElementById("leadHistory").value = "- 2025-08-01: הליד נוצר\n- 2025-08-02: בוצע קשר על ידי אליס";

  const modal = new bootstrap.Modal(document.getElementById("leadModal"));
  modal.show();
}

// --- Update lead from modal ---
function updateLead() {
  const id = document.getElementById("leadId").value;
  const name = document.getElementById("leadName").value;
  const email = document.getElementById("leadEmail").value;
  const status = document.getElementById("leadStatus").value;
  const user = document.getElementById("leadUser").value;

  const row = document.querySelector(`tr[data-lead-id='${id}']`);
  if (!row) return;

  row.cells[1].innerText = name;
  row.cells[2].innerText = email;

  if (row.closest("table").id === "assignedLeads") {
    row.cells[3].innerText = getUserLabel(user);
    row.cells[4].innerHTML = buildStatusBadge(status);
  }

  console.log(`Lead ${id} updated: ${name}, ${email}, ${status}, ${user}`);
  // TODO: AJAX save backend

  bootstrap.Modal.getInstance(document.getElementById("leadModal")).hide();
}

</script>

@endsection
