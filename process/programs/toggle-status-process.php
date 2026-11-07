<?php
/**
 * Procedural Toggle Course / Program Status Handler
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant
 */

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/logger.php';

require_role(['Administrator', 'Registrar']);

$current_auth = current_user();
$redirect_base = ($current_auth['role_name'] === 'Administrator') ? (APP_URL . '/admin/programs') : (APP_URL . '/registrar/programs');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($redirect_base . '/index.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash_message('danger', 'Invalid security token.');
    redirect($redirect_base . '/index.php');
}

$program_id = (int)($_POST['program_id'] ?? 0);
if ($program_id <= 0) {
    set_flash_message('danger', 'Invalid course / program identifier.');
    redirect($redirect_base . '/index.php');
}

$conn = db_connect();

$stmt = mysqli_prepare($conn, "SELECT program_id, program_code, status FROM programs WHERE program_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $program_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$program = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$program) {
    set_flash_message('danger', 'Course / Program record not found.');
    redirect($redirect_base . '/index.php');
}

$new_status = ($program['status'] === 'Active') ? 'Inactive' : 'Active';

$stmt_up = mysqli_prepare($conn, "UPDATE programs SET status = ?, updated_at = NOW() WHERE program_id = ?");
mysqli_stmt_bind_param($stmt_up, "si", $new_status, $program_id);

if (mysqli_stmt_execute($stmt_up)) {
    mysqli_stmt_close($stmt_up);

    log_activity($conn, 'UPDATE', 'PROGRAMS', $program_id, "Toggled status of course / program {$program['program_code']} to {$new_status}");
    set_flash_message('success', "Course / Program '{$program['program_code']}' is now {$new_status}.");
} else {
    $err = mysqli_stmt_error($stmt_up);
    mysqli_stmt_close($stmt_up);
    set_flash_message('danger', 'Failed to update program status: ' . $err);
}

redirect($redirect_base . '/index.php');
