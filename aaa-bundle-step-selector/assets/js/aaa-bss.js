(function($){
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
      var over = sum - max;
      $active.val(Math.max(0, cur - over));
    } else {
      var $last = $group.find('.aaa-bss-row input[type="number"]').last();
      var cur2 = parseInt($last.val(), 10);
      if (isNaN(cur2)) cur2 = 0;
      var over2 = sum - max;
      $last.val(Math.max(0, cur2 - over2));
    }
  }

  function collect($root){
    var out = { step1:{}, step2:{} };
    $root.find('.aaa-bss-list').each(function(){
      var $list = $(this);
      var group = $list.data('group');
      $list.find('.aaa-bss-row').each(function(){
        var $row = $(this);
        var pid = parseInt($row.data('product-id'), 10);
        var qty = parseInt($row.find('input').val(), 10);
        if (!pid || isNaN(qty) || qty <= 0) return;
        out[group][pid] = qty;
      });
    });
    return out;
  }

  function showMsg($root, msg){
    var $m = $root.find('.aaa-bss-msg');
    $m.text(msg).show();
  }

  $(document).on('input', '.aaa-bss-list input[type="number"]', function(){
    enforceMax($(this).closest('.aaa-bss-list'));
  });

  $(document).on('click', '.aaa-bss-next', function(){
    var $root = $(this).closest('.aaa-bss');
    var $g1 = $root.find('.aaa-bss-list[data-group="step1"]');
    var max1 = parseInt($g1.data('max'), 10);
    var sum1 = sumGroup($g1);

    if (sum1 < 1) { showMsg($root, 'Select at least 1 item in Step 1.'); return; }
    if (sum1 > max1) { showMsg($root, 'Step 1 exceeds maximum.'); return; }

    $root.find('.aaa-bss-msg').hide();
    $root.find('.aaa-bss-step-1').hide();
    $root.find('.aaa-bss-step-2').show();
    $root.attr('data-step', '2');
  });

  $(document).on('click', '.aaa-bss-back', function(){
    var $root = $(this).closest('.aaa-bss');
    $root.find('.aaa-bss-msg').hide();
    $root.find('.aaa-bss-step-2').hide();
    $root.find('.aaa-bss-step-1').show();
    $root.attr('data-step', '1');
  });

  $(document).on('click', '.aaa-bss-submit', function(){
    var $root = $(this).closest('.aaa-bss');
    $root.find('.aaa-bss-msg').hide();

    var $g1 = $root.find('.aaa-bss-list[data-group="step1"]');
    var $g2 = $root.find('.aaa-bss-list[data-group="step2"]');
    var sum1 = sumGroup($g1);
    var sum2 = sumGroup($g2);
    var max1 = parseInt($g1.data('max'), 10);
    var max2 = parseInt($g2.data('max'), 10);

    if (sum1 < 1) { showMsg($root, 'Select at least 1 item in Step 1.'); return; }
    if (sum1 > max1) { showMsg($root, 'Step 1 exceeds maximum.'); return; }
    if (sum2 > max2) { showMsg($root, 'Step 2 exceeds maximum.'); return; }

    $.post(AAA_BSS.ajaxUrl, {
      action: 'aaa_bss_add_to_cart',
      nonce: AAA_BSS.nonce,
      payload: JSON.stringify(collect($root))
    }).done(function(resp){
      if (!resp || !resp.success) {
        showMsg($root, (resp && resp.data && resp.data.message) ? resp.data.message : 'Error adding to cart.');
        return;
      }
      window.location.href = resp.data.redirectUrl || '/cart/';
    }).fail(function(){
      showMsg($root, 'Error adding to cart.');
    });
  });

})(jQuery);
