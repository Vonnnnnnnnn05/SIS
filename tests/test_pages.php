<?php
/**
 * Test render of all primary pages
 */
require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

$conn = db_connect();

// Ensure test data exists for views that require existing database records
$st_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT student_id FROM students LIMIT 1"));
if (!$st_row) {
    $prog_check = mysqli_query($conn, "SELECT program_id FROM programs LIMIT 1");
    if (mysqli_num_rows($prog_check) == 0) {
        mysqli_query($conn, "INSERT INTO programs (program_code, program_name, department, status) VALUES ('BSIT', 'Bachelor of Science in Information Technology', 'College of Computer Studies', 'Active')");
    }
    $active_prog_id = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT program_id FROM programs LIMIT 1"))[0];

    // Check if test student user exists
    $u_res = mysqli_query($conn, "SELECT user_id FROM users WHERE username = '2026-00001' LIMIT 1");
    $u_row = mysqli_fetch_assoc($u_res);
    $user_id_seed = null;
    if ($u_row) {
        $user_id_seed = (int)$u_row['user_id'];
    } else {
        $p_hash = password_hash('Student@2026', PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO users (username, email, password, role_id, status, must_change_password) VALUES ('2026-00001', 'juan.delacruz@sis.local', '{$p_hash}', 3, 'Active', 0)");
        $user_id_seed = (int)mysqli_insert_id($conn);
    }

    mysqli_query($conn, "INSERT INTO students (student_number, user_id, first_name, middle_name, last_name, date_of_birth, gender, civil_status, email, contact_number, home_address, emergency_contact_name, emergency_contact_number, program_id, current_status) VALUES ('2026-00001', {$user_id_seed}, 'Juan', 'Santos', 'Dela Cruz', '2005-01-01', 'Male', 'Single', 'juan.delacruz@sis.local', '09123456789', 'Manila, Philippines', 'Maria Dela Cruz', '09123456788', {$active_prog_id}, 'Active')");
    $active_st_id = (int)mysqli_insert_id($conn);
} else {
    $active_st_id = (int)$st_row['student_id'];
    $prog_res = mysqli_query($conn, "SELECT program_id FROM programs LIMIT 1");
    $active_prog_id = (int)mysqli_fetch_row($prog_res)[0];
}

$pages_to_test = [
    // Admin pages
    ['file' => 'admin/dashboard.php', 'role' => 'Administrator', 'user_id' => 1],
    ['file' => 'admin/users/index.php', 'role' => 'Administrator', 'user_id' => 1],
    ['file' => 'admin/users/create.php', 'role' => 'Administrator', 'user_id' => 1],
    ['file' => 'admin/academic-years/index.php', 'role' => 'Administrator', 'user_id' => 1],
    ['file' => 'admin/academic-years/create.php', 'role' => 'Administrator', 'user_id' => 1],
    ['file' => 'admin/students/index.php', 'role' => 'Administrator', 'user_id' => 1],
    ['file' => 'admin/students/view.php', 'role' => 'Administrator', 'user_id' => 1, 'get_id' => $active_st_id],
    ['file' => 'admin/students/edit.php', 'role' => 'Administrator', 'user_id' => 1, 'get_id' => $active_st_id],
    ['file' => 'admin/programs/index.php', 'role' => 'Administrator', 'user_id' => 1],
    ['file' => 'admin/programs/create.php', 'role' => 'Administrator', 'user_id' => 1],
    ['file' => 'admin/programs/edit.php', 'role' => 'Administrator', 'user_id' => 1, 'get_id' => $active_prog_id],
    ['file' => 'admin/reports/student-reports.php', 'role' => 'Administrator', 'user_id' => 1],
    ['file' => 'admin/reports/enrollment-reports.php', 'role' => 'Administrator', 'user_id' => 1],
    ['file' => 'admin/backup/index.php', 'role' => 'Administrator', 'user_id' => 1],
    ['file' => 'admin/logs/index.php', 'role' => 'Administrator', 'user_id' => 1],

    // Registrar pages
    ['file' => 'registrar/dashboard.php', 'role' => 'Registrar', 'user_id' => 2],
    ['file' => 'registrar/students/index.php', 'role' => 'Registrar', 'user_id' => 2],
    ['file' => 'registrar/students/view.php', 'role' => 'Registrar', 'user_id' => 2, 'get_id' => $active_st_id],
    ['file' => 'registrar/students/edit.php', 'role' => 'Registrar', 'user_id' => 2, 'get_id' => $active_st_id],
    ['file' => 'registrar/students/register.php', 'role' => 'Registrar', 'user_id' => 2],
    ['file' => 'registrar/enrollment/index.php', 'role' => 'Registrar', 'user_id' => 2],
    ['file' => 'registrar/enrollment/enroll.php', 'role' => 'Registrar', 'user_id' => 2],
    ['file' => 'registrar/programs/index.php', 'role' => 'Registrar', 'user_id' => 2],
    ['file' => 'registrar/programs/create.php', 'role' => 'Registrar', 'user_id' => 2],
    ['file' => 'registrar/programs/edit.php', 'role' => 'Registrar', 'user_id' => 2, 'get_id' => $active_prog_id],
    ['file' => 'registrar/academic-years/index.php', 'role' => 'Registrar', 'user_id' => 2],
    ['file' => 'registrar/reports/student-list.php', 'role' => 'Registrar', 'user_id' => 2],
    ['file' => 'registrar/reports/enrollment-list.php', 'role' => 'Registrar', 'user_id' => 2],

    // Student pages
    ['file' => 'student/dashboard.php', 'role' => 'Student', 'user_id' => 3, 'student_id' => $active_st_id],
    ['file' => 'student/profile.php', 'role' => 'Student', 'user_id' => 3, 'student_id' => $active_st_id],
    ['file' => 'student/profile-edit.php', 'role' => 'Student', 'user_id' => 3, 'student_id' => $active_st_id],
    ['file' => 'student/enrollment.php', 'role' => 'Student', 'user_id' => 3, 'student_id' => $active_st_id],
    ['file' => 'student/academic-records.php', 'role' => 'Student', 'user_id' => 3, 'student_id' => $active_st_id],
    ['file' => 'student/change-password.php', 'role' => 'Student', 'user_id' => 3, 'student_id' => $active_st_id]
];

function test_render_page($file, $role, $user_id, $student_id = null, $get_id = null) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = 'test_' . strtolower($role);
    $_SESSION['role_name'] = $role;
    $_SESSION['student_id'] = $student_id;
    $_SESSION['last_activity'] = time();
    if ($get_id !== null) {
        $_GET['id'] = $get_id;
    } else {
        unset($_GET['id']);
    }

    ob_start();
    include __DIR__ . '/../' . $file;
    return ob_get_clean();
}

$all_ok = true;
foreach ($pages_to_test as $item) {
    try {
        $out = test_render_page($item['file'], $item['role'], $item['user_id'], $item['student_id'] ?? null, $item['get_id'] ?? null);
        echo "[OK] {$item['file']} (" . strlen($out) . " bytes)\n";
    } catch (Throwable $t) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo "[ERROR] {$item['file']}: " . $t->getMessage() . " at " . $t->getFile() . ":" . $t->getLine() . "\n";
        $all_ok = false;
    }
}

if ($all_ok) {
    echo "\nALL " . count($pages_to_test) . " PAGES RENDERED SUCCESSFULLY WITHOUT RUNTIME ERRORS!\n";
}
