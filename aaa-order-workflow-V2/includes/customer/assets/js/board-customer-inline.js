// File: /wp-content/plugins/aaa-order-workflow/includes/customer/assets/js/board-customer-inline.js
// Purpose: Open/close + inline save (AJAX) for Customer Warnings & Special Needs (with options).
(function ($) {
	'use strict';

	function moveToBody($el) {
		if (!$el.length) return;
		if ($el.parent()[0] !== document.body) { $el.appendTo('body'); }
	}

	$(document).on('click', '.aaa-oc-open-customer-modal', function (e) {
		e.preventDefault();
		var $btn   = $(this);
		var target = $btn.data('target');
		var $modal = $(target);
		if (!$modal.length) return;

		var orderId = $btn.data('order');
		if (orderId) { $modal.find('form.aaa-oc-customer-form').attr('data-order', orderId); }

		moveToBody($modal);
		$modal.fadeIn(150);
	});

	function closeModal($m) { $m.fadeOut(120); }

	$(document).on('click', '.aaa-oc-close-customer-modal', function (e) {
		e.preventDefault();
		closeModal($(this).closest('.aaa-oc-customer-modal'));
	});
	$(document).on('click', '.aaa-oc-customer-modal-overlay', function () {
		closeModal($(this).closest('.aaa-oc-customer-modal'));
	});

	// Submit (AJAX with querystringed action + nonce to dodge WAF rules)
	$(document).on('submit', 'form.aaa-oc-customer-form', function (e) {
		e.preventDefault();
		var $f    = $(this);
		var uid   = parseInt($f.data('user') || 0, 10);
		var oid   = parseInt($f.data('order') || 0, 10);
		var nonce = $f.data('nonce') || '';
		if (!uid || !nonce) { alert('Missing user or nonce.'); return; }

		// Collect fields
		var needs = [];
		$f.find('input[name="needs[]"]:checked').each(function(){ needs.push($(this).val()); });

		var warn_opts = [];
		$f.find('input[name="warn_opts[]"]:checked').each(function(){ warn_opts.push($(this).val()); });

		var warn_note = ($f.find('textarea[name="warn_note"]').val() || '').trim();
		var ban       = $f.find('input[name="warn_is_ban"]:checked').length ? 'yes' : 'no';
		var len       = $f.find('select[name="ban_length"]').val() || 'none';

		var payload = {
			user_id: uid,
			order_id: oid,
			needs: needs,
			warn_opts: warn_opts,
			warn_note: warn_note,
			warn_is_ban: ban,
			ban_length: len
		};

		// Build ajax URL with action + _ajax_nonce in querystring
		var base = (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
		var qs   = 'action=aaa_oc_save_customer_flags&_ajax_nonce=' + encodeURIComponent(nonce);
		var url  = base + (base.indexOf('?') === -1 ? '?' : '&') + qs;

		var $submit = $f.find('button[type="submit"]');
		$submit.prop('disabled', true).text('Saving…');

		$.ajax({
			url: url,
			type: 'POST',
			dataType: 'json',
			data: payload
		}).done(function(resp){
			if (!resp || !resp.success || !resp.data) {
				console.error('[CUSTOMER][AJAX] Save failed:', resp);
				alert('Save failed.');
				return;
			}
			// Update the two box bodies on the card
			var $modal = $f.closest('.aaa-oc-customer-modal');
			var $root  = $modal.prevAll('.aaa-oc-customer-info-left').first();
			if ($root.length) {
				$root.find('.aaa-oc-box-body[data-box="warnings"]').html(resp.data.warnings_html);
				$root.find('.aaa-oc-box-body[data-box="needs"]').html(resp.data.needs_html);
			}
			closeModal($modal);
		}).fail(function(xhr){
			console.error('[CUSTOMER][AJAX] HTTP error', xhr && xhr.status, xhr && xhr.responseText);
			alert('AJAX error while saving. HTTP ' + (xhr && xhr.status ? xhr.status : '?'));
		}).always(function(){
			$submit.prop('disabled', false).text('Save');
		});
	});
})(jQuery);
