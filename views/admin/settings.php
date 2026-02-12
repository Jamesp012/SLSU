<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Admin Settings</li>
            </ol>
        </nav>
        <h2 class="fw-bold text-dark mb-0">Admin Settings</h2>
    </div>
</div>

<div class="row g-4">
    <!-- Change Password Section -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-key me-2"></i>Change My Password</h6>
            </div>
            <div class="card-body p-4">
                <form id="changePasswordForm">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label small fw-bold text-muted">CURRENT PASSWORD</label>
                        <input type="password" class="form-control bg-light border-0" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label small fw-bold text-muted">NEW PASSWORD</label>
                        <input type="password" class="form-control bg-light border-0" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label small fw-bold text-muted">CONFIRM NEW PASSWORD</label>
                        <input type="password" class="form-control bg-light border-0" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary shadow-sm">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add New Admin Section -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-success"><i class="fas fa-user-shield me-2"></i>Register New Admin</h6>
            </div>
            <div class="card-body p-4">
                <form id="addAdminForm">
                    <input type="hidden" name="action" value="add_admin">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label small fw-bold text-muted">FIRST NAME</label>
                            <input type="text" class="form-control bg-light border-0" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label small fw-bold text-muted">LAST NAME</label>
                            <input type="text" class="form-control bg-light border-0" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-bold text-muted">EMAIL ADDRESS</label>
                        <input type="email" class="form-control bg-light border-0" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="admin_password" class="form-label small fw-bold text-muted">PASSWORD</label>
                        <input type="password" class="form-control bg-light border-0" id="admin_password" name="password" required minlength="6">
                        <div class="form-text">Choose a secure password for the new administrator.</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success shadow-sm">Create Admin Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Change Password Handler
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        if ($('#new_password').val() !== $('#confirm_password').val()) {
            Swal.fire('Error!', 'New passwords do not match.', 'error');
            return;
        }

        const formData = $(this).serialize();
        submitForm('../../controllers/admin_contr.php', formData, '#changePasswordForm');
    });

    // Add Admin Handler
    $('#addAdminForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        submitForm('../../controllers/admin_contr.php', formData, '#addAdminForm');
    });

    function submitForm(url, data, formId) {
        Swal.fire({
            title: 'Processing',
            text: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('Success!', response.message, 'success');
                    $(formId)[0].reset();
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Could not connect to the server.', 'error');
            }
        });
    }
});
</script>
