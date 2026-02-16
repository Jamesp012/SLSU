<?php
session_start();
// controllers/achievement_contr.php
require_once __DIR__ . '/../models/AchievementQuestionModel.php';
require_once __DIR__ . '/../models/AchievementScoreModel.php';
require_once __DIR__ . '/../models/StudentModel.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$questionModel = new AchievementQuestionModel();
$scoreModel = new AchievementScoreModel();
$studentModel = new StudentModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'submit_test') {
        $answers = $_POST['answers'] ?? [];
        $email = $_SESSION['email'];
        
        $student = $studentModel->getStudentByEmail($email);
        if (!$student) {
            echo json_encode(['status' => 'error', 'message' => 'Student profile not found']);
            exit();
        }

        $questions = $questionModel->getAllQuestions();
        if (isset($questions['error'])) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to load questions']);
            exit();
        }

        $correctCount = 0;
        $totalQuestions = count($questions);
        $categoryScores = [
            'Scientific Ability' => 0,
            'Verbal Comprehension' => 0,
            'Numerical Ability' => 0
        ];
        $categoryCounts = [
            'Scientific Ability' => 0,
            'Verbal Comprehension' => 0,
            'Numerical Ability' => 0
        ];

        foreach ($questions as $q) {
            $qNum = (int)$q['question_number'];
            
            // Force category based on question number ranges
            if ($qNum >= 1 && $qNum <= 20) {
                $cat = 'Scientific Ability';
            } elseif ($qNum >= 21 && $qNum <= 40) {
                $cat = 'Verbal Comprehension';
            } elseif ($qNum >= 41 && $qNum <= 60) {
                $cat = 'Numerical Ability';
            } else {
                $cat = $q['category'] ?? 'General';
            }

            if (!isset($categoryScores[$cat])) {
                $categoryScores[$cat] = 0;
                $categoryCounts[$cat] = 0;
            }
            $categoryCounts[$cat]++;

            $qId = $q['id'];
            if (isset($answers[$qId]) && strtolower($answers[$qId]) === strtolower($q['correct_answer'])) {
                $categoryScores[$cat]++;
                $correctCount++;
            }
        }

        $totalStanine = 0;
        $catResults = [];
        $categoriesWithQuestions = 0;

        // Sort categories to ensure consistent display order
        $displayOrder = ['Scientific Ability', 'Verbal Comprehension', 'Numerical Ability'];
        foreach ($displayOrder as $cat) {
            if (!isset($categoryScores[$cat])) continue;
            
            $score = $categoryScores[$cat];
            $count = $categoryCounts[$cat];
            $categoriesWithQuestions++;
            
            // Calculate percentile (using percentage correct as proxy if table is missing)
            $percentile = ($count > 0) ? ($score / $count) * 100 : 0;
            
            // Map to stanine based on standard distributions
            $catStanine = 1;
            if ($percentile >= 96) $catStanine = 9;
            elseif ($percentile >= 89) $catStanine = 8;
            elseif ($percentile >= 77) $catStanine = 7;
            elseif ($percentile >= 60) $catStanine = 6;
            elseif ($percentile >= 40) $catStanine = 5;
            elseif ($percentile >= 23) $catStanine = 4;
            elseif ($percentile >= 11) $catStanine = 3;
            elseif ($percentile >= 4) $catStanine = 2;
            else $catStanine = 1;

            $catResults[$cat] = [
                'score' => $score,
                'total' => $count,
                'percentile' => round($percentile),
                'stanine' => $catStanine
            ];
            
            $totalStanine += $catStanine;
        }

        $averageStanine = ($categoriesWithQuestions > 0) ? ($totalStanine / $categoriesWithQuestions) : 0;
        
        // Final score uses the average stanine rounded to 1 decimal place for precision
        // but we'll use whole number for passing criteria
        $stanine = round($averageStanine);
        $overallPercentage = ($totalQuestions > 0) ? ($correctCount / $totalQuestions) * 100 : 0;

        $isPassed = ($stanine >= 4);

        $scoreData = [
            'student_id' => $student['id'],
            'score' => $correctCount,
            'total_questions' => $totalQuestions,
            'percentage' => round($overallPercentage, 2),
            'stanine' => $stanine,
            'category_scores' => json_encode($catResults),
            'is_passed' => $isPassed
        ];

        $result = $scoreModel->addScore($scoreData);

        if (isset($result['error'])) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save score: ' . $result['error']]);
        } else {
            // Update profile with scholastic ability stanine
            $studentModel->updateStudent($student['id'], ['achievement_stanine' => $stanine]);

            require_once __DIR__ . '/../helpers/CareerHelper.php';
            $recommendations = CareerHelper::getRecommendations($isPassed ? 'Science Technology, Engineering and Mathematics' : 'Field Experience');
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Test submitted successfully',
                'score' => $correctCount,
                'total' => $totalQuestions,
                'percentage' => round($overallPercentage, 2),
                'stanine' => $stanine,
                'category_scores' => $catResults,
                'is_passed' => $isPassed,
                'recommendations' => $recommendations
            ]);
        }
        exit();
    }

    if ($action === 'update_track') {
        $track = $_POST['track'] ?? '';
        $email = $_SESSION['email'];

        if (empty($track)) {
            echo json_encode(['status' => 'error', 'message' => 'No track selected']);
            exit();
        }

        $student = $studentModel->getStudentByEmail($email);
        if (!$student) {
            echo json_encode(['status' => 'error', 'message' => 'Student profile not found']);
            exit();
        }

        $result = $studentModel->updateStudent($student['id'], ['preferred_track' => $track], true);

        if (isset($result['error'])) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update track: ' . $result['error']]);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Track updated successfully']);
        }
        exit();
    }
}
