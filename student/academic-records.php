<?php
/**
 * Student Academic Records & Term History View
 * Student Information System (SIS)
 * Constitution v2.0.0 Compliant - Strict IDOR Protection - Zero Emojis
 */

require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Student');

$conn = db_connect();
$page_title = 'Academic Records';
$user = current_user();

// Strict IDOR protection: resolve student_id from session
$student_id = (int)($_SESSION['student_id'] ?? 0);
if ($student_id <= 0) {
    $lookup_stmt = mysqli_prepare($conn, "SELECT student_id, student_number FROM students WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($lookup_stmt, "i", $user['user_id']);
    mysqli_stmt_execute($lookup_stmt);
    $st_res = mysqli_stmt_get_result($lookup_stmt);
    if ($st_row = mysqli_fetch_assoc($st_res)) {
        $student_id = (int)$st_row['student_id'];
        $_SESSION['student_id'] = $student_id;
    }
    mysqli_stmt_close($lookup_stmt);
}

// Fetch student profile
$st_stmt = mysqli_prepare($conn, "SELECT s.*, p.program_code, p.program_name 
                                  FROM students s 
                                  LEFT JOIN programs p ON s.program_id = p.program_id 
                                  WHERE s.student_id = ?");
mysqli_stmt_bind_param($st_stmt, "i", $student_id);
mysqli_stmt_execute($st_stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($st_stmt));
mysqli_stmt_close($st_stmt);

// Fetch all chronological enrollment terms
$history = [];
if ($student_id > 0) {
    $sql = "SELECT e.enrollment_id, e.year_level, e.enrollment_date, e.enrollment_status, e.remarks, e.created_at,
                   sem.semester_term, sem.is_active as is_term_active,
                   ay.year_code, ay.start_year, ay.end_year,
                   p.program_code, p.program_name
            FROM enrollments e
            INNER JOIN semesters sem ON e.semester_id = sem.semester_id
            INNER JOIN academic_years ay ON sem.ay_id = ay.ay_id
            INNER JOIN programs p ON e.program_id = p.program_id
            WHERE e.student_id = ?
            ORDER BY ay.start_year DESC, sem.semester_term DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $history[] = $row;
    }
    mysqli_stmt_close($stmt);
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="app-main">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <main class="content-body">
            <div class="container-fluid px-0">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                    <div>
                        <h1 class="h3 mb-1 fw-bold">Academic Records History</h1>
                        <p class="text-muted small mb-0">Complete historical record of academic terms and enrollment progression.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button onclick="window.print()" class="btn btn-outline-primary">
                            <i class="bi bi-printer me-1"></i> Print Transcript Summary
                        </button>
                    </div>
                </div>

                <?php display_alerts(); ?>

                <?php if (!$student): ?>
                    <div class="alert alert-warning border-0 shadow-sm">
                        No student profile record found.
                    </div>
                <?php else: ?>

                    <!-- Student Header Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-secondary me-2"><?php echo escape($student['current_status']); ?></span>
                                        <span class="font-monospace text-muted small"><?php echo escape($student['student_number']); ?></span>
                                    </div>
                                    <h4 class="fw-bold mb-1">
                                        <?php echo escape($student['last_name'] . ', ' . $student['first_name'] . ($student['middle_name'] ? ' ' . $student['middle_name'] : '')); ?>
                                    </h4>
                                    <p class="text-muted small mb-0">
                                        <?php echo escape($student['program_code'] . ' - ' . $student['program_name']); ?>
                                        <span class="mx-2">&bull;</span>
                                        Admitted: <?php echo format_date($student['created_at']); ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <div class="bg-light p-3 rounded d-inline-block text-center border">
                                        <div class="fs-4 fw-bold text-primary"><?php echo count($history); ?></div>
                                        <small class="text-muted text-uppercase fw-semibold">Recorded Terms</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Terms Table -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="card-title mb-0 fw-semibold">
                                <i class="bi bi-clock-history me-1 text-primary"></i> Academic Terms History
                            </h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Academic Year</th>
                                        <th>Semester Term</th>
                                        <th>Degree Program</th>
                                        <th>Year Level</th>
                                        <th>Date Enrolled</th>
                                        <th>Standing Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($history)): ?>
                                        <?php foreach ($history as $row): ?>
                                            <tr>
                                                <td class="fw-bold">
                                                    AY <?php echo escape($row['year_code']); ?>
                                                </td>
                                                <td>
                                                    <i class="bi bi-calendar-check me-1 text-muted"></i>
                                                    <?php echo escape($row['semester_term']); ?>
                                                    <?php if ($row['is_term_active']): ?>
                                                        <span class="badge bg-success-subtle text-success ms-1 small">Active</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border"><?php echo escape($row['program_code']); ?></span>
                                                </td>
                                                <td>
                                                    <?php echo escape($row['year_level']); ?>
                                                </td>
                                                <td class="small text-muted">
                                                    <?php echo format_date($row['enrollment_date']); ?>
                                                </td>
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
                                                <td class="small text-muted">
                                                    <?php echo !empty($row['remarks']) ? escape($row['remarks']) : '&mdash;'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                                No enrollment terms found on your academic record.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </main>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
