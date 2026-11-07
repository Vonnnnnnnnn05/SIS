<?php
/**
 * Update Enrollment Status Request Handler
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/logger.php';

require_role(['Administrator', 'Registrar']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('danger', 'Invalid request method.');
    redirect(APP_URL . '/registrar/enrollment/index.php');
}

// Verify CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    set_flash_message('danger', 'Session security token expired or invalid. Please try again.');
    redirect(APP_URL . '/registrar/enrollment/index.php');
}

$conn = db_connect();
$user = current_user();

$enrollment_id = isset($_POST['enrollment_id']) && is_numeric($_POST['enrollment_id']) ? (int)$_POST['enrollment_id'] : 0;
$enrollment_status = sanitize($_POST['enrollment_status'] ?? '');
$remarks = sanitize($_POST['remarks'] ?? '');

$valid_statuses = ['Pending', 'Enrolled', 'Dropped', 'Completed'];

if ($enrollment_id <= 0 || !in_array($enrollment_status, $valid_statuses, true)) {
    set_flash_message('danger', 'Invalid input parameters for enrollment status update.');
    redirect(APP_URL . '/registrar/enrollment/index.php');
}

// Fetch existing enrollment record
$check_sql = "SELECT e.enrollment_id, e.enrollment_status, s.student_number, sem.semester_term, ay.year_code 
              FROM enrollments e
              INNER JOIN students s ON e.student_id = s.student_id
              INNER JOIN semesters sem ON e.semester_id = sem.semester_id
              INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id
              WHERE e.enrollment_id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "i", $enrollment_id);
mysqli_stmt_execute($check_stmt);
$res = mysqli_stmt_get_result($check_stmt);
$existing = mysqli_fetch_assoc($res);
mysqli_stmt_close($check_stmt);

if (!$existing) {
    set_flash_message('danger', 'Enrollment record not found.');
    redirect(APP_URL . '/registrar/enrollment/index.php');
}

$old_status = $existing['enrollment_status'];

// Update status
$update_sql = "UPDATE enrollments SET enrollment_status = ?, remarks = ?, updated_at = NOW() WHERE enrollment_id = ?";
$update_stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($update_stmt, "ssi", $enrollment_status, $remarks, $enrollment_id);

if (mysqli_stmt_execute($update_stmt)) {
    mysqli_stmt_close($update_stmt);

    // Audit Logging
    $details = sprintf(
        "Updated enrollment #%d for student %s (AY %s - %s) status from '%s' to '%s'. Remarks: %s",
        $enrollment_id,
        $existing['student_number'],
        $existing['year_code'],
        $existing['semester_term'],
        $old_status,
        $enrollment_status,
        $remarks ? $remarks : 'None'
    );
    log_activity($conn, 'UPDATE', 'ENROLLMENT', (string)$enrollment_id, $details);

    set_flash_message('success', 'Enrollment status updated to ' . escape($enrollment_status) . ' successfully.');
    redirect(APP_URL . '/registrar/enrollment/index.php');
} else {
    $err = mysqli_error($conn);
    mysqli_stmt_close($update_stmt);
    set_flash_message('danger', 'Failed to update enrollment: ' . escape($err));
    redirect(APP_URL . '/registrar/enrollment/edit-status.php?id=' . $enrollment_id);
}
