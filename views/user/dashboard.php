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
?>
<style>
    .transition { transition: all 0.3s ease; }
    .hover-shadow:hover { 
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .bg-primary-subtle { background-color: rgba(13, 110, 253, 0.1); }
</style>
<?php
require_once __DIR__ . '/../../models/StudentModel.php';
require_once __DIR__ . '/../../models/AchievementScoreModel.php';
require_once __DIR__ . '/../../models/STEMScoreModel.php';
require_once __DIR__ . '/../../models/STEMQuestionModel.php';

$studentModel = new StudentModel();
$scoreModel = new AchievementScoreModel();
$stemScoreModel = new STEMScoreModel();
$stemQuestionModel = new STEMQuestionModel();
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
                                Since you did not pass the Scholastic Ability Test (Average Stanine: <?php echo $achievement_score['stanine']; ?>), you are no longer eligible for the STEM strand. You will be reconsidered for Technical Vocational.
                            </div>
                            <div class="card mb-4 border-left-info shadow-sm">
                                <div class="card-body">
                                    <h6 class="font-weight-bold text-info text-uppercase mb-3">Scholastic Ability Test Breakdown</h6>
                                    <?php 
                                        $catScores = $achievement_score['category_scores'] ?? null;
                                        // Robust JSON decoding (handles double encoding)
                                        while (is_string($catScores) && !empty($catScores)) {
                                            $decoded = json_decode($catScores, true);
                                            if (json_last_error() !== JSON_ERROR_NONE) break;
                                            $catScores = $decoded;
                                        }
                                        
                                        if ($catScores && is_array($catScores)) {
                                            foreach ($catScores as $cat => $data):
                                    ?>
                                        <div class="row align-items-center mb-2">
                                            <div class="col">
                                                <div class="small fw-bold text-dark"><?php echo $cat; ?></div>
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $data['percentile'] ?? 0; ?>%"></div>
                                                </div>
                                            </div>
                                            <div class="col-auto text-end" style="width: 120px;">
                                                <div class="small text-muted">Pct: <?php echo $data['percentile'] ?? 0; ?>%</div>
                                                <div class="small fw-bold">Stanine: <?php echo $data['stanine'] ?? 0; ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                        <hr class="my-2">
                                        <div class="row align-items-center">
                                            <div class="col text-start">
                                                <div class="small fw-bold text-primary">Average Stanine Score</div>
                                            </div>
                                            <div class="col-auto text-end" style="width: 120px;">
                                                <div class="h5 mb-0 fw-bold text-primary"><?php echo $achievement_score['stanine'] ?? 'N/A'; ?></div>
                                            </div>
                                        </div>
                                    <?php  } else { ?>
                                        <div class="row align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Overall Stanine Equivalent</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo $achievement_score['stanine'] ?? 'N/A'; ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    <?php } ?>
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
                                        <p class="small text-muted mb-4">Complete the Interest-Based Assessment/Inventory to determine the recommended STEM career pathway for you. Here are the available STEM career pathways:</p>
                                        
                                        <div class="row">
                                            <?php 
                                                $allPathways = $stemQuestionModel->getAllPathways();
                                                $half = ceil(count($allPathways) / 2);
                                                $chunks = array_chunk($allPathways, $half);
                                                foreach ($chunks as $chunk):
                                            ?>
                                            <div class="col-md-6">
                                                <ul class="list-group list-group-flush small">
                                                    <?php foreach ($chunk as $pathway): ?>
                                                    <li class="list-group-item py-1 bg-transparent border-0"><i class="fas fa-check-circle me-2 text-success"></i> <?php echo htmlspecialchars($pathway['name']); ?></li>
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
                                        <div class="d-flex">
                                            <form action="../../controllers/stem_test_contr.php" method="POST" class="me-2" onsubmit="return confirm('DEBUG: Are you sure you want to reset your interest assessment?');">
                                                <input type="hidden" name="action" value="debug_reset_stem">
                                                <button type="submit" class="btn btn-sm btn-danger opacity-50"><i class="fas fa-bug"></i></button>
                                            </form>
                                            <a href="print_result.php" class="btn btn-sm btn-light text-success fw-bold">
                                                <i class="fas fa-print me-1"></i> Print Result
                                            </a>
                                        </div>
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
                                                    <div class="small fw-bold text-muted mb-2">Recommended Career Cluster: <span class="text-dark"><?php echo htmlspecialchars($res['cluster']); ?></span></div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="small fw-bold text-muted mb-1">Academic Focus:</div>
                                                            <div class="small"><?php echo implode(', ', $res['academic_electives']); ?></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="small fw-bold text-muted mb-1">Technical Skills:</div>
                                                            <div class="small"><?php echo implode(', ', $res['techpro_electives']); ?></div>
                                                        </div>
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
