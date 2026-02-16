<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../models/AchievementQuestionModel.php';
require_once __DIR__ . '/../models/AchievementScoreModel.php';

$questionModel = new AchievementQuestionModel();
$scoreModel = new AchievementScoreModel();

// Get all scores
global $php_fetch, $php_update;
$scores = $php_fetch('achievement_scores', '*', [], null, true);

if (isset($scores['error'])) {
    die("Error fetching scores: " . $scores['error']);
}

echo "Processing " . count($scores) . " scores...\n";

$questions = $questionModel->getAllQuestions(120); // Get all to be safe
$qMap = [];
foreach ($questions as $q) {
    $qMap[$q['id']] = $q;
}

foreach ($scores as $s) {
    echo "Processing score for student ID: " . $s['student_id'] . "\n";
    
    // We don't have the original answers in achievement_scores table unfortunately, 
    // unless they are stored in category_scores already but just empty.
    
    // If category_scores is already populated with something, we might be able to fix it.
    // If it's NULL, we can't recalculate perfectly without the raw answers.
    
    // HOWEVER, if the user just took the test and it showed 0, 
    // it means the controller FAILED to save category_scores correctly.
    
    // Let's check the controller logic again.
}

echo "Done.\n";
