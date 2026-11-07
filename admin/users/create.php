<?php
/**
 * Create User Account Form (Administrator Tier)
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
$page_title = 'Create User Account';

// Fetch roles for dropdown
$roles_res = mysqli_query($conn, "SELECT role_id, role_name, description FROM roles ORDER BY role_id ASC");
$all_roles = mysqli_fetch_all($roles_res, MYSQLI_ASSOC);

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
                        <i class="bi bi-arrow-left"></i> Back to Users
                    </a>
                    <h1 class="h4 mb-0 fw-bold">Create New User Account</h1>
                </div>

                <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="sis-card p-4">
                            <form action="<?php echo APP_URL; ?>/process/users/create-user-process.php" method="POST" autocomplete="off">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="username" class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="username" name="username" placeholder="e.g. registrar2" required autofocus>
                                        <div class="form-text small">Unique login identifier.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="user@sis.local" required>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label small fw-semibold">Initial Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                                        <div class="form-text small">Minimum 8 characters.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="role_id" class="form-label small fw-semibold">Account Role <span class="text-danger">*</span></label>
                                        <select class="form-select" id="role_id" name="role_id" required>
                                            <option value="">Select Role...</option>
                                            <?php foreach ($all_roles as $r): ?>
                                                <option value="<?php echo $r['role_id']; ?>">
                                                    <?php echo escape($r['role_name']); ?> - <?php echo escape($r['description']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="status" class="form-label small fw-semibold">Initial Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="Active" selected>Active</option>
                                            <option value="Inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-center pt-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="must_change_password" name="must_change_password" value="1" checked>
                                            <label class="form-check-label small" for="must_change_password">
                                                Require password change on first login
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i> Save User Account
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
