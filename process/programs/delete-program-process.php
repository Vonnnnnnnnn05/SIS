<?php
/**
 * Procedural Delete Course / Program Handler
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

$stmt = mysqli_prepare($conn, "SELECT program_id, program_code, program_name FROM programs WHERE program_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $program_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$program = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$program) {
    set_flash_message('danger', 'Course / Program record not found.');
    redirect($redirect_base . '/index.php');
}

// 1. Check for registered students under this program
$stmt_st = mysqli_prepare($conn, "SELECT COUNT(*) FROM students WHERE program_id = ?");
mysqli_stmt_bind_param($stmt_st, "i", $program_id);
mysqli_stmt_execute($stmt_st);
$student_count = (int)mysqli_stmt_get_result($stmt_st)->fetch_row()[0];
mysqli_stmt_close($stmt_st);

if ($student_count > 0) {
    set_flash_message('danger', "Cannot delete '{$program['program_code']}' because {$student_count} student(s) are registered under it. Consider deactivating the program instead.");
    redirect($redirect_base . '/index.php');
}

// 2. Check for historical enrollments under this program
$stmt_enr = mysqli_prepare($conn, "SELECT COUNT(*) FROM enrollments WHERE program_id = ?");
mysqli_stmt_bind_param($stmt_enr, "i", $program_id);
mysqli_stmt_execute($stmt_enr);
$enroll_count = (int)mysqli_stmt_get_result($stmt_enr)->fetch_row()[0];
mysqli_stmt_close($stmt_enr);

if ($enroll_count > 0) {
    set_flash_message('danger', "Cannot delete '{$program['program_code']}' because {$enroll_count} historical enrollment record(s) reference it. Consider deactivating the program instead.");
    redirect($redirect_base . '/index.php');
}

// 3. Delete program
$stmt_del = mysqli_prepare($conn, "DELETE FROM programs WHERE program_id = ?");
mysqli_stmt_bind_param($stmt_del, "i", $program_id);

if (mysqli_stmt_execute($stmt_del)) {
    mysqli_stmt_close($stmt_del);

    log_activity($conn, 'DELETE', 'PROGRAMS', $program_id, "Permanently deleted course / program {$program['program_code']} - {$program['program_name']}");
    set_flash_message('success', "Course / Program '{$program['program_code']}' was permanently deleted.");
} else {
    $err = mysqli_stmt_error($stmt_del);
    mysqli_stmt_close($stmt_del);
    set_flash_message('danger', 'Failed to delete program: ' . $err);
}

redirect($redirect_base . '/index.php');
