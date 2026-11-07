<?php
/**
 * Term Enrollments Directory (Registrar & Administrator)
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Zero Emojis
 */

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(['Administrator', 'Registrar']);

$conn = db_connect();
$page_title = 'Term Enrollments';

// Request filter parameters
$search = sanitize($_GET['q'] ?? '');
$semester_id = isset($_GET['semester_id']) && is_numeric($_GET['semester_id']) ? (int)$_GET['semester_id'] : 0;
$program_id = isset($_GET['program_id']) && is_numeric($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$status = sanitize($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Fetch semesters for filter dropdown
$semesters_res = mysqli_query($conn, "SELECT sem.semester_id, sem.semester_term, sem.is_active, ay.year_code 
                                      FROM semesters sem 
                                      INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id 
                                      ORDER BY ay.start_year DESC, sem.semester_term ASC");

// Fetch programs for filter dropdown
$programs_res = mysqli_query($conn, "SELECT program_id, program_code, program_name FROM programs ORDER BY program_code ASC");

// Dynamic query construction
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

// Total count for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM enrollments e 
              INNER JOIN students s ON e.student_id = s.student_id 
              INNER JOIN semesters sem ON e.semester_id = sem.semester_id 
              INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id 
              INNER JOIN programs p ON e.program_id = p.program_id 
              WHERE " . $where_sql;

$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_res = mysqli_stmt_get_result($count_stmt);
$total_rows = mysqli_fetch_assoc($count_res)['total'] ?? 0;
mysqli_stmt_close($count_stmt);

$total_pages = ceil($total_rows / $limit);

// Main record retrieval
$sql = "SELECT e.enrollment_id, e.year_level, e.enrollment_date, e.enrollment_status, e.remarks, e.created_at,
               s.student_id, s.student_number, s.first_name, s.middle_name, s.last_name, s.email,
               p.program_code, p.program_name,
               sem.semester_term, sem.is_active as is_term_active,
               ay.year_code,
               u.username as creator_name
        FROM enrollments e
        INNER JOIN students s ON e.student_id = s.student_id
        INNER JOIN semesters sem ON e.semester_id = sem.semester_id
        INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id
        INNER JOIN programs p ON e.program_id = p.program_id
        LEFT JOIN users u ON e.created_by = u.user_id
        WHERE " . $where_sql . "
        ORDER BY e.created_at DESC
        LIMIT ? OFFSET ?";

$select_stmt = mysqli_prepare($conn, $sql);
$data_types = $types . "ii";
$data_params = array_merge($params, [$limit, $offset]);
mysqli_stmt_bind_param($select_stmt, $data_types, ...$data_params);
mysqli_stmt_execute($select_stmt);
$result = mysqli_stmt_get_result($select_stmt);

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
                        <h1 class="h3 mb-1 fw-bold">Term Enrollments</h1>
                        <p class="text-muted small mb-0">Track and manage student enrollments across academic years and semesters.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo APP_URL; ?>/registrar/reports/enrollment-list.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . escape($_SERVER['QUERY_STRING']) : ''; ?>" 
                           class="btn btn-outline-secondary">
                            <i class="bi bi-printer me-1"></i> Print Roster
                        </a>
                        <a href="enroll.php" class="btn btn-primary">
                            <i class="bi bi-person-check-fill me-1"></i> Enroll Student
                        </a>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <!-- Filter Card -->
                <div class="card border-0 shadow-sm mb-4">
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
                                    <?php if ($semesters_res): ?>
                                        <?php while ($sem = mysqli_fetch_assoc($semesters_res)): ?>
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
                                    <?php if ($programs_res): ?>
                                        <?php while ($prog = mysqli_fetch_assoc($programs_res)): ?>
                                            <option value="<?php echo (int)$prog['program_id']; ?>" <?php echo $program_id === (int)$prog['program_id'] ? 'selected' : ''; ?>>
                                                <?php echo escape($prog['program_code']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold mb-1">Status</label>
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
                                    <i class="bi bi-funnel me-1"></i> Filter
                                </button>
                                <a href="index.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 fw-semibold">
                            <i class="bi bi-journal-check me-1 text-primary"></i> Enrollment Records
                        </h6>
                        <span class="badge bg-light text-dark border">
                            Total Records: <?php echo number_format($total_rows); ?>
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Student Number</th>
                                    <th>Student Name</th>
                                    <th>Program & Level</th>
                                    <th>Academic Term</th>
                                    <th>Enrollment Date</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="fw-bold font-monospace">
                                                <a href="<?php echo APP_URL; ?>/registrar/students/view.php?id=<?php echo (int)$row['student_id']; ?>" class="text-decoration-none">
                                                    <?php echo escape($row['student_number']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">
                                                    <?php echo escape($row['last_name'] . ', ' . $row['first_name'] . ($row['middle_name'] ? ' ' . substr($row['middle_name'], 0, 1) . '.' : '')); ?>
                                                </div>
                                                <span class="small text-muted"><?php echo escape($row['email']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?php echo escape($row['program_code']); ?></span>
                                                <span class="small text-muted d-block mt-1"><?php echo escape($row['year_level']); ?></span>
                                            </td>
                                            <td>
                                                <div>AY <?php echo escape($row['year_code']); ?></div>
                                                <span class="small text-muted"><?php echo escape($row['semester_term']); ?></span>
                                                <?php if ($row['is_term_active']): ?>
                                                    <span class="badge bg-success-subtle text-success ms-1 small">Active</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?php echo format_date($row['enrollment_date']); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = 'bg-secondary';
                                                switch ($row['enrollment_status']) {
                                                    case 'Enrolled':
                                                        $badge_class = 'bg-success';
                                                        break;
                                                    case 'Pending':
                                                        $badge_class = 'bg-warning text-dark';
                                                        break;
                                                    case 'Completed':
                                                        $badge_class = 'bg-info text-dark';
                                                        break;
                                                    case 'Dropped':
                                                        $badge_class = 'bg-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo escape($row['enrollment_status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit-status.php?id=<?php echo (int)$row['enrollment_id']; ?>" 
                                                       class="btn btn-outline-primary" 
                                                       title="Update Enrollment Status">
                                                        <i class="bi bi-pencil-square me-1"></i> Status
                                                    </a>
                                                    <a href="history.php?student_id=<?php echo (int)$row['student_id']; ?>" 
                                                       class="btn btn-outline-secondary" 
                                                       title="View Full Enrollment History">
                                                        <i class="bi bi-clock-history"></i>
                                                    </a>
                                                    <form method="POST" action="<?php echo APP_URL; ?>/process/enrollment/delete-enrollment-process.php" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this enrollment record for <?php echo escape($row['student_number']); ?> (<?php echo escape($row['semester_term']); ?>)?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="enrollment_id" value="<?php echo (int)$row['enrollment_id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger" title="Delete Enrollment Record">
                                                            <i class="bi bi-trash-fill"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            No enrollment records found matching the criteria.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer bg-white border-top py-2">
                            <?php 
                            $query_params = $_GET;
                            echo render_pagination($page, $total_pages, $query_params); 
                            ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>

        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
<?php
mysqli_stmt_close($select_stmt);
?>
