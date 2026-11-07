<?php
/**
 * User Login Interface
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/config/system.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// If already authenticated, redirect to role router
if (is_logged_in()) {
    redirect(APP_URL . '/index.php');
}

// Notice handling for session expiration or manual logout
if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    set_flash_message('warning', 'Your session has expired due to inactivity. Please log in again.');
}
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    set_flash_message('info', 'You have been successfully logged out.');
}

$page_title = 'Account Login';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container min-vh-100 d-flex flex-column justify-content-center py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="text-center mb-4">
                <div class="stat-icon-wrapper stat-icon-blue mx-auto mb-2" style="width: 58px; height: 58px;">
                    <i class="bi bi-mortarboard-fill fs-2"></i>
                </div>
                <h1 class="h4 fw-bold text-dark mb-1"><?php echo APP_NAME; ?></h1>
                <p class="text-muted small">Academic Management & Record System</p>
            </div>

            <div class="sis-card shadow-sm p-4">
                <h2 class="h5 fw-semibold mb-3 text-center">Sign In</h2>
                
                <?php require_once __DIR__ . '/includes/alerts.php'; ?>

                <form action="<?php echo APP_URL; ?>/process/login-process.php" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                    <div class="mb-3">
                        <label for="username" class="form-label small fw-semibold">Username or Student ID</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="e.g. admin or 2026-00001" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Log In
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
