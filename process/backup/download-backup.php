<?php
/**
 * Secure Backup Download Handler (Administrator Tier)
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

$conn = db_connect();

$backup_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($backup_id <= 0) {
    set_flash_message('danger', 'Invalid backup identifier.');
    redirect(APP_URL . '/admin/backup/index.php');
}

// Verify backup record in database
$stmt = mysqli_prepare($conn, "SELECT filename FROM backup_logs WHERE backup_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $backup_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$backup = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$backup) {
    set_flash_message('danger', 'Backup record not found.');
    redirect(APP_URL . '/admin/backup/index.php');
}

// Path traversal defense
$safe_filename = basename($backup['filename']);
$filepath = BACKUP_PATH . '/' . $safe_filename;

if (!file_exists($filepath) || !is_readable($filepath)) {
    set_flash_message('danger', 'The requested backup file does not exist on disk or cannot be read.');
    redirect(APP_URL . '/admin/backup/index.php');
}

// Audit logging
log_activity($conn, 'EXPORT', 'DATABASE', (string)$backup_id, "Downloaded database backup archive: {$safe_filename}");

// Stream download
header('Content-Description: File Transfer');
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

// Flush output buffers before reading file
if (ob_get_level()) {
    ob_end_clean();
}
readfile($filepath);
exit;
