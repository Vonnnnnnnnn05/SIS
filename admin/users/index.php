<?php
/**
 * User Account Directory (Administrator Tier)
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
$page_title = 'User Accounts Management';

// Filter parameters
$search = sanitize($_GET['q'] ?? '');
$role_id = isset($_GET['role_id']) && is_numeric($_GET['role_id']) ? (int)$_GET['role_id'] : 0;
$status = sanitize($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Build dynamic WHERE clause
$where_clauses = ["1=1"];
$types = "";
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(u.username LIKE ? OR u.email LIKE ?)";
    $types .= "ss";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($role_id > 0) {
    $where_clauses[] = "u.role_id = ?";
    $types .= "i";
    $params[] = $role_id;
}

if (!empty($status) && in_array($status, ['Active', 'Inactive'], true)) {
    $where_clauses[] = "u.status = ?";
    $types .= "s";
    $params[] = $status;
}

$where_sql = implode(" AND ", $where_clauses);

// Count total matching records for pagination
$count_sql = "SELECT COUNT(*) as total FROM users u WHERE " . $where_sql;
$count_stmt = mysqli_prepare($conn, $count_sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_res = mysqli_stmt_get_result($count_stmt);
$total_rows = mysqli_fetch_assoc($count_res)['total'];
mysqli_stmt_close($count_stmt);

$total_pages = ceil($total_rows / $limit);

// Fetch users for current page
$sql = "SELECT u.user_id, u.username, u.email, u.status, u.last_login_at, u.created_at, r.role_name 
        FROM users u 
        INNER JOIN roles r ON u.role_id = r.role_id 
        WHERE " . $where_sql . " 
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?";

$select_stmt = mysqli_prepare($conn, $sql);
$data_types = $types . "ii";
$data_params = array_merge($params, [$limit, $offset]);
mysqli_stmt_bind_param($select_stmt, $data_types, ...$data_params);
mysqli_stmt_execute($select_stmt);
$users_result = mysqli_stmt_get_result($select_stmt);

// Fetch roles for filter dropdown
$roles_res = mysqli_query($conn, "SELECT role_id, role_name FROM roles ORDER BY role_id ASC");
$all_roles = mysqli_fetch_all($roles_res, MYSQLI_ASSOC);

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
                        <h1 class="h3 mb-1 fw-bold">User Accounts</h1>
                        <p class="text-muted small mb-0">Manage administrator, registrar personnel, and student login credentials.</p>
                    </div>
                    <div>
                        <a href="create.php" class="btn btn-primary">
                            <i class="bi bi-person-plus-fill me-1"></i> Create User Account
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
                                <input type="text" class="form-control" name="q" placeholder="Search username or email..." value="<?php echo escape($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="role_id" class="form-select form-select-sm">
                                <option value="0">All Roles</option>
                                <?php foreach ($all_roles as $r): ?>
                                    <option value="<?php echo $r['role_id']; ?>" <?php echo $role_id == $r['role_id'] ? 'selected' : ''; ?>>
                                        <?php echo escape($r['role_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="Active" <?php echo $status === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $status === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
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
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($users_result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($users_result)): ?>
                                        <tr>
                                            <td class="text-muted small"><?php echo $row['user_id']; ?></td>
                                            <td class="fw-semibold text-dark"><?php echo escape($row['username']); ?></td>
                                            <td><?php echo escape($row['email']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo escape($row['role_name']); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] === 'Active'): ?>
                                                    <span class="badge badge-status badge-active">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-status badge-inactive">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted small"><?php echo format_date($row['last_login_at'], 'M d, Y h:i A'); ?></td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit.php?id=<?php echo $row['user_id']; ?>" class="btn btn-outline-secondary" title="Edit User">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                                                        <form method="POST" action="<?php echo APP_URL; ?>/process/users/toggle-status-process.php" class="d-inline" onsubmit="return confirm('Toggle status for this account?');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                            <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                            <button type="submit" class="btn btn-outline-<?php echo $row['status'] === 'Active' ? 'warning' : 'success'; ?>" title="Toggle Active/Inactive">
                                                                <i class="bi bi-power"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if ($row['user_id'] != $_SESSION['user_id'] && $row['user_id'] != 1): ?>
                                                        <form method="POST" action="<?php echo APP_URL; ?>/process/users/delete-user-process.php" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete user account \'<?php echo escape($row['username']); ?>\'? This action cannot be undone.');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                            <input type="hidden" name="user_id" value="<?php echo (int)$row['user_id']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger" title="Delete User Account">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="bi bi-people"></i>
                                                <div class="empty-state-title">No User Accounts Found</div>
                                                <div class="empty-state-text">No user records matched your current query or filter criteria.</div>
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
                                'role_id' => $role_id,
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
