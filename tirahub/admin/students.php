<?php
// ============================================================
//  TiraHub – Admin: Students Management
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireAdmin();

$pdo     = Database::connect();
$message = '';
$msgType = 'success';

// Toggle active status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_active'])) {
    $userId    = (int)$_POST['user_id'];
    $newStatus = (int)$_POST['new_status'];
    $pdo->prepare("UPDATE users SET is_active=? WHERE user_id=?")->execute([$newStatus, $userId]);
    $message = 'Student account ' . ($newStatus ? 'activated' : 'deactivated') . '.';
}

// Checkout student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $studId = (int)$_POST['student_id'];
    $stmt = $pdo->prepare("CALL sp_checkout_student(?,?,?,@msg)");
    $stmt->execute([$studId, $_SESSION['user_id'], date('Y-m-d')]);
    $res     = $pdo->query("SELECT @msg AS message")->fetch();
    $message = $res['message'];
    $msgType = str_contains($message, 'successfully') ? 'success' : 'danger';
}

$search   = trim($_GET['search'] ?? '');
$page     = max(1,(int)($_GET['page'] ?? 1));
$perPage  = 15;
$offset   = ($page-1)*$perPage;

$where  = []; $params = [];
if ($search) {
    $where[]  = "(sf.full_name LIKE ? OR sf.student_number LIKE ? OR sf.email LIKE ? OR sf.course LIKE ?)";
    $p        = "%$search%";
    $params   = [$p,$p,$p,$p];
}
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

$stmtC = $pdo->prepare("SELECT COUNT(*) FROM vw_students_full sf $whereSQL");
$stmtC->execute($params); $total = (int)$stmtC->fetchColumn();

$stmtL = $pdo->prepare(
    "SELECT sf.*,
            ra.room_id, ra.check_in_date, ra.status AS assign_status,
            r.room_number, b.building_name
     FROM vw_students_full sf
     LEFT JOIN room_assignments ra ON sf.student_id = ra.student_id AND ra.status='Active'
     LEFT JOIN rooms r ON ra.room_id = r.room_id
     LEFT JOIN buildings b ON r.building_id = b.building_id
     $whereSQL
     ORDER BY sf.last_name, sf.first_name
     LIMIT $perPage OFFSET $offset"
);
$stmtL->execute($params); $students = $stmtL->fetchAll();

