(function( $ ) {
	'use strict';

	$( document ).ready(
		function() {

			$( document ).on(
				'focus',
				'#_lddfw_delivery_date, #_lddfw_delivery_date_sdfdd_action, #_lddfw_delivery_date_sdfdd_action2',
				function () {
					$( this ).datepicker(
						{
							dateFormat: "yy-mm-dd"
						}
					);
				}
			);

			$( ".post-type-shop_order #bulk-action-selector-top" ).change(
				function() {

					if ($( this ).val() == "sdfdd_update_delivery_time") {
						var $this = $( this );
						if ($( "#_lddfw_delivery_date_sdfdd_action" ).length) {

							$( "#_lddfw_delivery_date_sdfdd_action" ).show();
							$( "#_lddfw_delivery_from_time_sdfdd_action" ).show();
							$( "#_lddfw_delivery_to_time_sdfdd_action" ).show();

						} else {
							$.post(
								sdfdd_ajax.ajaxurl,
								{
									action: 'sdfdd_ajax',
									sdfdd_service: 'sdfdd_get_bulk_delivery_time',
									sdfdd_obj_id: 'sdfdd_action',
									sdfdd_wpnonce: sdfdd_nonce.nonce,
								},
								function(data) {
									$( data ).insertAfter( $this );
								}
							);
						}
					} else {

						$( "#_lddfw_delivery_date_sdfdd_action" ).hide();
						$( "#_lddfw_delivery_from_time_sdfdd_action" ).hide();
						$( "#_lddfw_delivery_to_time_sdfdd_action" ).hide();

					}
				}
			);

			$( ".post-type-shop_order #bulk-action-selector-bottom" ).change(
				function() {
					if ($( this ).val() == "sdfdd_update_delivery_time") {
						var $this = $( this );
						if ($( "#_lddfw_delivery_date_sdfdd_action2" ).length) {

							$( "#_lddfw_delivery_date_sdfdd_action2" ).show();
							$( "#_lddfw_delivery_from_time_sdfdd_action2" ).show();
							$( "#_lddfw_delivery_to_time_sdfdd_action2" ).show();

						} else {
							$.post(
								sdfdd_ajax.ajaxurl,
								{
									action: 'sdfdd_ajax',
									sdfdd_obj_id: 'sdfdd_action2',
									sdfdd_service: 'sdfdd_get_bulk_delivery_time',
									sdfdd_wpnonce: sdfdd_nonce.nonce,
								},
								function(data) {
									$( data ).insertAfter( $this );
								}
							);
						}
					} else {
						$( "#_lddfw_delivery_date_sdfdd_action2" ).hide();
						$( "#_lddfw_delivery_from_time_sdfdd_action2" ).hide();
						$( "#_lddfw_delivery_to_time_sdfdd_action2" ).hide();

					}
				}
			);

		}
	);
})( jQuery );
