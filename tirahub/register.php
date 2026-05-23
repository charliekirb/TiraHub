<?php
// ============================================================
//  TiraHub – Student Registration (Premium Professional Design)
// ============================================================
require_once 'config/auth.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    $pdo = Database::connect();

    $username       = trim($_POST['username']       ?? '');
    $email          = trim($_POST['email']          ?? '');
    $password       = $_POST['password']            ?? '';
    $confirm        = $_POST['confirm_password']    ?? '';
    $student_number = trim($_POST['student_number'] ?? '');
    $first_name     = trim($_POST['first_name']     ?? '');
    $last_name      = trim($_POST['last_name']      ?? '');
    $middle_name    = trim($_POST['middle_name']    ?? '') ?: null;
    $gender         = trim($_POST['gender']         ?? '');
    $birthdate      = trim($_POST['birthdate']      ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '') ?: null;
    $address        = trim($_POST['address']        ?? '') ?: null;
    $course         = trim($_POST['course']         ?? '') ?: null;
    $year_level     = !empty($_POST['year_level'])  ? (int)$_POST['year_level'] : null;

    if (empty($username) || empty($email) || empty($password) || empty($student_number)
        || empty($first_name) || empty($last_name) || empty($gender) || empty($birthdate)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicates
        $chk = $pdo->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
        $chk->execute([$username]);
        if ($chk->fetch()) {
            $error = 'Username is already taken.';
        } else {
            $chk = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = 'Email is already registered.';
            } else {
                $chk = $pdo->prepare("SELECT student_id FROM students WHERE student_number = ? LIMIT 1");
                $chk->execute([$student_number]);
                if ($chk->fetch()) {
                    $error = 'Student number already exists.';
                }
            }
        }

        if (empty($error)) {
            try {
                $pdo->beginTransaction();

                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                $stmt = $pdo->prepare(
                    "INSERT INTO users (username, email, password_hash, role, is_active)
                     VALUES (?, ?, ?, 'student', 1)"
                );
                $stmt->execute([$username, $email, $hash]);
                $userId = (int)$pdo->lastInsertId();

                $stmt = $pdo->prepare(
                    "INSERT INTO students
                        (user_id, student_number, first_name, last_name, middle_name,
                         gender, birthdate, contact_number, address, course, year_level)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $userId, $student_number, $first_name, $last_name, $middle_name,
                    $gender, $birthdate, $contact_number, $address, $course, $year_level
                ]);

                $pdo->commit();

                $_SESSION['reg_success'] = 'Account created successfully! You can now log in.';
                header('Location: index.php');
                exit;

            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TiraHub – Create Account</title>
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

  /* Split layout container */
  .register-container {
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

  /* Left side - Hero section (same as login) */
  .hero-section {
    flex: 1;
    background: linear-gradient(135deg, var(--th-green) 0%, var(--th-green-dark) 100%);
    padding: 2.5rem;
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
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 1rem;
  }

  .hero-desc {
    font-size: 0.9rem;
    opacity: 0.85;
    line-height: 1.5;
    max-width: 85%;
  }

  .hero-stats {
    position: relative;
    z-index: 2;
    display: flex;
    gap: 2rem;
    margin-top: 2rem;
  }

  .stat {
    display: flex;
    flex-direction: column;
  }

  .stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
  }

  .stat-label {
    font-size: 0.65rem;
    opacity: 0.7;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  /* Right side - Form (scrollable) */
  .form-section {
    flex: 1.2;
    background: white;
    padding: 2rem;
    max-height: 90vh;
    overflow-y: auto;
  }

  .form-section::-webkit-scrollbar {
    width: 6px;
  }

  .form-section::-webkit-scrollbar-track {
    background: var(--th-gray-100);
  }

  .form-section::-webkit-scrollbar-thumb {
    background: var(--th-green);
    border-radius: 3px;
  }

  .logo-area {
    margin-bottom: 1.5rem;
    text-align: center;
  }

  .logo-icon {
    width: 48px;
    height: 48px;
    background: var(--th-green);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.8rem;
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

  /* Section divider */
  .section-divider {
    display: flex;
    align-items: center;
    margin: 1.5rem 0 1rem;
  }
  .section-divider .line {
    flex: 1;
    height: 1px;
    background: var(--th-gray-200);
  }
  .section-divider .text {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--th-green);
    padding: 0 0.75rem;
  }

  /* Form styling */
  .form-group {
    margin-bottom: 1rem;
  }

  .form-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--th-gray-700);
    margin-bottom: 0.4rem;
    display: block;
    letter-spacing: 0.3px;
  }

  .form-label .req {
    color: #dc3545;
  }

  .input-field {
    position: relative;
  }

  .input-field i:first-child {
    position: absolute;
    left: 0.9rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--th-gray-400);
    font-size: 0.9rem;
    z-index: 1;
  }

  .input-field input, 
  .input-field select {
    width: 100%;
    padding: 0.7rem 0.9rem 0.7rem 2.5rem;
    border: 1.5px solid var(--th-gray-200);
    border-radius: 12px;
    font-size: 0.85rem;
    font-family: inherit;
    transition: all 0.2s;
    background: var(--th-gray-50);
  }

  .input-field input:focus,
  .input-field select:focus {
    outline: none;
    border-color: var(--th-green);
    background: white;
    box-shadow: 0 0 0 3px rgba(26, 122, 74, 0.08);
  }

  .password-toggle {
    position: absolute;
    right: 0.9rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--th-gray-400);
    cursor: pointer;
    padding: 0;
  }

  .password-toggle:hover {
    color: var(--th-green);
  }

  /* Two-column layout for form fields */
  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.8rem;
  }

  /* Submit button */
  .btn-register {
    width: 100%;
    background: var(--th-green);
    color: white;
    border: none;
    padding: 0.85rem;
    border-radius: 14px;
    font-size: 0.9rem;
    font-weight: 600;
    font-family: inherit;
    margin-top: 1rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
  }

  .btn-register:hover {
    background: var(--th-green-dark);
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(26, 122, 74, 0.25);
  }

  /* Alert styling */
  .alert-custom {
    border-radius: 12px;
    border: none;
    padding: 0.75rem;
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .alert-error-custom {
    background: #fef2f2;
    color: #991b1b;
    border-left: 3px solid #ef4444;
  }

  /* Login link */
  .login-link {
    text-align: center;
    font-size: 0.8rem;
    color: var(--th-gray-600);
    margin-top: 1.2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--th-gray-200);
  }

  .login-link a {
    color: var(--th-green);
    font-weight: 600;
    text-decoration: none;
  }

  .login-link a:hover {
    color: var(--th-green-dark);
  }

  /* Responsive */
  @media (max-width: 900px) {
    .hero-section {
      display: none;
    }
    .form-section {
      flex: 1;
      padding: 1.5rem;
      max-height: none;
    }
    .register-container {
      margin: 1rem;
      max-width: 550px;
    }
    .form-row {
      grid-template-columns: 1fr;
      gap: 0.5rem;
    }
  }
