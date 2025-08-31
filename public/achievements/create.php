<?php
/**
 * Create Achievement Entry
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/AchievementJournal.php';

requireAuth();

$pageTitle = 'Create Achievement';
$pageHeader = true;
$pageDescription = 'Log a new achievement or development entry';

$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'] ?? 'employee';
$employeeId = $_SESSION['employee_id'] ?? null;

include __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>New Achievement</h5>
      </div>
      <div class="card-body">
        <form id="achievementForm" class="needs-validation" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

          <div class="mb-3">
            <label class="form-label">Title *</label>
            <input name="title" class="form-control" required>
            <div class="invalid-feedback">Please enter a title.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Dimension</label>
            <select name="dimension" class="form-select">
              <option value="">General</option>
              <option value="communication">Communication</option>
              <option value="collaboration">Collaboration</option>
              <option value="delivery">Delivery</option>
              <option value="quality">Quality</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Description *</label>
            <textarea name="content" class="form-control" rows="6" required></textarea>
            <div class="invalid-feedback">Please provide a description of the achievement.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Attachments (optional)</label>
            <input type="file" name="attachments[]" class="form-control" multiple>
          </div>

          <div class="d-flex justify-content-between">
            <a href="/achievements/journal.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Cancel</a>
            <div>
              <button type="button" id="saveDraft" class="btn btn-outline-secondary me-2">Save Draft</button>
              <button type="submit" id="submitAchievement" class="btn btn-primary">Create</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const form = document.getElementById('achievementForm');
  const saveDraft = document.getElementById('saveDraft');
  const submitBtn = document.getElementById('submitAchievement');

  form.addEventListener('submit', async function(e){
    e.preventDefault();
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }
    submitBtn.disabled = true;
    await submit('publish');
    submitBtn.disabled = false;
  });

  saveDraft.addEventListener('click', async function(){
    saveDraft.disabled = true;
    await submit('draft');
    saveDraft.disabled = false;
  });

  async function submit(action) {
    const fd = new FormData(form);
    fd.append('action', action);
    try {
      const res = await fetch('/api/achievements/create.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      });
      const result = await res.json();
      if (result.success) {
        if (window.showNotification) window.showNotification('success', result.message || 'Achievement saved.');
        window.location.href = '/achievements/view.php?id=' + (result.entry_id || '');
      } else {
        if (window.showNotification) window.showNotification('error', result.message || 'Save failed');
        else alert(result.message || 'Save failed');
      }
    } catch (err) {
      console.error(err);
      alert('Error saving achievement.');
    }
  }
})();
</script>

<script src="/assets/js/app.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>