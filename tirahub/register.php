<?php
// ============================================================
//  TiraHub – Student Registration (Pure PHP/PDO - No SP)
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
<title>TiraHub – Register</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root { --th-green:#1a7a4a; --th-green-dark:#145e38; --th-green-light:#e8f5ee; }
  body { background: linear-gradient(135deg,#1a7a4a 0%,#0d4f2e 60%,#072b19 100%); min-height:100vh; font-family:'Segoe UI',sans-serif; padding:2rem 0; }
  .reg-card { background:#fff; border-radius:18px; box-shadow:0 20px 60px rgba(0,0,0,.3); max-width:700px; margin:0 auto; padding:2.5rem 2.2rem; }
  .brand-logo { width:64px;height:64px;background:var(--th-green);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .8rem; }
  .brand-logo i { font-size:1.8rem;color:#fff; }
  .brand-title { color:var(--th-green-dark);font-weight:800;font-size:1.5rem; }
  .section-label { font-weight:700;color:var(--th-green-dark);border-left:4px solid var(--th-green);padding-left:.6rem;margin:1.4rem 0 .8rem;font-size:.88rem;text-transform:uppercase;letter-spacing:.5px; }
  .form-label { font-weight:600;color:#333;font-size:.85rem; }
  .form-control:focus,.form-select:focus { border-color:var(--th-green);box-shadow:0 0 0 .2rem rgba(26,122,74,.2); }
  .btn-register { background:var(--th-green);border:none;color:#fff;font-weight:700;padding:.75rem;border-radius:10px;font-size:1rem;transition:background .2s; }
  .btn-register:hover { background:var(--th-green-dark);color:#fff; }
  .req { color:#dc3545; }
</style>
</head>
<body>
<div class="container py-3">
<div class="reg-card">
  <div class="text-center mb-4">
    <div class="brand-logo"><i class="bi bi-building"></i></div>
    <div class="brand-title">TiraHub</div>
    <small class="text-muted">Create your student account</small>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger py-2 small">
      <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" autocomplete="off" novalidate>
    <div class="section-label"><i class="bi bi-person-badge me-1"></i>Account Information</div>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Username <span class="req">*</span></label>
        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username']??'') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email Address <span class="req">*</span></label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Password <span class="req">*</span></label>
        <div class="input-group">
          <input type="password" name="password" id="pw1" class="form-control" placeholder="Min. 8 characters" required>
          <button type="button" class="btn btn-outline-secondary" onclick="togglePw('pw1','eye1')"><i class="bi bi-eye" id="eye1"></i></button>
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Confirm Password <span class="req">*</span></label>
        <div class="input-group">
          <input type="password" name="confirm_password" id="pw2" class="form-control" required>
          <button type="button" class="btn btn-outline-secondary" onclick="togglePw('pw2','eye2')"><i class="bi bi-eye" id="eye2"></i></button>
        </div>
      </div>
    </div>

    <div class="section-label"><i class="bi bi-person me-1"></i>Personal Information</div>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">First Name <span class="req">*</span></label>
        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($_POST['first_name']??'') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Last Name <span class="req">*</span></label>
        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($_POST['last_name']??'') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Middle Name</label>
        <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($_POST['middle_name']??'') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Student Number <span class="req">*</span></label>
        <input type="text" name="student_number" class="form-control" value="<?= htmlspecialchars($_POST['student_number']??'') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Gender <span class="req">*</span></label>
        <select name="gender" class="form-select" required>
          <option value="">Select Gender</option>
          <?php foreach(['Male','Female','Other'] as $g): ?>
            <option value="<?= $g ?>" <?= (($_POST['gender'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Birthdate <span class="req">*</span></label>
        <input type="date" name="birthdate" class="form-control" value="<?= htmlspecialchars($_POST['birthdate']??'') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Contact Number</label>
        <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($_POST['contact_number']??'') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Course / Program</label>
        <input type="text" name="course" class="form-control" value="<?= htmlspecialchars($_POST['course']??'') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Year Level</label>
        <select name="year_level" class="form-select">
          <option value="">—</option>
          <?php for($y=1;$y<=6;$y++): ?>
            <option value="<?= $y ?>" <?= (($_POST['year_level']??'')==$y)?'selected':'' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-9">
        <label class="form-label">Home Address</label>
        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($_POST['address']??'') ?>">
      </div>
    </div>

    <button type="submit" class="btn btn-register w-100 mt-4">
      <i class="bi bi-person-plus me-1"></i> Create Account
    </button>
  </form>

  <hr class="mt-3">
  <p class="text-center mb-0 small">
    Already have an account?
    <a href="index.php" style="color:var(--th-green);font-weight:600;">Login here</a>
  </p>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePw(fieldId, iconId) {
  const f = document.getElementById(fieldId);
  const i = document.getElementById(iconId);
  if (f.type === 'password') { f.type = 'text'; i.classList.replace('bi-eye','bi-eye-slash'); }
  else { f.type = 'password'; i.classList.replace('bi-eye-slash','bi-eye'); }
}
</script>
</body>
</html>
