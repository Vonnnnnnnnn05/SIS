<?php
/**
 * System Activity Audit Logs Viewer (Administrator Tier)
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
$page_title = 'Activity Audit Logs';
$user = current_user();

// Request filter parameters
$search = sanitize($_GET['q'] ?? '');
$action_type = sanitize($_GET['action_type'] ?? '');
$module = sanitize($_GET['module'] ?? '');
$date_from = sanitize($_GET['date_from'] ?? '');
$date_to = sanitize($_GET['date_to'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Dynamic WHERE clauses
$where = ["1=1"];
$types = "";
$params = [];

if (!empty($search)) {
    $where[] = "(l.details LIKE ? OR l.target_id LIKE ? OR l.ip_address LIKE ? OR u.username LIKE ?)";
    $types .= "ssss";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($action_type)) {
    $where[] = "l.action_type = ?";
    $types .= "s";
    $params[] = $action_type;
}

if (!empty($module)) {
    $where[] = "l.module = ?";
    $types .= "s";
    $params[] = $module;
}

if (!empty($date_from)) {
    $where[] = "DATE(l.created_at) >= ?";
    $types .= "s";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where[] = "DATE(l.created_at) <= ?";
    $types .= "s";
    $params[] = $date_to;
}

$where_sql = implode(" AND ", $where);

// Total count for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM system_logs l 
              LEFT JOIN users u ON l.user_id = u.user_id 
              WHERE " . $where_sql;
$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_res = mysqli_stmt_get_result($count_stmt);
$total_rows = (int)(mysqli_fetch_assoc($count_res)['total'] ?? 0);
mysqli_stmt_close($count_stmt);

$total_pages = ceil($total_rows / $limit);

// Main record retrieval
$sql = "SELECT l.log_id, l.user_id, l.role, l.action_type, l.module, l.target_id, 
               l.details, l.ip_address, l.user_agent, l.created_at,
               u.username
        FROM system_logs l
        LEFT JOIN users u ON l.user_id = u.user_id
        WHERE " . $where_sql . "
        ORDER BY l.created_at DESC
        LIMIT ? OFFSET ?";

$select_stmt = mysqli_prepare($conn, $sql);
$data_types = $types . "ii";
$data_params = array_merge($params, [$limit, $offset]);
mysqli_stmt_bind_param($select_stmt, $data_types, ...$data_params);
mysqli_stmt_execute($select_stmt);
$result = mysqli_stmt_get_result($select_stmt);

// Distinct action types and modules for dropdowns
$actions_res = mysqli_query($conn, "SELECT DISTINCT action_type FROM system_logs ORDER BY action_type ASC");
$modules_res = mysqli_query($conn, "SELECT DISTINCT module FROM system_logs ORDER BY module ASC");

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
                        <h1 class="h3 mb-1 fw-bold">Activity Audit Logs</h1>
                        <p class="text-muted small mb-0">Immutable chronological audit trail of all security, authentication, and data operations.</p>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <!-- Filter Controls Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3">
                        <form method="GET" action="" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Search Keywords</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" name="q" class="form-control" placeholder="Details, target ID, IP, user..." value="<?php echo escape($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold mb-1">Action Type</label>
                                <select name="action_type" class="form-select form-select-sm">
                                    <option value="">All Actions</option>
                                    <?php if ($actions_res): ?>
                                        <?php while ($act = mysqli_fetch_assoc($actions_res)): ?>
                                            <option value="<?php echo escape($act['action_type']); ?>" <?php echo $action_type === $act['action_type'] ? 'selected' : ''; ?>>
                                                <?php echo escape($act['action_type']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold mb-1">Module</label>
                                <select name="module" class="form-select form-select-sm">
                                    <option value="">All Modules</option>
                                    <?php if ($modules_res): ?>
                                        <?php while ($mod = mysqli_fetch_assoc($modules_res)): ?>
                                            <option value="<?php echo escape($mod['module']); ?>" <?php echo $module === $mod['module'] ? 'selected' : ''; ?>>
                                                <?php echo escape($mod['module']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold mb-1">From Date</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo escape($date_from); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold mb-1">To Date</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo escape($date_to); ?>">
                            </div>
                            <div class="col-md-1 d-flex gap-1">
                                <button type="submit" class="btn btn-sm btn-primary w-100" title="Apply Filters">
                                    <i class="bi bi-funnel"></i>
                                </button>
                                <a href="index.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Log Records Table Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 fw-semibold">
                            <i class="bi bi-shield-check me-1 text-primary"></i> System Event Logs
                        </h6>
                        <span class="badge bg-light text-dark border">
                            Total Entries: <?php echo number_format($total_rows); ?>
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Actor</th>
                                    <th>Action</th>
                                    <th>Module</th>
                                    <th>Target ID</th>
                                    <th>Event Details</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="text-nowrap text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo format_date($row['created_at'], 'M d, Y H:i:s'); ?>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark"><?php echo escape($row['username'] ?? 'System / Guest'); ?></span>
                                                <div class="text-muted" style="font-size: 0.75rem;">
                                                    <?php echo escape($row['role']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $action_badge = 'bg-secondary';
                                                switch ($row['action_type']) {
                                                    case 'LOGIN':
                                                        $action_badge = 'bg-info text-dark';
                                                        break;
                                                    case 'LOGOUT':
                                                        $action_badge = 'bg-secondary';
                                                        break;
                                                    case 'CREATE':
                                                        $action_badge = 'bg-success';
                                                        break;
                                                    case 'UPDATE':
                                                        $action_badge = 'bg-primary';
                                                        break;
                                                    case 'ACTIVE':
                                                        $action_badge = 'bg-success';
                                                        break;
                                                    case 'INACTIVE':
                                                    case 'DEACTIVATE':
                                                        $action_badge = 'bg-warning text-dark';
                                                        break;
                                                    case 'BACKUP':
                                                    case 'RESTORE':
                                                        $action_badge = 'bg-dark';
                                                        break;
                                                    case 'EXPORT':
                                                        $action_badge = 'bg-secondary';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $action_badge; ?>">
                                                    <?php echo escape($row['action_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?php echo escape($row['module']); ?>
                                                </span>
                                            </td>
                                            <td class="font-monospace text-muted">
                                                <?php echo !empty($row['target_id']) ? '#' . escape($row['target_id']) : '&mdash;'; ?>
                                            </td>
                                            <td>
                                                <div class="text-break" style="max-width: 380px;">
                                                    <?php echo escape($row['details']); ?>
                                                </div>
                                            </td>
                                            <td class="font-monospace text-muted small">
                                                <?php echo escape($row['ip_address']); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="bi bi-shield-x fs-2 d-block mb-2"></i>
                                            No audit log entries matching your filter criteria.
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
