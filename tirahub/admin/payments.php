<?php
// ============================================================
//  TiraHub – Admin: Payments History
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireAdmin();

$pdo    = Database::connect();
$search = trim($_GET['search'] ?? '');
$method = $_GET['method'] ?? '';
$page   = max(1,(int)($_GET['page'] ?? 1));
$perPage= 20; $offset = ($page-1)*$perPage;

$where = []; $params = [];
if ($search) { $where[] = "(sf.full_name LIKE ? OR sf.student_number LIKE ? OR p.reference_no LIKE ?)"; $p="%$search%"; $params=[$p,$p,$p]; }
if ($method) { $where[] = "p.payment_method=?"; $params[] = $method; }
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

$stmtC = $pdo->prepare("SELECT COUNT(*) FROM payments p INNER JOIN vw_students_full sf ON p.student_id=sf.student_id $whereSQL");
$stmtC->execute($params); $total = (int)$stmtC->fetchColumn();

$stmtL = $pdo->prepare("SELECT p.*, sf.full_name, sf.student_number, u.username AS received_by_name
    FROM payments p
    INNER JOIN vw_students_full sf ON p.student_id=sf.student_id
    LEFT JOIN users u ON p.received_by=u.user_id
    $whereSQL ORDER BY p.payment_date DESC LIMIT $perPage OFFSET $offset");
$stmtL->execute($params); $payments = $stmtL->fetchAll();

$totalRev = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments")->fetchColumn();
$monthRev = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetchColumn();

renderHead('Payments');
renderAdminNav('payments', $_SESSION['user_id']);
?>
<div class="page-header">
  <div>
    <h4><i class="bi bi-receipt me-2"></i>Payment Records</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Payments</li></ol></nav>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4 col-6">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
      <div><div class="stat-value" style="font-size:1.2rem">₱<?= number_format($totalRev,2) ?></div><div class="stat-label">Total Revenue</div></div>
    </div>
  </div>
  <div class="col-md-4 col-6">
    <div class="stat-card" style="border-left-color:#0d6efd">
      <div class="stat-icon" style="background:#e8f0ff;color:#0d6efd"><i class="bi bi-calendar-month"></i></div>
      <div><div class="stat-value" style="color:#0d4db0;font-size:1.2rem">₱<?= number_format($monthRev,2) ?></div><div class="stat-label">This Month</div></div>
    </div>
  </div>
  <div class="col-md-4 col-6">
    <div class="stat-card" style="border-left-color:#6f42c1">
      <div class="stat-icon" style="background:#f3eeff;color:#6f42c1"><i class="bi bi-hash"></i></div>
      <div><div class="stat-value" style="color:#4b2d8a"><?= number_format($total) ?></div><div class="stat-label">Total Transactions</div></div>
    </div>
  </div>
</div>

<div class="th-card mb-3"><div class="card-body py-2">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-md-4"><input type="text" name="search" class="form-control form-control-sm" placeholder="Student name, number, reference…" value="<?= htmlspecialchars($search) ?>"></div>
    <div class="col-md-2">
      <select name="method" class="form-select form-select-sm">
        <option value="">All Methods</option>
        <?php foreach(['Cash','Bank Transfer','GCash','Maya','Other'] as $m): ?>
          <option value="<?= $m ?>" <?= $method===$m?'selected':'' ?>><?= $m ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-th-primary px-3">Filter</button><a href="payments.php" class="btn btn-sm btn-outline-secondary px-3 ms-1">Reset</a></div>
  </form>
</div></div>

<div class="th-card"><div class="card-body p-0"><div class="table-responsive">
  <table class="th-table">
    <thead><tr><th>#</th><th>Student</th><th>Bill Month</th><th>Amount</th><th>Method</th><th>Reference</th><th>Received By</th><th>Date</th></tr></thead>
    <tbody>
      <?php if (empty($payments)): ?>
        <tr><td colspan="8" class="text-center py-5 text-muted">No payments found.</td></tr>
      <?php else: ?>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td><?= $p['payment_id'] ?></td>
          <td><div class="fw-600"><?= htmlspecialchars($p['full_name']) ?></div><small class="text-muted"><?= htmlspecialchars($p['student_number']) ?></small></td>
          <td><small><?= $p['bill_id'] ?></small></td>
          <td><span class="text-success fw-700">₱<?= number_format($p['amount'],2) ?></span></td>
          <td><?= htmlspecialchars($p['payment_method']) ?></td>
          <td><small><?= htmlspecialchars($p['reference_no'] ?? '—') ?></small></td>
          <td><small><?= htmlspecialchars($p['received_by_name'] ?? '—') ?></small></td>
          <td><small><?= date('M d, Y H:i', strtotime($p['payment_date'])) ?></small></td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php if ($total > $perPage): ?>
  <div class="d-flex justify-content-end p-3"><?= paginate($total,$page,$perPage,'?search='.urlencode($search).'&method='.urlencode($method)) ?></div>
<?php endif; ?>
</div></div>

<?php renderFooter(); ?>
