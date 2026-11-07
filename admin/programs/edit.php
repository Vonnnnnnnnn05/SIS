<?php
/**
 * Edit Course / Program Form (Administrator Tier)
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
$program_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($program_id <= 0) {
    set_flash_message('danger', 'Invalid course / program specified.');
    redirect(APP_URL . '/admin/programs/index.php');
}

$stmt = mysqli_prepare($conn, "SELECT * FROM programs WHERE program_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $program_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$target_program = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$target_program) {
    set_flash_message('danger', 'Course / Program not found.');
    redirect(APP_URL . '/admin/programs/index.php');
}

// Fetch distinct departments for suggestions
$dept_res = mysqli_query($conn, "SELECT DISTINCT department FROM programs WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
$all_departments = mysqli_fetch_all($dept_res, MYSQLI_ASSOC);

$page_title = 'Edit Course / Program - ' . $target_program['program_code'];
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
                        <i class="bi bi-arrow-left"></i> Back to Courses & Programs
                    </a>
                    <h1 class="h4 mb-0 fw-bold">Edit Course / Program: <?php echo escape($target_program['program_code']); ?></h1>
                </div>

                <?php display_alerts(); ?>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="sis-card p-4">
                            <form action="<?php echo APP_URL; ?>/process/programs/edit-program-process.php" method="POST" autocomplete="off">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="program_id" value="<?php echo (int)$target_program['program_id']; ?>">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label for="program_code" class="form-label small fw-semibold">Course / Program Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control font-monospace text-uppercase" id="program_code" name="program_code" value="<?php echo escape($target_program['program_code']); ?>" maxlength="20" required>
                                        <div class="form-text small">Unique abbreviation (max 20 characters).</div>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="program_name" class="form-label small fw-semibold">Course / Degree Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="program_name" name="program_name" value="<?php echo escape($target_program['program_name']); ?>" maxlength="150" required>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-8">
                                        <label for="department" class="form-label small fw-semibold">Academic Department / College <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="department" name="department" list="department_list" value="<?php echo escape($target_program['department']); ?>" maxlength="100" required>
                                        <datalist id="department_list">
                                            <?php foreach ($all_departments as $d): ?>
                                                <option value="<?php echo escape($d['department']); ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="status" class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="Active" <?php echo $target_program['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo $target_program['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i> Update Course / Program
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
