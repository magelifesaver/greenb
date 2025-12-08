/**
 * Order refunds script.
 *
 * @var {jQuery} $
 * @var {object} params
 */
( function( $, params ) {
	'use strict';

	let OrderRefund = {

		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			$( '#woocommerce-order-items' )
				.on( 'click', 'button.refund-items', this.initRefundForm )
				.on( 'click', '.do-account-funds-refund', function( event ) {
					event.preventDefault();

					// Set the account funds refund flag to true.
					$( '#account_funds_refund' ).val( 1 );

					// Trigger manual refund.
					$( '.do-manual-refund' ).trigger( 'click' );
				}  )
				.on( 'woocommerce_order_meta_box_do_refund_ajax_data', function( event, data ) {
					var $field = $( '#account_funds_refund' );

					// Include the account funds info in the AJAX request.
					data.account_funds_refund = $field.val();

					// Set the account funds refund flag to false.
					$field.val( 0 );

					return data;
				})
				.on( 'change', '.refund_order_item_qty, .refund_line_total, .refund_line_tax, #refund_amount', this.refundFormToggle );
		},

		initRefundForm: function() {
			// WC doesn't trigger any event after reloading the items, so we must add the content on the fly.
			if ( $( '.refund-actions .do-account-funds-refund' ).length ) {
				return;
			}

			$( '.refund-actions .do-manual-refund' ).before( '<button type="button" class="button button-primary do-account-funds-refund">' + params.button_text + '</button>' );
			$( '#refunded_amount' ).after( '<input type="hidden" id="account_funds_refund" name="account_funds_refund" value="0" />' );
			$( '.refund-actions .do-account-funds-refund' ).hide(); // Hidden initially as need to check if any top up is being refunded in refundFormToggle first
		},

		refundFormToggle: function( event, ui ) {
			let topUpBeingRefunded = false;

 			$( '#order_line_items .item' ).each( function() {
				let displayMetaHtml = $( this ).find( '.display_meta th' ).html();

				if ( displayMetaHtml && displayMetaHtml.includes( '_top_up_amount' ) ) {

					if ( $( this ).find( '.refund_order_item_qty' ).val() > 0 || $( this ).find( '.refund_line_total' ).val() > 0 || $( this ).find( '.refund_line_tax' ).val() > 0 ) {
						topUpBeingRefunded = true;
					}
				}

			} );

			if ( topUpBeingRefunded ) {
				// Not .remove() as the button must be added via initRefundForm, it can't be removed and readded here, as the button would be unavailable for WooCommerce to set the refund amount on
				$( '.refund-actions .do-account-funds-refund' ).hide();
			} else {
				$( '.refund-actions .do-account-funds-refund' ).show();
			}

		}
	};

	$( function() {
		OrderRefund.init();
	} );

} )( jQuery, wc_account_funds_order_refund_params );
