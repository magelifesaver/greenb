<?php
/**
 * File: plugins/aaa-order-workflow/includes/indexmanager/index/class-aaa-oc-indexmanager-table-indexer.php
 * Purpose: Upsert/purge + bulk reindex for Users/Products/Orders.
 * Notes:
 *  - Reindex uses a 2s single-event schedule (debounced); hooks can also call upsert_now() directly.
 *  - Session-only guard for users is handled here (bypass on 'wp_login').
 *  - Supports computed user columns with token: json:<meta_key>.<dot.path>
 */
if ( ! defined('ABSPATH') ) exit;

class AAA_OC_IndexManager_Table_Indexer {

    /* ===========================
     * Public API
     * ========================= */

    /**
     * Debounced reindex (schedules a single-event 2s later).
     * Hooks should pass a descriptive $src (e.g., 'wp_login', 'save_post_product', etc.).
     */
    public static function reindex(string $entity, int $id, string $src = '') : void {
        if ( $id <= 0 ) return;

        // Users: session-only guard — allow 'wp_login' always, guard others
        if ( $entity === 'users' ) {
            $uopt = AAA_OC_IndexManager_Helpers::get_opt('users');
            if ( ! empty($uopt['session_only']) && $src !== 'wp_login' ) {
                $current = get_current_user_id() ?: ($GLOBALS['aaa_oc_im_last_uid'] ?? 0);
                if ( (int)$current !== $id ) return;
            }
        }

        $key = "aaa_oc_im_pending_{$entity}_{$id}";
        if ( get_transient($key) ) return;
        set_transient($key, 1, 5);

        if ( function_exists('wp_schedule_single_event') ) {
            wp_schedule_single_event( time() + 2, 'aaa_oc_im_reindex_event', [ $entity, $id ] );
        } else {
            self::upsert_now( $entity, $id );
        }
    }

    /** Wire the scheduled event handler. */
    public static function cron_init() : void {
        add_action( 'aaa_oc_im_reindex_event', [ __CLASS__, 'upsert_now' ], 10, 2 );
    }

    /**
     * Immediate upsert (no debounce, no schedule).
     * Safe to call from synchronous situations (e.g., wp_login).
     */
    public static function upsert_now(string $entity, int $id) : void {
        delete_transient( "aaa_oc_im_pending_{$entity}_{$id}" );

        $row = self::build_row( $entity, $id );
        if ( empty($row) ) return;

        global $wpdb;
        $table = AAA_OC_IndexManager_Helpers::table_name( $entity );

        // wpdb->replace needs types
        $fmt = [];
        foreach ( $row as $v ) {
            if ( is_int($v) )       { $fmt[] = '%d'; }
            elseif ( is_float($v) ) { $fmt[] = '%f'; }
            else                    { $fmt[] = '%s'; }
        }

        $wpdb->replace( $table, $row, $fmt );
    }

    /** Delete an entry by primary key for the entity. */
    public static function purge(string $entity, int $id) : void {
        global $wpdb;
        $table = AAA_OC_IndexManager_Helpers::table_name( $entity );
        $cfg   = AAA_OC_IndexManager_Helpers::get_opt( $entity );
        $pk    = $cfg['columns'][0]['col'] ?? 'object_id';
        $wpdb->delete( $table, [ $pk => $id ], [ '%d' ] );
    }

    /**
     * Bulk reindex everything for an entity (used by "Reindex Now" buttons).
     * Users: immediate upsert (fast).
     * Products/Orders: use debounced reindex() so qualifier logic runs consistently.
     */
    public static function reindex_table(string $entity) : void {
        if ( $entity === 'users' ) {
            $ids = get_users( [ 'fields' => 'ID' ] );
            foreach ( (array) $ids as $uid ) {
                self::upsert_now( 'users', (int) $uid );
            }
            return;
        }

        if ( $entity === 'products' ) {
            $q = new WP_Query( [ 'post_type' => 'product', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ] );
            foreach ( (array) $q->posts as $pid ) {
                self::reindex( 'products', (int) $pid, 'bulk' );
            }
            return;
        }

        if ( $entity === 'orders' ) {
            $q = new WP_Query( [ 'post_type' => 'shop_order', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ] );
            foreach ( (array) $q->posts as $oid ) {
                self::reindex( 'orders', (int) $oid, 'bulk' );
            }
            return;
        }
    }

    /* ===========================
     * Row builders
     * ========================= */

    protected static function build_row(string $entity, int $id) : array {
        if ( $entity === 'users' )    return self::build_user( $id );
        if ( $entity === 'products' ) return self::build_product( $id );
        if ( $entity === 'orders' )   return self::build_order( $id );
        return [];
    }

