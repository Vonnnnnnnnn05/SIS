<?php
/**
 * Enroll Student Form (Registrar & Administrator)
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
$page_title = 'Enroll Student';

// Pre-selected student ID from query param (if any)
$selected_student_id = isset($_GET['student_id']) && is_numeric($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

// Fetch current active semester
$active_sem_sql = "SELECT sem.semester_id, sem.semester_term, sem.start_date, sem.end_date, ay.year_code 
                   FROM semesters sem 
                   INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id 
                   WHERE sem.is_active = 1 
                   LIMIT 1";
$active_sem_res = mysqli_query($conn, $active_sem_sql);
$active_semester = mysqli_fetch_assoc($active_sem_res);

// Fetch all active students who are eligible for enrollment
$students_sql = "SELECT s.student_id, s.student_number, s.first_name, s.middle_name, s.last_name, s.program_id, p.program_code
                 FROM students s
                 LEFT JOIN programs p ON s.program_id = p.program_id
                 WHERE s.current_status = 'Active'
                 ORDER BY s.last_name ASC, s.first_name ASC";
$students_res = mysqli_query($conn, $students_sql);

// Fetch all programs
$programs_res = mysqli_query($conn, "SELECT program_id, program_code, program_name FROM programs ORDER BY program_code ASC");

// If pre-selected student ID, verify they are not already enrolled in the active semester
$already_enrolled = false;
$preselected_program_id = 0;
if ($selected_student_id > 0 && $active_semester) {
    $check_stmt = mysqli_prepare($conn, "SELECT enrollment_id FROM enrollments WHERE student_id = ? AND semester_id = ?");
    mysqli_stmt_bind_param($check_stmt, "ii", $selected_student_id, $active_semester['semester_id']);
    mysqli_stmt_execute($check_stmt);
    $check_res = mysqli_stmt_get_result($check_stmt);
    if (mysqli_num_rows($check_res) > 0) {
        $already_enrolled = true;
    }
    mysqli_stmt_close($check_stmt);

    // Fetch student's assigned program
    $prog_stmt = mysqli_prepare($conn, "SELECT program_id FROM students WHERE student_id = ?");
    mysqli_stmt_bind_param($prog_stmt, "ii", $selected_student_id);
    mysqli_stmt_execute($prog_stmt);
    $prog_res = mysqli_stmt_get_result($prog_stmt);
    if ($st_row = mysqli_fetch_assoc($prog_res)) {
        $preselected_program_id = (int)$st_row['program_id'];
    }
    mysqli_stmt_close($prog_stmt);
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
                        <h1 class="h3 mb-1 fw-bold">Enroll Student in Academic Term</h1>
                        <p class="text-muted small mb-0">Record formal semester enrollment and academic standing for an active student.</p>
                    </div>
                    <div>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to Enrollments
                        </a>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <?php if (!$active_semester): ?>
                    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-warning"></i>
                        <div>
                            <h6 class="alert-heading fw-bold mb-1">No Active Academic Term</h6>
                            <p class="mb-0">There is currently no academic semester marked as active for enrollment. Please activate an academic semester in the Academic Terms management section before processing student enrollments.</p>
                        </div>
                    </div>
                <?php else: ?>

                    <?php if ($already_enrolled): ?>
                        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-info-circle-fill fs-4 me-3 text-info"></i>
                            <div>
                                <strong>Notice:</strong> This student is already enrolled in the active semester (AY <?php echo escape($active_semester['year_code']); ?> &mdash; <?php echo escape($active_semester['semester_term']); ?>).
                                Duplicate enrollment for the same semester is prohibited. You can view or update their standing from the enrollments list.
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white py-3 border-bottom">
                                    <h6 class="card-title mb-0 fw-semibold">
                                        <i class="bi bi-journal-plus me-1 text-primary"></i> Term Enrollment Form
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <form method="POST" action="<?php echo APP_URL; ?>/process/enrollment/enroll-process.php">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="semester_id" value="<?php echo (int)$active_semester['semester_id']; ?>">

                                        <!-- Term Confirmation Banner -->
                                        <div class="p-3 bg-light rounded border mb-4">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge bg-success mb-1">Active Term Confirmed</span>
                                                    <h5 class="fw-bold mb-0">
                                                        AY <?php echo escape($active_semester['year_code']); ?> &mdash; <?php echo escape($active_semester['semester_term']); ?>
                                                    </h5>
                                                    <small class="text-muted">
                                                        Period: <?php echo !empty($active_semester['start_date']) ? format_date($active_semester['start_date']) : 'Current'; ?>
                                                        to <?php echo !empty($active_semester['end_date']) ? format_date($active_semester['end_date']) : 'Current'; ?>
                                                    </small>
                                                </div>
                                                <i class="bi bi-calendar-check fs-1 text-primary"></i>
                                            </div>
                                        </div>

                                        <!-- Student Selection -->
                                        <div class="mb-3">
                                            <label for="student_id" class="form-label fw-semibold">
                                                Select Active Student <span class="text-danger">*</span>
                                            </label>
                                            <select name="student_id" id="student_id" class="form-select" required onchange="onStudentSelect(this)">
                                                <option value="">-- Choose an active student --</option>
                                                <?php if ($students_res): ?>
                                                    <?php while ($st = mysqli_fetch_assoc($students_res)): ?>
                                                        <option value="<?php echo (int)$st['student_id']; ?>" 
                                                                data-program-id="<?php echo (int)$st['program_id']; ?>"
                                                                <?php echo $selected_student_id === (int)$st['student_id'] ? 'selected' : ''; ?>>
                                                            <?php echo escape($st['student_number'] . ' - ' . $st['last_name'] . ', ' . $st['first_name'] . ' (' . $st['program_code'] . ')'); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                            </select>
                                            <div class="form-text">Only students in 'Active' standing appear in this list.</div>
                                        </div>

                                        <div class="row">
                                            <!-- Degree Program -->
                                            <div class="col-md-6 mb-3">
                                                <label for="program_id" class="form-label fw-semibold">
                                                    Degree Program <span class="text-danger">*</span>
                                                </label>
                                                <select name="program_id" id="program_id" class="form-select" required>
                                                    <option value="">-- Select Degree Program --</option>
                                                    <?php 
                                                    mysqli_data_seek($programs_res, 0);
                                                    while ($prog = mysqli_fetch_assoc($programs_res)): 
                                                        $is_sel = ($preselected_program_id === (int)$prog['program_id']);
                                                    ?>
                                                        <option value="<?php echo (int)$prog['program_id']; ?>" <?php echo $is_sel ? 'selected' : ''; ?>>
                                                            <?php echo escape($prog['program_code'] . ' - ' . $prog['program_name']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>

                                            <!-- Year Level -->
                                            <div class="col-md-6 mb-3">
                                                <label for="year_level" class="form-label fw-semibold">
                                                    Year Level <span class="text-danger">*</span>
                                                </label>
                                                <select name="year_level" id="year_level" class="form-select" required>
                                                    <option value="">-- Select Year Level --</option>
                                                    <option value="1st Year">1st Year</option>
                                                    <option value="2nd Year">2nd Year</option>
                                                    <option value="3rd Year">3rd Year</option>
                                                    <option value="4th Year">4th Year</option>
                                                    <option value="5th Year">5th Year</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <!-- Initial Status -->
                                            <div class="col-md-6 mb-3">
                                                <label for="enrollment_status" class="form-label fw-semibold">
                                                    Initial Status <span class="text-danger">*</span>
                                                </label>
                                                <select name="enrollment_status" id="enrollment_status" class="form-select" required>
                                                    <option value="Enrolled" selected>Enrolled (Confirmed)</option>
                                                    <option value="Pending">Pending (Subject to clearance)</option>
                                                </select>
                                            </div>

                                            <!-- Remarks / Notes -->
                                            <div class="col-md-6 mb-3">
                                                <label for="remarks" class="form-label fw-semibold">Remarks / Academic Notes</label>
                                                <input type="text" name="remarks" id="remarks" class="form-control" placeholder="Optional notes (e.g. Regular, Irregular, Transferee)">
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <div class="d-flex justify-content-between align-items-center">
                                            <a href="index.php" class="btn btn-outline-secondary">
                                                Cancel
                                            </a>
                                            <button type="submit" class="btn btn-primary px-4" <?php echo $already_enrolled ? 'disabled' : ''; ?>>
                                                <i class="bi bi-check-circle me-1"></i> Confirm & Enroll Student
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Info & Instructions Column -->
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white py-3 border-bottom">
                                    <h6 class="card-title mb-0 fw-semibold">
                                        <i class="bi bi-shield-check me-1 text-primary"></i> Enrollment Rules
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled small mb-0">
                                        <li class="mb-2 d-flex">
                                            <i class="bi bi-check2 text-success me-2 flex-shrink-0"></i>
                                            <span><strong>Unique Term Enrollment:</strong> A student cannot be enrolled more than once in the same semester.</span>
                                        </li>
                                        <li class="mb-2 d-flex">
                                            <i class="bi bi-check2 text-success me-2 flex-shrink-0"></i>
                                            <span><strong>Active Status:</strong> Inactive, graduated, or withdrawn students are automatically excluded from enrollment eligibility.</span>
                                        </li>
                                        <li class="mb-2 d-flex">
                                            <i class="bi bi-check2 text-success me-2 flex-shrink-0"></i>
                                            <span><strong>Audit Trail:</strong> The registrar staff identity, IP address, and timestamp are permanently recorded upon confirmation.</span>
                                        </li>
                                        <li class="d-flex">
                                            <i class="bi bi-check2 text-success me-2 flex-shrink-0"></i>
                                            <span><strong>Academic Standing:</strong> Status updates (`Enrolled`, `Dropped`, `Completed`) can be managed at any point during the term.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </main>

        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>

<script>
function onStudentSelect(selectElem) {
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    const programId = selectedOption.getAttribute('data-program-id');
    if (programId) {
        const progSelect = document.getElementById('program_id');
        if (progSelect) {
            progSelect.value = programId;
        }
    }
}
</script>
