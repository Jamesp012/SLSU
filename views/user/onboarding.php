<?php
session_start();
// Check if user is logged in and is a student
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../../index.php");
    exit();
}

// If already completed, redirect to dashboard
if (isset($_SESSION['onboarding_completed']) && $_SESSION['onboarding_completed']) {
    header("Location: dashboard.php");
    exit();
}

require_once '../includes/header.php';
?>

<div class="row justify-content-center pt-5">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0">Complete Your Student Profile</h4>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">Welcome! Before you can explore the website, please provide the following details to complete your registration.</p>
                
                <form id="onboardingForm">
                    <input type="hidden" name="action" value="complete_onboarding">
                    
                    <div class="mb-4">
                        <label for="lrn" class="form-label">LRN (Learner Reference Number)</label>
                        <input type="text" class="form-control form-control-lg" id="lrn" name="lrn" required 
                               placeholder="12-digit number" pattern="\d{12}" title="LRN must be exactly 12 digits" autocomplete="off">
                        <div class="form-text">Your unique 12-digit number provided by DepEd.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="recent_school" class="form-label">Recent School Attended</label>
                        <input type="text" class="form-control form-control-lg" id="recent_school" name="recent_school" required 
                               placeholder="Enter the name of your previous school" autocomplete="organization">
                    </div>
                    
                    <div class="mb-4">
                        <label for="preferred_track" class="form-label">Preferred Academic Track</label>
                        <select class="form-select form-select-lg" id="preferred_track" name="preferred_track" required>
                            <option value="">Choose your track...</option>
                            <option value="STEM">STEM (Science, Technology, Engineering, and Mathematics)</option>
                            <option value="ABM">ABM (Accountancy, Business, and Management)</option>
                            <option value="HUMSS">HUMSS (Humanities and Social Sciences)</option>
                            <option value="GAS">GAS (General Academic Strand)</option>
                            <option value="TVL">TVL (Technical-Vocational-Livelihood)</option>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Submit and Proceed</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $('#onboardingForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        Swal.fire({
            title: 'Submitting Details',
            text: "Please wait...",
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
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'dashboard.php';
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

<?php require_once '../includes/footer.php'; ?>
