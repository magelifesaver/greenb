<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AAA_API_Lokey_Loader {

	public static function activate() {
		$settings = get_option( AAA_API_LOKEY_OPTION, array() );
		if ( empty( $settings['api_key'] ) ) {
			$settings['api_key'] = wp_generate_password( 40, false, false );
		}
		if ( empty( $settings['brand_taxonomy'] ) ) {
			$settings['brand_taxonomy'] = self::detect_brand_taxonomy();
		}
		if ( empty( $settings['namespaces'] ) || ! is_array( $settings['namespaces'] ) ) {
			$settings['namespaces'] = array( 'aaa/v1', 'aaa-api-lokey/v1' );
		}
		update_option( AAA_API_LOKEY_OPTION, $settings );
	}

	public static function init() {
		require_once AAA_API_LOKEY_DIR . 'includes/class-aaa-api-lokey-auth.php';
		require_once AAA_API_LOKEY_DIR . 'includes/class-aaa-api-lokey-settings.php';
		require_once AAA_API_LOKEY_DIR . 'includes/atum/class-aaa-api-lokey-atum-bridge.php';

		require_once AAA_API_LOKEY_DIR . 'includes/rest/class-aaa-api-lokey-rest-products-helpers.php';
		require_once AAA_API_LOKEY_DIR . 'includes/rest/class-aaa-api-lokey-rest-extended-helpers.php';

		require_once AAA_API_LOKEY_DIR . 'includes/rest/class-aaa-api-lokey-rest-products.php';
		require_once AAA_API_LOKEY_DIR . 'includes/rest/class-aaa-api-lokey-rest-atum.php';
		require_once AAA_API_LOKEY_DIR . 'includes/rest/class-aaa-api-lokey-rest-reports.php';

		require_once AAA_API_LOKEY_DIR . 'includes/rest/class-aaa-api-lokey-rest-lokey-inventory-common.php';
		require_once AAA_API_LOKEY_DIR . 'includes/rest/class-aaa-api-lokey-rest-lokey-inventory-diagnostics.php';
		require_once AAA_API_LOKEY_DIR . 'includes/rest/class-aaa-api-lokey-rest-lokey-inventory-list.php';
		require_once AAA_API_LOKEY_DIR . 'includes/rest/class-aaa-api-lokey-rest-lokey-inventory-update.php';
		require_once AAA_API_LOKEY_DIR . 'includes/rest/class-aaa-api-lokey-rest-lokey-inventory.php';
		require_once AAA_API_LOKEY_DIR . 'includes/rest/class-aaa-api-lokey-rest-extended-products.php';

		require_once AAA_API_LOKEY_DIR . 'includes/rest/class-aaa-api-lokey-rest.php';

		if ( is_admin() ) {
			AAA_API_Lokey_Settings::init();
		}

		AAA_API_Lokey_REST::init();
	}

	private static function detect_brand_taxonomy() {
		$candidates = array( 'product_brand', 'pwb-brand', 'product_brands', 'brand' );
		foreach ( $candidates as $tax ) {
			if ( taxonomy_exists( $tax ) ) {
				return $tax;
			}
		}
		return '';
	}
}
