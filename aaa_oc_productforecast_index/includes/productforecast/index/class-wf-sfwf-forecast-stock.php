<?php
/**
 * Filepath: sfwf/index/class-wf-sfwf-forecast-stock.php
 * ---------------------------------------------------------------------------
 * Calculates stock-dependent product metrics:
 * - forecast_stock_qty
 * - forecast_margin_percent
 * - forecast_frozen_capital
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WF_SFWF_Forecast_Stock {

    /**
     * Calculates current stock metrics for a given product.
     *
     * @param WC_Product $product
     * @return array
     */
    public static function calculate( $product ) {
        $product_id = $product->get_id();

        $price = floatval( $product->get_price() );
        $stock = intval( $product->get_stock_quantity() );
        $cost  = self::get_cost( $product );

        $margin_pct     = ( $price > 0 && $cost >= 0 ) ? round( (($price - $cost) / $price) * 100, 2 ) : 0;
        $frozen_capital = round( $stock * $cost, 2 );

        return [
            'forecast_stock_qty'      => $stock,
            'forecast_margin_percent' => $margin_pct,
            'forecast_frozen_capital' => $frozen_capital,
        ];
    }

    /**
     * Resolves cost based on:
     * 1. ATUM _purchase_price
     * 2. Woo _cogs_total_value
     * 3. forecast_cost_override or global fallback
     *
     * @param WC_Product $product
     * @return float
     */
    protected static function get_cost( $product ) {
        $product_id = $product->get_id();

        $cost = get_post_meta($product_id, '_purchase_price', true);
        if ( $cost === '' ) {
            $cost = get_post_meta($product_id, '_cogs_total_value', true);
        }

        if ( $cost === '' ) {
            $override = get_post_meta($product_id, 'forecast_cost_override', true);
            $fallback = WF_SFWF_Settings::get('global_cost_percent', 50);
            $cost_pct = $override !== '' ? floatval($override) : floatval($fallback);
            $cost = floatval($product->get_price()) * $cost_pct / 100;
        }

        return floatval($cost);
    }
}
