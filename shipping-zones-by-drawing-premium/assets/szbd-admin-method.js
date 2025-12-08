jQuery(document).ready(function($) {
	jQuery(document).on('wc_backbone_modal_loaded', function() {
		$('.szbd-enhanced-select').selectWoo();
		
		if ($('#woocommerce_szbd-shipping-method_rate_mode').find('option:selected').attr("value") == 'flat') {
			$('#woocommerce_szbd-shipping-method_rate_fixed').parents('fieldset').hide();
			$('#woocommerce_szbd-shipping-method_rate_distance').parents('fieldset').hide();

            $('#woocommerce_szbd-shipping-method_rate_fixed').parents('fieldset').prev('label').hide();
			$('#woocommerce_szbd-shipping-method_rate_distance').parents('fieldset').prev('label').hide();

		} else if ($('#woocommerce_szbd-shipping-method_rate_mode').find('option:selected').attr("value") == 'distance') {
			$('#woocommerce_szbd-shipping-method_rate').parents('fieldset').hide();
			$('#woocommerce_szbd-shipping-method_rate_fixed').parents('fieldset').hide();

            $('#woocommerce_szbd-shipping-method_rate').parents('fieldset').prev('label').hide();
			$('#woocommerce_szbd-shipping-method_rate_fixed').parents('fieldset').prev('label').hide();
		} else {
			$('#woocommerce_szbd-shipping-method_rate').parents('fieldset').prev('label').hide();
            $('#woocommerce_szbd-shipping-method_rate').parents('fieldset').hide();
		}
		$('#woocommerce_szbd-shipping-method_rate_mode').on('change', function() {
			if ($(this).find('option:selected').attr("value") == 'flat') {
				$('#woocommerce_szbd-shipping-method_rate_fixed').parents('fieldset').fadeOut();
				$('#woocommerce_szbd-shipping-method_rate_distance').parents('fieldset').fadeOut();
				$('#woocommerce_szbd-shipping-method_rate').parents('fieldset').fadeIn();

                $('#woocommerce_szbd-shipping-method_rate_fixed').parents('fieldset').prev('label').fadeOut();
				$('#woocommerce_szbd-shipping-method_rate_distance').parents('fieldset').prev('label').fadeOut();
				$('#woocommerce_szbd-shipping-method_rate').parents('fieldset').prev('label').fadeIn();
			} else if ($(this).find('option:selected').attr("value") == 'distance') {
				$('#woocommerce_szbd-shipping-method_rate').parents('fieldset').fadeOut();
				$('#woocommerce_szbd-shipping-method_rate_fixed').parents('fieldset').fadeOut();
				$('#woocommerce_szbd-shipping-method_rate_distance').parents('fieldset').fadeIn();

                $('#woocommerce_szbd-shipping-method_rate').parents('fieldset').prev('label').fadeOut();
				$('#woocommerce_szbd-shipping-method_rate_fixed').parents('fieldset').prev('label').fadeOut();
				$('#woocommerce_szbd-shipping-method_rate_distance').parents('fieldset').prev('label').fadeIn();
			} else {
				$('#woocommerce_szbd-shipping-method_rate').parents('fieldset').fadeOut();
				$('#woocommerce_szbd-shipping-method_rate_fixed').parents('fieldset').fadeIn();
				$('#woocommerce_szbd-shipping-method_rate_distance').parents('fieldset').fadeIn();

                $('#woocommerce_szbd-shipping-method_rate').parents('fieldset').prev('label').fadeOut();
				$('#woocommerce_szbd-shipping-method_rate_fixed').parents('fieldset').prev('label').fadeIn();
				$('#woocommerce_szbd-shipping-method_rate_distance').parents('fieldset').prev('label').fadeIn();
			}
		});
		if ($('#woocommerce_szbd-shipping-method_map').find('option:selected').attr("value") !== 'radius') {
			$('#woocommerce_szbd-shipping-method_max_radius').parents('fieldset').prev('label').hide();
            $('#woocommerce_szbd-shipping-method_max_radius').parents('fieldset').hide();
		}
		
		$('#woocommerce_szbd-shipping-method_map').on('change',function() {
			if ($(this).find('option:selected').attr("value") == 'radius') {
				
				$('#woocommerce_szbd-shipping-method_max_radius').parents('fieldset').fadeIn();
                $('#woocommerce_szbd-shipping-method_max_radius').parents('fieldset').prev('label').fadeIn();
			} else if ($(this).find('option:selected').attr("value") == 'none') {
				$('#woocommerce_szbd-shipping-method_max_radius').parents('fieldset').fadeOut();
                $('#woocommerce_szbd-shipping-method_max_radius').parents('fieldset').prev('label').fadeOut();
				
			} else {
				$('#woocommerce_szbd-shipping-method_max_radius').parents('fieldset').fadeOut();
                $('#woocommerce_szbd-shipping-method_max_radius').parents('fieldset').prev('label').fadeOut();
				
			}
		});
		
		
		
	});
});
