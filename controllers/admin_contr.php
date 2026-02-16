<?php
// controllers/admin_contr.php
require_once __DIR__ . '/../models/StudentModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/STEMQuestionModel.php';
require_once __DIR__ . '/../models/AchievementQuestionModel.php';
require_once __DIR__ . '/../helpers/EmailHelper.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$studentModel = new StudentModel();
$userModel = new UserModel();
$questionModel = new STEMQuestionModel();
$achievementQuestionModel = new AchievementQuestionModel();

function generateTempPassword($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $email = $_SESSION['email'];

        if (empty($current) || empty($new) || empty($confirm)) {
            echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
            exit();
        }

        if ($new !== $confirm) {
            echo json_encode(['status' => 'error', 'message' => 'New passwords do not match']);
            exit();
        }

        $user = $userModel->login($email, $current, true);
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Incorrect current password']);
            exit();
        }

        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $metadata = is_string($user['metadata']) ? json_decode($user['metadata'], true) : ($user['metadata'] ?? []);
        $metadata['password'] = $hashed;

        $result = $userModel->updateUser($user['id'], ['metadata' => $metadata], true);

        if (isset($result['error'])) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update password: ' . $result['error']]);
        } else {
            supabaseAuthRequest('PUT', 'user', ['password' => $new]);
            echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
        }
        exit();
    }

    if ($action === 'add_admin') {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
            exit();
        }

        // Check if user already exists
        $existingUser = $userModel->getUserByEmail($email, true);
        if ($existingUser) {
            echo json_encode(['status' => 'error', 'message' => 'A user with this email already exists']);
            exit();
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 1. Create User in Supabase Auth
        $authData = [
            'email' => $email,
            'password' => $password,
            'email_confirm' => true,
            'user_metadata' => [
                'first_name' => $first_name,
                'last_name' => $last_name
            ]
        ];

        $authResult = supabaseAuthRequest('POST', 'admin/users', $authData);
        $userId = $authResult['id'] ?? null;

        if (!$userId && isset($authResult['error'])) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create Auth account: ' . ($authResult['details']['msg'] ?? $authResult['error'])]);
            exit();
        }

        // 2. Create Admin Profile
        $profileData = [
            'id' => $userId,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'role' => 'admin',
            'metadata' => [
                'password' => $hashed_password
            ],
            'created_at' => date('Y-m-d H:i:s')
        ];

        $profileResult = $userModel->addUser($profileData);

        if (isset($profileResult['error'])) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create admin profile: ' . $profileResult['error']]);
            exit();
        }

        echo json_encode(['status' => 'success', 'message' => 'New admin account created successfully']);
        exit();
    }

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

    if ($action === 'add_question') {
        $data = [
            'pathway_id' => (int)$_POST['pathway_id'],
            'question_number' => (int)$_POST['question_number'],
            'question_text' => $_POST['question_text']
        ];
        $result = $questionModel->addQuestion($data);
        if (isset($result['error'])) {
            echo json_encode(['status' => 'error', 'message' => $result['error']]);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Question added successfully']);
        }
        exit();
    }

    if ($action === 'update_question') {
        $id = $_POST['question_id'];
        $data = [
            'pathway_id' => (int)$_POST['pathway_id'],
            'question_number' => (int)$_POST['question_number'],
            'question_text' => $_POST['question_text']
        ];
        $result = $questionModel->updateQuestion($id, $data);
        if (isset($result['error'])) {
            echo json_encode(['status' => 'error', 'message' => $result['error']]);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Question updated successfully']);
        }
        exit();
    }

    if ($action === 'delete_question') {
        $id = $_POST['id'];
        $result = $questionModel->deleteQuestion($id);
        if (isset($result['error'])) {
            echo json_encode(['status' => 'error', 'message' => $result['error']]);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Question deleted successfully']);
        }
        exit();
    }

    if ($action === 'add_achievement_question') {
        $data = [
            'question_number' => (int)$_POST['question_number'],
            'category' => $_POST['category'],
            'question_text' => $_POST['question_text'],
            'option_a' => $_POST['option_a'],
            'option_b' => $_POST['option_b'],
            'option_c' => $_POST['option_c'],
            'option_d' => $_POST['option_d'],
            'correct_answer' => $_POST['correct_answer']
        ];
        $result = $achievementQuestionModel->addQuestion($data);
        if (isset($result['error'])) {
            echo json_encode(['status' => 'error', 'message' => $result['error']]);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Scholastic Ability question added successfully']);
        }
        exit();
    }

    if ($action === 'update_achievement_question') {
        $id = $_POST['question_id'];
        $data = [
            'question_number' => (int)$_POST['question_number'],
            'category' => $_POST['category'],
            'question_text' => $_POST['question_text'],
            'option_a' => $_POST['option_a'],
            'option_b' => $_POST['option_b'],
            'option_c' => $_POST['option_c'],
            'option_d' => $_POST['option_d'],
            'correct_answer' => $_POST['correct_answer']
        ];
        $result = $achievementQuestionModel->updateQuestion($id, $data);
        if (isset($result['error'])) {
            echo json_encode(['status' => 'error', 'message' => $result['error']]);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Scholastic Ability question updated successfully']);
        }
        exit();
    }

    if ($action === 'delete_achievement_question') {
        $id = $_POST['id'];
        $result = $achievementQuestionModel->deleteQuestion($id);
        if (isset($result['error'])) {
            echo json_encode(['status' => 'error', 'message' => $result['error']]);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Scholastic Ability question deleted successfully']);
        }
        exit();
    }

    if ($action === 'delete_student') {
        $id = $_POST['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'Student ID is required']);
            exit();
        }

        try {
            // 1. Delete scores first (to avoid constraint issues if not cascading)
            require_once __DIR__ . '/../models/AchievementScoreModel.php';
            require_once __DIR__ . '/../models/STEMScoreModel.php';
            
            $achievementScoreModel = new AchievementScoreModel();
            $stemScoreModel = new STEMScoreModel();
            
            $achievementScoreModel->deleteScore($id);
            $stemScoreModel->deleteScore($id);
            $stemScoreModel->deletePathwayStanines($id);

            // 2. Delete from Supabase Auth
            // Using the GoTrue Admin API to delete the user
            $authRes = supabaseAuthRequest('DELETE', 'admin/users/' . $id);
            
            // 3. Delete profile
            $result = $studentModel->deleteStudent($id);
            
            if (isset($result['error'])) {
                // If profile deletion failed, it might be due to existing constraints we missed
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete student profile: ' . $result['error']]);
            } else {
                echo json_encode(['status' => 'success', 'message' => 'Student and all related data deleted successfully']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred during deletion: ' . $e->getMessage()]);
        }
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

    if ($action === 'fetch_questions') {
        $pathway_id = $_GET['pathway_id'] ?? null;
        if ($pathway_id) {
            $questions = $questionModel->getQuestionsByPathway($pathway_id);
        } else {
            $questions = $questionModel->getAllQuestions();
        }
        echo json_encode(['data' => $questions]);
        exit();
    }

    if ($action === 'fetch_achievement_questions') {
        $category = $_GET['category'] ?? null;
        if ($category) {
            $questions = $achievementQuestionModel->getQuestionsByCategory($category);
        } else {
            $questions = $achievementQuestionModel->getAllQuestions();
        }
        echo json_encode(['data' => $questions]);
        exit();
    }
}
