<?php
session_start();
// Check if user is logged in and is a student
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../../index.php");
    exit();
}

// Redirect to onboarding if not completed
if (!isset($_SESSION['onboarding_completed']) || !$_SESSION['onboarding_completed']) {
    header("Location: onboarding.php");
    exit();
}

require_once '../includes/header.php';
require_once __DIR__ . '/../../models/StudentModel.php';

$studentModel = new StudentModel();
$student_data = $studentModel->getStudentByEmail($_SESSION['email']);

if (!$student_data) {
    // Fallback if profile not found
    $student_data = [
        'first_name' => $_SESSION['first_name'] ?? 'Student',
        'last_name' => $_SESSION['last_name'] ?? '',
        'email' => $_SESSION['email'],
        'lrn' => 'N/A',
        'recent_school' => 'N/A',
        'preferred_track' => 'N/A',
        'exam_status' => 'Pending'
    ];
} else {
    $student_data['exam_status'] = $student_data['exam_status'] ?? 'Pending Assignment';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Student Dashboard</h1>
</div>

<div class="row">
    <!-- Profile Info -->
    <div class="col-md-4 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 bg-primary text-white">
                <h6 class="m-0 font-weight-bold">My Profile</h6>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-5x text-secondary"></i>
                </div>
                <h5><?php echo $student_data['first_name'] . ' ' . $student_data['last_name']; ?></h5>
                <p class="text-muted"><?php echo $student_data['email']; ?></p>
                <div class="mb-3">
                    <a href="profile.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit me-1"></i> Edit Profile
                    </a>
                </div>
                <hr>
                <div class="text-start">
                    <p><strong>LRN:</strong> <?php echo $student_data['lrn']; ?></p>
                    <p><strong>Track:</strong> <span class="badge bg-info"><?php echo $student_data['preferred_track']; ?></span></p>
                    <p><strong>School:</strong> <?php echo $student_data['recent_school']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Online Examination -->
    <div class="col-md-8 mb-4">
        <div class="card shadow mb-4 border-left-primary">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 font-weight-bold text-primary">Online Examination</h6>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <div class="mb-4">
                        <i class="fas fa-laptop-code fa-4x text-primary opacity-25"></i>
                    </div>
                    <h4>Cognitive Aptitude Test</h4>
                    <p class="text-muted mb-4">You can now proceed to take your online entrance examination. Please ensure you have a stable internet connection before starting.</p>
                    <a href="take_exam.php" class="btn btn-primary btn-lg px-5 shadow-sm">
                        <i class="fas fa-play-circle me-2"></i> Start Exam Now
                    </a>
                </div>
                <div class="mt-4 pt-3 border-top">
                    <div class="row text-center">
                        <div class="col-md-6 border-end">
                            <h6 class="text-muted small text-uppercase">Duration</h6>
                            <p class="fw-bold mb-0">60 Minutes</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small text-uppercase">Status</h6>
                            <p class="fw-bold mb-0 text-success">Available</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
