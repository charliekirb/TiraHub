<?php
// ============================================================
//  TiraHub – Admin: Notifications
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireAdmin();

$pdo = Database::connect();

// Mark all read
if (isset($_POST['mark_all_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$_SESSION['user_id']]);
}
// Mark single read
if (isset($_GET['read'])) {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE notification_id=? AND user_id=?")
        ->execute([(int)$_GET['read'], $_SESSION['user_id']]);
}

$notifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50");
$notifs->execute([$_SESSION['user_id']]);
$notifications = $notifs->fetchAll();

renderHead('Notifications');
renderAdminNav('notifications', $_SESSION['user_id']);
?>
<div class="page-header">
  <div>
    <h4><i class="bi bi-bell me-2"></i>Notifications</h4>
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
      <div class="text-center py-5 text-muted"><i class="bi bi-bell-slash fs-2 d-block mb-2"></i>No notifications.</div>
    <?php else: ?>
      <?php foreach ($notifications as $n):
        $iconMap = ['Info'=>'bi-info-circle text-info','Success'=>'bi-check-circle text-success','Warning'=>'bi-exclamation-triangle text-warning','Error'=>'bi-x-circle text-danger'];
        $icon = $iconMap[$n['type']] ?? 'bi-bell text-secondary';
        $bg   = $n['is_read'] ? '' : 'background:var(--th-green-pale);';
      ?>
      <div class="d-flex align-items-start gap-3 p-3 border-bottom" style="<?= $bg ?>">
        <i class="bi <?= $icon ?> fs-5 mt-1 flex-shrink-0"></i>
        <div class="flex-grow-1">
          <div class="fw-600 <?= $n['is_read']?'text-muted':'' ?>"><?= htmlspecialchars($n['title']) ?></div>
          <div class="small text-muted"><?= htmlspecialchars($n['message']) ?></div>
          <small class="text-muted"><?= date('M d, Y H:i', strtotime($n['created_at'])) ?></small>
        </div>
        <?php if (!$n['is_read']): ?>
          <a href="?read=<?= $n['notification_id'] ?>" class="btn btn-sm btn-outline-secondary px-2 py-1 flex-shrink-0">
            <i class="bi bi-check2"></i>
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
