<?php
// ============================================================
//  TiraHub – Student: Application Status
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireStudent();

$pdo       = Database::connect();
$studentId = (int)$_SESSION['student_id'];

$apps = $pdo->prepare("SELECT * FROM vw_applications_detail WHERE student_id=? ORDER BY submitted_at DESC");
$apps->execute([$studentId]); $applications = $apps->fetchAll();

renderHead('Application Status');
renderStudentNav('status', $_SESSION['user_id']);
?>
<div class="page-header">
  <div>
    <h4><i class="bi bi-clipboard-check me-2"></i>Application Status</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Status</li></ol></nav>
  </div>
  <a href="apply.php" class="btn btn-th-primary"><i class="bi bi-plus-circle me-1"></i>New Application</a>
</div>

<?php if (empty($applications)): ?>
  <div class="th-card"><div class="card-body text-center py-5">
    <i class="bi bi-file-earmark-x fs-1 text-muted d-block mb-2"></i>
    <p class="text-muted">No applications found. <a href="apply.php">Apply now</a></p>
  </div></div>
<?php else: ?>
  <?php foreach ($applications as $app):
    $borderMap = ['Approved'=>'#1a7a4a','Rejected'=>'#dc3545','Pending'=>'#f0a500','Under Review'=>'#0d6efd','Cancelled'=>'#6c757d'];
    $border = $borderMap[$app['status']] ?? '#6c757d';
  ?>
  <div class="th-card mb-4" style="border-left:5px solid <?= $border ?>">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Application #<?= $app['application_id'] ?> – <?= statusBadge($app['status']) ?></span>
      <small class="text-muted">Submitted: <?= date('M d, Y H:i', strtotime($app['submitted_at'])) ?></small>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <table class="table table-sm table-borderless small">
            <tr><th class="text-muted w-40">Preferred Type</th><td><?= htmlspecialchars($app['preferred_room_type'] ?? '—') ?></td></tr>
            <tr><th class="text-muted">Reviewed By</th><td><?= htmlspecialchars($app['reviewed_by_name'] ?? 'Pending review') ?></td></tr>
            <tr><th class="text-muted">Reviewed At</th><td><?= $app['reviewed_at'] ? date('M d, Y H:i', strtotime($app['reviewed_at'])) : '—' ?></td></tr>
          </table>
        </div>
        <div class="col-md-6">
          <?php if ($app['reason']): ?>
            <div class="form-section-title">Your Reason</div>
            <p class="small text-muted"><?= nl2br(htmlspecialchars($app['reason'])) ?></p>
          <?php endif; ?>
          <?php if ($app['remarks']): ?>
            <div class="form-section-title">Admin Remarks</div>
            <div class="alert alert-warning py-2 small"><?= nl2br(htmlspecialchars($app['remarks'])) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($app['status'] === 'Approved'): ?>
        <div class="alert alert-green mt-2 small">
          <i class="bi bi-check-circle me-2"></i>Your application is approved! Please wait for the admin to assign your room. You will be notified once a room is assigned.
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php renderFooter(); ?>
