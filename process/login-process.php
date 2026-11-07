<?php
/**
 * Procedural Login Processing Script
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant
 */

require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/login.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash_message('danger', 'Invalid security token. Please try submitting the form again.');
    redirect(APP_URL . '/login.php');
}

$username = sanitize($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    set_flash_message('danger', 'Please enter both your username and password.');
    redirect(APP_URL . '/login.php');
}

$conn = db_connect();

// 2. Query user by username or email using procedural prepared statement
$sql = "SELECT u.user_id, u.username, u.email, u.password, u.role_id, r.role_name, u.status, u.must_change_password 
        FROM users u 
        INNER JOIN roles r ON u.role_id = r.role_id 
        WHERE u.username = ? OR u.email = ? 
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    error_log("Login Query Prepare Error: " . mysqli_error($conn));
    set_flash_message('danger', 'A database error occurred. Please try again later.');
    redirect(APP_URL . '/login.php');
}

mysqli_stmt_bind_param($stmt, "ss", $username, $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// 3. Verify existence and password hash
if (!$user || !password_verify($password, $user['password'])) {
    set_flash_message('danger', 'Invalid credentials. Please verify your username and password.');
    redirect(APP_URL . '/login.php');
}

// 4. Verify account active status
if ($user['status'] !== 'Active') {
    set_flash_message('danger', 'Your account has been deactivated. Please contact an administrator.');
    redirect(APP_URL . '/login.php');
}

// 5. Successful authentication: Regenerate session to prevent fixation
session_regenerate_id(true);

$_SESSION['user_id'] = (int)$user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['role_id'] = (int)$user['role_id'];
$_SESSION['role_name'] = $user['role_name'];
$_SESSION['must_change_password'] = (bool)$user['must_change_password'];
$_SESSION['last_activity'] = time();

// If student role, retrieve student profile IDs
if ($user['role_name'] === 'Student') {
    $stmt_student = mysqli_prepare($conn, "SELECT student_id, student_number FROM students WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_student, "i", $user['user_id']);
    mysqli_stmt_execute($stmt_student);
    $res_student = mysqli_stmt_get_result($stmt_student);
    $student = mysqli_fetch_assoc($res_student);
    mysqli_stmt_close($stmt_student);

    if ($student) {
        $_SESSION['student_id'] = (int)$student['student_id'];
        $_SESSION['student_number'] = $student['student_number'];
    }
}

// 6. Update last login timestamp
$update_stmt = mysqli_prepare($conn, "UPDATE users SET last_login_at = NOW() WHERE user_id = ?");
mysqli_stmt_bind_param($update_stmt, "i", $user['user_id']);
mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

// 7. Audit log activity
log_activity($conn, 'LOGIN', 'AUTH', $user['user_id'], 'User logged in successfully');

// 8. Role-based redirect
if ($user['role_name'] === 'Administrator') {
    redirect(APP_URL . '/admin/dashboard.php');
} elseif ($user['role_name'] === 'Registrar') {
    redirect(APP_URL . '/registrar/dashboard.php');
} elseif ($user['role_name'] === 'Student') {
    if (!empty($user['must_change_password'])) {
        redirect(APP_URL . '/student/change-password.php');
    } else {
        redirect(APP_URL . '/student/dashboard.php');
    }
} else {
    redirect(APP_URL . '/index.php');
}
