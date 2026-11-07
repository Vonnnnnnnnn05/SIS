<?php
/**
 * Procedural Delete Academic Term Handler (Administrator Tier)
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant
 */

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/logger.php';

require_role('Administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/admin/academic-years/index.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash_message('danger', 'Invalid security token.');
    redirect(APP_URL . '/admin/academic-years/index.php');
}

$semester_id = (int)($_POST['semester_id'] ?? 0);

if ($semester_id <= 0) {
    set_flash_message('danger', 'Invalid academic term specified.');
    redirect(APP_URL . '/admin/academic-years/index.php');
}

$conn = db_connect();

$stmt = mysqli_prepare($conn, "SELECT sem.semester_id, sem.semester_term, sem.is_active, ay.year_code 
                               FROM semesters sem 
                               INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id 
                               WHERE sem.semester_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $semester_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$term = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$term) {
    set_flash_message('danger', 'Academic term record not found.');
    redirect(APP_URL . '/admin/academic-years/index.php');
}

if ($term['is_active']) {
    set_flash_message('danger', 'Cannot delete the currently active enrollment term. Please activate another term first.');
    redirect(APP_URL . '/admin/academic-years/index.php');
}

// Check if any student enrollments exist for this semester
$stmt_enr = mysqli_prepare($conn, "SELECT COUNT(*) FROM enrollments WHERE semester_id = ?");
mysqli_stmt_bind_param($stmt_enr, "i", $semester_id);
mysqli_stmt_execute($stmt_enr);
$enrolled_count = (int)mysqli_stmt_get_result($stmt_enr)->fetch_row()[0];
mysqli_stmt_close($stmt_enr);

if ($enrolled_count > 0) {
    set_flash_message('danger', "Cannot delete this term because {$enrolled_count} student enrollment record(s) are attached to it.");
    redirect(APP_URL . '/admin/academic-years/index.php');
}

$stmt_del = mysqli_prepare($conn, "DELETE FROM semesters WHERE semester_id = ?");
mysqli_stmt_bind_param($stmt_del, "i", $semester_id);

if (mysqli_stmt_execute($stmt_del)) {
    mysqli_stmt_close($stmt_del);

    log_activity($conn, 'DELETE', 'ACADEMIC_TERMS', $semester_id, "Deleted academic semester {$term['year_code']} - {$term['semester_term']}");
    set_flash_message('success', "Academic term '{$term['year_code']} - {$term['semester_term']}' deleted successfully.");
} else {
    $err = mysqli_stmt_error($stmt_del);
    mysqli_stmt_close($stmt_del);
    set_flash_message('danger', 'Failed to delete academic term: ' . $err);
}

redirect(APP_URL . '/admin/academic-years/index.php');
