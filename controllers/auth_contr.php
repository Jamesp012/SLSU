<?php
// controllers/auth_contr.php
require_once __DIR__ . '/../models/UserModel.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'student';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields']);
        exit();
    }

    // TEST CREDENTIALS for login testing
    $test_admin = [
        'email' => 'admin@slsu.edu.ph',
        'password' => 'admin123'
    ];

    $test_student = [
        'email' => 'student@example.com',
        'password' => 'student123'
    ];

    if ($role === 'admin') {
        if ($email === $test_admin['email'] && $password === $test_admin['password']) {
            $_SESSION['user_id'] = 'admin_test_001';
            $_SESSION['user_role'] = 'admin';
            $_SESSION['email'] = $email;
            $_SESSION['name'] = 'System Administrator';
            echo json_encode(['status' => 'success', 'redirect' => 'views/admin/dashboard.php']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid admin credentials']);
        }
    } else {
        if ($email === $test_student['email'] && $password === $test_student['password']) {
            $_SESSION['user_id'] = 'student_test_001';
            $_SESSION['user_role'] = 'student';
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = 'John';
            $_SESSION['last_name'] = 'Doe';
            echo json_encode(['status' => 'success', 'redirect' => 'views/user/dashboard.php']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid student credentials']);
        }
    }
    exit();
}
