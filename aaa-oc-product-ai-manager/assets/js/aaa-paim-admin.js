/**
 * File: /wp-content/plugins/aaa-product-ai-manager/assets/js/aaa-paim-admin.js
 * Purpose: Admin UX helpers (filtering, mode switch only; AI moved to aaa-paim-ai.js)
 * Version: 0.5.4
 */
(function($){
  // ---- Per-file debug (inherits from localized AAA_PAIM.debug if present) ----
  const DEBUG_THIS_FILE = true;
  const DEBUG = (window && window.AAA_PAIM && typeof window.AAA_PAIM.debug !== 'undefined')
    ? !!window.AAA_PAIM.debug
    : DEBUG_THIS_FILE;

  function log(){
    if (!DEBUG || !window.console) return;
    var args = Array.prototype.slice.call(arguments);
    args.unshift('[AAA-PAIM]');
    console.log.apply(console, args);
  }
  function warn(){
    if (!window.console) return;
    var args = Array.prototype.slice.call(arguments);
    args.unshift('[AAA-PAIM][WARN]');
    console.warn.apply(console, args);
  }

  // ----------------------------
  // Attribute filter boxes (search-as-you-type)
  // ----------------------------
  $(document).on('input', '.aaa-paim-filter', function(){
    try {
      const q = String($(this).val() || '').toLowerCase();
      const $box = $(this).closest('.aaa-paim-box');
      $box.find('.aaa-paim-item').each(function(){
        $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
      });
    } catch(e){ warn('[filter] error:', e); }
  });

  // ----------------------------
  // Products tab: auto-submit when switching Existing/New radio
  // ----------------------------
  $(document).on('change', 'input[name="mode"]', function(){
    try {
      const $form = $(this).closest('form');
      if ($form.length) {
        log('[mode] switching to:', this.value);
        $form.trigger('submit');
      }
    } catch(e){ warn('[mode] error:', e); }
  });

  log('admin JS loaded v0.5.4');
})(jQuery);
