<?php
/**
 * Self-Assessment Comparison - Self vs Manager Ratings
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/SelfAssessmentManager.php';

requireAuth();

$pageTitle = 'Self vs Manager Comparison';
$pageHeader = true;
$pageDescription = 'Compare your self-ratings with manager ratings for an assessment';

$currentUser = getCurrentUser();
$userRole = $_SESSION['user_role'] ?? 'employee';
$employeeId = $_SESSION['employee_id'] ?? null;

$assessmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
  <div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h3 mb-1"><i class="fas fa-balance-scale me-2 text-primary"></i>Self vs Manager Comparison</h1>
        <p class="text-muted mb-0">Visual comparison of ratings across dimensions</p>
      </div>
      <div>
        <a href="/self-assessment/view.php?id=<?php echo $assessmentId; ?>" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i>Back to Assessment
        </a>
      </div>
    </div>
  </div>

  <div id="comparisonRow" class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Rating Comparison</h6>
        </div>
        <div class="card-body">
          <canvas id="comparisonChart" height="220"></canvas>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-list me-2"></i>Dimension Details</h6>
        </div>
        <div class="card-body" id="dimensionDetails">
          <p class="text-muted">Loading comparison details...</p>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Notes</h6>
        </div>
        <div class="card-body">
          <p class="small text-muted">This comparison is anonymized for certain manager comments where applicable. Use the details below to identify differences and create development actions.</p>
          <?php if ($userRole === 'employee'): ?>
          <a href="/self-assessment/create.php" class="btn btn-sm btn-primary mt-2"><i class="fas fa-plus me-1"></i>New Self-Assessment</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Recommended Actions</h6>
        </div>
        <div class="card-body" id="recommendedActions">
          <p class="text-muted">Loading...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const assessmentId = <?php echo $assessmentId ?: 'null'; ?>;
if (!assessmentId) {
  document.getElementById('comparisonRow').innerHTML = '<div class="col-12"><div class="alert alert-warning">No assessment specified.</div></div>';
}

/**
 * Fetch assessment data (including manager ratings) and render chart
 */
async function loadComparison() {
  try {
    const res = await fetch('/api/self-assessment/get.php?id=' + assessmentId, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data || !data.assessment) {
      document.getElementById('dimensionDetails').innerHTML = '<div class="alert alert-danger">Comparison data not available.</div>';
      return;
    }
    renderComparison(data.assessment);
  } catch (err) {
    console.error(err);
    document.getElementById('dimensionDetails').innerHTML = '<div class="alert alert-danger">Failed to load comparison.</div>';
  }
}

function renderComparison(sa) {
  const dims = [];
  const selfRatings = [];
  const managerRatings = [];
  const detailsEl = document.getElementById('dimensionDetails');
  detailsEl.innerHTML = '';

  // Combine dimensions present in ratings or manager_ratings
  const ratingKeys = new Set();
  if (sa.ratings) Object.keys(sa.ratings).forEach(k => ratingKeys.add(k));
  if (sa.manager_ratings) Object.keys(sa.manager_ratings).forEach(k => ratingKeys.add(k));
  const keys = Array.from(ratingKeys);

  keys.forEach(k => {
    const label = k.replace('_', ' ');
    dims.push(label.charAt(0).toUpperCase() + label.slice(1));
    selfRatings.push(sa.ratings && sa.ratings[k] ? Number(sa.ratings[k]) : 0);
    managerRatings.push(sa.manager_ratings && sa.manager_ratings[k] ? Number(sa.manager_ratings[k]) : 0);

    // detail block
    const selfComment = sa.comments && sa.comments[k] ? sa.comments[k] : '';
    const managerComment = sa.manager_comments && sa.manager_comments[k] ? sa.manager_comments[k] : '';
    const block = document.createElement('div');
    block.className = 'mb-3 p-3 border rounded';
    block.innerHTML = `<div class="d-flex justify-content-between align-items-center mb-2">
      <strong class="text-capitalize">${label}</strong>
      <div><span class="badge bg-primary me-1">Self: ${sa.ratings && sa.ratings[k] ? sa.ratings[k] : '—'}</span><span class="badge bg-success">Manager: ${sa.manager_ratings && sa.manager_ratings[k] ? sa.manager_ratings[k] : '—'}</span></div>
    </div>
    <div class="small text-muted mb-2"><strong>Self comment:</strong> ${escapeHtml(selfComment) || '<span class="text-muted">None</span>'}</div>
    <div class="small text-muted"><strong>Manager comment:</strong> ${escapeHtml(managerComment) || '<span class="text-muted">None</span>'}</div>`;
    detailsEl.appendChild(block);
  });

  // render chart using Chart.js
  const ctx = document.getElementById('comparisonChart').getContext('2d');
  if (window.comparisonChartInstance) window.comparisonChartInstance.destroy();
  window.comparisonChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: dims,
      datasets: [
        {
          label: 'Self',
          data: selfRatings,
          backgroundColor: 'rgba(54,162,235,0.6)'
        },
        {
          label: 'Manager',
          data: managerRatings,
          backgroundColor: 'rgba(75,192,192,0.6)'
        }
      ]
    },
    options: {
      responsive: true,
      scales: {
        y: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } }
      },
      plugins: {
        tooltip: { mode: 'index', intersect: false },
        legend: { position: 'top' }
      }
    }
  });

  // Recommended actions: compute deltas and show suggested actions
  const recEl = document.getElementById('recommendedActions');
  recEl.innerHTML = '';
  let anyRecommendations = false;
  keys.forEach((k, idx) => {
    const s = selfRatings[idx] || 0;
    const m = managerRatings[idx] || 0;
    const delta = (s - m);
    if (Math.abs(delta) >= 1) {
      anyRecommendations = true;
      const action = document.createElement('div');
      action.className = 'mb-2';
      const tone = delta > 0 ? 'You rated yourself higher than manager — consider discussing examples.' : 'Manager rated higher — consider asking for specific guidance to improve visibility.';
      action.innerHTML = `<div class="p-2 border rounded">
        <strong class="text-capitalize">${k.replace('_',' ')}</strong>
        <div class="small text-muted mt-1">${tone}</div>
      </div>`;
      recEl.appendChild(action);
    }
  });
  if (!anyRecommendations) recEl.innerHTML = '<p class="text-muted small">No significant differences between self and manager ratings.</p>';
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/[&<>"]/g, function(tag) {
    const map = {'&':'&','<':'<','>':'>','"':'"'};
    return map[tag] || tag;
  });
}

document.addEventListener('DOMContentLoaded', loadComparison);
</script>

<script src="/assets/js/app.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>