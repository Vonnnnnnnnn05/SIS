<?php
/**
 * Registrar Dashboard
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Registrar');

$conn = db_connect();
$page_title = 'Registrar Dashboard';
$user = current_user();

// 1. Metric: Total & Active Students
$st_res = mysqli_query($conn, "SELECT COUNT(*) as total, 
                                      SUM(CASE WHEN current_status = 'Active' THEN 1 ELSE 0 END) as active_total 
                               FROM students");
$st_counts = mysqli_fetch_assoc($st_res);
$total_students = (int)($st_counts['total'] ?? 0);
$active_students = (int)($st_counts['active_total'] ?? 0);

// 2. Metric: Active Term & Enrollments
$active_term_res = mysqli_query($conn, "SELECT sem.semester_id, sem.semester_term, sem.start_date, sem.end_date, ay.year_code,
                                               (SELECT COUNT(*) FROM enrollments WHERE semester_id = sem.semester_id AND enrollment_status = 'Enrolled') as enrolled_count,
                                               (SELECT COUNT(*) FROM enrollments WHERE semester_id = sem.semester_id AND enrollment_status = 'Pending') as pending_count
                                        FROM semesters sem
                                        INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id
                                        WHERE sem.is_active = 1
                                        LIMIT 1");
$active_term = mysqli_fetch_assoc($active_term_res);
$active_term_enrolled = (int)($active_term['enrolled_count'] ?? 0);
$active_term_pending = (int)($active_term['pending_count'] ?? 0);

// 3. Recent Registrations (latest 5)
$recents_st_res = mysqli_query($conn, "SELECT s.student_id, s.student_number, s.first_name, s.last_name, s.created_at, p.program_code
                                       FROM students s
                                       LEFT JOIN programs p ON s.program_id = p.program_id
                                       ORDER BY s.created_at DESC
                                       LIMIT 5");

// 4. Recent Term Enrollments (latest 5)
$recents_enr_res = mysqli_query($conn, "SELECT e.enrollment_id, e.enrollment_date, e.enrollment_status, e.year_level,
                                               s.student_id, s.student_number, s.first_name, s.last_name,
                                               p.program_code
                                        FROM enrollments e
                                        INNER JOIN students s ON e.student_id = s.student_id
                                        INNER JOIN programs p ON e.program_id = p.program_id
                                        ORDER BY e.created_at DESC
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
                        <h1 class="h3 mb-1 fw-bold">Registrar Dashboard</h1>
                        <p class="text-muted small mb-0">Overview of student admissions, term enrollments, and academic records.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo APP_URL; ?>/registrar/students/register.php" class="btn btn-outline-primary">
                            <i class="bi bi-person-plus-fill me-1"></i> Register Student
                        </a>
                        <a href="<?php echo APP_URL; ?>/registrar/enrollment/enroll.php" class="btn btn-primary">
                            <i class="bi bi-journal-plus me-1"></i> Enroll Student
                        </a>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <!-- Active Term Announcement Banner -->
                <?php if ($active_term): ?>
                    <div class="card border-0 shadow-sm mb-4 border-start border-primary border-4">
                        <div class="card-body p-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                                <div>
                                    <span class="badge bg-primary text-uppercase mb-1">Active Enrollment Term</span>
                                    <h5 class="fw-bold mb-0">
                                        Academic Year <?php echo escape($active_term['year_code']); ?> &mdash; <?php echo escape($active_term['semester_term']); ?>
                                    </h5>
                                    <small class="text-muted">
                                        Period: <?php echo !empty($active_term['start_date']) ? format_date($active_term['start_date']) : 'Current'; ?>
                                        to <?php echo !empty($active_term['end_date']) ? format_date($active_term['end_date']) : 'Current'; ?>
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo APP_URL; ?>/registrar/enrollment/index.php" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-journal-text me-1"></i> View Term List
                                    </a>
                                    <a href="<?php echo APP_URL; ?>/registrar/enrollment/enroll.php" class="btn btn-sm btn-primary">
                                        <i class="bi bi-person-check-fill me-1"></i> Enroll Student
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning border-0 mb-4 py-3 d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i>
                        <div>
                            <strong>No Active Enrollment Term:</strong> An academic term must be activated by the Administrator before students can be enrolled.
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Statistics Metric Cards -->
                <div class="row g-3 mb-4">
                    <!-- Total Registered Students -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted small fw-semibold text-uppercase">Registered Students</span>
                                    <div class="rounded-2 bg-primary-subtle p-2 text-primary">
                                        <i class="bi bi-people-fill fs-5"></i>
                                    </div>
                                </div>
                                <div class="fs-3 fw-bold mb-1"><?php echo number_format($total_students); ?></div>
                                <div class="small text-muted">
                                    Total in Database
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Institutional Standing -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted small fw-semibold text-uppercase">Active Standing</span>
                                    <div class="rounded-2 bg-success-subtle p-2 text-success">
                                        <i class="bi bi-person-check-fill fs-5"></i>
                                    </div>
                                </div>
                                <div class="fs-3 fw-bold mb-1"><?php echo number_format($active_students); ?></div>
                                <div class="small text-muted">
                                    Eligible for Enrollment
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Term Enrolled -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted small fw-semibold text-uppercase">Enrolled (Term)</span>
                                    <div class="rounded-2 bg-info-subtle p-2 text-info">
                                        <i class="bi bi-journal-check fs-5"></i>
                                    </div>
                                </div>
                                <div class="fs-3 fw-bold mb-1"><?php echo number_format($active_term_enrolled); ?></div>
                                <div class="small text-muted">
                                    Confirmed Coursework
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Term Approvals -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted small fw-semibold text-uppercase">Pending Approvals</span>
                                    <div class="rounded-2 bg-warning-subtle p-2 text-warning">
                                        <i class="bi bi-hourglass-split fs-5"></i>
                                    </div>
                                </div>
                                <div class="fs-3 fw-bold mb-1"><?php echo number_format($active_term_pending); ?></div>
                                <div class="small text-muted">
                                    Awaiting Verification
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Two-Column Overview: Recent Enrollments & Recent Registrations -->
                <div class="row g-4">
                    <!-- Recent Term Enrollments -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0 fw-semibold">
                                    <i class="bi bi-journal-check me-1 text-primary"></i> Recent Term Enrollments
                                </h6>
                                <a href="<?php echo APP_URL; ?>/registrar/enrollment/index.php" class="small text-decoration-none">
                                    View All <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student</th>
                                            <th>Program / Level</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recents_enr_res && mysqli_num_rows($recents_enr_res) > 0): ?>
                                            <?php while ($enr = mysqli_fetch_assoc($recents_enr_res)): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold text-dark">
                                                            <?php echo escape($enr['last_name'] . ', ' . $enr['first_name']); ?>
                                                        </div>
                                                        <span class="font-monospace text-muted" style="font-size: 0.75rem;">
                                                            <?php echo escape($enr['student_number']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border"><?php echo escape($enr['program_code']); ?></span>
                                                        <div class="text-muted" style="font-size: 0.75rem;"><?php echo escape($enr['year_level']); ?></div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $badge_cls = 'bg-secondary';
                                                        if ($enr['enrollment_status'] === 'Enrolled') $badge_cls = 'bg-success';
                                                        elseif ($enr['enrollment_status'] === 'Pending') $badge_cls = 'bg-warning text-dark';
                                                        elseif ($enr['enrollment_status'] === 'Completed') $badge_cls = 'bg-info text-dark';
                                                        elseif ($enr['enrollment_status'] === 'Dropped') $badge_cls = 'bg-danger';
                                                        ?>
                                                        <span class="badge <?php echo $badge_cls; ?>"><?php echo escape($enr['enrollment_status']); ?></span>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="<?php echo APP_URL; ?>/registrar/enrollment/edit-status.php?id=<?php echo (int)$enr['enrollment_id']; ?>" 
                                                           class="btn btn-sm btn-outline-secondary py-0 px-2" title="Edit Status">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">
                                                    No term enrollments recorded yet.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Registrations -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0 fw-semibold">
                                    <i class="bi bi-person-check me-1 text-primary"></i> Recent Student Registrations
                                </h6>
                                <a href="<?php echo APP_URL; ?>/registrar/students/index.php" class="small text-decoration-none">
                                    View Directory <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if ($recents_st_res && mysqli_num_rows($recents_st_res) > 0): ?>
                                        <?php while ($st = mysqli_fetch_assoc($recents_st_res)): ?>
                                            <a href="<?php echo APP_URL; ?>/registrar/students/view.php?id=<?php echo (int)$st['student_id']; ?>" 
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
