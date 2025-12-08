/**
 * File: /wp-content/plugins/aaa-api-auditor/assets/js/aaa-api-auditor-admin.js
 * Purpose: Lightweight console hints for the auditor admin page.
 * Version: 1.0.0
 */
(function($){
  const DEBUG_THIS_FILE = true;
  const log = (...a)=>{ if(DEBUG_THIS_FILE) console.log('[AAA-API-AUDITOR]', ...a); };
  $(document).ready(function(){
    log('Admin page ready. Use "Run Scan" after saving any auth changes.');
  });
})(jQuery);
