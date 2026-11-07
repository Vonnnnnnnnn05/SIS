<?php
/**
 * Create Academic Term Form (Administrator Tier)
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
$page_title = 'Add Academic Term';

// Fetch existing academic years
$ay_res = mysqli_query($conn, "SELECT ay_id, year_code, is_current FROM academic_years ORDER BY start_year DESC");
$all_ays = mysqli_fetch_all($ay_res, MYSQLI_ASSOC);

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
                        <i class="bi bi-arrow-left"></i> Back to Terms
                    </a>
                    <h1 class="h4 mb-0 fw-bold">Configure Academic Term</h1>
                </div>

                <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="sis-card p-4">
                            <form action="<?php echo APP_URL; ?>/process/academic-years/create-term-process.php" method="POST" autocomplete="off">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="action" value="create_term">

                                <h2 class="h6 fw-bold text-primary text-uppercase mb-3">Academic Year Assignment</h2>
                                <div class="mb-4">
                                    <label for="ay_id" class="form-label small fw-semibold">Select Existing Academic Year</label>
                                    <select class="form-select" id="ay_id" name="ay_id">
                                        <option value="0">-- Or create a new academic year below --</option>
                                        <?php foreach ($all_ays as $ay): ?>
                                            <option value="<?php echo $ay['ay_id']; ?>" <?php echo $ay['is_current'] ? 'selected' : ''; ?>>
                                                <?php echo escape($ay['year_code']); ?> <?php echo $ay['is_current'] ? '(Current)' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="p-3 bg-light rounded border mb-4">
                                    <h3 class="h6 fw-bold mb-2">Create New Academic Year (Optional)</h3>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="new_start_year" class="form-label small fw-semibold">Start Year</label>
                                            <input type="number" class="form-control form-control-sm" id="new_start_year" name="new_start_year" placeholder="e.g. 2027" min="2020" max="2050">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="new_end_year" class="form-label small fw-semibold">End Year</label>
                                            <input type="number" class="form-control form-control-sm" id="new_end_year" name="new_end_year" placeholder="e.g. 2028" min="2020" max="2050">
                                        </div>
                                    </div>
                                </div>

                                <h2 class="h6 fw-bold text-primary text-uppercase mb-3">Semester Term Details</h2>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="semester_term" class="form-label small fw-semibold">Semester Term <span class="text-danger">*</span></label>
                                        <select class="form-select" id="semester_term" name="semester_term" required>
                                            <option value="1st Semester">1st Semester</option>
                                            <option value="2nd Semester">2nd Semester</option>
                                            <option value="Summer">Summer</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-center pt-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="set_active" name="set_active" value="1">
                                            <label class="form-check-label small fw-semibold" for="set_active">
                                                Set as active institutional enrollment semester
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label small fw-semibold">Term Start Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_date" class="form-label small fw-semibold">Term End Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i> Save Term Configuration
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
