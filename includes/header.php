<?php
/**
 * Shared Reusable HTML Header
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

$title = isset($page_title) ? $page_title . ' - ' . APP_SHORT_NAME : APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($title); ?></title>
    <!-- Local Offline Bootstrap 5.3 CSS Bundle -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/vendor/bootstrap/bootstrap.min.css">
    <!-- Local Offline Bootstrap Icons Font -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <!-- Custom Application CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    <!-- Clean Print Stylesheet for Reports -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/print.css" media="print">
</head>
<body class="bg-light">
