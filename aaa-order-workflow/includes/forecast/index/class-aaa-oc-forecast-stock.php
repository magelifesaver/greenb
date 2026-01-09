<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/index/class-aaa-oc-forecast-stock.php
 * Purpose: Calculates current stock metrics for a product. The calculations
 *          include stock quantity, margin percentage and frozen capital.
 *          Cost is resolved via ATUM purchase price, Woo COGS or a
 *          percentage of the product price.
 *
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Stock {
    /**
     * Compute stock-related metrics for a WooCommerce product.
     *
     * @param WC_Product $product Product instance.
     * @return array<string,mixed>    Metrics keyed by meta key name.
     */
    public static function calculate( WC_Product $product ) : array {
        $price = floatval( $product->get_price() );
        $stock = intval( $product->get_stock_quantity() );
        $cost  = self::get_cost( $product );
        $margin_pct     = ( $price > 0 && $cost >= 0 ) ? round( ( ( $price - $cost ) / $price ) * 100, 2 ) : 0;
        $frozen_capital = round( $stock * $cost, 2 );
        return [
            'forecast_stock_qty'      => $stock,
            'forecast_margin_percent' => $margin_pct,
            'forecast_frozen_capital' => $frozen_capital,
        ];
    }

    /**
     * Resolve per-unit cost via ATUM purchase price, WooCommerce COGS or a
     * percent override. Falls back to a global cost percent if present.
     *
     * @param WC_Product $product
     * @return float
     */
    protected static function get_cost( WC_Product $product ) : float {
        $product_id = $product->get_id();
        // 1) ATUM purchase price
        $cost = get_post_meta( $product_id, '_purchase_price', true );
        // 2) WooCommerce cost of goods
        if ( $cost === '' ) {
            $cost = get_post_meta( $product_id, '_cogs_total_value', true );
        }
        // 3) Override percentage or global fallback
        if ( $cost === '' ) {
            $override = get_post_meta( $product_id, 'forecast_cost_override', true );
            $default_pct = function_exists( 'aaa_oc_get_option' ) ? floatval( aaa_oc_get_option( 'global_cost_percent', 'forecast', 50 ) ) : 50;
            $pct = ( $override !== '' ) ? floatval( $override ) : $default_pct;
            $cost = floatval( $product->get_price() ) * $pct / 100;
        }
        return floatval( $cost );
    }
}