<?php
/**
 * Streaming CSV Export Handler
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

$conn = db_connect();
$type = sanitize($_GET['type'] ?? 'students');
$timestamp = date('Ymd_His');

if ($type === 'students') {
    // Search & Filter parameters
    $search = sanitize($_GET['q'] ?? '');
    $program_id = isset($_GET['program_id']) && is_numeric($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
    $status = sanitize($_GET['status'] ?? '');

    $where = ["1=1"];
    $types = "";
    $params = [];

    if (!empty($search)) {
        $where[] = "(s.student_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?)";
        $types .= "ssss";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if ($program_id > 0) {
        $where[] = "s.program_id = ?";
        $types .= "i";
        $params[] = $program_id;
    }

    if (!empty($status) && in_array($status, ['Active', 'Inactive', 'Graduated', 'Withdrawn'], true)) {
        $where[] = "s.current_status = ?";
        $types .= "s";
        $params[] = $status;
    }

    $where_sql = implode(" AND ", $where);

    $sql = "SELECT s.student_number, s.last_name, s.first_name, s.middle_name,
                   p.program_code, p.program_name,
                   s.current_status, s.email, s.contact_number, s.created_at
            FROM students s
            LEFT JOIN programs p ON s.program_id = p.program_id
            WHERE " . $where_sql . "
            ORDER BY s.last_name ASC, s.first_name ASC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $filename = "sis_students_report_{$timestamp}.csv";

    // Set CSV download headers
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // Output UTF-8 BOM for Excel compatibility
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

    // Headers
    fputcsv($out, [
        'Student Number',
        'Last Name',
        'First Name',
        'Middle Name',
        'Program Code',
        'Program Name',
        'Status',
        'Email Address',
        'Contact Number',
        'Registration Date'
    ]);

    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($out, [
            $row['student_number'],
            $row['last_name'],
            $row['first_name'],
            $row['middle_name'] ?? '',
            $row['program_code'] ?? 'N/A',
            $row['program_name'] ?? 'N/A',
            $row['current_status'],
            $row['email'],
            $row['contact_number'],
            format_date($row['created_at'])
        ]);
    }

    fclose($out);
    mysqli_stmt_close($stmt);

    log_activity($conn, 'EXPORT', 'REPORTS', null, "Exported student roster CSV ({$filename})");
    exit;

} elseif ($type === 'enrollments') {
    // Search & Filter parameters
    $search = sanitize($_GET['q'] ?? '');
    $semester_id = isset($_GET['semester_id']) && is_numeric($_GET['semester_id']) ? (int)$_GET['semester_id'] : 0;
    $program_id = isset($_GET['program_id']) && is_numeric($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
    $status = sanitize($_GET['status'] ?? '');

    $where = ["1=1"];
    $types = "";
    $params = [];

    if (!empty($search)) {
        $where[] = "(s.student_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
        $types .= "sss";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if ($semester_id > 0) {
        $where[] = "e.semester_id = ?";
        $types .= "i";
        $params[] = $semester_id;
    }

    if ($program_id > 0) {
        $where[] = "e.program_id = ?";
        $types .= "i";
        $params[] = $program_id;
    }

    if (!empty($status) && in_array($status, ['Pending', 'Enrolled', 'Dropped', 'Completed'], true)) {
        $where[] = "e.enrollment_status = ?";
        $types .= "s";
        $params[] = $status;
    }

    $where_sql = implode(" AND ", $where);

    $sql = "SELECT s.student_number, s.last_name, s.first_name, s.middle_name,
                   p.program_code, p.program_name,
                   e.year_level, e.enrollment_date, e.enrollment_status, e.remarks,
                   ay.year_code, sem.semester_term,
                   u.username as enrolled_by
            FROM enrollments e
            INNER JOIN students s ON e.student_id = s.student_id
            INNER JOIN semesters sem ON e.semester_id = sem.semester_id
            INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id
            INNER JOIN programs p ON e.program_id = p.program_id
            LEFT JOIN users u ON e.created_by = u.user_id
            WHERE " . $where_sql . "
            ORDER BY ay.start_year DESC, sem.semester_term ASC, s.last_name ASC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $filename = "sis_enrollments_report_{$timestamp}.csv";

    // Set CSV download headers
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // Output UTF-8 BOM
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

    // Headers
    fputcsv($out, [
        'Student Number',
        'Last Name',
        'First Name',
        'Program Code',
        'Year Level',
        'Academic Year',
        'Semester Term',
        'Enrollment Date',
        'Status',
        'Remarks',
        'Enrolled By'
    ]);

    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($out, [
            $row['student_number'],
            $row['last_name'],
            $row['first_name'] . ($row['middle_name'] ? ' ' . substr($row['middle_name'], 0, 1) . '.' : ''),
            $row['program_code'],
            $row['year_level'],
            $row['year_code'],
            $row['semester_term'],
            format_date($row['enrollment_date']),
            $row['enrollment_status'],
            $row['remarks'] ?? '',
            $row['enrolled_by'] ?? 'System'
        ]);
    }

    fclose($out);
    mysqli_stmt_close($stmt);

    log_activity($conn, 'EXPORT', 'REPORTS', null, "Exported term enrollment CSV ({$filename})");
    exit;

} else {
    set_flash_message('danger', 'Unknown report export type.');
    redirect(APP_URL . '/index.php');
}
