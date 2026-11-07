<?php
/**
 * Shared Reusable Footer
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant
 */

require_once __DIR__ . '/../config/system.php';
?>
        <footer class="app-footer no-print">
            <div class="container-fluid">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center">
                    <span>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</span>
                    <span class="text-muted small">Local Academic Environment &bull; Version <?php echo APP_VERSION; ?></span>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Local Offline Bootstrap 5.3 JS Bundle -->
<script src="<?php echo APP_URL; ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<!-- Application Vanilla JS Interactions -->
<script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
