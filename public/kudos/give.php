<?php
// public/kudos/give.php
// Simple form for giving kudos. Connects to /api/kudos/give.php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Give Kudos</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="csrf-token" content="<?php echo isset($_SESSION['csrf_token']) ? htmlspecialchars($_SESSION['csrf_token']) : ''; ?>">
    <link rel="stylesheet" href="/assets/css/app.css" />
</head>
<body>
<?php
if (file_exists(__DIR__ . '/../_partials/header.php')) {
    include_once __DIR__ . '/../_partials/header.php';
}
?>
<main class="container">
    <div class="page-header">
        <h1>Give Kudos</h1>
        <p class="muted">Recognize a colleague for their great work.</p>
    </div>

    <form id="give-kudos-form" class="card form-card">
        <div class="form-row">
            <label for="receiver">To</label>
            <input id="receiver" name="receiver" type="text" placeholder="Search name or email" autocomplete="off" required />
            <input id="receiver_id" name="receiver_id" type="hidden" />
            <div id="receiver-suggestions" class="suggestions" style="display:none;"></div>
        </div>

        <div class="form-row">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="4" placeholder="Write a brief message" required></textarea>
        </div>

        <div class="form-row">
            <label for="tags">Tags (optional)</label>
            <input id="tags" name="tags" type="text" placeholder="e.g. Collaboration, Innovation" />
            <small class="muted">Comma-separated</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Send Kudos</button>
            <a class="btn btn-ghost" href="/kudos/feed.php">Cancel</a>
        </div>

        <div id="form-error" class="alert alert-error" style="display:none;"></div>
        <div id="form-success" class="alert alert-success" style="display:none;"></div>
    </form>
</main>

<?php
if (file_exists(__DIR__ . '/../_partials/footer.php')) {
    include_once __DIR__ . '/../_partials/footer.php';
}
?>

<script>
(function(){
    const form = document.getElementById('give-kudos-form');
    const receiverInput = document.getElementById('receiver');
    const receiverIdInput = document.getElementById('receiver_id');
    const suggestions = document.getElementById('receiver-suggestions');
    const errorEl = document.getElementById('form-error');
    const successEl = document.getElementById('form-success');

    let suggestionTimer = null;

    receiverInput.addEventListener('input', () => {
        const q = receiverInput.value.trim();
        if (suggestionTimer) clearTimeout(suggestionTimer);
        if (!q || q.length < 2) {
            suggestions.style.display = 'none';
            return;
        }
        suggestionTimer = setTimeout(() => fetchSuggestions(q), 250);
    });

    function fetchSuggestions(q){
        fetch(`/api/users/search.php?q=${encodeURIComponent(q)}`, {credentials: 'same-origin'})
          .then(r => r.json()).then(data => {
              if (!data || !Array.isArray(data.items)) return;
              suggestions.innerHTML = '';
              data.items.slice(0,6).forEach(u => {
                  const div = document.createElement('div');
                  div.className = 'suggestion';
                  div.textContent = u.name + ' (' + u.email + ')';
                  div.dataset.id = u.id;
                  div.dataset.name = u.name;
                  div.addEventListener('click', () => {
                      receiverInput.value = u.name + ' ('+u.email+')';
                      receiverIdInput.value = u.id;
                      suggestions.style.display = 'none';
                  });
                  suggestions.appendChild(div);
              });
              suggestions.style.display = data.items.length ? 'block' : 'none';
          }).catch(err => {
              console.error(err);
          });
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        errorEl.style.display = 'none';
        successEl.style.display = 'none';

        const receiver_id = receiverIdInput.value || '';
        const message = document.getElementById('message').value.trim();
        const tags = document.getElementById('tags').value.split(',').map(t => t.trim()).filter(Boolean);

        if (!receiver_id) {
            errorEl.textContent = 'Please select a valid recipient from suggestions';
            errorEl.style.display = 'block';
            return;
        }

        const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : null;

        fetch('/api/kudos/give.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(csrf ? {'X-CSRF-Token': csrf} : {})
            },
            body: JSON.stringify({receiver_id, message, tags})
        }).then(r => r.json()).then(data => {
            if (data && data.success) {
                successEl.textContent = 'Kudos sent successfully!';
                successEl.style.display = 'block';
                form.reset();
                receiverIdInput.value = '';
            } else {
                errorEl.textContent = data && data.message ? data.message : 'Failed to send kudos';
                errorEl.style.display = 'block';
            }
        }).catch(err => {
            console.error(err);
            errorEl.textContent = 'Failed to send kudos';
            errorEl.style.display = 'block';
        });
    });
})();
</script>
</body>
</html>