    /**
     * Build Users row from configured columns.
     * Supports:
     *  - source=core (user fields)
     *  - source=meta (user_meta)
     *  - source=computed:
     *       * billing_address | shipping_address
     *       * updated_at
     *       * json:<meta_key>.<path>   (e.g., json:shipping_szbd-picked-location.lat)
     *  - source=table: fetch value from another table keyed by user ID
     */
    protected static function build_user(int $uid) : array {
        $u = get_userdata( $uid );
        if ( ! $u ) return [];

        $cfg = AAA_OC_IndexManager_Helpers::get_opt( 'users' );
        $row = [];

        foreach ( (array) $cfg['columns'] as $c ) {
            $col  = $c['col']    ?? '';
            $src  = $c['source'] ?? 'meta';
            $key  = $c['key']    ?? '';

            if ( ! $col ) continue;

            if ( $src === 'core' ) {
                $row[$col] = ( $key === 'ID' ) ? (int) $u->ID : (string) ( $u->$key ?? '' );
                continue;
            }

            if ( $src === 'meta' ) {
                // Allow meta keys with hyphens, slashes, etc.
                $row[$col] = (string) get_user_meta( $uid, $key, true );
                continue;
            }

            if ( $src === 'computed' ) {
                // Addresses
                if ( $key === 'billing_address' || $key === 'shipping_address' ) {
                    $t = ( $key === 'billing_address' ) ? 'billing' : 'shipping';
                    $g = function( $s ) use ( $uid, $t ) {
                        $v = get_user_meta( $uid, "{$t}_{$s}", true );
                        return ( $v !== '' ) ? $v : get_user_meta( $uid, "_{$t}_{$s}", true );
                    };
                    $a1 = $g('address_1'); $a2 = $g('address_2'); $city = $g('city');
                    $st = $g('state');     $pc = $g('postcode');  $co   = $g('country');
                    $line1 = trim( implode( ' ', array_filter( [ $a1, $a2 ] ) ) );
                    $line2 = trim( implode( ' ', array_filter( [ $city, $st, $pc ] ) ) );
                    $row[$col] = trim( implode( ', ', array_filter( [ $line1, $line2, $co ] ) ) );
                    continue;
                }

                if ( $key === 'updated_at' ) {
                    $row[$col] = current_time( 'mysql', false );
                    continue;
                }

                // json:<meta_key>.<dot.path>
                if ( strpos( $key, 'json:' ) === 0 ) {
                    $spec      = substr( $key, 5 );                  // "<meta_key>.<path>"
                    $meta_key  = $spec;
                    $json_path = '';

                    // split only on the first dot
                    $pos = strpos( $spec, '.' );
                    if ( $pos !== false ) {
                        $meta_key  = substr( $spec, 0, $pos );
                        $json_path = substr( $spec, $pos + 1 );
                    }

                    $raw  = get_user_meta( $uid, $meta_key, true );
                    $val  = self::json_extract( $raw, $json_path );
                    // Normalize numeric strings to numeric types
                  if ( is_numeric( $val ) ) {
                        $row[$col] = 0 + $val;
                    } else {
                        $row[$col] = is_scalar( $val ) ? (string) $val : '';
                    }
                    continue;
                }
            }

            if ( $src === 'table' ) {
                $ext_table   = self::ext_table_name( $c['ext_table'] ?? '' );
                $ext_fk_col  = sanitize_key( $c['ext_fk_col']  ?? '' );
                $ext_val_col = sanitize_key( $c['ext_val_col'] ?? '' );

                if ( $ext_table && $ext_fk_col && $ext_val_col ) {
                    global $wpdb;
                    $sql       = $wpdb->prepare( "SELECT `$ext_val_col` FROM `$ext_table` WHERE `$ext_fk_col` = %d LIMIT 1", $uid );
                    $row[$col] = (string) $wpdb->get_var( $sql );
                } else {
                    $row[$col] = '';
                }
                continue;
            }
        }

        // Ensure primary column is present (fallback to user ID)
        $pk_col = $cfg['columns'][0]['col'] ?? 'object_id';
        if ( empty( $row[ $pk_col ] ) ) $row[ $pk_col ] = (int) $u->ID;

        return $row;
    }

