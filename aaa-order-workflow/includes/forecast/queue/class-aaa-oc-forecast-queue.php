<?php
/**
 * Forecast queue handling.
 *
 * Provides CRUD and background processing for the forecast queue. Products
 * are queued when inventory needs to be analysed and forecasts generated.
 * The queue is processed in batches via WP‑Cron and can also be triggered
 * manually from the admin interface.
 *
 * @package AAA_Order_Workflow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AAA_OC_Forecast_Queue
 *
 * Manages the forecast queue. Methods in this class are static to simplify
 * usage and to mirror the original plugin design. Each method performs a
 * specific CRUD or scheduling action, and hooks are registered via the
 * init() method.
 */
class AAA_OC_Forecast_Queue {
    /**
     * WP‑Cron hook name for processing the forecast queue.
     *
     * Using a private constant avoids accidental external modification.
     */
    private const HOOK = 'aaa_oc_process_forecast_queue';

    /**
     * Transient key used to throttle inline processing. Prevents
     * multiple simultaneous cron events during admin page loads.
     */
    private const INLINE_LOCK = 'aaa_oc_forecast_queue_inline_lock';

    /**
     * WP‑Cron hook name for queueing all enabled products. This is a
     * separate hook so it can be scheduled independently without
     * interfering with the main processing hook.
     */
    private const QUEUE_ALL_HOOK = 'aaa_oc_queue_all_enabled_products';

    /**
     * Register actions on WordPress hooks.
     */
    public static function init(): void {
        // Clear any legacy recurring schedules for our cron hook on init.
        add_action( 'init', [ __CLASS__, 'clear_legacy_schedules' ] );
        // Process a small batch of queued items via WP‑Cron.
        add_action( self::HOOK, [ __CLASS__, 'process_queue' ] );
        // Schedule queueing of all enabled products.
        add_action( self::QUEUE_ALL_HOOK, [ __CLASS__, 'queue_all_enabled' ] );
        // During admin page loads, schedule processing if items are waiting.
        add_action( 'admin_init', [ __CLASS__, 'maybe_process_inline' ] );
    }

    /**
     * Clear any recurring schedules left over from older versions. This
     * ensures that our hook runs only when explicitly scheduled.
     */
    public static function clear_legacy_schedules(): void {
        $event = wp_get_scheduled_event( self::HOOK );
        if ( $event && ! empty( $event->schedule ) ) {
            wp_clear_scheduled_hook( self::HOOK );
        }
    }

    /**
     * Enqueue a single product for forecast processing.
     *
     * @param int $product_id Product ID.
     */
    public static function enqueue_product( int $product_id ): void {
        $pid = absint( $product_id );
        if ( $pid ) {
            self::queue_products( [ $pid ] );
        }
    }

