<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Clear current_page if it's not a direct file access or if we want to be more specific
$active_dashboard = ($current_page == 'dashboard.php') ? 'active' : '';
$active_profile = ($current_page == 'profile.php') ? 'active' : '';
$active_students = ($current_page == 'students.php') ? 'active' : '';
$active_questions = ($current_page == 'questions.php') ? 'active' : '';
$active_achievement = ($current_page == 'achievement_questions.php') ? 'active' : '';
$active_reports = ($current_page == 'reports.php') ? 'active' : '';
$active_settings = ($current_page == 'settings.php') ? 'active' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' ? 'Admin' : 'Student'; ?> - Entrance Exam Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #184226;
            --sidebar-hover: #1e5330;
            --primary-green: #184226;
            --accent-orange: #f0b508;
            --light-green: #2d7a46;
        }
        body {
            background-color: #f4f7f5;
            font-family: 'Inter', -apple-system, sans-serif;
            overflow-x: hidden;
        }
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background-color: var(--sidebar-bg);
            color: white;
            transition: all 0.3s ease;
            z-index: 1000;
            border-right: 3px solid var(--accent-orange);
        }
        .sidebar.collapsed {
            left: calc(-1 * var(--sidebar-width)) !important;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
        }
        .main-content.expanded {
            margin-left: 0 !important;
            width: 100% !important;
        }
        .nav-link {
            color: #d1d5db !important;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            margin: 0.2rem 1rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }
        .nav-link:hover {
            background-color: var(--sidebar-hover);
            color: var(--accent-orange) !important;
        }
        .nav-link.active {
            background-color: var(--accent-orange);
            color: var(--sidebar-bg) !important;
            font-weight: 600;
        }
        .navbar-top {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 0.75rem 1.5rem;
            border-bottom: 2px solid #eee;
        }
        #sidebarToggle {
            cursor: pointer;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: var(--primary-green);
            transition: all 0.2s;
        }
        #sidebarToggle:hover {
            background: #f1f5f9;
            color: var(--accent-orange);
            border-color: var(--accent-orange);
        }
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }
        .btn-primary {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }
        .btn-primary:hover {
            background-color: var(--light-green);
            border-color: var(--light-green);
        }
        .text-primary {
            color: var(--primary-green) !important;
        }
        .bg-primary {
            background-color: var(--primary-green) !important;
        }
        .btn-outline-primary {
            color: var(--primary-green);
            border-color: var(--primary-green);
        }
        .btn-outline-primary:hover {
            background-color: var(--primary-green);
            color: white;
        }
        .badge.bg-info {
            background-color: var(--accent-orange) !important;
            color: #184226 !important;
        }
        .bg-primary-subtle {
            background-color: rgba(24, 66, 38, 0.1) !important;
        }
        .text-info {
            color: var(--primary-green) !important;
        }
        .bg-info-subtle {
            background-color: rgba(240, 181, 8, 0.1) !important;
        }
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            backdrop-filter: blur(2px);
        }
        .sidebar-backdrop.show {
            display: block;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }
        @media (max-width: 991.98px) {
            .container-fluid {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            .navbar-top {
                padding: 0.5rem 1rem;
            }
            .sidebar {
                left: calc(-1 * var(--sidebar-width));
            }
            .sidebar.show {
                left: 0;
            }
            .main-content {
                margin-left: 0;
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-bold"><i class="fas fa-university me-2"></i>SLSU Portal</h5>
            <div class="d-md-none" id="mobileCloseSidebar" style="cursor: pointer;">
                <i class="fas fa-times"></i>
            </div>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $active_dashboard; ?>" href="dashboard.php">
                    <i class="fas fa-th-large me-3"></i> Dashboard
                </a>
            </li>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_students; ?>" href="students.php">
                    <i class="fas fa-users me-3"></i> Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_questions; ?>" href="questions.php">
                    <i class="fas fa-brain me-3"></i> Interest-Based Assessment
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_achievement; ?>" href="achievement_questions.php">
                    <i class="fas fa-list-check me-3"></i> Scholastic Ability Test
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_reports; ?>" href="reports.php">
                    <i class="fas fa-chart-line me-3"></i> Reports
                </a>
            </li>
            <?php else: ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_profile; ?>" href="profile.php">
                    <i class="fas fa-user-circle me-3"></i> My Profile
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_settings; ?>" href="settings.php">
                    <i class="fas fa-cog me-3"></i> Settings
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link text-danger mt-4" href="../../logout.php">
                    <i class="fas fa-sign-out-alt me-3"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content wrapper -->
    <div class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <nav class="navbar navbar-top">
            <div class="container-fluid d-flex align-items-center">
                <div id="sidebarToggle" class="me-3">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="ms-auto d-flex align-items-center">
                    <span class="text-muted me-3 d-none d-md-inline">Welcome, <?php echo $_SESSION['first_name'] ?? 'User'; ?></span>
                    <i class="fas fa-user-circle fa-lg text-secondary"></i>
                </div>
            </div>
        </nav>
        
        <div class="container-fluid py-4 px-3 px-md-4">
