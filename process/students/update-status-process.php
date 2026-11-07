<?php
/**
 * Procedural Update Student Status Handler
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
    redirect(APP_URL . '/registrar/students/index.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash_message('danger', 'Invalid security token.');
    redirect(APP_URL . '/registrar/students/index.php');
}

$student_id = (int)($_POST['student_id'] ?? 0);
$status = sanitize($_POST['current_status'] ?? '');

$allowed_statuses = ['Active', 'Inactive', 'Graduated', 'Withdrawn'];
if ($student_id <= 0 || !in_array($status, $allowed_statuses, true)) {
    set_flash_message('danger', 'Invalid student status value.');
    redirect(APP_URL . '/registrar/students/index.php');
}

$conn = db_connect();

// Fetch student
$stmt = mysqli_prepare($conn, "SELECT student_number, user_id FROM students WHERE student_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$student) {
    set_flash_message('danger', 'Student record not found.');
    redirect(APP_URL . '/registrar/students/index.php');
}

mysqli_begin_transaction($conn);
try {
    // 1. Update students table
    $stmt_up = mysqli_prepare($conn, "UPDATE students SET current_status = ?, updated_at = NOW() WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt_up, "si", $status, $student_id);
    mysqli_stmt_execute($stmt_up);
    mysqli_stmt_close($stmt_up);

    // 2. Align user account active/inactive state
    if (!empty($student['user_id'])) {
        $account_status = ($status === 'Active') ? 'Active' : 'Inactive';
        $stmt_user = mysqli_prepare($conn, "UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt_user, "si", $account_status, $student['user_id']);
        mysqli_stmt_execute($stmt_user);
        mysqli_stmt_close($stmt_user);
    }

    mysqli_commit($conn);

    log_activity($conn, 'UPDATE', 'STUDENTS', $student_id, "Changed status of {$student['student_number']} to {$status}");
    set_flash_message('success', "Student status updated to {$status}.");
} catch (Exception $e) {
    mysqli_rollback($conn);
    set_flash_message('danger', 'Failed to update student status.');
}

redirect(APP_URL . "/registrar/students/view.php?id={$student_id}");
