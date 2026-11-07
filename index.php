<?php
/**
 * Application Entry Point & Role Gateway
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant
 */

require_once __DIR__ . '/config/system.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    redirect(APP_URL . '/login.php');
}

$role = $_SESSION['role_name'] ?? '';

if ($role === 'Administrator') {
    redirect(APP_URL . '/admin/dashboard.php');
} elseif ($role === 'Registrar') {
    redirect(APP_URL . '/registrar/dashboard.php');
} elseif ($role === 'Student') {
    if (!empty($_SESSION['must_change_password'])) {
        redirect(APP_URL . '/student/change-password.php');
    }
    redirect(APP_URL . '/student/dashboard.php');
} else {
    // Unknown role fallback
    redirect(APP_URL . '/logout.php');
}
