<?php
/**
 * Administrator Dashboard
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Administrator');

$conn = db_connect();
$page_title = 'Administrator Dashboard';
$user = current_user();

// 1. Metric: Total Students
$st_res = mysqli_query($conn, "SELECT COUNT(*) as total, 
                                      SUM(CASE WHEN current_status = 'Active' THEN 1 ELSE 0 END) as active_total 
                               FROM students");
$st_counts = mysqli_fetch_assoc($st_res);
$total_students = (int)($st_counts['total'] ?? 0);
$active_students = (int)($st_counts['active_total'] ?? 0);

// 2. Metric: Total Users & Staff
$user_res = mysqli_query($conn, "SELECT COUNT(*) as total,
                                        SUM(CASE WHEN r.role_name = 'Registrar' THEN 1 ELSE 0 END) as registrars,
                                        SUM(CASE WHEN u.status = 'Active' THEN 1 ELSE 0 END) as active_users
                                 FROM users u
                                 INNER JOIN roles r ON u.role_id = r.role_id");
$user_counts = mysqli_fetch_assoc($user_res);
$total_users = (int)($user_counts['total'] ?? 0);
$total_registrars = (int)($user_counts['registrars'] ?? 0);

// 3. Metric: Active Term & Enrollments
$active_term_res = mysqli_query($conn, "SELECT sem.semester_id, sem.semester_term, ay.year_code,
                                               (SELECT COUNT(*) FROM enrollments WHERE semester_id = sem.semester_id) as enrolled_count
                                        FROM semesters sem
                                        INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id
                                        WHERE sem.is_active = 1
                                        LIMIT 1");
$active_term = mysqli_fetch_assoc($active_term_res);
$active_term_enrolled = (int)($active_term['enrolled_count'] ?? 0);

// 4. Metric: Degree Programs
$prog_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM programs WHERE status = 'Active'");
$total_programs = (int)(mysqli_fetch_assoc($prog_res)['total'] ?? 0);

// 5. Recent System Logs (latest 8)
$logs_res = mysqli_query($conn, "SELECT l.log_id, l.role, l.action_type, l.module, l.details, l.created_at, u.username
                                 FROM system_logs l
                                 LEFT JOIN users u ON l.user_id = u.user_id
                                 ORDER BY l.created_at DESC
                                 LIMIT 8");

// 6. Recent Student Registrations (latest 5)
$recents_res = mysqli_query($conn, "SELECT s.student_id, s.student_number, s.first_name, s.last_name, s.created_at, p.program_code
                                    FROM students s
                                    LEFT JOIN programs p ON s.program_id = p.program_id
                                    ORDER BY s.created_at DESC
                                    LIMIT 5");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <main class="content-body">
            <div class="container-fluid px-0">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                    <div>
                        <h1 class="h3 mb-1 fw-bold">Administrator Dashboard</h1>
                        <p class="text-muted small mb-0">System health, institutional metrics, and audit overview.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo APP_URL; ?>/admin/backup/index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-database-check me-1"></i> Database Backup
                        </a>
                        <a href="<?php echo APP_URL; ?>/admin/users/create.php" class="btn btn-primary">
                            <i class="bi bi-person-plus-fill me-1"></i> New User Account
                        </a>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <!-- Active Term Alert Banner -->
                <?php if ($active_term): ?>
                    <div class="alert alert-primary bg-primary-subtle border-0 mb-4 py-2 px-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-check fs-4 me-2 text-primary"></i>
                            <div>
                                <strong class="me-2">Current Active Semester:</strong>
                                <span>AY <?php echo escape($active_term['year_code']); ?> &mdash; <?php echo escape($active_term['semester_term']); ?></span>
                            </div>
                        </div>
                        <div>
                            <span class="badge bg-primary"><?php echo number_format($active_term_enrolled); ?> Students Enrolled</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning border-0 mb-4 py-2 px-3 d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-warning"></i>
                        <span>No academic semester is currently marked as active. <a href="<?php echo APP_URL; ?>/admin/academic-years/index.php" class="alert-link">Manage academic terms</a> to activate one.</span>
                    </div>
                <?php endif; ?>

                <!-- Statistics Metric Cards -->
                <div class="row g-3 mb-4">
                    <!-- Total Students Card -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted small fw-semibold text-uppercase">Total Students</span>
                                    <div class="rounded-2 bg-primary-subtle p-2 text-primary">
                                        <i class="bi bi-mortarboard fs-5"></i>
                                    </div>
                                </div>
                                <div class="fs-3 fw-bold mb-1"><?php echo number_format($total_students); ?></div>
                                <div class="small text-muted">
                                    <span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i><?php echo number_format($active_students); ?></span> Active Enrollees
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Term Enrollments Card -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted small fw-semibold text-uppercase">Term Enrollments</span>
                                    <div class="rounded-2 bg-success-subtle p-2 text-success">
                                        <i class="bi bi-journal-check fs-5"></i>
                                    </div>
                                </div>
                                <div class="fs-3 fw-bold mb-1"><?php echo number_format($active_term_enrolled); ?></div>
                                <div class="small text-muted">
                                    <?php echo $active_term ? 'Current Semester' : 'No Active Term'; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Accounts Card -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted small fw-semibold text-uppercase">User Accounts</span>
                                    <div class="rounded-2 bg-info-subtle p-2 text-info">
                                        <i class="bi bi-people fs-5"></i>
                                    </div>
                                </div>
                                <div class="fs-3 fw-bold mb-1"><?php echo number_format($total_users); ?></div>
                                <div class="small text-muted">
                                    <span class="fw-semibold"><?php echo number_format($total_registrars); ?></span> Registrar Officers
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Degree Programs Card -->
                    <div class="col-sm-6 col-xl-3">
                        <a href="<?php echo APP_URL; ?>/admin/programs/index.php" class="text-decoration-none text-dark">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small fw-semibold text-uppercase">Academic Programs</span>
                                        <div class="rounded-2 bg-warning-subtle p-2 text-warning">
                                            <i class="bi bi-book fs-5"></i>
                                        </div>
                                    </div>
                                    <div class="fs-3 fw-bold mb-1"><?php echo number_format($total_programs); ?></div>
                                    <div class="small text-primary fw-semibold">
                                        Manage Programs <i class="bi bi-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Two-Column Overview: Recent Logs & Recent Students -->
                <div class="row g-4">
                    <!-- Recent System Activity Logs -->
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0 fw-semibold">
                                    <i class="bi bi-shield-check me-1 text-primary"></i> Recent Audit Activity
                                </h6>
                                <a href="<?php echo APP_URL; ?>/admin/logs/index.php" class="small text-decoration-none">
                                    View All Logs <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Actor</th>
                                            <th>Action / Module</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($logs_res && mysqli_num_rows($logs_res) > 0): ?>
                                            <?php while ($log = mysqli_fetch_assoc($logs_res)): ?>
                                                <tr>
                                                    <td class="text-nowrap text-muted">
                                                        <?php echo format_date($log['created_at'], 'M d, H:i'); ?>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold"><?php echo escape($log['username'] ?? 'System'); ?></span>
                                                        <div class="text-muted" style="font-size: 0.75rem;"><?php echo escape($log['role']); ?></div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border"><?php echo escape($log['action_type']); ?></span>
                                                        <span class="text-muted ms-1"><?php echo escape($log['module']); ?></span>
                                                    </td>
                                                    <td class="text-truncate" style="max-width: 220px;" title="<?php echo escape($log['details']); ?>">
                                                        <?php echo escape($log['details']); ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">
                                                    No activity logged yet.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Student Registrations -->
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0 fw-semibold">
                                    <i class="bi bi-person-check me-1 text-primary"></i> Recent Registrations
                                </h6>
                                <a href="<?php echo APP_URL; ?>/admin/students/index.php" class="small text-decoration-none">
                                    View All Students <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if ($recents_res && mysqli_num_rows($recents_res) > 0): ?>
                                        <?php while ($st = mysqli_fetch_assoc($recents_res)): ?>
                                            <a href="<?php echo APP_URL; ?>/admin/students/view.php?id=<?php echo (int)$st['student_id']; ?>" 
                                               class="list-group-item list-group-item-action p-3">
                                                <div class="d-flex w-100 justify-content-between align-items-center">
                                                    <div>
                                                        <div class="fw-semibold text-dark">
                                                            <?php echo escape($st['last_name'] . ', ' . $st['first_name']); ?>
                                                        </div>
                                                        <span class="font-monospace small text-primary"><?php echo escape($st['student_number']); ?></span>
                                                        <span class="badge bg-light text-dark border ms-1"><?php echo escape($st['program_code']); ?></span>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo format_date($st['created_at'], 'M d'); ?>
                                                    </small>
                                                </div>
                                            </a>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="p-4 text-center text-muted">
                                            No students registered yet.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
