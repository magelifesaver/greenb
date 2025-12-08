<?php
/**
 * File: wp-content/plugins/aaa-delivery-blocks-coords/includes/class-adbc-admin.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

class ADBC_Admin {
	const DEBUG_THIS_FILE = true;

	public function __construct() {
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'render_billing' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'render_shipping' ) );
	}

	protected function get_values( $order, $group ) {
		try {
			$cf = Package::container()->get( CheckoutFields::class );
			return array(
				'lat'   => (string) $cf->get_field_from_object( ADBC_FIELD_LAT,  $order, $group ),
				'lng'   => (string) $cf->get_field_from_object( ADBC_FIELD_LNG,  $order, $group ),
				'flag'  => (string) $cf->get_field_from_object( ADBC_FIELD_FLAG, $order, $group ),
			);
		} catch ( \Throwable $e ) {
			ADBC_Logger::log( 'Admin read error', array( 'err' => $e->getMessage() ) );
			return array( 'lat' => '', 'lng' => '', 'flag' => '' );
		}
	}

	public function render_billing( $order ) {
		$v = $this->get_values( $order, 'billing' );
		echo '<p><strong>Billing Coords</strong><br/>Lat: ' . esc_html( $v['lat'] ) . ' &nbsp; Lng: ' . esc_html( $v['lng'] ) . '<br/>Verified: ' . esc_html( $v['flag'] ? $v['flag'] : 'no' ) . '</p>';
	}

	public function render_shipping( $order ) {
		$v = $this->get_values( $order, 'shipping' );
		echo '<p><strong>Shipping Coords</strong><br/>Lat: ' . esc_html( $v['lat'] ) . ' &nbsp; Lng: ' . esc_html( $v['lng'] ) . '<br/>Verified: ' . esc_html( $v['flag'] ? $v['flag'] : 'no' ) . '</p>';
	}
}
