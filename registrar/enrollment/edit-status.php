<?php
/**
 * Update Enrollment Status Form (Registrar & Administrator)
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
$page_title = 'Update Enrollment Status';

$enrollment_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($enrollment_id <= 0) {
    set_flash_message('danger', 'Invalid enrollment identifier.');
    redirect(APP_URL . '/registrar/enrollment/index.php');
}

$sql = "SELECT e.enrollment_id, e.year_level, e.enrollment_date, e.enrollment_status, e.remarks,
               s.student_id, s.student_number, s.first_name, s.middle_name, s.last_name, s.email,
               p.program_code, p.program_name,
               sem.semester_term, ay.year_code
        FROM enrollments e
        INNER JOIN students s ON e.student_id = s.student_id
        INNER JOIN semesters sem ON e.semester_id = sem.semester_id
        INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id
        INNER JOIN programs p ON e.program_id = p.program_id
        WHERE e.enrollment_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $enrollment_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$enrollment = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$enrollment) {
    set_flash_message('danger', 'Enrollment record not found.');
    redirect(APP_URL . '/registrar/enrollment/index.php');
}

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
                        <h1 class="h3 mb-1 fw-bold">Update Enrollment Status</h1>
                        <p class="text-muted small mb-0">Modify student academic standing for the active term.</p>
                    </div>
                    <div>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to Enrollments
                        </a>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <div class="row">
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="card-title mb-0 fw-semibold">
                                    <i class="bi bi-pencil-square me-1 text-primary"></i> Modify Enrollment Standing
                                </h6>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST" action="<?php echo APP_URL; ?>/process/enrollment/update-status-process.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="enrollment_id" value="<?php echo (int)$enrollment['enrollment_id']; ?>">

                                    <div class="mb-4 p-3 bg-light rounded border">
                                        <div class="row g-2">
                                            <div class="col-sm-6">
                                                <small class="text-muted d-block">Student Number</small>
                                                <span class="fw-bold font-monospace"><?php echo escape($enrollment['student_number']); ?></span>
                                            </div>
                                            <div class="col-sm-6">
                                                <small class="text-muted d-block">Student Name</small>
                                                <span class="fw-semibold"><?php echo escape($enrollment['last_name'] . ', ' . $enrollment['first_name'] . ($enrollment['middle_name'] ? ' ' . substr($enrollment['middle_name'], 0, 1) . '.' : '')); ?></span>
                                            </div>
                                            <div class="col-sm-6">
                                                <small class="text-muted d-block">Program & Year</small>
                                                <span><?php echo escape($enrollment['program_code'] . ' &mdash; ' . $enrollment['year_level']); ?></span>
                                            </div>
                                            <div class="col-sm-6">
                                                <small class="text-muted d-block">Academic Term</small>
                                                <span>AY <?php echo escape($enrollment['year_code'] . ' (' . $enrollment['semester_term'] . ')'); ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="enrollment_status" class="form-label fw-semibold">
                                            Enrollment Status <span class="text-danger">*</span>
                                        </label>
                                        <select name="enrollment_status" id="enrollment_status" class="form-select" required>
                                            <option value="Enrolled" <?php echo $enrollment['enrollment_status'] === 'Enrolled' ? 'selected' : ''; ?>>Enrolled (Active Coursework)</option>
                                            <option value="Pending" <?php echo $enrollment['enrollment_status'] === 'Pending' ? 'selected' : ''; ?>>Pending (Under Evaluation / Conditional)</option>
                                            <option value="Completed" <?php echo $enrollment['enrollment_status'] === 'Completed' ? 'selected' : ''; ?>>Completed (Finished Semester)</option>
                                            <option value="Dropped" <?php echo $enrollment['enrollment_status'] === 'Dropped' ? 'selected' : ''; ?>>Dropped (Withdrawn from Term)</option>
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label for="remarks" class="form-label fw-semibold">Remarks / Academic Justification</label>
                                        <textarea name="remarks" id="remarks" rows="3" class="form-control" placeholder="Provide notes regarding this status change (e.g. Cleared prerequisites, Official withdrawal filed)..."><?php echo escape($enrollment['remarks'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bi bi-save me-1"></i> Save Status Update
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="card-title mb-0 fw-semibold">
                                    <i class="bi bi-info-circle me-1 text-primary"></i> Status Guidelines
                                </h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0 small">
                                    <dt class="col-sm-4 text-success">Enrolled</dt>
                                    <dd class="col-sm-8 text-muted">The student is actively taking subjects for this semester.</dd>

                                    <dt class="col-sm-4 text-warning">Pending</dt>
                                    <dd class="col-sm-8 text-muted">Registration submitted but awaiting prerequisite validation or payment clearance.</dd>

                                    <dt class="col-sm-4 text-info">Completed</dt>
                                    <dd class="col-sm-8 text-muted">The semester has concluded and grades or credits are finalized.</dd>

                                    <dt class="col-sm-4 text-danger">Dropped</dt>
                                    <dd class="col-sm-8 text-muted">The student officially or unofficially ceased attendance for this term.</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
