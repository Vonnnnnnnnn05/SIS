<?php
/**
 * Procedural Create User Handler (Administrator Tier)
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant
 */

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/logger.php';

require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/admin/users/index.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash_message('danger', 'Invalid security token. Please try again.');
    redirect(APP_URL . '/admin/users/create.php');
}

$username = sanitize($_POST['username'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
$role_id = (int)($_POST['role_id'] ?? 0);
$status = sanitize($_POST['status'] ?? 'Active');
$must_change_password = !empty($_POST['must_change_password']) ? 1 : 0;

if (empty($username) || empty($email) || empty($password) || $role_id <= 0) {
    set_flash_message('danger', 'Please fill in all required fields.');
    redirect(APP_URL . '/admin/users/create.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash_message('danger', 'Please enter a valid email address.');
    redirect(APP_URL . '/admin/users/create.php');
}

if (strlen($password) < 8) {
    set_flash_message('danger', 'Password must be at least 8 characters long.');
    redirect(APP_URL . '/admin/users/create.php');
}

$conn = db_connect();

// Check uniqueness of username
$check_user_stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($check_user_stmt, "s", $username);
mysqli_stmt_execute($check_user_stmt);
mysqli_stmt_store_result($check_user_stmt);
if (mysqli_stmt_num_rows($check_user_stmt) > 0) {
    mysqli_stmt_close($check_user_stmt);
    set_flash_message('danger', 'The username "' . escape($username) . '" is already in use.');
    redirect(APP_URL . '/admin/users/create.php');
}
mysqli_stmt_close($check_user_stmt);

// Check uniqueness of email
$check_email_stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($check_email_stmt, "s", $email);
mysqli_stmt_execute($check_email_stmt);
mysqli_stmt_store_result($check_email_stmt);
if (mysqli_stmt_num_rows($check_email_stmt) > 0) {
    mysqli_stmt_close($check_email_stmt);
    set_flash_message('danger', 'The email address "' . escape($email) . '" is already registered.');
    redirect(APP_URL . '/admin/users/create.php');
}
mysqli_stmt_close($check_email_stmt);

// Hash password and insert
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$insert_sql = "INSERT INTO users (username, email, password, role_id, status, must_change_password, created_at) 
               VALUES (?, ?, ?, ?, ?, ?, NOW())";

$insert_stmt = mysqli_prepare($conn, $insert_sql);
mysqli_stmt_bind_param($insert_stmt, "sssisi", $username, $email, $password_hash, $role_id, $status, $must_change_password);
$success = mysqli_stmt_execute($insert_stmt);
$new_user_id = mysqli_insert_id($conn);
mysqli_stmt_close($insert_stmt);

if ($success) {
    log_activity($conn, 'CREATE', 'USERS', $new_user_id, "Created user account: {$username} ({$status})");
    set_flash_message('success', "User account for {$username} created successfully.");
    redirect(APP_URL . '/admin/users/index.php');
} else {
    set_flash_message('danger', 'Failed to create user account. Please try again.');
    redirect(APP_URL . '/admin/users/create.php');
}
