jQuery(document).ready(function($) {

	
	$('.woocommerce').find('table').show();
	$('.woocommerce').find('h2').show();
	// Main Settings Tab


	
	// Order Time Management Tab
	if ($('#tpfw_extratime_mode').find('option:selected').attr("value") == 'orders') {
		$('#tpfw_processing_cats').parents('tr').hide();
	}
	$('#tpfw_extratime_mode').on('change',function() {
		if ($(this).find('option:selected').attr("value") == 'orders') {
			$('#tpfw_processing_cats').parents('tr').fadeOut();
		} else if ($(this).find('option:selected').attr("value") == 'items') {
			$('#tpfw_processing_cats').parents('tr').fadeIn();
		}
	});
	// Preperation times
	if ($('#tpfw_preperation_time_mode').find('option:selected').attr("value") == 'dynamic') {
		$('#tpfw_pickup_fixed').parents('tr').hide();
		$('#tpfw_dynamic_preperation_table').show();
		$('#tpfw_dynamic_preperation_table').prevAll('h2:first').show();
	} else {
		$('#tpfw_preptime_order_item').parents('tr').hide();
		$('#tpfw_dynamic_preperation_table').hide();
		$('#tpfw_dynamic_preperation_table').prevAll('h2:first').hide();
	}
	$('#tpfw_preperation_time_mode').on('change',function() {
		if ($(this).find('option:selected').attr("value") == 'dynamic') {
			$('#tpfw_dynamic_preperation_table').fadeIn();
			$('#tpfw_preptime_order_item').parents('tr').fadeIn();
			$('#tpfw_dynamic_preperation_table').prevAll('h2:first').fadeIn();
			$('#tpfw_pickup_fixed').parents('tr').fadeOut();
		} else {
			$('#tpfw_preptime_order_item').parents('tr').fadeOut();
			$('#tpfw_dynamic_preperation_table').fadeOut();
			$('#tpfw_dynamic_preperation_table').prevAll('h2:first').fadeOut();
			$('#tpfw_pickup_fixed').parents('tr').fadeIn();
		}
	});
	// End of Order Time Management Tab

	// Order Time Management Tab (Only Premium features)
	if ($('#tpfw_ready_for_delivery_show').find('option:selected').attr("value") == 'fixed_ship') {} else if ($('#tpfw_ready_for_delivery_show').find('option:selected').attr("value") == 'variable_ship') {} else {
		$('#tpfw_shipping_time').parents('tr').hide();
		$('#tpfw_shipping_fixed').parents('tr').hide();
	}
	if ($('#tpfw_shipping_time').find('option:selected').attr("value") != 'fixedtime') {
		$('#tpfw_shipping_fixed').parents('tr').hide();
	}
	$('#tpfw_ready_for_delivery_show').on('change',function() {
		if ($(this).find('option:selected').attr("value") == 'fixed_ship') {
			$('#tpfw_shipping_time').parents('tr').fadeIn();
			if ($('#tpfw_shipping_time').find('option:selected').attr("value") != 'fixedtime') {
				$('#tpfw_shipping_fixed').parents('tr').fadeOut();
			} else {
				$('#tpfw_shipping_fixed').parents('tr').fadeIn();
			}
		} else if ($(this).find('option:selected').attr("value") == 'variable_ship') {
			$('#tpfw_shipping_time').parents('tr').fadeIn();
			if ($('#tpfw_shipping_time').find('option:selected').attr("value") != 'fixedtime') {
				$('#tpfw_shipping_fixed').parents('tr').fadeOut();
			} else {
				$('#tpfw_shipping_fixed').parents('tr').fadeIn();
			}
		} else {
			$('#tpfw_shipping_time').parents('tr').fadeOut();
			$('#tpfw_shipping_fixed').parents('tr').fadeOut();
		}
	});
	$('#tpfw_shipping_time').on('change',function() {
		if ($(this).find('option:selected').attr("value") != 'fixedtime') {
			$('#tpfw_shipping_fixed').parents('tr').fadeOut();
		} else {
			$('#tpfw_shipping_fixed').parents('tr').fadeIn();
		}
	});

	// End of Order Time Management Tab
});
