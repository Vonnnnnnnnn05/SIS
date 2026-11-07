<?php
/**
 * Academic Calendar View (Registrar Tier)
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('Registrar');

$conn = db_connect();
$page_title = 'Academic Calendar';

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
                        <h1 class="h3 mb-1 fw-bold">Academic Calendar & Terms</h1>
                        <p class="text-muted small mb-0">View institutional academic periods, enrollment deadlines, and active semesters.</p>
                    </div>
                    <div>
                        <a href="<?php echo APP_URL; ?>/registrar/enrollment/enroll.php" class="btn btn-primary">
                            <i class="bi bi-person-check-fill me-1"></i> Enroll Student
                        </a>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <!-- Active Term Announcement Banner -->
                <?php
                $active_term_sql = "SELECT sem.semester_id, sem.semester_term, sem.start_date, sem.end_date, ay.year_code
                                    FROM semesters sem
                                    INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id
                                    WHERE sem.is_active = 1 LIMIT 1";
                $active_res = mysqli_query($conn, $active_term_sql);
                $active_term = mysqli_fetch_assoc($active_res);
                ?>

                <div class="card border-0 shadow-sm mb-4 border-start border-primary border-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <span class="badge bg-primary text-uppercase mb-2">Current Active Term</span>
                                <?php if ($active_term): ?>
                                    <h4 class="fw-bold mb-1">
                                        Academic Year <?php echo escape($active_term['year_code']); ?> &mdash; <?php echo escape($active_term['semester_term']); ?>
                                    </h4>
                                    <p class="text-muted small mb-0">
                                        <i class="bi bi-calendar-range me-1"></i>
                                        Period: <?php echo !empty($active_term['start_date']) ? format_date($active_term['start_date']) : 'N/A'; ?>
                                        to <?php echo !empty($active_term['end_date']) ? format_date($active_term['end_date']) : 'N/A'; ?>
                                    </p>
                                <?php else: ?>
                                    <h5 class="fw-bold text-muted mb-1">No semester currently marked as active</h5>
                                    <p class="text-muted small mb-0">Contact the Administrator to activate the current enrollment term.</p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <a href="<?php echo APP_URL; ?>/registrar/enrollment/index.php" class="btn btn-outline-primary">
                                    <i class="bi bi-journal-text me-1"></i> View Term Enrollments
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Calendar History Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="card-title mb-0 fw-semibold">
                            <i class="bi bi-calendar3 me-1 text-primary"></i> Academic Terms History
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Academic Year</th>
                                    <th>Term / Semester</th>
                                    <th>Calendar Dates</th>
                                    <th>Enrollment Status</th>
                                    <th>Total Enrolled</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                AY <?php echo escape($row['year_code']); ?>
                                                <?php if ($row['is_current'] == 1): ?>
                                                    <span class="badge bg-secondary ms-1">Current Year</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <i class="bi bi-calendar-check me-1 text-muted"></i>
                                                <?php echo escape($row['semester_term']); ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?php if (!empty($row['start_date']) && !empty($row['end_date'])): ?>
                                                    <?php echo format_date($row['start_date']); ?> &mdash; <?php echo format_date($row['end_date']); ?>
                                                <?php else: ?>
                                                    <span class="fst-italic text-secondary">Dates unspecified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['is_active'] == 1): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle me-1"></i> Active Enrollment
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border">
                                                        Closed / Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?php echo number_format((int)$row['total_enrolled']); ?> students
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?php echo APP_URL; ?>/registrar/enrollment/index.php?semester_id=<?php echo (int)$row['semester_id']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary" 
                                                   title="View enrollments for this term">
                                                    <i class="bi bi-people me-1"></i> View Students
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            No academic terms registered yet.
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
