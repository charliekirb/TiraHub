<?php
// ============================================================
//  TiraHub – Admin: Manage Applications
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireAdmin();

$pdo     = Database::connect();
$message = '';
$msgType = 'success';

// Handle review action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appId   = (int)($_POST['application_id'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');
    $newStat = match($_POST['action']) {
        'review'  => 'Under Review',
        'approve' => 'Approved',
        'reject'  => 'Rejected',
        default   => ''
    };
    if ($newStat && $appId) {
        $stmt = $pdo->prepare("CALL sp_review_application(?,?,?,?,@msg)");
        $stmt->execute([$appId, $_SESSION['user_id'], $newStat, $remarks]);
        $res     = $pdo->query("SELECT @msg AS message")->fetch();
        $message = $res['message'];
        $msgType = str_contains(strtolower($message), 'updated') ? 'success' : 'danger';
    }
}

// Filter
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['search'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 12;
$offset       = ($page - 1) * $perPage;

$where   = [];
$params  = [];

if ($statusFilter) { $where[] = 'a.status = ?';       $params[] = $statusFilter; }
if ($search)       { $where[] = '(sf.full_name LIKE ? OR sf.student_number LIKE ? OR sf.email LIKE ?)';
                     $params  = array_merge($params, ["%$search%","%$search%","%$search%"]); }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countSQL = "SELECT COUNT(*) FROM vw_applications_detail a
             INNER JOIN vw_students_full sf ON a.student_id = sf.student_id $whereSQL";
$total    = (int)$pdo->prepare($countSQL)->execute($params) ? $pdo->prepare($countSQL)->execute($params) : 0;
$stmtC    = $pdo->prepare($countSQL); $stmtC->execute($params);
$total    = (int)$stmtC->fetchColumn();

$listSQL  = "SELECT a.* FROM vw_applications_detail a
             INNER JOIN vw_students_full sf ON a.student_id = sf.student_id
             $whereSQL ORDER BY a.submitted_at DESC LIMIT $perPage OFFSET $offset";
$stmtL    = $pdo->prepare($listSQL); $stmtL->execute($params);
$apps     = $stmtL->fetchAll();

// View single application
$viewApp  = null;
if (isset($_GET['view'])) {
    $stmtV  = $pdo->prepare("SELECT * FROM vw_applications_detail WHERE application_id = ?");
    $stmtV->execute([(int)$_GET['view']]);
    $viewApp = $stmtV->fetch();
}

renderHead('Applications');
renderAdminNav('applications', $_SESSION['user_id']);
?>

<div class="page-header">
  <div>
    <h4><i class="bi bi-file-earmark-text me-2"></i>Applications</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Applications</li>
      </ol>
    </nav>
  </div>
</div>

<?php if ($message): ?>
  <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'danger' ?> alert-auto-dismiss alert-dismissible">
    <i class="bi bi-<?= $msgType === 'success' ? 'check-circle' : 'x-circle' ?> me-2"></i><?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- View single application modal-like inline detail -->
<?php if ($viewApp): ?>
<div class="th-card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-person-lines-fill me-2"></i>Application #<?= $viewApp['application_id'] ?> – <?= htmlspecialchars($viewApp['student_name']) ?></span>
    <a href="applications.php" class="btn btn-sm btn-th-outline px-3"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>
  <div class="card-body">
    <div class="row g-4">
      <div class="col-md-6">
        <div class="form-section-title">Student Information</div>
        <table class="table table-sm table-borderless">
          <tr><th class="w-40 text-muted">Name</th><td><?= htmlspecialchars($viewApp['student_name']) ?></td></tr>
          <tr><th class="text-muted">Student No.</th><td><?= htmlspecialchars($viewApp['student_number']) ?></td></tr>
          <tr><th class="text-muted">Email</th><td><?= htmlspecialchars($viewApp['email']) ?></td></tr>
          <tr><th class="text-muted">Course</th><td><?= htmlspecialchars($viewApp['course'] ?? '—') ?></td></tr>
          <tr><th class="text-muted">Year</th><td><?= $viewApp['year_level'] ? 'Year ' . $viewApp['year_level'] : '—' ?></td></tr>
          <tr><th class="text-muted">Gender</th><td><?= htmlspecialchars($viewApp['gender']) ?></td></tr>
        </table>
      </div>
      <div class="col-md-6">
        <div class="form-section-title">Application Details</div>
        <table class="table table-sm table-borderless">
          <tr><th class="w-40 text-muted">Room Type</th><td><?= htmlspecialchars($viewApp['preferred_room_type'] ?? '—') ?></td></tr>
          <tr><th class="text-muted">Status</th><td><?= statusBadge($viewApp['status']) ?></td></tr>
          <tr><th class="text-muted">Submitted</th><td><?= date('M d, Y H:i', strtotime($viewApp['submitted_at'])) ?></td></tr>
          <tr><th class="text-muted">Reviewed By</th><td><?= htmlspecialchars($viewApp['reviewed_by_name'] ?? '—') ?></td></tr>
          <tr><th class="text-muted">Reviewed At</th><td><?= $viewApp['reviewed_at'] ? date('M d, Y H:i', strtotime($viewApp['reviewed_at'])) : '—' ?></td></tr>
        </table>
        <?php if ($viewApp['reason']): ?>
          <div class="form-section-title">Reason / Notes</div>
          <div class="alert alert-green p-2 small"><?= nl2br(htmlspecialchars($viewApp['reason'])) ?></div>
        <?php endif; ?>
        <?php if ($viewApp['remarks']): ?>
          <div class="form-section-title">Admin Remarks</div>
          <div class="alert alert-warning p-2 small"><?= nl2br(htmlspecialchars($viewApp['remarks'])) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (in_array($viewApp['status'], ['Pending','Under Review'])): ?>
    <hr>
    <div class="form-section-title">Review Action</div>
    <form method="POST" class="row g-3">
      <input type="hidden" name="application_id" value="<?= $viewApp['application_id'] ?>">
      <div class="col-12">
        <label class="form-label fw-600">Remarks (optional)</label>
        <textarea name="remarks" class="form-control" rows="2" placeholder="Enter remarks or reason…"></textarea>
      </div>
      <div class="col-auto">
        <button type="submit" name="action" value="review" class="btn btn-info text-white">
          <i class="bi bi-hourglass-split me-1"></i>Mark Under Review
        </button>
      </div>
      <div class="col-auto">
        <button type="submit" name="action" value="approve" class="btn btn-success">
          <i class="bi bi-check-circle me-1"></i>Approve
        </button>
      </div>
      <div class="col-auto">
        <button type="submit" name="action" value="reject" class="btn btn-danger"
                onclick="return confirm('Reject this application?')">
          <i class="bi bi-x-circle me-1"></i>Reject
        </button>
      </div>
    </form>
    <?php elseif ($viewApp['status'] === 'Approved'): ?>
    <hr>
    <a href="rooms.php?assign=<?= $viewApp['student_id'] ?>" class="btn btn-th-primary">
      <i class="bi bi-door-open me-1"></i>Assign Room to Student
    </a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="th-card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-4">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, student no., email…" value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Statuses</option>
          <?php foreach(['Pending','Under Review','Approved','Rejected','Cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-sm btn-th-primary px-3">Filter</button>
        <a href="applications.php" class="btn btn-sm btn-outline-secondary px-3 ms-1">Reset</a>
      </div>
      <div class="col-auto ms-auto">
        <span class="text-muted small"><?= number_format($total) ?> record(s)</span>
      </div>
    </form>
  </div>
</div>

<!-- Applications Table -->
<div class="th-card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="th-table">
        <thead>
          <tr>
            <th>#</th><th>Student</th><th>Course / Year</th><th>Room Type</th>
            <th>Status</th><th>Submitted</th><th>Reviewed By</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($apps)): ?>
            <tr><td colspan="8" class="text-center py-5 text-muted">No applications found.</td></tr>
          <?php else: ?>
            <?php foreach ($apps as $a): ?>
            <tr>
              <td><?= $a['application_id'] ?></td>
              <td>
                <div class="fw-600"><?= htmlspecialchars($a['student_name']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($a['student_number']) ?> · <?= htmlspecialchars($a['email']) ?></small>
              </td>
              <td><small><?= htmlspecialchars($a['course'] ?? '—') ?><?= $a['year_level'] ? ' / Y' . $a['year_level'] : '' ?></small></td>
              <td><small><?= htmlspecialchars($a['preferred_room_type'] ?? '—') ?></small></td>
              <td><?= statusBadge($a['status']) ?></td>
              <td><small><?= date('M d, Y', strtotime($a['submitted_at'])) ?></small></td>
              <td><small><?= htmlspecialchars($a['reviewed_by_name'] ?? '—') ?></small></td>
              <td>
                <a href="?view=<?= $a['application_id'] ?>" class="btn btn-sm btn-th-primary px-2 py-1" title="View">
                  <i class="bi bi-eye"></i>
                </a>
                <?php if ($a['status'] === 'Approved'): ?>
                <a href="rooms.php?assign=<?= $a['student_id'] ?>" class="btn btn-sm btn-success px-2 py-1 ms-1" title="Assign Room">
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
      <?= paginate($total, $page, $perPage, '?status=' . urlencode($statusFilter) . '&search=' . urlencode($search)) ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php renderFooter(); ?>
