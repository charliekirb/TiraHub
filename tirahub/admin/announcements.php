<?php
// ============================================================
//  TiraHub – Admin: Announcements
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireAdmin();

$pdo     = Database::connect();
$message = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $annId    = (int)($_POST['announcement_id'] ?? 0);
    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');
    $priority = $_POST['priority'] ?? 'Normal';
    $published= (int)($_POST['is_published'] ?? 1);
    $expiry   = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

    if (isset($_POST['delete_ann'])) {
        $pdo->prepare("DELETE FROM announcements WHERE announcement_id=?")->execute([$annId]);
        $message = 'Announcement deleted.';
    } elseif ($annId) {
        $pdo->prepare("UPDATE announcements SET title=?,content=?,priority=?,is_published=?,expiry_date=? WHERE announcement_id=?")
            ->execute([$title,$content,$priority,$published,$expiry,$annId]);
        $message = 'Announcement updated.';
    } else {
        $pdo->prepare("INSERT INTO announcements(posted_by,title,content,priority,is_published,expiry_date) VALUES(?,?,?,?,?,?)")
            ->execute([$_SESSION['user_id'],$title,$content,$priority,$published,$expiry]);
        $message = 'Announcement posted.';
    }
}

$anns = $pdo->query("SELECT a.*, u.username AS posted_by_name FROM announcements a INNER JOIN users u ON a.posted_by=u.user_id ORDER BY a.created_at DESC")->fetchAll();

$editAnn = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE announcement_id=?");
    $stmt->execute([(int)$_GET['edit']]); $editAnn = $stmt->fetch();
}

renderHead('Announcements');
renderAdminNav('announcements', $_SESSION['user_id']);
?>
<div class="page-header">
  <div>
    <h4><i class="bi bi-megaphone me-2"></i>Announcements</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Announcements</li></ol></nav>
  </div>
  <button class="btn btn-th-primary" data-bs-toggle="modal" data-bs-target="#annModal"><i class="bi bi-plus-circle me-1"></i>New Announcement</button>
</div>

<?php if ($message): ?>
  <div class="alert alert-success alert-auto-dismiss alert-dismissible"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-3">
  <?php foreach ($anns as $a):
    $borderColor = match($a['priority']) { 'Urgent' => '#dc3545', 'Important' => '#f0a500', default => 'var(--th-green)' };
  ?>
  <div class="col-12">
    <div class="th-card" style="border-left: 5px solid <?= $borderColor ?>">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div>
            <h6 class="fw-700 mb-1"><?= htmlspecialchars($a['title']) ?></h6>
            <div class="d-flex gap-2 flex-wrap mb-2">
              <?= statusBadge($a['priority']) ?>
              <?= $a['is_published'] ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-secondary">Draft</span>' ?>
              <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($a['posted_by_name']) ?></small>
              <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('M d, Y H:i', strtotime($a['created_at'])) ?></small>
              <?php if ($a['expiry_date']): ?><small class="text-warning"><i class="bi bi-calendar-x me-1"></i>Expires <?= date('M d, Y', strtotime($a['expiry_date'])) ?></small><?php endif; ?>
            </div>
            <p class="mb-0 small text-muted"><?= nl2br(htmlspecialchars(substr($a['content'],0,200))) ?><?= strlen($a['content'])>200?'…':'' ?></p>
          </div>
          <div class="d-flex gap-2">
            <a href="?edit=<?= $a['announcement_id'] ?>" class="btn btn-sm btn-warning text-white"><i class="bi bi-pencil"></i></a>
            <form method="POST" class="d-inline">
              <input type="hidden" name="announcement_id" value="<?= $a['announcement_id'] ?>">
              <button type="submit" name="delete_ann" value="1" class="btn btn-sm btn-danger" data-confirm="Delete this announcement?"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($anns)): ?>
    <div class="col-12"><div class="text-center text-muted py-5">No announcements yet.</div></div>
  <?php endif; ?>
</div>

<!-- Modal -->
<div class="modal fade" id="annModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0">
      <div class="modal-header" style="background:var(--th-green);color:#fff">
        <h5 class="modal-title"><i class="bi bi-megaphone me-2"></i><?= $editAnn ? 'Edit' : 'New' ?> Announcement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="announcement_id" value="<?= $editAnn['announcement_id'] ?? 0 ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-600">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editAnn['title'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">Content <span class="text-danger">*</span></label>
            <textarea name="content" class="form-control" rows="5" required><?= htmlspecialchars($editAnn['content'] ?? '') ?></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-600">Priority</label>
              <select name="priority" class="form-select">
                <?php foreach(['Normal','Important','Urgent'] as $pr): ?>
                  <option value="<?= $pr ?>" <?= (($editAnn['priority'] ?? 'Normal')===$pr)?'selected':'' ?>><?= $pr ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-600">Status</label>
              <select name="is_published" class="form-select">
                <option value="1" <?= (($editAnn['is_published'] ?? 1)==1)?'selected':'' ?>>Published</option>
                <option value="0" <?= (($editAnn['is_published'] ?? 1)==0)?'selected':'' ?>>Draft</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-600">Expiry Date</label>
              <input type="date" name="expiry_date" class="form-control" value="<?= htmlspecialchars($editAnn['expiry_date'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-th-primary"><i class="bi bi-send me-1"></i><?= $editAnn ? 'Update' : 'Post' ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if ($editAnn): ?>
<script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('annModal')).show());</script>
<?php endif; ?>

<?php renderFooter(); ?>
