<?php
/**
 * Create New Objective & Key Results (OKR)
 * Phase 4: OKR Creation Interface
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/OKRManager.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';

// Only employees (and managers for team) can create OKRs
requireRole(['employee', 'manager']);

$pageTitle = 'Create New OKR';
$pageHeader = true;
$pageDescription = 'Define ambitious objectives and measurable key results';

$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'] ?? 'employee';
$employeeId = (int)($_SESSION['employee_id'] ?? 0);

// Allow managers to create OKRs for team members
$targetEmployeeId = (isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : $employeeId);
if (!canAccessEmployee($targetEmployeeId)) {
    setFlashMessage('error', 'Access denied.');
    redirect('/okr/dashboard.php');
}

$periodClass = new EvaluationPeriod();
try {
    $periods = $periodClass->getActivePeriods();
} catch (Exception $e) {
    error_log("OKR periods load error: " . $e->getMessage());
    $periods = [];
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-bullseye me-2"></i>Create New Objective</h5>
        <?php if ($employeeId === $targetEmployeeId): ?>
          <span class="badge bg-primary">For You</span>
        <?php else: ?>
          <span class="badge bg-warning">Team Member</span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <form id="okrForm" class="needs-validation" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
          <input type="hidden" name="employee_id" value="<?php echo $targetEmployeeId; ?>">

          <div class="mb-3">
            <label class="form-label">Evaluation Period *</label>
            <select name="period_id" class="form-select" required>
              <option value="">Select Period</option>
              <?php foreach ($periods as $period): ?>
                <option value="<?php echo $period['period_id']; ?>"><?= htmlspecialchars($period['period_name']) ?> (<?= formatDate($period['start_date']) ?> - <?= formatDate($period['end_date']) ?>)</option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Period is necessary.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Objective *</label>
            <input name="objective_title" class="form-control" required maxlength="150">
            <div class="invalid-feedback">Objective title is required.</div>
            <div class="form-text">Make it SMART: Specific, Measurable, Achievable, Relevant, Time-bound.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Objective Description *</label>
            <textarea name="objective_description" class="form-control" rows="3" required></textarea>
            <div class="invalid-feedback">Objective description is required.</div>
            <div class="form-text">Explain <em>why</em> this objective is crucial.</div>
          </div>

          <!-- Key Results -->
          <div class="mb-4">
            <label class="form-label">Key Results (2–5) *</label>
            <div id="keyResultsList">
              <!-- JS will populate dynamically -->
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="addKeyResult">
              <i class="fas fa-plus me-1"></i>Add Key Result
            </button>
          </div>

          <div class="mb-3">
            <label class="form-label">Tags (optional)</label>
            <input name="tags" class="form-control">
            <div class="form-text">Comma-separated tags.</div>
          </div>

          <hr>
          <div class="d-flex justify-content-between">
            <a href="/okr/dashboard.php" class="btn btn-secondary">
              <i class="fas fa-arrow-left me-2"></i>Cancel
            </a>
            <div>
              <button type="button" id="saveDraftBtn" class="btn btn-outline-secondary me-2">
                <i class="fas fa-save me-1"></i>Save Draft
              </button>
              <button type="submit" class="btn btn-success" id="createOKR">
                <i class="fas fa-check me-1"></i>Create Objective
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Templates -->
<template id="key-result-template">
  <div class="key-result-item mb-3 border rounded p-3 position-relative">
    <div class="row">
      <div class="col-lg-10">
        <div class="mb-2">
          <label class="form-label">Key Result Title *</label>
          <input class="form-control kr-title" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Key Result Description</label>
          <textarea class="form-control kr-description" rows="2"></textarea>
        </div>
        <div class="row mb-2">
          <div class="col-md-6 mb-2">
            <label class="form-label">Target Value * (0-100)</label>
            <input type="number" class="form-control kr-target" min="0" max="100" required>
          </div>
          <div class="col-md-6 mb-2">
            <label class="form-label">Current Progress</label>
            <input type="number" class="form-control kr-current" min="0" max="100" value="0">
          </div>
        </div>
      </div>
      <div class="col-lg-2 text-end">
        <button type="button" class="btn btn-sm btn-outline-danger remove-kr">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
(function () {
  const form = document.getElementById('okrForm');
  const saveDraftBtn = document.getElementById('saveDraftBtn');
  const createOKRBtn = document.getElementById('createOKR');
  const keyResultsList = document.getElementById('keyResultsList');
  const keyResultTemplate = document.getElementById('key-result-template');
  let krCount = 0;

  // Initialize listeners
  document.getElementById('addKeyResult').addEventListener('click', addKeyResult);
  form.addEventListener('submit', submitOKR);

  function addKeyResult() {
    krCount++;
    const clone = keyResultTemplate.content.cloneNode(true);
    const id = 'kr-' + krCount;
    clone.querySelector('.key-result-item').setAttribute('data-id', id);
    keyResultsList.appendChild(clone);
    keyResultsList.querySelector('.remove-kr').addEventListener('click', function () {
      keyResultsList.removeChild(this.closest('.key-result-item'));
    });
  }

  // auto-add 2 empty key results
  addKeyResult(); addKeyResult();

  async function submitOKR(e) {
    e.preventDefault();
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }
    createOKRBtn.disabled = true;

    const fd = new FormData(form);
    const keyResultData = [];
    keyResultsList.querySelectorAll('.key-result-item').forEach(kr => {
      keyResultData.push({
        title: kr.querySelector('.kr-title').value,
        description: kr.querySelector('.kr-description').value,
        target: parseInt(kr.querySelector('.kr-target').value),
        current: parseInt(kr.querySelector('.kr-current').value),
      });
    });
    fd.append('key_results', JSON.stringify(keyResultData));

    try {
      const res = await fetch('/api/okr/create.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      });
      const result = await res.json();
      if (result.success) {
        window.showNotification?.('success', result.message || 'OKR created successfully.');
        window.location.href = '/okr/dashboard.php';
      } else {
        window.showNotification?.('error', result.message || 'Save failed');
      }
    } catch (err) {
      alert('Error saving OKR.');
    } finally {
      createOKRBtn.disabled = false;
    }
  }

  saveDraftBtn.addEventListener('click', async () => {
    const fd = new FormData(form);
    fd.append('status', 'draft');
    // simple draft save - reuse existing logic
    submitOKR.call({type:'click'});
  });
})();
</script>

<script src="/assets/js/app.js"></script>
<script src="/assets/js/okr-dashboard.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>