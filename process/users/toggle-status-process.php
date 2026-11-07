<?php
/**
 * Procedural Toggle User Status Handler (Administrator Tier)
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

if ($user_id <= 0) {
    set_flash_message('danger', 'Invalid user account.');
    redirect(APP_URL . '/admin/users/index.php');
}

if ($user_id === (int)$_SESSION['user_id']) {
    set_flash_message('danger', 'You cannot deactivate your own active administrative account.');
    redirect(APP_URL . '/admin/users/index.php');
}

$conn = db_connect();

// Fetch current status
$stmt = mysqli_prepare($conn, "SELECT username, status FROM users WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$user) {
    set_flash_message('danger', 'User account not found.');
    redirect(APP_URL . '/admin/users/index.php');
}

$new_status = ($user['status'] === 'Active') ? 'Inactive' : 'Active';

$update_stmt = mysqli_prepare($conn, "UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?");
mysqli_stmt_bind_param($update_stmt, "si", $new_status, $user_id);
$success = mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

if ($success) {
    $action_verb = ($new_status === 'Active') ? 'activated' : 'deactivated';
    log_activity($conn, strtoupper($new_status), 'USERS', $user_id, "User {$user['username']} status changed to {$new_status}");
    set_flash_message('success', "User account for {$user['username']} has been {$action_verb}.");
} else {
    set_flash_message('danger', 'Failed to update user account status.');
}

redirect(APP_URL . '/admin/users/index.php');
