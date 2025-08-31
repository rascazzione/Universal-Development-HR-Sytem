<?php
/**
 * View Achievement Entry
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/AchievementJournal.php';

requireAuth();

$pageTitle = 'View Achievement';
$pageHeader = true;
$pageDescription = 'Achievement details and interactions';

$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'] ?? 'employee';
$employeeId = $_SESSION['employee_id'] ?? null;

$entryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
  <div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h3 mb-1"><i class="fas fa-trophy me-2 text-primary"></i>Achievement</h1>
        <p class="text-muted mb-0">Details, attachments and social interactions</p>
      </div>
      <div>
        <a href="/achievements/journal.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
      </div>
    </div>
  </div>

  <div id="achievementContainer" class="row">
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-body" id="achievementBody">
          <div class="text-center text-muted py-4">Loading achievement...</div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Comments & Reactions</h6>
        </div>
        <div class="card-body" id="achievementSocial">
          <div class="mb-3" id="reactionsArea">Loading reactions...</div>
          <div id="commentsArea">Loading comments...</div>
          <div class="mt-3">
            <textarea id="newComment" class="form-control mb-2" rows="2" placeholder="Add a comment..."></textarea>
            <div class="d-flex justify-content-between">
              <div>
                <button id="reactLike" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-thumbs-up me-1"></i>Like</button>
                <button id="reactCelebrate" class="btn btn-sm btn-outline-success me-1"><i class="fas fa-party-horn me-1"></i>Celebrate</button>
              </div>
              <button id="postComment" class="btn btn-sm btn-primary">Post Comment</button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Meta</h6>
          <div id="entryActions"></div>
        </div>
        <div class="card-body" id="achievementMeta">
          <p class="text-muted">Loading metadata...</p>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-paperclip me-2"></i>Attachments</h6>
        </div>
        <div class="card-body" id="achievementAttachments">
          <p class="text-muted">No attachments</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const entryId = <?php echo $entryId ?: 'null'; ?>;
const currentUserId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
const userRole = '<?php echo $userRole; ?>';

async function loadEntry() {
  if (!entryId) {
    document.getElementById('achievementBody').innerHTML = '<div class="alert alert-warning">No entry specified.</div>';
    return;
  }
  try {
    const res = await fetch('/api/achievements/get.php?id=' + entryId, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data || !data.entry) {
      document.getElementById('achievementBody').innerHTML = '<div class="alert alert-danger">Entry not found.</div>';
      return;
    }
    renderEntry(data.entry);
  } catch (err) {
    console.error(err);
    document.getElementById('achievementBody').innerHTML = '<div class="alert alert-danger">Failed to load entry.</div>';
  }
}

function renderEntry(e) {
  const body = document.getElementById('achievementBody');
  body.innerHTML = `
    <h4>${escapeHtml(e.title)}</h4>
    <div class="small text-muted mb-2">${escapeHtml(e.author_name)} • ${e.created_at} • <span class="badge bg-secondary">${escapeHtml(e.dimension || 'General')}</span></div>
    <div class="mb-3">${escapeHtml(e.content).replace(/\n/g,'<br>')}</div>
  `;

  // attachments
  const attEl = document.getElementById('achievementAttachments');
  attEl.innerHTML = '';
  if (e.attachments && e.attachments.length) {
    e.attachments.forEach(a => {
      const aEl = document.createElement('div');
      aEl.innerHTML = `<a href="${escapeAttr(a.url)}" target="_blank" class="d-block">${escapeHtml(a.filename)}</a>`;
      attEl.appendChild(aEl);
    });
  } else {
    attEl.innerHTML = '<p class="text-muted">No attachments</p>';
  }

  // meta
  const metaEl = document.getElementById('achievementMeta');
  metaEl.innerHTML = `<div class="small">Views: <strong>${e.views || 0}</strong></div>
                      <div class="small">Likes: <strong>${e.reactions ? e.reactions.total_likes : 0}</strong></div>
                      <div class="small">Comments: <strong>${e.comments_count || 0}</strong></div>`;

  // actions
  const actionsEl = document.getElementById('entryActions');
  actionsEl.innerHTML = '';
  const canEdit = (e.author_id == currentUserId) || userRole === 'hr_admin';
  if (canEdit) {
    actionsEl.innerHTML = `<a href="/achievements/edit.php?id=${e.id}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                           <button id="deleteEntry" class="btn btn-sm btn-danger">Delete</button>`;
    document.getElementById('deleteEntry').addEventListener('click', confirmDelete);
  }

  // reactions area
  renderReactions(e.reactions || {});
  renderComments(e.comments || []);
}

function renderReactions(reactions) {
  const rEl = document.getElementById('reactionsArea');
  rEl.innerHTML = '';
  const likeCount = reactions.likes || 0;
  const celebrateCount = reactions.celebrate || 0;
  rEl.innerHTML = `<div class="small">Likes: <strong id="likeCount">${likeCount}</strong> • Celebrate: <strong id="celebrateCount">${celebrateCount}</strong></div>`;
}

function renderComments(comments) {
  const cEl = document.getElementById('commentsArea');
  cEl.innerHTML = '';
  if (!comments || comments.length === 0) {
    cEl.innerHTML = '<div class="text-muted small">No comments yet.</div>';
    return;
  }
  comments.forEach(cm => {
    const block = document.createElement('div');
    block.className = 'mb-2 p-2 border rounded';
    block.innerHTML = `<div class="d-flex justify-content-between"><div><strong>${escapeHtml(cm.author_name)}</strong> <small class="text-muted">${cm.created_at}</small></div>${cm.author_id == currentUserId || userRole === 'hr_admin' ? '<button class="btn btn-sm btn-link text-danger comment-delete" data-id="'+cm.id+'">Delete</button>' : ''}</div><div class="small mt-1">${escapeHtml(cm.content)}</div>`;
    cEl.appendChild(block);
  });
  // attach delete handlers
  document.querySelectorAll('.comment-delete').forEach(btn => {
    btn.addEventListener('click', async function() {
      const id = this.getAttribute('data-id');
      if (!confirm('Delete this comment?')) return;
      try {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('entry_id', entryId);
        fd.append('csrf_token', '<?php echo csrf_token(); ?>');
        const res = await fetch('/api/achievements/delete.php', { method: 'POST', credentials: 'same-origin', body: fd });
        const r = await res.json();
        if (r.success) {
          if (window.showNotification) window.showNotification('success', r.message || 'Deleted');
          loadEntry();
        } else {
          alert(r.message || 'Failed to delete');
        }
      } catch (err) {
        console.error(err);
        alert('Error deleting comment');
      }
    });
  });
}

async function confirmDelete() {
  if (!confirm('Delete this achievement? This action cannot be undone.')) return;
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
}

// reactions and comments handlers
document.getElementById('reactLike').addEventListener('click', async function() {
  await sendReaction('like');
});
document.getElementById('reactCelebrate').addEventListener('click', async function() {
  await sendReaction('celebrate');
});
document.getElementById('postComment').addEventListener('click', async function() {
  const content = document.getElementById('newComment').value.trim();
  if (!content) return alert('Please add a comment.');
  try {
    const fd = new FormData();
    fd.append('entry_id', entryId);
    fd.append('content', content);
    fd.append('csrf_token', '<?php echo csrf_token(); ?>');
    const res = await fetch('/api/achievements/create.php', { method: 'POST', credentials: 'same-origin', body: fd });
    const r = await res.json();
    if (r.success) {
      document.getElementById('newComment').value = '';
      if (window.showNotification) window.showNotification('success', r.message || 'Comment posted');
      loadEntry();
    } else {
      alert(r.message || 'Failed to post comment');
    }
  } catch (err) {
    console.error(err);
    alert('Failed to post comment');
  }
});

async function sendReaction(type) {
  try {
    const fd = new FormData();
    fd.append('entry_id', entryId);
    fd.append('reaction', type);
    fd.append('csrf_token', '<?php echo csrf_token(); ?>');
    const res = await fetch('/api/kudos/react.php', { method: 'POST', credentials: 'same-origin', body: fd });
    const r = await res.json();
    if (r.success) {
      if (window.showNotification) window.showNotification('success', r.message || 'Reacted');
      loadEntry();
    } else {
      alert(r.message || 'Reaction failed');
    }
  } catch (err) {
    console.error(err);
    alert('Reaction failed');
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

document.addEventListener('DOMContentLoaded', loadEntry);
</script>

<script src="/assets/js/app.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>