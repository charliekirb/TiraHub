<?php
// ============================================================
//  TiraHub – Admin: Room Management
// ============================================================
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';
requireAdmin();

$pdo     = Database::connect();
$message = '';
$msgType = 'success';

// Assign room flow
$assignStudentId = (int)($_GET['assign'] ?? 0);
$assignStudent   = null;
if ($assignStudentId) {
    $stmt = $pdo->prepare("SELECT * FROM vw_students_full WHERE student_id = ?");
    $stmt->execute([$assignStudentId]);
    $assignStudent = $stmt->fetch();
}

// Handle room assignment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_room'])) {
    $roomId    = (int)($_POST['room_id'] ?? 0);
    $studId    = (int)($_POST['student_id'] ?? 0);
    $checkIn   = $_POST['check_in_date'] ?? date('Y-m-d');
    $stmt = $pdo->prepare("CALL sp_assign_room(?,?,?,?,@aid,@msg)");
    $stmt->execute([$studId, $roomId, $_SESSION['user_id'], $checkIn]);
    $res     = $pdo->query("SELECT @aid AS assignment_id, @msg AS message")->fetch();
    $message = $res['message'];
    $msgType = (int)$res['assignment_id'] > 0 ? 'success' : 'danger';
    if ($msgType === 'success') $assignStudentId = 0;
}

// Handle add/edit room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_room'])) {
    $roomId     = (int)($_POST['room_id'] ?? 0);
    $buildingId = (int)$_POST['building_id'];
    $roomNum    = trim($_POST['room_number']);
    $floor      = (int)$_POST['floor'];
    $type       = $_POST['room_type'];
    $cap        = (int)$_POST['capacity'];
    $rate       = (float)$_POST['monthly_rate'];
    $status     = $_POST['status'];
    $desc       = trim($_POST['description'] ?? '');
    if ($roomId) {
        $stmt = $pdo->prepare("UPDATE rooms SET building_id=?,room_number=?,floor=?,room_type=?,capacity=?,monthly_rate=?,status=?,description=? WHERE room_id=?");
        $stmt->execute([$buildingId,$roomNum,$floor,$type,$cap,$rate,$status,$desc,$roomId]);
        $message = 'Room updated successfully.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO rooms(building_id,room_number,floor,room_type,capacity,monthly_rate,status,description) VALUES(?,?,?,?,?,?,?,?)");
        $stmt->execute([$buildingId,$roomNum,$floor,$type,$cap,$rate,'Available',$desc]);
        $message = 'Room added successfully.';
    }
}

// Handle delete room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_room'])) {
    $roomId = (int)$_POST['room_id'];
    try {
        $pdo->prepare("DELETE FROM rooms WHERE room_id=? AND current_occupants=0")->execute([$roomId]);
        $message = 'Room deleted.';
    } catch (PDOException $e) {
        $message = 'Cannot delete room with existing assignments.';
        $msgType = 'danger';
    }
}

$buildings = $pdo->query("SELECT * FROM buildings WHERE is_active=1 ORDER BY building_name")->fetchAll();

// Filters
$buildFilter  = (int)($_GET['building'] ?? 0);
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['search'] ?? '');
$page         = max(1,(int)($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page-1)*$perPage;

$where  = []; $params = [];
if ($buildFilter)  { $where[] = 'r.building_id=?'; $params[] = $buildFilter; }
if ($statusFilter) { $where[] = 'r.status=?';      $params[] = $statusFilter; }
if ($search)       { $where[] = '(r.room_number LIKE ? OR b.building_name LIKE ?)'; $params[]= "%$search%"; $params[]= "%$search%"; }
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

$stmtC = $pdo->prepare("SELECT COUNT(*) FROM rooms r INNER JOIN buildings b ON r.building_id=b.building_id $whereSQL");
$stmtC->execute($params); $total = (int)$stmtC->fetchColumn();

$stmtL = $pdo->prepare("SELECT r.*, b.building_name FROM rooms r INNER JOIN buildings b ON r.building_id=b.building_id $whereSQL ORDER BY b.building_name,r.floor,r.room_number LIMIT $perPage OFFSET $offset");
$stmtL->execute($params); $rooms = $stmtL->fetchAll();

// Edit room prefill
$editRoom = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id=?");
    $stmt->execute([(int)$_GET['edit']]); $editRoom = $stmt->fetch();
}

renderHead('Room Management');
renderAdminNav('rooms', $_SESSION['user_id']);
?>

