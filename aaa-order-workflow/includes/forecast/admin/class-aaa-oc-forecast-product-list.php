<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/class-aaa-oc-forecast-product-list.php
 * Purpose: Optional forecast columns + sorting + bulk queue on WooCommerce Products list.
 * Version: 0.1.2
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'AAA_OC_FORECAST_PRODUCT_LIST_DEBUG' ) ) { define( 'AAA_OC_FORECAST_PRODUCT_LIST_DEBUG', true ); }

class AAA_OC_Forecast_Product_List {

	private static $numeric = [ 'forecast_stock_qty','forecast_total_units_sold','forecast_sales_month','forecast_margin_percent','forecast_frozen_capital','forecast_po_priority_score' ];
	private static $table_cache = [];

	public static function init(): void {
		add_filter( 'manage_edit-product_columns', [ __CLASS__, 'add_columns' ], 60 );
		add_action( 'manage_product_posts_custom_column', [ __CLASS__, 'render_column' ], 60, 2 );
		add_filter( 'manage_edit-product_sortable_columns', [ __CLASS__, 'sortable_columns' ] );
		add_action( 'pre_get_posts', [ __CLASS__, 'handle_sort' ] );
		add_filter( 'posts_results', [ __CLASS__, 'prefetch_table_rows' ], 10, 2 );
		add_filter( 'bulk_actions-edit-product', [ __CLASS__, 'add_bulk_action' ] );
		add_filter( 'handle_bulk_actions-edit-product', [ __CLASS__, 'handle_bulk_action' ], 10, 3 );
		add_action( 'admin_notices', [ __CLASS__, 'maybe_notice' ] );
	}

	private static function get_settings(): array {
		if ( ! function_exists( 'aaa_oc_get_option' ) ) {
			require_once plugin_dir_path( __DIR__ ) . '/../core/options/class-aaa-oc-options.php';
			AAA_OC_Options::init();
		}
		$s = aaa_oc_get_option( 'forecast_field_settings', 'forecast', [] );
		if ( ! is_array( $s ) ) {
			return [];
		}
		foreach ( $s as $key => $cfg ) {
			if ( ! isset( $cfg['mirror'] ) ) {
				$s[ $key ]['mirror'] = 1;
			}
		}
		return $s;
	}

	private static function is_main_products_query( WP_Query $q ): bool {
		return is_admin() && $q->is_main_query() && $q->get( 'post_type' ) === 'product';
	}

	public static function add_columns( array $cols ): array {
		foreach ( self::get_settings() as $key => $cfg ) {
			if ( ! empty( $cfg['enabled'] ) ) { $cols[ $key ] = $key; }
		}
		return $cols;
	}

	public static function prefetch_table_rows( array $posts, WP_Query $q ): array {
		if ( empty( $posts ) || ! self::is_main_products_query( $q ) ) { return $posts; }
		if ( ! defined( 'AAA_OC_FORECAST_INDEX_TABLE' ) ) { return $posts; }

		$settings = self::get_settings();
		$table_cols = [];
		foreach ( $settings as $key => $cfg ) {
			if ( ! empty( $cfg['enabled'] ) ) { $table_cols[] = $key; }
		}
		if ( empty( $table_cols ) ) { return $posts; }

		$ids = array_values( array_filter( array_unique( array_map( static function( $p ){ return absint( $p->ID ); }, $posts ) ) ) );
		if ( empty( $ids ) ) { return $posts; }

		global $wpdb;
		$clean_cols = array_map( static function( $c ){ return preg_replace( '/[^a-zA-Z0-9_]/', '', $c ); }, $table_cols );
		$cols_sql = 'product_id,' . implode( ',', $clean_cols );
		$in = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql = $wpdb->prepare( "SELECT $cols_sql FROM " . AAA_OC_FORECAST_INDEX_TABLE . " WHERE product_id IN ($in)", $ids );
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		self::$table_cache = [];
		foreach ( (array) $rows as $row ) {
			$pid = absint( $row['product_id'] ?? 0 );
			if ( $pid ) { self::$table_cache[ $pid ] = $row; }
		}
		return $posts;
	}

	public static function render_column( string $col, int $post_id ): void {
		$cfg = self::get_settings()[ $col ] ?? [];
		if ( empty( $cfg['enabled'] ) ) { return; }

		$val = isset( self::$table_cache[ $post_id ][ $col ] ) ? self::$table_cache[ $post_id ][ $col ] : get_post_meta( $post_id, $col, true );
		if ( is_array( $val ) ) { $val = wp_json_encode( $val ); }
		echo esc_html( (string) $val );
	}

	public static function sortable_columns( array $sortable ): array {
		foreach ( self::get_settings() as $key => $cfg ) {
			if ( ! empty( $cfg['enabled'] ) && ! empty( $cfg['mirror'] ) ) { $sortable[ $key ] = $key; }
		}
		return $sortable;
	}

	public static function handle_sort( WP_Query $q ): void {
		if ( ! self::is_main_products_query( $q ) ) { return; }
		$orderby = (string) $q->get( 'orderby' );
		$cfg = self::get_settings()[ $orderby ] ?? [];
		if ( empty( $cfg['mirror'] ) ) { return; }
		$q->set( 'meta_key', $orderby );
		$q->set( 'orderby', in_array( $orderby, self::$numeric, true ) ? 'meta_value_num' : 'meta_value' );
	}

	public static function add_bulk_action( array $actions ): array {
		$actions['aaa_oc_queue_forecast'] = __( 'Queue for Forecast', 'aaa-oc' );
		return $actions;
	}

	public static function handle_bulk_action( string $redirect, string $action, array $ids ): string {
		if ( $action !== 'aaa_oc_queue_forecast' || empty( $ids ) || ! class_exists( 'AAA_OC_Forecast_Queue' ) ) { return $redirect; }
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		AAA_OC_Forecast_Queue::queue_products_for_forecast( $ids );
		if ( AAA_OC_FORECAST_PRODUCT_LIST_DEBUG ) { error_log( '[AAA_OC Forecast Product List] Bulk queued: ' . count( $ids ) ); }
		return add_query_arg( [ 'aaa_oc_forecast_queued' => count( $ids ) ], $redirect );
	}

	public static function maybe_notice(): void {
		$count = empty( $_GET['aaa_oc_forecast_queued'] ) ? 0 : absint( $_GET['aaa_oc_forecast_queued'] );
		if ( $count ) { echo '<div class="notice notice-success"><p>' . esc_html( $count . ' products queued for forecast.' ) . '</p></div>'; }
	}
}
