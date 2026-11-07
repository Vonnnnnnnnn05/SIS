<?php
/**
 * Procedural Update Student Profile Handler
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
$program_id = (int)($_POST['program_id'] ?? 0);
$first_name = sanitize($_POST['first_name'] ?? '');
$middle_name = sanitize($_POST['middle_name'] ?? '');
$last_name = sanitize($_POST['last_name'] ?? '');
$dob = sanitize($_POST['date_of_birth'] ?? '');
$gender = sanitize($_POST['gender'] ?? '');
$civil_status = sanitize($_POST['civil_status'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$contact = sanitize($_POST['contact_number'] ?? '');
$address = sanitize($_POST['home_address'] ?? '');
$em_name = sanitize($_POST['emergency_contact_name'] ?? '');
$em_phone = sanitize($_POST['emergency_contact_number'] ?? '');
$new_password = (string)($_POST['new_password'] ?? '');
$must_change_password = !empty($_POST['must_change_password']) ? 1 : 0;

if ($student_id <= 0 || empty($first_name) || empty($last_name) || empty($dob) || empty($email) || empty($contact) || $program_id <= 0) {
    set_flash_message('danger', 'Please complete all required fields.');
    redirect("{$redirect_base}/edit.php?id={$student_id}");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash_message('danger', 'Please provide a valid email address.');
    redirect("{$redirect_base}/edit.php?id={$student_id}");
}

if (!empty($new_password) && strlen($new_password) < 8) {
    set_flash_message('danger', 'New password must be at least 8 characters long.');
    redirect("{$redirect_base}/edit.php?id={$student_id}");
}

$conn = db_connect();

// Fetch current student to get existing user_id and student_number
$stmt_curr = mysqli_prepare($conn, "SELECT user_id, student_number FROM students WHERE student_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_curr, "i", $student_id);
mysqli_stmt_execute($stmt_curr);
$res_curr = mysqli_stmt_get_result($stmt_curr);
$curr_student = mysqli_fetch_assoc($res_curr);
mysqli_stmt_close($stmt_curr);

if (!$curr_student) {
    set_flash_message('danger', 'Student record not found.');
    redirect("{$redirect_base}/index.php");
}

$user_id = $curr_student['user_id'] ?? null;
$student_number = $curr_student['student_number'] ?? '';

// Check email uniqueness against other students
$stmt_check = mysqli_prepare($conn, "SELECT student_id FROM students WHERE email = ? AND student_id != ? LIMIT 1");
mysqli_stmt_bind_param($stmt_check, "si", $email, $student_id);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);
if (mysqli_stmt_num_rows($stmt_check) > 0) {
    mysqli_stmt_close($stmt_check);
    set_flash_message('danger', 'The email address "' . escape($email) . '" is already registered to another student.');
    redirect("{$redirect_base}/edit.php?id={$student_id}");
}
mysqli_stmt_close($stmt_check);

// Check email uniqueness against users table (excluding the student's linked user account)
if ($user_id) {
    $stmt_check_u = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_check_u, "si", $email, $user_id);
} else {
    $stmt_check_u = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_check_u, "s", $email);
}
mysqli_stmt_execute($stmt_check_u);
mysqli_stmt_store_result($stmt_check_u);
if (mysqli_stmt_num_rows($stmt_check_u) > 0) {
    mysqli_stmt_close($stmt_check_u);
    set_flash_message('danger', 'The email address "' . escape($email) . '" is already in use by another account.');
    redirect("{$redirect_base}/edit.php?id={$student_id}");
}
mysqli_stmt_close($stmt_check_u);

mysqli_begin_transaction($conn);
try {
    $sql = "UPDATE students 
            SET first_name = ?, middle_name = ?, last_name = ?, date_of_birth = ?, gender = ?, 
                civil_status = ?, email = ?, contact_number = ?, home_address = ?, 
                emergency_contact_name = ?, emergency_contact_number = ?, program_id = ?, updated_at = NOW() 
            WHERE student_id = ?";

    $stmt_up = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt_up, "sssssssssssii", $first_name, $middle_name, $last_name, $dob, $gender, $civil_status, $email, $contact, $address, $em_name, $em_phone, $program_id, $student_id);
    mysqli_stmt_execute($stmt_up);
    mysqli_stmt_close($stmt_up);

    // Synchronize users.email and optional password reset if student account exists
    if ($user_id) {
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_sync = mysqli_prepare($conn, "UPDATE users SET email = ?, password = ?, must_change_password = ?, updated_at = NOW() WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt_sync, "ssii", $email, $hashed, $must_change_password, $user_id);
        } else {
            $stmt_sync = mysqli_prepare($conn, "UPDATE users SET email = ?, updated_at = NOW() WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt_sync, "si", $email, $user_id);
        }
        mysqli_stmt_execute($stmt_sync);
        mysqli_stmt_close($stmt_sync);
    } elseif (!empty($new_password)) {
        // Auto-provision user account if not previously linked
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $sql_u = "INSERT INTO users (username, email, password, role_id, status, must_change_password, created_at) VALUES (?, ?, ?, 3, 'Active', ?, NOW())";
        $stmt_u = mysqli_prepare($conn, $sql_u);
        mysqli_stmt_bind_param($stmt_u, "sssi", $student_number, $email, $hashed, $must_change_password);
        mysqli_stmt_execute($stmt_u);
        $new_uid = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_u);

        $stmt_link = mysqli_prepare($conn, "UPDATE students SET user_id = ? WHERE student_id = ?");
        mysqli_stmt_bind_param($stmt_link, "ii", $new_uid, $student_id);
        mysqli_stmt_execute($stmt_link);
        mysqli_stmt_close($stmt_link);
    }

    mysqli_commit($conn);

    $log_note = !empty($new_password) ? "Updated profile and reset password for student {$student_number}" : "Updated profile for student {$student_number}";
    log_activity($conn, 'UPDATE', 'STUDENTS', $student_id, $log_note);
    set_flash_message('success', "Student profile for {$student_number} updated successfully." . (!empty($new_password) ? " Password was updated." : ""));
    redirect("{$redirect_base}/view.php?id={$student_id}");
} catch (Exception $e) {
    mysqli_rollback($conn);
    set_flash_message('danger', 'Failed to update student profile: ' . $e->getMessage());
    redirect("{$redirect_base}/edit.php?id={$student_id}");
}
