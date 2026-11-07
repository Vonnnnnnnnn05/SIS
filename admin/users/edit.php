<?php
/**
 * Edit User Account Form (Administrator Tier)
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
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    set_flash_message('danger', 'Invalid user account specified.');
    redirect(APP_URL . '/admin/users/index.php');
}

// Fetch user data via prepared statement
$stmt = mysqli_prepare($conn, "SELECT user_id, username, email, role_id, status, must_change_password FROM users WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$target_user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$target_user) {
    set_flash_message('danger', 'User account not found.');
    redirect(APP_URL . '/admin/users/index.php');
}

// Fetch all roles
$roles_res = mysqli_query($conn, "SELECT role_id, role_name, description FROM roles ORDER BY role_id ASC");
$all_roles = mysqli_fetch_all($roles_res, MYSQLI_ASSOC);

$page_title = 'Edit User Account - ' . $target_user['username'];
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <main class="content-body">
            <div class="container-fluid px-0">
                <div class="d-flex align-items-center mb-4">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left"></i> Back to Users
                    </a>
                    <h1 class="h4 mb-0 fw-bold">Edit User Account: <?php echo escape($target_user['username']); ?></h1>
                </div>

                <?php require_once __DIR__ . '/../../includes/alerts.php'; ?>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="sis-card p-4">
                            <form action="<?php echo APP_URL; ?>/process/users/edit-user-process.php" method="POST" autocomplete="off">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="user_id" value="<?php echo (int)$target_user['user_id']; ?>">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="username" class="form-label small fw-semibold">Username</label>
                                        <input type="text" class="form-control bg-light" id="username" value="<?php echo escape($target_user['username']); ?>" readonly>
                                        <div class="form-text small">Usernames cannot be modified.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($target_user['email']); ?>" required>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="role_id" class="form-label small fw-semibold">Account Role <span class="text-danger">*</span></label>
                                        <select class="form-select" id="role_id" name="role_id" required>
                                            <?php foreach ($all_roles as $r): ?>
                                                <option value="<?php echo $r['role_id']; ?>" <?php echo $target_user['role_id'] == $r['role_id'] ? 'selected' : ''; ?>>
                                                    <?php echo escape($r['role_name']); ?> - <?php echo escape($r['description']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status" class="form-label small fw-semibold">Account Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="Active" <?php echo $target_user['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo $target_user['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="p-3 bg-light rounded border mb-4">
                                    <h2 class="h6 fw-bold mb-2">Password Reset (Optional)</h2>
                                    <p class="small text-muted mb-2">Leave blank if you do not wish to reset this user's password.</p>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="new_password" class="form-label small fw-semibold">New Password</label>
                                            <input type="password" class="form-control form-control-sm" id="new_password" name="new_password" minlength="8" placeholder="Enter new password">
                                        </div>
                                        <div class="col-md-6 d-flex align-items-center pt-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="must_change_password" name="must_change_password" value="1" <?php echo $target_user['must_change_password'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label small" for="must_change_password">
                                                    Force password change on next login
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i> Update User Account
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
