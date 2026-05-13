<?php
// ============================================================
//  TiraHub – Admin: Billing Management
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireAdmin();

$pdo     = Database::connect();
$message = '';
$msgType = 'success';

// Generate monthly billing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_billing'])) {
    $month = $_POST['billing_month'] ?? date('Y-m-01');
    $stmt  = $pdo->prepare("CALL sp_generate_monthly_billing(?,@gen,@msg)");
    $stmt->execute([$month]);
    $res     = $pdo->query("SELECT @gen AS generated, @msg AS message")->fetch();
    $message = $res['message'];
    $msgType = 'success';
}

// Record payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $billId  = (int)$_POST['bill_id'];
    $studId  = (int)$_POST['student_id'];
    $amount  = (float)$_POST['amount'];
    $method  = $_POST['payment_method'];
    $ref     = trim($_POST['reference_no'] ?? '');
    $stmt    = $pdo->prepare("CALL sp_record_payment(?,?,?,?,?,?,@pid,@msg)");
    $stmt->execute([$billId,$studId,$amount,$method,$ref,$_SESSION['user_id']]);
    $res     = $pdo->query("SELECT @pid AS payment_id, @msg AS message")->fetch();
    $message = $res['message'];
    $msgType = (int)$res['payment_id'] > 0 ? 'success' : 'danger';
}

// Mark overdue (manual trigger helper)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_overdue'])) {
    $pdo->exec("UPDATE billing SET status='Overdue' WHERE due_date < CURDATE() AND status IN ('Unpaid','Partial')");
    $message = 'Overdue bills updated.';
}

