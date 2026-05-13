<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireStudent();
$pdo       = Database::connect();
$studentId = (int)$_SESSION['student_id'];
$message   = '';
$msgType   = 'success';

// Check existing
$chk = $pdo->prepare("SELECT status FROM applications WHERE student_id=? AND status IN ('Pending','Under Review','Approved') LIMIT 1");
$chk->execute([$studentId]); $existing = $chk->fetch();
$hasRoom = $pdo->prepare("SELECT assignment_id FROM room_assignments WHERE student_id=? AND status='Active' LIMIT 1");
$hasRoom->execute([$studentId]); $roomAssigned = $hasRoom->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing && !$roomAssigned) {
    $roomType = $_POST['preferred_room_type'] ?? '';
    $reason   = trim($_POST['reason'] ?? '');
    if (empty($reason)) {
        $message = 'Please provide your reason for applying.';
        $msgType = 'danger';
    } else {
        $stmt = $pdo->prepare("CALL sp_submit_application(?,?,?,@aid,@msg)");
        $stmt->execute([$studentId, $roomType ?: null, $reason]);
        $res  = $pdo->query("SELECT @aid AS application_id, @msg AS message")->fetch();
        if ((int)$res['application_id'] > 0) {
            header('Location: status.php?applied=1'); exit;
        } else {
            $message = $res['message'];
            $msgType = 'danger';
        }
    }
}

// Room types with info
$roomTypes = [
    'Single' => ['icon'=>'bi-person',       'desc'=>'Private room for 1 person',          'rate'=>'₱2,500 - ₱2,800/mo'],
    'Double' => ['icon'=>'bi-people',        'desc'=>'Shared room for 2 persons',          'rate'=>'₱1,800 - ₱2,000/mo'],
    'Triple' => ['icon'=>'bi-people-fill',   'desc'=>'Shared room for 3 persons',          'rate'=>'₱1,500/mo'],
    'Quad'   => ['icon'=>'bi-grid',          'desc'=>'Shared room for 4 persons',          'rate'=>'₱1,200/mo'],
    'Suite'  => ['icon'=>'bi-stars',         'desc'=>'Premium suite with own amenities',   'rate'=>'₱3,500 - ₱4,000/mo'],
];

renderHead('Apply for Dorm');
renderStudentNav('apply', $_SESSION['user_id']);
?>
<div class="page-header">
  <div class="page-header-left">
    <h4><i class="bi bi-file-earmark-plus"></i>Apply for Dormitory</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Apply</li></ol></nav>
  </div>
</div>

<?php if ($roomAssigned): ?>
  <div class="alert alert-success"><i class="bi bi-house-check me-2"></i>You are already assigned to a room. No need to apply again.</div>
<?php elseif ($existing): ?>
  <div class="alert alert-warning"><i class="bi bi-clock me-2"></i>You already have an active application with status: <strong><?= htmlspecialchars($existing['status']) ?></strong>. Please wait for the result.</div>
<?php else: ?>

