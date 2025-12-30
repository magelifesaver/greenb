<?php
/**
 * Core functionality for the AAA OC AIP Indexer Bridge.
 *
 * Provides the main bridge class responsible for exposing orders,
 * overriding update checks, adjusting AIP queries, and adding admin links.
 * Keeping this logic separate helps keep files short and maintainable.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent double loading.
if ( defined( 'AAA_OC_AIP_INDEXER_CORE_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_CORE_LOADED', true );

/**
 * Main loader class for the AIP/Order Workflow bridge.
 *
 * Registers filters and actions to expose orders and tune AIP queries.
 */
class AAA_OC_AIP_Indexer_Bridge {

    /** Registers WordPress hooks for the bridge. */
    public static function init() {
        // Make WooCommerce orders public so they appear in AIP post type selections.
        add_filter( 'woocommerce_register_post_type_shop_order', [ __CLASS__, 'shop_order_args' ] );

        // Reduce AIP database update checks to once per day.
        add_action( 'init', [ __CLASS__, 'override_aip_update_check' ], 9 );

        // Payment confirmation meta sync is handled in its own module.

        // Modify queries when AIP indexes orders: use incomplete statuses and date limit.
        add_action( 'pre_get_posts', [ __CLASS__, 'modify_aip_order_query' ] );

        // Ensure orders appear in the supported post types list for AIP.
        add_filter( 'aipkit_vector_post_processor_supported_post_types', [ __CLASS__, 'filter_supported_post_types' ] );

    }

    /**
     * Modify registration arguments for the `shop_order` post type.
     *
     * Sets the post type to public and shows a UI so calls to
     * get_post_types( [ 'public' => true ] ) include orders. Orders remain
     * excluded from front‑end search.
     *
     * @param array $args Existing arguments.
     * @return array Modified arguments.
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
     * Removes the frequent table check and schedules it to run once daily.
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
                if ( defined( 'AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE' ) && AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE ) {
                    error_log( '[AIP Bridge] Running AIP update check' );
                }
                $aip->check_for_updates();
                set_site_transient( 'aaa_oc_aip_update_last_run', 1, DAY_IN_SECONDS );
            }
        }, 10 );
    }


    /**
     * Adjust WP_Query arguments when the AIP plugin queries orders.
     *
     * Replaces the 'publish' status with WooCommerce statuses and limits
     * results to the last 90 days.
     *
     * @param \WP_Query $query Current query.
     */
    public static function modify_aip_order_query( $query ) {
        // Only run in the admin or cron context; skip front‑end.
        if ( ! is_admin() && ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
            return;
        }
        // Examine the post type and status.
        $type   = $query->get( 'post_type' );
        $status = $query->get( 'post_status' );
        if ( empty( $type ) || $status !== 'publish' ) {
            return;
        }
        $types = is_array( $type ) ? $type : [ $type ];
        if ( ! in_array( 'shop_order', $types, true ) ) {
            return;
        }
        // Replace 'publish' status with incomplete + completed statuses.
        $query->set( 'post_status', [ 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-failed', 'wc-completed' ] );
        // Limit to the last 90 days.
        $after = gmdate( 'Y-m-d', strtotime( '-90 days' ) );
        $query->set( 'date_query', [ [ 'after' => $after, 'inclusive' => true ] ] );
        if ( defined( 'AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE' ) && AAA_OC_AIP_INDEXER_DEBUG_THIS_FILE ) {
            error_log( '[AIP Bridge] Modified order query for AIP indexing' );
        }
    }

    /**
     * Ensure the `shop_order` post type appears in AIP's supported types.
     *
     * @param array $types Existing post types.
     * @return array Modified list.
     */
    public static function filter_supported_post_types( array $types ) : array {
        if ( ! in_array( 'shop_order', $types, true ) ) {
            $types[] = 'shop_order';
        }
        return $types;
    }

    /**
     * Add settings/debug link to the plugin row on the plugins page.
     *
     * @param array $links Existing action links.
     * @return array Modified links.
     */
    public static function plugin_action_links( array $links ) : array {
        if ( current_user_can( 'manage_options' ) ) {
            $debug_url = admin_url( 'admin.php?page=aaa-oc-aip-order-debug' );
            $links[]   = '<a href="' . esc_url( $debug_url ) . '">' . esc_html__( 'Debug', 'aaa-oc-aip-indexer' ) . '</a>';
        }
        return $links;
    }
}