<?php
/**
 * Application Configuration
 * Student Information System (SIS)
 */

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Student Information System');
    define('APP_SHORT_NAME', 'SIS');
    define('APP_VERSION', '1.0.0');
    define('APP_URL', 'http://localhost/sis');
    define('BASE_PATH', dirname(__DIR__));
    define('STORAGE_PATH', BASE_PATH . '/storage');
    define('BACKUP_PATH', STORAGE_PATH . '/backups');
    define('SESSION_LIFETIME', 1800); // 30 minutes in seconds

    // Set default timezone
    date_default_timezone_set('Asia/Manila');
}
