<?php
/**
 * Edit Achievement Entry
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/AchievementJournal.php';

requireAuth();

$pageTitle = 'Edit Achievement';
$pageHeader = true;
$pageDescription = 'Edit an existing achievement entry';

$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'] ?? 'employee';
$employeeId = $_SESSION['employee_id'] ?? null;

$entryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

include __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Achievement</h5>
        <div id="editActions"></div>
      </div>
      <div class="card-body" id="editBody">
        <div class="text-center text-muted py-4">Loading entry...</div>
      </div>
    </div>
  </div>
</div>

<script>
const entryId = <?php echo $entryId ?: 'null'; ?>;

async function loadEntryForEdit() {
  if (!entryId) {
    document.getElementById('editBody').innerHTML = '<div class="alert alert-warning">No entry specified.</div>';
    return;
  }
  try {
    const res = await fetch('/api/achievements/get.php?id=' + entryId, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data || !data.entry) {
      document.getElementById('editBody').innerHTML = '<div class="alert alert-danger">Entry not found or you do not have permission.</div>';
      return;
    }
    renderEditForm(data.entry);
  } catch (err) {
    console.error(err);
    document.getElementById('editBody').innerHTML = '<div class="alert alert-danger">Failed to load entry.</div>';
  }
}

function renderEditForm(e) {
  const body = document.getElementById('editBody');
  body.innerHTML = `
    <form id="editForm" class="needs-validation" novalidate enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="id" value="${e.id}">
      <div class="mb-3">
        <label class="form-label">Title *</label>
        <input name="title" class="form-control" required value="${escapeAttr(e.title)}">
        <div class="invalid-feedback">Please enter a title.</div>
      </div>

      <div class="mb-3">
        <label class="form-label">Dimension</label>
        <select name="dimension" class="form-select">
          <option value="">General</option>
          <option value="communication" ${e.dimension === 'communication' ? 'selected' : ''}>Communication</option>
          <option value="collaboration" ${e.dimension === 'collaboration' ? 'selected' : ''}>Collaboration</option>
          <option value="delivery" ${e.dimension === 'delivery' ? 'selected' : ''}>Delivery</option>
          <option value="quality" ${e.dimension === 'quality' ? 'selected' : ''}>Quality</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Description *</label>
        <textarea name="content" class="form-control" rows="6" required>${escapeAttr(e.content)}</textarea>
        <div class="invalid-feedback">Please provide a description.</div>
      </div>

      <div class="mb-3">
        <label class="form-label">Existing Attachments</label>
        <div id="existingAttachments" class="mb-2"></div>
        <label class="form-label">Add Attachments</label>
        <input type="file" name="attachments[]" class="form-control" multiple>
      </div>

      <div class="d-flex justify-content-between">
        <a href="/achievements/view.php?id=${e.id}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Cancel</a>
        <div>
          <button type="button" id="saveDraft" class="btn btn-outline-secondary me-2">Save Draft</button>
          <button type="submit" id="saveBtn" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>
  `;

  // populate attachments
  const attEl = document.getElementById('existingAttachments');
  attEl.innerHTML = '';
  if (e.attachments && e.attachments.length) {
    e.attachments.forEach(a => {
      const el = document.createElement('div');
      el.className = 'mb-1';
      el.innerHTML = `<a href="${escapeAttr(a.url)}" target="_blank">${escapeHtml(a.filename)}</a> ${a.can_delete ? '<button class="btn btn-sm btn-link text-danger remove-attachment" data-file="'+escapeAttr(a.file_id)+'">Remove</button>' : ''}`;
      attEl.appendChild(el);
    });
    // attach remove handlers
    document.querySelectorAll('.remove-attachment').forEach(btn => {
      btn.addEventListener('click', async function(){
        const fileId = this.getAttribute('data-file');
        if (!confirm('Remove this attachment?')) return;
        try {
          const fd = new FormData();
          fd.append('id', entryId);
          fd.append('file_id', fileId);
          fd.append('csrf_token', '<?php echo csrf_token(); ?>');
          const res = await fetch('/api/achievements/delete.php', { method: 'POST', credentials: 'same-origin', body: fd });
          const r = await res.json();
          if (r.success) {
            if (window.showNotification) window.showNotification('success', r.message || 'Removed');
            loadEntryForEdit();
          } else {
            alert(r.message || 'Failed to remove');
          }
        } catch (err) {
          console.error(err);
          alert('Failed to remove attachment');
        }
      });
    });
  } else {
    attEl.innerHTML = '<div class="text-muted small">No attachments</div>';
  }

  // attach form handlers
  const form = document.getElementById('editForm');
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }
    document.getElementById('saveBtn').disabled = true;
    await submitEdit('publish');
    document.getElementById('saveBtn').disabled = false;
  });

  document.getElementById('saveDraft').addEventListener('click', async function() {
    this.disabled = true;
    await submitEdit('draft');
    this.disabled = false;
  });

  // set header actions
  const headerActions = document.getElementById('editActions');
  headerActions.innerHTML = `<a href="/achievements/view.php?id=${e.id}" class="btn btn-sm btn-outline-secondary me-1">Preview</a> <button id="deleteEntry" class="btn btn-sm btn-danger">Delete</button>`;
  document.getElementById('deleteEntry').addEventListener('click', async function(){
    if (!confirm('Delete this achievement?')) return;
    try {
      const fd = new FormData();
      fd.append('id', entryId);
      fd.append('csrf_token', '<?php echo csrf_token(); ?>');
      const res = await fetch('/api/achievements/delete.php', { method: 'POST', credentials: 'same-origin', body: fd });
      const r = await res.json();
      if (r.success) {
        if (window.showNotification) window.showNotification('success', r.message || 'Deleted');
        window.location.href = '/achievements/journal.php';
      } else {
        alert(r.message || 'Delete failed');
      }
    } catch (err) {
      console.error(err);
      alert('Delete failed');
    }
  });
}

async function submitEdit(action) {
  const form = document.getElementById('editForm');
  const fd = new FormData(form);
  fd.append('action', action);
  try {
    const res = await fetch('/api/achievements/update.php', { method: 'POST', credentials: 'same-origin', body: fd });
    const result = await res.json();
    if (result.success) {
      if (window.showNotification) window.showNotification('success', result.message || 'Saved.');
      // redirect to view page
      window.location.href = '/achievements/view.php?id=' + (result.entry_id || entryId);
    } else {
      if (window.showNotification) window.showNotification('error', result.message || 'Save failed');
      else alert(result.message || 'Save failed');
    }
  } catch (err) {
    console.error(err);
    alert('Error saving changes');
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"]/g, function(tag) {
    const map = {'&':'&','<':'<','>':'>','"':'"'};
    return map[tag] || tag;
  });
}
function escapeAttr(str) {
  if (!str) return '';
  return String(str).replace(/"/g, '"');
}

document.addEventListener('DOMContentLoaded', loadEntryForEdit);
</script>

<script src="/assets/js/app.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>