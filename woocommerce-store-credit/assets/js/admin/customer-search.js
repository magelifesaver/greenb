/**
 * Customer search.
 *
 * @package WC_Store_Credit/Assets/Js/Admin
 * @since   3.1.0
 */

(function( $ ) {

	'use strict';

	/**
	 * Email Validation.
	 */
	function isEmail( email ) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email );
	}

	$( function() {
		var wc_store_credit_customer_search = {

			init: function() {
				// Enhanced selects script is initialized in head.
				this.enhanceCustomerSearch();
			},

			enhanceCustomerSearch: function () {
				$( ':input.wc-customer-search' ).filter( '.enhanced' ).each( function() {
					var select2_options = $( this ).data( 'select2' ).options.options;

					// Tags option not enabled.
					if ( ! select2_options.tags ) {
						return;
					}

					// Adds restrictions for creating tags.
					select2_options.createTag = function ( params ) {
						var term = $.trim( params.term );

						if ( '' === term || ! isEmail( term ) ) {
							return null;
						}

						return {
							id: term,
							text: term
						};
					};

					if ( $( this ).prop( 'multiple' ) ) {
						select2_options.tokenizer = function( input, selection, callback ) {
							var terms = input.split( ',' );
							for ( var i = 0; i < terms.length; i++ ) {
								var term = $.trim( terms[i] );
								if ( term ) {
									callback( { id: term, text: term } );
								}
							}
						};
					}

					// Re-initialize the select.
					$( this ).select2( 'destroy' ).select2( select2_options );
				});
			}
		};

		wc_store_credit_customer_search.init();
	});
})( jQuery );
