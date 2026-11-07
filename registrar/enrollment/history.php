<?php
/**
 * Student Enrollment History (Registrar & Administrator)
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(['Administrator', 'Registrar']);

$conn = db_connect();
$page_title = 'Enrollment History';

$student_id = isset($_GET['student_id']) && is_numeric($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($student_id <= 0) {
    set_flash_message('danger', 'Invalid student identifier.');
    redirect(APP_URL . '/registrar/enrollment/index.php');
}

// Fetch student biographical summary
$st_sql = "SELECT s.student_id, s.student_number, s.first_name, s.middle_name, s.last_name, s.email, 
                  s.contact_number, s.current_status, s.created_at, p.program_code, p.program_name 
           FROM students s 
           LEFT JOIN programs p ON s.program_id = p.program_id 
           WHERE s.student_id = ?";
$st_stmt = mysqli_prepare($conn, $st_sql);
mysqli_stmt_bind_param($st_stmt, "i", $student_id);
mysqli_stmt_execute($st_stmt);
$st_res = mysqli_stmt_get_result($st_stmt);
$student = mysqli_fetch_assoc($st_res);
mysqli_stmt_close($st_stmt);

if (!$student) {
    set_flash_message('danger', 'Student not found.');
    redirect(APP_URL . '/registrar/enrollment/index.php');
}

// Fetch chronological enrollment records
$hist_sql = "SELECT e.enrollment_id, e.year_level, e.enrollment_date, e.enrollment_status, e.remarks, e.created_at,
                    sem.semester_id, sem.semester_term, sem.is_active as is_term_active,
                    ay.year_code, ay.start_year, ay.end_year,
                    p.program_code, p.program_name,
                    u.username as creator_name
             FROM enrollments e
             INNER JOIN semesters sem ON e.semester_id = sem.semester_id
             INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id
             INNER JOIN programs p ON e.program_id = p.program_id
             LEFT JOIN users u ON e.created_by = u.user_id
             WHERE e.student_id = ?
             ORDER BY ay.start_year DESC, sem.semester_term DESC";
$hist_stmt = mysqli_prepare($conn, $hist_sql);
mysqli_stmt_bind_param($hist_stmt, "i", $student_id);
mysqli_stmt_execute($hist_stmt);
$history_res = mysqli_stmt_get_result($hist_stmt);

$total_terms = mysqli_num_rows($history_res);

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content-body">
            <div class="container-fluid px-0">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                    <div>
                        <h1 class="h3 mb-1 fw-bold">Enrollment History</h1>
                        <p class="text-muted small mb-0">Chronological academic progression and term enrollments for student <?php echo escape($student['student_number']); ?>.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="enroll.php?student_id=<?php echo (int)$student['student_id']; ?>" class="btn btn-primary">
                            <i class="bi bi-person-check-fill me-1"></i> Enroll in Active Term
                        </a>
                        <a href="<?php echo APP_URL; ?>/registrar/students/view.php?id=<?php echo (int)$student['student_id']; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-person-badge me-1"></i> Full Profile
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <!-- Student Summary Banner -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <span class="badge bg-secondary mb-2"><?php echo escape($student['current_status']); ?></span>
                                <h4 class="fw-bold mb-1">
                                    <?php echo escape($student['last_name'] . ', ' . $student['first_name'] . ($student['middle_name'] ? ' ' . $student['middle_name'] : '')); ?>
                                </h4>
                                <p class="text-muted small mb-0">
                                    <span class="font-monospace fw-semibold"><?php echo escape($student['student_number']); ?></span>
                                    &bull; <?php echo escape($student['program_code'] . ' - ' . $student['program_name']); ?>
                                    &bull; <?php echo escape($student['email']); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <div class="bg-light p-3 rounded d-inline-block text-center border">
                                    <div class="fs-4 fw-bold text-primary"><?php echo $total_terms; ?></div>
                                    <small class="text-muted text-uppercase fw-semibold">Terms Enrolled</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrollment History Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="card-title mb-0 fw-semibold">
                            <i class="bi bi-clock-history me-1 text-primary"></i> Academic Terms History
                        </h6>
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
                                    <th>Enrolled By</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($total_terms > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($history_res)): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">AY <?php echo escape($row['year_code']); ?></div>
                                                <span class="small text-muted"><?php echo escape($row['semester_term']); ?></span>
                                                <?php if ($row['is_term_active']): ?>
                                                    <span class="badge bg-success-subtle text-success ms-1 small">Active Term</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?php echo escape($row['program_code']); ?></span>
                                                <div class="small text-muted mt-1"><?php echo escape($row['year_level']); ?></div>
                                            </td>
                                            <td class="small text-muted">
                                                <?php echo format_date($row['enrollment_date']); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = 'bg-secondary';
                                                switch ($row['enrollment_status']) {
                                                    case 'Enrolled':
                                                        $badge_class = 'bg-success';
                                                        break;
                                                    case 'Pending':
                                                        $badge_class = 'bg-warning text-dark';
                                                        break;
                                                    case 'Completed':
                                                        $badge_class = 'bg-info text-dark';
                                                        break;
                                                    case 'Dropped':
                                                        $badge_class = 'bg-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo escape($row['enrollment_status']); ?>
                                                </span>
                                            </td>
                                            <td class="small">
                                                <?php echo !empty($row['remarks']) ? escape($row['remarks']) : '<span class="text-muted fst-italic">None</span>'; ?>
                                            </td>
                                            <td class="small text-muted">
                                                <i class="bi bi-person me-1"></i>
                                                <?php echo escape($row['creator_name'] ?? 'System'); ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="edit-status.php?id=<?php echo (int)$row['enrollment_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Update Status">
                                                    <i class="bi bi-pencil-square me-1"></i> Status
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                                            This student has no recorded term enrollments yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>

        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
<?php
mysqli_stmt_close($hist_stmt);
?>
