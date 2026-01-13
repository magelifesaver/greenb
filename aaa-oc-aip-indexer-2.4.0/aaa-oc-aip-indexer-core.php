<?php
/**
 * Core bridge for exposing orders to AIP and adjusting AIP indexing queries.
 *
 * File: /wp-content/plugins/aaa-oc-aip-indexer-2.1.0/aaa-oc-aip-indexer-core.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'AAA_OC_AIP_INDEXER_CORE_LOADED' ) ) {
    return;
}
define( 'AAA_OC_AIP_INDEXER_CORE_LOADED', true );

class AAA_OC_AIP_Indexer_Bridge {

    public static function init() {
        add_filter( 'woocommerce_register_post_type_shop_order', [ __CLASS__, 'shop_order_args' ] );
        add_action( 'init', [ __CLASS__, 'override_aip_update_check' ], 9 );
        add_action( 'pre_get_posts', [ __CLASS__, 'modify_aip_order_query' ] );
        add_filter( 'aipkit_vector_post_processor_supported_post_types', [ __CLASS__, 'filter_supported_post_types' ] );
    }

    public static function shop_order_args( array $args ) : array {
        $args['public']              = true;
        $args['show_ui']             = true;
        $args['exclude_from_search'] = true;
        return $args;
    }

    public static function override_aip_update_check() {
        if ( ! class_exists( '\\WPAICG\\WP_AI_Content_Generator' ) ) {
            return;
        }
        $aip = \WPAICG\WP_AI_Content_Generator::get_instance();
        remove_action( 'init', [ $aip, 'check_for_updates' ], 10 );

        add_action( 'init', function() use ( $aip ) {
            if ( false !== get_site_transient( 'aaa_oc_aip_update_last_run' ) ) {
                return;
            }
            if ( defined( 'AAA_OC_AIP_INDEXER_LOGGING_ENABLED' ) && AAA_OC_AIP_INDEXER_LOGGING_ENABLED ) {
                error_log( '[AAA-OC-AIP] Running AIP update check (daily)' );
            }
            $aip->check_for_updates();
            set_site_transient( 'aaa_oc_aip_update_last_run', 1, DAY_IN_SECONDS );
        }, 10 );
    }

    public static function modify_aip_order_query( $query ) {
        if ( ! is_admin() && ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
            return;
        }

        $type   = $query->get( 'post_type' );
        $status = $query->get( 'post_status' );

        if ( empty( $type ) || $status !== 'publish' ) {
            return;
        }

        $types = is_array( $type ) ? $type : [ $type ];
        if ( ! in_array( 'shop_order', $types, true ) ) {
            return;
        }

        $query->set( 'post_status', [ 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-failed', 'wc-completed' ] );

        $after = gmdate( 'Y-m-d', strtotime( '-90 days' ) );
        $query->set( 'date_query', [ [ 'after' => $after, 'inclusive' => true ] ] );

        if ( defined( 'AAA_OC_AIP_INDEXER_LOGGING_ENABLED' ) && AAA_OC_AIP_INDEXER_LOGGING_ENABLED ) {
            error_log( '[AAA-OC-AIP] Modified order query for AIP indexing' );
        }
    }

    public static function filter_supported_post_types( array $types ) : array {
        if ( ! in_array( 'shop_order', $types, true ) ) {
            $types[] = 'shop_order';
        }
        return $types;
    }

    public static function plugin_action_links( array $links ) : array {
        if ( current_user_can( 'manage_options' ) ) {
            $debug_url = admin_url( 'admin.php?page=aaa-oc-aip-order-debug' );
            $links[]   = '<a href="' . esc_url( $debug_url ) . '">Debug</a>';
        }
        return $links;
    }
}
