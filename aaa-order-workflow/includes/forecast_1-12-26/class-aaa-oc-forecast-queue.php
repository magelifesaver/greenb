<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/class-aaa-oc-forecast-queue.php
 * Purpose: Forecast queue CRUD + batch processing.
 * Version: 0.1.3
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Queue {

	private const HOOK           = 'aaa_oc_process_forecast_queue';
	private const PO_HOOK        = 'aaa_oc_process_po_queue';
	private const INLINE_LOCK    = 'aaa_oc_forecast_queue_inline_lock';
	private const QUEUE_ALL_HOOK = 'aaa_oc_queue_all_enabled_products';

	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'clear_legacy_schedule' ] );
		add_action( self::HOOK, [ __CLASS__, 'process_forecast_queue' ] );
		add_action( self::PO_HOOK, [ __CLASS__, 'process_po_queue' ] );
		add_action( self::QUEUE_ALL_HOOK, [ __CLASS__, 'queue_all_enabled_products' ] );
		add_action( 'admin_init', [ __CLASS__, 'maybe_process_inline' ] );
	}

	public static function clear_legacy_schedule(): void {
		foreach ( [ self::HOOK, self::PO_HOOK ] as $hook ) {
			$event = wp_get_scheduled_event( $hook );
			if ( $event && ! empty( $event->schedule ) ) {
				wp_clear_scheduled_hook( $hook );
			}
		}
	}

	public static function enqueue_product( int $product_id ): void {
		$pid = absint( $product_id );
		if ( $pid ) { self::queue_products_for_forecast( [ $pid ] ); }
	}

	public static function enqueue_po_product( int $product_id ): void {
		$pid = absint( $product_id );
		if ( $pid ) { self::queue_products_for_po( [ $pid ] ); }
	}

	public static function dequeue_product( int $product_id ): void {
		$pid = absint( $product_id );
		if ( ! $pid ) { return; }
		global $wpdb;

		foreach ( [ AAA_OC_FORECAST_QUEUE_TABLE, AAA_OC_FORECAST_PO_QUEUE_TABLE ] as $table ) {
			if ( ! self::table_exists( $table ) ) { continue; }
			$sql = $wpdb->prepare(
				"DELETE FROM {$table} WHERE product_id = %d AND status IN ('pending','processing')",
				$pid
			);
			$wpdb->query( $sql );
		}
	}

	public static function queue_products_for_forecast( array $product_ids ): void {
		$ids = array_values( array_filter( array_map( 'absint', (array) $product_ids ) ) );
		if ( empty( $ids ) ) { return; }

		self::maybe_install_tables();

		global $wpdb;
		$table   = AAA_OC_FORECAST_QUEUE_TABLE;
		$user_id = get_current_user_id();

		if ( ! self::table_exists( $table ) ) { return; }

		foreach ( $ids as $pid ) {
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$table} WHERE product_id = %d AND status IN ('pending','processing') LIMIT 1",
				$pid
			) );
			if ( $exists ) { continue; }

			$data   = [ 'product_id' => $pid, 'status' => 'pending' ];
			$format = [ '%d', '%s' ];

			if ( self::table_has_col( $table, 'user_id' ) ) {
				$data['user_id'] = $user_id; $format[] = '%d';
			} elseif ( self::table_has_col( $table, 'created_by' ) ) {
				$data['created_by'] = $user_id; $format[] = '%d';
			}
			if ( self::table_has_col( $table, 'attempts' ) ) {
				$data['attempts'] = 0; $format[] = '%d';
			}

			$wpdb->insert( $table, $data, $format );
		}

		self::schedule_next_run( MINUTE_IN_SECONDS );
	}

	public static function schedule_queue_all_enabled( int $delay = MINUTE_IN_SECONDS ): void {
		if ( ! wp_next_scheduled( self::QUEUE_ALL_HOOK ) ) {
			wp_schedule_single_event( time() + max( 5, $delay ), self::QUEUE_ALL_HOOK );
		}
	}

	public static function schedule_process_queue( int $delay = MINUTE_IN_SECONDS ): void {
		self::schedule_next_run( $delay );
	}

	public static function queue_all_enabled_products(): void {
		$q = new WP_Query( [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'meta_query'     => [
				[ 'key' => 'forecast_enable_reorder', 'value' => 'yes' ],
			],
		] );

		$ids = array_values( array_filter( array_map( 'absint', (array) $q->posts ) ) );

		if ( ! empty( $ids ) ) {
			self::queue_products_for_forecast( $ids );
		}
	}

	public static function queue_products_for_po( array $product_ids ): void {
		$ids = array_values( array_filter( array_map( 'absint', (array) $product_ids ) ) );
		if ( empty( $ids ) ) { return; }

		self::maybe_install_tables();

		global $wpdb;
		$table   = AAA_OC_FORECAST_PO_QUEUE_TABLE;
		$user_id = get_current_user_id();

		if ( ! self::table_exists( $table ) ) { return; }

		foreach ( $ids as $pid ) {
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$table} WHERE product_id = %d AND status IN ('pending','processing') LIMIT 1",
				$pid
			) );
			if ( $exists ) { continue; }

			$data   = [ 'product_id' => $pid, 'status' => 'pending' ];
			$format = [ '%d', '%s' ];

			if ( self::table_has_col( $table, 'user_id' ) ) {
				$data['user_id'] = $user_id; $format[] = '%d';
			} elseif ( self::table_has_col( $table, 'created_by' ) ) {
				$data['created_by'] = $user_id; $format[] = '%d';
			}
			if ( self::table_has_col( $table, 'quantity' ) ) {
				$data['quantity'] = 1; $format[] = '%d';
			}
			if ( self::table_has_col( $table, 'attempts' ) ) {
				$data['attempts'] = 0; $format[] = '%d';
			}

			$wpdb->insert( $table, $data, $format );
		}

		self::schedule_next_po_run( MINUTE_IN_SECONDS );
	}

	public static function process_forecast_queue(): void {
		self::maybe_install_tables();

		global $wpdb;
		$table = AAA_OC_FORECAST_QUEUE_TABLE;
		if ( ! self::table_exists( $table ) ) { return; }

		$rows = $wpdb->get_results(
			"SELECT * FROM {$table} WHERE status = 'pending' ORDER BY id ASC LIMIT 5",
			ARRAY_A
		);
		if ( empty( $rows ) ) { return; }

		$user_id = get_current_user_id();

		foreach ( $rows as $row ) {
			$id  = absint( $row['id'] ?? 0 );
			$pid = absint( $row['product_id'] ?? 0 );
			if ( ! $id ) { continue; }

			$attempts = absint( $row['attempts'] ?? 0 );
			$update   = [ 'status' => 'processing' ];
			$fmt      = [ '%s' ];

			if ( self::table_has_col( $table, 'attempts' ) ) { $update['attempts'] = $attempts + 1; $fmt[] = '%d'; }
			if ( self::table_has_col( $table, 'user_id' ) ) { $update['user_id'] = $user_id; $fmt[] = '%d'; }
			if ( self::table_has_col( $table, 'updated_by' ) ) { $update['updated_by'] = $user_id; $fmt[] = '%d'; }

			$wpdb->update( $table, $update, [ 'id' => $id ], $fmt, [ '%d' ] );

			if ( $pid && class_exists( 'AAA_OC_Forecast_Runner' ) ) {
				try {
					AAA_OC_Forecast_Runner::update_single_product( $pid );
				} catch ( Throwable $e ) {
					if ( defined( 'AAA_OC_FORECAST_DEBUG' ) && AAA_OC_FORECAST_DEBUG ) {
						error_log( '[Forecast][Queue] Runner failed for product ' . $pid . ': ' . $e->getMessage() );
					}
				}
			}

			$done = [ 'status' => 'done' ];
			$dft  = [ '%s' ];
			if ( self::table_has_col( $table, 'user_id' ) ) { $done['user_id'] = $user_id; $dft[] = '%d'; }
			if ( self::table_has_col( $table, 'updated_by' ) ) { $done['updated_by'] = $user_id; $dft[] = '%d'; }

			$wpdb->update( $table, $done, [ 'id' => $id ], $dft, [ '%d' ] );
		}

		$more = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" );
		if ( $more > 0 ) { self::schedule_next_run( 5 * MINUTE_IN_SECONDS ); }
	}

	public static function process_po_queue(): void {
		self::maybe_install_tables();

		global $wpdb;
		$table = AAA_OC_FORECAST_PO_QUEUE_TABLE;
		if ( ! self::table_exists( $table ) ) { return; }

		$rows = $wpdb->get_results(
			"SELECT * FROM {$table} WHERE status = 'pending' ORDER BY id ASC LIMIT 5",
			ARRAY_A
		);
		if ( empty( $rows ) ) { return; }

		$user_id = get_current_user_id();

		foreach ( $rows as $row ) {
			$id  = absint( $row['id'] ?? 0 );
			$pid = absint( $row['product_id'] ?? 0 );
			if ( ! $id ) { continue; }

			$attempts = absint( $row['attempts'] ?? 0 );
			$update   = [ 'status' => 'processing' ];
			$fmt      = [ '%s' ];

			if ( self::table_has_col( $table, 'attempts' ) ) { $update['attempts'] = $attempts + 1; $fmt[] = '%d'; }
			if ( self::table_has_col( $table, 'user_id' ) ) { $update['user_id'] = $user_id; $fmt[] = '%d'; }
			if ( self::table_has_col( $table, 'updated_by' ) ) { $update['updated_by'] = $user_id; $fmt[] = '%d'; }

			$wpdb->update( $table, $update, [ 'id' => $id ], $fmt, [ '%d' ] );

			if ( $pid ) {
				/**
				 * PO queue processor hook.
				 * Attach your PO builder here so this queue file stays decoupled from PO implementation.
				 */
				do_action( 'aaa_oc_forecast_po_queue_process_product', $pid, $row );
			}

			$done = [ 'status' => 'done' ];
			$dft  = [ '%s' ];
			if ( self::table_has_col( $table, 'user_id' ) ) { $done['user_id'] = $user_id; $dft[] = '%d'; }
			if ( self::table_has_col( $table, 'updated_by' ) ) { $done['updated_by'] = $user_id; $dft[] = '%d'; }

			$wpdb->update( $table, $done, [ 'id' => $id ], $dft, [ '%d' ] );
		}

		$more = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" );
		if ( $more > 0 ) { self::schedule_next_po_run( 5 * MINUTE_IN_SECONDS ); }
	}

	public static function maybe_process_inline(): void {
		if ( ! is_admin() ) { return; }
		if ( get_transient( self::INLINE_LOCK ) ) { return; }

		global $wpdb;
		$table = AAA_OC_FORECAST_QUEUE_TABLE;
		if ( ! self::table_exists( $table ) ) { return; }

		$pending = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" );
		if ( $pending < 1 ) { return; }

		set_transient( self::INLINE_LOCK, 1, MINUTE_IN_SECONDS );
		self::schedule_next_run( MINUTE_IN_SECONDS );
	}

	private static function schedule_next_run( int $delay ): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_single_event( time() + max( 5, $delay ), self::HOOK );
		}
	}

	private static function schedule_next_po_run( int $delay ): void {
		if ( ! wp_next_scheduled( self::PO_HOOK ) ) {
			wp_schedule_single_event( time() + max( 5, $delay ), self::PO_HOOK );
		}
	}

	private static function maybe_install_tables(): void {
		if ( class_exists( 'AAA_OC_Forecast_Queue_Installer' ) ) {
			AAA_OC_Forecast_Queue_Installer::maybe_install_tables();
		}
	}

	private static function table_exists( string $table ): bool {
		global $wpdb;
		$found = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
		return ( $found === $table );
	}

	private static function table_has_col( string $table, string $col ): bool {
		static $cache = [];
		$key = $table . '|' . $col;
		if ( isset( $cache[ $key ] ) ) { return $cache[ $key ]; }
		global $wpdb;
		$found = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $col ) );
		$cache[ $key ] = ( $found === $col );
		return $cache[ $key ];
	}
}
