<?php
// public/kudos/feed.php
// Lightweight KUDOS feed UI. Assumes site-wide header/footer and authentication are handled globally.
// This file focuses on the client-side feed and connects to the existing API endpoints under /public/api/kudos/

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Kudos Feed</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <!-- CSRF token if your app injects one into a global meta tag -->
    <meta name="csrf-token" content="<?php echo isset($_SESSION['csrf_token']) ? htmlspecialchars($_SESSION['csrf_token']) : ''; ?>">
    <link rel="stylesheet" href="/assets/css/app.css" />
</head>
<body>
<?php
// Optional: include shared header/navigation if available
if (file_exists(__DIR__ . '/../_partials/header.php')) {
    include_once __DIR__ . '/../_partials/header.php';
}
?>
<main class="container">
    <div class="page-header">
        <h1>Kudos Feed</h1>
        <p class="muted">See what people are celebrating across the organization.</p>
        <div class="actions">
            <a class="btn btn-primary" href="/kudos/give.php">Give Kudos</a>
            <a class="btn btn-secondary" href="/kudos/leaderboard.php">Leaderboard</a>
            <a class="btn btn-tertiary" href="/kudos/analytics.php">Analytics</a>
        </div>
    </div>

    <section id="kudos-feed">
        <div id="feed-list" class="feed-list">
            <!-- feed items inserted here -->
        </div>

        <div id="feed-empty" class="empty-state" style="display:none;">
            <p>No kudos yet — be the first to recognize someone!</p>
            <a class="btn btn-primary" href="/kudos/give.php">Give Kudos</a>
        </div>

        <div id="feed-loading" class="loading" style="display:none;">
            <p>Loading...</p>
        </div>

        <div id="feed-error" class="alert alert-error" style="display:none;"></div>

        <div class="load-more-wrap">
            <button id="load-more" class="btn" style="display:none;">Load more</button>
        </div>
    </section>
</main>

<?php
if (file_exists(__DIR__ . '/../_partials/footer.php')) {
    include_once __DIR__ . '/../_partials/footer.php';
}
?>

<script>
(function(){
    const feedList = document.getElementById('feed-list');
    const loadingEl = document.getElementById('feed-loading');
    const emptyEl = document.getElementById('feed-empty');
    const errorEl = document.getElementById('feed-error');
    const loadMoreBtn = document.getElementById('load-more');

    let page = 1;
    const perPage = 10;
    let hasMore = true;
    let loading = false;

    function showLoading(on){
        loading = on;
        loadingEl.style.display = on ? 'block' : 'none';
    }

    function fetchFeed(reset = false){
        if (loading) return;
        showLoading(true);
        if (reset) {
            page = 1;
            hasMore = true;
            feedList.innerHTML = '';
            emptyEl.style.display = 'none';
            errorEl.style.display = 'none';
        }

        const url = `/api/kudos/feed.php?page=${page}&per_page=${perPage}`;
        fetch(url, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        }).then(r => r.json())
          .then(data => {
              showLoading(false);
              if (!data || !Array.isArray(data.items)) {
                  errorEl.textContent = data && data.message ? data.message : 'Unexpected response from server';
                  errorEl.style.display = 'block';
                  return;
              }

              if (data.items.length === 0 && page === 1) {
                  emptyEl.style.display = 'block';
                  loadMoreBtn.style.display = 'none';
                  return;
              }

              renderItems(data.items);
              hasMore = data.items.length >= perPage && data.total && (page * perPage) < data.total;
              loadMoreBtn.style.display = hasMore ? 'inline-block' : 'none';
              page++;
          }).catch(err => {
              showLoading(false);
              errorEl.textContent = 'Failed to load feed. Please try again later.';
              errorEl.style.display = 'block';
              console.error(err);
          });
    }

    function renderItems(items){
        items.forEach(item => {
            const el = document.createElement('article');
            el.className = 'kudos-item card';
            el.innerHTML = `
                <div class="kudos-header">
                    <div class="avatar">${escapeHtml(initials(item.giver_name || ''))}</div>
                    <div class="meta">
                        <strong>${escapeHtml(item.giver_name || 'Someone')}</strong>
                        <span class="muted">gave kudos to</span>
                        <strong>${escapeHtml(item.receiver_name || '')}</strong>
                        <div class="small muted">${escapeHtml(item.created_at || '')}</div>
                    </div>
                </div>
                <div class="kudos-body">
                    <p>${escapeHtml(item.message || '')}</p>
                    ${renderTags(item.tags || [])}
                </div>
                <div class="kudos-actions">
                    <button class="btn btn-ghost react-btn" data-id="${escapeAttr(item.id)}" data-reaction="clap">👏 <span class="count">${item.reactions ? item.reactions.clap || 0 : 0}</span></button>
                    <a class="btn btn-link" href="/kudos/view.php?id=${encodeURIComponent(item.id)}">View</a>
                </div>
            `;
            feedList.appendChild(el);

            // attach reaction handler
            const reactBtn = el.querySelector('.react-btn');
            if (reactBtn) {
                reactBtn.addEventListener('click', () => {
                    sendReaction(reactBtn.dataset.id, reactBtn);
                });
            }
        });
    }

    function renderTags(tags){
        if (!tags.length) return '';
        return '<div class="tags">' + tags.map(t => '<span class="tag">'+escapeHtml(t)+'</span>').join('') + '</div>';
    }

    function sendReaction(kudosId, btn){
        const reaction = btn.dataset.reaction || 'clap';
        const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : null;
        fetch('/api/kudos/react.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(csrf ? {'X-CSRF-Token': csrf} : {})
            },
            body: JSON.stringify({kudos_id: kudosId, reaction: reaction})
        }).then(r => r.json()).then(data => {
            if (data && data.success) {
                const countEl = btn.querySelector('.count');
                if (countEl) {
                    countEl.textContent = data.reaction_count || parseInt(countEl.textContent||0,10) + 1;
                }
            } else {
                alert(data && data.message ? data.message : 'Could not apply reaction');
            }
        }).catch(err => {
            console.error(err);
            alert('Failed to send reaction');
        });
    }

    function escapeHtml(s){
        if (!s) return '';
        return String(s)
            .replace(/&/g, '&')
            .replace(/</g, '<')
            .replace(/>/g, '>')
            .replace(/"/g, '"')
            .replace(/'/g, ''');
    }

    function escapeAttr(s){
        return escapeHtml(s);
    }

    function initials(name){
        if (!name) return '';
        return name.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase();
    }

    loadMoreBtn.addEventListener('click', () => {
        if (hasMore) fetchFeed();
    });

    // initial load
    fetchFeed(true);
})();
</script>
</body>
</html>