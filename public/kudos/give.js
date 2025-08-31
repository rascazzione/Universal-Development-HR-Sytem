/* Client-side JS to give kudos:
   - Wires a kudos form (id/name flexible) to POST to /api/kudos/give.php
   - Implements optimistic UI (creates a pending kudos item in the feed)
   - Handles errors and reverts optimistic changes if the request fails
   - Looks for CSRF token in: form input[name="csrf_token"] OR meta[name="csrf-token"]
   - Expects the server to return JSON: { success: true, kudos: { id, from, to, message, points, created_at } }
*/

/* Utility: show a temporary toast message */
function kudosShowToast(message, type = 'info', timeout = 4000) {
  const existing = document.getElementById('kudos-toast-container');
  let container = existing;
  if (!container) {
    container = document.createElement('div');
    container.id = 'kudos-toast-container';
    container.style.position = 'fixed';
    container.style.right = '16px';
    container.style.top = '16px';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = 'kudos-toast kudos-toast-' + type;
  toast.style.marginTop = '8px';
  toast.style.padding = '10px 14px';
  toast.style.borderRadius = '6px';
  toast.style.background = type === 'error' ? '#ffdddd' : type === 'success' ? '#ddffdd' : '#ffffff';
  toast.style.color = '#111';
  toast.style.boxShadow = '0 2px 6px rgba(0,0,0,0.12)';
  toast.textContent = message;

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.transition = 'opacity 220ms';
    toast.style.opacity = '0';
    setTimeout(() => { toast.remove(); }, 220);
  }, timeout);
}

/* Create a DOM node representing a kudos entry (from server data) */
function createKudosElement(kudos, opts = {}) {
  // kudos: { id, from, to, message, points, created_at }
  const wrapper = document.createElement('div');
  wrapper.className = 'kudos-item';
  if (opts.pending) wrapper.classList.add('kudos-pending');

  wrapper.dataset.kudosId = kudos.id || '';
  const header = document.createElement('div');
  header.className = 'kudos-header';
  header.style.fontWeight = '600';
  header.style.marginBottom = '6px';
  header.textContent = (kudos.from ? kudos.from : 'You') + ' → ' + (kudos.to ? kudos.to : 'someone') + ` • ${kudos.points || 0} pts`;

  const body = document.createElement('div');
  body.className = 'kudos-body';
  body.textContent = kudos.message || '';

  const footer = document.createElement('div');
  footer.className = 'kudos-footer';
  footer.style.fontSize = '12px';
  footer.style.color = '#666';
  footer.style.marginTop = '6px';
  footer.textContent = kudos.created_at ? new Date(kudos.created_at).toLocaleString() : (opts.pending ? 'Sending...' : '');

  wrapper.appendChild(header);
  wrapper.appendChild(body);
  wrapper.appendChild(footer);

  if (opts.pending) {
    // Add a subtle spinner / indicator
    const spinner = document.createElement('span');
    spinner.className = 'kudos-spinner';
    spinner.style.display = 'inline-block';
    spinner.style.marginLeft = '8px';
    spinner.style.width = '12px';
    spinner.style.height = '12px';
    spinner.style.border = '2px solid rgba(0,0,0,0.15)';
    spinner.style.borderTopColor = 'rgba(0,0,0,0.6)';
    spinner.style.borderRadius = '50%';
    spinner.style.animation = 'kudos-spin 1s linear infinite';
    footer.appendChild(spinner);

    // minimal keyframes injection if not present
    if (!document.getElementById('kudos-spin-style')) {
      const style = document.createElement('style');
      style.id = 'kudos-spin-style';
      style.textContent = '@keyframes kudos-spin { from { transform: rotate(0deg) } to { transform: rotate(360deg) } }';
      document.head.appendChild(style);
    }
  }

  return wrapper;
}

/* Helper: find csfr token */
function findCsrfToken(form) {
  if (!form) return null;
  const input = form.querySelector('input[name="csrf_token"], input[name="_csrf"], input[name="csrf"]');
  if (input && input.value) return input.value;
  const meta = document.querySelector('meta[name="csrf-token"], meta[name="csrf-token"]');
  if (meta && meta.content) return meta.content;
  return null;
}

