<?php
/**
 * Academic Calendar & Terms Management (Administrator Tier)
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
$page_title = 'Academic Terms Management';

// Fetch all semesters joined with academic years
$sql = "SELECT sem.semester_id, sem.semester_term, sem.is_active, sem.start_date, sem.end_date, sem.created_at,
               ay.ay_id, ay.year_code, ay.start_year, ay.end_year, ay.is_current,
               (SELECT COUNT(*) FROM enrollments WHERE semester_id = sem.semester_id) as total_enrolled
        FROM semesters sem
        INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id
        ORDER BY ay.start_year DESC, sem.semester_term ASC";

$result = mysqli_query($conn, $sql);

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
                        <h1 class="h3 mb-1 fw-bold">Academic Years & Semesters</h1>
                        <p class="text-muted small mb-0">Configure institutional calendar terms and set the active enrollment period.</p>
                    </div>
                    <div>
                        <a href="create.php" class="btn btn-primary">
                            <i class="bi bi-calendar-plus me-1"></i> Add Academic Term
                        </a>
                    </div>
                </div>

                <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

                <div class="sis-card">
                    <div class="table-responsive">
                        <table class="table sis-table align-middle">
                            <thead>
                                <tr>
                                    <th>Academic Year</th>
                                    <th>Semester / Term</th>
                                    <th>Duration</th>
                                    <th>Total Enrolled</th>
                                    <th>Enrollment Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="fw-bold text-dark">
                                                <?php echo escape($row['year_code']); ?>
                                                <?php if ($row['is_current']): ?>
                                                    <span class="badge bg-secondary ms-1 small">Current Year</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-semibold text-primary">
                                                <?php echo escape($row['semester_term']); ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?php echo format_date($row['start_date']); ?> &ndash; <?php echo format_date($row['end_date']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?php echo (int)$row['total_enrolled']; ?> students</span>
                                            </td>
                                            <td>
                                                <?php if ($row['is_active']): ?>
                                                    <span class="badge badge-status badge-active">
                                                        <i class="bi bi-check-circle-fill me-1"></i> Active Term
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-status badge-inactive">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1 align-items-center">
                                                    <?php if (!$row['is_active']): ?>
                                                        <form action="<?php echo APP_URL; ?>/process/academic-years/create-term-process.php" method="POST" class="d-inline" onsubmit="return confirm('Set this semester as the official active enrollment term?');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                            <input type="hidden" name="action" value="set_active">
                                                            <input type="hidden" name="semester_id" value="<?php echo $row['semester_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                                <i class="bi bi-play-circle me-1"></i> Activate
                                                            </button>
                                                        </form>
                                                        <form action="<?php echo APP_URL; ?>/process/academic-years/delete-term-process.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete academic term <?php echo escape($row['year_code'] . ' ' . $row['semester_term']); ?>?');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                            <input type="hidden" name="semester_id" value="<?php echo (int)$row['semester_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Academic Term">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted small"><i class="bi bi-lock-fill me-1"></i> Currently Active</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            No academic terms configured. Click "Add Academic Term" to create one.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
