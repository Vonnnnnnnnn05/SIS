<?php
/**
 * Change Password Interface
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$user = current_user();
$page_title = 'Change Password';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <main class="content-body">
            <div class="container-fluid px-0">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="d-flex align-items-center mb-4">
                            <h1 class="h4 mb-0 fw-bold">Change Password</h1>
                        </div>

                        <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

                        <?php if (!empty($user['must_change_password'])): ?>
                            <div class="alert alert-warning d-flex align-items-center mb-4 shadow-sm" role="alert">
                                <i class="bi bi-shield-exclamation fs-4 me-2"></i>
                                <div>
                                    <strong>Default Password Detected:</strong> For security reasons, you must change your initial password before accessing the system.
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="sis-card p-4">
                            <form action="<?php echo APP_URL; ?>/process/change-password-process.php" method="POST" autocomplete="off">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                                <div class="mb-3">
                                    <label for="current_password" class="form-label small fw-semibold">Current Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-muted"><i class="bi bi-lock"></i></span>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required autofocus>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label small fw-semibold">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-muted"><i class="bi bi-key"></i></span>
                                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                                    </div>
                                    <div class="form-text small">Must be at least 8 characters long.</div>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label small fw-semibold">Confirm New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-muted"><i class="bi bi-key-fill"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-1"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
