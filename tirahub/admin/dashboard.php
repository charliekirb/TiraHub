<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireAdmin();
$pdo   = Database::connect();
$stats = $pdo->query("SELECT * FROM vw_dashboard_stats")->fetch();
$recentApps = $pdo->query("SELECT * FROM vw_applications_detail ORDER BY submitted_at DESC LIMIT 8")->fetchAll();
$rooms      = $pdo->query("SELECT * FROM vw_room_occupancy ORDER BY building_name, room_number")->fetchAll();
$recentPay  = $pdo->query("SELECT p.*, sf.full_name, sf.student_number FROM payments p INNER JOIN students s ON p.student_id=s.student_id INNER JOIN vw_students_full sf ON s.student_id=sf.student_id ORDER BY p.payment_date DESC LIMIT 6")->fetchAll();
$monthlyRev = $pdo->query("SELECT DATE_FORMAT(payment_date,'%b %Y') AS month, SUM(amount) AS total FROM payments WHERE payment_date >= DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(payment_date,'%Y-%m') ORDER BY MIN(payment_date) ASC")->fetchAll();
$appStats   = $pdo->query("SELECT status, COUNT(*) AS total FROM applications GROUP BY status")->fetchAll();
renderHead('Dashboard');
renderAdminNav('dashboard', $_SESSION['user_id']);
?>
<div class="page-header">
  <div class="page-header-left">
    <h4><i class="bi bi-speedometer2"></i>Dashboard</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item active">Overview</li></ol></nav>
  </div>
  <span class="badge bg-light text-muted border" style="font-size:.8rem;padding:.5rem .9rem">
    <i class="bi bi-calendar3 me-1 text-green"></i><?= date('F d, Y') ?>
  </span>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-people"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $stats['total_students'] ?>"><?= number_format($stats['total_students']) ?></div>
        <div class="stat-label">Total Students</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card yellow">
      <div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $stats['pending_applications'] ?>"><?= number_format($stats['pending_applications']) ?></div>
        <div class="stat-label">Pending Applications</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-door-open"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $stats['available_rooms'] ?>"><?= number_format($stats['available_rooms']) ?></div>
        <div class="stat-label">Available Rooms</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card blue">
      <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
      <div>
        <div class="stat-value" style="font-size:1.2rem">₱<?= number_format($stats['revenue_this_month'],2) ?></div>
        <div class="stat-label">Revenue This Month</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card purple">
      <div class="stat-icon"><i class="bi bi-house-check"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $stats['active_tenants'] ?>"><?= number_format($stats['active_tenants']) ?></div>
        <div class="stat-label">Active Tenants</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card red">
      <div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div>
      <div>
        <div class="stat-value" style="font-size:1.1rem">₱<?= number_format($stats['total_outstanding'],2) ?></div>
        <div class="stat-label">Outstanding Balance</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $stats['approved_applications'] ?>"><?= number_format($stats['approved_applications']) ?></div>
        <div class="stat-label">Approved Applications</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card red">
      <div class="stat-icon"><i class="bi bi-door-closed"></i></div>
      <div>
        <div class="stat-value" data-count="<?= $stats['full_rooms'] ?>"><?= number_format($stats['full_rooms']) ?></div>
        <div class="stat-label">Full Rooms</div>
      </div>
    </div>
  </div>
</div>

