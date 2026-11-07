<?php
/**
 * Procedural Authentication & Role Authorization Guards
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if a user is currently authenticated
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get active user session information
 * @return array|null
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }

    return [
        'user_id' => (int)$_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role_id' => (int)($_SESSION['role_id'] ?? 0),
        'role_name' => $_SESSION['role_name'] ?? '',
        'student_id' => isset($_SESSION['student_id']) ? (int)$_SESSION['student_id'] : null,
        'student_number' => $_SESSION['student_number'] ?? null,
        'must_change_password' => !empty($_SESSION['must_change_password'])
    ];
}

/**
 * Check if the active user matches one of the specified roles
 * @param string|array $allowed_roles
 * @return bool
 */
function has_role($allowed_roles) {
    if (!is_logged_in()) {
        return false;
    }

    if (is_string($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    $current_role = $_SESSION['role_name'] ?? '';
    return in_array($current_role, $allowed_roles, true);
}

/**
 * Enforce that the user is logged in before continuing
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash_message('warning', 'Please log in to access this page.');
        redirect(APP_URL . '/login.php');
    }
}

/**
 * Enforce that the user has an allowed role before continuing
 * @param string|array $allowed_roles
 */
function require_role($allowed_roles) {
    require_login();

    if (!has_role($allowed_roles)) {
        http_response_code(403);
        include __DIR__ . '/403.php';
        exit;
    }
}
