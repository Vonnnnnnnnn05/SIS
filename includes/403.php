<?php
/**
 * HTTP 403 Forbidden Error View
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$page_title = '403 Forbidden';
require_once __DIR__ . '/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5 text-center">
            <div class="sis-card p-4 p-md-5">
                <div class="stat-icon-wrapper stat-icon-amber mx-auto mb-3" style="width: 72px; height: 72px;">
                    <i class="bi bi-shield-x fs-1"></i>
                </div>
                <h1 class="h3 fw-bold mb-2">Access Forbidden</h1>
                <p class="text-muted mb-4">
                    You do not have the required administrative permissions to access this page or perform this operation.
                </p>
                <div class="d-grid gap-2">
                    <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary">
                        <i class="bi bi-house-door-fill me-1"></i> Return to Dashboard
                    </a>
                    <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-outline-secondary">
                        <i class="bi bi-box-arrow-right me-1"></i> Switch Account
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
