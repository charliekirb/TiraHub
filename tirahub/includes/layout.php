<?php
// ============================================================
//  TiraHub – Shared Layout Helpers v2
// ============================================================

function renderHead(string $title, string $extra = ''): void {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TiraHub – {$title}</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
{$extra}
</head>
<body>
HTML;
}

function renderAdminNav(string $active, int $userId): void {
    require_once __DIR__ . '/../config/database.php';
    $pdo   = Database::connect();
    $notif = unreadNotifCount($pdo, $userId);
    $uname = $_SESSION['username'] ?? 'Admin';
    $initial = strtoupper(substr($uname, 0, 1));

    $links = [
        'dashboard'     => ['Dashboard',      'bi-speedometer2',     'admin/dashboard.php'],
        'applications'  => ['Applications',   'bi-file-earmark-text','admin/applications.php'],
        'rooms'         => ['Rooms',           'bi-door-open',        'admin/rooms.php'],
        'students'      => ['Students',        'bi-people',           'admin/students.php'],
        'billing'       => ['Billing',         'bi-cash-coin',        'admin/billing.php'],
        'payments'      => ['Payments',        'bi-receipt',          'admin/payments.php'],
        'announcements' => ['Announcements',   'bi-megaphone',        'admin/announcements.php'],
        'reports'       => ['Reports',         'bi-bar-chart-line',   'admin/reports.php'],
    ];

    echo '<nav class="sidebar">';
    echo '<div class="sidebar-brand">';
    echo '<div class="sidebar-brand-icon"><i class="bi bi-building"></i></div>';
    echo '<div class="sidebar-brand-text"><div class="sidebar-brand-title">TiraHub</div><div class="sidebar-brand-sub">Admin Portal</div></div>';
    echo '</div>';
    echo '<ul class="sidebar-nav">';
    echo '<li class="sidebar-nav-label">Main Menu</li>';
    foreach ($links as $key => [$label, $icon, $href]) {
        $cls   = ($active === $key) ? 'active' : '';
        $badge = ($key === 'applications' && $notif > 0) ? "<span class='nav-badge'>{$notif}</span>" : '';
        echo "<li><a href='../{$href}' class='{$cls}'><i class='bi {$icon}'></i><span>{$label}</span>{$badge}</a></li>";
    }
    echo '</ul>';
    echo '<div class="sidebar-footer">';
    echo "<a href='../logout.php'><i class='bi bi-box-arrow-left'></i><span>Logout</span></a>";
    echo '</div></nav>';

    $badge2 = $notif > 0 ? "<span class='badge bg-danger notif-live-count'>{$notif}</span>" : "<span class='badge bg-danger notif-live-count' style='display:none'></span>";
    echo <<<HTML
<div class="main-wrapper">
<header class="topbar">
  <div class="topbar-left">
    <button class="sidebar-toggle d-lg-none" onclick="toggleSidebar()">
      <i class="bi bi-list fs-5"></i>
    </button>
    <div class="topbar-search">
      <i class="bi bi-search"></i>
      <input type="text" placeholder="Search anything...">
    </div>
  </div>
  <div class="topbar-right">
    <div class="dropdown">
      <a class="notif-btn" data-bs-toggle="dropdown" title="Notifications">
        <i class="bi bi-bell"></i>{$badge2}
      </a>
      <div class="dropdown-menu notif-dropdown dropdown-menu-end p-0">
        <div class="notif-dropdown-header">
          <span><i class="bi bi-bell me-2"></i>Notifications</span>
          <a href="../admin/notifications.php" class="small text-green">View all</a>
        </div>
        <div id="notifList">
          <div class="text-center text-muted py-3 small">Loading...</div>
        </div>
      </div>
    </div>
    <div class="dropdown">
      <a class="d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown" style="cursor:pointer">
        <div class="user-avatar">{$initial}</div>
        <div class="d-none d-md-block">
          <div class="fw-700 small">{$uname}</div>
          <div style="font-size:.7rem;color:#888">Administrator</div>
        </div>
        <i class="bi bi-chevron-down small text-muted d-none d-md-block"></i>
      </a>
      <ul class="dropdown-menu dropdown-menu-end" style="border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:none;min-width:180px">
        <li><div class="px-3 py-2 border-bottom"><div class="fw-700 small">{$uname}</div><div class="text-muted" style="font-size:.75rem">Administrator</div></div></li>
        <li><a class="dropdown-item py-2" href="../admin/profile.php"><i class="bi bi-person me-2 text-green"></i>My Profile</a></li>
        <li><hr class="dropdown-divider my-1"></li>
        <li><a class="dropdown-item py-2 text-danger" href="../logout.php"><i class="bi bi-box-arrow-left me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</header>
<main class="main-content">
HTML;

    // Mobile bottom nav
    $mobileLinks = [
        ['Dashboard',     'bi-speedometer2',      'admin/dashboard.php',     'dashboard'],
        ['Applications',  'bi-file-earmark-text', 'admin/applications.php',  'applications'],
        ['Rooms',         'bi-door-open',         'admin/rooms.php',          'rooms'],
        ['Billing',       'bi-cash-coin',         'admin/billing.php',        'billing'],
        ['More',          'bi-grid',              'admin/reports.php',        'reports'],
    ];
    echo '<nav class="mobile-bottom-nav"><ul>';
    foreach ($mobileLinks as [$lbl,$ico,$href,$key]) {
        $cls = $active === $key ? 'active' : '';
        echo "<li><a href='../{$href}' class='{$cls}'><i class='bi {$ico}'></i><span>{$lbl}</span></a></li>";
    }
    echo '</ul></nav>';
}

