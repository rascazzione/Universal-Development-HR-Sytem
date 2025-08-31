<?php
/**
 * KUDOS Leaderboard
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/KudosManager.php';

requireAuth();

$pageTitle = 'KUDOS Leaderboard';
$pageHeader = true;
$pageDescription = 'Top recognitions and points leaderboard';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
  <div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h3 mb-1"><i class="fas fa-trophy me-2 text-primary"></i>KUDOS Leaderboard</h1>
        <p class="text-muted mb-0">Top contributors through recognitions</p>
      </div>
      <div>
        <button id="refreshLeaderboard" class="btn btn-outline-secondary"><i class="fas fa-sync-alt"></i></button>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body" id="leaderboardList">
          <div class="text-center text-muted py-4">Loading leaderboard...</div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Points Breakdown</h6>
        </div>
        <div class="card-body" id="pointsBreakdown">
          <p class="text-muted small">Loading data...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
async function fetchLeaderboard() {
  try {
    const res = await fetch('/api/kudos/leaderboard.php', { credentials: 'same-origin' });
    const data = await res.json();
    renderLeaderboard(data);
  } catch (err) {
    console.error(err);
    document.getElementById('leaderboardList').innerHTML = '<div class="alert alert-danger">Failed to load leaderboard.</div>';
  }
}

function renderLeaderboard(data) {
  const container = document.getElementById('leaderboardList');
  if (!data || !data.leaderboard || data.leaderboard.length === 0) {
    container.innerHTML = '<div class="text-center text-muted py-4">No leaderboard data.</div>';
    return;
  }
  let html = '<ol class="list-group list-group-numbered">';
  data.leaderboard.forEach(row => {
    html += `<li class="list-group-item d-flex justify-content-between align-items-start">
      <div class="ms-2 me-auto">
        <div class="fw-bold">${escapeHtml(row.name)}</div>
        <small class="text-muted">${escapeHtml(row.position || '')}</small>
      </div>
      <span class="badge bg-primary rounded-pill">${row.points}</span>
    </li>`;
  });
  html += '</ol>';
  container.innerHTML = html;

  // points breakdown
  const pb = document.getElementById('pointsBreakdown');
  if (data.points_breakdown) {
    let pbHtml = '<div class="small">';
    Object.keys(data.points_breakdown).forEach(k => {
      pbHtml += `<div class="mb-1"><strong>${escapeHtml(k)}</strong>: ${data.points_breakdown[k]}</div>`;
    });
    pbHtml += '</div>';
    pb.innerHTML = pbHtml;
  } else {
    pb.innerHTML = '<p class="text-muted small">No breakdown available</p>';
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"]/g, function(tag){ const map = {'&':'&','<':'<','>':'>','"':'"'}; return map[tag] || tag; });
}

document.getElementById('refreshLeaderboard').addEventListener('click', fetchLeaderboard);
document.addEventListener('DOMContentLoaded', fetchLeaderboard);
</script>

<script src="/assets/js/app.js"></script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>