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
    .last-child-mb-0:last-child { margin-bottom: 0 !important; }
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

        <!-- Stanine Reference Card -->
        <div class="card shadow mt-4">
            <div class="card-header py-3 bg-light">
                <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-info-circle me-2"></i> Stanine Reference Guide</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0" style="font-size: 0.75rem;">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-3">Stanine</th>
                                <th>Percentile Range</th>
                                <th>Interpretation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            require_once __DIR__ . '/../../helpers/CareerHelper.php';
                            for ($i = 9; $i >= 1; $i--): 
                                $info = CareerHelper::getStanineInfo($i);
                                $color = ($i >= 7) ? 'text-success' : (($i >= 4) ? 'text-warning' : 'text-danger');
                            ?>
                            <tr>
                                <td class="ps-3 fw-bold"><?php echo $i; ?></td>
                                <td><?php echo $info['range']; ?>%</td>
                                <td class="fw-bold <?php echo $color; ?>"><?php echo $info['interpretation']; ?></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
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
                <h6 class="m-0 font-weight-bold text-primary">Examination Results & Eligibility</h6>
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
                    
                    <?php if (!$achievement_score['is_passed']): ?>
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i> 
                            Since you did not pass the Scholastic Ability Test, you are no longer eligible for the STEM strand. You will be reconsidered for Technical Vocational.
                        </div>
                        
                        <!-- Scholastic Results for Failed Students -->
                        <div class="card mb-4 border-left-success shadow-sm">
                            <div class="card-header bg-success text-white text-start">
                                <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-bar me-2"></i> Scholastic Ability Test Results</h6>
                            </div>
                            <div class="card-body text-start">
                                <?php 
                                    $catScores = $achievement_score['category_scores'] ?? null;
                                    while (is_string($catScores) && !empty($catScores)) {
                                        $decoded = json_decode($catScores, true);
                                        if (json_last_error() !== JSON_ERROR_NONE) break;
                                        $catScores = $decoded;
                                    }
                                    if (!$catScores || !is_array($catScores)) {
                                        if (isset($student_data['cognitive_stanines'])) {
                                            $rawStanines = $student_data['cognitive_stanines'];
                                            while (is_string($rawStanines) && !empty($rawStanines)) {
                                                $decoded = json_decode($rawStanines, true);
                                                if (json_last_error() !== JSON_ERROR_NONE) break;
                                                $rawStanines = $decoded;
                                            }
                                            if (is_array($rawStanines)) $catScores = $rawStanines;
                                        }
                                    }

                                    $saCategories = ['Scientific Ability', 'Verbal Comprehension', 'Numerical Ability'];
                                    $foundSa = false;
                                    require_once __DIR__ . '/../../helpers/CareerHelper.php';
                                    if ($catScores && is_array($catScores)) {
                                        foreach ($saCategories as $cat):
                                            if (!isset($catScores[$cat])) continue;
                                            $data = $catScores[$cat];
                                            $stanineInfo = CareerHelper::getStanineInfo($data['stanine']);
                                            $foundSa = true;
                                ?>
                                    <div class="mb-4 last-child-mb-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="font-weight-bold text-success mb-0"><?php echo $cat; ?></h6>
                                            <div class="text-end">
                                                <span class="badge bg-success text-white">Stanine: <?php echo $data['stanine']; ?></span>
                                                <span class="badge bg-secondary text-white ms-1"><?php echo $stanineInfo['interpretation']; ?></span>
                                            </div>
                                        </div>
                                        <div class="ps-3 ms-1 border-start">
                                            <div class="progress progress-sm mb-1" style="height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $data['percentile'] ?? 0; ?>%"></div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Raw Score: <?php echo $data['score']; ?>/<?php echo $data['total']; ?></small>
                                                <small class="text-muted">Percentile Range: <?php echo $stanineInfo['range']; ?>%</small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; }
                                if (!$foundSa) { ?>
                                    <div class="text-center py-3">
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">Overall Stanine: <?php echo $achievement_score['stanine'] ?? 'N/A'; ?></div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-light text-start">
                                <h6 class="m-0 font-weight-bold text-dark">Related Courses for <?php echo htmlspecialchars($student_data['preferred_track'] ?? 'Selected'); ?> Track</h6>
                            </div>
                            <div class="card-body text-start">
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
                        <!-- Results for Passed Students -->
                        <div class="card mb-4 border-left-success shadow-sm">
                            <div class="card-header bg-success text-white text-start">
                                <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-bar me-2"></i> Scholastic Ability Test Results</h6>
                            </div>
                            <div class="card-body text-start">
                                <?php 
                                    $catScores = $achievement_score['category_scores'] ?? null;
                                    while (is_string($catScores) && !empty($catScores)) {
                                        $decoded = json_decode($catScores, true);
                                        if (json_last_error() !== JSON_ERROR_NONE) break;
                                        $catScores = $decoded;
                                    }
                                    if (!$catScores || !is_array($catScores)) {
                                        if (isset($student_data['cognitive_stanines'])) {
                                            $rawStanines = $student_data['cognitive_stanines'];
                                            while (is_string($rawStanines) && !empty($rawStanines)) {
                                                $decoded = json_decode($rawStanines, true);
                                                if (json_last_error() !== JSON_ERROR_NONE) break;
                                                $rawStanines = $decoded;
                                            }
                                            if (is_array($rawStanines)) $catScores = $rawStanines;
                                        }
                                    }

                                    $saCategories = ['Scientific Ability', 'Verbal Comprehension', 'Numerical Ability'];
                                    require_once __DIR__ . '/../../helpers/CareerHelper.php';
                                    if ($catScores && is_array($catScores)) {
                                        foreach ($saCategories as $cat):
                                            if (!isset($catScores[$cat])) continue;
                                            $data = $catScores[$cat];
                                            $stanineInfo = CareerHelper::getStanineInfo($data['stanine']);
                                ?>
                                    <div class="mb-4 last-child-mb-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="font-weight-bold text-success mb-0"><?php echo $cat; ?></h6>
                                            <div class="text-end">
                                                <span class="badge bg-success text-white">Stanine: <?php echo $data['stanine']; ?></span>
                                                <span class="badge bg-secondary text-white ms-1"><?php echo $stanineInfo['interpretation']; ?></span>
                                            </div>
                                        </div>
                                        <div class="ps-3 ms-1 border-start">
                                            <div class="progress progress-sm mb-1" style="height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $data['percentile'] ?? 0; ?>%"></div>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Raw Score: <?php echo $data['score']; ?>/<?php echo $data['total']; ?></small>
                                                <small class="text-muted">Percentile Range: <?php echo $stanineInfo['range']; ?>%</small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; } ?>
                            </div>
                        </div>

                        <?php if (!$stem_score): ?>
                            <div class="card mb-4 shadow-sm">
                                <div class="card-header bg-success text-white text-start">
                                    <h6 class="m-0 font-weight-bold">STEM Career Pathways Preview</h6>
                                </div>
                                <div class="card-body text-start">
                                    <?php 
                                        require_once __DIR__ . '/../../helpers/CareerHelper.php';
                                        $recommendationData = CareerHelper::getRecommendations('Science Technology, Engineering and Mathematics');
                                    ?>
                                    <p class="small text-muted mb-4">Complete the Interest-Based Assessment/Inventory to determine the recommended STEM career pathway for you. Here are the available pathways:</p>
                                    
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
                                                <li class="list-group-item py-1 bg-transparent border-0"><i class="fas fa-check-circle me-2 text-success"></i> <?php echo htmlspecialchars(preg_replace('/^STEM PATHWAY \d+\.\s*/i', '', $pathway['name'])); ?></li>
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
                                <div class="card-body text-start">
                                    <p class="text-muted small mb-4">Based on your Interest-Based Assessment, here are the top 3 pathways where you showed the most interest and aptitude:</p>
                                    
                                    <?php 
                                        require_once __DIR__ . '/../../helpers/CareerHelper.php';
                                        $pathwayScores = json_decode($stem_score['all_scores'], true);
                                        $recommendations = CareerHelper::getRecommendations('Science Technology, Engineering and Mathematics', $pathwayScores);
                                        
                                        foreach ($recommendations as $index => $res):
                                            $pathway = $res['pathway'];
                                            // Remove "STEM PATHWAY X. " prefix if exists
                                            $displayName = preg_replace('/^STEM PATHWAY \d+\.\s*/i', '', $pathway);
                                            $stanineInfo = CareerHelper::getStanineInfo($res['stanine']);
                                    ?>
                                        <div class="mb-4 last-child-mb-0">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="font-weight-bold text-primary mb-0">
                                                    <?php echo htmlspecialchars($displayName); ?>
                                                </h6>
                                                <div class="text-end">
                                                    <span class="badge bg-success text-white">Stanine: <?php echo $res['stanine']; ?></span>
                                                    <span class="badge bg-secondary text-white ms-1"><?php echo $stanineInfo['interpretation']; ?></span>
                                                </div>
                                            </div>
                                            <div class="ps-4 ms-2 border-start">
                                                <div class="small text-dark mb-3"><?php echo htmlspecialchars($res['description']); ?></div>
                                                
                                                <div class="mb-3">
                                                    <div class="small fw-bold text-primary mb-1"><i class="fas fa-university me-1"></i> RELATED COURSES TO PURSUE IN COLLEGE</div>
                                                    <div class="small text-muted"><?php echo implode(', ', $res['courses']); ?></div>
                                                </div>
                                                <div class="mb-1">
                                                    <div class="small fw-bold text-success"><i class="fas fa-book-reader me-1"></i> RECOMMENDED ELECTIVES TO TAKE IN SSHS</div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-2">
                                                        <div class="small fw-bold text-muted mb-1">ACADEMIC:</div>
                                                        <div class="small text-secondary"><?php echo implode(', ', $res['academic_electives']); ?></div>
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <div class="small fw-bold text-muted mb-1">TECH - PRO:</div>
                                                        <div class="small text-secondary"><?php echo implode(', ', $res['techpro_electives']); ?></div>
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
                                You can now proceed to take your online interest assessment. Please ensure you have a stable internet connection before starting.
                            </p>
                            <a href="take_exam.php" class="btn btn-primary btn-lg px-5 shadow-sm">
                                <i class="fas fa-play-circle me-2"></i> Start Assessment Now
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
                <?php if ($achievement_score['is_passed'] && !$stem_score): ?>
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
