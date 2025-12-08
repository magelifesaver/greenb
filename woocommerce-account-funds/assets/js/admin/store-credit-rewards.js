/**
 * Credit sources screens script.
 *
 * @param {jQuery} $
 */
( function( $ ) {
	'use strict';

	// store credit configuration metabox tabs
	$( 'ul.wc-tabs' ).show();

	$( 'div.panel-wrap' ).each( function() {
		$( this ).find( 'div.panel:not(:first)' ).hide();
	} );

	$( 'ul.wc-tabs a' ).on( 'click',function() {
		const target      = $( this ).attr( 'href' );
		const panel_wrap  = $( this ).closest( 'div.panel-wrap' );

		$( 'ul.wc-tabs li', panel_wrap ).removeClass( 'active' );
		$( this ).parent().addClass( 'active' );
		$( 'div.panel', panel_wrap ).hide();
		$( target ).show();

		return false;
	} );

	$( 'ul.wc-tabs li:visible' ).eq( 0 ).find( 'a' ).trigger( 'click' );

	// Amount "Type" toggles between percentage and fixed store credit amount fields
	$( '#amount_type' ).on( 'change', function() {
		let $percentage_amount_field = $( 'p.percentage_amount_field' ),
			$fixed_amount_field      = $( 'p.fixed_amount_field' ),
			amount_type              = $( this ).val();

		if ( amount_type === 'percentage' ) {
			$percentage_amount_field.removeClass( 'hidden' );
			$fixed_amount_field.addClass( 'hidden' );
		} else if ( amount_type === 'fixed' ) {
			$percentage_amount_field.addClass( 'hidden' );
			$fixed_amount_field.removeClass( 'hidden' );
		}
	} ).trigger( 'change' );

	// "Award cap" toggles store credit fields related to award counts and budgets
	$( '#award_cap' ).on( 'change', function() {
		let award_cap          = $( this ).val(),
			$award_limit_field = $( 'p.award_limit_field' );

		if ( award_cap === 'award_limit' ) {
			$award_limit_field.removeClass( 'hidden' );
		} else {
			$award_limit_field.addClass( 'hidden' );
		}
	} ).trigger( 'change' );

	// "Awarded upon" toggles related rules fields in the "Rules" panel
	$( '#trigger' ).on( 'change', function() {
		let trigger       = $( this ).val(),
			$rules_panel  = $( 'div#store-credit-configuration-rules-panel' ),
			$unique_field = $( '#unique' );

		$( 'div.options_group', $rules_panel ).each( function() {
			let $options_group = $( this );

			if ( $options_group.hasClass( trigger ) ) {
				$options_group.removeClass( 'hidden' );
			} else {
				$options_group.addClass( 'hidden' );
			}
		} );

		// toggle visibility of trigger descriptions spans based on the current chosen trigger
		$( '.trigger-description' ).each( function() {
			$( this ).closest( 'span.description' ).css( 'display', 'block' );
			if ( $( this ).data( 'trigger' ) === trigger ) {
				$( this ).css( 'display', 'block' );
			} else {
				$( this ).css( 'display', 'none' );
			}
		} );

		// Rewards: When "Customer account registration" is chosen, hide the "Rules" panel altogether, otherwise show it
		if ( trigger === 'account_signup' ) {
			$( 'li.rules-tab' ).hide();
			$unique_field.val( 'yes' );
			$unique_field.prop( 'disabled', true );
		} else {
			$( 'li.rules-tab' ).show();
			$unique_field.val( 'no' );
			$unique_field.prop( 'disabled', false );
		}
	} ).trigger( 'change' );

	// Cashback: "Type of order" toggles related rules fields
	$( '#orders' ).on( 'change', function() {
		let eligible_orders           = $( this ).val(),
			show_products_field       = eligible_orders === 'including_products' || eligible_orders === 'excluding_products',
			show_product_categories   = eligible_orders === 'including_product_categories' || eligible_orders === 'excluding_product_categories',
			show_product_types        = eligible_orders === 'including_product_types' || eligible_orders === 'excluding_product_types',
			$products_field           = $( 'div.order_paid p.product_ids_field' ),
			$product_categories_field = $( 'div.order_paid p.product_cat_ids_field' ),
			$product_types_field      = $( 'div.order_paid p.product_types_field' );

		if ( show_products_field ) {
			$products_field.removeClass( 'hidden' );
			$product_categories_field.addClass( 'hidden' );
			$product_types_field.addClass( 'hidden' );
		} else if ( show_product_categories ) {
			$products_field.addClass( 'hidden' );
			$product_categories_field.removeClass( 'hidden' );
			$product_types_field.addClass( 'hidden' );
		} else if ( show_product_types ) {
			$products_field.addClass( 'hidden' );
			$product_categories_field.addClass( 'hidden' );
			$product_types_field.removeClass( 'hidden' );
		} else {
			$products_field.addClass( 'hidden' );
			$product_categories_field.addClass( 'hidden' );
			$product_types_field.addClass( 'hidden' );
		}
	} ).trigger( 'change' );

	// Cashback/Rewards: "Eligible products" toggles related rules fields
	$( '#products' ).on( 'change', function() {
		let eligible_products         = $( this ).val(),
			$parent_group             = $( this ).closest( 'div.options_group' ),
			show_products_field       = eligible_products === 'some_products',
			show_product_categories   = eligible_products === 'some_product_categories',
			$products_field           = $parent_group.find( 'p.product_ids_field' ),
			$product_categories_field = $parent_group.find( 'p.product_cat_ids_field' );

		if ( show_products_field ) {
			$products_field.removeClass( 'hidden' );
			$product_categories_field.addClass( 'hidden' );
		} else if ( show_product_categories ) {
			$products_field.addClass( 'hidden' );
			$product_categories_field.removeClass( 'hidden' );
		} else {
			$products_field.addClass( 'hidden' );
			$product_categories_field.addClass( 'hidden' );
		}
	} ).trigger( 'change' );

	// "Award limit" toggles per-customer eligibility rules fields
	$( 'select.award-limits' ).on( 'change', function() {
		let award_limit   = $( this ).val(),
			$descriptions = $( this ).closest( 'p' ).find( 'span.award-limit-description' );

		console.log( award_limit );
		$descriptions.each( function() {
			let $description = $( this );
			console.log( $description.data( 'award-limit' ) );
			if ( $( this ).data( 'award-limit' ) === award_limit ) {
				$( this ).css( 'display', 'block' );
			} else {
				$( this ).css( 'display', 'none' );
			}
		} );
	} ).trigger( 'change' );

} )( jQuery );
