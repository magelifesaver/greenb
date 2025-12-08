jQuery(document).ready(function($) {
	// Enables the color picker in settings page
	$('.cma-color-picker').wpColorPicker();
	if (!$('#cma_delivery_colormode:checkbox').prop('checked')) {
		$('#cma_color_positive').parents('tr').hide();
		$('#cma_color_negative').parents('tr').hide();
	}
	$('#cma_delivery_colormode:checkbox').on('change', function() {
		if (this.checked) {
			$('#cma_color_positive').parents('tr').fadeIn('slow');
			$('#cma_color_negative').parents('tr').fadeIn('slow');
		} else {
			$('#cma_color_positive').parents('tr').fadeOut('slow');
			$('#cma_color_negative').parents('tr').fadeOut('slow');
		}
	});
	if (!$('#cma_geolocate_position:checkbox').prop('checked')) {
		$('#cma_validation_after_geolocation').parents('tr').hide();
		$('#cma_max_accuracy').parents('tr').hide();
	}
	$('#cma_geolocate_position:checkbox').on('change', function() {
		if (this.checked) {
			$('#cma_validation_after_geolocation').parents('tr').fadeIn('slow');
			$('#cma_max_accuracy').parents('tr').fadeIn('slow');
		} else {
			$('#cma_validation_after_geolocation').parents('tr').fadeOut('slow');
			$('#cma_max_accuracy').parents('tr').fadeOut('slow');
		}
	});
	if ($('#cma_message_format').find('option:selected').attr("value") == 'print') {
		$('#cma_top_bar_info').parents('tr').hide();
	}
	$('#cma_message_format').on('change', function() {
		if ($(this).find('option:selected').attr("value") == 'print') {
			$('#cma_top_bar_info').parents('tr').hide();
		} else if ($(this).find('option:selected').attr("value") == 'popup') {
			$('#cma_top_bar_info').parents('tr').fadeIn('slow');
		}
	});
	// Advanced address options
	if (!$('#cma_address_advanced:checkbox').prop('checked')) {
		$('#cma_autocomplete_types').parents('tr').hide();
		$('#cma_result_types').parents('tr').hide();
	}
	$('#cma_address_advanced:checkbox').on('change', function() {
		if (this.checked) {
			$('#cma_autocomplete_types').parents('tr').fadeIn();
			$('#cma_result_types').parents('tr').fadeIn();
		} else {
			$('#cma_autocomplete_types').parents('tr').fadeOut();
			$('#cma_result_types').parents('tr').fadeOut();
		}
	});
});
