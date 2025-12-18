// File Path: /wp-content/plugins/aaa-fci/assets/js/aaa-fci-flag.js
// Purpose: Flag ONLY the Woo Fast Cart iframe so iframe-cleanup CSS does not affect other iframes (Customizer preview).

(function () {
  try {
    // Must be inside an iframe.
    if (window.self === window.top) return;

    // --- Do NOT run inside WP Theme Customizer preview iframe ---
    // Customizer preview URLs usually contain these query params.
    var q = (window.location && window.location.search) ? window.location.search : '';
    if (
      q.indexOf('customize_changeset_uuid=') !== -1 ||
      q.indexOf('customize_theme=') !== -1 ||
      q.indexOf('customize_messenger_channel=') !== -1 ||
      q.indexOf('customize_autosaved=') !== -1 ||
      q.indexOf('customize_preview_nonce=') !== -1
    ) {
      return;
    }

    // Also common body class in preview.
    if (document.body && document.body.classList && document.body.classList.contains('customize-support')) {
      return;
    }

    // --- Only flag if this iframe looks like Fast Cart ---
    var fe = window.frameElement;
    var feId = fe && fe.id ? String(fe.id).toLowerCase() : '';
    var feName = fe && fe.name ? String(fe.name).toLowerCase() : '';
    var feClass = fe && fe.className ? String(fe.className).toLowerCase() : '';
    var url = (window.location && window.location.href) ? String(window.location.href).toLowerCase() : '';

    // Heuristics: frame element or URL contains common fast-cart tokens OR DOM contains WFC markers.
    var looksByFrame =
      feId.indexOf('wfc') !== -1 || feId.indexOf('fast') !== -1 ||
      feName.indexOf('wfc') !== -1 || feName.indexOf('fast') !== -1 ||
      feClass.indexOf('wfc') !== -1 || feClass.indexOf('fast') !== -1;

    var looksByUrl =
      url.indexOf('wfc') !== -1 || url.indexOf('fastcart') !== -1 || url.indexOf('fast-cart') !== -1;

    var looksByDom = !!document.querySelector(
      '[class*="wfc"], [id*="wfc"], [data-wfc], [class*="fastcart"], [id*="fastcart"], [data-fastcart]'
    );

    if (!looksByFrame && !looksByUrl && !looksByDom) {
      return;
    }

    document.documentElement.setAttribute('data-aaa-fci', 'fastcart');
  } catch (e) {
    // Fail closed (no flag) so we do not hide headers in unknown iframes.
  }
})();
