<?php
/**
 * Shared Role-Filtered Sidebar Navigation
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$auth_user = current_user();
$role = $auth_user['role_name'] ?? '';
$current_script = $_SERVER['SCRIPT_NAME'] ?? '';

/**
 * Check if the active script matches the link target
 * @param string $path_substring
 * @return string ('active' or '')
 */
function is_nav_active($path_substring) {
    global $current_script;
    return (strpos($current_script, $path_substring) !== false) ? 'active' : '';
}
?>
<aside class="app-sidebar">
    <a href="<?php echo APP_URL; ?>/index.php" class="sidebar-brand">
        <i class="bi bi-mortarboard-fill"></i>
        <span class="text-truncate"><?php echo APP_NAME; ?></span>
    </a>

    <div class="sidebar-menu">
        <?php if ($role === 'Administrator'): ?>
            <div class="sidebar-heading">Core Administration</div>
            <a href="<?php echo APP_URL; ?>/admin/dashboard.php" class="nav-link-custom <?php echo is_nav_active('admin/dashboard.php'); ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="<?php echo APP_URL; ?>/admin/users/index.php" class="nav-link-custom <?php echo is_nav_active('admin/users/'); ?>">
                <i class="bi bi-people-fill"></i> User Accounts
            </a>
            <a href="<?php echo APP_URL; ?>/admin/students/index.php" class="nav-link-custom <?php echo is_nav_active('admin/students/'); ?>">
                <i class="bi bi-mortarboard"></i> Student Records
            </a>
            <a href="<?php echo APP_URL; ?>/admin/programs/index.php" class="nav-link-custom <?php echo is_nav_active('admin/programs/'); ?>">
                <i class="bi bi-book"></i> Courses & Programs
            </a>
            <a href="<?php echo APP_URL; ?>/admin/academic-years/index.php" class="nav-link-custom <?php echo is_nav_active('admin/academic-years/'); ?>">
                <i class="bi bi-calendar-event"></i> Academic Terms
            </a>

            <div class="sidebar-heading">Reports & Data</div>
            <a href="<?php echo APP_URL; ?>/admin/reports/student-reports.php" class="nav-link-custom <?php echo is_nav_active('admin/reports/'); ?>">
                <i class="bi bi-file-earmark-bar-graph"></i> Institutional Reports
            </a>
            <a href="<?php echo APP_URL; ?>/admin/backup/index.php" class="nav-link-custom <?php echo is_nav_active('admin/backup/'); ?>">
                <i class="bi bi-database-check"></i> Database Backup
            </a>
            <a href="<?php echo APP_URL; ?>/admin/logs/index.php" class="nav-link-custom <?php echo is_nav_active('admin/logs/'); ?>">
                <i class="bi bi-shield-check"></i> Activity Audit Logs
            </a>

        <?php elseif ($role === 'Registrar'): ?>
            <div class="sidebar-heading">Registrar Operations</div>
            <a href="<?php echo APP_URL; ?>/registrar/dashboard.php" class="nav-link-custom <?php echo is_nav_active('registrar/dashboard.php'); ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="<?php echo APP_URL; ?>/registrar/students/index.php" class="nav-link-custom <?php echo (is_nav_active('registrar/students/') && !is_nav_active('register.php')); ?>">
                <i class="bi bi-people-fill"></i> Student Directory
            </a>
            <a href="<?php echo APP_URL; ?>/registrar/students/register.php" class="nav-link-custom <?php echo is_nav_active('register.php'); ?>">
                <i class="bi bi-person-plus-fill"></i> Register Student
            </a>
            <a href="<?php echo APP_URL; ?>/registrar/enrollment/index.php" class="nav-link-custom <?php echo is_nav_active('registrar/enrollment/'); ?>">
                <i class="bi bi-journal-check"></i> Term Enrollments
            </a>
            <a href="<?php echo APP_URL; ?>/registrar/programs/index.php" class="nav-link-custom <?php echo is_nav_active('registrar/programs/'); ?>">
                <i class="bi bi-book"></i> Courses & Programs
            </a>
            <a href="<?php echo APP_URL; ?>/registrar/academic-years/index.php" class="nav-link-custom <?php echo is_nav_active('registrar/academic-years/'); ?>">
                <i class="bi bi-calendar-event"></i> Academic Calendar
            </a>

            <div class="sidebar-heading">Records & Exports</div>
            <a href="<?php echo APP_URL; ?>/registrar/reports/student-list.php" class="nav-link-custom <?php echo is_nav_active('registrar/reports/'); ?>">
                <i class="bi bi-printer-fill"></i> Registrar Reports
            </a>

        <?php elseif ($role === 'Student'): ?>
            <div class="sidebar-heading">Student Portal</div>
            <a href="<?php echo APP_URL; ?>/student/dashboard.php" class="nav-link-custom <?php echo is_nav_active('student/dashboard.php'); ?>">
                <i class="bi bi-speedometer2"></i> My Dashboard
            </a>
            <a href="<?php echo APP_URL; ?>/student/profile.php" class="nav-link-custom <?php echo is_nav_active('student/profile'); ?>">
                <i class="bi bi-person-vcard"></i> My Profile
            </a>
            <a href="<?php echo APP_URL; ?>/student/enrollment.php" class="nav-link-custom <?php echo is_nav_active('student/enrollment.php'); ?>">
                <i class="bi bi-journal-text"></i> Current Enrollment
            </a>
            <a href="<?php echo APP_URL; ?>/student/academic-records.php" class="nav-link-custom <?php echo is_nav_active('student/academic-records.php'); ?>">
                <i class="bi bi-clock-history"></i> Academic History
            </a>
            <a href="<?php echo APP_URL; ?>/student/change-password.php" class="nav-link-custom <?php echo is_nav_active('student/change-password.php'); ?>">
                <i class="bi bi-key"></i> Change Password
            </a>
        <?php endif; ?>
    </div>
</aside>
