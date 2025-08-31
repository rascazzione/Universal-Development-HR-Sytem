<?php
/**
 * IDP Dashboard - Individual Development Plan Overview
 * Phase 4: Development Planning Interface
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/IDRManager.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';

requireAuth();

$pageTitle = 'IDP Dashboard';
$pageHeader = true;
$pageDescription = 'Track and manage your individual development activities and learning goals';

$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'] ?? 'employee';
$employeeId = (int)($_SESSION['employee_id'] ?? 0);

// Managers can view team IDPs, employees see own
$viewEmployeeId = (isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : $employeeId);
if (!canAccessEmployee($viewEmployeeId)) {
    setFlashMessage('error', 'Access denied to development plans.');
    redirect('/dashboard.php');
}

$idpManager = new IDRManager();
$periods = [];

$periodClass = new EvaluationPeriod();
try {
    $periods = $periodClass->getActivePeriods();
} catch (Exception $e) {
    error_log("IDP periods load error: " . $e->getMessage());
}

$idps = [];
try {
    $idps = $idpManager->getIDPs($viewEmployeeId);
} catch (Exception $e) {
    error_log("IDP dashboard error: " . $e->getMessage());
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
  <!-- Header -->
  <div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h3 mb-1">
          <i class="fas fa-graduation-cap me-2 text-secondary"></i>
          <?= ($viewEmployeeId === $employeeId) ? 'My Development Plan' : 'Team Development Plans' ?>
        </h1>
        <p class="text-muted mb-0">Track learning activities and career growth</p>
      </div>
      <div class="d-flex gap-2">
        <a href="/idp/create.php" class="btn btn-primary">
          <i class="fas fa-plus me-1"></i>New Development Activity
        </a>
        <select id="periodFilter" class="form-select" style="width: auto;">
          <option value="">All Periods</option>
          <?php foreach ($periods as $period): ?>
            <option value="<?= $period['period_id'] ?>"><?= htmlspecialchars($period['period_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Overview Cards -->
  <div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card">
        <div class="card-body text-center">
          <h5 class="mb-1" id="totalActivities"><?= count($idps) ?></h5>
          <div class="text-muted">Activities Defined</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-12 mb-3">
      <div class="card">
        <div class="card-body text-center">
          <h5 class="mb-1" id="completedActivities">0</h5>
          <div class="text-muted">Completed</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card">
        <div class="card-body text-center">
          <h5 class="mb-1" id="inProgressActivities">0</h5>
          <div class="text-muted">In Progress</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card">
        <div class="card-body text-center">
          <h5 class="mb-1" id="skillCount">0</h5>
          <div class="text-muted">Skills Targeted</div>
        </div>
      </div>
    </div>
  </div>

  <!-- IDP Timeline -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Development Timeline</h5>
          <button class="btn btn-outline-secondary btn-sm" id="refreshIDP"><i class="fas fa-sync-alt"></i></button>
        </div>
        <div class="card-body">
          <div id="idpTimeline">
            <p class="text-muted">
              <?= empty($idps) ? 'Add your first learning objective.' : 'Loading development plans...' ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Progress Chart -->
  <div class="row mb-4">
    <div class="col-lg-6 mb-4">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Progress Overview</h6>
        </div>
        <div class="card-body">
          <canvas id="idpStatusChart" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-6 mb-4">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-skill me-2"></i>Skills Development</h6>
        </div>
        <div class="card-body">
          <canvas id="idpSkillsChart" height="200"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Help Section -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>How This Works</h5>
        </div>
        <div class="card-body">
          <ul class="list-unstyled mb-0 small text-muted">
            <li><i class="fas fa-check me-2"></i>Set SMART goals for skills and competencies.</li>
            <li><i class="fas fa-check me-2"></i>Add learning activities such as courses, certifications or mentorship.</li>
            <li><i class="fas fa-check me-2"></i>Track progress regularly and update status.</li>
            <li><i class="fas fa-check me-2"></i>All activities are linked to your performance and growth conversations.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
window.viewEmployeeId = <?= json_encode($viewEmployeeId) ?>;
window.userRole = <?= json_encode($userRole) ?>;
</script>
<script src="/assets/js/idp-dashboard.js"></script>
<script src="/assets/js/app.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>