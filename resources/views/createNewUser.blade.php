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

   <<form id="userForm" class="needs-validation" novalidate>
  <div class="mb-3">
    <label for="name" class="form-label">Full Name</label>
    <input type="text" class="form-control" id="name" name="name" required>
    <div class="invalid-feedback">
      Please enter a full name.
    </div>
  </div>

  <div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input type="email" class="form-control" id="email" name="email" required>
    <div class="invalid-feedback">
      Please enter a valid email.
    </div>
  </div>

  <div class="mb-3">
    <label for="password" class="form-label">Password</label>
    <input type="password" class="form-control" id="password" name="password" minlength="6" required>
    <div class="invalid-feedback">
      Password must be at least 6 characters.
    </div>
  </div>

  <div class="mb-3">
    <label for="role" class="form-label">Role</label>
    <select class="form-select" id="role" name="role" required>
      <option value="">Choose...</option>
      <option value="admin">Admin</option>
      <option value="editor">Editor</option>
      <option value="viewer">Viewer</option>
    </select>
    <div class="invalid-feedback">
      Please select a role.
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Create User</button>
</form>

<script>
  (function () {
    'use strict';
    const form = document.getElementById('userForm');
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  })();
</script>

@endsection
