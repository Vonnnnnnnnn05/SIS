<?php
/**
 * Shared Flash Message Alert Component
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/functions.php';

$flash = get_flash_message();
if ($flash):
    $type = escape($flash['type']);
    $msg = escape($flash['message']);
    
    $icon = 'bi-info-circle-fill';
    if ($type === 'success') {
        $icon = 'bi-check-circle-fill';
    } elseif ($type === 'danger') {
        $icon = 'bi-exclamation-octagon-fill';
    } elseif ($type === 'warning') {
        $icon = 'bi-exclamation-triangle-fill';
    }
?>
<div class="alert alert-<?php echo $type; ?> alert-dismissible fade show d-flex align-items-center mb-4 no-print shadow-sm" role="alert">
    <i class="bi <?php echo $icon; ?> fs-5 me-2 flex-shrink-0"></i>
    <div class="flex-grow-1">
        <?php echo $msg; ?>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
