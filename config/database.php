<?php
/**
 * Database Configuration & Procedural Connection Helper
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant (mysqli_connect)
 */

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'sis_db');
    define('DB_PORT', 3306);
    define('DB_CHARSET', 'utf8mb4');
}

/**
 * Returns the shared procedural MySQLi database connection
 * @return mysqli
 */
function db_connect() {
    static $conn = null;

    if ($conn === null) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

        if (!$conn) {
            error_log('Database Connection Failed: ' . mysqli_connect_error());
            die('<div style="font-family:sans-serif;padding:2rem;text-align:center;">'
                . '<h2 style="color:#b91c1c;">Database Connection Error</h2>'
                . '<p>Unable to connect to the database. Please verify that MySQL is running in XAMPP.</p>'
                . '</div>');
        }

        mysqli_set_charset($conn, DB_CHARSET);
    }

    return $conn;
}
