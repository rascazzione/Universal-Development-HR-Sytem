<?php
/**
 * View / Edit Self-Assessment
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/SelfAssessmentManager.php';
require_once __DIR__ . '/../../classes/EvaluationPeriod.php';

requireAuth();

$pageTitle = 'View Self-Assessment';
$pageHeader = true;
$pageDescription = 'View or edit your self-assessment';

$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'] ?? 'employee';
$employeeId = $_SESSION['employee_id'] ?? null;

$assessmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editMode = isset($_GET['edit']) && $_GET['edit'] == '1';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
  <div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h3 mb-1"><i class="fas fa-file-alt me-2 text-primary"></i>Self-Assessment Details</h1>
        <p class="text-muted mb-0">Review your self-assessment and make edits if permitted</p>
      </div>
      <div>
        <a href="/self-assessment/dashboard.php" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i>Back
        </a>
      </div>
    </div>
  </div>

  <div id="saContainer" class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-file-lines me-2"></i>Assessment</h5>
          <div id="saActions"></div>
        </div>
        <div class="card-body" id="saBody">
          <div class="text-center text-muted py-4">Loading assessment...</div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <!-- Sidebar: status, timeline, manager comments -->
      <div class="card mb-3">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Status</h6>
        </div>
        <div class="card-body" id="saStatus">
          <p class="text-muted">Loading...</p>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Manager Feedback</h6>
        </div>
        <div class="card-body" id="saManagerFeedback">
          <p class="text-muted">Not available</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
window.assessmentId = <?php echo $assessmentId ?: 'null'; ?>;
window.userRole = '<?php echo $userRole; ?>';
window.employeeId = <?php echo $employeeId ?: 'null'; ?>;
const editModeRequested = <?php echo $editMode ? 'true' : 'false'; ?>;

async function loadAssessment() {
  if (!window.assessmentId) {
    document.getElementById('saBody').innerHTML = '<div class="alert alert-warning">No assessment specified.</div>';
    return;
  }
  try {
    const res = await fetch('/api/self-assessment/get.php?id=' + window.assessmentId, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data || !data.assessment) {
      document.getElementById('saBody').innerHTML = '<div class="alert alert-danger">Assessment not found.</div>';
      return;
    }
    renderAssessment(data.assessment);
  } catch (err) {
    console.error(err);
    document.getElementById('saBody').innerHTML = '<div class="alert alert-danger">Failed to load assessment.</div>';
  }
}

function renderAssessment(sa) {
  const canEdit = (window.userRole === 'employee' && sa.employee_id == window.employeeId && sa.status === 'draft') || editModeRequested && window.userRole === 'employee' && sa.employee_id == window.employeeId;
  const isManagerView = window.userRole === 'manager';

  // Actions
  const actionsEl = document.getElementById('saActions');
  actionsEl.innerHTML = '';
  if (canEdit) {
    actionsEl.innerHTML = `
      <a href="/self-assessment/view.php?id=${sa.id}&edit=1" class="btn btn-sm btn-outline-primary me-2">Edit</a>
      <button id="submitSaBtn" class="btn btn-sm btn-primary">Submit</button>
    `;
  } else if (sa.status === 'submitted' && isManagerView) {
    actionsEl.innerHTML = `<button id="managerReviewBtn" class="btn btn-sm btn-success">Review</button>`;
  }

  // Main body
  const bodyEl = document.getElementById('saBody');
  const periodHtml = sa.period_name ? `<small class="text-muted">${sa.period_name}</small>` : '';
  let html = `<div class="mb-3"><strong>Period:</strong> ${periodHtml}</div>`;

  html += `<form id="saViewForm" class="${canEdit ? 'needs-validation' : ''}" ${canEdit ? 'novalidate' : 'disabled'}>`;
  html += `<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">`;

  if (sa.ratings && Object.keys(sa.ratings).length) {
    for (const [dim, rating] of Object.entries(sa.ratings)) {
      const comment = (sa.comments && sa.comments[dim]) ? sa.comments[dim] : '';
      html += `
        <div class="mb-3 p-3 border rounded dimension-block">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label mb-0 text-capitalize">${dim.replace('_',' ')}</label>
            ${canEdit ? `
            <select name="rating_${dim}" class="form-select form-select-sm" style="width:90px;" required>
              <option value="">Rate</option>
              ${[1,2,3,4,5].map(i => `<option value="${i}" ${i == rating ? 'selected' : ''}>${i}</option>`).join('')}
            </select>` : `<div><strong>${rating}/5</strong></div>`}
          </div>
          ${canEdit ? `<textarea name="comment_${dim}" class="form-control" rows="2">${comment}</textarea>` : `<p class="mb-0 small">${escapeHtml(comment) || '<span class="text-muted">No comment</span>'}</p>`}
        </div>
      `;
    }
  } else {
    html += `<div class="alert alert-info">No rating details available.</div>`;
  }

  html += `<div class="mb-3"><label class="form-label">Additional Comments</label>${canEdit ? `<textarea name="additional_comments" class="form-control" rows="4">${sa.additional_comments || ''}</textarea>` : `<p class="small">${escapeHtml(sa.additional_comments) || '<span class="text-muted">None</span>'}</p>`}</div>`;

  html += `</form>`;
  bodyEl.innerHTML = html;

  // Sidebar status
  const statusEl = document.getElementById('saStatus');
  statusEl.innerHTML = `<div><span class="badge bg-${sa.status === 'draft' ? 'secondary' : (sa.status === 'submitted' ? 'warning' : 'success')}">${sa.status}</span></div>
                        <small class="text-muted">Created: ${sa.created_at || 'N/A'}</small><br>
                        <small class="text-muted">Updated: ${sa.updated_at || 'N/A'}</small>`;

  // Manager feedback area
  const mgrEl = document.getElementById('saManagerFeedback');
  if (sa.manager_feedback) {
    mgrEl.innerHTML = `<div class="small">${escapeHtml(sa.manager_feedback)}</div>`;
  } else {
    mgrEl.innerHTML = `<div class="text-muted small">No manager feedback yet.</div>`;
  }

  // Attach event handlers
  if (canEdit) {
    const submitBtn = document.getElementById('submitSaBtn');
    if (submitBtn) {
      submitBtn.addEventListener('click', async function() {
        submitBtn.disabled = true;
        await submitUpdate(sa.id, 'submit');
        submitBtn.disabled = false;
      });
    }

    // enable form validation and autosave option
    const viewForm = document.getElementById('saViewForm');
    viewForm.addEventListener('submit', function(e) {
      e.preventDefault();
    });
  }

  if (document.getElementById('managerReviewBtn')) {
    document.getElementById('managerReviewBtn').addEventListener('click', function() {
      // quick manager review modal or redirect to manager review page
      window.location.href = '/employees/view-feedback.php?id=' + sa.employee_id;
    });
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/[&<>"]/g, function(tag) {
    const charsToReplace = {'&':'&','<':'<','>':'>','"':'"'};
    return charsToReplace[tag] || tag;
  });
}

async function submitUpdate(id, action) {
  const formEl = document.getElementById('saViewForm');
  const fd = new FormData();
  fd.append('id', id);
  fd.append('action', action);
  fd.append('csrf_token', formEl.querySelector('input[name="csrf_token"]').value);

  // collect ratings/comments if editable
  formEl.querySelectorAll('.dimension-block').forEach(block => {
    const label = block.querySelector('.form-label').textContent.trim().toLowerCase().replace(' ', '_');
    const ratingEl = block.querySelector('select');
    const commentEl = block.querySelector('textarea');
    if (ratingEl && ratingEl.value) fd.append('ratings['+label+']', ratingEl.value);
    if (commentEl && commentEl.value) fd.append('comments['+label+']', commentEl.value);
  });
  const addComments = formEl.querySelector('textarea[name="additional_comments"]');
  if (addComments && addComments.value) fd.append('additional_comments', addComments.value);

  try {
    const res = await fetch('/api/self-assessment/update.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: fd
    });
    const result = await res.json();
    if (result.success) {
      if (window.showNotification) window.showNotification('success', result.message || 'Updated successfully.');
      // reload to reflect new status / data
      setTimeout(() => location.reload(), 700);
    } else {
      if (window.showNotification) window.showNotification('error', result.message || 'Update failed.');
      else alert(result.message || 'Update failed.');
    }
  } catch (err) {
    console.error(err);
    alert('An error occurred while updating.');
  }
}

// load on ready
document.addEventListener('DOMContentLoaded', loadAssessment);
</script>

<script src="/assets/js/app.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>