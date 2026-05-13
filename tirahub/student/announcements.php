<?php
// ============================================================
//  TiraHub – Student: Announcements
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireStudent();

$pdo  = Database::connect();
$anns = $pdo->query(
    "SELECT a.*, u.username AS posted_by_name FROM announcements a
     INNER JOIN users u ON a.posted_by=u.user_id
     WHERE a.is_published=1 AND (a.expiry_date IS NULL OR a.expiry_date >= NOW())
     ORDER BY a.priority DESC, a.publish_date DESC"
)->fetchAll();

renderHead('Announcements');
renderStudentNav('announcements', $_SESSION['user_id']);
?>
<div class="page-header">
  <div>
    <h4><i class="bi bi-megaphone me-2"></i>Announcements</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Announcements</li></ol></nav>
  </div>
</div>

<?php if (empty($anns)): ?>
  <div class="th-card"><div class="card-body text-center py-5 text-muted"><i class="bi bi-megaphone fs-1 d-block mb-2"></i>No announcements at this time.</div></div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($anns as $a):
      $border = match($a['priority']) { 'Urgent'=>'#dc3545','Important'=>'#f0a500',default=>'var(--th-green)' };
      $bg     = match($a['priority']) { 'Urgent'=>'#fff5f5','Important'=>'#fffbf0',default=>'#fff' };
    ?>
    <div class="col-12">
      <div class="th-card" style="border-left:5px solid <?= $border ?>;background:<?= $bg ?>">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <h6 class="fw-700 mb-0"><?= htmlspecialchars($a['title']) ?></h6>
            <?= statusBadge($a['priority']) ?>
          </div>
          <div class="text-muted small mb-3"><?= nl2br(htmlspecialchars($a['content'])) ?></div>
          <div class="d-flex gap-3 flex-wrap">
            <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($a['posted_by_name']) ?></small>
            <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('M d, Y H:i', strtotime($a['publish_date'])) ?></small>
            <?php if ($a['expiry_date']): ?>
              <small class="text-warning"><i class="bi bi-calendar-x me-1"></i>Expires <?= date('M d, Y', strtotime($a['expiry_date'])) ?></small>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php renderFooter(); ?>