// View student detail
$viewStudent = null;
if (isset($_GET['view'])) {
    $sv = $pdo->prepare("SELECT sf.*, ra.check_in_date, ra.status AS assign_status, r.room_number, b.building_name, r.monthly_rate, r.room_type
        FROM vw_students_full sf
        LEFT JOIN room_assignments ra ON sf.student_id=ra.student_id AND ra.status='Active'
        LEFT JOIN rooms r ON ra.room_id=r.room_id
        LEFT JOIN buildings b ON r.building_id=b.building_id
        WHERE sf.student_id=?");
    $sv->execute([(int)$_GET['view']]); $viewStudent = $sv->fetch();

    $bills = $pdo->prepare("SELECT * FROM billing WHERE student_id=? ORDER BY billing_month DESC");
    $bills->execute([(int)$_GET['view']]); $studentBills = $bills->fetchAll();

    $appH = $pdo->prepare("SELECT * FROM vw_applications_detail WHERE student_id=? ORDER BY submitted_at DESC");
    $appH->execute([(int)$_GET['view']]); $appHistory = $appH->fetchAll();
}

renderHead('Students');
renderAdminNav('students', $_SESSION['user_id']);
?>

<div class="page-header">
  <div>
    <h4><i class="bi bi-people me-2"></i>Students</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Students</li>
      </ol>
    </nav>
  </div>
</div>

<?php if ($message): ?>
  <div class="alert alert-<?= $msgType==='success'?'success':'danger' ?> alert-auto-dismiss alert-dismissible">
    <i class="bi bi-<?= $msgType==='success'?'check-circle':'x-circle' ?> me-2"></i><?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if ($viewStudent): ?>
<!-- Student Detail -->
<div class="th-card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-person-badge me-2"></i><?= htmlspecialchars($viewStudent['full_name']) ?></span>
    <a href="students.php" class="btn btn-sm btn-th-outline"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>
  <div class="card-body">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="form-section-title">Personal Info</div>
        <table class="table table-sm table-borderless small">
          <tr><th class="text-muted w-40">Student No.</th><td><?= htmlspecialchars($viewStudent['student_number']) ?></td></tr>
          <tr><th class="text-muted">Email</th><td><?= htmlspecialchars($viewStudent['email']) ?></td></tr>
          <tr><th class="text-muted">Gender</th><td><?= $viewStudent['gender'] ?></td></tr>
          <tr><th class="text-muted">Birthdate</th><td><?= $viewStudent['birthdate'] ? date('M d, Y', strtotime($viewStudent['birthdate'])) : '—' ?></td></tr>
          <tr><th class="text-muted">Contact</th><td><?= htmlspecialchars($viewStudent['contact_number'] ?? '—') ?></td></tr>
          <tr><th class="text-muted">Course</th><td><?= htmlspecialchars($viewStudent['course'] ?? '—') ?></td></tr>
          <tr><th class="text-muted">Year</th><td><?= $viewStudent['year_level'] ? 'Year '.$viewStudent['year_level'] : '—' ?></td></tr>
          <tr><th class="text-muted">Address</th><td><?= htmlspecialchars($viewStudent['address'] ?? '—') ?></td></tr>
          <tr><th class="text-muted">Account</th><td><?= $viewStudent['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' ?></td></tr>
        </table>
        <form method="POST" class="d-inline me-2">
          <input type="hidden" name="user_id" value="<?= $viewStudent['user_id'] ?>">
          <input type="hidden" name="new_status" value="<?= $viewStudent['is_active'] ? 0 : 1 ?>">
          <button type="submit" name="toggle_active" value="1"
            class="btn btn-sm <?= $viewStudent['is_active'] ? 'btn-warning' : 'btn-success' ?>">
            <?= $viewStudent['is_active'] ? 'Deactivate Account' : 'Activate Account' ?>
          </button>
        </form>
        <?php if ($viewStudent['assign_status'] === 'Active'): ?>
        <form method="POST" class="d-inline">
          <input type="hidden" name="student_id" value="<?= $viewStudent['student_id'] ?>">
          <button type="submit" name="checkout" value="1"
            class="btn btn-sm btn-danger"
            data-confirm="Check out this student?">
            <i class="bi bi-box-arrow-right me-1"></i>Checkout
          </button>
        </form>
        <?php endif; ?>
      </div>
      <div class="col-md-4">
        <div class="form-section-title">Room Assignment</div>
        <?php if ($viewStudent['assign_status'] === 'Active'): ?>
          <div class="alert alert-green">
            <div class="fw-700"><?= htmlspecialchars($viewStudent['building_name']) ?> – Room <?= htmlspecialchars($viewStudent['room_number']) ?></div>
            <div class="small"><?= $viewStudent['room_type'] ?> · ₱<?= number_format($viewStudent['monthly_rate'],2) ?>/mo</div>
            <div class="small text-muted">Since <?= date('M d, Y', strtotime($viewStudent['check_in_date'])) ?></div>
          </div>
        <?php else: ?>
          <div class="alert alert-warning small">No active room assignment.</div>
          <a href="rooms.php?assign=<?= $viewStudent['student_id'] ?>" class="btn btn-sm btn-th-primary">
            <i class="bi bi-door-open me-1"></i>Assign Room
          </a>
        <?php endif; ?>

        <div class="form-section-title mt-3">Application History</div>
        <?php foreach ($appHistory as $ah): ?>
          <div class="d-flex align-items-center justify-content-between border-bottom py-1 small">
            <span><?= date('M d, Y', strtotime($ah['submitted_at'])) ?></span>
            <?= statusBadge($ah['status']) ?>
          </div>
        <?php endforeach; ?>
        <?php if (empty($appHistory)): ?><p class="text-muted small">No applications.</p><?php endif; ?>
      </div>
      <div class="col-md-4">
        <div class="form-section-title">Billing Summary</div>
        <?php if (empty($studentBills)): ?>
          <p class="text-muted small">No billing records.</p>
        <?php else: ?>
          <?php foreach ($studentBills as $bill): ?>
          <div class="border rounded p-2 mb-2 small">
            <div class="d-flex justify-content-between">
              <strong><?= date('M Y', strtotime($bill['billing_month'])) ?></strong>
              <?= statusBadge($bill['status']) ?>
            </div>
            <div class="text-muted">Due: ₱<?= number_format($bill['amount_due'],2) ?> · Paid: ₱<?= number_format($bill['amount_paid'],2) ?></div>
            <?php if ($bill['amount_due'] > $bill['amount_paid']): ?>
              <div class="text-danger">Balance: ₱<?= number_format($bill['amount_due']-$bill['amount_paid'],2) ?></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php else: ?>

<!-- Filters -->
<div class="th-card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-5">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, student no., email, course…" value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-auto">
        <button class="btn btn-sm btn-th-primary px-3">Search</button>
        <a href="students.php" class="btn btn-sm btn-outline-secondary px-3 ms-1">Reset</a>
      </div>
      <div class="col-auto ms-auto"><span class="text-muted small"><?= $total ?> student(s)</span></div>
    </form>
  </div>
</div>

<!-- Table -->
<div class="th-card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="th-table">
        <thead>
          <tr><th>#</th><th>Student</th><th>Course / Year</th><th>Contact</th><th>Room</th><th>Account</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr><td colspan="7" class="text-center py-5 text-muted">No students found.</td></tr>
          <?php else: ?>
            <?php foreach ($students as $s): ?>
            <tr>
              <td><?= $s['student_id'] ?></td>
              <td>
                <div class="fw-600"><?= htmlspecialchars($s['full_name']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($s['student_number']) ?> · <?= htmlspecialchars($s['email']) ?></small>
              </td>
              <td><small><?= htmlspecialchars($s['course'] ?? '—') ?><?= $s['year_level'] ? ' / Y'.$s['year_level'] : '' ?></small></td>
              <td><small><?= htmlspecialchars($s['contact_number'] ?? '—') ?></small></td>
              <td>
                <?php if ($s['assign_status'] === 'Active'): ?>
                  <span class="badge bg-success"><?= htmlspecialchars($s['building_name']) ?> - <?= htmlspecialchars($s['room_number']) ?></span>
                <?php else: ?>
                  <span class="text-muted small">Unassigned</span>
                <?php endif; ?>
              </td>
              <td><?= $s['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' ?></td>
              <td>
                <a href="?view=<?= $s['student_id'] ?>" class="btn btn-sm btn-th-primary px-2 py-1" title="View">
                  <i class="bi bi-eye"></i>
                </a>
                <?php if (!$s['room_id']): ?>
                <a href="rooms.php?assign=<?= $s['student_id'] ?>" class="btn btn-sm btn-success px-2 py-1 ms-1" title="Assign Room">
                  <i class="bi bi-door-open"></i>
                </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($total > $perPage): ?>
    <div class="d-flex justify-content-end p-3">
      <?= paginate($total, $page, $perPage, '?search='.urlencode($search)) ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php renderFooter(); ?>
