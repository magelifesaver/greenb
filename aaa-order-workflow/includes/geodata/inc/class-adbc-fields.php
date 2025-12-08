<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/geodata/inc/class-adbc-fields.php
 * Purpose: Register Additional Checkout Fields (coords + flag) and respect "hide" options from aaa_oc_options.
 * Version: 1.0.2
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

class ADBC_Fields {

	public static function register() {
		add_action( 'woocommerce_init', function () {
			if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
				return;
			}

			// Read options from the same store used by the admin tab
			if ( class_exists( 'AAA_OC_Options' ) ) {
				$opts = AAA_OC_Options::get( 'delivery_adbc_options', 'adbc', [] );
			} else {
				// Legacy fallback, should be unused now but kept for safety
				$opts = get_option( 'delivery_global', [] );
			}

			$attrs = [ 'aria-hidden' => 'true' ];

			// --- Shipping coords fields ---
			// Only register when NOT hidden. If hidden, don't register at all.
			if ( empty( $opts['hide_shipping_coords'] ) ) {
				woocommerce_register_additional_checkout_field( [
					'id'                 => defined('ADBC_FIELD_LAT') ? ADBC_FIELD_LAT : 'aaa-delivery-blocks/latitude',
					'label'              => 'Latitude',
					'location'           => 'address',
					'type'               => 'text',
					'attributes'         => $attrs,
					'sanitize_callback'  => fn( $v ) => is_numeric( $v ) ? (string)(float)$v : '',
				] );
				woocommerce_register_additional_checkout_field( [
					'id'                 => defined('ADBC_FIELD_LNG') ? ADBC_FIELD_LNG : 'aaa-delivery-blocks/longitude',
					'label'              => 'Longitude',
					'location'           => 'address',
					'type'               => 'text',
					'attributes'         => $attrs,
					'sanitize_callback'  => fn( $v ) => is_numeric( $v ) ? (string)(float)$v : '',
				] );
			}

			// --- Shipping coords verification flag ---
			if ( empty( $opts['hide_shipping_flag'] ) ) {
				woocommerce_register_additional_checkout_field( [
					'id'                 => defined('ADBC_FIELD_FLAG') ? ADBC_FIELD_FLAG : 'aaa-delivery-blocks/coords-verified',
					'label'              => 'Coords Verified',
					'location'           => 'address',
					'type'               => 'text',
					'attributes'         => $attrs,
					'sanitize_callback'  => fn( $v ) => $v === 'yes' ? 'yes' : 'no',
				] );
			}

			// --- CSS hiding (Blocks UI) when options demand hiding ---
			add_action( 'wp_enqueue_scripts', function () use ( $opts ) {
				$css = '';

				// If "Hide Shipping Coords Fields" was requested AND fields were registered previously by cache or another layer,
				// also force-hide via CSS to be defensive.
				if ( ! empty( $opts['hide_shipping_coords'] ) ) {
					$lat = defined('ADBC_FIELD_LAT') ? ADBC_FIELD_LAT : 'aaa-delivery-blocks/latitude';
					$lng = defined('ADBC_FIELD_LNG') ? ADBC_FIELD_LNG : 'aaa-delivery-blocks/longitude';
					$css .= '[data-additional-field-id="' . esc_attr( $lat ) . '"],
					         [data-additional-field-id="' . esc_attr( $lng ) . '"]{display:none!important;}';
				}
				if ( ! empty( $opts['hide_shipping_flag'] ) ) {
					$flag = defined('ADBC_FIELD_FLAG') ? ADBC_FIELD_FLAG : 'aaa-delivery-blocks/coords-verified';
					$css .= '[data-additional-field-id="' . esc_attr( $flag ) . '"]{display:none!important;}';
				}

				if ( $css ) {
					// Target Blocks stylesheet handle so rules are present on checkout
					wp_add_inline_style( 'woocommerce-blocks-style', $css );
				}
			} );

			// Optional debug log (kept silent unless WP_DEBUG & ADBC_DEBUG true)
			if ( defined('WP_DEBUG') && WP_DEBUG && defined('ADBC_DEBUG') && ADBC_DEBUG ) {
				if ( function_exists( 'error_log' ) ) {
					@error_log( '[ADBC_Fields] registered with opts: ' . wp_json_encode( $opts ) );
				}
			}
		} );

