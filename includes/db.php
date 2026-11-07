<?php
/**
 * Database Connection Wrapper (Procedural mysqli)
 * Student Information System (SIS)
 * Strictly adheres to Constitution v2.0.0 (mysqli_connect)
 */

require_once __DIR__ . '/../config/database.php';

// Enable strict error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * Get the procedural database connection
 * @return mysqli
 */
function get_db_connection() {
    static $conn = null;

    if ($conn === null) {
        try {
            $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }
            mysqli_set_charset($conn, DB_CHARSET);
        } catch (Exception $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            die('<div style="font-family:sans-serif;padding:2rem;text-align:center;">'
                . '<h2 style="color:#b91c1c;">Database Connection Failed</h2>'
                . '<p>Please ensure MySQL is running in XAMPP and the database <code>' . htmlspecialchars(DB_NAME) . '</code> is properly imported.</p>'
                . '</div>');
        }
    }

    return $conn;
}
