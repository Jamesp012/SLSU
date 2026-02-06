<?php
// controllers/admin_contr.php
require_once __DIR__ . '/../models/StudentModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/EmailHelper.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$studentModel = new StudentModel();
$userModel = new UserModel();

function generateTempPassword($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_student') {
        $first_name = $_POST['first_name'] ?? '';
        $middle_name = $_POST['middle_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';

        if (empty($first_name) || empty($last_name) || empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'First Name, Last Name, and Email are required']);
            exit();
        }

        // Check if user already exists
        $existingUser = $userModel->getUserByEmail($email, true);
        if ($existingUser) {
            echo json_encode(['status' => 'error', 'message' => 'A user with this email already exists']);
            exit();
        }

        $temp_password = generateTempPassword();
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

        // 1. Create User in Supabase Auth (to get a valid UUID)
        // Using the admin/users endpoint requires the service_role key
        $authData = [
            'email' => $email,
            'password' => $temp_password,
            'email_confirm' => true,
            'user_metadata' => [
                'first_name' => $first_name,
                'last_name' => $last_name
            ]
        ];

        $authResult = supabaseAuthRequest('POST', 'admin/users', $authData);

        $userId = null;
        if (isset($authResult['error'])) {
            $msg = $authResult['details']['msg'] ?? $authResult['details']['message'] ?? $authResult['error'] ?? 'Unknown Auth Error';
            
            // If user already exists in Auth but not in our profiles table, we try to find them
            if (stripos($msg, 'already registered') !== false || stripos($msg, 'already exists') !== false || (isset($authResult['details']['error_code']) && $authResult['details']['error_code'] === 'email_exists')) {
                $allUsers = supabaseAuthRequest('GET', 'admin/users');
                if (!isset($allUsers['error']) && (is_array($allUsers) || isset($allUsers['users']))) {
                    // Supabase returns users in a 'users' key or as a direct array
                    $usersList = $allUsers['users'] ?? (is_array($allUsers) ? $allUsers : []);
                    foreach ($usersList as $u) {
                        if (isset($u['email']) && strtolower($u['email']) === strtolower($email)) {
                            $userId = $u['id'];
                            break;
                        }
                    }
                } else {
                    error_log("Failed to fetch users list for recovery: " . json_encode($allUsers));
                }
            }
            
            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create Auth account: ' . $msg]);
                exit();
            }
        } else {
            $userId = $authResult['id'] ?? null;
        }

        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to obtain User ID from Auth']);
            exit();
        }

        // 2. Create Student Profile in public.profiles
        // We store the hashed password in metadata since the profiles table lacks a password column
        $profileData = [
            'id' => $userId,
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'email' => $email,
            'role' => 'student',
            'metadata' => [
                'password' => $hashed_password
            ],
            'created_at' => date('Y-m-d H:i:s')
        ];

        $profileResult = $studentModel->addStudent($profileData);

        if (isset($profileResult['error'])) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create student profile: ' . $profileResult['error']]);
            exit();
        }

        // 3. Send Email using PHPMailer
        $mailSent = EmailHelper::sendTempPassword($email, $first_name, $temp_password);

        echo json_encode([
            'status' => 'success', 
            'message' => 'Student added successfully. Temporary password: ' . $temp_password . ($mailSent ? ' (Email sent via PHPMailer)' : ' (Email failed to send - check server config)')
        ]);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'fetch_students') {
        $students = $studentModel->getAllStudents(true);
        echo json_encode(['data' => $students]);
        exit();
    }
}
