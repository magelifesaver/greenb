;(function ($) {
  'use strict';


  const CONFIG = [
    { key: 'aaaOC_hideCompleted', slug: 'wc-completed', label: 'Completed' },
    { key: 'aaaOC_hideScheduled', slug: 'wc-scheduled', label: 'Scheduled' }
  ];

  const isHidden = (key) => localStorage.getItem(key) === '1';
  const setHidden = (key, val) => localStorage.setItem(key, val ? '1' : '0');

  function injectCssOnce() {
    if (document.getElementById('aaa-oc-hide-columns-style')) return;
    const style = document.createElement('style');
    style.id = 'aaa-oc-hide-columns-style';
    style.textContent = `
      .aaa-oc-column.aaa-oc-hidden { display: none !important; }
      .aaa-oc-header-tools {
        float: right; display: inline-flex; gap: .5rem; align-items: center;
      }
      .aaa-oc-toggle-col { cursor: pointer; }
      @media (max-width: 900px){
        .aaa-oc-header-tools { float: none; display: inline-block; margin-left: .75rem; }
      }
    `;
    document.head.appendChild(style);
  }

  function ensureHeaderButtons() {
    const $h1 = $('.wrap h1').first();
    if (!$h1.length) return;

    let $tools = $('.aaa-oc-header-tools');
    if (!$tools.length) {
      $tools = $('<span class="aaa-oc-header-tools"></span>');
      $h1.append($tools);
    }

    $tools.empty();
    CONFIG.forEach(({ key, label }) => {
      const $btn = $(`
        <button type="button"
                class="button button-modern aaa-oc-toggle-col"
                data-key="${key}">
          ${isHidden(key) ? 'Show ' + label : 'Hide ' + label}
        </button>
      `);
      $tools.append($btn);
    });
  }

  function updateHeaderButtonLabels() {
    $('.aaa-oc-toggle-col').each(function () {
      const key = this.getAttribute('data-key');
      const cfg = CONFIG.find(c => c.key === key);
      if (!cfg) return;
      $(this).text(isHidden(key) ? 'Show ' + cfg.label : 'Hide ' + cfg.label);
    });
  }

  function getColumnBySlug(slug) {
    const btn = document.querySelector(
      '#aaa-oc-board-columns .aaa-oc-column .aaa-oc-sort-toggle[data-status-slug="' + slug + '"]'
    );
    return btn ? btn.closest('.aaa-oc-column') : null;
  }

  function applyHiddenState() {
    CONFIG.forEach(({ key, slug }) => {
      const col = getColumnBySlug(slug);
      if (!col) return;
      if (isHidden(key)) col.classList.add('aaa-oc-hidden');
      else col.classList.remove('aaa-oc-hidden');
    });
    updateHeaderButtonLabels();
  }

  function handleToggleClick(e) {
    const key = e.currentTarget.getAttribute('data-key');
    if (!key) return;
    setHidden(key, !isHidden(key));
    applyHiddenState();
  }

  function setupReapplyHooks() {
    const board = document.getElementById('aaa-oc-board-columns');
    if (board) {
      const mo = new MutationObserver(() => applyHiddenState());
      mo.observe(board, { childList: true, subtree: false });
    }
    $(document).ajaxSuccess(function (_evt, _xhr, settings) {
      try {
        if (typeof settings.data === 'string' &&
            settings.data.indexOf('action=aaa_oc_get_latest_orders') !== -1) {
          applyHiddenState();
        }
      } catch (_) {}
    });
  }

  $(document).ready(function () {
    injectCssOnce();
    ensureHeaderButtons();
    applyHiddenState();
    setupReapplyHooks();

    $(document).on('click', '.aaa-oc-toggle-col', handleToggleClick);
  });

})(jQuery);
