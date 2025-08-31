<?php
/**
 * Achievement Journal - Timeline View
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/AchievementJournal.php';

requireAuth();

$pageTitle = 'Achievement Journal';
$pageHeader = true;
$pageDescription = 'Track and showcase achievements over time';

$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'] ?? 'employee';
$employeeId = $_SESSION['employee_id'] ?? null;

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
  <div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h3 mb-1"><i class="fas fa-trophy me-2 text-primary"></i>Achievement Journal</h1>
        <p class="text-muted mb-0">A timeline of achievements and growth evidence</p>
      </div>
      <div class="d-flex gap-2">
        <a href="/achievements/create.php" class="btn btn-primary">
          <i class="fas fa-plus me-1"></i>New Entry
        </a>
        <button id="refreshJournal" class="btn btn-outline-secondary"><i class="fas fa-sync-alt"></i></button>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body" id="journalTimeline" style="min-height:200px;">
          <div class="text-center text-muted py-4">Loading journal...</div>
        </div>
        <div class="card-footer text-center">
          <button id="loadMoreBtn" class="btn btn-sm btn-outline-primary">Load more</button>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h6>
        </div>
        <div class="card-body">
          <div class="mb-2">
            <label class="form-label small">Author</label>
            <select id="filterAuthor" class="form-select form-select-sm">
              <option value="">All</option>
              <option value="me">My entries</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label small">Dimension</label>
            <select id="filterDimension" class="form-select form-select-sm">
              <option value="">All</option>
              <option value="communication">Communication</option>
              <option value="collaboration">Collaboration</option>
              <option value="delivery">Delivery</option>
              <option value="quality">Quality</option>
            </select>
          </div>
          <div>
            <label class="form-label small">Search</label>
            <input id="filterSearch" class="form-control form-control-sm" placeholder="keywords">
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Quick Stats</h6>
        </div>
        <div class="card-body" id="journalStats">
          <p class="text-muted small">Loading stats...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let journalPage = 1;
const pageSize = 10;

async function fetchJournal(reset = false) {
  if (reset) {
    journalPage = 1;
    document.getElementById('journalTimeline').innerHTML = '<div class="text-center text-muted py-4">Loading journal...</div>';
  }
  const params = new URLSearchParams();
  params.append('page', journalPage);
  params.append('page_size', pageSize);
  const authorFilter = document.getElementById('filterAuthor').value;
  const dimFilter = document.getElementById('filterDimension').value;
  const search = document.getElementById('filterSearch').value;
  if (authorFilter) params.append('author', authorFilter);
  if (dimFilter) params.append('dimension', dimFilter);
  if (search) params.append('q', search);

  try {
    const res = await fetch('/api/achievements/list.php?' + params.toString(), { credentials: 'same-origin' });
    const data = await res.json();
    renderJournal(data, reset);
  } catch (err) {
    console.error(err);
    document.getElementById('journalTimeline').innerHTML = '<div class="alert alert-danger">Failed to load journal.</div>';
  }
}

function renderJournal(data, reset) {
  const container = document.getElementById('journalTimeline');
  if (!data || !data.entries || data.entries.length === 0) {
    if (reset || journalPage === 1) {
      container.innerHTML = '<div class="text-center text-muted py-4">No achievement entries found.</div>';
    } else {
      document.getElementById('loadMoreBtn').disabled = true;
      document.getElementById('loadMoreBtn').textContent = 'No more entries';
    }
    updateStats(data);
    return;
  }

  if (reset) container.innerHTML = '';

  data.entries.forEach(entry => {
    const entryEl = document.createElement('div');
    entryEl.className = 'mb-3 p-3 border rounded';
    entryEl.innerHTML = `
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
          <strong>${escapeHtml(entry.title)}</strong>
          <div class="small text-muted">${escapeHtml(entry.author_name)} • ${entry.created_at}</div>
        </div>
        <div class="text-end">
          <span class="badge bg-secondary">${escapeHtml(entry.dimension || 'General')}</span><br>
          <div class="mt-2">
            <a href="/achievements/view.php?id=${entry.id}" class="btn btn-sm btn-outline-primary me-1">View</a>
            ${entry.can_edit ? `<a href="/achievements/edit.php?id=${entry.id}" class="btn btn-sm btn-primary">Edit</a>` : ''}
          </div>
        </div>
      </div>
      <p class="mb-0">${escapeHtml(truncate(entry.content, 200))}</p>
    `;
    container.appendChild(entryEl);
  });

  // pagination: enable load more if results equal page size
  if (data.entries.length === pageSize) {
    document.getElementById('loadMoreBtn').disabled = false;
    document.getElementById('loadMoreBtn').textContent = 'Load more';
  } else {
    document.getElementById('loadMoreBtn').disabled = true;
    document.getElementById('loadMoreBtn').textContent = 'No more entries';
  }

  updateStats(data);
}

function updateStats(data) {
  const statsEl = document.getElementById('journalStats');
  if (!data || !data.stats) {
    statsEl.innerHTML = '<p class="text-muted small">No stats available.</p>';
    return;
  }
  statsEl.innerHTML = `
    <div class="small">Total entries: <strong>${data.stats.total || 0}</strong></div>
    <div class="small">My entries: <strong>${data.stats.my_entries || 0}</strong></div>
    <div class="small">Most active dimension: <strong>${data.stats.top_dimension || '—'}</strong></div>
  `;
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/[&<>"]/g, function(tag) {
    const map = {'&':'&','<':'<','>':'>','"':'"'};
    return map[tag] || tag;
  });
}

function truncate(str, n) {
  if (!str) return '';
  return str.length > n ? str.substring(0, n) + '…' : str;
}

document.getElementById('loadMoreBtn').addEventListener('click', function() {
  journalPage++;
  fetchJournal(false);
});
document.getElementById('refreshJournal').addEventListener('click', function() { fetchJournal(true); });
document.getElementById('filterAuthor').addEventListener('change', function(){ fetchJournal(true); });
document.getElementById('filterDimension').addEventListener('change', function(){ fetchJournal(true); });
document.getElementById('filterSearch').addEventListener('keyup', debounce(function(){ fetchJournal(true); }, 400));

function debounce(fn, delay) {
  let t;
  return function() {
    clearTimeout(t);
    t = setTimeout(() => fn.apply(this, arguments), delay);
  };
}

// initial load
fetchJournal(true);
</script>

<script src="/assets/js/app.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>