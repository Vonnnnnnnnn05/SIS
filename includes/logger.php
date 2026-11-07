<?php
/**
 * Procedural System Activity Logger
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant
 */

require_once __DIR__ . '/session.php';

/**
 * Log an administrative or operational event into system_logs
 * @param mysqli $conn
 * @param string $action_type ('LOGIN', 'LOGOUT', 'CREATE', 'UPDATE', 'DEACTIVATE', 'BACKUP', 'RESTORE')
 * @param string $module ('AUTH', 'USERS', 'STUDENTS', 'ENROLLMENT', 'TERMS', 'DATABASE')
 * @param string|int|null $target_id
 * @param string $details
 * @return bool
 */
function log_activity($conn, $action_type, $module, $target_id, $details) {
    if (!$conn) {
        return false;
    }

    $user_id = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role_name'] ?? 'System';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $target_id = $target_id !== null ? (string)$target_id : null;

    $sql = "INSERT INTO system_logs (user_id, role, action_type, module, target_id, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log('Failed to prepare log statement: ' . mysqli_error($conn));
        return false;
    }

    mysqli_stmt_bind_param($stmt, "isssssss", $user_id, $role, $action_type, $module, $target_id, $details, $ip_address, $user_agent);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $success;
}
