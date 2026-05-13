<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireStudent();
$pdo       = Database::connect();
$studentId = (int)$_SESSION['student_id'];

// Latest application
$appStmt = $pdo->prepare("SELECT application_id,status,remarks,submitted_at,reviewed_at FROM applications WHERE student_id=? ORDER BY submitted_at DESC LIMIT 1");
$appStmt->execute([$studentId]); $latestApp = $appStmt->fetch();

// Room assignment
$roomStmt = $pdo->prepare("SELECT ra.check_in_date, r.room_number, b.building_name, r.room_type, r.monthly_rate, r.floor FROM room_assignments ra INNER JOIN rooms r ON ra.room_id=r.room_id INNER JOIN buildings b ON r.building_id=b.building_id WHERE ra.student_id=? AND ra.status='Active' LIMIT 1");
$roomStmt->execute([$studentId]); $roomInfo = $roomStmt->fetch();

// Outstanding bills
$billStmt = $pdo->prepare("SELECT bill_id,billing_month,amount_due,amount_paid,(amount_due-amount_paid) AS balance,due_date,status FROM billing WHERE student_id=? AND status IN ('Unpaid','Partial','Overdue') ORDER BY due_date ASC");
$billStmt->execute([$studentId]); $bills = $billStmt->fetchAll();

// Notifications
$notifStmt = $pdo->prepare("SELECT notification_id,title,message,type,created_at FROM notifications WHERE user_id=? AND is_read=0 ORDER BY created_at DESC LIMIT 5");
$notifStmt->execute([$_SESSION['user_id']]); $notifs = $notifStmt->fetchAll();

// Announcements
$annStmt = $pdo->query("SELECT title,content,priority,publish_date FROM announcements WHERE is_published=1 AND (expiry_date IS NULL OR expiry_date >= NOW()) ORDER BY publish_date DESC LIMIT 3");
$anns = $annStmt->fetchAll();

// Student info
$stuStmt = $pdo->prepare("SELECT * FROM vw_students_full WHERE student_id=?");
$stuStmt->execute([$studentId]); $stu = $stuStmt->fetch();

renderHead('Student Dashboard');
renderStudentNav('dashboard', $_SESSION['user_id']);
$statusColors = ['Pending'=>'warning','Under Review'=>'info','Approved'=>'success','Rejected'=>'danger','Cancelled'=>'secondary'];
$statusIcons  = ['Pending'=>'bi-clock','Under Review'=>'bi-eye','Approved'=>'bi-check-circle','Rejected'=>'bi-x-circle','Cancelled'=>'bi-slash-circle'];
?>
<div class="page-header">
  <div class="page-header-left">
    <h4><i class="bi bi-speedometer2"></i>My Dashboard</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item active">Overview</li></ol></nav>
  </div>
</div>