		add_action( 'woocommerce_store_api_checkout_update_order_meta', [ __CLASS__, 'save_coords' ], 10, 1 );
	}

	public static function save_coords( WC_Order $order ) {
		try {
			$cf = Package::container()->get( CheckoutFields::class );

			$lat_b = (string) $cf->get_field_from_object( defined('ADBC_FIELD_LAT') ? ADBC_FIELD_LAT : 'aaa-delivery-blocks/latitude',  $order, 'billing' );
			$lng_b = (string) $cf->get_field_from_object( defined('ADBC_FIELD_LNG') ? ADBC_FIELD_LNG : 'aaa-delivery-blocks/longitude', $order, 'billing' );
			$lat_s = (string) $cf->get_field_from_object( defined('ADBC_FIELD_LAT') ? ADBC_FIELD_LAT : 'aaa-delivery-blocks/latitude',  $order, 'shipping' );
			$lng_s = (string) $cf->get_field_from_object( defined('ADBC_FIELD_LNG') ? ADBC_FIELD_LNG : 'aaa-delivery-blocks/longitude', $order, 'shipping' );

			$flag_b = ( $lat_b !== '' && $lng_b !== '' ) ? 'yes' : 'no';
			$flag_s = ( $lat_s !== '' && $lng_s !== '' ) ? 'yes' : 'no';

			$order->update_meta_data( CheckoutFields::BILLING_FIELDS_PREFIX  . ( defined('ADBC_FIELD_LAT') ? ADBC_FIELD_LAT : 'aaa-delivery-blocks/latitude' ),  $lat_b );
			$order->update_meta_data( CheckoutFields::BILLING_FIELDS_PREFIX  . ( defined('ADBC_FIELD_LNG') ? ADBC_FIELD_LNG : 'aaa-delivery-blocks/longitude' ), $lng_b );
			$order->update_meta_data( CheckoutFields::BILLING_FIELDS_PREFIX  . ( defined('ADBC_FIELD_FLAG') ? ADBC_FIELD_FLAG : 'aaa-delivery-blocks/coords-verified' ), $flag_b );
			$order->update_meta_data( CheckoutFields::SHIPPING_FIELDS_PREFIX . ( defined('ADBC_FIELD_LAT') ? ADBC_FIELD_LAT : 'aaa-delivery-blocks/latitude' ),  $lat_s );
			$order->update_meta_data( CheckoutFields::SHIPPING_FIELDS_PREFIX . ( defined('ADBC_FIELD_LNG') ? ADBC_FIELD_LNG : 'aaa-delivery-blocks/longitude' ), $lng_s );
			$order->update_meta_data( CheckoutFields::SHIPPING_FIELDS_PREFIX . ( defined('ADBC_FIELD_FLAG') ? ADBC_FIELD_FLAG : 'aaa-delivery-blocks/coords-verified' ), $flag_s );

			if ( $uid = $order->get_user_id() ) {
				update_user_meta( $uid, '_wc_billing/'  . ( defined('ADBC_FIELD_LAT') ? ADBC_FIELD_LAT : 'aaa-delivery-blocks/latitude' ),  $lat_b );
				update_user_meta( $uid, '_wc_billing/'  . ( defined('ADBC_FIELD_LNG') ? ADBC_FIELD_LNG : 'aaa-delivery-blocks/longitude' ), $lng_b );
				update_user_meta( $uid, '_wc_billing/'  . ( defined('ADBC_FIELD_FLAG') ? ADBC_FIELD_FLAG : 'aaa-delivery-blocks/coords-verified' ), $flag_b );
				update_user_meta( $uid, '_wc_shipping/' . ( defined('ADBC_FIELD_LAT') ? ADBC_FIELD_LAT : 'aaa-delivery-blocks/latitude' ),  $lat_s );
				update_user_meta( $uid, '_wc_shipping/' . ( defined('ADBC_FIELD_LNG') ? ADBC_FIELD_LNG : 'aaa-delivery-blocks/longitude' ), $lng_s );
				update_user_meta( $uid, '_wc_shipping/' . ( defined('ADBC_FIELD_FLAG') ? ADBC_FIELD_FLAG : 'aaa-delivery-blocks/coords-verified' ), $flag_s );
			}
		} catch ( \Throwable $e ) {
			if ( defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log') ) {
				@error_log( '[ADBC_Fields] save_coords error: ' . $e->getMessage() );
			}
		}
	}
}
