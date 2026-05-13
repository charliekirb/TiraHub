<?php
// ============================================================
//  TiraHub – Student: My Billing
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireStudent();

$pdo       = Database::connect();
$studentId = (int)$_SESSION['student_id'];

$bills = $pdo->prepare(
    "SELECT b.*, r.room_number, bld.building_name
     FROM billing b
     INNER JOIN rooms r ON b.room_id=r.room_id
     INNER JOIN buildings bld ON r.building_id=bld.building_id
     WHERE b.student_id=?
     ORDER BY b.billing_month DESC"
);
$bills->execute([$studentId]); $bills = $bills->fetchAll();

// Payments per bill
$payments = $pdo->prepare(
    "SELECT p.*, u.username AS received_by_name
     FROM payments p
     LEFT JOIN users u ON p.received_by=u.user_id
     WHERE p.student_id=?
     ORDER BY p.payment_date DESC"
);
$payments->execute([$studentId]); $payments = $payments->fetchAll();

// Summary
$summary = $pdo->prepare(
    "SELECT SUM(amount_due) AS total_due, SUM(amount_paid) AS total_paid,
            SUM(amount_due-amount_paid) AS balance,
            SUM(CASE WHEN status='Overdue' THEN 1 ELSE 0 END) AS overdue_count
     FROM billing WHERE student_id=?"
);
$summary->execute([$studentId]); $summary = $summary->fetch();

renderHead('My Billing');
renderStudentNav('billing', $_SESSION['user_id']);
?>
<div class="page-header">
  <div>
    <h4><i class="bi bi-cash-coin me-2"></i>My Billing</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Billing</li></ol></nav>
  </div>
</div>

<!-- Summary -->
<?php if ($summary['total_due'] > 0): ?>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-receipt"></i></div>
      <div><div class="stat-value" style="font-size:1.1rem">₱<?= number_format($summary['total_due'],2) ?></div><div class="stat-label">Total Billed</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
      <div><div class="stat-value" style="font-size:1.1rem">₱<?= number_format($summary['total_paid'],2) ?></div><div class="stat-label">Total Paid</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card" style="border-left-color:<?= $summary['balance']>0?'#dc3545':'var(--th-green)' ?>">
      <div class="stat-icon" style="background:<?= $summary['balance']>0?'#fff0f0':'var(--th-green-light)' ?>;color:<?= $summary['balance']>0?'#dc3545':'var(--th-green)' ?>"><i class="bi bi-wallet2"></i></div>
      <div><div class="stat-value" style="font-size:1.1rem;color:<?= $summary['balance']>0?'#a71d2a':'var(--th-green-dark)' ?>">₱<?= number_format($summary['balance'],2) ?></div><div class="stat-label">Balance</div></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card" style="border-left-color:<?= $summary['overdue_count']>0?'#212529':'#6c757d' ?>">
      <div class="stat-icon" style="background:#f8f9fa;color:#212529"><i class="bi bi-exclamation-circle"></i></div>
      <div><div class="stat-value" style="font-size:1.1rem"><?= $summary['overdue_count'] ?></div><div class="stat-label">Overdue Bills</div></div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Bills -->
  <div class="col-lg-7">
    <div class="th-card">
      <div class="card-header"><i class="bi bi-file-earmark-text me-2"></i>Billing Records</div>
      <div class="card-body p-0">
        <?php if (empty($bills)): ?>
          <div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No billing records yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="th-table">
              <thead><tr><th>#</th><th>Room</th><th>Month</th><th>Due</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Status</th></tr></thead>
              <tbody>
                <?php foreach ($bills as $b):
                  $balance = $b['amount_due'] - $b['amount_paid'];
                ?>
                <tr>
                  <td><?= $b['bill_id'] ?></td>
                  <td><small><?= htmlspecialchars($b['building_name']) ?> - <?= htmlspecialchars($b['room_number']) ?></small></td>
                  <td><small><?= date('M Y', strtotime($b['billing_month'])) ?></small></td>
                  <td>₱<?= number_format($b['amount_due'],2) ?></td>
                  <td class="text-success">₱<?= number_format($b['amount_paid'],2) ?></td>
                  <td class="<?= $balance>0?'text-danger fw-700':'' ?>">₱<?= number_format($balance,2) ?></td>
                  <td><small class="<?= strtotime($b['due_date'])<time()&&$b['status']!=='Paid'?'text-danger fw-600':'' ?>"><?= date('M d, Y', strtotime($b['due_date'])) ?></small></td>
                  <td><?= statusBadge($b['status']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Payment History -->
  <div class="col-lg-5">
    <div class="th-card">
      <div class="card-header"><i class="bi bi-receipt me-2"></i>Payment History</div>
      <div class="card-body p-0">
        <?php if (empty($payments)): ?>
          <div class="text-center py-4 text-muted small">No payments recorded.</div>
        <?php else: ?>
          <?php foreach ($payments as $p): ?>
          <div class="d-flex align-items-center gap-3 p-3 border-bottom">
            <div style="width:40px;height:40px;background:var(--th-green-light);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--th-green);flex-shrink:0">
              <i class="bi bi-cash-coin"></i>
            </div>
            <div class="flex-grow-1">
              <div class="fw-600 small">₱<?= number_format($p['amount'],2) ?> – <?= htmlspecialchars($p['payment_method']) ?></div>
              <div class="text-muted" style="font-size:.78rem">
                Bill #<?= $p['bill_id'] ?>
                <?= $p['reference_no'] ? ' · Ref: ' . htmlspecialchars($p['reference_no']) : '' ?>
              </div>
              <div class="text-muted" style="font-size:.75rem"><?= date('M d, Y H:i', strtotime($p['payment_date'])) ?></div>
            </div>
            <span class="badge bg-success">Paid</span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php renderFooter(); ?>
