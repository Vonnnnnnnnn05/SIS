<?php
/**
 * Logout Trigger
 * Student Information System (SIS)
 */

require_once __DIR__ . '/config/system.php';
header('Location: ' . APP_URL . '/process/logout-process.php');
exit;
