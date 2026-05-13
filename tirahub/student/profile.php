<?php
// ============================================================
//  TiraHub – Student: Profile
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireStudent();

$pdo       = Database::connect();
$studentId = (int)$_SESSION['student_id'];
$message   = '';
$msgType   = 'success';

$user    = $pdo->prepare("SELECT * FROM users WHERE user_id=?");
$user->execute([$_SESSION['user_id']]); $user = $user->fetch();

$student = $pdo->prepare("SELECT * FROM students WHERE student_id=?");
$student->execute([$studentId]); $student = $student->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $pdo->prepare("UPDATE students SET first_name=?,last_name=?,middle_name=?,contact_number=?,address=?,course=?,year_level=?,guardian_name=?,guardian_contact=? WHERE student_id=?")
            ->execute([
                trim($_POST['first_name']), trim($_POST['last_name']), trim($_POST['middle_name']) ?: null,
                trim($_POST['contact_number']) ?: null, trim($_POST['address']) ?: null,
                trim($_POST['course']) ?: null, (int)$_POST['year_level'] ?: null,
                trim($_POST['guardian_name']) ?: null, trim($_POST['guardian_contact']) ?: null,
                $studentId
            ]);
        $pdo->prepare("UPDATE users SET email=? WHERE user_id=?")->execute([trim($_POST['email']), $_SESSION['user_id']]);
        $message = 'Profile updated successfully.';
        $student = $pdo->prepare("SELECT * FROM students WHERE student_id=?")->execute([$studentId]) ? $student : $student;
        $stmtS   = $pdo->prepare("SELECT * FROM students WHERE student_id=?"); $stmtS->execute([$studentId]); $student = $stmtS->fetch();
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
            $pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?")
                ->execute([password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]), $_SESSION['user_id']]);
            $message = 'Password changed successfully.';
        }
    }
}

renderHead('My Profile');
renderStudentNav('profile', $_SESSION['user_id']);
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
  <div class="col-lg-8">
    <div class="th-card">
      <div class="card-header"><i class="bi bi-person me-2"></i>Personal Information</div>
      <div class="card-body">
        <form method="POST">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-600">First Name</label>
              <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($student['first_name']) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-600">Last Name</label>
              <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($student['last_name']) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-600">Middle Name</label>
              <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($student['middle_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Email</label>
              <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Contact Number</label>
              <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($student['contact_number'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Course / Program</label>
              <input type="text" name="course" class="form-control" value="<?= htmlspecialchars($student['course'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-600">Year Level</label>
              <select name="year_level" class="form-select">
                <option value="">—</option>
                <?php for ($y=1;$y<=6;$y++): ?>
                  <option value="<?= $y ?>" <?= $student['year_level']==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-9">
              <label class="form-label fw-600">Address</label>
              <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($student['address'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Guardian Name</label>
              <input type="text" name="guardian_name" class="form-control" value="<?= htmlspecialchars($student['guardian_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Guardian Contact</label>
              <input type="text" name="guardian_contact" class="form-control" value="<?= htmlspecialchars($student['guardian_contact'] ?? '') ?>">
            </div>
          </div>
          <button type="submit" name="update_profile" value="1" class="btn btn-th-primary mt-4">
            <i class="bi bi-save me-1"></i>Update Profile
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <!-- Account Info -->
    <div class="th-card mb-4">
      <div class="card-header"><i class="bi bi-shield-lock me-2"></i>Account Info</div>
      <div class="card-body text-center">
        <div style="width:72px;height:72px;background:var(--th-green);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:2rem;color:#fff">
          <i class="bi bi-person-fill"></i>
        </div>
        <div class="fw-700"><?= htmlspecialchars($user['username']) ?></div>
        <div class="text-muted small mb-1"><?= htmlspecialchars($user['email']) ?></div>
        <span class="badge bg-primary">Student</span>
        <div class="text-muted small mt-2">Student No: <strong><?= htmlspecialchars($student['student_number']) ?></strong></div>
        <div class="text-muted small">Since <?= date('M Y', strtotime($user['created_at'])) ?></div>
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
            <label class="form-label fw-600">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
          <button type="submit" name="change_password" value="1" class="btn btn-warning text-white w-100">
            <i class="bi bi-shield-lock me-1"></i>Change Password
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php renderFooter(); ?>
