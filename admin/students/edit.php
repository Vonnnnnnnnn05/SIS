<?php
/**
 * Edit Student Profile Form (Administrator Tier)
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('Administrator');

$conn = db_connect();
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    set_flash_message('danger', 'Invalid student record specified.');
    redirect(APP_URL . '/admin/students/index.php');
}

// Fetch student
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE student_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$student) {
    set_flash_message('danger', 'Student record not found.');
    redirect(APP_URL . '/admin/students/index.php');
}

// Fetch active programs
$prog_res = mysqli_query($conn, "SELECT program_id, program_code, program_name FROM programs WHERE status = 'Active' ORDER BY program_code ASC");
$all_programs = mysqli_fetch_all($prog_res, MYSQLI_ASSOC);

$page_title = 'Edit Student Profile - ' . $student['student_number'];
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content-body">
            <div class="container-fluid px-0">
                <div class="d-flex align-items-center mb-4">
                    <a href="view.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left"></i> Back to Profile
                    </a>
                    <h1 class="h4 mb-0 fw-bold">Edit Student: <?php echo escape($student['student_number']); ?></h1>
                </div>

                <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="sis-card p-4">
                            <form action="<?php echo APP_URL; ?>/process/students/update-student-process.php" method="POST" autocomplete="off">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">

                                <h2 class="h6 fw-bold text-primary text-uppercase mb-3">1. Academic & Identification</h2>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="student_number" class="form-label small fw-semibold">Student Number</label>
                                        <input type="text" class="form-control bg-light font-monospace" id="student_number" value="<?php echo escape($student['student_number']); ?>" readonly>
                                        <div class="form-text small">Student numbers are immutable once registered.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="program_id" class="form-label small fw-semibold">Degree Program <span class="text-danger">*</span></label>
                                        <select class="form-select" id="program_id" name="program_id" required>
                                            <?php foreach ($all_programs as $p): ?>
                                                <option value="<?php echo $p['program_id']; ?>" <?php echo $student['program_id'] == $p['program_id'] ? 'selected' : ''; ?>>
                                                    <?php echo escape($p['program_code']); ?> - <?php echo escape($p['program_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <h2 class="h6 fw-bold text-primary text-uppercase mb-3">2. Biographical Details</h2>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label for="first_name" class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo escape($student['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="middle_name" class="form-label small fw-semibold">Middle Name</label>
                                        <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo escape($student['middle_name']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="last_name" class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo escape($student['last_name']); ?>" required>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label for="date_of_birth" class="form-label small fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo escape($student['date_of_birth']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="gender" class="form-label small fw-semibold">Gender <span class="text-danger">*</span></label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="Male" <?php echo $student['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $student['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo $student['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="civil_status" class="form-label small fw-semibold">Civil Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="civil_status" name="civil_status" required>
                                            <option value="Single" <?php echo $student['civil_status'] === 'Single' ? 'selected' : ''; ?>>Single</option>
                                            <option value="Married" <?php echo $student['civil_status'] === 'Married' ? 'selected' : ''; ?>>Married</option>
                                            <option value="Widowed" <?php echo $student['civil_status'] === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                            <option value="Separated" <?php echo $student['civil_status'] === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                                        </select>
                                    </div>
                                </div>

                                <h2 class="h6 fw-bold text-primary text-uppercase mb-3">3. Contact & Emergency Details</h2>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($student['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contact_number" class="form-label small fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo escape($student['contact_number']); ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="home_address" class="form-label small fw-semibold">Residential Address <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="home_address" name="home_address" rows="2" required><?php echo escape($student['home_address']); ?></textarea>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="emergency_contact_name" class="form-label small fw-semibold">Emergency Contact Person <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo escape($student['emergency_contact_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="emergency_contact_number" class="form-label small fw-semibold">Emergency Contact Phone <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="emergency_contact_number" name="emergency_contact_number" value="<?php echo escape($student['emergency_contact_number']); ?>" required>
                                    </div>
                                </div>

                                <h2 class="h6 fw-bold text-primary text-uppercase mb-3">4. Student Portal Account & Password</h2>
                                <div class="p-3 bg-light rounded border mb-4">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="new_password" class="form-label small fw-semibold">Reset Password (Optional)</label>
                                            <input type="password" class="form-control form-control-sm" id="new_password" name="new_password" minlength="8" placeholder="Leave blank to keep existing password">
                                            <div class="form-text small">Enter at least 8 characters to reset the student's portal password.</div>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-center pt-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="must_change_password" name="must_change_password" value="1" checked>
                                                <label class="form-check-label small" for="must_change_password">
                                                    Require student to change password on next login
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                    <a href="view.php?id=<?php echo $student['student_id']; ?>" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
