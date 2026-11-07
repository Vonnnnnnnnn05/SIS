<?php
/**
 * Register New Student Form (Registrar & Administrator)
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
$page_title = 'Register New Student';

// Auto-generate next Student ID preview (YYYY-NNNNN)
$current_year = date('Y');
$stmt_seq = mysqli_prepare($conn, "SELECT student_number FROM students WHERE student_number LIKE ? ORDER BY student_number DESC LIMIT 1");
$like_year = $current_year . '-%';
mysqli_stmt_bind_param($stmt_seq, "s", $like_year);
mysqli_stmt_execute($stmt_seq);
$seq_res = mysqli_stmt_get_result($stmt_seq);
$latest = mysqli_fetch_assoc($seq_res);
mysqli_stmt_close($stmt_seq);

$next_seq = 1;
if ($latest && preg_match('/^[0-9]{4}-([0-9]{5})$/', $latest['student_number'], $matches)) {
    $next_seq = (int)$matches[1] + 1;
}
$auto_student_number = sprintf('%s-%05d', $current_year, $next_seq);

// Fetch active programs
$prog_res = mysqli_query($conn, "SELECT program_id, program_code, program_name FROM programs WHERE status = 'Active' ORDER BY program_code ASC");
$all_programs = mysqli_fetch_all($prog_res, MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content-body">
            <div class="container-fluid px-0">
                <div class="d-flex align-items-center mb-4">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left"></i> Directory
                    </a>
                    <div>
                        <h1 class="h4 mb-0 fw-bold">New Student Registration</h1>
                        <p class="text-muted small mb-0">Record admission details and automatically provision student portal access.</p>
                    </div>
                </div>

                <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="sis-card p-4">
                            <form action="<?php echo APP_URL; ?>/process/students/register-process.php" method="POST" autocomplete="off">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                                <!-- Section 1: Identification & Program -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h2 class="h6 fw-bold text-primary text-uppercase mb-0">1. Student Identification & Academic Program</h2>
                                    <span class="badge bg-light text-muted border small">Auto-Provisioning Enabled</span>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="student_number" class="form-label small fw-semibold">Student Number <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-hash"></i></span>
                                            <input type="text" class="form-control font-monospace" id="student_number" name="student_number" value="<?php echo escape($auto_student_number); ?>" required>
                                        </div>
                                        <div class="form-text small">Auto-generated format: <code>YYYY-NNNNN</code>. May be modified for transfer students.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="program_id" class="form-label small fw-semibold">Enrolling Degree Program <span class="text-danger">*</span></label>
                                        <select class="form-select" id="program_id" name="program_id" required>
                                            <option value="">Select Degree Program...</option>
                                            <?php foreach ($all_programs as $p): ?>
                                                <option value="<?php echo $p['program_id']; ?>">
                                                    <?php echo escape($p['program_code']); ?> - <?php echo escape($p['program_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Section 2: Biographical Details -->
                                <h2 class="h6 fw-bold text-primary text-uppercase mb-3">2. Biographical Details</h2>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label for="first_name" class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Given name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="middle_name" class="form-label small fw-semibold">Middle Name</label>
                                        <input type="text" class="form-control" id="middle_name" name="middle_name" placeholder="Middle name (optional)">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="last_name" class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Surname" required>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label for="date_of_birth" class="form-label small fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="gender" class="form-label small fw-semibold">Gender <span class="text-danger">*</span></label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="">Select...</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="civil_status" class="form-label small fw-semibold">Civil Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="civil_status" name="civil_status" required>
                                            <option value="Single" selected>Single</option>
                                            <option value="Married">Married</option>
                                            <option value="Widowed">Widowed</option>
                                            <option value="Separated">Separated</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Section 3: Contact & Emergency -->
                                <h2 class="h6 fw-bold text-primary text-uppercase mb-3">3. Contact & Emergency Details</h2>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="student@example.com" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contact_number" class="form-label small fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-telephone"></i></span>
                                            <input type="text" class="form-control" id="contact_number" name="contact_number" placeholder="0917XXXXXXX" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="home_address" class="form-label small fw-semibold">Permanent Home Address <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="home_address" name="home_address" rows="2" placeholder="House/Street, Barangay, City/Municipality, Province" required></textarea>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="emergency_contact_name" class="form-label small fw-semibold">Emergency Contact Person <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" placeholder="Full name of guardian / parent" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="emergency_contact_number" class="form-label small fw-semibold">Emergency Contact Mobile <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="emergency_contact_number" name="emergency_contact_number" placeholder="Emergency phone" required>
                                    </div>
                                </div>

                                <!-- Security Notice Box -->
                                <div class="p-3 bg-light rounded border mb-4">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-shield-lock-fill text-primary fs-4 me-3"></i>
                                        <div class="small">
                                            <strong>User Account Auto-Creation:</strong> A student login account will be created automatically using the Student Number as username and initial default password <code>Student@<?php echo $current_year; ?></code>. The student will be prompted to change their password on first login.
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-check-circle-fill me-1"></i> Register Student & Create Account
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