/* Wire the kudos form to the API and feed */
function wireKudosForm(options = {}) {
  const form = document.querySelector(options.formSelector || '#kudos-give-form') ||
               document.querySelector('form[name="kudos-give"]') ||
               document.querySelector('form.kudos-give');

  if (!form) {
    console.warn('Kudos: no form found for selector', options.formSelector || '#kudos-give-form');
    return;
  }

  const feed = document.querySelector(options.feedSelector || '#kudos-feed') || document.querySelector('.kudos-feed');
  // button & inputs
  const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
  const recipientInput = form.querySelector('select[name="recipient_id"], input[name="recipient_id"], select[name="to"], input[name="to"]');
  const messageInput = form.querySelector('textarea[name="message"], input[name="message"]');
  const pointsInput = form.querySelector('select[name="points"], input[name="points"]');

  form.addEventListener('submit', async function (ev) {
    ev.preventDefault();

    // basic validation
    const recipientVal = recipientInput ? recipientInput.value : null;
    const messageVal = messageInput ? messageInput.value.trim() : '';
    const pointsVal = pointsInput ? parseInt(pointsInput.value, 10) || 0 : 0;

    if (!recipientVal) {
      kudosShowToast('Please select a recipient.', 'error');
      return;
    }
    if (!messageVal) {
      kudosShowToast('Please enter a message.', 'error');
      return;
    }

    // collect csrf
    const csrfToken = findCsrfToken(form);

    // prepare payload
    const payload = {
      recipient_id: isNaN(Number(recipientVal)) ? recipientVal : Number(recipientVal),
      message: messageVal,
      points: pointsVal
    };
    if (csrfToken) payload.csrf_token = csrfToken;

    // disable submit button
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.dataset.origText = submitBtn.textContent;
      submitBtn.textContent = 'Sending...';
    }

    // optimistic UI: create a temporary kudos element and insert at top of feed (if feed exists)
    let tempEl = null;
    if (feed) {
      const pendingKudos = {
        id: 'temp-' + Date.now() + '-' + Math.floor(Math.random() * 1000),
        from: options.currentUserName || 'You',
        to: recipientInput ? (recipientInput.options ? (recipientInput.options[recipientInput.selectedIndex] ? recipientInput.options[recipientInput.selectedIndex].text : recipientVal) : recipientVal) : 'recipient',
        message: messageVal,
        points: pointsVal,
        created_at: new Date().toISOString()
      };
      tempEl = createKudosElement(pendingKudos, { pending: true });
      // insert at top
      feed.insertBefore(tempEl, feed.firstChild);
    }

    // attempt network request
    try {
      const res = await fetch(options.apiPath || '/api/kudos/give.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
      });

      if (!res.ok) {
        // read body for error message when possible
        let text = await res.text();
        let msg = 'Failed to send kudos.';
        try {
          const json = JSON.parse(text);
          if (json && json.error) msg = json.error;
        } catch (e) {
          if (text) msg = text;
        }
        throw new Error(msg);
      }

      const data = await res.json();
      if (!data || !data.success) {
        const err = (data && data.error) ? data.error : 'Server returned an error while sending kudos.';
        throw new Error(err);
      }

      // success: replace temp element with actual data if feed exists
      if (feed) {
        const newEl = createKudosElement(data.kudos || {
          id: data.kudos_id || data.id,
          from: data.kudos && data.kudos.from ? data.kudos.from : options.currentUserName || 'You',
          to: data.kudos && data.kudos.to ? data.kudos.to : (recipientInput ? recipientInput.value : ''),
          message: messageVal,
          points: pointsVal,
          created_at: (data.kudos && data.kudos.created_at) || new Date().toISOString()
        });
        if (tempEl && tempEl.parentNode) {
          tempEl.parentNode.replaceChild(newEl, tempEl);
        } else {
          feed.insertBefore(newEl, feed.firstChild);
        }
      }

      kudosShowToast('Kudos sent!', 'success');
      // clear message input
      if (messageInput) messageInput.value = '';
      // optionally reset points to default 0 or first option
      if (pointsInput && pointsInput.tagName === 'SELECT') pointsInput.selectedIndex = 0;

    } catch (err) {
      // revert optimistic UI
      if (tempEl && tempEl.parentNode) tempEl.remove();
      kudosShowToast(err.message || 'Failed to send kudos. Please try again.', 'error');
      console.error('Kudos send error:', err);
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        if (submitBtn.dataset.origText) submitBtn.textContent = submitBtn.dataset.origText;
      }
    }
  });
}

/* Auto-wire on DOMContentLoaded unless explicitly disabled */
document.addEventListener('DOMContentLoaded', function () {
  // Allow pages to override options by attaching window.KUDOS_GIVE_OPTIONS
  const options = window.KUDOS_GIVE_OPTIONS || {};
  wireKudosForm(options);
});