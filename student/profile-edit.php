<?php
/**
 * Student Edit Contact Profile Form
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
$page_title = 'Update Contact Information';
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

if (!$student) {
    set_flash_message('danger', 'Student record not found.');
    redirect(APP_URL . '/student/dashboard.php');
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
                        <h1 class="h3 mb-1 fw-bold">Update Contact Information</h1>
                        <p class="text-muted small mb-0">Keep your personal contact details and emergency contact up to date.</p>
                    </div>
                    <div>
                        <a href="profile.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to Profile
                        </a>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="card-title mb-0 fw-semibold">
                                    <i class="bi bi-pencil-square me-1 text-primary"></i> Editable Contact Fields
                                </h6>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST" action="<?php echo APP_URL; ?>/process/students/update-own-profile-process.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                                    <!-- Read-Only Identity Card -->
                                    <div class="p-3 bg-light rounded border mb-4">
                                        <div class="row g-2 small">
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">Student Number (Immutable)</span>
                                                <span class="fw-bold font-monospace"><?php echo escape($student['student_number']); ?></span>
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">Student Name (Immutable)</span>
                                                <span class="fw-semibold"><?php echo escape($student['last_name'] . ', ' . $student['first_name'] . ($student['middle_name'] ? ' ' . $student['middle_name'] : '')); ?></span>
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">Program</span>
                                                <span><?php echo escape($student['program_code'] . ' - ' . $student['program_name']); ?></span>
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted d-block">Official Email</span>
                                                <span><?php echo escape($student['email']); ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact Number -->
                                    <div class="mb-3">
                                        <label for="contact_number" class="form-label fw-semibold">
                                            Mobile / Contact Number <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="contact_number" id="contact_number" class="form-control" 
                                               value="<?php echo escape($student['contact_number']); ?>" required maxlength="25">
                                        <div class="form-text">e.g. 0917-123-4567 or +63 917 123 4567</div>
                                    </div>

                                    <!-- Home Address -->
                                    <div class="mb-4">
                                        <label for="home_address" class="form-label fw-semibold">
                                            Home / Residential Address <span class="text-danger">*</span>
                                        </label>
                                        <textarea name="home_address" id="home_address" rows="3" class="form-control" required><?php echo escape($student['home_address']); ?></textarea>
                                        <div class="form-text">Street, Barangay, Municipality/City, Province, ZIP Code.</div>
                                    </div>

                                    <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2">
                                        <i class="bi bi-shield-exclamation me-1"></i> Emergency Contact Details
                                    </h6>

                                    <div class="row">
                                        <!-- Emergency Contact Name -->
                                        <div class="col-md-6 mb-3">
                                            <label for="emergency_contact_name" class="form-label fw-semibold">
                                                Contact Person Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="emergency_contact_name" id="emergency_contact_name" class="form-control" 
                                                   value="<?php echo escape($student['emergency_contact_name']); ?>" required maxlength="100">
                                            <div class="form-text">Parent, guardian, or immediate relative.</div>
                                        </div>

                                        <!-- Emergency Contact Phone -->
                                        <div class="col-md-6 mb-4">
                                            <label for="emergency_contact_number" class="form-label fw-semibold">
                                                Contact Person Phone <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="emergency_contact_number" id="emergency_contact_number" class="form-control" 
                                                   value="<?php echo escape($student['emergency_contact_number']); ?>" required maxlength="25">
                                            <div class="form-text">Active phone number reachable during emergencies.</div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="profile.php" class="btn btn-outline-secondary">
                                            Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bi bi-save me-1"></i> Save Contact Details
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="card-title mb-0 fw-semibold">
                                    <i class="bi bi-info-circle me-1 text-primary"></i> Policy Guidelines
                                </h6>
                            </div>
                            <div class="card-body small text-muted">
                                <p>Students are strictly permitted to update their own contact information and emergency contact records.</p>
                                <p>If your legal name, date of birth, civil status, degree program, or official email address requires correction, you must file a formal Change of Information form at the Registrar's Office with supporting civil documents.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