    /**
     * Enqueue multiple products for forecast processing.
     *
     * @param array<int> $product_ids List of product IDs.
     */
    public static function queue_products( array $product_ids ): void {
        global $wpdb;
        $ids = array_values( array_filter( array_map( 'absint', (array) $product_ids ) ) );
        if ( empty( $ids ) ) {
            return;
        }

        // Ensure our tables exist before inserting.
        AAA_OC_Forecast_Queue_Installer::maybe_install_tables();

        $table   = AAA_OC_FORECAST_QUEUE_TABLE;
        $user_id = get_current_user_id();

        if ( ! self::table_exists( $table ) ) {
            return;
        }

        foreach ( $ids as $pid ) {
            // Skip if already queued or processing for this product.
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$table} WHERE product_id = %d AND status IN ('pending','processing') LIMIT 1",
                $pid
            ) );
            if ( $exists ) {
                continue;
            }

            $data   = [ 'product_id' => $pid, 'status' => 'pending' ];
            $format = [ '%d', '%s' ];

            if ( self::table_has_column( $table, 'user_id' ) ) {
                $data['user_id'] = $user_id;
                $format[]       = '%d';
            }
            if ( self::table_has_column( $table, 'attempts' ) ) {
                $data['attempts'] = 0;
                $format[]         = '%d';
            }

            $wpdb->insert( $table, $data, $format );
        }

        // Schedule processing soon.
        self::schedule_process( MINUTE_IN_SECONDS );
    }

    /**
     * Schedule processing of the forecast queue. The delay ensures a
     * minimum grace period and avoids multiple schedules if one is
     * already pending.
     *
     * @param int $delay Delay in seconds.
     */
    public static function schedule_process( int $delay = MINUTE_IN_SECONDS ): void {
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_single_event( time() + max( 5, $delay ), self::HOOK );
        }
    }

    /**
     * Schedule the cron job that queues all enabled products. This is
     * typically triggered by an admin-post handler.
     *
     * @param int $delay Delay in seconds.
     */
    public static function schedule_queue_all_enabled( int $delay = MINUTE_IN_SECONDS ): void {
        if ( ! wp_next_scheduled( self::QUEUE_ALL_HOOK ) ) {
            wp_schedule_single_event( time() + max( 5, $delay ), self::QUEUE_ALL_HOOK );
        }
    }

    /**
     * Queue all enabled products. Products are considered enabled if
     * they have a meta value of 'yes' for the key 'forecast_enable_reorder'.
     */
    public static function queue_all_enabled(): void {
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
            self::queue_products( $ids );
        }
    }

    /**
     * Process a batch of pending forecast queue rows. Up to five items
     * are processed per run to keep runtime reasonable. Completed rows
     * are marked as 'done'.
     */
    public static function process_queue(): void {
        global $wpdb;

        AAA_OC_Forecast_Queue_Installer::maybe_install_tables();

        $table = AAA_OC_FORECAST_QUEUE_TABLE;
        if ( ! self::table_exists( $table ) ) {
            return;
        }

        $rows = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY id ASC LIMIT 5",
            ARRAY_A
        );
        if ( empty( $rows ) ) {
            return;
        }

        $user_id = get_current_user_id();

        foreach ( $rows as $row ) {
            $id  = absint( $row['id'] ?? 0 );
            $pid = absint( $row['product_id'] ?? 0 );
            if ( ! $id ) {
                continue;
            }

            $attempts = absint( $row['attempts'] ?? 0 );
            $update   = [ 'status' => 'processing' ];
            $fmt      = [ '%s' ];

            if ( self::table_has_column( $table, 'attempts' ) ) {
                $update['attempts'] = $attempts + 1;
                $fmt[]              = '%d';
            }
            if ( self::table_has_column( $table, 'user_id' ) ) {
                $update['user_id'] = $user_id;
                $fmt[]             = '%d';
            }

            $wpdb->update( $table, $update, [ 'id' => $id ], $fmt, [ '%d' ] );

            // Fire the forecast runner if available. Catch any exceptions
            // and log them in debug mode.
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
            if ( self::table_has_column( $table, 'user_id' ) ) {
                $done['user_id'] = $user_id;
                $dft[]           = '%d';
            }

            $wpdb->update( $table, $done, [ 'id' => $id ], $dft, [ '%d' ] );
        }

        $more = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" );
        if ( $more > 0 ) {
            self::schedule_process( 5 * MINUTE_IN_SECONDS );
        }
    }

    /**
     * During admin page loads, schedule processing of the queue if
     * pending items exist. This allows the queue to run in the
     * background without waiting for cron.
     */
    public static function maybe_process_inline(): void {
        if ( ! is_admin() ) {
            return;
        }
        if ( get_transient( self::INLINE_LOCK ) ) {
            return;
        }
        global $wpdb;
        $table = AAA_OC_FORECAST_QUEUE_TABLE;
        if ( ! self::table_exists( $table ) ) {
            return;
        }
        $pending = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" );
        if ( $pending < 1 ) {
            return;
        }
        set_transient( self::INLINE_LOCK, 1, MINUTE_IN_SECONDS );
        self::schedule_process( MINUTE_IN_SECONDS );
    }

    /**
     * Check if a database table exists. Uses caching to avoid
     * repetitive SHOW TABLES queries.
     *
     * @param string $table Table name.
     * @return bool Whether the table exists.
     */
    private static function table_exists( string $table ): bool {
        global $wpdb;
        $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        return ( $found === $table );
    }

    /**
     * Check if a table contains a specific column. Results are
     * cached for the lifetime of the request.
     *
     * @param string $table Table name.
     * @param string $col   Column name.
     * @return bool Whether the column exists on the table.
     */
    private static function table_has_column( string $table, string $col ): bool {
        static $cache = [];
        $key = $table . '|' . $col;
        if ( isset( $cache[ $key ] ) ) {
            return $cache[ $key ];
        }
        global $wpdb;
        $found        = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $col ) );
        $cache[ $key ] = ( $found === $col );
        return $cache[ $key ];
    }
}