function renderStudentNav(string $active, int $userId): void {
    require_once __DIR__ . '/../config/database.php';
    $pdo   = Database::connect();
    $notif = unreadNotifCount($pdo, $userId);
    $uname = $_SESSION['username'] ?? 'Student';
    $initial = strtoupper(substr($uname, 0, 1));

    $links = [
        'dashboard'     => ['Dashboard',         'bi-speedometer2',      'student/dashboard.php'],
        'apply'         => ['Apply for Dorm',     'bi-file-earmark-plus', 'student/apply.php'],
        'status'        => ['Application Status', 'bi-clipboard-check',   'student/status.php'],
        'billing'       => ['My Billing',         'bi-cash-coin',         'student/billing.php'],
        'announcements' => ['Announcements',      'bi-megaphone',         'student/announcements.php'],
        'notifications' => ['Notifications',      'bi-bell',              'student/notifications.php'],
    ];

    echo '<nav class="sidebar">';
    echo '<div class="sidebar-brand">';
    echo '<div class="sidebar-brand-icon"><i class="bi bi-building"></i></div>';
    echo '<div class="sidebar-brand-text"><div class="sidebar-brand-title">TiraHub</div><div class="sidebar-brand-sub">Student Portal</div></div>';
    echo '</div>';
    echo '<ul class="sidebar-nav">';
    echo '<li class="sidebar-nav-label">Student Menu</li>';
    foreach ($links as $key => [$label, $icon, $href]) {
        $cls   = ($active === $key) ? 'active' : '';
        $badge = ($key === 'notifications' && $notif > 0) ? "<span class='nav-badge notif-live-count'>{$notif}</span>" : '';
        echo "<li><a href='../{$href}' class='{$cls}'><i class='bi {$icon}'></i><span>{$label}</span>{$badge}</a></li>";
    }
    echo '</ul>';
    echo '<div class="sidebar-footer">';
    echo "<a href='../logout.php'><i class='bi bi-box-arrow-left'></i><span>Logout</span></a>";
    echo '</div></nav>';

    $badge2 = $notif > 0 ? "<span class='badge bg-danger notif-live-count'>{$notif}</span>" : "<span class='badge bg-danger notif-live-count' style='display:none'></span>";
    echo <<<HTML
<div class="main-wrapper">
<header class="topbar">
  <div class="topbar-left">
    <button class="sidebar-toggle d-lg-none" onclick="toggleSidebar()">
      <i class="bi bi-list fs-5"></i>
    </button>
  </div>
  <div class="topbar-right">
    <div class="dropdown">
      <a class="notif-btn" data-bs-toggle="dropdown" title="Notifications">
        <i class="bi bi-bell"></i>{$badge2}
      </a>
      <div class="dropdown-menu notif-dropdown dropdown-menu-end p-0">
        <div class="notif-dropdown-header">
          <span><i class="bi bi-bell me-2"></i>Notifications</span>
          <a href="../student/notifications.php" class="small text-green">View all</a>
        </div>
        <div id="notifList">
          <div class="text-center text-muted py-3 small">Loading...</div>
        </div>
      </div>
    </div>
    <div class="dropdown">
      <a class="d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown" style="cursor:pointer">
        <div class="user-avatar">{$initial}</div>
        <div class="d-none d-md-block">
          <div class="fw-700 small">{$uname}</div>
          <div style="font-size:.7rem;color:#888">Student</div>
        </div>
        <i class="bi bi-chevron-down small text-muted d-none d-md-block"></i>
      </a>
      <ul class="dropdown-menu dropdown-menu-end" style="border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:none;min-width:180px">
        <li><div class="px-3 py-2 border-bottom"><div class="fw-700 small">{$uname}</div><div class="text-muted" style="font-size:.75rem">Student</div></div></li>
        <li><a class="dropdown-item py-2" href="../student/profile.php"><i class="bi bi-person me-2 text-green"></i>My Profile</a></li>
        <li><hr class="dropdown-divider my-1"></li>
        <li><a class="dropdown-item py-2 text-danger" href="../logout.php"><i class="bi bi-box-arrow-left me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</header>
<main class="main-content">
HTML;

    // Mobile bottom nav
    $mobileLinks = [
        ['Home',          'bi-speedometer2',   'student/dashboard.php',     'dashboard'],
        ['Apply',         'bi-file-earmark-plus','student/apply.php',        'apply'],
        ['Status',        'bi-clipboard-check', 'student/status.php',        'status'],
        ['Billing',       'bi-cash-coin',       'student/billing.php',       'billing'],
        ['Notifications', 'bi-bell',            'student/notifications.php', 'notifications'],
    ];
    echo '<nav class="mobile-bottom-nav"><ul>';
    foreach ($mobileLinks as [$lbl,$ico,$href,$key]) {
        $cls = $active === $key ? 'active' : '';
        $nb  = ($key==='notifications' && $notif>0) ? "<span class='mob-badge notif-live-count'>{$notif}</span>" : '';
        echo "<li><a href='../{$href}' class='{$cls}'><i class='bi {$ico}'></i>{$nb}<span>{$lbl}</span></a></li>";
    }
    echo '</ul></nav>';
}

