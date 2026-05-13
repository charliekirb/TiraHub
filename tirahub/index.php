<?php
// ============================================================
//  TiraHub – Login Page (Fixed)
// ============================================================
require_once 'config/auth.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit;
}

$error   = '';
$success = '';

// Flash message from registration
$flash = getFlash('login');
if ($flash) $success = $flash['message'];

// Direct session success from register.php
if (!empty($_SESSION['reg_success'])) {
    $success = $_SESSION['reg_success'];
    unset($_SESSION['reg_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    $pdo = Database::connect();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT u.*, s.student_id
                 FROM users u
                 LEFT JOIN students s ON s.user_id = u.user_id
                 WHERE (u.username = ? OR u.email = ?)
                   AND u.is_active = 1
                 LIMIT 1"
            );
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                setSession($user, (int)($user['student_id'] ?? 0));

                // Audit log
                $pdo->prepare(
                    "INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address)
                     VALUES (?,?,?,?,?)"
                )->execute([$user['user_id'], 'LOGIN', 'users', $user['user_id'], getClientIP()]);

                header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Login error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TiraHub – Login</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root {
    --th-green:      #1a7a4a;
    --th-green-dark: #145e38;
    --th-green-light:#e8f5ee;
  }
  body {
    background: linear-gradient(135deg, #1a7a4a 0%, #0d4f2e 60%, #072b19 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', sans-serif;
  }
  .login-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    width: 100%;
    max-width: 430px;
    padding: 2.5rem 2.2rem;
  }
  .brand-logo {
    width: 72px; height: 72px;
    background: var(--th-green);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem;
  }
  .brand-logo i { font-size: 2rem; color: #fff; }
  .brand-title  { color: var(--th-green-dark); font-weight: 800; font-size: 1.7rem; }
  .brand-sub    { color: #666; font-size: .85rem; }
  .form-label   { font-weight: 600; color: #333; font-size: .88rem; }
  .form-control:focus {
    border-color: var(--th-green);
    box-shadow: 0 0 0 .2rem rgba(26,122,74,.2);
  }
  .input-group-text { background: var(--th-green-light); border-right: none; color: var(--th-green); }
  .input-group .form-control { border-left: none; }
  .btn-login {
    background: var(--th-green); border: none; color: #fff;
    font-weight: 700; padding: .7rem; border-radius: 10px;
    transition: background .2s;
  }
  .btn-login:hover { background: var(--th-green-dark); color: #fff; }
  .register-link a { color: var(--th-green); font-weight: 600; }
  .toggle-pw { cursor:pointer; background:var(--th-green-light); border-left:none; color:var(--th-green); }
</style>
</head>
<body>
<div class="login-card">
  <div class="text-center mb-4">
    <div class="brand-logo"><i class="bi bi-building"></i></div>
    <div class="brand-title">TiraHub</div>
    <div class="brand-sub">Dormitory Management System</div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off" novalidate>
    <div class="mb-3">
      <label class="form-label">Username or Email</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-person"></i></span>
        <input type="text" name="username" class="form-control"
               placeholder="Enter username or email"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               required autofocus>
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label">Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-lock"></i></span>
        <input type="password" name="password" id="passwordField"
               class="form-control" placeholder="Enter your password" required>
        <span class="input-group-text toggle-pw" onclick="togglePw()">
          <i class="bi bi-eye" id="eyeIcon"></i>
        </span>
      </div>
    </div>
    <button type="submit" class="btn btn-login w-100 mb-3">
      <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
    </button>
  </form>

  <hr>
  <p class="text-center mb-0 register-link small">
    Don't have an account? <a href="register.php">Register here</a>
  </p>
</div>

<script>
function togglePw() {
  const f = document.getElementById('passwordField');
  const e = document.getElementById('eyeIcon');
  if (f.type === 'password') {
    f.type = 'text';
    e.classList.replace('bi-eye','bi-eye-slash');
  } else {
    f.type = 'password';
    e.classList.replace('bi-eye-slash','bi-eye');
  }
}
</script>
</body>
</html>
