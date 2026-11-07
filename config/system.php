<?php
/**
 * System Configuration
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant
 */

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Student Information System');
    define('APP_SHORT_NAME', 'SIS');
    define('APP_VERSION', '1.0.0');
    define('APP_URL', 'http://localhost/sis');
    define('BASE_PATH', dirname(__DIR__));
    define('BACKUP_PATH', BASE_PATH . '/database/backup');
    define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds

    // Set institutional default timezone
    date_default_timezone_set('Asia/Manila');
}
