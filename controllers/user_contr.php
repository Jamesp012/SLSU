<?php
// controllers/user_contr.php
require_once __DIR__ . '/../models/StudentModel.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$studentModel = new StudentModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'complete_onboarding') {
        $lrn = $_POST['lrn'] ?? '';
        $recent_school = $_POST['recent_school'] ?? '';
        $preferred_track = $_POST['preferred_track'] ?? '';
        $email = $_SESSION['email'];

        if (empty($lrn) || empty($recent_school) || empty($preferred_track)) {
            echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
            exit();
        }

        // Get student profile by email
        $profile = $studentModel->getStudentByEmail($email, true);
        
        if (!$profile) {
            echo json_encode(['status' => 'error', 'message' => 'Profile not found']);
            exit();
        }

        $data = [
            'lrn' => $lrn,
            'recent_school' => $recent_school,
            'preferred_track' => $preferred_track
        ];

        $result = $studentModel->updateStudent($profile['id'], $data, true);

        if (isset($result['error'])) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update profile: ' . $result['error']]);
        } else {
            $_SESSION['onboarding_completed'] = true; // Still use session for UI logic
            echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
        }
        exit();
    }
}
