<?php
/**
 * Update Own Profile Handler (Student Tier)
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Strict IDOR Protection - Zero Emojis
 */

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/logger.php';

require_role('Student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('danger', 'Invalid request method.');
    redirect(APP_URL . '/student/profile.php');
}

// Verify CSRF Token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    set_flash_message('danger', 'Session security token expired or invalid. Please try again.');
    redirect(APP_URL . '/student/profile-edit.php');
}

$conn = db_connect();
$user = current_user();

// Strict IDOR Protection: Always bind session student ID, ignore any user-supplied ID
$student_id = (int)($_SESSION['student_id'] ?? 0);
if ($student_id <= 0) {
    $lookup_stmt = mysqli_prepare($conn, "SELECT student_id, student_number FROM students WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($lookup_stmt, "i", $user['user_id']);
    mysqli_stmt_execute($lookup_stmt);
    $st_res = mysqli_stmt_get_result($lookup_stmt);
    if ($st_row = mysqli_fetch_assoc($st_res)) {
        $student_id = (int)$st_row['student_id'];
        $_SESSION['student_id'] = $student_id;
    }
    mysqli_stmt_close($lookup_stmt);
}

if ($student_id <= 0) {
    set_flash_message('danger', 'Unauthorized or unlinked student profile.');
    redirect(APP_URL . '/student/dashboard.php');
}

// Sanitize permitted input fields
$contact_number = sanitize($_POST['contact_number'] ?? '');
$home_address = sanitize($_POST['home_address'] ?? '');
$emergency_contact_name = sanitize($_POST['emergency_contact_name'] ?? '');
$emergency_contact_number = sanitize($_POST['emergency_contact_number'] ?? '');

if (empty($contact_number) || empty($home_address) || empty($emergency_contact_name) || empty($emergency_contact_number)) {
    set_flash_message('danger', 'All contact and emergency fields are required.');
    redirect(APP_URL . '/student/profile-edit.php');
}

// Fetch student number for audit logging
$st_stmt = mysqli_prepare($conn, "SELECT student_number FROM students WHERE student_id = ?");
mysqli_stmt_bind_param($st_stmt, "i", $student_id);
mysqli_stmt_execute($st_stmt);
$st_res = mysqli_stmt_get_result($st_stmt);
$student = mysqli_fetch_assoc($st_res);
mysqli_stmt_close($st_stmt);

$student_number = $student['student_number'] ?? 'Unknown';

// Update student record (contact fields only)
$sql = "UPDATE students 
        SET contact_number = ?, 
            home_address = ?, 
            emergency_contact_name = ?, 
            emergency_contact_number = ?, 
            updated_at = NOW() 
        WHERE student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssssi", $contact_number, $home_address, $emergency_contact_name, $emergency_contact_number, $student_id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    // Audit Logging
    log_activity($conn, 'UPDATE', 'STUDENTS', (string)$student_id, "Student {$student_number} updated own contact and emergency information");

    set_flash_message('success', 'Your contact details have been successfully updated.');
    redirect(APP_URL . '/student/profile.php');
} else {
    $err = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    set_flash_message('danger', 'Failed to update profile: ' . escape($err));
    redirect(APP_URL . '/student/profile-edit.php');
}