function renderFooter(): void {
    echo <<<HTML
</main>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../assets/js/app.js"></script>
<script>
// Load notifications in dropdown
document.querySelectorAll('.notif-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const list = document.getElementById('notifList');
    if (!list) return;
    fetch('../api/notif_count.php?fetch=1')
      .then(r => r.json())
      .then(d => {
        if (!d.items || d.items.length === 0) {
          list.innerHTML = '<div class="text-center text-muted py-4 small"><i class="bi bi-bell-slash d-block mb-2" style="font-size:1.5rem"></i>No new notifications</div>';
          return;
        }
        list.innerHTML = d.items.map(function(n){
          var nBg  = {Success:'#e8f5ee',Error:'#fff0f2',Warning:'#fff8e1',Info:'#e8f0ff'};
          var nClr = {Success:'#1a7a4a',Error:'#dc3545',Warning:'#f0a500',Info:'#0d6efd'};
          var nIco = {Success:'bi-check-circle',Error:'bi-x-circle',Warning:'bi-exclamation-triangle',Info:'bi-info-circle'};
          var bg   = nBg[n.type]  ? nBg[n.type]  : '#f8f9fa';
          var clr  = nClr[n.type] ? nClr[n.type] : '#666';
          var ico  = nIco[n.type] ? nIco[n.type] : 'bi-bell';
          var unread = n.is_read==0 ? 'unread' : '';
          var msg  = n.message.length > 60 ? n.message.substring(0,60)+'...' : n.message;
          return '<div class="notif-item '+unread+'">'
            +'<div class="d-flex gap-2 align-items-start">'
            +'<div class="notif-icon" style="background:'+bg+';color:'+clr+'"><i class="bi '+ico+'"></i></div>'
            +'<div><div class="fw-600 small">'+n.title+'</div>'
            +'<div class="text-muted" style="font-size:.75rem">'+msg+'</div>'
            +'<div class="text-muted" style="font-size:.7rem">'+n.created_at+'</div>'
            +'</div></div></div>';
        }).join('');
      })
      .catch(() => {
        list.innerHTML = '<div class="text-center text-muted py-3 small">Could not load notifications.</div>';
      });
  });
});
</script>
</body>
</html>
HTML;
}

function statusBadge(string $status): string {
    $map = [
        'Pending'           => ['warning',   'bi-clock'],
        'Under Review'      => ['info',      'bi-eye'],
        'Approved'          => ['success',   'bi-check-circle'],
        'Rejected'          => ['danger',    'bi-x-circle'],
        'Cancelled'         => ['secondary', 'bi-slash-circle'],
        'Active'            => ['success',   'bi-check-circle'],
        'Checked Out'       => ['secondary', 'bi-box-arrow-right'],
        'Transferred'       => ['info',      'bi-arrow-left-right'],
        'Available'         => ['success',   'bi-door-open'],
        'Full'              => ['danger',    'bi-door-closed'],
        'Under Maintenance' => ['warning',   'bi-tools'],
        'Reserved'          => ['info',      'bi-lock'],
        'Unpaid'            => ['danger',    'bi-exclamation-circle'],
        'Partial'           => ['warning',   'bi-dash-circle'],
        'Paid'              => ['success',   'bi-check-circle'],
        'Overdue'           => ['dark',      'bi-alarm'],
        'Normal'            => ['secondary', 'bi-info-circle'],
        'Important'         => ['warning',   'bi-star'],
        'Urgent'            => ['danger',    'bi-lightning'],
    ];
    [$color, $icon] = $map[$status] ?? ['secondary', 'bi-circle'];
    return "<span class='badge bg-{$color}'><i class='bi {$icon} me-1'></i>{$status}</span>";
}

function paginate(int $total, int $page, int $perPage, string $url): string {
    $pages = (int)ceil($total / $perPage);
    if ($pages <= 1) return '';
    $html = '<nav aria-label="Pagination"><ul class="pagination pagination-sm mb-0">';
    if ($page > 1)
        $html .= "<li class='page-item'><a class='page-link' href='{$url}&page=".($page-1)."'><i class='bi bi-chevron-left'></i></a></li>";
    for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++) {
        $active = ($i === $page) ? 'active' : '';
        $html  .= "<li class='page-item {$active}'><a class='page-link' href='{$url}&page={$i}'>{$i}</a></li>";
    }
    if ($page < $pages)
        $html .= "<li class='page-item'><a class='page-link' href='{$url}&page=".($page+1)."'><i class='bi bi-chevron-right'></i></a></li>";
    $html .= '</ul></nav>';
    return $html;
}
