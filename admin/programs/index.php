<?php
/**
 * Course & Degree Program Directory (Administrator Tier)
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
$page_title = 'Courses & Programs';

// Filters & Pagination
$search = sanitize($_GET['q'] ?? '');
$department_filter = sanitize($_GET['dept'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Summary counts
$total_programs = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM programs"))[0];
$active_programs = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM programs WHERE status = 'Active'"))[0];
$inactive_programs = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM programs WHERE status = 'Inactive'"))[0];

// Distinct departments for filter
$dept_res = mysqli_query($conn, "SELECT DISTINCT department FROM programs WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
$all_departments = mysqli_fetch_all($dept_res, MYSQLI_ASSOC);

// Dynamic Query Building
$where = ["1=1"];
$types = "";
$params = [];

if (!empty($search)) {
    $where[] = "(program_code LIKE ? OR program_name LIKE ? OR department LIKE ?)";
    $types .= "sss";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($department_filter)) {
    $where[] = "department = ?";
    $types .= "s";
    $params[] = $department_filter;
}

if (!empty($status_filter) && in_array($status_filter, ['Active', 'Inactive'], true)) {
    $where[] = "status = ?";
    $types .= "s";
    $params[] = $status_filter;
}

$where_clause = implode(" AND ", $where);

// Count Total Matching Records
$count_sql = "SELECT COUNT(*) FROM programs WHERE {$where_clause}";
$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$total_records = (int)mysqli_stmt_get_result($count_stmt)->fetch_row()[0];
mysqli_stmt_close($count_stmt);

$total_pages = max(1, ceil($total_records / $limit));

// Fetch Paginated Programs with Student Counts
$data_sql = "SELECT p.*, 
                    (SELECT COUNT(*) FROM students s WHERE s.program_id = p.program_id) AS student_count
             FROM programs p
             WHERE {$where_clause}
             ORDER BY p.status ASC, p.program_code ASC
             LIMIT ? OFFSET ?";

$data_stmt = mysqli_prepare($conn, $data_sql);
$data_types = $types . "ii";
$data_params = array_merge($params, [$limit, $offset]);
mysqli_stmt_bind_param($data_stmt, $data_types, ...$data_params);
mysqli_stmt_execute($data_stmt);
$programs_res = mysqli_stmt_get_result($data_stmt);

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content-body">
            <div class="container-fluid px-0">
                <!-- Header -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                    <div>
                        <h1 class="h3 mb-1 fw-bold">Courses & Degree Programs</h1>
                        <p class="text-muted small mb-0">Manage institutional curricula, degree offerings, and academic departments.</p>
                    </div>
                    <div>
                        <a href="create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add New Course / Program
                        </a>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <!-- Summary Metrics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="sis-card stat-card p-3">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon-wrapper stat-icon-blue me-3">
                                    <i class="bi bi-book fs-4"></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo $total_programs; ?></div>
                                    <div class="stat-label">Total Degree Programs</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="sis-card stat-card p-3">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon-wrapper stat-icon-green me-3">
                                    <i class="bi bi-check-circle fs-4"></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo $active_programs; ?></div>
                                    <div class="stat-label">Active Degree Offerings</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="sis-card stat-card p-3">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon-wrapper stat-icon-amber me-3">
                                    <i class="bi bi-slash-circle fs-4"></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo $inactive_programs; ?></div>
                                    <div class="stat-label">Inactive Programs</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search & Filters -->
                <div class="sis-card p-3 mb-4">
                    <form action="index.php" method="GET" class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="q" class="form-control border-start-0" placeholder="Search by code, title, or department..." value="<?php echo escape($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="dept" class="form-select form-select-sm">
                                <option value="">All Departments</option>
                                <?php foreach ($all_departments as $d): ?>
                                    <option value="<?php echo escape($d['department']); ?>" <?php echo $department_filter === $d['department'] ? 'selected' : ''; ?>>
                                        <?php echo escape($d['department']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active Only</option>
                                <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-1">
                            <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
                            <a href="index.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>

                <!-- Table Card -->
                <div class="sis-card">
                    <div class="table-responsive">
                        <table class="table sis-table align-middle">
                            <thead>
                                <tr>
                                    <th>Program Code</th>
                                    <th>Degree / Course Title</th>
                                    <th>Department</th>
                                    <th>Enrolled Students</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($programs_res) > 0): ?>
                                    <?php while ($p = mysqli_fetch_assoc($programs_res)): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-light text-primary border font-monospace fs-6">
                                                    <?php echo escape($p['program_code']); ?>
                                                </span>
                                            </td>
                                            <td class="fw-semibold">
                                                <?php echo escape($p['program_name']); ?>
                                            </td>
                                            <td class="small text-muted">
                                                <i class="bi bi-building me-1"></i><?php echo escape($p['department']); ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo APP_URL; ?>/admin/students/index.php?program_id=<?php echo $p['program_id']; ?>" class="badge bg-secondary-subtle text-dark text-decoration-none border" title="View students in this program">
                                                    <i class="bi bi-people me-1"></i><?php echo (int)$p['student_count']; ?> Students
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge badge-status <?php echo $p['status'] === 'Active' ? 'badge-active' : 'badge-inactive'; ?>">
                                                    <?php echo escape($p['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small"><?php echo format_date($p['created_at']); ?></td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1 align-items-center">
                                                    <a href="edit.php?id=<?php echo $p['program_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit Course / Program">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <form action="<?php echo APP_URL; ?>/process/programs/toggle-status-process.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to <?php echo $p['status'] === 'Active' ? 'deactivate' : 'activate'; ?> this course?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="program_id" value="<?php echo $p['program_id']; ?>">
                                                        <button type="submit" class="btn btn-sm <?php echo $p['status'] === 'Active' ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $p['status'] === 'Active' ? 'Deactivate Program' : 'Activate Program'; ?>">
                                                            <i class="bi <?php echo $p['status'] === 'Active' ? 'bi-slash-circle' : 'bi-check-circle'; ?>"></i>
                                                        </button>
                                                    </form>
                                                    <form action="<?php echo APP_URL; ?>/process/programs/delete-program-process.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete course / program \'<?php echo escape($p['program_code']); ?>\'?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="program_id" value="<?php echo (int)$p['program_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Course / Program">
                                                            <i class="bi bi-trash-fill"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="bi bi-book"></i>
                                                <div class="empty-state-title">No Courses / Programs Found</div>
                                                <div class="empty-state-text">No academic programs matched your current search and filter criteria.</div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="p-3 border-top">
                            <?php echo render_pagination($page, $total_pages, ['q' => $search, 'dept' => $department_filter, 'status' => $status_filter]); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
