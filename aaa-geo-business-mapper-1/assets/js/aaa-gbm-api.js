// AJAX wrapper for AAA Geo Business Mapper. Provides a single function to
// call admin‑ajax.php with appropriate nonce. Handles JSON parsing and
// error logging.

(function () {
  const DEBUG_THIS_FILE = true;
  function log(msg, obj) {
    if (!DEBUG_THIS_FILE) return;
    console.log('[AAA_GBM/api]', msg, obj || '');
  }
  /**
   * Call an AJAX action on the WordPress server. Returns a promise that
   * resolves to the parsed JSON or rejects on network/parse errors.
   * If the response contains success:false, the promise resolves with
   * the response. The caller should handle success property.
   */
  function ajax(action, payload) {
    payload.action = action;
    payload.nonce  = AAA_GBM_CFG.nonce;
    return fetch(AAA_GBM_CFG.ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams(payload).toString(),
    })
      .then((r) => {
        if (!r.ok) {
          throw new Error('HTTP error ' + r.status);
        }
        return r.json();
      })
      .catch((err) => {
        log('AJAX exception', err);
        return { success: false, data: { message: err.message } };
      });
  }
  window.AAA_GBM.ajax = ajax;
})();