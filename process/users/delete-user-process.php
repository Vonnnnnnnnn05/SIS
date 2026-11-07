<?php
/**
 * Procedural Delete User Account Handler (Administrator Tier)
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
$current_admin_id = (int)$_SESSION['user_id'];

if ($user_id <= 0) {
    set_flash_message('danger', 'Invalid user account specified.');
    redirect(APP_URL . '/admin/users/index.php');
}

if ($user_id === $current_admin_id) {
    set_flash_message('danger', 'You cannot delete your own currently logged-in account.');
    redirect(APP_URL . '/admin/users/index.php');
}

if ($user_id === 1) {
    set_flash_message('danger', 'The primary system administrator account cannot be deleted.');
    redirect(APP_URL . '/admin/users/index.php');
}

$conn = db_connect();

// Fetch target user details for logging
$stmt = mysqli_prepare($conn, "SELECT username, email FROM users WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$target_user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$target_user) {
    set_flash_message('danger', 'User account not found.');
    redirect(APP_URL . '/admin/users/index.php');
}

mysqli_begin_transaction($conn);
try {
    // 1. Unlink from students table if linked
    $stmt_st = mysqli_prepare($conn, "UPDATE students SET user_id = NULL WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt_st, "i", $user_id);
    mysqli_stmt_execute($stmt_st);
    mysqli_stmt_close($stmt_st);

    // 2. Reassign any enrollments created by this user to the current admin
    $stmt_enr = mysqli_prepare($conn, "UPDATE enrollments SET created_by = ? WHERE created_by = ?");
    mysqli_stmt_bind_param($stmt_enr, "ii", $current_admin_id, $user_id);
    mysqli_stmt_execute($stmt_enr);
    mysqli_stmt_close($stmt_enr);

    // 3. Delete user account
    $stmt_del = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt_del, "i", $user_id);
    mysqli_stmt_execute($stmt_del);
    mysqli_stmt_close($stmt_del);

    mysqli_commit($conn);

    log_activity($conn, 'DELETE', 'USERS', $user_id, "Deleted user account '{$target_user['username']}' ({$target_user['email']})");
    set_flash_message('success', "User account '{$target_user['username']}' deleted successfully.");
} catch (Exception $e) {
    mysqli_rollback($conn);
    set_flash_message('danger', 'Failed to delete user account: ' . $e->getMessage());
}

redirect(APP_URL . '/admin/users/index.php');
