<?php
// ============================================================
//  TiraHub – Admin: Reports
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireAdmin();

$pdo = Database::connect();

// Occupancy report
$occupancy = $pdo->query("SELECT * FROM vw_room_occupancy ORDER BY building_name, room_number")->fetchAll();

// Billing report by month
$billingByMonth = $pdo->query(
    "SELECT DATE_FORMAT(billing_month,'%Y-%m') AS month_label,
            COUNT(*) AS bill_count,
            SUM(amount_due) AS total_due,
            SUM(amount_paid) AS total_paid,
            SUM(amount_due-amount_paid) AS total_balance,
            SUM(CASE WHEN status='Paid' THEN 1 ELSE 0 END) AS paid_count,
            SUM(CASE WHEN status='Unpaid' THEN 1 ELSE 0 END) AS unpaid_count,
            SUM(CASE WHEN status='Overdue' THEN 1 ELSE 0 END) AS overdue_count
     FROM billing
     GROUP BY DATE_FORMAT(billing_month,'%Y-%m')
     ORDER BY billing_month DESC
     LIMIT 12"
)->fetchAll();

// Application stats
$appStats = $pdo->query(
    "SELECT status, COUNT(*) AS cnt FROM applications GROUP BY status ORDER BY cnt DESC"
)->fetchAll();

// Revenue by payment method
$revByMethod = $pdo->query(
    "SELECT payment_method, COUNT(*) AS txn_count, SUM(amount) AS total
     FROM payments GROUP BY payment_method ORDER BY total DESC"
)->fetchAll();

// Top students with outstanding balance
$topBalance = $pdo->query(
    "SELECT * FROM vw_billing_summary WHERE total_balance > 0 ORDER BY total_balance DESC LIMIT 10"
)->fetchAll();

// Tenants per building
$tenantsPerBuilding = $pdo->query(
    "SELECT b.building_name,
            COUNT(ra.assignment_id) AS active_tenants,
            SUM(r.capacity) AS total_capacity
     FROM buildings b
     LEFT JOIN rooms r ON b.building_id=r.building_id
     LEFT JOIN room_assignments ra ON r.room_id=ra.room_id AND ra.status='Active'
     GROUP BY b.building_id, b.building_name"
)->fetchAll();

// Summary totals
$summary = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM students) AS total_students,
        (SELECT COUNT(*) FROM room_assignments WHERE status='Active') AS active_tenants,
        (SELECT COUNT(*) FROM applications WHERE status='Pending') AS pending_apps,
        (SELECT COALESCE(SUM(amount),0) FROM payments WHERE YEAR(payment_date)=YEAR(CURDATE())) AS revenue_ytd,
        (SELECT COALESCE(SUM(amount_due-amount_paid),0) FROM billing WHERE status IN ('Unpaid','Partial','Overdue')) AS outstanding"
)->fetch();

renderHead('Reports');
renderAdminNav('reports', $_SESSION['user_id']);
?>

<div class="page-header">
  <div>
    <h4><i class="bi bi-bar-chart-line me-2"></i>Reports & Analytics</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Reports</li>
      </ol>
    </nav>
  </div>
  <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i>Generated: <?= date('F d, Y H:i') ?></span>
</div>

<!-- Summary KPIs -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-people"></i></div>
      <div><div class="stat-value"><?= number_format($summary['total_students']) ?></div><div class="stat-label">Total Students</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card" style="border-left-color:#6f42c1">
      <div class="stat-icon" style="background:#f3eeff;color:#6f42c1"><i class="bi bi-house-check"></i></div>
      <div><div class="stat-value" style="color:#4b2d8a"><?= number_format($summary['active_tenants']) ?></div><div class="stat-label">Active Tenants</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card" style="border-left-color:#0d6efd">
      <div class="stat-icon" style="background:#e8f0ff;color:#0d6efd"><i class="bi bi-cash-stack"></i></div>
      <div><div class="stat-value" style="color:#0d4db0;font-size:1.15rem">₱<?= number_format($summary['revenue_ytd'],0) ?></div><div class="stat-label">Revenue YTD</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card" style="border-left-color:#dc3545">
      <div class="stat-icon" style="background:#fff0f0;color:#dc3545"><i class="bi bi-exclamation-circle"></i></div>
      <div><div class="stat-value" style="color:#a71d2a;font-size:1.15rem">₱<?= number_format($summary['outstanding'],0) ?></div><div class="stat-label">Outstanding</div></div>
    </div>
  </div>
</div>

