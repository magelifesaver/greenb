// File Path: /wp-content/plugins/aaa-geo-business-mapper/assets/js/aaa-gbm-api.js
(function () {
  const DEBUG_THIS_FILE = true;
  function log(msg, obj) { if (DEBUG_THIS_FILE) console.log("[AAA_GBM/api]", msg, obj || ""); }

  window.AAA_GBM.ajax = function ajax(action, payload) {
    payload.action = action;
    payload.nonce = AAA_GBM_CFG.nonce;

    return fetch(AAA_GBM_CFG.ajaxUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body: new URLSearchParams(payload).toString(),
    })
      .then((r) => r.json())
      .catch((e) => {
        log("fetch error", e);
        return { success: false, data: { message: String(e) } };
      });
  };
})();
