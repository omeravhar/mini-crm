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
<div class="row">
  <div class="col-12">
    <!-- Button trigger modal -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
      + הוספת משתמש חדש
    </button>
  </div>
</div>
 <table class="table table-striped table-hover mt-4">
  <thead class="table-dark">
    <tr>
      <th scope="col">#</th>
      <th scope="col">שם</th>
      <th scope="col">דוא"ל</th>
      <th scope="col">תפקיד</th>
      <th scope="col">פעולות</th>
    </tr>
  </thead>
  <tbody>
    <tr data-user-id="1">
      <th scope="row">1</th>
      <td>יוסי כהן</td>
      <td>yossi@example.com</td>
      <td>מנהל מערכת</td>
      <td>
        <button class="btn btn-sm btn-warning me-2" onclick="openUpdateModal(1, 'יוסי כהן', 'yossi@example.com', 'Admin')">עדכון</button>
        <button class="btn btn-sm btn-danger" onclick="openDeleteModal(1, 'יוסי כהן')">מחיקה</button>
      </td>
    </tr>
    <tr data-user-id="2">
      <th scope="row">2</th>
      <td>נועה לוי</td>
      <td>noa@example.com</td>
      <td>עורכת</td>
      <td>
        <button class="btn btn-sm btn-warning me-2" onclick="openUpdateModal(2, 'נועה לוי', 'noa@example.com', 'Editor')">עדכון</button>
        <button class="btn btn-sm btn-danger" onclick="openDeleteModal(2, 'נועה לוי')">מחיקה</button>
      </td>
    </tr>
  </tbody>
</table>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">מחיקת משתמש</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="deleteUserMessage">האם אתה בטוח שברצונך למחוק משתמש זה?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">מחיקה</button>
      </div>
    </div>
  </div>
</div>

<!-- Update User Modal -->
<div class="modal fade" id="updateUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="updateUserForm" class="needs-validation" novalidate>
        <div class="modal-header bg-warning">
          <h5 class="modal-title">עדכון משתמש</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="updateUserId" name="id">

          <div class="mb-3">
            <label for="updateName" class="form-label">שם מלא</label>
            <input type="text" class="form-control" id="updateName" name="name" required>
            <div class="invalid-feedback">נא להזין שם.</div>
          </div>

          <div class="mb-3">
            <label for="updateEmail" class="form-label">דוא"ל</label>
            <input type="email" class="form-control" id="updateEmail" name="email" required>
            <div class="invalid-feedback">נא להזין דוא"ל תקין.</div>
          </div>

          <div class="mb-3">
            <label for="updateRole" class="form-label">תפקיד</label>
            <select class="form-select" id="updateRole" name="role" required>
              <option value="">בחר...</option>
              <option value="Admin">מנהל מערכת</option>
              <option value="Editor">עורך/ת</option>
              <option value="Viewer">צופה</option>
            </select>
            <div class="invalid-feedback">Please select a role.</div>
          </div>
            <div class="mb-3">
                  <label for="userPassword" class="form-label">Password</label>
                  <input type="password" class="form-control" id="userPassword" name="password" >
                </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>



<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <form id="addUserForm">
          <div class="mb-3">
            <label for="userName" class="form-label">שם</label>
            <input type="text" class="form-control" id="userName" name="name" required>
          </div>
          
          <div class="mb-3">
            <label for="userEmail" class="form-label">דוא"ל</label>
            <input type="email" class="form-control" id="userEmail" name="email" required>
          </div>
          
          <div class="mb-3">
            <label for="userPassword" class="form-label">סיסמה</label>
            <input type="password" class="form-control" id="userPassword" name="password" required>
          </div>
          
          <div class="mb-3">
            <label for="userRole" class="form-label">תפקיד</label>
            <select class="form-select" id="userRole" name="role" required>
              <option value="">בחר תפקיד</option>
              <option value="Admin">מנהל מערכת</option>
              <option value="Editor">עורך/ת</option>
              <option value="Viewer">צופה</option>
            </select>
          </div>
        </form>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
        <button type="submit" class="btn btn-primary" form="addUserForm">שמירת משתמש</button>
      </div>
      
    </div>
  </div>
</div>
<script>
  let deleteUserId = null;

  function openDeleteModal(userId, userName) {
    deleteUserId = userId;
    document.getElementById('deleteUserMessage').innerText =
      `האם אתה בטוח שברצונך למחוק את המשתמש "${userName}"?`;

    const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    deleteModal.show();

    document.getElementById('confirmDeleteBtn').onclick = function () {
      deleteUser(deleteUserId);
      deleteModal.hide();
    };
  }

  function deleteUser(userId) {
    console.log("Deleting user with ID:", userId);
    // TODO: AJAX call to backend
  }

  function openUpdateModal(userId, name, email, role) {
    document.getElementById('updateUserId').value = userId;
    document.getElementById('updateName').value = name;
    document.getElementById('updateEmail').value = email;
    document.getElementById('updateRole').value = role;

    const updateModal = new bootstrap.Modal(document.getElementById('updateUserModal'));
    updateModal.show();
  }

  document.getElementById('updateUserForm').addEventListener('submit', function (event) {
    event.preventDefault();
    event.stopPropagation();

    if (!this.checkValidity()) {
      this.classList.add('was-validated');
      return;
    }

    const userId = document.getElementById('updateUserId').value;
    const name = document.getElementById('updateName').value;
    const email = document.getElementById('updateEmail').value;
    const role = document.getElementById('updateRole').value;

    console.log("Updating user:", { userId, name, email, role });

    // TODO: AJAX call to backend
    // fetch(`/users/${userId}`, { method: 'PUT', body: JSON.stringify({ name, email, role }) })

    bootstrap.Modal.getInstance(document.getElementById('updateUserModal')).hide();



  });


 document.getElementById("addUserForm").addEventListener("submit", function(event) {
    event.preventDefault();
    
    const newUser = {
      name: document.getElementById("userName").value,
      email: document.getElementById("userEmail").value,
      password: document.getElementById("userPassword").value,
      role: document.getElementById("userRole").value,
    };
    
    console.log("Creating new user:", newUser);
    // Example: send to backend
    // fetch('/users', { method: 'POST', body: JSON.stringify(newUser), headers: {'Content-Type': 'application/json'} })
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
    modal.hide();
    
    // Reset form
    event.target.reset();
  });


</script>
@endsection
