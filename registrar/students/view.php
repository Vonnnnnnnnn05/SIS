<?php
/**
 * Detailed Student Profile View (Registrar & Administrator)
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
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    set_flash_message('danger', 'Invalid student identifier.');
    redirect(APP_URL . '/registrar/students/index.php');
}

// Fetch student biographical & program record
$sql = "SELECT s.*, p.program_code, p.program_name, p.department, u.username as account_username, u.status as account_status 
        FROM students s 
        LEFT JOIN programs p ON s.program_id = p.program_id 
        LEFT JOIN users u ON s.user_id = u.user_id 
        WHERE s.student_id = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$student) {
    set_flash_message('danger', 'Student profile record not found.');
    redirect(APP_URL . '/registrar/students/index.php');
}

// Fetch enrollment history for this student
$hist_sql = "SELECT e.*, ay.year_code, sem.semester_term, p.program_code, u.username as processed_by 
             FROM enrollments e 
             INNER JOIN semesters sem ON e.semester_id = sem.semester_id 
             INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id 
             INNER JOIN programs p ON e.program_id = p.program_id 
             LEFT JOIN users u ON e.created_by = u.user_id 
             WHERE e.student_id = ? 
             ORDER BY e.created_at DESC";

$hist_stmt = mysqli_prepare($conn, $hist_sql);
mysqli_stmt_bind_param($hist_stmt, "i", $student_id);
mysqli_stmt_execute($hist_stmt);
$enrollments_res = mysqli_stmt_get_result($hist_stmt);

$page_title = 'Student Profile: ' . $student['student_number'];
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content-body">
            <div class="container-fluid px-0">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                    <div class="d-flex align-items-center">
                        <a href="index.php" class="btn btn-sm btn-outline-secondary me-3">
                            <i class="bi bi-arrow-left"></i> Directory
                        </a>
                        <div>
                            <h1 class="h4 mb-0 fw-bold">
                                <?php echo escape($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']); ?>
                            </h1>
                            <span class="text-primary font-monospace fw-semibold small"><?php echo escape($student['student_number']); ?></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="edit.php?id=<?php echo $student['student_id']; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-pencil-square me-1"></i> Edit Profile
                        </a>
                        <a href="<?php echo APP_URL; ?>/registrar/enrollment/enroll.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-primary">
                            <i class="bi bi-journal-plus me-1"></i> Enroll in Term
                        </a>
                        <form method="POST" action="<?php echo APP_URL; ?>/process/students/delete-student-process.php" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete student profile <?php echo escape($student['student_number']); ?>? This action cannot be undone.');">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="student_id" value="<?php echo (int)$student['student_id']; ?>">
                            <button type="submit" class="btn btn-outline-danger" title="Delete Student Record">
                                <i class="bi bi-trash-fill me-1"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>

                <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

                <div class="row g-4">
                    <!-- Left: Profile Summary & Academic Standing -->
                    <div class="col-lg-4">
                        <div class="sis-card p-4 text-center mb-4">
                            <div class="stat-icon-wrapper stat-icon-blue mx-auto mb-3" style="width: 76px; height: 76px;">
                                <i class="bi bi-person-fill fs-1"></i>
                            </div>
                            <h2 class="h5 fw-bold mb-1">
                                <?php echo escape($student['first_name'] . ' ' . $student['last_name']); ?>
                            </h2>
                            <p class="text-muted small mb-2"><?php echo escape($student['program_name']); ?></p>

                            <div class="mb-3">
                                <?php
                                $badge_class = 'badge-active';
                                if ($student['current_status'] === 'Inactive') $badge_class = 'badge-inactive';
                                elseif ($student['current_status'] === 'Graduated') $badge_class = 'badge-completed';
                                elseif ($student['current_status'] === 'Withdrawn') $badge_class = 'badge-withdrawn';
                                ?>
                                <span class="badge badge-status <?php echo $badge_class; ?> fs-6">
                                    <?php echo escape($student['current_status']); ?>
                                </span>
                            </div>

                            <!-- Institutional Status Transition Form -->
                            <form action="<?php echo APP_URL; ?>/process/students/update-status-process.php" method="POST" class="mt-3 pt-3 border-top text-start">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                <label for="current_status" class="form-label small fw-semibold">Update Institutional Standing</label>
                                <div class="input-group input-group-sm">
                                    <select name="current_status" id="current_status" class="form-select">
                                        <option value="Active" <?php echo $student['current_status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo $student['current_status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="Graduated" <?php echo $student['current_status'] === 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                                        <option value="Withdrawn" <?php echo $student['current_status'] === 'Withdrawn' ? 'selected' : ''; ?>>Withdrawn</option>
                                    </select>
                                    <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Update student status?');">
                                        Update
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="sis-card p-4">
                            <h3 class="h6 fw-bold mb-3 border-bottom pb-2">User Account Status</h3>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Username:</span>
                                <span class="fw-semibold small"><?php echo escape($student['account_username'] ?? 'Not Provisioned'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Account Status:</span>
                                <span class="small fw-semibold"><?php echo escape($student['account_status'] ?? 'None'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Registration Date:</span>
                                <span class="small"><?php echo format_date($student['created_at']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Detailed Information Tabs & Enrollment History -->
                    <div class="col-lg-8">
                        <div class="sis-card">
                            <div class="sis-card-header">
                                <span class="fw-bold"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Biographical & Contact Information</span>
                            </div>
                            <div class="sis-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="small text-muted">First Name</div>
                                        <div class="fw-semibold"><?php echo escape($student['first_name']); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="small text-muted">Middle Name</div>
                                        <div class="fw-semibold"><?php echo escape($student['middle_name'] ?: 'None'); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="small text-muted">Last Name</div>
                                        <div class="fw-semibold"><?php echo escape($student['last_name']); ?></div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="small text-muted">Date of Birth</div>
                                        <div><?php echo format_date($student['date_of_birth']); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="small text-muted">Gender</div>
                                        <div><?php echo escape($student['gender']); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="small text-muted">Civil Status</div>
                                        <div><?php echo escape($student['civil_status']); ?></div>
                                    </div>

                                    <div class="col-md-6 border-top pt-2">
                                        <div class="small text-muted">Email Address</div>
                                        <div><i class="bi bi-envelope me-1 text-muted"></i><?php echo escape($student['email']); ?></div>
                                    </div>
                                    <div class="col-md-6 border-top pt-2">
                                        <div class="small text-muted">Mobile Number</div>
                                        <div><i class="bi bi-telephone me-1 text-muted"></i><?php echo escape($student['contact_number']); ?></div>
                                    </div>

                                    <div class="col-12 border-top pt-2">
                                        <div class="small text-muted">Residential Address</div>
                                        <div><i class="bi bi-geo-alt me-1 text-muted"></i><?php echo escape($student['home_address']); ?></div>
                                    </div>

                                    <div class="col-md-6 border-top pt-2">
                                        <div class="small text-muted">Emergency Contact Name</div>
                                        <div class="fw-semibold"><?php echo escape($student['emergency_contact_name']); ?></div>
                                    </div>
                                    <div class="col-md-6 border-top pt-2">
                                        <div class="small text-muted">Emergency Contact Phone</div>
                                        <div><?php echo escape($student['emergency_contact_number']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enrollment History -->
                        <div class="sis-card">
                            <div class="sis-card-header">
                                <span class="fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Academic Enrollment History</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table sis-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Term</th>
                                            <th>Year Level</th>
                                            <th>Program</th>
                                            <th>Date Enrolled</th>
                                            <th>Status</th>
                                            <th>Processed By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($enrollments_res) > 0): ?>
                                            <?php while ($e = mysqli_fetch_assoc($enrollments_res)): ?>
                                                <tr>
                                                    <td class="fw-semibold">
                                                        <?php echo escape($e['year_code'] . ' - ' . $e['semester_term']); ?>
                                                    </td>
                                                    <td><?php echo escape($e['year_level']); ?></td>
                                                    <td><span class="badge bg-light text-dark border"><?php echo escape($e['program_code']); ?></span></td>
                                                    <td class="small"><?php echo format_date($e['enrollment_date']); ?></td>
                                                    <td>
                                                        <span class="badge badge-status <?php echo ($e['enrollment_status'] === 'Enrolled') ? 'badge-active' : 'badge-pending'; ?>">
                                                            <?php echo escape($e['enrollment_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-muted small"><?php echo escape($e['processed_by'] ?? 'Staff'); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    No term enrollments recorded for this student yet.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
<?php mysqli_stmt_close($hist_stmt); ?>
