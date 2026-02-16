<?php
session_start();
// Allow both student (for their own) and admin (for any student)
if (!isset($_SESSION['user_role'])) {
    header("Location: ../../index.php");
    exit();
}

require_once __DIR__ . '/../../models/StudentModel.php';
require_once __DIR__ . '/../../models/AchievementScoreModel.php';
require_once __DIR__ . '/../../models/STEMScoreModel.php';
require_once __DIR__ . '/../../helpers/CareerHelper.php';

$studentModel = new StudentModel();
$achievementScoreModel = new AchievementScoreModel();
$stemScoreModel = new STEMScoreModel();

// Determine which student to show
$studentId = $_GET['student_id'] ?? null;
$email = $_SESSION['email'];

if ($_SESSION['user_role'] === 'admin' && $studentId) {
    // Admin viewing a specific student
    $student = $studentModel->getStudentById($studentId);
    $isAdminCopy = true;
} else if ($_SESSION['user_role'] === 'student') {
    // Student viewing their own
    $student = $studentModel->getStudentByEmail($email);
    $isAdminCopy = false;
} else {
    die("Unauthorized access or missing student ID.");
}

if (!$student) {
    die("Student profile not found.");
}

$achievementScore = $achievementScoreModel->getScoreByStudentId($student['id']);
$stemScore = $stemScoreModel->getScoreByStudentId($student['id']);

// Get recommendations and electives
$pathwayScores = $stemScore ? json_decode($stemScore['all_scores'], true) : null;
$recommendations = CareerHelper::getRecommendations($student['preferred_track'] ?? 'Field Experience', $pathwayScores);

// If STEM score exists, we might have multiple recommendations, but for the general cluster/electives 
// we'll focus on the top one for the summary sections if needed, or show per top 3.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - <?php echo $student['first_name'] . ' ' . $student['last_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            border: 1px solid #eee;
        }
        .header-section {
            border-bottom: 3px solid #184226;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        .university-name {
            color: #184226;
            font-weight: bold;
            font-size: 24px;
            text-transform: uppercase;
        }
        .result-title {
            font-size: 20px;
            color: #555;
            margin-top: 5px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            width: 150px;
            display: inline-block;
        }
        .info-value {
            color: #000;
        }
        .section-title {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-left: 5px solid #184226;
            margin: 30px 0 15px 0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 16px;
        }
        .stanine-badge {
            background-color: #184226;
            color: white;
            padding: 5px 12px;
            border-radius: 4px;
            font-weight: bold;
        }
        .pathway-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .elective-tag {
            display: inline-block;
            background-color: #e9ecef;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 5px;
            margin-bottom: 5px;
            color: #495057;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                background-color: white;
            }
            .print-container {
                border: none;
                width: 100%;
                max-width: 100%;
                padding: 0;
            }
        }
    </style>
</head>
<body>

<div class="no-print text-center my-4">
    <button onclick="window.print()" class="btn btn-primary btn-lg px-5">
        <i class="fas fa-print me-2"></i> Print Result / Save as PDF
    </button>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-lg ms-2">Back to Dashboard</a>
</div>

