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



<div class="container mt-4">

  <!-- Table 1: New Leads -->
  <h3>New Leads (Unassigned)</h3>
  <table class="table table-bordered" id="unassignedLeads">
    <thead>
      <tr>
        <th>Lead ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Assign</th>
      </tr>
    </thead>
    <tbody>
      <tr draggable="true" data-lead-id="101" onclick="openLeadModal(this)">
        <td>101</td>
        <td>John Doe</td>
        <td>john@example.com</td>
        <td>
          <form method="POST" action="/assign-lead">
            <input type="hidden" name="lead_id" value="101">
            <select name="user_id" class="form-select" required onchange="this.form.submit()">
              <option value="">Select user...</option>
              <option value="1">Alice</option>
              <option value="2">Bob</option>
            </select>
          </form>
        </td>
      </tr>
      <tr draggable="true" data-lead-id="97" onclick="openLeadModal(this)">
        <td>97</td>
        <td>John Doe</td>
        <td>john@example.com</td>
        <td>
          <form method="POST" action="/assign-lead">
            <input type="hidden" name="lead_id" value="97">
            <select name="user_id" class="form-select" required onchange="this.form.submit()">
              <option value="">Select user...</option>
              <option value="1">Alice</option>
              <option value="2">Bob</option>
            </select>
          </form>
        </td>
      </tr>
      <tr draggable="true" data-lead-id="98" onclick="openLeadModal(this)">
        <td>98</td>
        <td>John Doe</td>
        <td>john@example.com</td>
        <td>
          <form method="POST" action="/assign-lead">
            <input type="hidden" name="lead_id" value="98">
            <select name="user_id" class="form-select" required onchange="this.form.submit()">
              <option value="">Select user...</option>
              <option value="1">Alice</option>
              <option value="2">Bob</option>
            </select>
          </form>
        </td>
      </tr>
      <tr draggable="true" data-lead-id="102" onclick="openLeadModal(this)">
        <td>102</td>
        <td>John Doe</td>
        <td>john@example.com</td>
        <td>
          <form method="POST" action="/assign-lead">
            <input type="hidden" name="lead_id" value="102">
            <select name="user_id" class="form-select" required onchange="this.form.submit()">
              <option value="">Select user...</option>
              <option value="1">Alice</option>
              <option value="2">Bob</option>
            </select>
          </form>
        </td>
      </tr>
    </tbody>
  </table>

  <!-- Table 2: Assigned Leads -->
  <h3 class="mt-5">Assigned Leads</h3>
  <table class="table table-striped" id="assignedLeads">
    <thead>
      <tr>
        <th>Lead ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>User</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <tr class="placeholder">
        <td colspan="5" style="text-align:center;color:#aaa;">Drag leads here</td>
      </tr>
      <tr draggable="true" data-lead-id="201" onclick="openLeadModal(this)">
        <td>201</td>
        <td>Jane Smith</td>
        <td>jane@example.com</td>
        <td>Alice</td>
        <td><span class="badge bg-success">Contacted</span></td>
      </tr>
    </tbody>
  </table>
</div>


<!-- Lead Modal -->
<div class="modal fade" id="leadModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Lead Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="leadForm">
          <input type="hidden" id="leadId">

          <div class="mb-3">
            <label>Name</label>
            <input type="text" id="leadName" class="form-control">
          </div>

          <div class="mb-3">
            <label>Email</label>
            <input type="email" id="leadEmail" class="form-control">
          </div>

          <div class="mb-3">
            <label>Phone</label>
            <input type="text" id="leadPhone" class="form-control">
          </div>

          <div class="mb-3">
            <label>Status</label>
            <select id="leadStatus" class="form-select">
              <option value="new">New</option>
              <option value="contacted">Contacted</option>
              <option value="in-progress">In Progress</option>
              <option value="closed">Closed</option>
            </select>
          </div>

          <div class="mb-3">
            <label>Assigned User</label>
            <select id="leadUser" class="form-select">
              <option value="1">Alice</option>
              <option value="2">Bob</option>
            </select>
          </div>

          <div class="mb-3">
            <label>History</label>
            <textarea id="leadHistory" class="form-control" rows="4" readonly>
- 2025-08-01: Created
- 2025-08-02: Contacted by Alice
            </textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" onclick="updateLead()">Update</button>
      </div>
    </div>
  </div>
</div>

<script>
// --- Function to add placeholder if table is empty ---
function addPlaceholderIfEmpty(tbody) {
  const dataRows = tbody.querySelectorAll('tr[data-lead-id]');
  if (dataRows.length === 0 && !tbody.querySelector('.placeholder')) {
    const tableId = tbody.closest('table').id;
    const colspan = (tableId === 'unassignedLeads') ? 4 : 5;
    const placeholder = document.createElement('tr');
    placeholder.classList.add('placeholder');
    placeholder.innerHTML = `<td colspan="${colspan}" style="text-align:center;color:#aaa;">Drag leads here</td>`;
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
    const leadData = {
      id: row.dataset.leadId,
      name: row.cells[1]?.innerText || "",
      email: row.cells[2]?.innerText || "",
      user: row.cells[3]?.innerText || "",
      status: row.cells[4]?.innerText || "New"
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
              <option value="">Select user...</option>
              <option value="1">Alice</option>
              <option value="2">Bob</option>
            </select>
          </form>
        </td>
      `;
    } else {
      // Assign default values if moving from unassigned
      const user = leadData.user || "Alice";
      const status = leadData.status || "New";
      newRow.innerHTML = `
        <td>${leadData.id}</td>
        <td>${leadData.name}</td>
        <td>${leadData.email}</td>
        <td>${user}</td>
        <td><span class="badge bg-success">${status}</span></td>
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
  document.getElementById("leadStatus").value = row.cells[4]?.innerText.toLowerCase() || "new";
  document.getElementById("leadUser").value = row.cells[3]?.innerText || "1";
  document.getElementById("leadHistory").value = "- 2025-08-01: Created\n- 2025-08-02: Contacted by Alice";

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
    row.cells[3].innerText = user;
    row.cells[4].innerHTML = `<span class="badge bg-success">${status}</span>`;
  }

  console.log(`Lead ${id} updated: ${name}, ${email}, ${status}, ${user}`);
  // TODO: AJAX save backend

  bootstrap.Modal.getInstance(document.getElementById("leadModal")).hide();
}

</script>

@endsection
