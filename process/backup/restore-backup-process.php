<?php
/**
 * Database Restore & Recovery Handler (Administrator Tier)
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Double Confirmation Security
 */

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/logger.php';

require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('danger', 'Invalid request method.');
    redirect(APP_URL . '/admin/backup/index.php');
}

// Verify CSRF Token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    set_flash_message('danger', 'Session security token expired or invalid.');
    redirect(APP_URL . '/admin/backup/index.php');
}

$conn = db_connect();
$user = current_user();

// 1. Double-confirmation: Verify administrator password
$admin_password = $_POST['admin_password'] ?? '';
if (empty($admin_password)) {
    set_flash_message('danger', 'Administrator password confirmation is required to execute a database restoration.');
    redirect(APP_URL . '/admin/backup/index.php');
}

$stmt_admin = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_admin, "i", $user['user_id']);
mysqli_stmt_execute($stmt_admin);
$res_admin = mysqli_stmt_get_result($stmt_admin);
$admin_row = mysqli_fetch_assoc($res_admin);
mysqli_stmt_close($stmt_admin);

if (!$admin_row || !password_verify($admin_password, $admin_row['password'])) {
    set_flash_message('danger', 'Password confirmation failed. Database restoration aborted.');
    redirect(APP_URL . '/admin/backup/index.php');
}

// 2. Identify SQL source: uploaded file OR existing archive
$sql_content = '';
$restore_source_name = '';

if (!empty($_FILES['backup_file']['name'])) {
    $file = $_FILES['backup_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        set_flash_message('danger', 'File upload failed with error code ' . $file['error']);
        redirect(APP_URL . '/admin/backup/index.php');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'sql') {
        set_flash_message('danger', 'Invalid file type. Only .sql backup files may be restored.');
        redirect(APP_URL . '/admin/backup/index.php');
    }

    if ($file['size'] > 50 * 1024 * 1024) { // 50MB maximum
        set_flash_message('danger', 'Uploaded file exceeds the 50MB restoration limit.');
        redirect(APP_URL . '/admin/backup/index.php');
    }

    $sql_content = file_get_contents($file['tmp_name']);
    $restore_source_name = basename($file['name']);

} elseif (!empty($_POST['backup_id'])) {
    $backup_id = (int)$_POST['backup_id'];
    $stmt_b = mysqli_prepare($conn, "SELECT filename FROM backup_logs WHERE backup_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_b, "i", $backup_id);
    mysqli_stmt_execute($stmt_b);
    $res_b = mysqli_stmt_get_result($stmt_b);
    $backup_row = mysqli_fetch_assoc($res_b);
    mysqli_stmt_close($stmt_b);

    if (!$backup_row) {
        set_flash_message('danger', 'Selected backup archive record not found.');
        redirect(APP_URL . '/admin/backup/index.php');
    }

    $filepath = BACKUP_PATH . '/' . basename($backup_row['filename']);
    if (!file_exists($filepath)) {
        set_flash_message('danger', 'Backup archive file does not exist on disk.');
        redirect(APP_URL . '/admin/backup/index.php');
    }

    $sql_content = file_get_contents($filepath);
    $restore_source_name = basename($backup_row['filename']);
} else {
    set_flash_message('danger', 'Please choose a backup file from the history list or upload a .sql file.');
    redirect(APP_URL . '/admin/backup/index.php');
}

if (empty(trim($sql_content))) {
    set_flash_message('danger', 'The selected backup file is empty.');
    redirect(APP_URL . '/admin/backup/index.php');
}

// 3. Execute multi-query restoration
if (mysqli_multi_query($conn, $sql_content)) {
    $has_error = false;
    $error_msg = '';

    do {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }

        if (mysqli_errno($conn)) {
            $has_error = true;
            $error_msg = mysqli_error($conn);
            break;
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));

    if ($has_error) {
        set_flash_message('danger', 'Database restore encountered an error: ' . escape($error_msg));
        redirect(APP_URL . '/admin/backup/index.php');
    }

    // Re-connect to ensure fresh session / connection state after table rewrites
    $conn2 = db_connect();
    log_activity($conn2, 'RESTORE', 'DATABASE', null, "Restored database schema and data from {$restore_source_name}");
    mysqli_close($conn2);

    set_flash_message('success', "Database successfully restored from archive: {$restore_source_name}. All tables and records have been synchronized.");
    redirect(APP_URL . '/admin/backup/index.php');
} else {
    set_flash_message('danger', 'Failed to execute database restoration: ' . escape(mysqli_error($conn)));
    redirect(APP_URL . '/admin/backup/index.php');
}
