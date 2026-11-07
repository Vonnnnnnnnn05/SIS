<?php
/**
 * Create Course / Program Form (Registrar Tier)
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
$page_title = 'Add New Course / Program';

// Fetch distinct departments for suggestions
$dept_res = mysqli_query($conn, "SELECT DISTINCT department FROM programs WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
$all_departments = mysqli_fetch_all($dept_res, MYSQLI_ASSOC);

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
                    <h1 class="h4 mb-0 fw-bold">Add New Course / Degree Program</h1>
                </div>

                <?php display_alerts(); ?>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="sis-card p-4">
                            <form action="<?php echo APP_URL; ?>/process/programs/create-program-process.php" method="POST" autocomplete="off">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label for="program_code" class="form-label small fw-semibold">Course / Program Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control font-monospace text-uppercase" id="program_code" name="program_code" placeholder="e.g. BSIT" maxlength="20" required autofocus>
                                        <div class="form-text small">Unique abbreviation (max 20 characters).</div>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="program_name" class="form-label small fw-semibold">Course / Degree Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="program_name" name="program_name" placeholder="e.g. Bachelor of Science in Information Technology" maxlength="150" required>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-8">
                                        <label for="department" class="form-label small fw-semibold">Academic Department / College <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="department" name="department" list="department_list" placeholder="e.g. College of Computer Studies" maxlength="100" required>
                                        <datalist id="department_list">
                                            <?php foreach ($all_departments as $d): ?>
                                                <option value="<?php echo escape($d['department']); ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                        <div class="form-text small">Select from existing departments or type a new one.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="status" class="form-label small fw-semibold">Initial Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="Active" selected>Active (Available for Enrollment)</option>
                                            <option value="Inactive">Inactive (Archived / Closed)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i> Save Course / Program
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
