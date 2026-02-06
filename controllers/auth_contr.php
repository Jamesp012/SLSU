<?php
// controllers/auth_contr.php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to output, log them instead
require_once __DIR__ . '/../models/UserModel.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $role = $_POST['role'] ?? 'student';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
            exit();
        }

        // TEST CREDENTIALS as fallback
        $test_admin = [
            'email' => 'admin@slsu.edu.ph',
            'password' => 'admin123'
        ];

        $test_student = [
            'email' => 'student@example.com',
            'password' => 'student123'
        ];

        $userModel = new UserModel();
        $user = $userModel->login($email, $password, true);

        if (!$user) {
            // Check against test credentials if DB login fails
            if ($role === 'admin' && $email === $test_admin['email'] && $password === $test_admin['password']) {
                $user = [
                    'id' => 'admin_test_001',
                    'role' => 'admin',
                    'email' => $email,
                    'first_name' => 'System',
                    'last_name' => 'Administrator',
                    'onboarding_completed' => true
                ];
            } elseif ($role === 'student' && $email === $test_student['email'] && $password === $test_student['password']) {
                $user = [
                    'id' => 'student_test_001',
                    'role' => 'student',
                    'email' => $email,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'onboarding_completed' => true // Assume test student is already onboarded
                ];
            }
        } else {
            // For real DB users, check the profile for onboarding status (LRN presence)
            require_once __DIR__ . '/../models/StudentModel.php';
            $studentModel = new StudentModel();
            $profile = $studentModel->getStudentByEmail($email, true);
            if ($profile) {
                $user['first_name'] = $profile['first_name'];
                $user['last_name'] = $profile['last_name'];
                $user['onboarding_completed'] = !empty($profile['lrn']);
            }
        }

        if ($user) {
            if ($user['role'] !== $role) {
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized role']);
                exit();
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'] ?? '';
            $_SESSION['last_name'] = $user['last_name'] ?? '';
            $_SESSION['onboarding_completed'] = $user['onboarding_completed'] ?? false;

            if ($user['role'] === 'admin') {
                $redirect = 'views/admin/dashboard.php';
            } else {
                $redirect = ($user['onboarding_completed']) ? 'views/user/dashboard.php' : 'views/user/onboarding.php';
            }
            echo json_encode(['status' => 'success', 'redirect' => $redirect]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
        }
    } catch (Exception $e) {
        error_log("Login Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred']);
    }
    exit();
}