<div class="print-container shadow-sm mb-5">
    <!-- Header -->
    <div class="header-section text-center position-relative">
        <?php if (isset($isAdminCopy) && $isAdminCopy): ?>
            <div class="position-absolute top-0 start-0 badge bg-danger text-uppercase p-2" style="font-size: 0.6rem; transform: rotate(-15deg); margin-top: -10px;">
                Admin Copy
            </div>
        <?php endif; ?>
        <div class="university-name">Southern Luzon State University</div>
        <div class="result-title">Admission Examination Result Summary</div>
    </div>

    <!-- Student Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="mb-2">
                <span class="info-label">Name:</span>
                <span class="info-value"><?php echo strtoupper($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'][0] . '. ' : '') . $student['last_name']); ?></span>
            </div>
            <div class="mb-2">
                <span class="info-label">LRN:</span>
                <span class="info-value"><?php echo $student['lrn']; ?></span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-2">
                <span class="info-label">Assigned Track:</span>
                <span class="info-value fw-bold text-primary"><?php echo $student['preferred_track'] ?? 'Not Assigned'; ?></span>
            </div>
            <div class="mb-2">
                <span class="info-label">Date Generated:</span>
                <span class="info-value"><?php echo date('F d, Y'); ?></span>
            </div>
        </div>
    </div>

    <!-- Test Scores (Stanines) -->
    <div class="section-title">Examination Scores (Stanines)</div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Examination Name</th>
                    <th class="text-center">Raw Score</th>
                    <th class="text-center">Percentile Range</th>
                    <th class="text-center">Stanine Score</th>
                    <th class="text-center">Interpretation</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Scholastic Ability Test Breakdown
                $achievementCats = [
                    'Scientific Ability' => ['total' => 20],
                    'Verbal Comprehension' => ['total' => 20],
                    'Numerical Ability' => ['total' => 20]
                ];

                $catScores = [];
                if (isset($achievementScore['category_scores']) && !empty($achievementScore['category_scores'])) {
                    $rawCatScores = $achievementScore['category_scores'];
                    while (is_string($rawCatScores) && !empty($rawCatScores)) {
                        $decoded = json_decode($rawCatScores, true);
                        if (json_last_error() !== JSON_ERROR_NONE) break;
                        $rawCatScores = $decoded;
                    }
                    if (is_array($rawCatScores)) {
                        $catScores = $rawCatScores;
                    }
                }

                // Fallback: Check if it's stored in the student's profile (cognitive_stanines)
                if (empty($catScores) && isset($student['cognitive_stanines']) && !empty($student['cognitive_stanines'])) {
                    $rawCatScores = $student['cognitive_stanines'];
                    while (is_string($rawCatScores) && !empty($rawCatScores)) {
                        $decoded = json_decode($rawCatScores, true);
                        if (json_last_error() !== JSON_ERROR_NONE) break;
                        $rawCatScores = $decoded;
                    }
                    if (is_array($rawCatScores)) {
                        $catScores = $rawCatScores;
                    }
                }

                // If no category scores (old data), estimate them for display
                if (empty($catScores) && isset($achievementScore['score'])) {
                    $totalScore = $achievementScore['score'];
                    $totalParts = count($achievementCats);
                    $baseScore = floor($totalScore / $totalParts);
                    $remainder = $totalScore % $totalParts;
                    
                    $i = 0;
                    foreach ($achievementCats as $catName => $defaults) {
                        $pScore = $baseScore + ($i < $remainder ? 1 : 0);
                        if ($pScore > 20) $pScore = 20;
                        
                        $pPercentile = ($pScore / 20) * 100;
                        
                        // Map to stanine
                        $pStanine = 1;
                        if ($pPercentile >= 96) $pStanine = 9;
                        elseif ($pPercentile >= 89) $pStanine = 8;
                        elseif ($pPercentile >= 77) $pStanine = 7;
                        elseif ($pPercentile >= 60) $pStanine = 6;
                        elseif ($pPercentile >= 40) $pStanine = 5;
                        elseif ($pPercentile >= 23) $pStanine = 4;
                        elseif ($pPercentile >= 11) $pStanine = 3;
                        elseif ($pPercentile >= 4) $pStanine = 2;
                        
                        $catScores[$catName] = [
                            'score' => $pScore,
                            'total' => 20,
                            'percentile' => round($pPercentile),
                            'stanine' => $pStanine
                        ];
                        $i++;
                    }
                }

                foreach ($achievementCats as $catName => $defaults) {
                    $data = $catScores[$catName] ?? null;
                    $score = $data['score'] ?? 0;
                    $total = $data['total'] ?? $defaults['total'];
                    $stanine = $data['stanine'] ?? 0;
                    $stanineInfo = CareerHelper::getStanineInfo($stanine);
                    ?>
                    <tr>
                        <td class="ps-4"><i><?php echo $catName; ?></i></td>
                        <td class="text-center small"><?php echo $score; ?> / <?php echo $total; ?></td>
                        <td class="text-center small"><?php echo $stanineInfo['range']; ?>%</td>
                        <td class="text-center small"><?php echo $stanine; ?></td>
                        <td class="text-center small"><?php echo $stanineInfo['interpretation']; ?></td>
                        <td class="small text-muted">Part Result</td>
                    </tr>
                    <?php
                }
                ?>
                <tr class="table-light">
                    <td class="fw-bold">Scholastic Ability Test (Overall)</td>
                    <td class="text-center fw-bold"><?php echo $achievementScore['score'] ?? 'N/A'; ?> / <?php echo $achievementScore['total_questions'] ?? 'N/A'; ?></td>
                    <td class="text-center fw-bold">-</td>
                    <td class="text-center"><span class="stanine-badge"><?php echo $achievementScore['stanine'] ?? 'N/A'; ?></span></td>
                    <td class="text-center fw-bold"><?php echo ($achievementScore && isset($achievementScore['stanine'])) ? CareerHelper::getStanineInfo($achievementScore['stanine'])['interpretation'] : 'N/A'; ?></td>
                    <td><?php echo ($achievementScore && $achievementScore['is_passed']) ? '<span class="text-success fw-bold">Passed</span>' : '<span class="text-danger fw-bold">Failed</span>'; ?></td>
                </tr>

                <?php if ($stemScore && $pathwayScores): ?>
                <tr class="table-secondary">
                    <td colspan="6" class="fw-bold">Interest-Based Assessment (STEM Pathways Breakdown)</td>
                </tr>
                <?php 
                foreach ($pathwayScores as $pId => $data) {
                    $stanine = $data['stanine'];
                    $stanineInfo = CareerHelper::getStanineInfo($stanine);
                    ?>
                    <tr>
                        <td class="ps-4"><i><?php echo $data['name']; ?></i></td>
                        <td class="text-center small"><?php echo $data['raw_score']; ?> / 40</td>
                        <td class="text-center small"><?php echo $stanineInfo['range']; ?>%</td>
                        <td class="text-center small"><?php echo $stanine; ?></td>
                        <td class="text-center small"><?php echo $stanineInfo['interpretation']; ?></td>
                        <td class="small text-muted">Pathway Interest</td>
                    </tr>
                <?php } ?>
                <tr class="table-light">
                    <td class="fw-bold">Interest-Based Assessment (Overall Result)</td>
                    <td class="text-center fw-bold">-</td>
                    <td class="text-center fw-bold">-</td>
                    <td class="text-center"><span class="stanine-badge">Done</span></td>
                    <td class="text-center fw-bold">-</td>
                    <td><span class="text-success fw-bold">Completed</span></td>
                </tr>
                <?php elseif ($stemScore): ?>
                <tr>
                    <td class="fw-bold">Interest-Based Assessment</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center"><span class="stanine-badge">Done</span></td>
                    <td class="text-center">-</td>
                    <td><span class="text-success fw-bold">Completed</span></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Top 3 Interests & Career Pathways -->
    <div class="section-title">Top 3 Career Interests & Recommendations</div>
    <?php if ($stemScore && !empty($recommendations)): ?>
        <?php foreach (array_slice($recommendations, 0, 3) as $index => $rec): 
            $displayName = preg_replace('/^STEM PATHWAY \d+\.\s*/i', '', $rec['pathway']);
        ?>
            <div class="pathway-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold text-primary">#<?php echo $index + 1; ?> <?php echo $displayName; ?></h5>
                </div>
                
                <div class="mb-3">
                    <div class="small text-dark mb-2"><?php echo htmlspecialchars($rec['description']); ?></div>
                </div>

                <div class="mb-3">
                    <div class="small fw-bold text-primary mb-1">RELATED COURSES TO PURSUE IN COLLEGE:</div>
                    <div class="small text-muted"><?php echo implode(', ', $rec['courses']); ?></div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="small fw-bold text-success mb-2">RECOMMENDED ELECTIVES TO TAKE IN SSHS:</div>
                    </div>
                    <div class="col-6">
                        <div class="small fw-bold text-muted mb-2">ACADEMIC:</div>
                        <?php foreach ($rec['academic_electives'] as $elective): ?>
                            <span class="elective-tag"><?php echo $elective; ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-6">
                        <div class="small fw-bold text-muted mb-2">TECH - PRO:</div>
                        <?php foreach ($rec['techpro_electives'] as $elective): ?>
                            <span class="elective-tag"><?php echo $elective; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted fst-italic">No interest assessment data available.</p>
    <?php endif; ?>

    <!-- Summary of Science Inclined Careers -->
    <?php if ($student['preferred_track'] === 'Science Technology, Engineering and Mathematics'): ?>
    <div class="section-title">Science Inclined Career Pathways Summary</div>
    <div class="p-3 border rounded bg-light">
        <p class="small mb-3">Your aptitude for the STEM strand has been evaluated. Below is the summary of your focus areas based on your interest results:</p>
        <div class="row">
            <div class="col-6">
                <div class="fw-bold small text-primary mb-2">Primary Academic Focus:</div>
                <ul class="small">
                    <li>Advanced Mathematics</li>
                    <li>Experimental Sciences</li>
                    <li>Scientific Research & Reporting</li>
                </ul>
            </div>
            <div class="col-6">
                <div class="fw-bold small text-primary mb-2">Technological Specialization:</div>
                <ul class="small">
                    <li>Digital Literacy & Programming</li>
                    <li>Technical Design & Modeling</li>
                    <li>Innovation & Applied Technology</li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer Note -->
    <div class="mt-5 pt-4 text-center text-muted small border-top">
        <p>This is a computer-generated report from the SLSU Entrance Exam Portal. No signature is required.</p>
        <p>&copy; <?php echo date('Y'); ?> Southern Luzon State University. All rights reserved.</p>
    </div>
</div>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>
    // Auto trigger print after a short delay to ensure layout is ready
    window.onload = function() {
        // Uncomment below if you want it to trigger automatically
        // setTimeout(() => { window.print(); }, 1000);
    }
</script>

</body>
</html>