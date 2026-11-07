<?php
/**
 * Procedural Edit User Handler (Administrator Tier)
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
    set_flash_message('danger', 'Invalid security token.');
    redirect(APP_URL . '/admin/users/index.php');
}

$user_id = (int)($_POST['user_id'] ?? 0);
$email = sanitize($_POST['email'] ?? '');
$role_id = (int)($_POST['role_id'] ?? 0);
$status = sanitize($_POST['status'] ?? 'Active');
$new_password = (string)($_POST['new_password'] ?? '');
$must_change_password = !empty($_POST['must_change_password']) ? 1 : 0;

if ($user_id <= 0 || empty($email) || $role_id <= 0) {
    set_flash_message('danger', 'Please provide all required fields.');
    redirect(APP_URL . "/admin/users/edit.php?id={$user_id}");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash_message('danger', 'Invalid email address provided.');
    redirect(APP_URL . "/admin/users/edit.php?id={$user_id}");
}

$conn = db_connect();

// Check email uniqueness against other users
$stmt_check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1");
mysqli_stmt_bind_param($stmt_check, "si", $email, $user_id);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);
if (mysqli_stmt_num_rows($stmt_check) > 0) {
    mysqli_stmt_close($stmt_check);
    set_flash_message('danger', 'Email address is already in use by another account.');
    redirect(APP_URL . "/admin/users/edit.php?id={$user_id}");
}
mysqli_stmt_close($stmt_check);

mysqli_begin_transaction($conn);
try {
    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            throw new Exception("New password must be at least 8 characters long.");
        }
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET email = ?, role_id = ?, status = ?, password = ?, must_change_password = ?, updated_at = NOW() WHERE user_id = ?";
        $stmt_up = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt_up, "sissii", $email, $role_id, $status, $hash, $must_change_password, $user_id);
    } else {
        $update_sql = "UPDATE users SET email = ?, role_id = ?, status = ?, must_change_password = ?, updated_at = NOW() WHERE user_id = ?";
        $stmt_up = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt_up, "sisii", $email, $role_id, $status, $must_change_password, $user_id);
    }

    mysqli_stmt_execute($stmt_up);
    mysqli_stmt_close($stmt_up);

    // Synchronize students.email if user is linked to a student profile (Recommendation D1)
    $stmt_sync = mysqli_prepare($conn, "UPDATE students SET email = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt_sync, "si", $email, $user_id);
    mysqli_stmt_execute($stmt_sync);
    mysqli_stmt_close($stmt_sync);

    mysqli_commit($conn);

    log_activity($conn, 'UPDATE', 'USERS', $user_id, "Updated user account ID {$user_id} ({$email}, Role: {$role_id}, Status: {$status})");
    set_flash_message('success', 'User account updated successfully.');
    redirect(APP_URL . '/admin/users/index.php');
} catch (Exception $e) {
    mysqli_rollback($conn);
    set_flash_message('danger', 'Error updating account: ' . $e->getMessage());
    redirect(APP_URL . "/admin/users/edit.php?id={$user_id}");
}
