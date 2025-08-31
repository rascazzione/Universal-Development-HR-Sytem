<?php
/**
 * Create Self-Assessment Page
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';
require_once __DIR__ . '/../../classes/SelfAssessmentManager.php';

// Only employees may create their own self-assessments
requireRole(['employee']);

$pageTitle = 'Create Self-Assessment';
$pageHeader = true;
$pageDescription = 'Complete your self-assessment for a selected evaluation period';

$periodClass = new EvaluationPeriod();
$periods = $periodClass->getActivePeriods();

$errors = [];
$success = false;

include __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-pen-square me-2"></i>Create Self-Assessment</h5>
      </div>
      <div class="card-body">
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if (empty($periods)): ?>
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>No active evaluation periods available. Contact HR.
          </div>
        <?php else: ?>
          <form id="saCreateForm" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <div class="mb-3">
              <label for="period_id" class="form-label"><i class="fas fa-calendar-alt me-1"></i>Evaluation Period *</label>
              <select id="period_id" name="period_id" class="form-select" required>
                <option value="">Select Period</option>
                <?php foreach ($periods as $p): ?>
                  <option value="<?php echo $p['period_id']; ?>"><?php echo htmlspecialchars($p['period_name']); ?> (<?php echo formatDate($p['start_date']); ?> - <?php echo formatDate($p['end_date']); ?>)</option>
                <?php endforeach; ?>
              </select>
              <div class="invalid-feedback">Please select an evaluation period.</div>
            </div>

            <div id="dimensionsSection" class="mb-3">
              <h6>Self Ratings (1-5)</h6>
              <p class="text-muted small">Rate yourself on each dimension and add comments where helpful.</p>

              <?php
              // Default dimensions - mirror server-side dimensions if available
              $dimensions = ['communication', 'collaboration', 'delivery', 'quality'];
              foreach ($dimensions as $dim):
              ?>
              <div class="mb-3 dimension-block p-3 border rounded">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <label class="form-label mb-0"><?php echo ucfirst($dim); ?></label>
                  <div>
                    <select name="rating_<?php echo $dim; ?>" class="form-select form-select-sm d-inline-block" style="width:90px;" required>
                      <option value="">Rate</option>
                      <?php for ($i=1;$i<=5;$i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                      <?php endfor; ?>
                    </select>
                  </div>
                </div>
                <textarea name="comment_<?php echo $dim; ?>" class="form-control" rows="2" placeholder="Add context or examples (optional)"></textarea>
              </div>
              <?php endforeach; ?>
            </div>

            <div class="mb-3">
              <label for="additional_comments" class="form-label">Additional Comments</label>
              <textarea id="additional_comments" name="additional_comments" class="form-control" rows="4" placeholder="Optional summary or development notes"></textarea>
            </div>

            <div class="d-flex justify-content-between">
              <a href="/self-assessment/dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Cancel
              </a>
              <div>
                <button type="button" id="saveDraftBtn" class="btn btn-outline-secondary me-2">
                  <i class="fas fa-save me-1"></i>Save Draft
                </button>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                  <i class="fas fa-paper-plane me-1"></i>Submit Self-Assessment
                </button>
              </div>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  'use strict';

  const form = document.getElementById('saCreateForm');
  const saveDraftBtn = document.getElementById('saveDraftBtn');
  const submitBtn = document.getElementById('submitBtn');

  // Bootstrap validation
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }
    submitBtn.disabled = true;
    await submitForm({ action: 'submit' });
    submitBtn.disabled = false;
  });

  saveDraftBtn.addEventListener('click', async function() {
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      // allow saving drafts even if some ratings missing - collect what exists
    }
    saveDraftBtn.disabled = true;
    await submitForm({ action: 'draft' });
    saveDraftBtn.disabled = false;
  });

  function collectFormData(actionType) {
    const fd = new FormData();
    fd.append('action', actionType);
    fd.append('csrf_token', form.querySelector('input[name="csrf_token"]').value);
    fd.append('period_id', form.querySelector('select[name="period_id"]').value);

    // collect dynamic rating fields
    document.querySelectorAll('.dimension-block').forEach(block => {
      const label = block.querySelector('.form-label').textContent.trim().toLowerCase();
      const ratingEl = block.querySelector('select');
      const commentEl = block.querySelector('textarea');
      if (ratingEl && ratingEl.value) fd.append('ratings['+label+']', ratingEl.value);
      if (commentEl && commentEl.value) fd.append('comments['+label+']', commentEl.value);
    });

    const addComments = document.getElementById('additional_comments');
    if (addComments && addComments.value) fd.append('additional_comments', addComments.value);

    return fd;
  }

  async function submitForm(opts = { action: 'submit' }) {
    const fd = collectFormData(opts.action);
    try {
      const res = await fetch('/api/self-assessment/create.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      });
      const result = await res.json();
      if (result.success) {
        // use notification system if available
        if (window.showNotification) window.showNotification('success', result.message || 'Saved successfully.');
        // redirect to view page for the newly created assessment
        if (result.assessment_id) {
          window.location.href = '/self-assessment/view.php?id=' + result.assessment_id;
        } else {
          // fallback refresh dashboard
          window.location.href = '/self-assessment/dashboard.php';
        }
      } else {
        const errMsg = result.message || 'Failed to save self-assessment';
        if (window.showNotification) window.showNotification('error', errMsg);
        else alert(errMsg);
      }
    } catch (err) {
      console.error('Submit error', err);
      alert('An error occurred while submitting. Please try again.');
    }
  }

})();
</script>

<script src="/assets/js/app.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>