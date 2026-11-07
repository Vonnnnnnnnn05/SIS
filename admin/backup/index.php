<?php
/**
 * Database Backup & Recovery Management (Administrator Tier)
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('Administrator');

$conn = db_connect();
$page_title = 'Database Backup & Recovery';
$user = current_user();

// Fetch backup history records
$sql = "SELECT b.backup_id, b.filename, b.file_size_bytes, b.record_count, b.created_at,
               u.username as creator_name
        FROM backup_logs b
        LEFT JOIN users u ON b.created_by = u.user_id
        ORDER BY b.created_at DESC";
$backups_res = mysqli_query($conn, $sql);

// Calculate total backup files on disk
$backup_files_count = 0;
$total_backup_bytes = 0;
if (is_dir(BACKUP_PATH)) {
    $files = scandir(BACKUP_PATH);
    foreach ($files as $f) {
        if (substr($f, -4) === '.sql') {
            $backup_files_count++;
            $total_backup_bytes += filesize(BACKUP_PATH . '/' . $f);
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content-body">
            <div class="container-fluid px-0">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                    <div>
                        <h1 class="h3 mb-1 fw-bold">Database Backup & Recovery</h1>
                        <p class="text-muted small mb-0">Generate institutional data dumps, download archives, and restore historical snapshots.</p>
                    </div>
                    <div>
                        <form method="POST" action="<?php echo APP_URL; ?>/process/backup/create-backup-process.php" class="d-inline" onsubmit="return confirm('Generate a complete database backup archive now?');">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-database-add me-1"></i> Generate New Backup
                            </button>
                        </form>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <!-- Status Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-3">
                                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Archived Backups</span>
                                <div class="fs-3 fw-bold text-dark"><?php echo number_format($backup_files_count); ?></div>
                                <small class="text-muted">Stored in protected local directory</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-3">
                                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Storage Utilized</span>
                                <div class="fs-3 fw-bold text-primary">
                                    <?php 
                                    if ($total_backup_bytes > 1048576) {
                                        echo number_format($total_backup_bytes / 1048576, 2) . ' MB';
                                    } else {
                                        echo number_format($total_backup_bytes / 1024, 1) . ' KB';
                                    }
                                    ?>
                                </div>
                                <small class="text-muted">Total size of .sql files</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-3">
                                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Directory Security</span>
                                <div class="fs-4 fw-bold text-success">
                                    <i class="bi bi-shield-check me-1"></i> Protected
                                </div>
                                <small class="text-muted">HTTP direct access blocked via .htaccess</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Backup History List -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0 fw-semibold">
                                    <i class="bi bi-clock-history me-1 text-primary"></i> Backup Archive History
                                </h6>
                                <span class="badge bg-light text-dark border">
                                    <?php echo $backups_res ? mysqli_num_rows($backups_res) : 0; ?> Logged Backups
                                </span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Filename</th>
                                            <th>Size</th>
                                            <th>Records</th>
                                            <th>Created By</th>
                                            <th>Created At</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($backups_res && mysqli_num_rows($backups_res) > 0): ?>
                                            <?php while ($b = mysqli_fetch_assoc($backups_res)): 
                                                $exists_on_disk = file_exists(BACKUP_PATH . '/' . basename($b['filename']));
                                            ?>
                                                <tr>
                                                    <td class="font-monospace fw-semibold">
                                                        <i class="bi bi-file-earmark-code me-1 text-secondary"></i>
                                                        <?php echo escape($b['filename']); ?>
                                                        <?php if (!$exists_on_disk): ?>
                                                            <span class="badge bg-warning text-dark ms-1">Missing on disk</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-muted">
                                                        <?php 
                                                        $bytes = (int)$b['file_size_bytes'];
                                                        echo ($bytes > 1048576) ? number_format($bytes / 1048576, 2) . ' MB' : number_format($bytes / 1024, 1) . ' KB';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border">
                                                            <?php echo number_format((int)$b['record_count']); ?> rows
                                                        </span>
                                                    </td>
                                                    <td class="text-muted">
                                                        <?php echo escape($b['creator_name'] ?? 'System'); ?>
                                                    </td>
                                                    <td class="text-muted text-nowrap">
                                                        <?php echo format_date($b['created_at'], 'M d, Y H:i'); ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($exists_on_disk): ?>
                                                                <a href="<?php echo APP_URL; ?>/process/backup/download-backup.php?id=<?php echo (int)$b['backup_id']; ?>" 
                                                                   class="btn btn-outline-secondary" 
                                                                   title="Download SQL File">
                                                                    <i class="bi bi-download"></i>
                                                                </a>
                                                                <button type="button" 
                                                                        class="btn btn-outline-danger" 
                                                                        title="Restore This Backup"
                                                                        onclick="openRestoreModal(<?php echo (int)$b['backup_id']; ?>, '<?php echo escape($b['filename']); ?>')">
                                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <button class="btn btn-outline-secondary disabled" disabled>
                                                                    <i class="bi bi-slash-circle"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    <i class="bi bi-database fs-2 d-block mb-2"></i>
                                                    No database backup archives generated yet. Click "Generate New Backup" above to create one.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Upload & Restore Card -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="card-title mb-0 fw-semibold text-danger">
                                    <i class="bi bi-exclamation-triangle-fill me-1 text-danger"></i> Upload & Restore Archive
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning border-0 small py-2 px-3 mb-3">
                                    <strong>Caution:</strong> Restoring a database overwrites existing data with the snapshot contents. Double password verification is enforced.
                                </div>

                                <form method="POST" action="<?php echo APP_URL; ?>/process/backup/restore-backup-process.php" enctype="multipart/form-data" onsubmit="return confirm('CAUTION: This will overwrite current tables and records with the uploaded backup file. Proceed?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                                    <div class="mb-3">
                                        <label for="backup_file" class="form-label fw-semibold small">Choose .sql Backup File <span class="text-danger">*</span></label>
                                        <input type="file" name="backup_file" id="backup_file" class="form-control form-control-sm" accept=".sql" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="admin_password" class="form-label fw-semibold small">Confirm Administrator Password <span class="text-danger">*</span></label>
                                        <input type="password" name="admin_password" id="admin_password" class="form-control form-control-sm" placeholder="Enter your current password..." required>
                                    </div>

                                    <button type="submit" class="btn btn-sm btn-danger w-100">
                                        <i class="bi bi-upload me-1"></i> Upload & Restore Database
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Maintenance Guidelines -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="card-title mb-0 fw-semibold">
                                    <i class="bi bi-info-circle me-1 text-primary"></i> Backup Instructions
                                </h6>
                            </div>
                            <div class="card-body small text-muted">
                                <p><strong>Automated Dump:</strong> Backups are generated using native PHP procedural SQL builders without external dependencies or exec commands.</p>
                                <p><strong>Security:</strong> All backup files are stored in <code>database/backup/</code> where direct web access is restricted by Apache directives.</p>
                                <p class="mb-0"><strong>Recovery:</strong> Before restoring an older snapshot, create a fresh backup of the current database to prevent accidental data loss.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>

<!-- Modal for Restoring from Existing History -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo APP_URL; ?>/process/backup/restore-backup-process.php">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="backup_id" id="modal_backup_id" value="">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-danger" id="restoreModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Confirm Database Restoration
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger border-0 small">
                        <strong>Warning:</strong> Restoring this backup will overwrite all existing tables, user accounts, student records, and enrollments with the data from:
                        <div class="fw-bold font-monospace mt-1" id="modal_filename_display"></div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_admin_password" class="form-label fw-semibold small">
                            Administrator Password Confirmation <span class="text-danger">*</span>
                        </label>
                        <input type="password" name="admin_password" id="modal_admin_password" class="form-control" placeholder="Enter your administrator password to proceed..." required>
                        <div class="form-text">Double-confirmation security requirement for database restoration.</div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Confirm & Execute Restore
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRestoreModal(backupId, filename) {
    document.getElementById('modal_backup_id').value = backupId;
    document.getElementById('modal_filename_display').textContent = filename;
    document.getElementById('modal_admin_password').value = '';
    const restoreModal = new bootstrap.Modal(document.getElementById('restoreModal'));
    restoreModal.show();
}
</script>