$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['search'] ?? '');
$page         = max(1,(int)($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page-1)*$perPage;

$where = []; $params = [];
if ($statusFilter) { $where[] = 'b.status=?'; $params[] = $statusFilter; }
if ($search)       { $where[] = "(sf.full_name LIKE ? OR sf.student_number LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

$stmtC = $pdo->prepare("SELECT COUNT(*) FROM billing b INNER JOIN vw_students_full sf ON b.student_id=sf.student_id $whereSQL");
$stmtC->execute($params); $total = (int)$stmtC->fetchColumn();

$stmtL = $pdo->prepare(
    "SELECT b.*, sf.full_name, sf.student_number, r.room_number, bld.building_name
     FROM billing b
     INNER JOIN vw_students_full sf ON b.student_id=sf.student_id
     INNER JOIN rooms r ON b.room_id=r.room_id
     INNER JOIN buildings bld ON r.building_id=bld.building_id
     $whereSQL
     ORDER BY b.due_date ASC, sf.last_name
     LIMIT $perPage OFFSET $offset"
);
$stmtL->execute($params); $bills = $stmtL->fetchAll();

// Summary stats
$stats = $pdo->query("SELECT
    SUM(CASE WHEN status='Unpaid'  THEN amount_due-amount_paid ELSE 0 END) AS unpaid_total,
    SUM(CASE WHEN status='Overdue' THEN amount_due-amount_paid ELSE 0 END) AS overdue_total,
    SUM(CASE WHEN status='Paid'    THEN amount_due ELSE 0 END) AS paid_total,
    COUNT(CASE WHEN status='Unpaid'  THEN 1 END) AS unpaid_count,
    COUNT(CASE WHEN status='Overdue' THEN 1 END) AS overdue_count
FROM billing")->fetch();

renderHead('Billing');
renderAdminNav('billing', $_SESSION['user_id']);
?>

<div class="page-header">
  <div>
    <h4><i class="bi bi-cash-coin me-2"></i>Billing Management</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Billing</li>
      </ol>
    </nav>
  </div>
  <div class="d-flex gap-2">
    <form method="POST" class="d-flex gap-2 align-items-end">
      <input type="month" name="billing_month" class="form-control form-control-sm" value="<?= date('Y-m') ?>" required>
      <button type="submit" name="generate_billing" value="1" class="btn btn-sm btn-th-primary text-nowrap">
        <i class="bi bi-lightning me-1"></i>Generate Monthly Bills
      </button>
    </form>
    <form method="POST">
      <button type="submit" name="mark_overdue" value="1" class="btn btn-sm btn-danger">
        <i class="bi bi-exclamation-triangle me-1"></i>Mark Overdue
      </button>
    </form>
  </div>
</div>

<?php if ($message): ?>
  <div class="alert alert-<?= $msgType==='success'?'success':'danger' ?> alert-auto-dismiss alert-dismissible">
    <i class="bi bi-<?= $msgType==='success'?'check-circle':'x-circle' ?> me-2"></i><?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-md-3 col-6">
    <div class="stat-card" style="border-left-color:#dc3545">
      <div class="stat-icon" style="background:#fff0f0;color:#dc3545"><i class="bi bi-clock-history"></i></div>
      <div>
        <div class="stat-value" style="color:#a71d2a"><?= $stats['unpaid_count'] ?></div>
        <div class="stat-label">Unpaid Bills</div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card" style="border-left-color:#212529">
      <div class="stat-icon" style="background:#f1f1f1;color:#212529"><i class="bi bi-exclamation-circle"></i></div>
      <div>
        <div class="stat-value" style="color:#212529"><?= $stats['overdue_count'] ?></div>
        <div class="stat-label">Overdue Bills</div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card" style="border-left-color:#dc3545">
      <div class="stat-icon" style="background:#fff0f0;color:#dc3545"><i class="bi bi-currency-exchange"></i></div>
      <div>
        <div class="stat-value" style="color:#a71d2a;font-size:1.2rem">₱<?= number_format($stats['unpaid_total']+$stats['overdue_total'],2) ?></div>
        <div class="stat-label">Total Outstanding</div>
      </div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
      <div>
        <div class="stat-value" style="font-size:1.2rem">₱<?= number_format($stats['paid_total'],2) ?></div>
        <div class="stat-label">Total Collected</div>
      </div>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="th-card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-4">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search student name or number…" value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Statuses</option>
          <?php foreach(['Unpaid','Partial','Paid','Overdue'] as $s): ?>
            <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-sm btn-th-primary px-3">Filter</button>
        <a href="billing.php" class="btn btn-sm btn-outline-secondary px-3 ms-1">Reset</a>
      </div>
      <div class="col-auto ms-auto"><span class="text-muted small"><?= $total ?> bill(s)</span></div>
    </form>
  </div>
</div>

<!-- Billing Table -->
<div class="th-card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="th-table">
        <thead>
          <tr><th>#</th><th>Student</th><th>Room</th><th>Month</th><th>Amount Due</th><th>Paid</th><th>Balance</th><th>Due Date</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php if (empty($bills)): ?>
            <tr><td colspan="10" class="text-center py-5 text-muted">No billing records found.</td></tr>
          <?php else: ?>
            <?php foreach ($bills as $b):
              $balance = $b['amount_due'] - $b['amount_paid'];
            ?>
            <tr>
              <td><?= $b['bill_id'] ?></td>
              <td>
                <div class="fw-600"><?= htmlspecialchars($b['full_name']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($b['student_number']) ?></small>
              </td>
              <td><small><?= htmlspecialchars($b['building_name']) ?> - <?= htmlspecialchars($b['room_number']) ?></small></td>
              <td><small><?= date('M Y', strtotime($b['billing_month'])) ?></small></td>
              <td>₱<?= number_format($b['amount_due'],2) ?></td>
              <td class="text-success">₱<?= number_format($b['amount_paid'],2) ?></td>
              <td class="<?= $balance>0?'text-danger fw-700':'' ?>">₱<?= number_format($balance,2) ?></td>
              <td>
                <small class="<?= strtotime($b['due_date'])<time()&&$b['status']!=='Paid'?'text-danger fw-600':'' ?>">
                  <?= date('M d, Y', strtotime($b['due_date'])) ?>
                </small>
              </td>
              <td><?= statusBadge($b['status']) ?></td>
              <td>
                <?php if ($b['status'] !== 'Paid'): ?>
                <button class="btn btn-sm btn-th-primary px-2 py-1"
                  onclick="openPayModal(<?= $b['bill_id'] ?>,<?= $b['student_id'] ?>,'<?= htmlspecialchars($b['full_name']) ?>',<?= $balance ?>)">
                  <i class="bi bi-cash me-1"></i>Pay
                </button>
                <?php else: ?>
                  <span class="text-muted small">—</span>
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
      <?= paginate($total, $page, $perPage, '?status='.urlencode($statusFilter).'&search='.urlencode($search)) ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content border-0">
      <div class="modal-header" style="background:var(--th-green);color:#fff">
        <h5 class="modal-title"><i class="bi bi-cash me-2"></i>Record Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="bill_id" id="payBillId">
        <input type="hidden" name="student_id" id="payStudentId">
        <div class="modal-body">
          <p class="mb-3">Student: <strong id="payStudentName"></strong></p>
          <div class="mb-3">
            <label class="form-label fw-600">Amount <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">₱</span>
              <input type="number" name="amount" id="payAmount" class="form-control" min="1" step="0.01" required>
            </div>
            <small class="text-muted">Balance: ₱<span id="payBalance"></span></small>
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">Payment Method <span class="text-danger">*</span></label>
            <select name="payment_method" class="form-select" required>
              <?php foreach(['Cash','Bank Transfer','GCash','Maya','Other'] as $pm): ?>
                <option value="<?= $pm ?>"><?= $pm ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">Reference No.</label>
            <input type="text" name="reference_no" class="form-control" placeholder="Optional">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="record_payment" value="1" class="btn btn-th-primary">
            <i class="bi bi-check-circle me-1"></i>Confirm Payment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openPayModal(billId, studentId, name, balance) {
  document.getElementById('payBillId').value    = billId;
  document.getElementById('payStudentId').value = studentId;
  document.getElementById('payStudentName').textContent = name;
  document.getElementById('payBalance').textContent     = parseFloat(balance).toFixed(2);
  document.getElementById('payAmount').value    = parseFloat(balance).toFixed(2);
  document.getElementById('payAmount').max      = parseFloat(balance).toFixed(2);
  new bootstrap.Modal(document.getElementById('payModal')).show();
}
</script>

<?php renderFooter(); ?>
