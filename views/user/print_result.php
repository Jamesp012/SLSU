<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
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

$email = $_SESSION['email'];
$student = $studentModel->getStudentByEmail($email);

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
    <div class="header-section text-center">
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
                    <th class="text-center" style="width: 150px;">Stanine Score</th>
                    <th>Status / Remarks</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Scholastic Ability Test</td>
                    <td class="text-center"><span class="stanine-badge"><?php echo $achievementScore['stanine'] ?? 'N/A'; ?></span></td>
                    <td><?php echo ($achievementScore && $achievementScore['is_passed']) ? '<span class="text-success fw-bold">Passed</span>' : '<span class="text-danger fw-bold">Failed</span>'; ?></td>
                </tr>
                <?php if ($stemScore): ?>
                <tr>
                    <td>Interest-Based Assessment</td>
                    <td class="text-center"><span class="stanine-badge"><?php echo $stemScore['max_score'] > 0 ? 'Completed' : 'N/A'; ?></span></td>
                    <td><span class="text-success fw-bold">Assessment Completed</span></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Top 3 Interests & Career Pathways -->
    <div class="section-title">Top 3 Career Interests & Recommendations</div>
    <?php if ($stemScore && !empty($recommendations)): ?>
        <?php foreach (array_slice($recommendations, 0, 3) as $index => $rec): ?>
            <div class="pathway-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold text-primary">#<?php echo $index + 1; ?> <?php echo $rec['pathway']; ?></h5>
                    <span class="badge bg-secondary">Cluster: <?php echo $rec['cluster']; ?></span>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="small fw-bold text-muted mb-2">Recommended Courses:</div>
                        <ul class="ps-3 mb-0">
                            <?php foreach ($rec['courses'] as $course): ?>
                                <li class="small"><?php echo $course; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <div class="small fw-bold text-muted">Academic Electives:</div>
                            <?php foreach ($rec['academic_electives'] as $elective): ?>
                                <span class="elective-tag"><?php echo $elective; ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div>
                            <div class="small fw-bold text-muted">TechPro Electives:</div>
                            <?php foreach ($rec['techpro_electives'] as $elective): ?>
                                <span class="elective-tag"><?php echo $elective; ?></span>
                            <?php endforeach; ?>
                        </div>
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