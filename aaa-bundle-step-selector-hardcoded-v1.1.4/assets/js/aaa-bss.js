(function($){
  var LS_KEY = 'aaa_bss_last_selection_v1';

  function sumGroup($group){
    var sum = 0;
    $group.find('.aaa-bss-row input[type="number"]').each(function(){
      var v = parseInt($(this).val(), 10);
      if (!isNaN(v) && v > 0) sum += v;
    });
    return sum;
  }

  function enforceMax($group){
    var max = parseInt($group.data('max'), 10);
    if (isNaN(max)) max = 0;

    var sum = sumGroup($group);
    if (sum <= max) return;

    var $active = $(document.activeElement);
    if ($active && $active.length && $active.closest('.aaa-bss-list')[0] === $group[0]) {
      var cur = parseInt($active.val(), 10);
      if (isNaN(cur)) cur = 0;
      $active.val(Math.max(0, cur - (sum - max)));
    } else {
      var $last = $group.find('.aaa-bss-row input[type="number"]').last();
      var cur2 = parseInt($last.val(), 10);
      if (isNaN(cur2)) cur2 = 0;
      $last.val(Math.max(0, cur2 - (sum - max)));
    }
  }

  function collect($root){
    var out = { step1:{}, step2:{} };
    $root.find('.aaa-bss-list').each(function(){
      var $list = $(this);
      var group = $list.data('group');
      $list.find('.aaa-bss-row').each(function(){
        var $row = $(this);
        if ($row.hasClass('aaa-bss-row-missing')) return;

        var pid = parseInt($row.data('product-id'), 10);
        var qty = parseInt($row.find('input').val(), 10);
        if (!pid || isNaN(qty) || qty <= 0) return;
        out[group][pid] = qty;
      });
    });
    return out;
  }

  function saveSelection(payload){
    try { localStorage.setItem(LS_KEY, JSON.stringify(payload)); } catch(e){}
  }

  function loadSelection(){
    try {
      var raw = localStorage.getItem(LS_KEY);
      if (!raw) return null;
      var obj = JSON.parse(raw);
      return (obj && typeof obj === 'object') ? obj : null;
    } catch(e){ return null; }
  }

  function applySelection($root, payload){
    if (!payload) return;
    ['step1','step2'].forEach(function(group){
      if (!payload[group]) return;
      Object.keys(payload[group]).forEach(function(pid){
        var qty = parseInt(payload[group][pid], 10);
        if (!qty || qty < 0) qty = 0;
        var $row = $root.find('.aaa-bss-list[data-group="'+group+'"] .aaa-bss-row[data-product-id="'+pid+'"]');
        if ($row.length) $row.find('input[type="number"]').val(qty);
      });
    });
  }

  function showMsg($root, msg){
    var $m = $root.find('.aaa-bss-msg');
    $m.text(msg).show();
  }

  function hideMsg($root){
    $root.find('.aaa-bss-msg').hide();
  }

  function setFulfilledView($root, fulfilled){
    var $success = $root.find('.aaa-bss-success');
    var $form = $root.find('.aaa-bss-form');
    if (fulfilled) {
      $form.hide();
      $success.show();
    } else {
      $success.hide();
      $form.show();
    }
  }

  function openModal(){
    var $m = $('.aaa-bss-modal').first();
    if (!$m.length) return;
    $m.show().attr('aria-hidden','false');
    $('body').addClass('aaa-bss-modal-open');

    // On open: restore selections and fetch cart state.
    var $root = $m.find('.aaa-bss').first();
    applySelection($root, loadSelection());
    fetchStatus($root);
  }

  function closeModal(){
    var $m = $('.aaa-bss-modal').first();
    if (!$m.length) return;
    $m.hide().attr('aria-hidden','true');
    $('body').removeClass('aaa-bss-modal-open');
  }

  function applyFragments(fragments){
    if (!fragments) return;
    try {
      $.each(fragments, function(selector, html){
        if (!selector) return;
        var $existing = $(selector);
        if ($existing.length) $existing.replaceWith(html);
      });
    } catch(e){}
  }

  function triggerFastCart(fragments, cartHash){
    try { $(document.body).trigger('wc_fragments_refreshed'); } catch(e){}
    try {
      var $mock = $('<a/>')
        .attr('data-product_id', '0')
        .attr('data-quantity', '1')
        .addClass('add_to_cart_button');
      $(document.body).trigger('added_to_cart', [fragments || {}, cartHash || null, $mock]);
    } catch(e){}
    try { $(document.body).trigger('wc_fragment_refresh'); } catch(e){}

    if (window.AAA_BSS_FASTCART_OPEN && typeof window.AAA_BSS_FASTCART_OPEN === 'function') {
      try { window.AAA_BSS_FASTCART_OPEN(); } catch(e){}
    }
  }

  function fetchStatus($root){
    $.post(AAA_BSS.ajaxUrl, {
      action: 'aaa_bss_status',
      nonce: AAA_BSS.nonce
    }).done(function(resp){
      if (!resp || !resp.success) return;
      var st = (resp.data && resp.data.state) ? resp.data.state : null;
      if (!st) return;

      // Lock/unlock UI based on cart.
      setFulfilledView($root, !!st.promo_fulfilled);

      // If gift missing and promo main items exist, show reminder in modal too.
      if (st.gift_missing) {
        showMsg($root, 'Gift item missing: select your Step 2 item to qualify.');
      } else {
        hideMsg($root);
      }

      // Apply fragments so header + fastcart message area can update.
      applyFragments(resp.data.fragments || {});
    }).fail(function(){});
  }

  // Keep modal view in sync when cart fragments refresh (Fast Cart updates).
  $(document.body).on('wc_fragments_refreshed', function(){
    var $m = $('.aaa-bss-modal:visible').first();
    if (!$m.length) return;
    fetchStatus($m.find('.aaa-bss').first());
  });

  $(document).on('input', '.aaa-bss-list input[type="number"]', function(){
    enforceMax($(this).closest('.aaa-bss-list'));
    // Save as user types.
    var $root = $(this).closest('.aaa-bss');
    saveSelection(collect($root));
  });

  $(document).on('click', '.aaa-bss-banner-btn', function(e){
    e.preventDefault();
    openModal();
  });

  $(document).on('click', '.aaa-bss-close, .aaa-bss-modal-backdrop', function(){
    closeModal();
  });

  $(document).on('click', '.aaa-bss-next', function(){
    var $root = $(this).closest('.aaa-bss');
    var $g1 = $root.find('.aaa-bss-list[data-group="step1"]');
    var max1 = parseInt($g1.data('max'), 10);
    var sum1 = sumGroup($g1);

    if (sum1 < 1) { showMsg($root, 'Select at least 1 item in Step 1.'); return; }
    if (sum1 > max1) { showMsg($root, 'Step 1 exceeds maximum.'); return; }

    hideMsg($root);
    $root.find('.aaa-bss-step-1').hide();
    $root.find('.aaa-bss-step-2').show();
  });

  $(document).on('click', '.aaa-bss-back', function(){
    var $root = $(this).closest('.aaa-bss');
    hideMsg($root);
    $root.find('.aaa-bss-step-2').hide();
    $root.find('.aaa-bss-step-1').show();
  });

  $(document).on('click', '.aaa-bss-submit', function(){
    var $root = $(this).closest('.aaa-bss');

    // If already fulfilled, don't allow re-submit.
    if ($root.find('.aaa-bss-success:visible').length) {
      showMsg($root, 'Promotion is already in your cart.');
      return;
    }

    var $g1 = $root.find('.aaa-bss-list[data-group="step1"]');
    var $g2 = $root.find('.aaa-bss-list[data-group="step2"]');

    var sum1 = sumGroup($g1);
    var sum2 = sumGroup($g2);

    var max1 = parseInt($g1.data('max'), 10);
    var max2 = parseInt($g2.data('max'), 10);

    if (sum1 < 1) { showMsg($root, 'Select at least 1 item in Step 1.'); return; }
    if (sum1 > max1) { showMsg($root, 'Step 1 exceeds maximum.'); return; }

    // NEW: Step 2 required.
    if (sum2 < 1) { showMsg($root, 'Select 1 item in Step 2.'); return; }
    if (sum2 > max2) { showMsg($root, 'Step 2 exceeds maximum.'); return; }

    hideMsg($root);

    var payload = collect($root);
    saveSelection(payload);

    $.post(AAA_BSS.ajaxUrl, {
      action: 'aaa_bss_add_to_cart',
      nonce: AAA_BSS.nonce,
      payload: JSON.stringify(payload)
    }).done(function(resp){
      if (!resp || !resp.success) {
        showMsg($root, (resp && resp.data && resp.data.message) ? resp.data.message : 'Error adding to cart.');
        return;
      }

      applyFragments(resp.data.fragments || {});
      setFulfilledView($root, !!(resp.data.state && resp.data.state.promo_fulfilled));
      closeModal();
      triggerFastCart(resp.data.fragments || {}, resp.data.cart_hash || null);
    }).fail(function(){
      showMsg($root, 'Error adding to cart.');
    });
  });

})(jQuery);
