/* File: /wp-content/plugins/aaa-offline-gateways-blocks/assets/js/classic/aaa-pm-classic-tip.js */
(function($){
	function addBtn(){
		$('.aaa-tip-wrap').each(function(){
			if ($(this).data('aaaTipBtn')) return;
			$(this).data('aaaTipBtn', true);
			const $btn = $('<button type="button" class="button button-primary aaa-tip-apply">Apply tip</button>');
			$btn.on('click', function(){ $('body').trigger('update_checkout'); });
			$(this).append($('<p/>').append($btn));
		});
	}
	$(document).on('updated_checkout', addBtn);
	$(addBtn);
})(jQuery);
