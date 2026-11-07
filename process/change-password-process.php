<?php
/**
 * Procedural Password Change Handler
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant
 */

require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/logger.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/student/change-password.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash_message('danger', 'Invalid security token. Please try again.');
    redirect(APP_URL . '/student/change-password.php');
}

$current_password = (string)($_POST['current_password'] ?? '');
$new_password = (string)($_POST['new_password'] ?? '');
$confirm_password = (string)($_POST['confirm_password'] ?? '');

// 2. Validate input lengths and match
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    set_flash_message('danger', 'All password fields are required.');
    redirect(APP_URL . '/student/change-password.php');
}

if (strlen($new_password) < 8) {
    set_flash_message('danger', 'The new password must be at least 8 characters in length.');
    redirect(APP_URL . '/student/change-password.php');
}

if ($new_password !== $confirm_password) {
    set_flash_message('danger', 'The new password and confirmation password do not match.');
    redirect(APP_URL . '/student/change-password.php');
}

$conn = db_connect();
$user_id = (int)$_SESSION['user_id'];

// 3. Query existing password hash using prepared statement
$stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$user || !password_verify($current_password, $user['password'])) {
    set_flash_message('danger', 'Your current password was entered incorrectly.');
    redirect(APP_URL . '/student/change-password.php');
}

// 4. Hash new password and update user record
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);
$update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ?, must_change_password = 0, updated_at = NOW() WHERE user_id = ?");
mysqli_stmt_bind_param($update_stmt, "si", $new_hash, $user_id);
$success = mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

if ($success) {
    $_SESSION['must_change_password'] = false;
    log_activity($conn, 'UPDATE', 'AUTH', $user_id, 'User changed password');
    set_flash_message('success', 'Your password has been successfully updated.');
    redirect(APP_URL . '/index.php');
} else {
    set_flash_message('danger', 'An error occurred while updating your password. Please try again.');
    redirect(APP_URL . '/student/change-password.php');
}
