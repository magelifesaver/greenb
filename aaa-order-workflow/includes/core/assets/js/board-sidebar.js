/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/assets/js/board-sidebar.js
 * Purpose: Mount inside existing sidebar only (no auto-create).
 * Version: 1.0.1
 */
(function () {
  const DEBUG_THIS_FILE = true;
  function log(...a){ if(DEBUG_THIS_FILE) console.log('[AAA-OC][Sidebar]', ...a); }

  // Try common selectors you already use; do NOT create new elements
  function findSidebarRoot() {
    return (
      document.querySelector('[data-aaa-oc-sidebar]') ||
      document.querySelector('#aaa-oc-sidebar') ||
      document.querySelector('.aaa-oc-sidebar')
    );
  }

  // Count cards per column (unchanged)
  function collectSummary() {
    const out = {};
    const columns = document.querySelectorAll('[data-status-column]');
    if (columns.length) {
      columns.forEach(col => {
        const status = col.getAttribute('data-status-column') || 'unknown';
        const count = col.querySelectorAll('.oc-card, [data-order-card]').length;
        out[status] = (out[status] || 0) + count;
      });
    } else {
      document.querySelectorAll('.board-column').forEach(col => {
        const head = (col.querySelector('h3,h2,.column-title') || {}).textContent || 'unknown';
        const status = head.trim().toLowerCase().replace(/\s+/g,'-');
        const count = col.querySelectorAll('.oc-card, [data-order-card]').length;
        out[status] = (out[status] || 0) + count;
      });
    }
    return out;
  }

  function renderSummary(root) {
    const summary = collectSummary();
    const keys = Object.keys(summary);
    const rows = keys.length
      ? keys.map(k => `<div class="sb-row"><span class="sb-k">${k.toUpperCase()}</span><span class="sb-v">${summary[k]}</span></div>`).join('')
      : '<div class="sb-empty">No orders visible.</div>';

    const box = root.querySelector('[data-section="summary"]') || (()=>{
      const s = document.createElement('section');
      s.className='sb-box'; s.setAttribute('data-section','summary');
      root.appendChild(s); return s;
    })();
    box.innerHTML = `<div class="sb-h">Summary</div>${rows}`;
  }

  function ensurePrefsMount(root) {
    let box = root.querySelector('[data-section="prefs"]');
    if (!box) {
      box = document.createElement('section');
      box.className='sb-box'; box.setAttribute('data-section','prefs');
      box.innerHTML = `<div class="sb-h">Preferences</div><div data-aaa-oc-prefs></div>`;
      root.appendChild(box);
      document.dispatchEvent(new CustomEvent('aaa-oc:prefs:mount'));
    }
  }

  function ensureFeedMount(root) {
    let box = root.querySelector('[data-section="pcfeed"]');
    if (!box) {
      box = document.createElement('section');
      box.className='sb-box'; box.setAttribute('data-section','pcfeed');
      box.innerHTML = `<div class="sb-h">Payment Confirmations</div><div data-aaa-oc-feed></div>`;
      root.appendChild(box);
      document.dispatchEvent(new CustomEvent('aaa-oc:feed:mount'));
    }
  }

  function init() {
    const root = findSidebarRoot();
    if (!root) { log('No existing sidebar root found; aborting.'); return; }
    if (root.dataset.sidebarMounted === '1') { log('Already mounted; skipping.'); return; }
    root.dataset.sidebarMounted = '1';

    // Do not wipe user’s markup; only (idempotently) draw our blocks
    renderSummary(root);
    ensurePrefsMount(root);
    ensureFeedMount(root);

    // expose refresh hook for other modules
    window.AAA_OC = window.AAA_OC || {};
    window.AAA_OC.sidebar = window.AAA_OC.sidebar || {};
    window.AAA_OC.sidebar.refreshSummary = () => renderSummary(root);

    log('mounted into existing sidebar');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else { init(); }

  document.addEventListener('aaa-oc:board:refreshed', () => {
    const root = findSidebarRoot();
    if (root && window.AAA_OC?.sidebar?.refreshSummary) window.AAA_OC.sidebar.refreshSummary();
  });
})();
