<?php
/**
 * Student Portal Dashboard
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Student');

$conn = db_connect();
$page_title = 'Student Dashboard';
$user = current_user();

// Resolve student ID from session (strict IDOR protection)
$student_id = (int)($_SESSION['student_id'] ?? 0);
if ($student_id <= 0) {
    $lookup_stmt = mysqli_prepare($conn, "SELECT student_id, student_number FROM students WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($lookup_stmt, "i", $user['user_id']);
    mysqli_stmt_execute($lookup_stmt);
    $st_res = mysqli_stmt_get_result($lookup_stmt);
    if ($st_row = mysqli_fetch_assoc($st_res)) {
        $student_id = (int)$st_row['student_id'];
        $_SESSION['student_id'] = $student_id;
        $_SESSION['student_number'] = $st_row['student_number'];
    }
    mysqli_stmt_close($lookup_stmt);
}

// Fetch student profile
$student = null;
if ($student_id > 0) {
    $st_stmt = mysqli_prepare($conn, "SELECT s.*, p.program_code, p.program_name 
                                      FROM students s 
                                      LEFT JOIN programs p ON s.program_id = p.program_id 
                                      WHERE s.student_id = ?");
    mysqli_stmt_bind_param($st_stmt, "i", $student_id);
    mysqli_stmt_execute($st_stmt);
    $student = mysqli_fetch_assoc(mysqli_stmt_get_result($st_stmt));
    mysqli_stmt_close($st_stmt);
}

// Fetch active semester details
$active_sem_res = mysqli_query($conn, "SELECT sem.semester_id, sem.semester_term, sem.start_date, sem.end_date, ay.year_code 
                                       FROM semesters sem 
                                       INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id 
                                       WHERE sem.is_active = 1 
                                       LIMIT 1");
$active_semester = mysqli_fetch_assoc($active_sem_res);

// Fetch student enrollment for the active semester
$current_enrollment = null;
if ($student_id > 0 && $active_semester) {
    $enr_stmt = mysqli_prepare($conn, "SELECT e.*, p.program_code, p.program_name 
                                       FROM enrollments e 
                                       INNER JOIN programs p ON e.program_id = p.program_id 
                                       WHERE e.student_id = ? AND e.semester_id = ?");
    mysqli_stmt_bind_param($enr_stmt, "ii", $student_id, $active_semester['semester_id']);
    mysqli_stmt_execute($enr_stmt);
    $current_enrollment = mysqli_fetch_assoc(mysqli_stmt_get_result($enr_stmt));
    mysqli_stmt_close($enr_stmt);
}

// Fetch total terms enrolled count
$total_terms_count = 0;
if ($student_id > 0) {
    $terms_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM enrollments WHERE student_id = ?");
    mysqli_stmt_bind_param($terms_stmt, "i", $student_id);
    mysqli_stmt_execute($terms_stmt);
    $total_terms_count = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($terms_stmt))['total'] ?? 0);
    mysqli_stmt_close($terms_stmt);
}

// Fetch recent term enrollments (latest 3)
$recent_enrollments = [];
if ($student_id > 0) {
    $hist_sql = "SELECT e.enrollment_id, e.year_level, e.enrollment_date, e.enrollment_status, e.remarks,
                        sem.semester_term, sem.is_active as is_term_active,
                        ay.year_code, p.program_code
                 FROM enrollments e
                 INNER JOIN semesters sem ON e.semester_id = sem.semester_id
                 INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id
                 INNER JOIN programs p ON e.program_id = p.program_id
                 WHERE e.student_id = ?
                 ORDER BY ay.start_year DESC, sem.semester_term DESC
                 LIMIT 3";
    $hist_stmt = mysqli_prepare($conn, $hist_sql);
    mysqli_stmt_bind_param($hist_stmt, "i", $student_id);
    mysqli_stmt_execute($hist_stmt);
    $hist_res = mysqli_stmt_get_result($hist_stmt);
    while ($row = mysqli_fetch_assoc($hist_res)) {
        $recent_enrollments[] = $row;
    }
    mysqli_stmt_close($hist_stmt);
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
                        <h1 class="h3 mb-1 fw-bold">Student Portal</h1>
                        <p class="text-muted small mb-0">Welcome back, <?php echo escape($student ? $student['first_name'] : $user['username']); ?>!</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="profile.php" class="btn btn-outline-secondary">
                            <i class="bi bi-person-vcard me-1"></i> View Profile
                        </a>
                        <a href="enrollment.php" class="btn btn-primary">
                            <i class="bi bi-journal-text me-1"></i> Current Enrollment
                        </a>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <?php if (!$student): ?>
                    <div class="alert alert-warning border-0 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
                        No student record is linked to this user account. Please contact the Registrar's Office.
                    </div>
                <?php else: ?>

                    <!-- Student Summary Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-success me-2"><?php echo escape($student['current_status']); ?></span>
                                        <span class="font-monospace text-muted small"><?php echo escape($student['student_number']); ?></span>
                                    </div>
                                    <h3 class="fw-bold mb-1">
                                        <?php echo escape($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']); ?>
                                    </h3>
                                    <p class="text-muted small mb-0">
                                        <i class="bi bi-book me-1"></i> <?php echo escape($student['program_code'] . ' - ' . $student['program_name']); ?>
                                        <span class="mx-2">&bull;</span>
                                        <i class="bi bi-envelope me-1"></i> <?php echo escape($student['email']); ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <div class="bg-light p-3 rounded d-inline-block text-center border">
                                        <div class="fs-4 fw-bold text-primary"><?php echo $total_terms_count; ?></div>
                                        <small class="text-muted text-uppercase fw-semibold">Terms Completed / Enrolled</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Term Status Section -->
                    <div class="row g-3 mb-4">
                        <!-- Current Term Banner -->
                        <div class="col-md-8">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-white py-3 border-bottom">
                                    <h6 class="card-title mb-0 fw-semibold">
                                        <i class="bi bi-calendar-event me-1 text-primary"></i> Current Academic Term
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <?php if ($active_semester): ?>
                                        <h5 class="fw-bold mb-1">
                                            Academic Year <?php echo escape($active_semester['year_code']); ?> &mdash; <?php echo escape($active_semester['semester_term']); ?>
                                        </h5>
                                        <p class="text-muted small mb-3">
                                            Term Duration: <?php echo !empty($active_semester['start_date']) ? format_date($active_semester['start_date']) : 'Current'; ?>
                                            to <?php echo !empty($active_semester['end_date']) ? format_date($active_semester['end_date']) : 'Current'; ?>
                                        </p>

                                        <?php if ($current_enrollment): ?>
                                            <div class="p-3 bg-light rounded border">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <small class="text-muted text-uppercase fw-semibold d-block">Enrollment Status</small>
                                                        <?php
                                                        $badge_cls = 'bg-secondary';
                                                        if ($current_enrollment['enrollment_status'] === 'Enrolled') $badge_cls = 'bg-success';
                                                        elseif ($current_enrollment['enrollment_status'] === 'Pending') $badge_cls = 'bg-warning text-dark';
                                                        elseif ($current_enrollment['enrollment_status'] === 'Completed') $badge_cls = 'bg-info text-dark';
                                                        elseif ($current_enrollment['enrollment_status'] === 'Dropped') $badge_cls = 'bg-danger';
                                                        ?>
                                                        <span class="badge <?php echo $badge_cls; ?> fs-6 mt-1">
                                                            <i class="bi bi-check-circle me-1"></i> <?php echo escape($current_enrollment['enrollment_status']); ?>
                                                        </span>
                                                        <span class="ms-2 text-muted small">&bull; <?php echo escape($current_enrollment['year_level']); ?></span>
                                                    </div>
                                                    <div>
                                                        <a href="enrollment.php" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-eye me-1"></i> View Details
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info border-0 mb-0 d-flex align-items-center">
                                                <i class="bi bi-info-circle-fill fs-4 me-3 text-info"></i>
                                                <div>
                                                    You are not currently enrolled in the active semester. Please visit the Registrar's Office to process your term enrollment.
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No active academic term configured at this time.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Portal Actions -->
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-white py-3 border-bottom">
                                    <h6 class="card-title mb-0 fw-semibold">
                                        <i class="bi bi-lightning-charge me-1 text-primary"></i> Quick Actions
                                    </h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="d-grid gap-2">
                                        <a href="profile.php" class="btn btn-outline-primary text-start">
                                            <i class="bi bi-person-lines-fill me-2"></i> View & Update Profile
                                        </a>
                                        <a href="academic-records.php" class="btn btn-outline-secondary text-start">
                                            <i class="bi bi-clock-history me-2"></i> Full Academic History
                                        </a>
                                        <a href="change-password.php" class="btn btn-outline-secondary text-start">
                                            <i class="bi bi-key me-2"></i> Account Security
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Academic Terms Table -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0 fw-semibold">
                                <i class="bi bi-journal-bookmark me-1 text-primary"></i> Academic Terms Overview
                            </h6>
                            <a href="academic-records.php" class="small text-decoration-none">
                                View Full History <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Academic Term</th>
                                        <th>Program & Level</th>
                                        <th>Enrollment Date</th>
                                        <th>Standing</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_enrollments)): ?>
                                        <?php foreach ($recent_enrollments as $enr): ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-semibold">AY <?php echo escape($enr['year_code']); ?></span>
                                                    <div class="small text-muted"><?php echo escape($enr['semester_term']); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?php echo escape($enr['program_code']); ?></span>
                                                    <span class="ms-1 small text-muted"><?php echo escape($enr['year_level']); ?></span>
                                                </td>
                                                <td class="small text-muted">
                                                    <?php echo format_date($enr['enrollment_date']); ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $b_cls = 'bg-secondary';
                                                    if ($enr['enrollment_status'] === 'Enrolled') $b_cls = 'bg-success';
                                                    elseif ($enr['enrollment_status'] === 'Pending') $b_cls = 'bg-warning text-dark';
                                                    elseif ($enr['enrollment_status'] === 'Completed') $b_cls = 'bg-info text-dark';
                                                    elseif ($enr['enrollment_status'] === 'Dropped') $b_cls = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?php echo $b_cls; ?>"><?php echo escape($enr['enrollment_status']); ?></span>
                                                </td>
                                                <td class="small text-muted">
                                                    <?php echo !empty($enr['remarks']) ? escape($enr['remarks']) : '&mdash;'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                No enrollment terms on record yet.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </main>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
