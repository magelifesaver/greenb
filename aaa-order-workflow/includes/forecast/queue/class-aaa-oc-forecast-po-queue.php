<?php
/**
 * Purchase order queue handling.
 *
 * Provides CRUD and background processing for the PO queue. Products
 * enter this queue when a reorder needs to be triggered, and each item
 * results in the creation of a purchase order via the PO manager. The
 * queue mirrors much of the logic of the forecast queue but includes a
 * quantity column and delegates order creation to a separate class.
 *
 * @package AAA_Order_Workflow
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AAA_OC_Forecast_PO_Queue
 *
 * Manages the purchase order queue. All methods are static to simplify
 * usage and to mirror the design of the forecast queue class. Each
 * pending row in the PO queue corresponds to a purchase order that
 * will be created when processed.
 */
class AAA_OC_Forecast_PO_Queue {
    /**
     * Cron hook for processing PO queue items.
     */
    private const HOOK = 'aaa_oc_process_po_queue';

    /**
     * Transient key used to throttle inline PO processing.
     */
    private const INLINE_LOCK = 'aaa_oc_po_queue_inline_lock';

    /**
     * Register hooks for this queue.
     */
    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'clear_legacy_schedules' ] );
        add_action( self::HOOK, [ __CLASS__, 'process_queue' ] );
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
     * Enqueue a single product into the PO queue.
     *
     * @param int $product_id Product ID.
     * @param int $qty        Quantity for the PO.
     */
    public static function enqueue_product( int $product_id, int $qty = 1 ): void {
        $pid = absint( $product_id );
        $qty = max( 1, absint( $qty ) );
        if ( $pid ) {
            self::queue_products( [ [ 'id' => $pid, 'qty' => $qty ] ] );
        }
    }

    /**
     * Enqueue multiple products into the PO queue. Items should be
     * associative arrays containing id and qty keys.
     *
     * @param array<array{ id: int, qty?: int }> $items Items to queue.
     */
    public static function queue_products( array $items ): void {
        global $wpdb;
        if ( empty( $items ) ) {
            return;
        }

        AAA_OC_Forecast_Queue_Installer::maybe_install_tables();
        $table   = AAA_OC_FORECAST_PO_QUEUE_TABLE;
        $user_id = get_current_user_id();

        if ( ! self::table_exists( $table ) ) {
            return;
        }

        foreach ( $items as $item ) {
            $pid = absint( $item['id'] ?? 0 );
            $qty = max( 1, absint( $item['qty'] ?? 1 ) );
            if ( ! $pid ) {
                continue;
            }
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$table} WHERE product_id = %d AND status IN ('pending','processing') LIMIT 1",
                $pid
            ) );
            if ( $exists ) {
                continue;
            }
            $data   = [ 'product_id' => $pid, 'status' => 'pending' ];
            $format = [ '%d', '%s' ];
            if ( self::table_has_column( $table, 'quantity' ) ) {
                $data['quantity'] = $qty;
                $format[]        = '%d';
            }
            if ( self::table_has_column( $table, 'user_id' ) ) {
                $data['user_id'] = $user_id;
                $format[]        = '%d';
            }
            if ( self::table_has_column( $table, 'attempts' ) ) {
                $data['attempts'] = 0;
                $format[]         = '%d';
            }
            $wpdb->insert( $table, $data, $format );
        }
        // Schedule processing.
        self::schedule_process( MINUTE_IN_SECONDS );
    }

    /**
     * Schedule processing of the PO queue.
     *
     * @param int $delay Delay in seconds.
     */
    public static function schedule_process( int $delay = MINUTE_IN_SECONDS ): void {
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_single_event( time() + max( 5, $delay ), self::HOOK );
        }
    }

    /**
     * Process a batch of PO queue items. Each pending row results
     * in the creation of a purchase order via the PO manager. Once
     * processed the row is marked as done.
     */
    public static function process_queue(): void {
        global $wpdb;
        AAA_OC_Forecast_Queue_Installer::maybe_install_tables();
        $table = AAA_OC_FORECAST_PO_QUEUE_TABLE;
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
            $qty = absint( $row['quantity'] ?? 1 );
            $attempts = absint( $row['attempts'] ?? 0 );
            if ( ! $id ) {
                continue;
            }
            // Move to processing.
            $update = [ 'status' => 'processing' ];
            $fmt    = [ '%s' ];
            if ( self::table_has_column( $table, 'attempts' ) ) {
                $update['attempts'] = $attempts + 1;
                $fmt[]              = '%d';
            }
            if ( self::table_has_column( $table, 'user_id' ) ) {
                $update['user_id'] = $user_id;
                $fmt[]             = '%d';
            }
            $wpdb->update( $table, $update, [ 'id' => $id ], $fmt, [ '%d' ] );
            // Create a purchase order via the manager.
            if ( $pid ) {
                AAA_OC_PO_Manager::create_purchase_order( $pid, $qty, $row );
            }
            // Mark as done.
            $done = [ 'status' => 'done' ];
            $df   = [ '%s' ];
            if ( self::table_has_column( $table, 'user_id' ) ) {
                $done['user_id'] = $user_id;
                $df[]            = '%d';
            }
            $wpdb->update( $table, $done, [ 'id' => $id ], $df, [ '%d' ] );
        }
        $more = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" );
        if ( $more > 0 ) {
            self::schedule_process( 5 * MINUTE_IN_SECONDS );
        }
    }

    /**
     * Inline processing during admin page loads. If pending items exist
     * and no inline processing is currently scheduled, schedule a run
     * shortly. This helps avoid leaving orders stuck.
     */
    public static function maybe_process_inline(): void {
        if ( ! is_admin() ) {
            return;
        }
        if ( get_transient( self::INLINE_LOCK ) ) {
            return;
        }
        global $wpdb;
        $table = AAA_OC_FORECAST_PO_QUEUE_TABLE;
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
     * Check if a table exists in the database.
     *
     * @param string $table Table name.
     * @return bool
     */
    private static function table_exists( string $table ): bool {
        global $wpdb;
        $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        return ( $found === $table );
    }

    /**
     * Check if a table has a specific column, with caching.
     *
     * @param string $table Table name.
     * @param string $col   Column name.
     * @return bool
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