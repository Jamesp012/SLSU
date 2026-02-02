<?php
session_start();
// Check if user is logged in and is a student
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../../index.php");
    exit();
}

require_once '../includes/header.php';

// Simulate fetching student data based on session
$student_data = [
    'first_name' => $_SESSION['first_name'] ?? 'John',
    'last_name' => $_SESSION['last_name'] ?? 'Doe',
    'middle_name' => 'Michael',
    'email' => $_SESSION['email'] ?? 'student@example.com',
    'lrn' => '123456789012',
    'recent_school' => 'Sample High School',
    'preferred_track' => 'STEM',
    'exam_status' => 'Pending Assignment'
];
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
                <hr>
                <div class="text-start">
                    <p><strong>LRN:</strong> <?php echo $student_data['lrn']; ?></p>
                    <p><strong>Track:</strong> <span class="badge bg-info"><?php echo $student_data['preferred_track']; ?></span></p>
                    <p><strong>School:</strong> <?php echo $student_data['recent_school']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Status -->
    <div class="col-md-8 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Exam Information</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Your examination schedule is currently being processed. Please check back later.
                </div>
                <div class="row text-center mt-4">
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <h6 class="text-muted">Status</h6>
                            <p class="h5"><?php echo $student_data['exam_status']; ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <h6 class="text-muted">Exam Date</h6>
                            <p class="h5">TBA</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light">
                            <h6 class="text-muted">Venue</h6>
                            <p class="h5">TBA</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <button class="btn btn-outline-primary w-100 disabled">
                            <i class="fas fa-file-pdf me-2"></i> Download Permit (TBA)
                        </button>
                    </div>
                    <div class="col-sm-6">
                        <button class="btn btn-outline-secondary w-100">
                            <i class="fas fa-book me-2"></i> Reviewer Materials
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
