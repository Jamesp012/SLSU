<?php
// controllers/stem_test_contr.php
require_once __DIR__ . '/../models/STEMQuestionModel.php';
require_once __DIR__ . '/../models/STEMScoreModel.php';
require_once __DIR__ . '/../models/StudentModel.php';
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$questionModel = new STEMQuestionModel();
$scoreModel = new STEMScoreModel();
$studentModel = new StudentModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'submit_test') {
        $userAnswers = $_POST['answers'] ?? [];
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

        // Initialize pathway scores
        $pathwayScores = [];
        $pathways = $questionModel->getAllPathways();
        foreach ($pathways as $p) {
            $pathwayScores[$p['id']] = [
                'name' => $p['name'],
                'likes' => 0,
                'neutrals' => 0,
                'raw_score' => 0
            ];
        }

        // Calculate scores per pathway
        foreach ($questions as $q) {
            $qId = $q['id'];
            $pId = $q['pathway_id'];
            
            if (isset($userAnswers[$qId]) && isset($pathwayScores[$pId])) {
                $ans = strtolower($userAnswers[$qId]);
                if ($ans === 'like') {
                    $pathwayScores[$pId]['likes']++;
                } elseif ($ans === 'neutral') {
                    $pathwayScores[$pId]['neutrals']++;
                }
            }
        }

        // Finalize raw scores: (Likes * 2) + Neutrals
        $maxScore = 0;
        $topPathway = null;
        
        foreach ($pathwayScores as $pId => &$data) {
            $data['raw_score'] = ($data['likes'] * 2) + $data['neutrals'];
            
            // Calculate stanine for this pathway (assuming max score is 40 based on 20 items per pathway)
            // Percentage = (raw_score / 40) * 100
            $percentage = ($data['raw_score'] / 40) * 100;
            $stanine = 1;
            if ($percentage >= 97) $stanine = 9;
            elseif ($percentage >= 90) $stanine = 8;
            elseif ($percentage >= 80) $stanine = 7;
            elseif ($percentage >= 60) $stanine = 6;
            elseif ($percentage >= 50) $stanine = 5;
            elseif ($percentage >= 25) $stanine = 4;
            elseif ($percentage >= 15) $stanine = 3;
            elseif ($percentage >= 5) $stanine = 2;
            else $stanine = 1;
            
            $data['stanine'] = $stanine;

            if ($data['raw_score'] > $maxScore) {
                $maxScore = $data['raw_score'];
                $topPathway = $data['name'];
            }
        }

        // Prepare data for storage
        $scoreData = [
            'student_id' => $student['id'],
            'top_pathway' => $topPathway,
            'max_score' => $maxScore,
            'all_scores' => json_encode($pathwayScores),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $result = $scoreModel->addScore($scoreData);

        if (isset($result['error'])) {
            error_log("Failed to save STEM score: " . json_encode($result));
        }
        
        // Update profile with STEM stanines (store all as JSON in cognitive_stanines column)
        $studentModel->updateStudent($student['id'], ['cognitive_stanines' => json_encode($pathwayScores)]);

        // Clear existing pathway stanines first to avoid unique constraint violation on retake
        $scoreModel->deletePathwayStanines($student['id']);

        // Save individual pathway stanines
        $pathwayStaninesData = [];
        foreach ($pathwayScores as $pId => $data) {
            $pathwayStaninesData[] = [
                'student_id' => $student['id'],
                'pathway_id' => $pId,
                'pathway_name' => $data['name'],
                'raw_score' => $data['raw_score'],
                'stanine' => $data['stanine'],
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        $scoreModel->bulkAddPathwayStanines($pathwayStaninesData);

        // Trigger CareerHelper if both tests are done
        require_once __DIR__ . '/../helpers/CareerHelper.php';
        $recommendations = CareerHelper::getRecommendations('Science Technology, Engineering and Mathematics', $pathwayScores);

        echo json_encode([
            'status' => 'success', 
            'message' => 'Test submitted successfully',
            'score' => $maxScore,
            'top_pathway' => $topPathway,
            'pathway_scores' => $pathwayScores,
            'recommendations' => $recommendations
        ]);
        exit();
    }

    if ($action === 'debug_reset_stem') {
        $email = $_SESSION['email'];
        $student = $studentModel->getStudentByEmail($email);
        if ($student) {
            $scoreModel->deleteScore($student['id']);
            $scoreModel->deletePathwayStanines($student['id']);
            $studentModel->updateStudent($student['id'], ['cognitive_stanines' => null]);
        }
        header("Location: ../views/user/dashboard.php");
        exit();
    }
}
