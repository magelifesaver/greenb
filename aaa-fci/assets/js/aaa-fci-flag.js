// File Path: /wp-content/plugins/aaa-fci/assets/js/aaa-fci-flag.js
// Purpose: Scope CSS by flagging only the Fast Cart iframe document.
(function(){
  try {
    // Only set the flag if we're truly inside an iframe.
    if (window.self !== window.top) {
      document.documentElement.setAttribute('data-aaa-fci', 'fastcart');
    }
  } catch (e) {
    // In some browsers, cross-origin check might throw; fail closed (no flag).
  }
})();