<div class="page-header">
  <div>
    <h4><i class="bi bi-door-open me-2"></i>Room Management</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Rooms</li>
      </ol>
    </nav>
  </div>
  <button class="btn btn-th-primary" data-bs-toggle="modal" data-bs-target="#roomModal">
    <i class="bi bi-plus-circle me-1"></i>Add Room
  </button>
</div>

<?php if ($message): ?>
  <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'danger' ?> alert-auto-dismiss alert-dismissible">
    <i class="bi bi-<?= $msgType==='success'?'check-circle':'x-circle' ?> me-2"></i><?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Assign Room Panel -->
<?php if ($assignStudent): ?>
<div class="th-card mb-4 border-start border-4 border-success">
  <div class="card-header bg-success text-white">
    <i class="bi bi-door-open me-2"></i>Assign Room to: <strong><?= htmlspecialchars($assignStudent['full_name']) ?></strong>
    (<?= htmlspecialchars($assignStudent['student_number']) ?>)
  </div>
  <div class="card-body">
    <form method="POST" class="row g-3">
      <input type="hidden" name="student_id" value="<?= $assignStudentId ?>">
      <div class="col-md-4">
        <label class="form-label fw-600">Check-in Date <span class="text-danger">*</span></label>
        <input type="date" name="check_in_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="col-12">
        <label class="form-label fw-600">Select Room <span class="text-danger">*</span></label>
        <div class="row g-3" id="roomPicker">
          <?php
          $avRooms = $pdo->query("SELECT * FROM vw_room_occupancy WHERE status NOT IN ('Full','Under Maintenance') ORDER BY building_name, room_number")->fetchAll();
          foreach ($avRooms as $ar):
          $pct = (int)$ar['occupancy_pct'];
          ?>
          <div class="col-md-3 col-6">
            <label class="room-card d-block" style="cursor:pointer">
              <input type="radio" name="room_id" value="<?= $ar['room_id'] ?>" class="d-none" required>
              <div class="fw-700 text-green"><?= htmlspecialchars($ar['building_name']) ?></div>
              <div class="small text-muted mb-1">Room <?= htmlspecialchars($ar['room_number']) ?> – Floor <?= $ar['floor'] ?></div>
              <div><span class="badge bg-secondary"><?= $ar['room_type'] ?></span></div>
              <div class="room-occupancy-bar mt-2">
                <div class="bar-fill <?= $pct>=75?'bar-warning':'' ?>" style="width:<?= min($pct,100) ?>%"></div>
              </div>
              <div class="d-flex justify-content-between mt-1">
                <small><?= $ar['current_occupants'] ?>/<?= $ar['capacity'] ?></small>
                <small class="fw-600 text-green">₱<?= number_format($ar['monthly_rate'],0) ?>/mo</small>
              </div>
            </label>
          </div>
          <?php endforeach; ?>
          <?php if (empty($avRooms)): ?>
            <div class="col-12"><div class="alert alert-warning">No available rooms found.</div></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-auto">
        <button type="submit" name="assign_room" value="1" class="btn btn-success">
          <i class="bi bi-check-circle me-1"></i>Confirm Assignment
        </button>
        <a href="rooms.php" class="btn btn-outline-secondary ms-2">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="th-card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-3">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search room / building…" value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-md-3">
        <select name="building" class="form-select form-select-sm">
          <option value="">All Buildings</option>
          <?php foreach ($buildings as $b): ?>
            <option value="<?= $b['building_id'] ?>" <?= $buildFilter===$b['building_id']?'selected':'' ?>><?= htmlspecialchars($b['building_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Statuses</option>
          <?php foreach(['Available','Full','Under Maintenance','Reserved'] as $s): ?>
            <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button class="btn btn-sm btn-th-primary px-3">Filter</button>
        <a href="rooms.php" class="btn btn-sm btn-outline-secondary px-3 ms-1">Reset</a>
      </div>
      <div class="col-auto ms-auto"><span class="text-muted small"><?= $total ?> room(s)</span></div>
    </form>
  </div>
</div>

<!-- Rooms Table -->
<div class="th-card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="th-table">
        <thead>
          <tr><th>#</th><th>Building</th><th>Room</th><th>Floor</th><th>Type</th><th>Capacity</th><th>Occupancy</th><th>Rate/mo</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($rooms)): ?>
            <tr><td colspan="10" class="text-center py-5 text-muted">No rooms found.</td></tr>
          <?php else: ?>
            <?php foreach ($rooms as $r):
              $pct  = $r['capacity'] > 0 ? round($r['current_occupants']/$r['capacity']*100) : 0;
              $color = $pct>=100?'bar-danger':($pct>=75?'bar-warning':'');
            ?>
            <tr>
              <td><?= $r['room_id'] ?></td>
              <td><?= htmlspecialchars($r['building_name']) ?></td>
              <td><strong><?= htmlspecialchars($r['room_number']) ?></strong></td>
              <td><?= $r['floor'] ?></td>
              <td><span class="badge bg-secondary"><?= $r['room_type'] ?></span></td>
              <td><?= $r['capacity'] ?></td>
              <td>
                <div class="room-occupancy-bar" style="width:80px">
                  <div class="bar-fill <?= $color ?>" style="width:<?= min($pct,100) ?>%"></div>
                </div>
                <small><?= $r['current_occupants'] ?>/<?= $r['capacity'] ?></small>
              </td>
              <td><strong>₱<?= number_format($r['monthly_rate'],2) ?></strong></td>
              <td><?= statusBadge($r['status']) ?></td>
              <td>
                <a href="?edit=<?= $r['room_id'] ?>" class="btn btn-sm btn-warning text-white px-2 py-1" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="room_id" value="<?= $r['room_id'] ?>">
                  <button type="submit" name="delete_room" value="1"
                    class="btn btn-sm btn-danger px-2 py-1 ms-1"
                    data-confirm="Delete this room? Only empty rooms can be deleted."
                    title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($total > $perPage): ?>
    <div class="d-flex justify-content-end p-3">
      <?= paginate($total, $page, $perPage, '?building='.$buildFilter.'&status='.urlencode($statusFilter).'&search='.urlencode($search)) ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Add/Edit Room Modal -->
<div class="modal fade" id="roomModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0">
      <div class="modal-header" style="background:var(--th-green);color:#fff">
        <h5 class="modal-title"><i class="bi bi-door-open me-2"></i><?= $editRoom ? 'Edit' : 'Add' ?> Room</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="room_id" value="<?= $editRoom['room_id'] ?? 0 ?>">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-600">Building <span class="text-danger">*</span></label>
              <select name="building_id" class="form-select" required>
                <option value="">Select Building</option>
                <?php foreach ($buildings as $b): ?>
                  <option value="<?= $b['building_id'] ?>" <?= ($editRoom && $editRoom['building_id']==$b['building_id'])?'selected':'' ?>><?= htmlspecialchars($b['building_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-600">Room Number <span class="text-danger">*</span></label>
              <input type="text" name="room_number" class="form-control" value="<?= htmlspecialchars($editRoom['room_number'] ?? '') ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-600">Floor <span class="text-danger">*</span></label>
              <input type="number" name="floor" class="form-control" min="1" max="20" value="<?= $editRoom['floor'] ?? 1 ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-600">Room Type <span class="text-danger">*</span></label>
              <select name="room_type" class="form-select" required>
                <?php foreach(['Single','Double','Triple','Quad','Suite'] as $rt): ?>
                  <option value="<?= $rt ?>" <?= ($editRoom && $editRoom['room_type']===$rt)?'selected':'' ?>><?= $rt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-600">Capacity <span class="text-danger">*</span></label>
              <input type="number" name="capacity" class="form-control" min="1" max="10" value="<?= $editRoom['capacity'] ?? 2 ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-600">Monthly Rate (₱) <span class="text-danger">*</span></label>
              <input type="number" name="monthly_rate" class="form-control" min="0" step="0.01" value="<?= $editRoom['monthly_rate'] ?? '' ?>" required>
            </div>
            <?php if ($editRoom): ?>
            <div class="col-md-4">
              <label class="form-label fw-600">Status</label>
              <select name="status" class="form-select">
                <?php foreach(['Available','Full','Under Maintenance','Reserved'] as $s): ?>
                  <option value="<?= $s ?>" <?= ($editRoom['status']===$s)?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
            <div class="col-12">
              <label class="form-label fw-600">Description</label>
              <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($editRoom['description'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="save_room" value="1" class="btn btn-th-primary">
            <i class="bi bi-save me-1"></i>Save Room
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if ($editRoom): ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    new bootstrap.Modal(document.getElementById('roomModal')).show();
  });
</script>
<?php endif; ?>

<!-- Room picker radio visual selection -->
<script>
document.querySelectorAll('#roomPicker .room-card').forEach(card => {
  card.addEventListener('click', () => {
    document.querySelectorAll('#roomPicker .room-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    card.querySelector('input[type=radio]').checked = true;
  });
});
</script>

<?php renderFooter(); ?>
