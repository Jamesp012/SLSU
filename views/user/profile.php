<?php
session_start();
// Check if user is logged in and is a student
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../../index.php");
    exit();
}

require_once '../includes/header.php';
require_once __DIR__ . '/../../models/StudentModel.php';
require_once __DIR__ . '/../../models/AchievementScoreModel.php';

$studentModel = new StudentModel();
$scoreModel = new AchievementScoreModel();
$student_data = $studentModel->getStudentByEmail($_SESSION['email']);

$achievement_score = null;
if ($student_data) {
    $achievement_score = $scoreModel->getScoreByStudentId($student_data['id']);
}

if (!$student_data) {
    echo "<div class='alert alert-danger'>Profile not found.</div>";
    require_once '../includes/footer.php';
    exit();
}
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Profile</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-bold text-dark mb-0">Profile Overview</h2>
            <button type="button" class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                <i class="fas fa-user-edit me-2"></i> Edit Details
            </button>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Profile Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center p-4">
            <div class="card-body">
                <div class="position-relative d-inline-block mb-3">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center shadow-sm" style="width: 120px; height: 120px; border: 4px solid #fff;">
                        <i class="fas fa-user fa-4x text-primary opacity-50"></i>
                    </div>
                    <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle p-2" title="Status: Active"></span>
                </div>
                <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']); ?></h4>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($student_data['email']); ?></p>
                <div class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">Student Account</div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <hr class="opacity-10">
                <div class="row text-center">
                    <div class="col-12 small text-muted">
                        <i class="fas fa-clock me-1"></i> Member since <?php echo date('M Y', strtotime($student_data['created_at'] ?? 'now')); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Card -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary">Academic Information</h6>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-sm-4 text-muted">Full Name</div>
                    <div class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($student_data['first_name'] . ' ' . ($student_data['middle_name'] ? $student_data['middle_name'] . ' ' : '') . $student_data['last_name']); ?></div>
                </div>
                <div class="row mb-4">
                    <div class="col-sm-4 text-muted">LRN (Learner Reference No.)</div>
                    <div class="col-sm-8 fw-semibold font-monospace"><?php echo htmlspecialchars($student_data['lrn']); ?></div>
                </div>
                <div class="row mb-4">
                    <div class="col-sm-4 text-muted">Recent School</div>
                    <div class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($student_data['recent_school']); ?></div>
                </div>
                <div class="row mb-0">
                    <div class="col-sm-4 text-muted">Academic Track</div>
                    <div class="col-sm-8 fw-semibold">
                        <span class="badge bg-info-subtle text-info px-3 py-2"><?php echo htmlspecialchars($student_data['preferred_track']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm mt-4 bg-primary text-white overflow-hidden position-relative">
            <div class="card-body p-4 position-relative z-1">
                <h5 class="fw-bold mb-2">Ready for the Exam?</h5>
                <p class="mb-0 opacity-75">You can update your academic details anytime before starting the examination. Make sure your LRN is accurate.</p>
            </div>
            <i class="fas fa-graduation-cap position-absolute end-0 bottom-0 fa-6x opacity-10 mb-n3 me-n2"></i>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="editProfileModalLabel">Edit Academic Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editProfileForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-3">
                        <label for="lrn" class="form-label small fw-bold text-muted">LRN (12 DIGITS)</label>
                        <input type="text" class="form-control form-control-lg bg-light border-0" id="lrn" name="lrn" 
                               value="<?php echo htmlspecialchars($student_data['lrn']); ?>" 
                               required pattern="\d{12}" maxlength="12">
                    </div>
                    
                    <div class="mb-3">
                        <label for="recent_school" class="form-label small fw-bold text-muted">RECENT SCHOOL ATTENDED</label>
                        <input type="text" class="form-control form-control-lg bg-light border-0" id="recent_school" name="recent_school" 
                               value="<?php echo htmlspecialchars($student_data['recent_school']); ?>" required>
                    </div>
                    
                    <div class="mb-0">
                        <label for="preferred_track" class="form-label small fw-bold text-muted">PREFERRED ACADEMIC TRACK</label>
                        <select class="form-select form-select-lg bg-light border-0" id="preferred_track" name="preferred_track" required <?php echo ($achievement_score && !$achievement_score['is_passed']) ? 'disabled' : ''; ?>>
                            <?php if ($achievement_score && !$achievement_score['is_passed']): ?>
                                <option value="TVL" selected>TVL (Technical-Vocational-Livelihood)</option>
                            <?php else: ?>
                                <option value="STEM" <?php echo $student_data['preferred_track'] == 'STEM' ? 'selected' : ''; ?>>STEM</option>
                                <option value="ABM" <?php echo $student_data['preferred_track'] == 'ABM' ? 'selected' : ''; ?>>ABM</option>
                                <option value="HUMSS" <?php echo $student_data['preferred_track'] == 'HUMSS' ? 'selected' : ''; ?>>HUMSS</option>
                                <option value="GAS" <?php echo $student_data['preferred_track'] == 'GAS' ? 'selected' : ''; ?>>GAS</option>
                                <option value="TVL" <?php echo $student_data['preferred_track'] == 'TVL' ? 'selected' : ''; ?>>TVL</option>
                            <?php endif; ?>
                        </select>
                        <?php if ($achievement_score && !$achievement_score['is_passed']): ?>
                            <input type="hidden" name="preferred_track" value="TVL">
                            <div class="form-text text-danger mt-2">
                                <i class="fas fa-info-circle me-1"></i> Track selection is restricted to TVL due to Scholastic Ability Test results.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 p-4">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<script>
$(document).ready(function() {
    $('#editProfileForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        Swal.fire({
            title: 'Saving Changes',
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
                        text: 'Profile updated successfully!',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
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