<?php if ($message): ?>
  <div class="alert alert-<?= $msgType ?> alert-auto-dismiss alert-dismissible">
    <i class="bi bi-<?= $msgType==='success'?'check-circle':'exclamation-circle' ?> me-2"></i><?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="th-card">
  <div class="card-header"><span><i class="bi bi-file-earmark-plus me-2"></i>Dormitory Application Form</span></div>
  <div class="card-body">

    <!-- Step Wizard -->
    <div class="step-wizard">
      <div class="step-item active" data-step="1">
        <div class="step-circle">1</div>
        <div class="step-label">Room Type</div>
      </div>
      <div class="step-item" data-step="2">
        <div class="step-circle">2</div>
        <div class="step-label">Reason</div>
      </div>
      <div class="step-item" data-step="3">
        <div class="step-circle">3</div>
        <div class="step-label">Confirm</div>
      </div>
    </div>

    <form method="POST" id="applyForm" class="no-loader">
      <!-- Step 1: Room Type -->
      <div class="step-pane active" data-step="1">
        <div class="form-section-title"><i class="bi bi-door-open me-1"></i>Select Preferred Room Type</div>
        <p class="text-muted small mb-3">Choose the type of room you prefer. This is not final — admin will assign the actual room.</p>
        <div class="row g-3 mb-3">
          <?php foreach ($roomTypes as $type => $info): ?>
          <div class="col-md-4 col-6">
            <label class="d-block" style="cursor:pointer">
              <input type="radio" name="preferred_room_type" value="<?= $type ?>" class="d-none room-type-radio">
              <div class="room-type-card p-3 rounded border-2 border text-center" style="transition:all .2s;border-color:#e9ecef">
                <i class="bi <?= $info['icon'] ?>" style="font-size:2rem;color:#ccc;display:block;margin-bottom:.5rem"></i>
                <div class="fw-700 mb-1"><?= $type ?></div>
                <div class="text-muted small mb-1"><?= $info['desc'] ?></div>
                <div class="fw-600 small" style="color:var(--th-green)"><?= $info['rate'] ?></div>
              </div>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Step 2: Reason -->
      <div class="step-pane" data-step="2">
        <div class="form-section-title"><i class="bi bi-chat-text me-1"></i>Reason for Application</div>
        <p class="text-muted small mb-3">Tell us why you want to stay in the dormitory. This helps the admin review your application.</p>
        <div class="mb-3">
          <label class="form-label">Your Reason <span class="text-danger">*</span></label>
          <textarea name="reason" id="reasonField" class="form-control" rows="5"
            placeholder="e.g. I live far from school and it takes 2+ hours to commute daily. Staying in the dorm will help me focus on my studies..." required
            style="resize:vertical"><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
          <div class="d-flex justify-content-between mt-1">
            <small class="text-muted">Be specific and honest. Minimum 20 characters.</small>
            <small class="text-muted"><span id="charCount">0</span> chars</small>
          </div>
        </div>
      </div>

      <!-- Step 3: Confirm -->
      <div class="step-pane" data-step="3">
        <div class="form-section-title"><i class="bi bi-check-circle me-1"></i>Review & Confirm</div>
        <div class="alert alert-green mb-4">
          <i class="bi bi-info-circle me-2"></i>Please review your application before submitting.
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="p-3 rounded" style="background:#f9fafb;border:1px solid #f0f0f0">
              <div class="text-muted small mb-1">Preferred Room Type</div>
              <div class="fw-700" id="confirmRoomType">—</div>
            </div>
          </div>
          <div class="col-12">
            <div class="p-3 rounded" style="background:#f9fafb;border:1px solid #f0f0f0">
              <div class="text-muted small mb-1">Your Reason</div>
              <div class="small" id="confirmReason" style="white-space:pre-wrap">—</div>
            </div>
          </div>
        </div>
        <div class="mt-3 p-3 rounded border border-warning" style="background:#fffbf0">
          <i class="bi bi-exclamation-triangle text-warning me-2"></i>
          <small>By submitting, you confirm that all information is accurate. False information may result in application rejection.</small>
        </div>
      </div>

      <!-- Wizard Navigation -->
      <div class="d-flex justify-content-between mt-4 pt-3 border-top">
        <button type="button" id="stepPrev" class="btn btn-outline-secondary px-4" onclick="goToStep(currentStep-1)" style="display:none">
          <i class="bi bi-arrow-left me-1"></i>Previous
        </button>
        <div></div>
        <div class="d-flex gap-2">
          <button type="button" id="stepNext" class="btn btn-th-primary px-4" onclick="goToStep(currentStep+1)">
            Next <i class="bi bi-arrow-right ms-1"></i>
          </button>
          <button type="submit" id="stepSubmit" class="btn btn-success px-4" style="display:none"
            onclick="showLoader('Submitting application...')">
            <i class="bi bi-send me-1"></i>Submit Application
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// Room type card selection
document.querySelectorAll('.room-type-radio').forEach(radio => {
  radio.addEventListener('change', () => {
    document.querySelectorAll('.room-type-card').forEach(c => {
      c.style.borderColor = '#e9ecef';
      c.style.background  = '#fff';
      c.querySelector('i').style.color = '#ccc';
    });
    const card = radio.nextElementSibling;
    card.style.borderColor = 'var(--th-green)';
    card.style.background  = 'var(--th-green-pale, #f0faf4)';
    card.querySelector('i').style.color = 'var(--th-green)';
    document.getElementById('confirmRoomType').textContent = radio.value || '(No preference)';
  });
});

// Char counter
const reason = document.getElementById('reasonField');
const counter = document.getElementById('charCount');
if (reason && counter) {
  reason.addEventListener('input', () => {
    counter.textContent = reason.value.length;
    document.getElementById('confirmReason').textContent = reason.value;
  });
}

// Override goToStep to update confirm
const _orig = window.goToStep;
window.goToStep = function(step) {
  if (step === 3) {
    const sel = document.querySelector('.room-type-radio:checked');
    document.getElementById('confirmRoomType').textContent = sel ? sel.value : 'No preference';
    document.getElementById('confirmReason').textContent   = document.getElementById('reasonField')?.value || '—';
  }
  if (_orig) _orig(step);
};
</script>
<?php endif; ?>
<?php renderFooter(); ?>
