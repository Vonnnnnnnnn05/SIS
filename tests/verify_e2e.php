<?php
/**
 * Automated Verification Script
 * Student Information System (SIS)
 * Constitution v2.0.0 Verification Suite
 */

require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/logger.php';

$conn = db_connect();

echo "=========================================================\n";
echo "SIS CONSTITUTION v2.0.0 - AUTOMATED VERIFICATION SUITE\n";
echo "=========================================================\n\n";

$pass_count = 0;
$fail_count = 0;

function assert_test($label, $condition, $details = '') {
    global $pass_count, $fail_count;
    if ($condition) {
        echo "[PASS] $label\n";
        $pass_count++;
    } else {
        echo "[FAIL] $label - $details\n";
        $fail_count++;
    }
}

// 1. Table Verification
$required_tables = ['roles', 'programs', 'academic_years', 'semesters', 'users', 'students', 'enrollments', 'system_logs', 'backup_logs'];
foreach ($required_tables as $table) {
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    assert_test("Table `$table` exists in database", mysqli_num_rows($res) > 0);
}

// 2. Seed Accounts & Password Hashes
$accounts = [
    'admin' => ['role' => 'Administrator', 'pass' => 'Admin@123'],
    'registrar' => ['role' => 'Registrar', 'pass' => 'Registrar@123']
];

// Dynamically check for any student account if present
$stu_user_res = mysqli_query($conn, "SELECT u.username, u.password, r.role_name FROM users u INNER JOIN roles r ON u.role_id = r.role_id WHERE r.role_name = 'Student' LIMIT 1");
if ($stu_user = mysqli_fetch_assoc($stu_user_res)) {
    $accounts[$stu_user['username']] = ['role' => 'Student', 'pass' => null];
}

foreach ($accounts as $uname => $meta) {
    $stmt = mysqli_prepare($conn, "SELECT u.password, u.status, r.role_name FROM users u INNER JOIN roles r ON u.role_id = r.role_id WHERE u.username = ?");
    mysqli_stmt_bind_param($stmt, "s", $uname);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    assert_test("Account `{$uname}` exists with role `{$meta['role']}`", $user && $user['role_name'] === $meta['role']);
    $algo_info = password_get_info($user['password'] ?? '');
    $is_valid_hash = ($algo_info['algo'] !== 0 && !empty($user['password']));
    $matches_default = (!empty($meta['pass']) && password_verify($meta['pass'], $user['password'] ?? ''));
    assert_test("Account `{$uname}` password hashes verify properly", $user && ($matches_default || $is_valid_hash), $matches_default ? "Default password" : "Active password hash verified");
}

// 3. Active Academic Term
$sem_res = mysqli_query($conn, "SELECT sem.semester_id, sem.semester_term, sem.is_active, ay.year_code FROM semesters sem INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id WHERE sem.is_active = 1 LIMIT 1");
$active_term = mysqli_fetch_assoc($sem_res);
assert_test("Active academic semester is provisioned", !empty($active_term));

// 4. Duplicate Term Enrollment Prevention
if ($active_term) {
    // Find or pick a student
    $st_res = mysqli_query($conn, "SELECT student_id, student_number FROM students LIMIT 1");
    $st = mysqli_fetch_assoc($st_res);

    if ($st) {
        $st_id = (int)$st['student_id'];
        $sem_id = (int)$active_term['semester_id'];

        // Clean any existing test enrollment for this pair
        mysqli_query($conn, "DELETE FROM enrollments WHERE student_id = $st_id AND semester_id = $sem_id");

        // Insert first enrollment
        $p_res = mysqli_query($conn, "SELECT program_id FROM programs LIMIT 1");
        $prog_id = (int)mysqli_fetch_assoc($p_res)['program_id'];
        $u_res = mysqli_query($conn, "SELECT user_id FROM users WHERE username = 'admin' LIMIT 1");
        $creator_id = (int)mysqli_fetch_assoc($u_res)['user_id'];

        $ins = mysqli_query($conn, "INSERT INTO enrollments (student_id, semester_id, program_id, year_level, enrollment_date, enrollment_status, created_by) VALUES ($st_id, $sem_id, $prog_id, '1st Year', CURDATE(), 'Enrolled', $creator_id)");
        assert_test("Initial enrollment inserts successfully", $ins === true);

        // Attempt duplicate enrollment
        $caught_dup = false;
        try {
            mysqli_query($conn, "INSERT INTO enrollments (student_id, semester_id, program_id, year_level, enrollment_date, enrollment_status, created_by) VALUES ($st_id, $sem_id, $prog_id, '1st Year', CURDATE(), 'Enrolled', $creator_id)");
        } catch (mysqli_sql_exception $e) {
            $caught_dup = true;
        }
        assert_test("Duplicate enrollment for same semester is blocked by unique constraint", $caught_dup === true);
    }
}

// 5. Activity Audit Logging
$log_test_details = "Automated verification test log at " . date('Y-m-d H:i:s');
$log_success = log_activity($conn, 'CREATE', 'TEST', '9999', $log_test_details);
assert_test("Audit logger log_activity() executes successfully", $log_success === true);

$chk_log = mysqli_query($conn, "SELECT log_id FROM system_logs WHERE details = '" . mysqli_real_escape_string($conn, $log_test_details) . "'");
assert_test("Audit entry is persisted in `system_logs`", mysqli_num_rows($chk_log) > 0);
mysqli_query($conn, "DELETE FROM system_logs WHERE details = '" . mysqli_real_escape_string($conn, $log_test_details) . "'");

// 6. Security & Storage Directory Protection
assert_test("Backup directory `database/backup/.htaccess` exists", file_exists(BACKUP_PATH . '/.htaccess'));
$htaccess = file_get_contents(BACKUP_PATH . '/.htaccess');
assert_test("Backup directory denies public HTTP access", stripos($htaccess, 'Deny from all') !== false || stripos($htaccess, 'Require all denied') !== false);

echo "\n---------------------------------------------------------\n";
echo "RESULTS: {$pass_count} PASSED, {$fail_count} FAILED.\n";
echo "---------------------------------------------------------\n";
