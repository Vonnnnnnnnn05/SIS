<?php
/**
 * Administrator Student Profile View
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant
 */

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('Administrator');

// Reuse the student profile view
require_once __DIR__ . '/../../registrar/students/view.php';
