<?php
/**
 * Institutional Term Enrollment Reports (Administrator Tier)
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
$page_title = 'Term Enrollment Reports';
$user = current_user();

// Filters
$search = sanitize($_GET['q'] ?? '');
$semester_id = isset($_GET['semester_id']) && is_numeric($_GET['semester_id']) ? (int)$_GET['semester_id'] : 0;
$program_id = isset($_GET['program_id']) && is_numeric($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$status = sanitize($_GET['status'] ?? '');

$where = ["1=1"];
$types = "";
$params = [];

if (!empty($search)) {
    $where[] = "(s.student_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
    $types .= "sss";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($semester_id > 0) {
    $where[] = "e.semester_id = ?";
    $types .= "i";
    $params[] = $semester_id;
}

if ($program_id > 0) {
    $where[] = "e.program_id = ?";
    $types .= "i";
    $params[] = $program_id;
}

if (!empty($status) && in_array($status, ['Pending', 'Enrolled', 'Dropped', 'Completed'], true)) {
    $where[] = "e.enrollment_status = ?";
    $types .= "s";
    $params[] = $status;
}

$where_sql = implode(" AND ", $where);

// Semesters for filter dropdown
$sem_res = mysqli_query($conn, "SELECT sem.semester_id, sem.semester_term, sem.is_active, ay.year_code 
                                FROM semesters sem 
                                INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id 
                                ORDER BY ay.start_year DESC, sem.semester_term ASC");

// Programs for filter dropdown
$prog_res = mysqli_query($conn, "SELECT program_id, program_code, program_name FROM programs ORDER BY program_code ASC");

// Fetch enrollments
$sql = "SELECT e.enrollment_id, e.year_level, e.enrollment_date, e.enrollment_status, e.remarks,
               s.student_number, s.first_name, s.middle_name, s.last_name, s.email,
               p.program_code, p.program_name,
               sem.semester_term, ay.year_code,
               u.username as enrolled_by
        FROM enrollments e
        INNER JOIN students s ON e.student_id = s.student_id
        INNER JOIN semesters sem ON e.semester_id = sem.semester_id
        INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id
        INNER JOIN programs p ON e.program_id = p.program_id
        LEFT JOIN users u ON e.created_by = u.user_id
        WHERE " . $where_sql . "
        ORDER BY ay.start_year DESC, sem.semester_term ASC, s.last_name ASC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_records = mysqli_num_rows($result);

// Build query string for CSV export
$export_params = http_build_query([
    'type' => 'enrollments',
    'q' => $search,
    'semester_id' => $semester_id,
    'program_id' => $program_id,
    'status' => $status
]);

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content-body">
            <div class="container-fluid px-0">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2 no-print">
                    <div>
                        <h1 class="h3 mb-1 fw-bold">Term Enrollment Reports</h1>
                        <p class="text-muted small mb-0">Official institutional term registrations and academic progression rosters.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo APP_URL; ?>/admin/reports/student-reports.php" class="btn btn-outline-secondary">
                            <i class="bi bi-people me-1"></i> Student Master Roster
                        </a>
                        <a href="<?php echo APP_URL; ?>/process/reports/export-csv.php?<?php echo $export_params; ?>" class="btn btn-outline-success">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
                        </a>
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="bi bi-printer me-1"></i> Print Report
                        </button>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <!-- Filter Controls (Screen Only) -->
                <div class="card border-0 shadow-sm mb-4 no-print">
                    <div class="card-body p-3">
                        <form method="GET" action="" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Search Student</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" name="q" class="form-control" placeholder="ID or name..." value="<?php echo escape($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Academic Term</label>
                                <select name="semester_id" class="form-select form-select-sm">
                                    <option value="">All Semesters</option>
                                    <?php if ($sem_res): ?>
                                        <?php while ($sem = mysqli_fetch_assoc($sem_res)): ?>
                                            <option value="<?php echo (int)$sem['semester_id']; ?>" <?php echo $semester_id === (int)$sem['semester_id'] ? 'selected' : ''; ?>>
                                                AY <?php echo escape($sem['year_code']); ?> - <?php echo escape($sem['semester_term']); ?>
                                                <?php echo $sem['is_active'] ? ' (Active)' : ''; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold mb-1">Program</label>
                                <select name="program_id" class="form-select form-select-sm">
                                    <option value="">All Programs</option>
                                    <?php if ($prog_res): ?>
                                        <?php while ($pr = mysqli_fetch_assoc($prog_res)): ?>
                                            <option value="<?php echo (int)$pr['program_id']; ?>" <?php echo $program_id === (int)$pr['program_id'] ? 'selected' : ''; ?>>
                                                <?php echo escape($pr['program_code']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold mb-1">Standing</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="Enrolled" <?php echo $status === 'Enrolled' ? 'selected' : ''; ?>>Enrolled</option>
                                    <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Completed" <?php echo $status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Dropped" <?php echo $status === 'Dropped' ? 'selected' : ''; ?>>Dropped</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex gap-1">
                                <button type="submit" class="btn btn-sm btn-primary w-100">
                                    <i class="bi bi-funnel me-1"></i> Apply
                                </button>
                                <a href="enrollment-reports.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Printable Header -->
                <div class="print-header d-none">
                    <h2><?php echo APP_NAME; ?></h2>
                    <p>Official Institutional Term Enrollment Roster</p>
                    <p class="small text-muted">Generated by: <?php echo escape($user['username'] . ' (' . $user['role_name'] . ')'); ?> &bull; Date: <?php echo date('F d, Y h:i A'); ?></p>
                </div>

                <!-- Enrollment Roster Table Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 fw-semibold">
                            <i class="bi bi-journal-check me-1 text-primary"></i> Term Enrollments
                        </h6>
                        <span class="badge bg-light text-dark border">
                            Total Records: <?php echo number_format($total_records); ?>
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Student Number</th>
                                    <th>Student Name</th>
                                    <th>Program & Level</th>
                                    <th>Academic Term</th>
                                    <th>Enrollment Date</th>
                                    <th>Standing</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($total_records > 0): ?>
                                    <?php $i = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="text-muted"><?php echo $i++; ?></td>
                                            <td class="fw-bold font-monospace"><?php echo escape($row['student_number']); ?></td>
                                            <td class="fw-semibold">
                                                <?php echo escape($row['last_name'] . ', ' . $row['first_name'] . ($row['middle_name'] ? ' ' . substr($row['middle_name'], 0, 1) . '.' : '')); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?php echo escape($row['program_code']); ?></span>
                                                <span class="text-muted ms-1"><?php echo escape($row['year_level']); ?></span>
                                            </td>
                                            <td>AY <?php echo escape($row['year_code']); ?> &mdash; <?php echo escape($row['semester_term']); ?></td>
                                            <td class="text-muted"><?php echo format_date($row['enrollment_date']); ?></td>
                                            <td>
                                                <?php
                                                $b_cls = 'bg-secondary';
                                                if ($row['enrollment_status'] === 'Enrolled') $b_cls = 'bg-success';
                                                elseif ($row['enrollment_status'] === 'Pending') $b_cls = 'bg-warning text-dark';
                                                elseif ($row['enrollment_status'] === 'Completed') $b_cls = 'bg-info text-dark';
                                                elseif ($row['enrollment_status'] === 'Dropped') $b_cls = 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $b_cls; ?>"><?php echo escape($row['enrollment_status']); ?></span>
                                            </td>
                                            <td class="text-muted"><?php echo !empty($row['remarks']) ? escape($row['remarks']) : '&mdash;'; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            No enrollment records found matching the specified criteria.
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
<?php
mysqli_stmt_close($stmt);
?>