</style>
</head>
<body>

<div class="bg-gradient"></div>
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<div class="register-container">
  <!-- Left Hero Section (same as login page) -->
  <div class="hero-section">
    <div class="hero-content">
      <div class="hero-badge">
        <i class="bi bi-building"></i>
        <span>TiraHub</span>
      </div>
      <div class="hero-title">
        Join the Community
        <span>Start your dorm journey here</span>
      </div>
      <div class="hero-desc">
        Create your account to access room assignments, payments, and dorm announcements.
      </div>
    </div>
  </div>

  <!-- Right Form Section -->
  <div class="form-section">
    <div class="logo-area">
      <div class="logo-icon">
        <i class="bi bi-building"></i>
      </div>
      <h2>Create account</h2>
      <p>Fill in your details to register</p>
    </div>

    <!-- Error Alert -->
    <?php if ($error): ?>
      <div class="alert-custom alert-error-custom">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off" novalidate>
      <!-- Account Information Section -->
      <div class="section-divider">
        <div class="line"></div>
        <div class="text"><i class="bi bi-person-circle me-1"></i> Account</div>
        <div class="line"></div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Username <span class="req">*</span></label>
          <div class="input-field">
            <i class="bi bi-person"></i>
            <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email <span class="req">*</span></label>
          <div class="input-field">
            <i class="bi bi-envelope"></i>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Password <span class="req">*</span></label>
          <div class="input-field">
            <i class="bi bi-lock"></i>
            <input type="password" name="password" id="passwordField" required>
            <button type="button" class="password-toggle" onclick="togglePassword('passwordField', 'eyeIcon1')">
              <i class="bi bi-eye-slash" id="eyeIcon1"></i>
            </button>
          </div>
          <small class="text-muted" style="font-size: 0.65rem;">Minimum 8 characters</small>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password <span class="req">*</span></label>
          <div class="input-field">
            <i class="bi bi-lock-fill"></i>
            <input type="password" name="confirm_password" id="confirmField" required>
            <button type="button" class="password-toggle" onclick="togglePassword('confirmField', 'eyeIcon2')">
              <i class="bi bi-eye-slash" id="eyeIcon2"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Personal Information Section -->
      <div class="section-divider">
        <div class="line"></div>
        <div class="text"><i class="bi bi-card-text me-1"></i> Personal Details</div>
        <div class="line"></div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">First Name <span class="req">*</span></label>
          <div class="input-field">
            <i class="bi bi-person"></i>
            <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Last Name <span class="req">*</span></label>
          <div class="input-field">
            <i class="bi bi-person"></i>
            <input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Middle Name</label>
          <div class="input-field">
            <i class="bi bi-person"></i>
            <input type="text" name="middle_name" value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Student Number <span class="req">*</span></label>
          <div class="input-field">
            <i class="bi bi-credit-card"></i>
            <input type="text" name="student_number" value="<?= htmlspecialchars($_POST['student_number'] ?? '') ?>" required>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Gender <span class="req">*</span></label>
          <div class="input-field">
            <i class="bi bi-gender-ambiguous"></i>
            <select name="gender" required>
              <option value="">Select</option>
              <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                <option value="<?= $g ?>" <?= (($_POST['gender'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Birthdate <span class="req">*</span></label>
          <div class="input-field">
            <i class="bi bi-calendar"></i>
            <input type="date" name="birthdate" value="<?= htmlspecialchars($_POST['birthdate'] ?? '') ?>" required>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Contact Number</label>
          <div class="input-field">
            <i class="bi bi-telephone"></i>
            <input type="text" name="contact_number" value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Course/Program</label>
          <div class="input-field">
            <i class="bi bi-book"></i>
            <input type="text" name="course" value="<?= htmlspecialchars($_POST['course'] ?? '') ?>">
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Year Level</label>
          <div class="input-field">
            <i class="bi bi-sort-numeric-up"></i>
            <select name="year_level">
              <option value="">Select Year</option>
              <?php for ($y = 1; $y <= 4; $y++): ?>
                <option value="<?= $y ?>" <?= (($_POST['year_level'] ?? '') == $y) ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Home Address</label>
          <div class="input-field">
            <i class="bi bi-house"></i>
            <input type="text" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
          </div>
        </div>
      </div>

      <button type="submit" class="btn-register">
        <i class="bi bi-person-plus"></i>
        Create Account
      </button>
    </form>

    <div class="login-link">
      Already have an account? <a href="index.php">Sign in here</a>
    </div>
  </div>
</div>

<script>
  function togglePassword(fieldId, iconId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(iconId);
    if (field.type === 'password') {
      field.type = 'text';
      icon.classList.remove('bi-eye-slash');
      icon.classList.add('bi-eye');
    } else {
      field.type = 'password';
      icon.classList.remove('bi-eye');
      icon.classList.add('bi-eye-slash');
    }
  }
</script>
</body>
</html>
