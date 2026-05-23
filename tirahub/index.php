<?php
// ============================================================
//  TiraHub – Login Page (Premium Professional Design)
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
<title>TiraHub – Secure Login</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  :root {
    --th-green: #1a7a4a;
    --th-green-dark: #0e5a37;
    --th-green-light: #d4f5e4;
    --th-green-soft: #e6f4ec;
    --th-gray-50: #fafbfc;
    --th-gray-100: #f0f2f5;
    --th-gray-200: #e4e7eb;
    --th-gray-300: #d1d6dc;
    --th-gray-600: #5a6874;
    --th-gray-700: #3a4755;
    --th-gray-900: #1e293b;
  }

  body {
    font-family: 'Space Grotesk', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    background: var(--th-gray-50);
  }

  /* Premium gradient background with organic shapes */
  .bg-gradient {
    position: fixed;
    inset: 0;
    z-index: 0;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 30%, #fefce8 70%, #f0fdf4 100%);
  }

  /* Animated blob shapes */
  .blob {
    position: fixed;
    border-radius: 50%;
    filter: blur(60px);
    opacity: 0.4;
    z-index: 0;
    animation: float 20s infinite ease-in-out;
  }

  .blob-1 {
    width: 500px;
    height: 500px;
    background: var(--th-green);
    top: -200px;
    right: -100px;
    animation-delay: 0s;
  }

  .blob-2 {
    width: 600px;
    height: 600px;
    background: var(--th-green-dark);
    bottom: -250px;
    left: -150px;
    animation-delay: -7s;
  }

  .blob-3 {
    width: 350px;
    height: 350px;
    background: #34d399;
    top: 40%;
    right: 20%;
    animation-delay: -14s;
    opacity: 0.3;
  }

  @keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -30px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
  }

  /* Hero image section - split layout */
  .login-container {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 1280px;
    margin: 2rem;
    display: flex;
    border-radius: 2rem;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  }

  /* Left side - Hero with photo */
  .hero-section {
    flex: 1.2;
    background: linear-gradient(135deg, var(--th-green) 0%, var(--th-green-dark) 100%);
    padding: 3rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
  }

  .hero-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url('https://images.unsplash.com/photo-1555854877-bab0e564b8d5?q=80&w=2069&auto=format&fit=crop');
    background-size: cover;
    background-position: center;
    opacity: 0.25;
    mix-blend-mode: overlay;
  }

  .hero-section::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(26,122,74,0.3) 0%, rgba(14,90,55,0.5) 100%);
  }

  .hero-content {
    position: relative;
    z-index: 2;
    color: white;
  }

  .hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    padding: 0.5rem 1rem;
    border-radius: 100px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-bottom: 2rem;
    width: fit-content;
  }

  .hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 1rem;
  }

  .hero-title span {
    display: block;
    opacity: 0.9;
  }

  .hero-desc {
    font-size: 0.95rem;
    opacity: 0.85;
    line-height: 1.5;
    max-width: 85%;
  }

  .hero-stats {
    position: relative;
    z-index: 2;
    display: flex;
    gap: 2rem;
    margin-top: 3rem;
  }

  .stat {
    display: flex;
    flex-direction: column;
  }

  .stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: white;
  }

  .stat-label {
    font-size: 0.7rem;
    opacity: 0.7;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  /* Right side - Form */
  .form-section {
    flex: 0.9;
    background: white;
    padding: 3rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .logo-area {
    margin-bottom: 2rem;
  }

  .logo-icon {
    width: 48px;
    height: 48px;
    background: var(--th-green);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
  }

  .logo-icon i {
    font-size: 1.5rem;
    color: white;
  }

  .logo-area h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--th-gray-900);
    margin-bottom: 0.25rem;
  }

  .logo-area p {
    font-size: 0.875rem;
    color: var(--th-gray-600);
  }

  /* Form styling */
  .form-group {
    margin-bottom: 1.25rem;
  }

  .form-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--th-gray-700);
    margin-bottom: 0.5rem;
    display: block;
    letter-spacing: 0.3px;
  }

  .input-field {
    position: relative;
  }

  .input-field i:first-child {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--th-gray-400);
    font-size: 1rem;
    transition: color 0.2s;
  }

  .input-field input {
    width: 100%;
    padding: 0.875rem 1rem 0.875rem 2.75rem;
    border: 1.5px solid var(--th-gray-200);
    border-radius: 14px;
    font-size: 0.9rem;
    font-family: inherit;
    transition: all 0.2s;
    background: var(--th-gray-50);
  }

  .input-field input:focus {
    outline: none;
    border-color: var(--th-green);
    background: white;
    box-shadow: 0 0 0 4px rgba(26, 122, 74, 0.08);
  }

  .input-field input:focus + i {
    color: var(--th-green);
  }

  .password-toggle {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--th-gray-400);
    cursor: pointer;
    transition: color 0.2s;
  }

  .password-toggle:hover {
    color: var(--th-green);
  }

  /* Submit button */
  .btn-login {
    width: 100%;
    background: var(--th-green);
    color: white;
    border: none;
    padding: 0.875rem;
    border-radius: 14px;
    font-size: 0.9rem;
    font-weight: 600;
    font-family: inherit;
    margin-top: 0.5rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
  }

  .btn-login:hover {
    background: var(--th-green-dark);
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(26, 122, 74, 0.25);
  }

  /* Alert styling */
  .alert-custom {
    border-radius: 14px;
    border: none;
    padding: 0.875rem;
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .alert-success-custom {
    background: #ecfdf5;
    color: #065f46;
    border-left: 3px solid var(--th-green);
  }

  .alert-error-custom {
    background: #fef2f2;
    color: #991b1b;
    border-left: 3px solid #ef4444;
  }

  /* Divider */
  .divider {
    margin: 1.75rem 0;
    text-align: center;
    position: relative;
  }

  .divider::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    width: 100%;
    height: 1px;
    background: var(--th-gray-200);
  }

  .divider span {
    background: white;
    padding: 0 0.75rem;
    position: relative;
    font-size: 0.7rem;
    color: var(--th-gray-500);
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  /* Register link */
  .register-link {
    text-align: center;
    font-size: 0.85rem;
    color: var(--th-gray-600);
  }

  .register-link a {
    color: var(--th-green);
    font-weight: 600;
    text-decoration: none;
    transition: color 0.2s;
  }

  .register-link a:hover {
    color: var(--th-green-dark);
  }

  /* Responsive */
  @media (max-width: 900px) {
    .hero-section {
      display: none;
    }
    .form-section {
      flex: 1;
      padding: 2rem;
    }
    .login-container {
      margin: 1rem;
      max-width: 500px;
    }
  }

  @media (max-width: 480px) {
    .form-section {
      padding: 1.5rem;
    }
  }

  /* Subtle photo gallery dots */
  .photo-dots {
    position: absolute;
    bottom: 2rem;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    z-index: 2;
  }

  .dot {
    width: 6px;
    height: 6px;
    background: rgba(255,255,255,0.4);
    border-radius: 50%;
    transition: all 0.3s;
  }

  .dot.active {
    width: 20px;
    background: white;
    border-radius: 4px;
  }
</style>
</head>
<body>

<div class="bg-gradient"></div>
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<div class="login-container">
  <!-- Left Hero Section with Photo -->
  <div class="hero-section">
    <div class="hero-content">
      <div class="hero-badge">
        <i class="bi bi-building"></i>
        <span>TiraHub</span>
      </div>
      <div class="hero-title">
         
        <span>Dormitory Management System</span>
      </div>
      <div class="hero-desc">
        Streamlined dormitory operations for students and administrators.
      </div>
    </div>

    
  </div>

  <!-- Right Form Section -->
  <div class="form-section">
    <div class="logo-area">
      <div class="logo-icon">
        <i class="bi bi-building"></i>
      </div>
      <h2>Welcome back</h2>
      <p>Sign in to access your dashboard</p>
    </div>

    <!-- Alert Messages -->
    <?php if ($success): ?>
      <div class="alert-custom alert-success-custom">
        <i class="bi bi-check-circle-fill"></i>
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert-custom alert-error-custom">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" autocomplete="off" novalidate>
      <div class="form-group">
        <label class="form-label">Enter your Email</label>
        <div class="input-field">
          <i class="bi bi-envelope"></i>
          <input type="text" name="username" 
                 placeholder="Enter your email"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 required autofocus>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-field">
          <i class="bi bi-lock"></i>
          <input type="password" name="password" id="passwordField" 
                 placeholder="Enter your password" required>
          <button type="button" class="password-toggle" id="togglePassword">
            <i class="bi bi-eye-slash" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-login">
        <i class="bi bi-box-arrow-in-right"></i>
        Sign In
      </button>
    </form>

    <div class="divider">
      <span>New here?</span>
    </div>

    <div class="register-link">
      Don't have an account? <a href="register.php">Register here</a>
    </div>
  </div>
</div>

<script>
  // Password toggle functionality
  const toggleBtn = document.getElementById('togglePassword');
  const passwordField = document.getElementById('passwordField');
  const eyeIcon = document.getElementById('eyeIcon');

  toggleBtn.addEventListener('click', function() {
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    
    if (type === 'text') {
      eyeIcon.classList.remove('bi-eye-slash');
      eyeIcon.classList.add('bi-eye');
    } else {
      eyeIcon.classList.remove('bi-eye');
      eyeIcon.classList.add('bi-eye-slash');
    }
  });
</script>
</body>
</html>
