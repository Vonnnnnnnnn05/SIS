<?php
/**
 * Procedural Delete Student Record Handler
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
$redirect_base = ($current_auth['role_name'] === 'Administrator') ? (APP_URL . '/admin/students') : (APP_URL . '/registrar/students');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($redirect_base . '/index.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash_message('danger', 'Invalid security token.');
    redirect($redirect_base . '/index.php');
}

$student_id = (int)($_POST['student_id'] ?? 0);

if ($student_id <= 0) {
    set_flash_message('danger', 'Invalid student record specified.');
    redirect($redirect_base . '/index.php');
}

$conn = db_connect();

// Fetch student details
$stmt = mysqli_prepare($conn, "SELECT student_id, student_number, user_id, first_name, last_name FROM students WHERE student_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$student) {
    set_flash_message('danger', 'Student record not found.');
    redirect($redirect_base . '/index.php');
}

mysqli_begin_transaction($conn);
try {
    // 1. Delete associated term enrollments
    $stmt_enr = mysqli_prepare($conn, "DELETE FROM enrollments WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt_enr, "i", $student_id);
    mysqli_stmt_execute($stmt_enr);
    mysqli_stmt_close($stmt_enr);

    // 2. Delete student record
    $stmt_del = mysqli_prepare($conn, "DELETE FROM students WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt_del, "i", $student_id);
    mysqli_stmt_execute($stmt_del);
    mysqli_stmt_close($stmt_del);

    // 3. Delete student user account if linked and role is Student
    if (!empty($student['user_id'])) {
        $stmt_u = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ? AND role_id = 3");
        mysqli_stmt_bind_param($stmt_u, "i", $student['user_id']);
        mysqli_stmt_execute($stmt_u);
        mysqli_stmt_close($stmt_u);
    }

    mysqli_commit($conn);

    log_activity($conn, 'DELETE', 'STUDENTS', $student_id, "Permanently deleted student {$student['student_number']} ({$student['first_name']} {$student['last_name']}) and associated enrollments");
    set_flash_message('success', "Student {$student['student_number']} ({$student['first_name']} {$student['last_name']}) deleted successfully.");
} catch (Exception $e) {
    mysqli_rollback($conn);
    set_flash_message('danger', 'Failed to delete student record: ' . $e->getMessage());
}

redirect($redirect_base . '/index.php');