<!-- Welcome Banner -->
<div class="p-4 mb-4 rounded-3" style="background:linear-gradient(135deg,#0a3d22,#1a7a4a);color:#fff;position:relative;overflow:hidden">
  <div style="position:absolute;right:-20px;bottom:-20px;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,.05)"></div>
  <div style="position:absolute;right:80px;top:-40px;width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.05)"></div>
  <div class="d-flex align-items-center gap-3">
    <div style="width:54px;height:54px;border-radius:16px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:800;flex-shrink:0">
      <?= strtoupper(substr($stu['first_name']??'S',0,1)) ?>
    </div>
    <div>
      <h5 class="mb-0 fw-800">Welcome back, <?= htmlspecialchars($stu['first_name'] ?? $_SESSION['username']) ?>! 👋</h5>
      <div style="opacity:.8;font-size:.85rem"><?= htmlspecialchars($stu['course']??'') ?><?= $stu['year_level'] ? ' · Year '.$stu['year_level'] : '' ?> · <?= htmlspecialchars($stu['student_number']??'') ?></div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Application Status -->
  <div class="col-md-6 col-lg-4">
    <div class="th-card h-100">
      <div class="card-header"><span><i class="bi bi-file-earmark-text me-2"></i>Application Status</span></div>
      <div class="card-body">
        <?php if ($latestApp): $sc = $statusColors[$latestApp['status']]??'secondary'; $si = $statusIcons[$latestApp['status']]??'bi-circle'; ?>
          <div class="text-center py-2">
            <div style="width:70px;height:70px;border-radius:50%;background:var(--bs-<?= $sc ?>-bg,#f8f9fa);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:2rem">
              <i class="bi <?= $si ?> text-<?= $sc ?>"></i>
            </div>
            <div class="mb-2"><?= statusBadge($latestApp['status']) ?></div>
            <div class="text-muted small">Application #<?= $latestApp['application_id'] ?></div>
            <div class="text-muted small">Submitted: <?= date('M d, Y', strtotime($latestApp['submitted_at'])) ?></div>
            <?php if ($latestApp['remarks']): ?>
              <div class="alert alert-warning mt-3 py-2 text-start small">
                <strong>Remarks:</strong> <?= htmlspecialchars($latestApp['remarks']) ?>
              </div>
            <?php endif; ?>
          </div>
          <a href="status.php" class="btn btn-th-outline w-100 mt-2">View Details</a>
        <?php else: ?>
          <div class="empty-state">
            <i class="bi bi-file-earmark-plus"></i>
            <p>No application yet.</p>
            <a href="apply.php" class="btn btn-th-primary mt-2"><i class="bi bi-plus-circle me-1"></i>Apply Now</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Room Info -->
  <div class="col-md-6 col-lg-4">
    <div class="th-card h-100">
      <div class="card-header"><span><i class="bi bi-house me-2"></i>Room Assignment</span></div>
      <div class="card-body">
        <?php if ($roomInfo): ?>
          <div class="text-center py-2">
            <div style="width:70px;height:70px;border-radius:50%;background:var(--th-green-light);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:2rem">
              <i class="bi bi-door-open text-green"></i>
            </div>
            <h5 class="fw-800 text-green mb-0"><?= htmlspecialchars($roomInfo['building_name']) ?></h5>
            <div class="text-muted mb-2">Room <?= htmlspecialchars($roomInfo['room_number']) ?> · Floor <?= $roomInfo['floor'] ?></div>
            <span class="badge bg-secondary mb-2"><?= $roomInfo['room_type'] ?></span>
            <div class="fw-700 text-green">₱<?= number_format($roomInfo['monthly_rate'],2) ?>/month</div>
            <div class="text-muted small mt-1">Since <?= date('M d, Y', strtotime($roomInfo['check_in_date'])) ?></div>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class="bi bi-door-closed"></i>
            <p>No room assigned yet.</p>
            <?php if (!$latestApp): ?>
              <a href="apply.php" class="btn btn-th-primary mt-2 btn-sm">Apply for Dorm</a>
            <?php else: ?>
              <small class="text-muted">Waiting for admin assignment.</small>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Outstanding Bills -->
  <div class="col-md-6 col-lg-4">
    <div class="th-card h-100">
      <div class="card-header"><span><i class="bi bi-cash-coin me-2"></i>Outstanding Bills</span></div>
      <div class="card-body">
        <?php if (empty($bills)): ?>
          <div class="empty-state">
            <i class="bi bi-check-circle text-success" style="color:var(--th-green)!important"></i>
            <p class="text-success fw-600">All bills paid! 🎉</p>
          </div>
        <?php else: ?>
          <?php $totalBalance = array_sum(array_column($bills,'balance')); ?>
          <div class="text-center mb-3 p-3 rounded" style="background:var(--th-red-light)">
            <div style="font-size:1.5rem;font-weight:800;color:var(--th-red)">₱<?= number_format($totalBalance,2) ?></div>
            <div class="text-muted small">Total Outstanding Balance</div>
          </div>
          <?php foreach ($bills as $b): $overdue = strtotime($b['due_date']) < time(); ?>
          <div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded" style="background:#f9fafb;border:1px solid #f0f0f0">
            <div>
              <div class="fw-600 small"><?= date('F Y', strtotime($b['billing_month'])) ?></div>
              <div class="text-muted" style="font-size:.72rem">Due: <?= date('M d', strtotime($b['due_date'])) ?><?= $overdue?' <span class="text-danger">(Overdue)</span>':'' ?></div>
            </div>
            <div class="text-end">
              <?= statusBadge($b['status']) ?>
              <div class="fw-700 small text-danger mt-1">₱<?= number_format($b['balance'],2) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
          <a href="billing.php" class="btn btn-th-outline w-100 mt-2 btn-sm">View All Bills</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Notifications -->
  <div class="col-md-6">
    <div class="th-card">
      <div class="card-header">
        <span><i class="bi bi-bell me-2"></i>Recent Notifications</span>
        <a href="notifications.php" class="btn btn-sm btn-th-outline px-3">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($notifs)): ?>
          <div class="empty-state py-4"><i class="bi bi-bell-slash"></i><p>No new notifications.</p></div>
        <?php else: foreach ($notifs as $n):
          $nc = ['Success'=>'#e8f5ee','Error'=>'#fff0f2','Warning'=>'#fff8e1','Info'=>'#e8f0ff'][$n['type']] ?? '#f8f9fa';
          $ni = ['Success'=>'bi-check-circle','Error'=>'bi-x-circle','Warning'=>'bi-exclamation-triangle','Info'=>'bi-info-circle'][$n['type']] ?? 'bi-bell';
          $nclr = ['Success'=>'#1a7a4a','Error'=>'#dc3545','Warning'=>'#f0a500','Info'=>'#0d6efd'][$n['type']] ?? '#666';
        ?>
        <div class="d-flex gap-3 p-3 border-bottom">
          <div style="width:38px;height:38px;border-radius:10px;background:<?= $nc ?>;color:<?= $nclr ?>;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0">
            <i class="bi <?= $ni ?>"></i>
          </div>
          <div>
            <div class="fw-600 small"><?= htmlspecialchars($n['title']) ?></div>
            <div class="text-muted" style="font-size:.77rem"><?= htmlspecialchars(substr($n['message'],0,70)) ?><?= strlen($n['message'])>70?'...':'' ?></div>
            <div class="text-muted" style="font-size:.7rem"><?= date('M d, Y H:i', strtotime($n['created_at'])) ?></div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

  <!-- Announcements -->
  <div class="col-md-6">
    <div class="th-card">
      <div class="card-header">
        <span><i class="bi bi-megaphone me-2"></i>Announcements</span>
        <a href="announcements.php" class="btn btn-sm btn-th-outline px-3">View All</a>
      </div>
      <div class="card-body">
        <?php if (empty($anns)): ?>
          <div class="empty-state py-3"><i class="bi bi-megaphone"></i><p>No announcements.</p></div>
        <?php else: foreach ($anns as $a):
          $bc = ['Urgent'=>'#dc3545','Important'=>'#f0a500','Normal'=>'var(--th-green)'][$a['priority']] ?? 'var(--th-green)';
        ?>
        <div class="mb-3 p-3 rounded" style="border-left:4px solid <?= $bc ?>;background:#f9fafb">
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="fw-700 small"><?= htmlspecialchars($a['title']) ?></span>
            <?= statusBadge($a['priority']) ?>
          </div>
          <p class="text-muted small mb-1"><?= htmlspecialchars(substr($a['content'],0,100)) ?><?= strlen($a['content'])>100?'...':'' ?></p>
          <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('M d, Y', strtotime($a['publish_date'])) ?></small>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>
<?php renderFooter(); ?>
