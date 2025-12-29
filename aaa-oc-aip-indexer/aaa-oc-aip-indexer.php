<?php
/**
 * Plugin Name: AAA OC AIP Indexer Bridge
 * Description: Bridges the AAA Order Workflow and AIP AI Integration Assistant plugins.  This loader exposes WooCommerce orders to the AIP indexer, limits indexing to incomplete orders from the last 90 days, caches AIP update checks, and duplicates private payment confirmation meta for inclusion.  A debug module is loaded in the admin to inspect order queries during development.
 * Version: 1.4.0
 * Author: AI Assistant
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer.php
 * Purpose: Main loader for the AIP/Order Workflow bridge.  Registers hooks and loads the debug module when in the admin area.  Hard‑codes qualifying order statuses as incomplete (pending, on hold, processing, failed) and limits indexing to orders created in the last 90 days.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent double loading.
if ( defined( 'AAA_OC_AIP_INDEXER_BRIDGE_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_BRIDGE_LOADED', true );

// Local debug toggle.  Set to false to silence log statements for this file.
if ( ! defined( 'AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE' ) ) {
    define( 'AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE', true );
}

/**
 * Main loader class for the AIP/Order Workflow bridge.
 *
 * Exposes the `shop_order` post type to the AIP indexer, adjusts indexing
 * queries for orders, caches AIP database checks, and copies private
 * payment confirmation meta keys to public keys.  When in the admin,
 * it includes a separate debug module to inspect order queries.
 */
class AAA_OC_AIP_Indexer_Bridge {

    /**
     * Registers WordPress hooks for the bridge.
     */
    public static function init() {
        // Make WooCommerce orders public so they appear in AIP post type selections.
        add_filter( 'woocommerce_register_post_type_shop_order', [ __CLASS__, 'shop_order_args' ] );

        // Reduce AIP database update checks to once per day.
        add_action( 'init', [ __CLASS__, 'override_aip_update_check' ], 9 );

        // Duplicate payment confirmation meta keys to public keys on save.
        add_action( 'save_post_payment-confirmation', [ __CLASS__, 'copy_payconfirm_meta' ], 20, 3 );

        // Modify queries when AIP indexes orders: use incomplete statuses and date limit.
        add_action( 'pre_get_posts', [ __CLASS__, 'modify_aip_order_query' ] );

        // Ensure orders appear in the supported post types list for AIP.
        add_filter( 'aipkit_vector_post_processor_supported_post_types', [ __CLASS__, 'filter_supported_post_types' ] );

        // Load the debug module in the admin area.
        if ( is_admin() ) {
            require_once dirname( __FILE__ ) . '/aaa-oc-aip-indexer-debug.php';
        }
    }

    /**
     * Modify registration arguments for the `shop_order` post type.
     *
     * Sets the post type to public and shows a UI so that calls to
     * get_post_types( [ 'public' => true ] ) include orders.  Orders remain
     * excluded from front‑end search and are not publicly queryable.
     *
     * @param array $args The existing post type arguments.
     * @return array The modified arguments.
     */
    public static function shop_order_args( array $args ) : array {
        $args['public']              = true;
        $args['show_ui']             = true;
        $args['exclude_from_search'] = true;
        return $args;
    }

    /**
     * Override the AIP plugin's update check to reduce database queries.
     *
     * The AIP plugin checks for its tables on every page load by default.
     * This method removes that hook and schedules the check to run once
     * per day.  Logs a message when the check runs if debug is enabled.
     */
    public static function override_aip_update_check() {
        if ( ! class_exists( '\\WPAICG\\WP_AI_Content_Generator' ) ) {
            return;
        }
        $aip = \WPAICG\WP_AI_Content_Generator::get_instance();
        // Remove the default update check.
        remove_action( 'init', [ $aip, 'check_for_updates' ], 10 );
        // Run the update check only if the transient has expired.
        add_action( 'init', function() use ( $aip ) {
            if ( false === get_site_transient( 'aaa_oc_aip_update_last_run' ) ) {
                if ( AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE ) {
                    error_log( '[AIP Bridge] Running AIP update check' );
                }
                $aip->check_for_updates();
                set_site_transient( 'aaa_oc_aip_update_last_run', 1, DAY_IN_SECONDS );
            }
        }, 10 );
    }

    /**
     * Copy private payment confirmation meta keys to public keys on save.
     *
     * The AIP plugin only lists public meta keys (without underscores) in its
     * settings UI.  To allow inclusion of payment confirmation data, this
     * method duplicates selected private keys to their public counterparts.
     *
     * @param int     $post_id Post ID being saved.
     * @param \WP_Post $post    The post object.
     * @param bool    $update  Whether this is an update or a new post.
     */
    public static function copy_payconfirm_meta( $post_id, $post, $update ) {
        // Skip autosaves and ensure the correct post type.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( $post->post_type !== 'payment-confirmation' ) {
            return;
        }
        $keys = [
            '_pc_payment_method',
            '_pc_account_name',
            '_pc_amount',
            '_pc_sent_on',
            '_pc_txn',
            '_pc_memo',
            '_pc_matched_order_id',
            '_pc_match_confidence',
            '_pc_match_method',
            '_pc_match_status',
            '_pc_match_reason',
            '_pc_last_match_result',
        ];
        foreach ( $keys as $private ) {
            $value = get_post_meta( $post_id, $private, true );
            if ( empty( $value ) || is_array( $value ) || is_object( $value ) ) {
                continue;
            }
            update_post_meta( $post_id, ltrim( $private, '_' ), $value );
        }
    }

    /**
     * Adjust WP_Query arguments when the AIP plugin queries WooCommerce orders.
     *
     * The AIP automated tasks build queries with 'post_type' => 'shop_order'
     * and 'post_status' => 'publish'.  WooCommerce orders never use
     * 'publish'; they use statuses like wc-pending or wc-processing.  This
     * method replaces the 'publish' status with a list of incomplete
     * statuses and limits the results to orders from the last 90 days.
     *
     * @param \WP_Query $query The current query instance.
     */
    public static function modify_aip_order_query( $query ) {
        // Only run in the admin or cron context; skip front‑end.
        if ( ! is_admin() && ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
            return;
        }
        // Examine the post type and status.
        $type  = $query->get( 'post_type' );
        $status = $query->get( 'post_status' );
        if ( empty( $type ) || $status !== 'publish' ) {
            return;
        }
        $types = is_array( $type ) ? $type : [ $type ];
        if ( ! in_array( 'shop_order', $types, true ) ) {
            return;
        }
        // Replace 'publish' status with incomplete statuses.
        $query->set( 'post_status', [ 'wc-pending', 'wc-completed', 'wc-processing', 'wc-failed' ] );
        // Limit to the last 90 days.
        $after = gmdate( 'Y-m-d', strtotime( '-1 days' ) );
        $query->set( 'date_query', [ [ 'after' => $after, 'inclusive' => true ] ] );
        if ( AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE ) {
            error_log( '[AIP Bridge] Modified order query for AIP indexing' );
        }
    }

    /**
     * Ensure the `shop_order` post type appears in AIP's list of supported types.
     *
     * @param array $types Existing post types supported by AIP.
     * @return array The modified list of post types.
     */
    public static function filter_supported_post_types( array $types ) : array {
        if ( ! in_array( 'shop_order', $types, true ) ) {
            $types[] = 'shop_order';
        }
        return $types;
    }
}

// Initialize hooks.
AAA_OC_AIP_Indexer_Bridge::init();