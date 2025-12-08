/**
 * File: /wp-content/plugins/aaa-attr-term-importer/assets/js/aaa-attr-term-importer.js
 * Purpose: Small UX helpers for the Attribute Term Importer (line count, quick cleanup)
 * Version: 0.1.2
 */
(function($){
  var DEBUG = true;

  function updateCount() {
    try {
      var $ta = $('#lines');
      if (!$ta.length) return;
      var raw = String($ta.val() || '');
      var lines = raw.split(/\r\n|\r|\n/).map(function(s){ return s.trim(); }).filter(Boolean);
      $('#aaa-attr-linecount').text(lines.length ? (lines.length + ' lines detected') : '');
    } catch(e){ if (DEBUG && window.console) console.warn('[AAA-ATTR][count]', e); }
  }

  $(document).on('input', '#lines', updateCount);
  $(document).ready(updateCount);

})(jQuery);