<!-- CHARTS ROW -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="th-card">
      <div class="card-header"><span><i class="bi bi-graph-up me-2"></i>Monthly Revenue (Last 6 Months)</span></div>
      <div class="card-body chart-container">
        <canvas id="revenueChart" height="90"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="th-card h-100">
      <div class="card-header"><span><i class="bi bi-pie-chart me-2"></i>Applications by Status</span></div>
      <div class="card-body chart-container">
        <canvas id="appChart" height="160"></canvas>
        <div class="chart-legend mt-3">
          <?php
          $clrs = ['Pending'=>'#f0a500','Under Review'=>'#0d6efd','Approved'=>'#1a7a4a','Rejected'=>'#dc3545','Cancelled'=>'#6c757d'];
          foreach ($appStats as $as):
            $c = $clrs[$as['status']] ?? '#999'; ?>
            <div class="chart-legend-item">
              <div class="chart-legend-dot" style="background:<?= $c ?>"></div>
              <span><?= $as['status'] ?> (<?= $as['total'] ?>)</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Recent Applications -->
  <div class="col-lg-8">
    <div class="th-card">
      <div class="card-header">
        <span><i class="bi bi-file-earmark-text me-2"></i>Recent Applications</span>
        <a href="applications.php" class="btn btn-sm btn-th-outline px-3">View All</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="th-table">
            <thead><tr><th>#</th><th>Student</th><th>Room Type</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
              <?php if (empty($recentApps)): ?>
                <tr><td colspan="6"><div class="empty-state"><i class="bi bi-inbox"></i><p>No applications yet.</p></div></td></tr>
              <?php else: foreach ($recentApps as $app): ?>
              <tr>
                <td><span class="badge bg-light text-muted border">#<?= $app['application_id'] ?></span></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="user-avatar" style="width:32px;height:32px;font-size:.8rem;border-radius:8px"><?= strtoupper(substr($app['student_name'],0,1)) ?></div>
                    <div>
                      <div class="fw-600"><?= htmlspecialchars($app['student_name']) ?></div>
                      <small class="text-muted"><?= htmlspecialchars($app['student_number']) ?></small>
                    </div>
                  </div>
                </td>
                <td><small><?= htmlspecialchars($app['preferred_room_type'] ?? '—') ?></small></td>
                <td><?= statusBadge($app['status']) ?></td>
                <td><small class="text-muted"><?= date('M d, Y', strtotime($app['submitted_at'])) ?></small></td>
                <td><a href="applications.php?view=<?= $app['application_id'] ?>" class="btn btn-sm btn-th-primary px-2 py-1"><i class="bi bi-eye"></i></a></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Room Occupancy -->
  <div class="col-lg-4">
    <div class="th-card h-100">
      <div class="card-header">
        <span><i class="bi bi-door-open me-2"></i>Room Occupancy</span>
        <a href="rooms.php" class="btn btn-sm btn-th-outline px-3">Manage</a>
      </div>
      <div class="card-body" style="max-height:380px;overflow-y:auto">
        <?php foreach ($rooms as $r):
          $pct   = (int)$r['occupancy_pct'];
          $col   = $pct>=100?'bar-danger':($pct>=75?'bar-warning':'');
          $scls  = $pct>=100?'danger':($pct>=75?'warning':'success');
        ?>
        <div class="mb-3 p-2 rounded" style="background:#f9fafb;border:1px solid #f0f0f0">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
              <span class="fw-600 small"><?= htmlspecialchars($r['building_name']) ?></span>
              <span class="text-muted small"> – <?= htmlspecialchars($r['room_number']) ?></span>
              <span class="badge bg-secondary ms-1" style="font-size:.65rem"><?= $r['room_type'] ?></span>
            </div>
            <span class="badge bg-<?= $scls ?>" style="font-size:.65rem"><?= $pct ?>%</span>
          </div>
          <div class="room-occupancy-bar">
            <div class="bar-fill <?= $col ?>" style="width:<?= min($pct,100) ?>%"></div>
          </div>
          <div class="d-flex justify-content-between mt-1">
            <small class="text-muted"><?= $r['current_occupants'] ?>/<?= $r['capacity'] ?> occupants</small>
            <small class="fw-600 text-green">₱<?= number_format($r['monthly_rate'],0) ?>/mo</small>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Recent Payments -->
  <div class="col-12">
    <div class="th-card">
      <div class="card-header">
        <span><i class="bi bi-receipt me-2"></i>Recent Payments</span>
        <a href="payments.php" class="btn btn-sm btn-th-outline px-3">View All</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="th-table">
            <thead><tr><th>#</th><th>Student</th><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th></tr></thead>
            <tbody>
              <?php if (empty($recentPay)): ?>
                <tr><td colspan="6"><div class="empty-state"><i class="bi bi-receipt"></i><p>No payments yet.</p></div></td></tr>
              <?php else: foreach ($recentPay as $p): ?>
              <tr>
                <td><span class="badge bg-light text-muted border">#<?= $p['payment_id'] ?></span></td>
                <td>
                  <div class="fw-600"><?= htmlspecialchars($p['full_name']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($p['student_number']) ?></small>
                </td>
                <td><span class="fw-700" style="color:var(--th-green)">₱<?= number_format($p['amount'],2) ?></span></td>
                <td>
                  <?php
                  $micons = ['Cash'=>'bi-cash','GCash'=>'bi-phone','Maya'=>'bi-phone','Bank Transfer'=>'bi-bank','Other'=>'bi-credit-card'];
                  $mic = $micons[$p['payment_method']] ?? 'bi-credit-card';
                  ?>
                  <span class="badge bg-light text-dark border"><i class="bi <?= $mic ?> me-1"></i><?= $p['payment_method'] ?></span>
                </td>
                <td><small><?= htmlspecialchars($p['reference_no'] ?? '—') ?></small></td>
                <td><small class="text-muted"><?= date('M d, Y H:i', strtotime($p['payment_date'])) ?></small></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php renderFooter(); ?>
<script>
window.addEventListener('load', function () {
  var revEl = document.getElementById('revenueChart');
  if (revEl && typeof Chart !== 'undefined') {
    new Chart(revEl, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode(array_column($monthlyRev,'month')); ?>,
        datasets: [{
          label: 'Revenue',
          data: <?php echo json_encode(array_column($monthlyRev,'total')); ?>,
          backgroundColor: 'rgba(26,122,74,0.75)',
          borderColor: '#145e38',
          borderWidth: 2,
          borderRadius: 8,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, grid: { color: '#f0f2f5' },
               ticks: { font:{size:11}, callback: function(v){ return '₱' + Number(v).toLocaleString(); } } },
          x: { grid: { display: false }, ticks: { font:{size:11} } }
        }
      }
    });
  }

  var appEl = document.getElementById('appChart');
  if (appEl && typeof Chart !== 'undefined') {
    new Chart(appEl, {
      type: 'doughnut',
      data: {
        labels: <?php echo json_encode(array_column($appStats,'status')); ?>,
        datasets: [{
          data: <?php echo json_encode(array_column($appStats,'total')); ?>,
          backgroundColor: ['#f0a500','#0d6efd','#1a7a4a','#dc3545','#6c757d'],
          borderWidth: 3, borderColor: '#fff', hoverOffset: 8
        }]
      },
      options: {
        cutout: '65%', responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false } }
      }
    });
  }
});
</script>