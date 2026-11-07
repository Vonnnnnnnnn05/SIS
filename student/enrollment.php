<?php
/**
 * Student Current Term Enrollment View
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Strict IDOR Protection - Zero Emojis
 */

require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Student');

$conn = db_connect();
$page_title = 'Current Enrollment';
$user = current_user();

// Strict IDOR protection: resolve student_id from session
$student_id = (int)($_SESSION['student_id'] ?? 0);
if ($student_id <= 0) {
    $lookup_stmt = mysqli_prepare($conn, "SELECT student_id, student_number FROM students WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($lookup_stmt, "i", $user['user_id']);
    mysqli_stmt_execute($lookup_stmt);
    $st_res = mysqli_stmt_get_result($lookup_stmt);
    if ($st_row = mysqli_fetch_assoc($st_res)) {
        $student_id = (int)$st_row['student_id'];
        $_SESSION['student_id'] = $student_id;
    }
    mysqli_stmt_close($lookup_stmt);
}

// Fetch active semester details
$active_sem_res = mysqli_query($conn, "SELECT sem.semester_id, sem.semester_term, sem.start_date, sem.end_date, ay.year_code 
                                       FROM semesters sem 
                                       INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id 
                                       WHERE sem.is_active = 1 
                                       LIMIT 1");
$active_semester = mysqli_fetch_assoc($active_sem_res);

// Fetch student profile summary
$st_stmt = mysqli_prepare($conn, "SELECT s.*, p.program_code, p.program_name 
                                  FROM students s 
                                  LEFT JOIN programs p ON s.program_id = p.program_id 
                                  WHERE s.student_id = ?");
mysqli_stmt_bind_param($st_stmt, "i", $student_id);
mysqli_stmt_execute($st_stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($st_stmt));
mysqli_stmt_close($st_stmt);

// Fetch current term enrollment record
$enrollment = null;
if ($student_id > 0 && $active_semester) {
    $enr_sql = "SELECT e.*, p.program_code, p.program_name, 
                       sem.semester_term, sem.start_date, sem.end_date,
                       ay.year_code
                FROM enrollments e
                INNER JOIN semesters sem ON e.semester_id = sem.semester_id
                INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id
                INNER JOIN programs p ON e.program_id = p.program_id
                WHERE e.student_id = ? AND sem.is_active = 1";
    $enr_stmt = mysqli_prepare($conn, $enr_sql);
    mysqli_stmt_bind_param($enr_stmt, "i", $student_id);
    mysqli_stmt_execute($enr_stmt);
    $enrollment = mysqli_fetch_assoc(mysqli_stmt_get_result($enr_stmt));
    mysqli_stmt_close($enr_stmt);
}

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
                        <h1 class="h3 mb-1 fw-bold">Current Term Enrollment</h1>
                        <p class="text-muted small mb-0">Official enrollment standing for the active semester.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="academic-records.php" class="btn btn-outline-secondary">
                            <i class="bi bi-clock-history me-1"></i> Academic History
                        </a>
                        <?php if ($enrollment): ?>
                            <button onclick="window.print()" class="btn btn-outline-primary">
                                <i class="bi bi-printer me-1"></i> Print Verification
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <?php if (!$active_semester): ?>
                    <div class="alert alert-warning border-0 shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
                        No academic term is currently marked as active.
                    </div>
                <?php elseif (!$enrollment): ?>
                    <div class="card border-0 shadow-sm text-center py-5">
                        <div class="card-body">
                            <i class="bi bi-journal-x fs-1 text-muted d-block mb-3"></i>
                            <h4 class="fw-bold mb-2">Not Enrolled for the Active Term</h4>
                            <p class="text-muted mb-4 max-w-md mx-auto">
                                You do not have an active enrollment record for 
                                <strong>AY <?php echo escape($active_semester['year_code']); ?> &mdash; <?php echo escape($active_semester['semester_term']); ?></strong>.
                                Please visit the Registrar's Office to process your term enrollment and course verification.
                            </p>
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="bi bi-house me-1"></i> Return to Dashboard
                            </a>
                        </div>
                    </div>
                <?php else: ?>

                    <!-- Enrollment Status Card -->
                    <div class="card border-0 shadow-sm mb-4 border-start border-success border-4">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <span class="badge bg-success mb-2">
                                        <i class="bi bi-check-circle-fill me-1"></i> Officially <?php echo escape($enrollment['enrollment_status']); ?>
                                    </span>
                                    <h4 class="fw-bold mb-1">
                                        AY <?php echo escape($enrollment['year_code']); ?> &mdash; <?php echo escape($enrollment['semester_term']); ?>
                                    </h4>
                                    <p class="text-muted small mb-0">
                                        <span>Program: <strong><?php echo escape($enrollment['program_name'] . ' (' . $enrollment['program_code'] . ')'); ?></strong></span>
                                        <span class="mx-2">&bull;</span>
                                        <span>Standing: <strong><?php echo escape($enrollment['year_level']); ?></strong></span>
                                    </p>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <div class="bg-light p-3 rounded d-inline-block text-center border">
                                        <div class="small text-muted text-uppercase fw-semibold">Enrollment Date</div>
                                        <div class="fw-bold fs-6 text-dark mt-1"><?php echo format_date($enrollment['enrollment_date']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Details Table -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="card-title mb-0 fw-semibold">
                                <i class="bi bi-card-checklist me-1 text-primary"></i> Term Summary & Verification Details
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-secondary mb-3">Student Particulars</h6>
                                    <dl class="row mb-0 small">
                                        <dt class="col-sm-5 text-muted">Student Number</dt>
                                        <dd class="col-sm-7 fw-bold font-monospace"><?php echo escape($student['student_number']); ?></dd>

                                        <dt class="col-sm-5 text-muted">Student Name</dt>
                                        <dd class="col-sm-7 fw-semibold"><?php echo escape($student['last_name'] . ', ' . $student['first_name'] . ($student['middle_name'] ? ' ' . $student['middle_name'] : '')); ?></dd>

                                        <dt class="col-sm-5 text-muted">Institutional Email</dt>
                                        <dd class="col-sm-7"><?php echo escape($student['email']); ?></dd>

                                        <dt class="col-sm-5 text-muted">Institutional Standing</dt>
                                        <dd class="col-sm-7"><span class="badge bg-success-subtle text-success"><?php echo escape($student['current_status']); ?></span></dd>
                                    </dl>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="fw-bold text-secondary mb-3">Academic Classification</h6>
                                    <dl class="row mb-0 small">
                                        <dt class="col-sm-5 text-muted">Academic Term</dt>
                                        <dd class="col-sm-7">AY <?php echo escape($enrollment['year_code']); ?> &mdash; <?php echo escape($enrollment['semester_term']); ?></dd>

                                        <dt class="col-sm-5 text-muted">Year Classification</dt>
                                        <dd class="col-sm-7"><?php echo escape($enrollment['year_level']); ?></dd>

                                        <dt class="col-sm-5 text-muted">Registration Date</dt>
                                        <dd class="col-sm-7"><?php echo format_date($enrollment['enrollment_date']); ?></dd>

                                        <dt class="col-sm-5 text-muted">Academic Remarks</dt>
                                        <dd class="col-sm-7"><?php echo !empty($enrollment['remarks']) ? escape($enrollment['remarks']) : '<span class="text-muted fst-italic">Regular status</span>'; ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </main>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
