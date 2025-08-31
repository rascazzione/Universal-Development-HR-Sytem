<?php
/**
 * Self-Assessment Dashboard - Overview and Status
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';
require_once __DIR__ . '/../../classes/SelfAssessmentManager.php';

requireAuth();

$pageTitle = 'Self-Assessment';
$pageHeader = true;
$pageDescription = 'Create and track your self-assessments';

$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'] ?? 'employee';
$employeeId = $_SESSION['employee_id'] ?? null;

$periodClass = new EvaluationPeriod();
$periods = [];
try {
    $periods = $periodClass->getActivePeriods();
} catch (Exception $e) {
    error_log("Self-assessment periods load error: ".$e->getMessage());
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
  <div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h3 mb-1"><i class="fas fa-file-alt me-2 text-primary"></i>Self-Assessments</h1>
        <p class="text-muted mb-0">Overview of your self-assessments and their status</p>
      </div>
      <div class="d-flex gap-2">
        <?php if ($userRole === 'employee'): ?>
        <a href="/self-assessment/create.php" class="btn btn-primary">
          <i class="fas fa-plus me-1"></i>New Self-Assessment
        </a>
        <?php endif; ?>
        <select id="periodFilter" class="form-select" style="width:auto;">
          <option value="">All Periods</option>
          <?php foreach ($periods as $period): ?>
          <option value="<?php echo $period['period_id']; ?>"><?php echo htmlspecialchars($period['period_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-md-4 mb-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-2">Pending</h6>
          <div class="display-6" id="saPendingCount">0</div>
          <small class="text-muted">Assessments awaiting your completion</small>
        </div>
      </div>
    </div>

    <div class="col-md-4 mb-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-2">Submitted</h6>
          <div class="display-6" id="saSubmittedCount">0</div>
          <small class="text-muted">Assessments submitted for manager review</small>
        </div>
      </div>
    </div>

    <div class="col-md-4 mb-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-2">Completed</h6>
          <div class="display-6" id="saCompletedCount">0</div>
          <small class="text-muted">Finalized self-assessments</small>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-list me-2"></i>Your Self-Assessments</h5>
          <div>
            <button class="btn btn-sm btn-outline-secondary" id="refreshAssessments"><i class="fas fa-sync-alt"></i></button>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped" id="saTable">
              <thead>
                <tr>
                  <th>Period</th>
                  <th>Created</th>
                  <th>Status</th>
                  <th>Last Updated</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Rating Overview</h6>
        </div>
        <div class="card-body">
          <canvas id="saRatingsChart" height="200"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Feedback Summary</h6>
        </div>
        <div class="card-body">
          <div id="saFeedbackSummary">No feedback yet.</div>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
window.userRole = '<?php echo $userRole; ?>';
window.employeeId = <?php echo $employeeId ?: 'null'; ?>;

async function fetchAssessments() {
  const periodId = document.getElementById('periodFilter').value;
  const url = '/api/self-assessment/get.php?employee_id=' + (window.employeeId || '') + (periodId ? '&period_id='+periodId : '');
  try {
    const res = await fetch(url);
    const data = await res.json();
    renderAssessments(data);
  } catch (e) {
    console.error('Failed to load self-assessments', e);
  }
}

function renderAssessments(data) {
  const tbody = document.querySelector('#saTable tbody');
  tbody.innerHTML = '';
  if (!data || !data.assessments || data.assessments.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No self-assessments found.</td></tr>';
    document.getElementById('saPendingCount').textContent = 0;
    document.getElementById('saSubmittedCount').textContent = 0;
    document.getElementById('saCompletedCount').textContent = 0;
    return;
  }

  let pending = 0, submitted = 0, completed = 0;
  data.assessments.forEach(sa => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${sa.period_name || ''}</td>
      <td>${sa.created_at || ''}</td>
      <td>${sa.status || ''}</td>
      <td>${sa.updated_at || ''}</td>
      <td>
        <a href="/self-assessment/view.php?id=${sa.id}" class="btn btn-sm btn-outline-primary me-1">View</a>
        ${sa.status === 'draft' ? `<a href="/self-assessment/view.php?id=${sa.id}&edit=1" class="btn btn-sm btn-primary">Edit</a>` : ''}
        ${sa.status === 'submitted' ? `<a href="/self-assessment/comparison.php?id=${sa.id}" class="btn btn-sm btn-outline-success">Compare</a>` : ''}
      </td>`;
    tbody.appendChild(tr);
    if (sa.status === 'draft') pending++;
    else if (sa.status === 'submitted') submitted++;
    else if (sa.status === 'completed') completed++;
  });

  document.getElementById('saPendingCount').textContent = pending;
  document.getElementById('saSubmittedCount').textContent = submitted;
  document.getElementById('saCompletedCount').textContent = completed;

  // TODO: update chart and feedback summary
}

document.getElementById('periodFilter').addEventListener('change', fetchAssessments);
document.getElementById('refreshAssessments').addEventListener('click', fetchAssessments);

// initial load
fetchAssessments();
</script>

<script src="/assets/js/app.js"></script>
<script src="/assets/js/dashboard-employee.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>