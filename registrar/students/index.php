<?php
/**
 * Student Directory (Registrar & Administrator)
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
$page_title = 'Student Directory';

// Filters
$search = sanitize($_GET['q'] ?? '');
$program_id = isset($_GET['program_id']) && is_numeric($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$status = sanitize($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Dynamic WHERE clauses
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

// Total count for pagination
$count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM students s WHERE " . $where_sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_res = mysqli_stmt_get_result($count_stmt);
$total_rows = mysqli_fetch_assoc($count_res)['total'];
mysqli_stmt_close($count_stmt);

$total_pages = ceil($total_rows / $limit);

// Fetch students with program information
$sql = "SELECT s.student_id, s.student_number, s.first_name, s.middle_name, s.last_name, s.email, 
               s.contact_number, s.current_status, s.created_at, p.program_code, p.program_name 
        FROM students s 
        LEFT JOIN programs p ON s.program_id = p.program_id 
        WHERE " . $where_sql . " 
        ORDER BY s.last_name ASC, s.first_name ASC 
        LIMIT ? OFFSET ?";

$select_stmt = mysqli_prepare($conn, $sql);
$data_types = $types . "ii";
$data_params = array_merge($params, [$limit, $offset]);
mysqli_stmt_bind_param($select_stmt, $data_types, ...$data_params);
mysqli_stmt_execute($select_stmt);
$students_result = mysqli_stmt_get_result($select_stmt);

// Fetch programs for filter dropdown
$prog_res = mysqli_query($conn, "SELECT program_id, program_code, program_name FROM programs WHERE status = 'Active' ORDER BY program_code ASC");
$all_programs = mysqli_fetch_all($prog_res, MYSQLI_ASSOC);

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
                        <h1 class="h3 mb-1 fw-bold">Student Records</h1>
                        <p class="text-muted small mb-0">Search, manage, and review institutional student profiles.</p>
                    </div>
                    <div>
                        <a href="register.php" class="btn btn-primary">
                            <i class="bi bi-person-plus-fill me-1"></i> Register New Student
                        </a>
                    </div>
                </div>

                <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

                <!-- Filter Bar -->
                <div class="sis-card p-3 mb-4">
                    <form method="GET" action="index.php" class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" name="q" placeholder="Search ID, name, email..." value="<?php echo escape($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="program_id" class="form-select form-select-sm">
                                <option value="0">All Programs</option>
                                <?php foreach ($all_programs as $p): ?>
                                    <option value="<?php echo $p['program_id']; ?>" <?php echo $program_id == $p['program_id'] ? 'selected' : ''; ?>>
                                        <?php echo escape($p['program_code']); ?> - <?php echo escape($p['program_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="Active" <?php echo $status === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $status === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Graduated" <?php echo $status === 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                                <option value="Withdrawn" <?php echo $status === 'Withdrawn' ? 'selected' : ''; ?>>Withdrawn</option>
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
                                    <th>Student Number</th>
                                    <th>Full Name</th>
                                    <th>Program</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($students_result) > 0): ?>
                                    <?php while ($s = mysqli_fetch_assoc($students_result)): ?>
                                        <tr>
                                            <td class="fw-bold text-primary font-monospace"><?php echo escape($s['student_number']); ?></td>
                                            <td class="fw-semibold">
                                                <?php echo escape($s['last_name'] . ', ' . $s['first_name'] . ($s['middle_name'] ? ' ' . $s['middle_name'] : '')); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?php echo escape($s['program_code'] ?? 'None'); ?></span>
                                            </td>
                                            <td class="small">
                                                <div><i class="bi bi-envelope me-1 text-muted"></i><?php echo escape($s['email']); ?></div>
                                                <div><i class="bi bi-telephone me-1 text-muted"></i><?php echo escape($s['contact_number']); ?></div>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = 'badge-active';
                                                if ($s['current_status'] === 'Inactive') $badge_class = 'badge-inactive';
                                                elseif ($s['current_status'] === 'Graduated') $badge_class = 'badge-completed';
                                                elseif ($s['current_status'] === 'Withdrawn') $badge_class = 'badge-withdrawn';
                                                ?>
                                                <span class="badge badge-status <?php echo $badge_class; ?>">
                                                    <?php echo escape($s['current_status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small"><?php echo format_date($s['created_at']); ?></td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view.php?id=<?php echo $s['student_id']; ?>" class="btn btn-outline-primary" title="View Profile">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $s['student_id']; ?>" class="btn btn-outline-secondary" title="Edit Profile">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="<?php echo APP_URL; ?>/registrar/enrollment/enroll.php?student_id=<?php echo $s['student_id']; ?>" class="btn btn-outline-success" title="Enroll in Term">
                                                        <i class="bi bi-journal-plus"></i>
                                                    </a>
                                                    <form method="POST" action="<?php echo APP_URL; ?>/process/students/delete-student-process.php" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete student \'<?php echo escape($s['student_number']); ?>\'? All historical enrollments and records will be removed.');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="student_id" value="<?php echo (int)$s['student_id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger" title="Delete Student Record">
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
                                                <i class="bi bi-mortarboard"></i>
                                                <div class="empty-state-title">No Students Found</div>
                                                <div class="empty-state-text">No student records matched your current query or filter criteria.</div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="sis-card-body border-top py-2">
                            <?php 
                            echo render_pagination($page, $total_pages, [
                                'q' => $search,
                                'program_id' => $program_id,
                                'status' => $status
                            ]); 
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
<?php mysqli_stmt_close($select_stmt); ?>
