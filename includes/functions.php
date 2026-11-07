<?php
/**
 * Procedural Helper Functions
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant
 */

require_once __DIR__ . '/session.php';

/**
 * Escape output for safe rendering in HTML (XSS prevention)
 * @param mixed $data
 * @return string
 */
function escape($data) {
    if ($data === null) {
        return '';
    }
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

/**
 * Trim and sanitize string input
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return trim((string)$data);
}

/**
 * Set a one-time flash notification message in session
 * @param string $type ('success', 'danger', 'warning', 'info')
 * @param string $message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type === 'error' ? 'danger' : $type,
        'message' => $message
    ];
}

/**
 * Check if a flash notification message exists
 * @return bool
 */
function has_flash_message() {
    return isset($_SESSION['flash']) && !empty($_SESSION['flash']);
}

/**
 * Retrieve and clear the flash notification message
 * @return array|null
 */
function get_flash_message() {
    if (has_flash_message()) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash alert component
 */
function display_alerts() {
    require __DIR__ . '/alerts.php';
}

/**
 * Generate or retrieve current CSRF token
 * @return string
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify submitted CSRF token
 * @param string $token
 * @return bool
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Format date string safely
 * @param string|null $date
 * @param string $format
 * @return string
 */
function format_date($date, $format = 'M d, Y') {
    if (!$date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

/**
 * Redirect to specified URL and terminate script
 * @param string $url
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Render procedural Bootstrap 5 pagination controls
 * @param int $current_page
 * @param int $total_pages
 * @param array $query_params
 * @return string HTML
 */
function render_pagination($current_page, $total_pages, $query_params = []) {
    if ($total_pages <= 1) {
        return '';
    }

    $html = '<nav aria-label="Page navigation" class="mt-3"><ul class="pagination pagination-sm justify-content-end mb-0">';

    // Previous button
    unset($query_params['page']);
    $prev_page = max(1, $current_page - 1);
    $prev_query = http_build_query(array_merge($query_params, ['page' => $prev_page]));
    $prev_disabled = ($current_page <= 1) ? ' disabled' : '';
    $html .= '<li class="page-item' . $prev_disabled . '"><a class="page-link" href="?' . escape($prev_query) . '"><i class="bi bi-chevron-left"></i> Previous</a></li>';

    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);

    if ($start > 1) {
        $q = http_build_query(array_merge($query_params, ['page' => 1]));
        $html .= '<li class="page-item"><a class="page-link" href="?' . escape($q) . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i === $current_page) ? ' active' : '';
        $q = http_build_query(array_merge($query_params, ['page' => $i]));
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="?' . escape($q) . '">' . $i . '</a></li>';
    }

    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
        }
        $q = http_build_query(array_merge($query_params, ['page' => $total_pages]));
        $html .= '<li class="page-item"><a class="page-link" href="?' . escape($q) . '">' . $total_pages . '</a></li>';
    }

    // Next button
    $next_page = min($total_pages, $current_page + 1);
    $next_query = http_build_query(array_merge($query_params, ['page' => $next_page]));
    $next_disabled = ($current_page >= $total_pages) ? ' disabled' : '';
    $html .= '<li class="page-item' . $next_disabled . '"><a class="page-link" href="?' . escape($next_query) . '">Next <i class="bi bi-chevron-right"></i></a></li>';

    $html .= '</ul></nav>';
    return $html;
}
