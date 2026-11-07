<?php
/**
 * Procedural Student Registration & User Provisioning Handler
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

// 1. Verify CSRF Token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash_message('danger', 'Invalid security token. Please try again.');
    redirect(APP_URL . '/registrar/students/register.php');
}

// 2. Extract and sanitize inputs
$student_number = sanitize($_POST['student_number'] ?? '');
$program_id = (int)($_POST['program_id'] ?? 0);
$first_name = sanitize($_POST['first_name'] ?? '');
$middle_name = sanitize($_POST['middle_name'] ?? '');
$last_name = sanitize($_POST['last_name'] ?? '');
$dob = sanitize($_POST['date_of_birth'] ?? '');
$gender = sanitize($_POST['gender'] ?? '');
$civil_status = sanitize($_POST['civil_status'] ?? 'Single');
$email = sanitize($_POST['email'] ?? '');
$contact = sanitize($_POST['contact_number'] ?? '');
$address = sanitize($_POST['home_address'] ?? '');
$em_name = sanitize($_POST['emergency_contact_name'] ?? '');
$em_phone = sanitize($_POST['emergency_contact_number'] ?? '');

// 3. Mandatory field validation
if (empty($student_number) || empty($first_name) || empty($last_name) || empty($dob) || 
    empty($gender) || empty($email) || empty($contact) || empty($address) || 
    empty($em_name) || empty($em_phone) || $program_id <= 0) {
    set_flash_message('danger', 'Please complete all required fields marked with an asterisk (*).');
    redirect(APP_URL . '/registrar/students/register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash_message('danger', 'Please provide a valid email address.');
    redirect(APP_URL . '/registrar/students/register.php');
}

$conn = db_connect();

// 4. Duplicate checks (T038)
// Duplicate Student Number
$stmt_check_id = mysqli_prepare($conn, "SELECT student_id FROM students WHERE student_number = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_check_id, "s", $student_number);
mysqli_stmt_execute($stmt_check_id);
mysqli_stmt_store_result($stmt_check_id);
if (mysqli_stmt_num_rows($stmt_check_id) > 0) {
    mysqli_stmt_close($stmt_check_id);
    set_flash_message('danger', "Student number '{$student_number}' already exists in the system.");
    redirect(APP_URL . '/registrar/students/register.php');
}
mysqli_stmt_close($stmt_check_id);

// Duplicate Username in users table
$stmt_check_u = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_check_u, "s", $student_number);
mysqli_stmt_execute($stmt_check_u);
mysqli_stmt_store_result($stmt_check_u);
if (mysqli_stmt_num_rows($stmt_check_u) > 0) {
    mysqli_stmt_close($stmt_check_u);
    set_flash_message('danger', "User account handle '{$student_number}' already exists.");
    redirect(APP_URL . '/registrar/students/register.php');
}
mysqli_stmt_close($stmt_check_u);

// Duplicate Email
$stmt_check_em = mysqli_prepare($conn, "SELECT email FROM users WHERE email = ? UNION SELECT email FROM students WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_check_em, "ss", $email, $email);
mysqli_stmt_execute($stmt_check_em);
mysqli_stmt_store_result($stmt_check_em);
if (mysqli_stmt_num_rows($stmt_check_em) > 0) {
    mysqli_stmt_close($stmt_check_em);
    set_flash_message('danger', "Email address '{$email}' is already registered.");
    redirect(APP_URL . '/registrar/students/register.php');
}
mysqli_stmt_close($stmt_check_em);

// 5. Database Transaction: provision user account and student record
mysqli_begin_transaction($conn);
try {
    // A. Create user account
    $birth_year = date('Y', strtotime($dob));
    $default_password = 'Student@' . date('Y');
    $password_hash = password_hash($default_password, PASSWORD_DEFAULT);

    $sql_user = "INSERT INTO users (username, email, password, role_id, status, must_change_password, created_at) 
                 VALUES (?, ?, ?, 3, 'Active', 1, NOW())";
    $stmt_user = mysqli_prepare($conn, $sql_user);
    mysqli_stmt_bind_param($stmt_user, "sss", $student_number, $email, $password_hash);
    mysqli_stmt_execute($stmt_user);
    $new_user_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_user);

    // B. Create student record
    $sql_student = "INSERT INTO students (
                        student_number, user_id, first_name, middle_name, last_name, 
                        date_of_birth, gender, civil_status, email, contact_number, 
                        home_address, emergency_contact_name, emergency_contact_number, 
                        program_id, current_status, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', NOW()
                    )";

    $stmt_student = mysqli_prepare($conn, $sql_student);
    mysqli_stmt_bind_param($stmt_student, "sisssssssssssi", 
        $student_number, $new_user_id, $first_name, $middle_name, $last_name, 
        $dob, $gender, $civil_status, $email, $contact, 
        $address, $em_name, $em_phone, $program_id
    );
    mysqli_stmt_execute($stmt_student);
    $new_student_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_student);

    mysqli_commit($conn);

    log_activity($conn, 'CREATE', 'STUDENTS', $new_student_id, "Registered new student {$student_number} ({$last_name}, {$first_name})");
    set_flash_message('success', "Student {$student_number} successfully registered! Student portal account provisioned.");
    redirect(APP_URL . "/registrar/students/view.php?id={$new_student_id}");

} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Student Registration Error: " . $e->getMessage());
    set_flash_message('danger', 'Registration failed due to a database error: ' . $e->getMessage());
    redirect(APP_URL . '/registrar/students/register.php');
}
