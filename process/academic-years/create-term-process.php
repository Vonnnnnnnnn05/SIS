<?php
/**
 * Procedural Academic Term Processing Handler
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

$action = sanitize($_POST['action'] ?? '');
$conn = db_connect();

// 1. Action: Set Active Semester Term
if ($action === 'set_active') {
    $semester_id = (int)($_POST['semester_id'] ?? 0);
    if ($semester_id <= 0) {
        set_flash_message('danger', 'Invalid semester specified.');
        redirect(APP_URL . '/admin/academic-years/index.php');
    }

    mysqli_begin_transaction($conn);
    try {
        // Deactivate all semesters
        mysqli_query($conn, "UPDATE semesters SET is_active = 0");

        // Activate specified semester
        $stmt_act = mysqli_prepare($conn, "UPDATE semesters SET is_active = 1 WHERE semester_id = ?");
        mysqli_stmt_bind_param($stmt_act, "i", $semester_id);
        mysqli_stmt_execute($stmt_act);
        mysqli_stmt_close($stmt_act);

        // Find linked ay_id
        $stmt_ay = mysqli_prepare($conn, "SELECT ay_id, semester_term FROM semesters WHERE semester_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_ay, "i", $semester_id);
        mysqli_stmt_execute($stmt_ay);
        $res_ay = mysqli_stmt_get_result($stmt_ay);
        $sem_row = mysqli_fetch_assoc($res_ay);
        mysqli_stmt_close($stmt_ay);

        if ($sem_row) {
            mysqli_query($conn, "UPDATE academic_years SET is_current = 0");
            $stmt_cur = mysqli_prepare($conn, "UPDATE academic_years SET is_current = 1 WHERE ay_id = ?");
            mysqli_stmt_bind_param($stmt_cur, "i", $sem_row['ay_id']);
            mysqli_stmt_execute($stmt_cur);
            mysqli_stmt_close($stmt_cur);
        }

        mysqli_commit($conn);

        log_activity($conn, 'UPDATE', 'TERMS', $semester_id, "Set semester ID {$semester_id} ({$sem_row['semester_term']}) as active enrollment term");
        set_flash_message('success', "Academic semester {$sem_row['semester_term']} activated successfully.");
    } catch (Exception $e) {
        mysqli_rollback($conn);
        set_flash_message('danger', 'Failed to activate semester term: ' . $e->getMessage());
    }

    redirect(APP_URL . '/admin/academic-years/index.php');
}

// 2. Action: Create Academic Term
if ($action === 'create_term') {
    $ay_id = (int)($_POST['ay_id'] ?? 0);
    $new_start = (int)($_POST['new_start_year'] ?? 0);
    $new_end = (int)($_POST['new_end_year'] ?? 0);
    $semester_term = sanitize($_POST['semester_term'] ?? '');
    $set_active = !empty($_POST['set_active']) ? 1 : 0;
    $start_date = sanitize($_POST['start_date'] ?? '');
    $end_date = sanitize($_POST['end_date'] ?? '');

    if (empty($semester_term) || empty($start_date) || empty($end_date)) {
        set_flash_message('danger', 'Please provide semester term name and valid term dates.');
        redirect(APP_URL . '/admin/academic-years/create.php');
    }

    mysqli_begin_transaction($conn);
    try {
        // If creating new academic year
        if ($ay_id <= 0 && $new_start > 0 && $new_end > $new_start) {
            $year_code = "{$new_start}-{$new_end}";

            // Check if year_code exists
            $stmt_c = mysqli_prepare($conn, "SELECT ay_id FROM academic_years WHERE year_code = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt_c, "s", $year_code);
            mysqli_stmt_execute($stmt_c);
            $res_c = mysqli_stmt_get_result($stmt_c);
            $existing_ay = mysqli_fetch_assoc($res_c);
            mysqli_stmt_close($stmt_c);

            if ($existing_ay) {
                $ay_id = (int)$existing_ay['ay_id'];
            } else {
                $stmt_in_ay = mysqli_prepare($conn, "INSERT INTO academic_years (year_code, start_year, end_year, is_current, created_at) VALUES (?, ?, ?, 0, NOW())");
                mysqli_stmt_bind_param($stmt_in_ay, "sii", $year_code, $new_start, $new_end);
                mysqli_stmt_execute($stmt_in_ay);
                $ay_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt_in_ay);
            }
        }

        if ($ay_id <= 0) {
            throw new Exception("Please select or define a valid academic year.");
        }

        // Check composite uniqueness: (ay_id, semester_term)
        $stmt_check_term = mysqli_prepare($conn, "SELECT semester_id FROM semesters WHERE ay_id = ? AND semester_term = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_check_term, "is", $ay_id, $semester_term);
        mysqli_stmt_execute($stmt_check_term);
        mysqli_stmt_store_result($stmt_check_term);
        if (mysqli_stmt_num_rows($stmt_check_term) > 0) {
            mysqli_stmt_close($stmt_check_term);
            throw new Exception("The term '{$semester_term}' is already configured for this academic year.");
        }
        mysqli_stmt_close($stmt_check_term);

        // If setting active, deactivate existing
        if ($set_active) {
            mysqli_query($conn, "UPDATE semesters SET is_active = 0");
            mysqli_query($conn, "UPDATE academic_years SET is_current = 0");
            $stmt_cur2 = mysqli_prepare($conn, "UPDATE academic_years SET is_current = 1 WHERE ay_id = ?");
            mysqli_stmt_bind_param($stmt_cur2, "i", $ay_id);
            mysqli_stmt_execute($stmt_cur2);
            mysqli_stmt_close($stmt_cur2);
        }

        // Insert new semester
        $stmt_in_sem = mysqli_prepare($conn, "INSERT INTO semesters (ay_id, semester_term, is_active, start_date, end_date, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt_in_sem, "isisss", $ay_id, $semester_term, $set_active, $start_date, $end_date);
        mysqli_stmt_execute($stmt_in_sem);
        $new_sem_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_in_sem);

        mysqli_commit($conn);

        log_activity($conn, 'CREATE', 'TERMS', $new_sem_id, "Created academic term {$semester_term} (ID: {$new_sem_id})");
        set_flash_message('success', "Academic term '{$semester_term}' created successfully.");
        redirect(APP_URL . '/admin/academic-years/index.php');

    } catch (Exception $e) {
        mysqli_rollback($conn);
        set_flash_message('danger', 'Error: ' . $e->getMessage());
        redirect(APP_URL . '/admin/academic-years/create.php');
    }
}

redirect(APP_URL . '/admin/academic-years/index.php');
