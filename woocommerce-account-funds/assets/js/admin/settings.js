/**
 * Settings screen script.
 *
 * @param {jQuery} $
 */
( function( $ ) {
	'use strict';

	let Settings = {

		init: function () {
			this.$topUpCheckbox   = $( 'input#top_up_enabled' );
			this.$topUpTypeSelect = $( 'select#top_up_image_type' );
			this.$topUpImageRow   = $( 'input#top_up_image_id' ).closest( 'tr' );

			this.toggleTopUpFields( this.$topUpCheckbox.prop( 'checked' ) );
			this.toggleTopUpImageRow( this.$topUpTypeSelect.val() === 'custom' && this.$topUpCheckbox.prop( 'checked' ) );
			this.bindEvents();

			this.topUpImageSelect();
		},

		bindEvents: function() {
			let that = this;

			this.$topUpCheckbox.on( 'change', function() {
				that.toggleTopUpFields( $( this ).prop( 'checked' ) );
			} );

			this.$topUpTypeSelect.on( 'change', function() {
				that.toggleTopUpImageRow( $( this ).val() === 'custom' );
			} );
		},

		toggleTopUpFields: function( visible ) {

			$( 'input#allow_top_up_rewards, input#minimum_top_up, input#maximum_top_up, select#top_up_image_type' ).closest( 'tr' ).toggle( visible );

			if ( visible && $( 'select#top_up_image_type' ).val() === 'custom' ) {
				this.$topUpImageRow.show();
			} else {
				this.$topUpImageRow.hide();
			}
		},

		toggleTopUpImageRow: function( visible ) {
			this.$topUpImageRow.toggle( visible );
		},

		topUpImageSelect: function() {
			let mediaUploader,
			    $topUpImageId      = $( '#top_up_image_id' ),
			    $topUpImagePreview = $( '#top-up-image-preview' );

			$topUpImageId.hide();
			$topUpImagePreview.hide();

			if ( $topUpImagePreview.attr( 'src' ) !== '' ) {
				$topUpImagePreview.show();
			}

			$( '#top-up-image-select' ).on( 'click', function( e ) {
				e.preventDefault();

				let $topUpImageId      = $( '#top_up_image_id' ),
					$topUpImagePreview = $( '#top-up-image-preview' );

				if ( mediaUploader ) {
					mediaUploader.open();
					return;
				}

				mediaUploader = wp.media({
					multiple: false
				} );

				mediaUploader.on( 'select', function() {
					let attachment = mediaUploader.state().get( 'selection' ).first().toJSON();

					$topUpImageId.val( attachment.id ).trigger( 'change' ); // triggered to ensure save settings button becomes active if only changing this one field
					$topUpImagePreview.attr( 'src', attachment.url );

					$topUpImagePreview.show();
				} );

				mediaUploader.open();
			} );
		},
	};

	$( function() {
		Settings.init();
	} );

} )( jQuery );
