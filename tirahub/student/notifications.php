<?php
// ============================================================
//  TiraHub – Student: Notifications
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireStudent();

$pdo = Database::connect();

if (isset($_POST['mark_all_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$_SESSION['user_id']]);
}
if (isset($_GET['read'])) {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE notification_id=? AND user_id=?")
        ->execute([(int)$_GET['read'], $_SESSION['user_id']]);
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 80");
$stmt->execute([$_SESSION['user_id']]); $notifications = $stmt->fetchAll();

renderHead('Notifications');
renderStudentNav('notifications', $_SESSION['user_id']);
?>
<div class="page-header">
  <div>
    <h4><i class="bi bi-bell me-2"></i>Notifications</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Notifications</li></ol></nav>
  </div>
  <form method="POST">
    <button type="submit" name="mark_all_read" value="1" class="btn btn-sm btn-th-outline">
      <i class="bi bi-check2-all me-1"></i>Mark All Read
    </button>
  </form>
</div>

<div class="th-card">
  <div class="card-body p-0">
    <?php if (empty($notifications)): ?>
      <div class="text-center py-5 text-muted"><i class="bi bi-bell-slash fs-2 d-block mb-2"></i>No notifications yet.</div>
    <?php else: ?>
      <?php foreach ($notifications as $n):
        $iconMap = ['Info'=>'bi-info-circle text-info','Success'=>'bi-check-circle text-success','Warning'=>'bi-exclamation-triangle text-warning','Error'=>'bi-x-circle text-danger'];
        $icon = $iconMap[$n['type']] ?? 'bi-bell text-secondary';
      ?>
      <div class="d-flex align-items-start gap-3 p-3 border-bottom <?= !$n['is_read']?'':'opacity-75' ?>"
           style="<?= !$n['is_read']?'background:var(--th-green-pale)':'' ?>">
        <i class="bi <?= $icon ?> fs-5 mt-1 flex-shrink-0"></i>
        <div class="flex-grow-1">
          <div class="fw-600 small"><?= htmlspecialchars($n['title']) ?></div>
          <div class="text-muted small"><?= htmlspecialchars($n['message']) ?></div>
          <small class="text-muted"><?= date('M d, Y H:i', strtotime($n['created_at'])) ?></small>
        </div>
        <?php if (!$n['is_read']): ?>
          <a href="?read=<?= $n['notification_id'] ?>" class="btn btn-sm btn-outline-secondary px-2 py-1 flex-shrink-0 text-nowrap">
            <i class="bi bi-check2"></i> Read
          </a>
        <?php else: ?>
          <span class="badge bg-secondary flex-shrink-0">Read</span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php renderFooter(); ?>
