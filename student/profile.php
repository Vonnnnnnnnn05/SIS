<?php
/**
 * Student Personal Profile View
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
$page_title = 'My Profile';
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

$sql = "SELECT s.*, p.program_code, p.program_name 
        FROM students s 
        LEFT JOIN programs p ON s.program_id = p.program_id 
        WHERE s.student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

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
                        <h1 class="h3 mb-1 fw-bold">My Personal Profile</h1>
                        <p class="text-muted small mb-0">Official institutional record and contact details.</p>
                    </div>
                    <div>
                        <a href="profile-edit.php" class="btn btn-primary">
                            <i class="bi bi-pencil-square me-1"></i> Edit Contact Details
                        </a>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <?php if (!$student): ?>
                    <div class="alert alert-warning border-0 shadow-sm" role="alert">
                        No student profile record found linked to your account.
                    </div>
                <?php else: ?>

                    <!-- Profile Overview Card -->
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
                                        <i class="bi bi-mortarboard-fill text-primary me-1"></i>
                                        <?php echo escape($student['program_code'] . ' &mdash; ' . $student['program_name']); ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <span class="badge bg-light text-dark border p-2">
                                        <i class="bi bi-calendar3 me-1"></i> Registered: <?php echo format_date($student['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Left Column: Official Academic & Biographical Info (Locked) -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 fw-semibold">
                                        <i class="bi bi-file-earmark-person me-1 text-primary"></i> Academic & Identity Information
                                    </h6>
                                    <span class="badge bg-light text-muted border">
                                        <i class="bi bi-lock-fill me-1"></i> Official / Read-Only
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-light border small text-muted mb-3 py-2 px-3">
                                        <i class="bi bi-info-circle me-1"></i> Official records can only be updated by the Registrar's Office.
                                    </div>
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5 text-muted small">Student Number</dt>
                                        <dd class="col-sm-7 fw-bold font-monospace"><?php echo escape($student['student_number']); ?></dd>

                                        <dt class="col-sm-5 text-muted small">First Name</dt>
                                        <dd class="col-sm-7"><?php echo escape($student['first_name']); ?></dd>

                                        <dt class="col-sm-5 text-muted small">Middle Name</dt>
                                        <dd class="col-sm-7"><?php echo !empty($student['middle_name']) ? escape($student['middle_name']) : '&mdash;'; ?></dd>

                                        <dt class="col-sm-5 text-muted small">Last Name</dt>
                                        <dd class="col-sm-7"><?php echo escape($student['last_name']); ?></dd>

                                        <dt class="col-sm-5 text-muted small">Date of Birth</dt>
                                        <dd class="col-sm-7"><?php echo format_date($student['date_of_birth']); ?></dd>

                                        <dt class="col-sm-5 text-muted small">Gender</dt>
                                        <dd class="col-sm-7"><?php echo escape($student['gender']); ?></dd>

                                        <dt class="col-sm-5 text-muted small">Civil Status</dt>
                                        <dd class="col-sm-7"><?php echo escape($student['civil_status']); ?></dd>

                                        <dt class="col-sm-5 text-muted small">Degree Program</dt>
                                        <dd class="col-sm-7"><?php echo escape($student['program_name'] . ' (' . $student['program_code'] . ')'); ?></dd>

                                        <dt class="col-sm-5 text-muted small">Official Email</dt>
                                        <dd class="col-sm-7"><?php echo escape($student['email']); ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Editable Contact & Emergency Details -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 fw-semibold">
                                        <i class="bi bi-telephone me-1 text-primary"></i> Contact & Emergency Information
                                    </h6>
                                    <a href="profile-edit.php" class="btn btn-sm btn-outline-primary py-0 px-2">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </a>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5 text-muted small">Mobile / Phone</dt>
                                        <dd class="col-sm-7 fw-semibold"><?php echo escape($student['contact_number']); ?></dd>

                                        <dt class="col-sm-5 text-muted small">Home Address</dt>
                                        <dd class="col-sm-7"><?php echo nl2br(escape($student['home_address'])); ?></dd>

                                        <dt class="col-sm-12 my-2"><hr class="my-1"></dt>

                                        <dt class="col-sm-5 text-muted small">Emergency Contact Person</dt>
                                        <dd class="col-sm-7 fw-semibold"><?php echo escape($student['emergency_contact_name']); ?></dd>

                                        <dt class="col-sm-5 text-muted small">Emergency Phone</dt>
                                        <dd class="col-sm-7 fw-semibold"><?php echo escape($student['emergency_contact_number']); ?></dd>

                                        <dt class="col-sm-5 text-muted small">Last Profile Update</dt>
                                        <dd class="col-sm-7 text-muted small"><?php echo format_date($student['updated_at'], 'M d, Y h:i A'); ?></dd>
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
