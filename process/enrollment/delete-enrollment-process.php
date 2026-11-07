<?php
/**
 * Procedural Delete Term Enrollment Handler
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/registrar/enrollment/index.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash_message('danger', 'Invalid security token.');
    redirect(APP_URL . '/registrar/enrollment/index.php');
}

$enrollment_id = (int)($_POST['enrollment_id'] ?? 0);

if ($enrollment_id <= 0) {
    set_flash_message('danger', 'Invalid enrollment record specified.');
    redirect(APP_URL . '/registrar/enrollment/index.php');
}

$conn = db_connect();

// Fetch enrollment details for audit log
$stmt = mysqli_prepare($conn, "SELECT e.enrollment_id, s.student_number, sem.semester_term, ay.year_code 
                               FROM enrollments e 
                               INNER JOIN students s ON e.student_id = s.student_id 
                               INNER JOIN semesters sem ON e.semester_id = sem.semester_id 
                               INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id 
                               WHERE e.enrollment_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $enrollment_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$enrollment = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$enrollment) {
    set_flash_message('danger', 'Enrollment record not found.');
    redirect(APP_URL . '/registrar/enrollment/index.php');
}

$stmt_del = mysqli_prepare($conn, "DELETE FROM enrollments WHERE enrollment_id = ?");
mysqli_stmt_bind_param($stmt_del, "i", $enrollment_id);

if (mysqli_stmt_execute($stmt_del)) {
    mysqli_stmt_close($stmt_del);

    log_activity($conn, 'DELETE', 'ENROLLMENT', $enrollment_id, "Deleted enrollment ID {$enrollment_id} for student {$enrollment['student_number']} ({$enrollment['year_code']} - {$enrollment['semester_term']})");
    set_flash_message('success', "Enrollment record for student {$enrollment['student_number']} deleted successfully.");
} else {
    $err = mysqli_stmt_error($stmt_del);
    mysqli_stmt_close($stmt_del);
    set_flash_message('danger', 'Failed to delete enrollment record: ' . $err);
}

redirect(APP_URL . '/registrar/enrollment/index.php');
