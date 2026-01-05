<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AAA_API_Lokey_REST_LokeyInventory_Diagnostics {
	public static function diagnostics( $request ) {
		$start = microtime( true );
		$base = trailingslashit( rest_url( 'lokey-inventory/v1' ) );
		$eps = array(
			array( 'label' => 'Diagnostics', 'route' => '/diagnostics', 'active' => true, 'endpoint' => $base . 'diagnostics' ),
			array( 'label' => 'Inventory list', 'route' => '/inventory', 'active' => true, 'endpoint' => $base . 'inventory' ),
			array( 'label' => 'Inventory update', 'route' => '/inventory/{id}', 'active' => true, 'endpoint' => $base . 'inventory/{id}' ),
			array( 'label' => 'Products extended', 'route' => '/products/extended', 'active' => true, 'endpoint' => $base . 'products/extended' ),
			array( 'label' => 'Products extended update', 'route' => '/products/extended/{id}', 'active' => true, 'endpoint' => $base . 'products/extended/{id}' ),
		);
		$out = array(
			'version'      => AAA_API_Lokey_REST_Extended_Helpers::api_version(),
			'status'       => 'ok',
			'plugin'       => AAA_API_LOKEY_SLUG,
			'namespace'    => 'lokey-inventory/v1',
			'constants'    => array(
				'AAA_API_LOKEY_VERSION'     => AAA_API_LOKEY_VERSION,
				'AAA_API_LOKEY_API_VERSION' => defined( 'AAA_API_LOKEY_API_VERSION' ) ? AAA_API_LOKEY_API_VERSION : AAA_API_LOKEY_VERSION,
				'woo_active'               => class_exists( 'WooCommerce' ),
				'atum_active'              => AAA_API_Lokey_Atum_Bridge::is_active(),
			),
			'endpoints'    => $eps,
			'execution_ms' => round( ( microtime( true ) - $start ) * 1000, 2 ),
			'timestamp'    => current_time( 'mysql' ),
		);
		return rest_ensure_response( $out );
	}
}
