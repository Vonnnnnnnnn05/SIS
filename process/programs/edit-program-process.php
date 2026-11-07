<?php
/**
 * Procedural Edit Course / Program Handler
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
$redirect_base = ($current_auth['role_name'] === 'Administrator') ? (APP_URL . '/admin/programs') : (APP_URL . '/registrar/programs');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($redirect_base . '/index.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash_message('danger', 'Invalid security token. Please try again.');
    redirect($redirect_base . '/index.php');
}

$program_id = (int)($_POST['program_id'] ?? 0);
$program_code = strtoupper(trim(sanitize($_POST['program_code'] ?? '')));
$program_name = trim(sanitize($_POST['program_name'] ?? ''));
$department = trim(sanitize($_POST['department'] ?? ''));
$status = sanitize($_POST['status'] ?? 'Active');

if (!in_array($status, ['Active', 'Inactive'], true)) {
    $status = 'Active';
}

if ($program_id <= 0 || empty($program_code) || empty($program_name) || empty($department)) {
    set_flash_message('danger', 'Please complete all required fields.');
    redirect("{$redirect_base}/edit.php?id={$program_id}");
}

if (strlen($program_code) > 20) {
    set_flash_message('danger', 'Course code must not exceed 20 characters.');
    redirect("{$redirect_base}/edit.php?id={$program_id}");
}

$conn = db_connect();

// Check uniqueness of program_code excluding current program_id
$stmt_check = mysqli_prepare($conn, "SELECT program_id FROM programs WHERE program_code = ? AND program_id != ? LIMIT 1");
mysqli_stmt_bind_param($stmt_check, "si", $program_code, $program_id);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);
if (mysqli_stmt_num_rows($stmt_check) > 0) {
    mysqli_stmt_close($stmt_check);
    set_flash_message('danger', 'Course / Program code "' . escape($program_code) . '" is already in use by another program.');
    redirect("{$redirect_base}/edit.php?id={$program_id}");
}
mysqli_stmt_close($stmt_check);

// Update program
$stmt_up = mysqli_prepare($conn, "UPDATE programs SET program_code = ?, program_name = ?, department = ?, status = ?, updated_at = NOW() WHERE program_id = ?");
mysqli_stmt_bind_param($stmt_up, "ssssi", $program_code, $program_name, $department, $status, $program_id);

if (mysqli_stmt_execute($stmt_up)) {
    mysqli_stmt_close($stmt_up);

    log_activity($conn, 'UPDATE', 'PROGRAMS', $program_id, "Updated course / program {$program_code} - {$program_name} (Status: {$status})");
    set_flash_message('success', "Course / Program '{$program_code}' updated successfully.");
    redirect($redirect_base . '/index.php');
} else {
    $err = mysqli_stmt_error($stmt_up);
    mysqli_stmt_close($stmt_up);
    set_flash_message('danger', 'Failed to update program: ' . $err);
    redirect("{$redirect_base}/edit.php?id={$program_id}");
}
