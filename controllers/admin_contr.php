<?php
// controllers/admin_contr.php
require_once __DIR__ . '/../models/StudentModel.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$studentModel = new StudentModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_student') {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $exam_date = $_POST['exam_date'] ?? '';

        if (empty($first_name) || empty($last_name) || empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
            exit();
        }

        $data = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'exam_date' => $exam_date,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $result = $studentModel->addStudent($data);

        if (isset($result['error'])) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add student: ' . $result['error']]);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Student added successfully']);
        }
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'fetch_students') {
        $students = $studentModel->getAllStudents();
        echo json_encode(['data' => $students]);
        exit();
    }
}
