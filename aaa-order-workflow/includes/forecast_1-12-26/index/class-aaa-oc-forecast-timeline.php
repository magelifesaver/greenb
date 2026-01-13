<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/index/class-aaa-oc-forecast-timeline.php
 * Purpose: Retrieves key lifecycle dates for a product, including first and
 *          last sale dates and first and last purchase order dates. These
 *          dates feed into sales metrics and status evaluations.
 *
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Timeline {
    /**
     * Get timeline values for a product.
     *
     * @param int $product_id
     * @return array<string,string>
     */
    public static function get_timeline( $product_id ) : array {
        return [
            'forecast_first_sold_date' => self::get_first_sold( $product_id ),
            'forecast_last_sold_date'  => self::get_last_sold( $product_id ),
            'forecast_first_purchased' => self::get_first_purchased( $product_id ),
            'forecast_last_purchased'  => self::get_last_purchased( $product_id ),
        ];
    }

    protected static function get_first_sold( $product_id ) : string {
        global $wpdb;
        $result = $wpdb->get_var( $wpdb->prepare( "
            SELECT MIN(p.post_date)
            FROM {$wpdb->prefix}posts p
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta om ON oi.order_item_id = om.order_item_id
            WHERE om.meta_key = '_product_id'
              AND om.meta_value = %d
              AND p.post_type = 'shop_order'
              AND p.post_status IN ('wc-completed','wc-processing')
        ", $product_id ) );
        return $result ?: '';
    }

    protected static function get_last_sold( $product_id ) : string {
        global $wpdb;
        $result = $wpdb->get_var( $wpdb->prepare( "
            SELECT MAX(p.post_date)
            FROM {$wpdb->prefix}posts p
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta om ON oi.order_item_id = om.order_item_id
            WHERE om.meta_key = '_product_id'
              AND om.meta_value = %d
              AND p.post_type = 'shop_order'
              AND p.post_status IN ('wc-completed','wc-processing')
        ", $product_id ) );
        return $result ?: '';
    }

    protected static function get_first_purchased( $product_id ) : string {
        global $wpdb;
        $result = $wpdb->get_var( $wpdb->prepare( "
            SELECT MIN(p.post_date)
            FROM {$wpdb->prefix}atum_order_itemmeta oim
            JOIN {$wpdb->prefix}atum_order_items oi ON oim.order_item_id = oi.order_item_id
            JOIN {$wpdb->posts} p ON oi.order_id = p.ID
            WHERE oim.meta_key = '_product_id'
              AND oim.meta_value = %d
              AND p.post_type = 'atum_purchase_order'
              AND p.post_status NOT IN ('trash','draft')
        ", $product_id ) );
        return $result ?: '';
    }

    protected static function get_last_purchased( $product_id ) : string {
        global $wpdb;
        $result = $wpdb->get_var( $wpdb->prepare( "
            SELECT MAX(p.post_date)
            FROM {$wpdb->prefix}atum_order_itemmeta oim
            JOIN {$wpdb->prefix}atum_order_items oi ON oim.order_item_id = oi.order_item_id
            JOIN {$wpdb->posts} p ON oi.order_id = p.ID
            WHERE oim.meta_key = '_product_id'
              AND oim.meta_value = %d
              AND p.post_type = 'atum_purchase_order'
              AND p.post_status NOT IN ('trash','draft')
        ", $product_id ) );
        return $result ?: '';
    }
}