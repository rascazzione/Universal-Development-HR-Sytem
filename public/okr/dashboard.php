<?php
/**
 * OKR Dashboard - Objective & Key Results Overview
 * Phase 4: OKR Management Interface
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/OKRManager.php';

requireAuth();

$pageTitle = 'OKR Dashboard';
$pageHeader = true;
$pageDescription = 'Manage Objectives and Key Results for yourself and your team';

// Initialize classes
$okrManager = new OKR();
$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'] ?? 'employee';
$employeeId = (int)($_SESSION['employee_id'] ?? 0);

// Determine scope: employee sees own OKRs, manager sees team OKRs
$viewEmployeeId = ($_GET['employee_id'] ?? $employeeId) ?: $employeeId;

// Validate access via auth.php helper
if (!canAccessEmployee($viewEmployeeId)) {
    setFlashMessage('error', 'You do not have permission to view these OKRs.');
    redirect('/dashboard.php');
}

// Get filter
$periodId = isset($_GET['period_id']) ? (int)$_GET['period_id'] : null;
try {
    $okrs = $okrManager->getObjectives($viewEmployeeId, ['period_id' => $periodId]);
} catch (Exception $e) {
    error_log("OKR dashboard error: " . $e->getMessage());
    $okrs = [];
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
  <!-- Header -->
  <div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h3 mb-1">
          <i class="fas fa-bullseye me-2 text-info"></i>
          OKR Dashboard - <?= ($viewEmployeeId == $_SESSION['employee_id']) ? 'My OKRs' : 'Team OKRs' ?>
        </h1>
        <p class="text-muted mb-0">Track objectives, key results, and progress</p>
      </div>
      <div class="d-flex gap-2">
        <a href="/okr/create.php" class="btn btn-primary">
          <i class="fas fa-plus me-1"></i>New Objective
        </a>
        <select id="periodFilter" class="form-select" style="width: auto;">
          <option value="">All Periods</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Overview Cards -->
  <div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card">
        <div class="card-body text-center">
          <h5 class="mb-1" id="totalObjectives"><?= count($okrs) ?></h5>
          <div class="text-muted">Active Objectives</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card">
        <div class="card-body text-center">
          <h5 class="mb-1" id="completedKRs">0</h5>
          <div class="text-muted">Completed Results</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card">
        <div class="card-body text-center">
          <h5 class="mb-1" id="avgProgress">0%</h5>
          <div class="text-muted">Avg Progress</div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card">
        <div class="card-body text-center">
          <h5 class="mb-1" id="dueSoon">0</h5>
          <div class="text-muted">Due Soon</div>
        </div>
      </div>
    </div>
  </div>

  <!-- OKR Timeline -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Objectives & Key Results</h5>
          <button class="btn btn-outline-secondary btn-sm" id="refreshOKR"><i class="fas fa-sync-alt"></i></button>
        </div>
        <div class="card-body">
          <div id="okrTimeline">
            <p class="text-muted">
              <?= empty($okrs) ? 'No OKRs found. <a href="/okr/create.php">Create your first!</a>' : 'Loading OKRs...' ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- OKR Chart -->
  <div class="row mb-4">
    <div class="col-lg-6 mb-4">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Progress Overview</h6>
        </div>
        <div class="card-body">
          <canvas id="okrStatusChart" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-6 mb-4">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-tasks me-2"></i>Completion Rate</h6>
        </div>
        <div class="card-body">
          <canvas id="okrCompletionChart" height="200"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Optional Summary -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>How This Works</h5>
        </div>
        <div class="card-body">
          <ul class="list-unstyled mb-0 small text-muted">
            <li><i class="fas fa-check me-2"></i><strong>Objectives</strong> define ambitious goals.</li>
            <li><i class="fas fa-check me-2"></i><strong>Key Results</strong> are measurable & time-bound outcomes.</li>
            <li><i class="fas fa-check me-2"></i>Update progress frequently to stay aligned.</li>
            <li><i class="fas fa-check me-2"></i>All OKRs here automatically appear in your performance reviews.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- OKR JavaScript -->
<script>
window.viewEmployeeId = <?= json_encode($viewEmployeeId) ?>;
window.userRole = <?= json_encode($userRole) ?>;
</script>
<script src="/assets/js/okr-dashboard.js"></script>
<script src="/assets/js/app.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>