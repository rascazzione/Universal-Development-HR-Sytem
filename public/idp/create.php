<?php
/**
 * Create New Individual Development Plan (IDP) Activity
 * Phase 4: Development Plan Creation Interface
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/IDRManager.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';

// Allow employees and managers to create IDP activities
requireRole(['employee', 'manager']);

$pageTitle = 'Create New Development Activity';
$pageHeader = true;
$pageDescription = 'Record learning goals and plan development actions';

$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'] ?? 'employee';
$employeeId = (int)($_SESSION['employee_id'] ?? 0);

// Allow managers to create activities for team members
$targetEmployeeId = (isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : $employeeId);
if (!canAccessEmployee($targetEmployeeId)) {
    setFlashMessage('error', 'Access denied to create IDP activity.');
    redirect('/idp/dashboard.php');
}

$periodClass = new EvaluationPeriod();
try {
    $periods = $periodClass->getActivePeriods();
} catch (Exception $e) {
    error_log("IDP periods load error: " . $e->getMessage());
    $periods = [];
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>New Development Activity</h5>
        <?php if ($employeeId === $targetEmployeeId): ?>
          <span class="badge bg-primary">For You</span>
        <?php else: ?>
          <span class="badge bg-warning">Team Member</span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <form id="idpForm" class="needs-validation" novalidate>
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
            <div class="invalid-feedback">Evaluation period is required.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Development Goal/Activity Title *</label>
            <input name="activity_title" class="form-control" required maxlength="200">
            <div class="invalid-feedback">Title is required.</div>
            <div class="form-text">Brief, specific title of learning or development intent (e.g., “Complete AWS Solution Architect Certification”)</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Development Category *</label>
            <select name="development_category" class="form-select" required>
              <option value="">Select Category</option>
              <option value="skill_enhancement">Skill Enhancement</option>
              <option value="certification">Certification</option>
              <option value="course">Course / Training</option>
              <option value="workshop">Workshop / Seminar</option>
              <option value="mentorship">Mentorship</option>
              <option value="project">Special Project</option>
              <option value="reading">Book / Reading Focus</option>
              <option value="other">Other</option>
            </select>
            <div class="invalid-feedback">Development category is required.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Target Skills or Competencies *</label>
            <input name="target_competencies" class="form-control" required>
            <div class="invalid-feedback">At least one skill or competency is required.</div>
            <div class="form-text">Comma-separated list of skills/competencies targeted.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Detailed Description / Action Plan *</label>
            <textarea name="description" class="form-control" rows="4" required></textarea>
            <div class="invalid-feedback">Description is required.</div>
            <div class="form-text">Steps, resources, timeline, and expected outcomes.</div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Planned Start Date</label>
              <input type="date" name="start_date" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Planned Completion Date</label>
              <input type="date" name="end_date" class="form-control" required>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Progress (%) *</label>
              <input type="number" name="progress" min="0" max="100" value="0" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Priority</label>
              <select name="priority" class="form-select">
                <option value="high">High</option>
                <option value="medium" selected>Medium</option>
                <option value="low">Low</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Resources Required / Notes</label>
            <textarea name="resources_notes" class="form-control" rows="2"></textarea>
            <div class="form-text">Budget, time, tools, or stakeholder support required.</div>
          </div>

          <hr>
          <div class="d-flex justify-content-between">
            <a href="/idp/dashboard.php" class="btn btn-secondary">
              <i class="fas fa-arrow-left me-2"></i>Cancel
            </a>
            <div>
              <button type="button" id="saveDraftBtn" class="btn btn-outline-secondary me-2">
                <i class="fas fa-save me-1"></i>Save Draft
              </button>
              <button type="submit" class="btn btn-success" id="createIDP">
                <i class="fas fa-check me-1"></i>Save Development Activity
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const form = document.getElementById('idpForm');
  const createIDPBtn = document.getElementById('createIDP');
  const saveDraftBtn = document.getElementById('saveDraftBtn');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }
    createIDPBtn.disabled = true;

    const fd = new FormData(form);
    try {
      const res = await fetch('/api/idp/create.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      });
      const result = await res.json();
      if (result.success) {
        window.showNotification?.('success', result.message || 'Development activity recorded.');
        window.location.href = '/idp/dashboard.php';
      } else {
        window.showNotification?.('error', result.message || 'Save failed');
      }
    } catch (err) {
      alert('Error saving development activity.');
    } finally {
      createIDPBtn.disabled = false;
    }
  });

  saveDraftBtn.addEventListener('click', async () => {
    // re-use form submit logic
    form.querySelector('[name="status"]')?.value === 'draft';
    form.dispatchEvent(new Event('submit'));
  });
})();
</script>

<script src="/assets/js/app.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>