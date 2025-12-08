/**
 * File: /wp-content/plugins/aaa-product-ai-manager/assets/js/aaa-paim-ai.js
 * Purpose: AI-related admin handlers (Verify API, Run AI Now)
 * Version: 0.5.3
 */
(function($){
  // ---- Per-file debug (inherits from localized AAA_PAIM.debug if present) ----
  const DEBUG_THIS_FILE = true;
  const DEBUG = (window && window.AAA_PAIM && typeof window.AAA_PAIM.debug !== 'undefined')
    ? !!window.AAA_PAIM.debug : DEBUG_THIS_FILE;

  function getAjaxUrl(){
    if (window.AAA_PAIM && AAA_PAIM.ajax_url) return AAA_PAIM.ajax_url;
    if (typeof window.ajaxurl !== 'undefined') return window.ajaxurl;
    return '/wp-admin/admin-ajax.php';
  }
    function getNonce(){
      // Prefer the fresh hidden input on the page
      var dom = document.getElementById('aaa-paim-nonce');
      if (dom && dom.value) return dom.value;
      // Fallback to localized value
      return (window.AAA_PAIM && AAA_PAIM.nonce) ? AAA_PAIM.nonce : '';
    }
  function log(){ if (DEBUG && window.console) console.log.apply(console, ['[AAA-PAIM][AI]'].concat([].slice.call(arguments))); }
  function warn(){ if (window.console) console.warn.apply(console, ['[AAA-PAIM][AI][WARN]'].concat([].slice.call(arguments))); }

  // ----------------------------
  // Global tab: Verify OpenAI API key
  // ----------------------------
  $(document).on('click', '#aaa-paim-verify-api', function(e){
    e.preventDefault();
    const $btn = $(this);
    const $res = $('#aaa-paim-verify-result');

    const payload = {
      action: 'aaa_paim_verify_openai',
      nonce:  getNonce(),
      api_key: ($('#aaa_paim_openai_api_key').val() || '').trim() // optional; server uses saved key if blank
    };

    const url = getAjaxUrl();
    $btn.prop('disabled', true);
    $res.text((AAA_PAIM.i18n && AAA_PAIM.i18n.verifying) ? AAA_PAIM.i18n.verifying : 'Verifying…');

    $.post(url, payload)
      .done(function(r){
        if (r && r.success) {
          $res.text((AAA_PAIM.i18n && AAA_PAIM.i18n.verified) ? AAA_PAIM.i18n.verified : 'Verified').css('color','#2271b1');
        } else {
          const msg = r && r.data && r.data.message ? r.data.message : ((AAA_PAIM.i18n && AAA_PAIM.i18n.failed) ? AAA_PAIM.i18n.failed : 'Verification failed');
          $res.text(msg).css('color','#d63638');
        }
      })
      .fail(function(xhr){
        $res.text('Verification failed (' + (xhr.status||'') + ')').css('color','#d63638');
      })
      .always(function(){ $btn.prop('disabled', false); });
  });

  // ----------------------------
  // Products tab: Run AI Now
  // ----------------------------
  $(document).on('click', '#aaa-paim-run-ai', function(e){
    e.preventDefault();

    const $btn = $(this);
    const $res = $('#aaa-paim-ai-result');
    const productId = $btn.data('product');
    const setId     = $btn.data('set');

    if (!productId || !setId) {
      $res.text('Missing product or set.').css('color','#d63638');
      warn('missing product/set', { productId, setId });
      return;
    }

    // 1) Gather explicit AI-marked items
    let aiItems = [];
    $('input[type=checkbox][name^="ai["]:checked').each(function(){
      aiItems.push( this.name.replace(/^ai\[/,'').replace(/\]$/,'') ); // e.g. "taxonomy:pa_flavor" or "meta:net_weight"
    });

    // 2) If none checked, auto-infer 'missing' fields (empty UI values)
    if (!aiItems.length) {
      $('select[name^="tax["]').each(function(){
        const val = $(this).val();
        const has = val && (Array.isArray(val) ? val.length : String(val).length);
        if (!has) {
          const k = this.name.replace(/^tax\[/,'').replace(/\]\[\]$/,'').replace(/\]$/,'');
          aiItems.push('taxonomy:' + k);
        }
      });
      $('input[type=text][name^="meta["]').each(function(){
        const v = (this.value || '').trim();
        if (!v) {
          const k = this.name.replace(/^meta\[/,'').replace(/\]$/,'');
          aiItems.push('meta:' + k);
        }
      });
    }

    if (!aiItems.length) {
      $res.text('Nothing to fill: tick “Request AI” or leave some fields empty.').css('color','#d63638');
      return;
    }

    // 3) Also send the Source URLs textarea so a pre-save isn’t required
    const sources = ($('textarea[name="source_urls"]').length ? $('textarea[name="source_urls"]').val() : '').trim();

    const payload = {
      action:     'aaa_paim_run_ai',
      nonce:      getNonce(),
      product_id: productId,
      set_id:     setId,
      ai_items:   aiItems,
      source_urls: sources
    };

    const url = getAjaxUrl();
    $btn.prop('disabled', true);
    $res.text('Running…').css('color','#2271b1');
    log('POST', { url, payloadCount: aiItems.length });

    $.ajax({ url: url, method: 'POST', data: payload })
      .done(function(r){
      if (r && r.success) {
        const applied = r.data && r.data.result && r.data.result.applied ? r.data.result.applied.join(', ') : 'OK';
        $res
          .text('AI applied: ' + applied + ' — review values and refresh when ready.')
          .css('color','#2271b1')
          .attr('data-sticky','1'); // persist
        // Tip: manually refresh the page to see updated fields, or click Edit Product to verify.
      } else {
        const msg = (r && r.data && r.data.message) ? String(r.data.message) : 'AI failed';
        $res
          .text('AI failed: ' + msg)
          .css('color','#d63638')
          .attr('data-error','1');
        if (window.console) console.error('[AAA-PAIM][AI] server error:', msg, r);
        // alert('AI failed: ' + msg); // optional during testing
      }
      })
      .fail(function(xhr){
        let msg = 'AI failed (' + (xhr.status||'') + ')';
        if (xhr.status === 404) msg += ' — admin-ajax.php not reachable.';
        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
          msg += ' ' + xhr.responseJSON.data.message;
        }
        $res.text(msg).css('color','#d63638');
        warn('xhr fail', xhr);
      })
      .always(function(){ $btn.prop('disabled', false); });
  });

  if (DEBUG && console) console.log('[AAA-PAIM][AI] js loaded v0.5.3');
})(jQuery);
