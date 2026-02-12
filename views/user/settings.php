<?php
session_start();
// Check if user is logged in and is a student
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
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
                <li class="breadcrumb-item active" aria-current="page">Account Settings</li>
            </ol>
        </nav>
        <h2 class="fw-bold text-dark mb-0">Account Settings</h2>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="fas fa-key me-2"></i>Change Password</h6>
            </div>
            <div class="card-body p-4">
                <form id="changePasswordForm">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label small fw-bold text-muted">CURRENT PASSWORD</label>
                        <input type="password" class="form-control form-control-lg bg-light border-0" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label small fw-bold text-muted">NEW PASSWORD</label>
                        <input type="password" class="form-control form-control-lg bg-light border-0" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label small fw-bold text-muted">CONFIRM NEW PASSWORD</label>
                        <input type="password" class="form-control form-control-lg bg-light border-0" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        const newPass = $('#new_password').val();
        const confirmPass = $('#confirm_password').val();
        
        if (newPass !== confirmPass) {
            Swal.fire('Error!', 'New passwords do not match.', 'error');
            return;
        }

        const formData = $(this).serialize();

        Swal.fire({
            title: 'Updating Password',
            text: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '../../controllers/user_contr.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#changePasswordForm')[0].reset();
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Could not connect to the server.', 'error');
            }
        });
    });
});
</script>
