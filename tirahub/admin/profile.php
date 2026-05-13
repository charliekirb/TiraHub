<?php
// ============================================================
//  TiraHub – Admin: Profile
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireAdmin();

$pdo     = Database::connect();
$message = '';
$msgType = 'success';

$user = $pdo->prepare("SELECT * FROM users WHERE user_id=?");
$user->execute([$_SESSION['user_id']]); $user = $user->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_info'])) {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email.'; $msgType = 'danger';
        } else {
            $pdo->prepare("UPDATE users SET email=?, username=? WHERE user_id=?")
                ->execute([$email, trim($_POST['username']), $_SESSION['user_id']]);
            $_SESSION['username'] = trim($_POST['username']);
            $message = 'Profile updated.';
        }
    } elseif (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password_hash'])) {
            $message = 'Current password is incorrect.'; $msgType = 'danger';
        } elseif (strlen($new) < 8) {
            $message = 'New password must be at least 8 characters.'; $msgType = 'danger';
        } elseif ($new !== $confirm) {
            $message = 'Passwords do not match.'; $msgType = 'danger';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]);
            $pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?")->execute([$hash, $_SESSION['user_id']]);
            $message = 'Password changed successfully.';
        }
    }
    // Refresh
    $stmtU = $pdo->prepare("SELECT * FROM users WHERE user_id=?");
    $stmtU->execute([$_SESSION['user_id']]); $user = $stmtU->fetch();
}

// Recent audit logs for this admin
$logs = $pdo->prepare("SELECT * FROM audit_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$logs->execute([$_SESSION['user_id']]); $auditLogs = $logs->fetchAll();

renderHead('My Profile');
renderAdminNav('profile', $_SESSION['user_id']);
?>
<div class="page-header">
  <div>
    <h4><i class="bi bi-person-circle me-2"></i>My Profile</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Profile</li></ol></nav>
  </div>
</div>

<?php if ($message): ?>
  <div class="alert alert-<?= $msgType==='success'?'success':'danger' ?> alert-auto-dismiss alert-dismissible">
    <i class="bi bi-<?= $msgType==='success'?'check-circle':'x-circle' ?> me-2"></i><?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <!-- Update Info -->
    <div class="th-card mb-4">
      <div class="card-header"><i class="bi bi-person me-2"></i>Account Information</div>
      <div class="card-body">
        <div class="text-center mb-4">
          <div style="width:80px;height:80px;background:var(--th-green);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:2.5rem;color:#fff">
            <i class="bi bi-person-fill"></i>
          </div>
          <div class="fw-700 fs-5"><?= htmlspecialchars($user['username']) ?></div>
          <span class="badge bg-success">Administrator</span>
        </div>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label fw-600">Username</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">Account Created</label>
            <input type="text" class="form-control" value="<?= date('M d, Y H:i', strtotime($user['created_at'])) ?>" disabled>
          </div>
          <button type="submit" name="update_info" value="1" class="btn btn-th-primary w-100">
            <i class="bi bi-save me-1"></i>Update Info
          </button>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="th-card">
      <div class="card-header"><i class="bi bi-lock me-2"></i>Change Password</div>
      <div class="card-body">
        <form method="POST">
          <div class="mb-3">
            <label class="form-label fw-600">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">New Password</label>
            <input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
          <button type="submit" name="change_password" value="1" class="btn btn-warning text-white w-100">
            <i class="bi bi-shield-lock me-1"></i>Change Password
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Audit Log -->
  <div class="col-lg-7">
    <div class="th-card">
      <div class="card-header"><i class="bi bi-journal-text me-2"></i>My Activity Log</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="th-table">
            <thead><tr><th>Action</th><th>Table</th><th>Record</th><th>Date</th></tr></thead>
            <tbody>
              <?php if (empty($auditLogs)): ?>
                <tr><td colspan="4" class="text-center py-4 text-muted">No activity recorded.</td></tr>
              <?php else: ?>
                <?php foreach ($auditLogs as $log): ?>
                <tr>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($log['action']) ?></span></td>
                  <td><small><?= htmlspecialchars($log['table_name']) ?></small></td>
                  <td><small>#<?= $log['record_id'] ?></small></td>
                  <td><small><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></small></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php renderFooter(); ?>