<div class="row g-4">

  <!-- Billing by Month -->
  <div class="col-lg-8">
    <div class="th-card">
      <div class="card-header"><i class="bi bi-calendar-month me-2"></i>Billing Summary by Month</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="th-table">
            <thead>
              <tr><th>Month</th><th>Bills</th><th>Total Due</th><th>Collected</th><th>Balance</th><th>Paid</th><th>Unpaid</th><th>Overdue</th></tr>
            </thead>
            <tbody>
              <?php if (empty($billingByMonth)): ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">No billing data yet.</td></tr>
              <?php else: ?>
                <?php foreach ($billingByMonth as $bm): ?>
                <tr>
                  <td><strong><?= $bm['month_label'] ?></strong></td>
                  <td><?= $bm['bill_count'] ?></td>
                  <td>₱<?= number_format($bm['total_due'],2) ?></td>
                  <td class="text-success fw-600">₱<?= number_format($bm['total_paid'],2) ?></td>
                  <td class="<?= $bm['total_balance']>0?'text-danger fw-600':'' ?>">₱<?= number_format($bm['total_balance'],2) ?></td>
                  <td><span class="badge bg-success"><?= $bm['paid_count'] ?></span></td>
                  <td><span class="badge bg-danger"><?= $bm['unpaid_count'] ?></span></td>
                  <td><span class="badge bg-dark"><?= $bm['overdue_count'] ?></span></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Application Status Breakdown -->
  <div class="col-lg-4">
    <div class="th-card mb-4">
      <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Application Status</div>
      <div class="card-body">
        <?php
        $appTotal = array_sum(array_column($appStats,'cnt'));
        foreach ($appStats as $as):
          $pct = $appTotal > 0 ? round($as['cnt']/$appTotal*100) : 0;
          $color = match($as['status']) {
            'Approved'     => '#1a7a4a',
            'Rejected'     => '#dc3545',
            'Pending'      => '#f0a500',
            'Under Review' => '#0d6efd',
            default        => '#6c757d'
          };
        ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span class="small fw-600"><?= $as['status'] ?></span>
            <span class="small text-muted"><?= $as['cnt'] ?> (<?= $pct ?>%)</span>
          </div>
          <div class="progress" style="height:8px;border-radius:99px">
            <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $color ?>;border-radius:99px"></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($appStats)): ?><p class="text-muted small text-center">No data.</p><?php endif; ?>
      </div>
    </div>

    <!-- Revenue by Method -->
    <div class="th-card">
      <div class="card-header"><i class="bi bi-credit-card me-2"></i>Revenue by Payment Method</div>
      <div class="card-body p-0">
        <table class="th-table">
          <thead><tr><th>Method</th><th>Txns</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach ($revByMethod as $rm): ?>
            <tr>
              <td><strong><?= $rm['payment_method'] ?></strong></td>
              <td><?= $rm['txn_count'] ?></td>
              <td class="text-success fw-600">₱<?= number_format($rm['total'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($revByMethod)): ?><tr><td colspan="3" class="text-center text-muted py-3">No payments yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Room Occupancy -->
  <div class="col-lg-6">
    <div class="th-card">
      <div class="card-header"><i class="bi bi-building me-2"></i>Occupancy by Building</div>
      <div class="card-body">
        <?php foreach ($tenantsPerBuilding as $tb):
          $pct = $tb['total_capacity'] > 0 ? round($tb['active_tenants']/$tb['total_capacity']*100) : 0;
          $color = $pct>=90?'#dc3545':($pct>=70?'#f0a500':'#1a7a4a');
        ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span class="fw-600 small"><?= htmlspecialchars($tb['building_name']) ?></span>
            <span class="small text-muted"><?= $tb['active_tenants'] ?>/<?= $tb['total_capacity'] ?> (<?= $pct ?>%)</span>
          </div>
          <div class="progress" style="height:10px;border-radius:99px">
            <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $color ?>;border-radius:99px"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Top Outstanding Balances -->
  <div class="col-lg-6">
    <div class="th-card">
      <div class="card-header"><i class="bi bi-exclamation-triangle me-2"></i>Top Outstanding Balances</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="th-table">
            <thead><tr><th>Student</th><th>Bills</th><th>Balance</th><th>Overdue</th></tr></thead>
            <tbody>
              <?php if (empty($topBalance)): ?>
                <tr><td colspan="4" class="text-center py-4 text-success"><i class="bi bi-check-circle me-2"></i>All balances cleared!</td></tr>
              <?php else: ?>
                <?php foreach ($topBalance as $tb): ?>
                <tr>
                  <td>
                    <div class="fw-600"><?= htmlspecialchars($tb['student_name']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($tb['student_number']) ?></small>
                  </td>
                  <td><?= $tb['total_bills'] ?></td>
                  <td class="text-danger fw-700">₱<?= number_format($tb['total_balance'],2) ?></td>
                  <td><span class="badge bg-dark"><?= $tb['overdue_count'] ?></span></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Full Room Occupancy Detail -->
  <div class="col-12">
    <div class="th-card">
      <div class="card-header"><i class="bi bi-door-open me-2"></i>Room-by-Room Occupancy</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="th-table">
            <thead>
              <tr><th>Building</th><th>Room</th><th>Floor</th><th>Type</th><th>Capacity</th><th>Occupants</th><th>Available</th><th>Rate/mo</th><th>Occupancy %</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php foreach ($occupancy as $oc):
                $pct = (int)$oc['occupancy_pct'];
                $barColor = $pct>=100?'#dc3545':($pct>=75?'#f0a500':'#1a7a4a');
              ?>
              <tr>
                <td><?= htmlspecialchars($oc['building_name']) ?></td>
                <td><strong><?= htmlspecialchars($oc['room_number']) ?></strong></td>
                <td><?= $oc['floor'] ?></td>
                <td><span class="badge bg-secondary"><?= $oc['room_type'] ?></span></td>
                <td><?= $oc['capacity'] ?></td>
                <td><?= $oc['current_occupants'] ?></td>
                <td><?= $oc['available_slots'] ?></td>
                <td>₱<?= number_format($oc['monthly_rate'],2) ?></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="room-occupancy-bar" style="width:70px">
                      <div class="bar-fill" style="width:<?= min($pct,100) ?>%;background:<?= $barColor ?>"></div>
                    </div>
                    <small><?= $pct ?>%</small>
                  </div>
                </td>
                <td><?= statusBadge($oc['status']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>

<?php renderFooter(); ?>
