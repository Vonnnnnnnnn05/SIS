<?php
/**
 * Term Enrollment Request Handler
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
    redirect(APP_URL . '/registrar/enrollment/enroll.php');
}

$conn = db_connect();
$user = current_user();

// Sanitize inputs
$student_id = isset($_POST['student_id']) && is_numeric($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$semester_id = isset($_POST['semester_id']) && is_numeric($_POST['semester_id']) ? (int)$_POST['semester_id'] : 0;
$program_id = isset($_POST['program_id']) && is_numeric($_POST['program_id']) ? (int)$_POST['program_id'] : 0;
$year_level = sanitize($_POST['year_level'] ?? '');
$enrollment_status = sanitize($_POST['enrollment_status'] ?? 'Enrolled');
$remarks = sanitize($_POST['remarks'] ?? '');

$valid_year_levels = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
$valid_statuses = ['Pending', 'Enrolled'];

// Validation
if ($student_id <= 0 || $semester_id <= 0 || $program_id <= 0) {
    set_flash_message('danger', 'Please complete all required fields.');
    redirect(APP_URL . '/registrar/enrollment/enroll.php?student_id=' . $student_id);
}

if (!in_array($year_level, $valid_year_levels, true)) {
    set_flash_message('danger', 'Please select a valid year level.');
    redirect(APP_URL . '/registrar/enrollment/enroll.php?student_id=' . $student_id);
}

if (!in_array($enrollment_status, $valid_statuses, true)) {
    set_flash_message('danger', 'Invalid enrollment status.');
    redirect(APP_URL . '/registrar/enrollment/enroll.php?student_id=' . $student_id);
}

// 1. Verify student exists and is active
$student_stmt = mysqli_prepare($conn, "SELECT student_id, student_number, first_name, last_name, current_status FROM students WHERE student_id = ?");
mysqli_stmt_bind_param($student_stmt, "i", $student_id);
mysqli_stmt_execute($student_stmt);
$st_res = mysqli_stmt_get_result($student_stmt);
$student = mysqli_fetch_assoc($st_res);
mysqli_stmt_close($student_stmt);

if (!$student) {
    set_flash_message('danger', 'Student record not found.');
    redirect(APP_URL . '/registrar/enrollment/enroll.php');
}

if ($student['current_status'] !== 'Active') {
    set_flash_message('danger', 'Cannot enroll student with standing: ' . escape($student['current_status']) . '. Only active students may be enrolled.');
    redirect(APP_URL . '/registrar/enrollment/enroll.php?student_id=' . $student_id);
}

// 2. Verify semester exists and is active
$sem_stmt = mysqli_prepare($conn, "SELECT sem.semester_id, sem.semester_term, sem.is_active, ay.year_code 
                                   FROM semesters sem 
                                   INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id 
                                   WHERE sem.semester_id = ?");
mysqli_stmt_bind_param($sem_stmt, "i", $semester_id);
mysqli_stmt_execute($sem_stmt);
$sem_res = mysqli_stmt_get_result($sem_stmt);
$semester = mysqli_fetch_assoc($sem_res);
mysqli_stmt_close($sem_stmt);

if (!$semester) {
    set_flash_message('danger', 'Academic semester record not found.');
    redirect(APP_URL . '/registrar/enrollment/enroll.php?student_id=' . $student_id);
}

if ((int)$semester['is_active'] !== 1) {
    set_flash_message('danger', 'Enrollment is closed for this semester. Only active semesters accept new enrollments.');
    redirect(APP_URL . '/registrar/enrollment/enroll.php?student_id=' . $student_id);
}

// 3. Verify program exists
$prog_stmt = mysqli_prepare($conn, "SELECT program_id, program_code FROM programs WHERE program_id = ?");
mysqli_stmt_bind_param($prog_stmt, "i", $program_id);
mysqli_stmt_execute($prog_stmt);
$prog_res = mysqli_stmt_get_result($prog_stmt);
$program = mysqli_fetch_assoc($prog_res);
mysqli_stmt_close($prog_stmt);

if (!$program) {
    set_flash_message('danger', 'Degree program not found.');
    redirect(APP_URL . '/registrar/enrollment/enroll.php?student_id=' . $student_id);
}

// 4. Check for duplicate term enrollment (uq_student_semester)
$dup_stmt = mysqli_prepare($conn, "SELECT enrollment_id FROM enrollments WHERE student_id = ? AND semester_id = ?");
mysqli_stmt_bind_param($dup_stmt, "ii", $student_id, $semester_id);
mysqli_stmt_execute($dup_stmt);
$dup_res = mysqli_stmt_get_result($dup_stmt);
if (mysqli_num_rows($dup_res) > 0) {
    mysqli_stmt_close($dup_stmt);
    set_flash_message('danger', 'Student ' . escape($student['student_number']) . ' is already registered/enrolled for AY ' . escape($semester['year_code']) . ' - ' . escape($semester['semester_term']) . '. Duplicate enrollments for the same semester are prohibited.');
    redirect(APP_URL . '/registrar/enrollment/enroll.php?student_id=' . $student_id);
}
mysqli_stmt_close($dup_stmt);

// 5. Insert enrollment record
$insert_sql = "INSERT INTO enrollments (student_id, semester_id, program_id, year_level, enrollment_date, enrollment_status, remarks, created_by) 
               VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?)";
$insert_stmt = mysqli_prepare($conn, $insert_sql);
$created_by = (int)$user['user_id'];
mysqli_stmt_bind_param($insert_stmt, "iiisssi", $student_id, $semester_id, $program_id, $year_level, $enrollment_status, $remarks, $created_by);

if (mysqli_stmt_execute($insert_stmt)) {
    $enrollment_id = mysqli_insert_id($conn);
    mysqli_stmt_close($insert_stmt);

    // Audit Logging
    $details = sprintf(
        "Enrolled student %s (%s, %s) into %s, %s (AY %s - %s) with status '%s'",
        $student['student_number'],
        $student['last_name'],
        $student['first_name'],
        $program['program_code'],
        $year_level,
        $semester['year_code'],
        $semester['semester_term'],
        $enrollment_status
    );
    log_activity($conn, 'CREATE', 'ENROLLMENT', (string)$enrollment_id, $details);

    set_flash_message('success', 'Student ' . escape($student['student_number']) . ' successfully enrolled for AY ' . escape($semester['year_code']) . ' - ' . escape($semester['semester_term']) . '.');
    redirect(APP_URL . '/registrar/enrollment/index.php');
} else {
    $err = mysqli_error($conn);
    mysqli_stmt_close($insert_stmt);
    set_flash_message('danger', 'Failed to save enrollment: ' . escape($err));
    redirect(APP_URL . '/registrar/enrollment/enroll.php?student_id=' . $student_id);
}
