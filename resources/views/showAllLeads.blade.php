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





 <div class="container mt-4">

  <!-- Table 1: Unassigned Leads -->
  <h3>New Leads (Unassigned)</h3>
  <table class="table table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th scope="col">#</th>
        <th scope="col">Name</th>
        <th scope="col">Email</th>
        <th scope="col">Phone</th>
        <th scope="col">Source</th>
        <th scope="col">Assign to User</th>
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
              <option value="">Select user...</option>
              <option value="101">Alice</option>
              <option value="102">Bob</option>
              <option value="103">Charlie</option>
            </select>
          </form>
        </td>
      </tr>
    </tbody>
  </table>

  <!-- Table 2: Assigned Leads -->
  <h3 class="mt-5">Assigned Leads</h3>
  <table class="table table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th>Lead ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Assigned User</th>
        <th>Status</th>
        <th>Reassign</th>
      </tr>
    </thead>
    <tbody>
      <tr class="lead-row" data-lead-id="201">
        <td>201</td>
        <td>Jane Smith</td>
        <td>jane@example.com</td>
        <td>Alice</td>
        <td><span class="badge bg-warning text-dark">Pending</span></td>
        <td>
          <form onsubmit="reassignLead(event, 201)">
            <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="1" selected>Alice</option>
              <option value="2">Bob</option>
            </select>
          </form>
        </td>
      </tr>

      <tr class="lead-row" data-lead-id="202">
        <td>202</td>
        <td>Michael Brown</td>
        <td>mike@example.com</td>
        <td>Bob</td>
        <td><span class="badge bg-success">Closed</span></td>
        <td>
          <form onsubmit="reassignLead(event, 202)">
            <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="1">Alice</option>
              <option value="2" selected>Bob</option>
            </select>
          </form>
        </td>
      </tr>

      <tr class="lead-row" data-lead-id="203">
        <td>203</td>
        <td>David Green</td>
        <td>david@example.com</td>
        <td>Charlie</td>
        <td><span class="badge bg-danger">Rejected</span></td>
        <td>
          <form onsubmit="reassignLead(event, 203)">
            <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="3" selected>Charlie</option>
              <option value="1">Alice</option>
              <option value="2">Bob</option>
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
        <h5 class="modal-title">Lead Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <h6>Lead Information</h6>
        <div id="leadDetails"></div>
        <hr>
        <h6>History</h6>
        <ul class="list-group" id="leadHistory"></ul>
        <hr>
        <h6>Actions</h6>
        <form id="updateLeadForm" onsubmit="updateLead(event)">
          <input type="hidden" name="lead_id" id="leadIdInput">
          <div class="mb-3">
            <label class="form-label">Change Status</label>
            <select class="form-select" name="status" id="leadStatusSelect">
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Closed">Closed</option>
              <option value="Rejected">Rejected</option>
              <option value="On Hold">On Hold</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Update Lead</button>
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
      name: "Example Lead",
      email: "lead@example.com",
      phone: "+972-50-0000000",
      status: "Pending",
      user_name: "Alice",
      history: [
        { date: "2025-08-01", action: "Created lead" },
        { date: "2025-08-05", action: "Assigned to Alice" }
      ]
    };

    // Fill modal
    document.getElementById("leadIdInput").value = data.id;
    document.getElementById("leadStatusSelect").value = data.status;
    document.getElementById("leadDetails").innerHTML = `
      <p><strong>ID:</strong> ${data.id}</p>
      <p><strong>Name:</strong> ${data.name}</p>
      <p><strong>Email:</strong> ${data.email}</p>
      <p><strong>Phone:</strong> ${data.phone}</p>
      <p><strong>Status:</strong> ${renderStatusBadge(data.status)}</p>
      <p><strong>Assigned To:</strong> ${data.user_name}</p>
    `;

    let historyHtml = "";
    if (data.history.length > 0) {
      data.history.forEach(h => {
        historyHtml += `<li class="list-group-item"><strong>${h.date}:</strong> ${h.action}</li>`;
      });
    } else {
      historyHtml = `<li class="list-group-item">No history available.</li>`;
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
      case "pending": badgeClass = "bg-warning text-dark"; break;
      case "in progress": badgeClass = "bg-primary"; break;
      case "closed": badgeClass = "bg-success"; break;
      case "rejected": badgeClass = "bg-danger"; break;
      case "on hold": badgeClass = "bg-secondary"; break;
    }
    return `<span class="badge ${badgeClass}">${label}</span>`;
  }
</script>


@endsection