    /**
     * Build Products row from configured columns (core/meta/computed: updated_at).
     */
    protected static function build_product(int $pid) : array {
        if ( ! function_exists('wc_get_product') ) return [];
        $p = wc_get_product( $pid );
        if ( ! $p ) return [];

        $cfg = AAA_OC_IndexManager_Helpers::get_opt( 'products' );
        $row = [];

        foreach ( (array) $cfg['columns'] as $c ) {
            $col = $c['col'] ?? ''; if ( ! $col ) continue;
            $src = $c['source'] ?? 'meta';
            $key = $c['key'] ?? '';

            if ( $src === 'core' ) {
                switch ( $key ) {
                    case 'ID':             $row[$col] = (int) $p->get_id();                     break;
                    case 'sku':            $row[$col] = (string) $p->get_sku();                  break;
                    case 'post_title':     $post = get_post( $p->get_id() ); $row[$col] = $post ? (string) $post->post_title : ''; break;
                    case 'price':          $row[$col] = (float)  $p->get_price( 'edit' );        break;
                    case 'stock_status':   $row[$col] = (string) $p->get_stock_status( 'edit' ); break;
                    case 'stock_quantity': $row[$col] = (int)    $p->get_stock_quantity( 'edit' ); break;
                    default:               $row[$col] = '';                                      break;
                }
                continue;
            }

            if ( $src === 'meta' ) {
                $row[$col] = (string) get_post_meta( $pid, $key, true );
                continue;
            }

            if ( $src === 'computed' && $key === 'updated_at' ) {
                $row[$col] = current_time( 'mysql', false );
                continue;
            }
        }

        // Ensure primary
        $pk_col = $cfg['columns'][0]['col'] ?? 'object_id';
        if ( empty( $row[ $pk_col ] ) ) $row[ $pk_col ] = (int) $pid;

        return $row;
    }

    /**
     * Build Orders row from configured columns (core/meta/computed: updated_at).
     */
    protected static function build_order(int $oid) : array {
        if ( ! function_exists('wc_get_order') ) return [];
        $o = wc_get_order( $oid );
        if ( ! $o ) return [];

        $cfg = AAA_OC_IndexManager_Helpers::get_opt( 'orders' );
        $row = [];

        foreach ( (array) $cfg['columns'] as $c ) {
            $col = $c['col'] ?? ''; if ( ! $col ) continue;
            $src = $c['source'] ?? 'meta';
            $key = $c['key'] ?? '';

            if ( $src === 'core' ) {
                switch ( $key ) {
                    case 'ID':            $row[$col] = (int)    $o->get_id();          break;
                    case 'order_number':  $row[$col] = (string) $o->get_order_number(); break;
                    case 'customer_id':   $row[$col] = (int)    $o->get_customer_id();  break;
                    case 'status':        $row[$col] = (string) $o->get_status();       break;
                    case 'total':         $row[$col] = (float)  $o->get_total();        break;
                    case 'currency':      $row[$col] = (string) $o->get_currency();     break;
                    default:              $row[$col] = '';                              break;
                }
                continue;
            }

            if ( $src === 'meta' ) {
                $row[$col] = (string) get_post_meta( $oid, $key, true );
                continue;
            }

            if ( $src === 'computed' && $key === 'updated_at' ) {
                $row[$col] = current_time( 'mysql', false );
                continue;
            }
        }

        // Ensure primary
        $pk_col = $cfg['columns'][0]['col'] ?? 'object_id';
        if ( empty( $row[ $pk_col ] ) ) $row[ $pk_col ] = (int) $oid;

        return $row;
    }

    /* ===========================
     * Utilities
     * ========================= */

    /** Build a safe, prefixed table name from a suffix (or return empty string). */
    protected static function ext_table_name( string $suffix ) : string {
        global $wpdb;

        $suffix = sanitize_key( $suffix );
        if ( $suffix === '' ) return '';

        if ( strpos( $suffix, $wpdb->prefix ) === 0 ) {
            return $suffix;
        }

        return $wpdb->prefix . $suffix;
    }

    /**
     * Extract value from usermeta JSON.
     * - $raw can be string JSON or scalar; returns '' if not found
     * - $path supports dot notation (e.g., "lat" or "a.b.c")
     */
    protected static function json_extract( $raw, string $path ) {
        if ( ! is_string($raw) || $raw === '' ) return '';
        $data = json_decode( $raw, true );
        if ( ! is_array($data) ) return '';

        if ( $path === '' ) return $data; // not typical, but supported

        $cur = $data;
        foreach ( explode('.', $path) as $seg ) {
            if ( $seg === '' ) continue;
            if ( is_array($cur) && array_key_exists($seg, $cur) ) {
                $cur = $cur[$seg];
            } else {
                return '';
            }
        }
        return $cur;
    }
}

AAA_OC_IndexManager_Table_Indexer::cron_init();
