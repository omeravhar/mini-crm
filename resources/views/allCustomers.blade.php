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

   <!-- Customers Table -->
    <div class="card">
        <div class="card-body">
            <table class="table table-hover" id="customersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>John</td>
                        <td>Doe</td>
                        <td>john@example.com</td>
                        <td>+972-50-123-4567</td>
                        <td>
                            <button class="btn btn-sm btn-primary viewCustomerBtn" 
                                data-bs-toggle="modal" 
                                data-bs-target="#customerModal"
                                data-customer='{"id":1,"first_name":"John","last_name":"Doe","email":"john@example.com","phone":"+972-50-123-4567","company":"Example Ltd","job_title":"Manager","website":"https://example.com"}'>
                                View / Edit
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Jane</td>
                        <td>Smith</td>
                        <td>jane@example.com</td>
                        <td>+972-52-987-6543</td>
                        <td>
                            <button class="btn btn-sm btn-primary viewCustomerBtn" 
                                data-bs-toggle="modal" 
                                data-bs-target="#customerModal"
                                data-customer='{"id":2,"first_name":"Jane","last_name":"Smith","email":"jane@example.com","phone":"+972-52-987-6543","company":"Acme Corp","job_title":"CEO","website":"https://acme.com"}'>
                                View / Edit
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>David</td>
                        <td>Lee</td>
                        <td>david@example.com</td>
                        <td>+972-54-555-1234</td>
                        <td>
                            <button class="btn btn-sm btn-primary viewCustomerBtn" 
                                data-bs-toggle="modal" 
                                data-bs-target="#customerModal"
                                data-customer='{"id":3,"first_name":"David","last_name":"Lee","email":"david@example.com","phone":"+972-54-555-1234","company":"Tech Solutions","job_title":"Developer","website":"https://techsolutions.com"}'>
                                View / Edit
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Customer Modal (same as before) -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form id="customerForm" method="POST" class="needs-validation" novalidate>
        <input type="hidden" name="id" id="customerId">
        <div class="modal-header">
          <h5 class="modal-title" id="customerModalLabel">Customer Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="firstNameModal" class="form-label required">First Name</label>
              <input type="text" class="form-control" id="firstNameModal" name="first_name" required>
            </div>
            <div class="col-md-6">
              <label for="lastNameModal" class="form-label required">Last Name</label>
              <input type="text" class="form-control" id="lastNameModal" name="last_name" required>
            </div>
            <div class="col-md-8">
              <label for="emailModal" class="form-label required">Email</label>
              <input type="email" class="form-control" id="emailModal" name="email" required>
            </div>
            <div class="col-md-4">
              <label for="phoneModal" class="form-label">Phone</label>
              <input type="tel" class="form-control" id="phoneModal" name="phone">
            </div>
            <div class="col-md-12">
              <label for="companyModal" class="form-label">Company</label>
              <input type="text" class="form-control" id="companyModal" name="company">
            </div>
            <div class="col-md-6">
              <label for="titleModal" class="form-label">Job Title</label>
              <input type="text" class="form-control" id="titleModal" name="job_title">
            </div>
            <div class="col-md-6">
              <label for="websiteModal" class="form-label">Website</label>
              <input type="url" class="form-control" id="websiteModal" name="website">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Update Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const customerModal = document.getElementById('customerModal');

    customerModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const customer = JSON.parse(button.getAttribute('data-customer'));

        document.getElementById('customerId').value = customer.id;
        document.getElementById('firstNameModal').value = customer.first_name || '';
        document.getElementById('lastNameModal').value = customer.last_name || '';
        document.getElementById('emailModal').value = customer.email || '';
        document.getElementById('phoneModal').value = customer.phone || '';
        document.getElementById('companyModal').value = customer.company || '';
        document.getElementById('titleModal').value = customer.job_title || '';
        document.getElementById('websiteModal').value = customer.website || '';
    });
});
</script>
@endsection
