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
require_once __DIR__ . '/../../models/AchievementScoreModel.php';
require_once __DIR__ . '/../../models/STEMScoreModel.php';

$studentModel = new StudentModel();
$scoreModel = new AchievementScoreModel();
$stemScoreModel = new STEMScoreModel();
$student_data = $studentModel->getStudentByEmail($_SESSION['email']);

$achievement_score = null;
$stem_score = null;
if ($student_data) {
    $achievement_score = $scoreModel->getScoreByStudentId($student_data['id']);
    $stem_score = $stemScoreModel->getScoreByStudentId($student_data['id']);
}

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
        <?php if (!$achievement_score): ?>
        <div class="card shadow mb-4 border-left-warning">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 font-weight-bold text-warning">Step 1: Scholastic Ability Test</h6>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <div class="mb-4">
                        <i class="fas fa-file-alt fa-4x text-warning opacity-25"></i>
                    </div>
                    <h4>Scholastic Ability Test Required</h4>
                    <p class="text-muted mb-4">Before taking the Interest-Based Assessment, you must first complete the Scholastic Ability Test. Your eligibility for the STEM strand depends on this result.</p>
                    <a href="take_achievement_test.php" class="btn btn-warning btn-lg px-5 shadow-sm text-white">
                        <i class="fas fa-edit me-2"></i> Start Scholastic Ability Test
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card shadow mb-4 border-left-primary">
            <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Step 2: Interest-Based Assessment</h6>
                <?php if ($achievement_score['is_passed']): ?>
                    <span class="badge bg-success">Eligible for STEM</span>
                <?php else: ?>
                    <span class="badge bg-danger">Technical Vocational Only</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <div class="mb-4">
                        <i class="fas fa-laptop-code fa-4x text-primary opacity-25"></i>
                    </div>
                    <h4>Interest-Based Assessment</h4>
                    <p class="text-muted mb-4">
                        <?php if (!$achievement_score['is_passed']): ?>
                            <div class="alert alert-warning mb-4">
                                <i class="fas fa-exclamation-triangle me-2"></i> 
                                Since you did not pass the Scholastic Ability Test (Score: <?php echo $achievement_score['score']; ?>), you are no longer eligible for the STEM strand. You will be reconsidered for Technical Vocational.
                            </div>
                            <div class="card mb-4 border-left-info shadow-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Stanine Equivalent</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                    if (isset($achievement_score['stanine'])) {
                                                        echo $achievement_score['stanine'];
                                                    } else {
                                                        $p = $achievement_score['percentage'];
                                                        if ($p >= 97) echo 9;
                                                        elseif ($p >= 90) echo 8;
                                                        elseif ($p >= 80) echo 7;
                                                        elseif ($p >= 60) echo 6;
                                                        elseif ($p >= 50) echo 5;
                                                        elseif ($p >= 25) echo 4;
                                                        elseif ($p >= 15) echo 3;
                                                        elseif ($p >= 5) echo 2;
                                                        else echo 1;
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-4 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="m-0 font-weight-bold text-dark">Related Courses for <?php echo htmlspecialchars($student_data['preferred_track'] ?? 'Selected'); ?> Track</h6>
                                </div>
                                <div class="card-body">
                                    <?php 
                                        require_once __DIR__ . '/../../helpers/CareerHelper.php';
                                        $recommendations = CareerHelper::getRecommendations($student_data['preferred_track'] ?? 'Field Experience');
                                    ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($recommendations[0]['courses'] as $course): ?>
                                            <li class="list-group-item"><i class="fas fa-graduation-cap me-2 text-primary"></i> <?php echo htmlspecialchars($course); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php if (!$stem_score): ?>
                                <div class="card mb-4 shadow-sm">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="m-0 font-weight-bold">Related Courses</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                            require_once __DIR__ . '/../../helpers/CareerHelper.php';
                                            $recommendationData = CareerHelper::getRecommendations('Science Technology, Engineering and Mathematics');
                                            $courses = $recommendationData[0]['courses'] ?? [];
                                        ?>
                                        <p class="small text-muted mb-3">Complete the Interest-Based Assessment to see your personalized course recommendation. Here are some courses under this strand:</p>
                                        <div class="row">
                                            <?php 
                                                $half = ceil(count($courses) / 2);
                                                $chunks = array_chunk($courses, $half);
                                                foreach ($chunks as $chunk):
                                            ?>
                                            <div class="col-md-6">
                                                <ul class="list-group list-group-flush small">
                                                    <?php foreach ($chunk as $course): ?>
                                                    <li class="list-group-item py-1"><i class="fas fa-check-circle me-2 text-success"></i> <?php echo htmlspecialchars($course); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="card mb-4 shadow-sm border-left-success">
                                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                        <h6 class="m-0 font-weight-bold"><i class="fas fa-award me-2"></i> Your Top STEM Interests</h6>
                                        <a href="print_result.php" class="btn btn-sm btn-light text-success fw-bold">
                                            <i class="fas fa-print me-1"></i> Print Result
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small mb-4">Based on your Interest-Based Assessment, here are the top 3 pathways where you showed the most interest and aptitude:</p>
                                        
                                        <?php 
                                            require_once __DIR__ . '/../../helpers/CareerHelper.php';
                                            $pathwayScores = json_decode($stem_score['all_scores'], true);
                                            $recommendations = CareerHelper::getRecommendations('Science Technology, Engineering and Mathematics', $pathwayScores);
                                            
                                            foreach ($recommendations as $index => $res):
                                                $pathway = $res['pathway'];
                                                $courses = $res['courses'];
                                        ?>
                                            <div class="mb-4 last-child-mb-0">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="font-weight-bold text-primary mb-0">
                                                        <span class="badge bg-primary me-2">#<?php echo $index + 1; ?></span>
                                                        <?php echo htmlspecialchars($pathway); ?>
                                                    </h6>
                                                    <span class="badge bg-success text-white">Stanine: <?php echo $res['stanine']; ?></span>
                                                </div>
                                                <div class="ps-4 ms-2 border-start">
                                                    <p class="small text-muted mb-2">Recommended Courses:</p>
                                                    <div class="row">
                                                        <?php foreach ($courses as $course): ?>
                                                            <div class="col-md-6">
                                                                <div class="small mb-1"><i class="fas fa-graduation-cap me-2 text-success"></i> <?php echo htmlspecialchars($course); ?></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if ($index < 2): ?><hr class="my-3"><?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($achievement_score['is_passed']): ?>
                            <?php if (!$stem_score): ?>
                                <p class="text-muted mb-4">
                                    You can now proceed to take your online entrance examination. Please ensure you have a stable internet connection before starting.
                                </p>
                                <a href="take_exam.php" class="btn btn-primary btn-lg px-5 shadow-sm">
                                    <i class="fas fa-play-circle me-2"></i> Start Exam Now
                                </a>
                            <?php else: ?>
                                <div class="alert alert-success mt-4">
                                    <i class="fas fa-check-circle me-2"></i> You have successfully completed all required examinations.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-secondary mt-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-info-circle me-2"></i> Your examination process is complete. Your strand assignment is <strong>Technical Vocational</strong>.
                                </div>
                                <a href="print_result.php" class="btn btn-sm btn-outline-secondary border-2 fw-bold">
                                    <i class="fas fa-print me-1"></i> Print Result
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($achievement_score['is_passed']): ?>
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
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
