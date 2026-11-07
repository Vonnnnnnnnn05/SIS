<?php
/**
 * Institutional Student Reports (Administrator Tier)
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
$page_title = 'Student Institutional Reports';
$user = current_user();

// Filters
$search = sanitize($_GET['q'] ?? '');
$program_id = isset($_GET['program_id']) && is_numeric($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$status = sanitize($_GET['status'] ?? '');

$where = ["1=1"];
$types = "";
$params = [];

if (!empty($search)) {
    $where[] = "(s.student_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?)";
    $types .= "ssss";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($program_id > 0) {
    $where[] = "s.program_id = ?";
    $types .= "i";
    $params[] = $program_id;
}

if (!empty($status) && in_array($status, ['Active', 'Inactive', 'Graduated', 'Withdrawn'], true)) {
    $where[] = "s.current_status = ?";
    $types .= "s";
    $params[] = $status;
}

$where_sql = implode(" AND ", $where);

// Fetch programs for filter dropdown
$prog_res = mysqli_query($conn, "SELECT program_id, program_code, program_name FROM programs ORDER BY program_code ASC");

// Fetch student records
$sql = "SELECT s.student_id, s.student_number, s.first_name, s.middle_name, s.last_name, 
               s.email, s.contact_number, s.current_status, s.created_at,
               p.program_code, p.program_name
        FROM students s
        LEFT JOIN programs p ON s.program_id = p.program_id
        WHERE " . $where_sql . "
        ORDER BY s.last_name ASC, s.first_name ASC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_records = mysqli_num_rows($result);

// Build query string for CSV export
$export_params = http_build_query([
    'type' => 'students',
    'q' => $search,
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
                        <h1 class="h3 mb-1 fw-bold">Student Roster Reports</h1>
                        <p class="text-muted small mb-0">Generate institutional student rosters and demographic reports.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo APP_URL; ?>/admin/reports/enrollment-reports.php" class="btn btn-outline-secondary">
                            <i class="bi bi-journal-text me-1"></i> Term Enrollment Reports
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
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Search Keyword</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" name="q" class="form-control" placeholder="Student number, name, email..." value="<?php echo escape($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Degree Program</label>
                                <select name="program_id" class="form-select form-select-sm">
                                    <option value="">All Programs</option>
                                    <?php if ($prog_res): ?>
                                        <?php while ($pr = mysqli_fetch_assoc($prog_res)): ?>
                                            <option value="<?php echo (int)$pr['program_id']; ?>" <?php echo $program_id === (int)$pr['program_id'] ? 'selected' : ''; ?>>
                                                <?php echo escape($pr['program_code'] . ' - ' . $pr['program_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Institutional Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="Active" <?php echo $status === 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo $status === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="Graduated" <?php echo $status === 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                                    <option value="Withdrawn" <?php echo $status === 'Withdrawn' ? 'selected' : ''; ?>>Withdrawn</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex gap-1">
                                <button type="submit" class="btn btn-sm btn-primary w-100">
                                    <i class="bi bi-funnel me-1"></i> Apply
                                </button>
                                <a href="student-reports.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Printable Institutional Header (Visible in print) -->
                <div class="print-header d-none">
                    <h2><?php echo APP_NAME; ?></h2>
                    <p>Official Institutional Student Roster Report</p>
                    <p class="small text-muted">Generated by: <?php echo escape($user['username'] . ' (' . $user['role_name'] . ')'); ?> &bull; Date: <?php echo date('F d, Y h:i A'); ?></p>
                </div>

                <!-- Roster Table Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 fw-semibold">
                            <i class="bi bi-people-fill me-1 text-primary"></i> Student Roster
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
                                    <th>Full Name</th>
                                    <th>Program</th>
                                    <th>Status</th>
                                    <th>Email Address</th>
                                    <th>Contact</th>
                                    <th>Date Registered</th>
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
                                            </td>
                                            <td>
                                                <?php
                                                $b_cls = 'bg-secondary';
                                                if ($row['current_status'] === 'Active') $b_cls = 'bg-success';
                                                elseif ($row['current_status'] === 'Inactive') $b_cls = 'bg-warning text-dark';
                                                elseif ($row['current_status'] === 'Graduated') $b_cls = 'bg-info text-dark';
                                                elseif ($row['current_status'] === 'Withdrawn') $b_cls = 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $b_cls; ?>"><?php echo escape($row['current_status']); ?></span>
                                            </td>
                                            <td><?php echo escape($row['email']); ?></td>
                                            <td><?php echo escape($row['contact_number']); ?></td>
                                            <td class="text-muted"><?php echo format_date($row['created_at']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            No student records found matching the specified criteria.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-none print-only mt-4 small text-muted text-end border-top pt-2">
                    End of Report &bull; Page 1 of 1
                </div>

            </div>
        </main>

        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
<?php
mysqli_stmt_close($stmt);
?>
