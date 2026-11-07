<?php
/**
 * Shared Top Navigation Bar
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$auth_user = current_user();
?>
<header class="app-header">
    <div class="d-flex align-items-center">
        <button class="btn btn-sm btn-outline-secondary d-lg-none me-3" id="sidebarToggle" type="button" aria-label="Toggle navigation">
            <i class="bi bi-list fs-5"></i>
        </button>
        <span class="fw-semibold text-secondary d-none d-sm-inline">
            <?php echo isset($page_title) ? escape($page_title) : APP_NAME; ?>
        </span>
    </div>

    <div class="d-flex align-items-center">
        <?php if ($auth_user): ?>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-light text-dark dropdown-toggle d-flex align-items-center border-0" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-5 me-2 text-primary"></i>
                    <div class="text-start me-1 d-none d-md-block">
                        <div class="fw-semibold small leading-tight"><?php echo escape($auth_user['username']); ?></div>
                        <div class="text-muted" style="font-size: 0.72rem;"><?php echo escape($auth_user['role_name']); ?></div>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="userMenuDropdown">
                    <li class="dropdown-header small text-uppercase fw-bold text-muted">Account Access</li>
                    <?php if ($auth_user['role_name'] === 'Student'): ?>
                        <li>
                            <a class="dropdown-item" href="<?php echo APP_URL; ?>/student/profile.php">
                                <i class="bi bi-person-vcard me-2 text-primary"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo APP_URL; ?>/student/change-password.php">
                                <i class="bi bi-key me-2 text-secondary"></i> Change Password
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a class="dropdown-item" href="<?php echo APP_URL; ?>/student/change-password.php">
                                <i class="bi bi-key me-2 text-secondary"></i> Change Password
                            </a>
                        </li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?php echo APP_URL; ?>/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Log Out
                        </a>
                    </li>
                </ul>
            </div>
        <?php else: ?>
            <a href="<?php echo APP_URL; ?>/login.php" class="btn btn-sm btn-primary">
                <i class="bi bi-box-arrow-in-right me-1"></i> Log In
            </a>
        <?php endif; ?>
    </div>
</header>
