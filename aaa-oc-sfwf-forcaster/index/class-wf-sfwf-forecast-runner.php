<?php
/**
 * Filepath: sfwf/index/class-wf-sfwf-forecast-runner.php
 * ---------------------------------------------------------------------------
 * Main entry point to run forecast calculations.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WF_SFWF_Forecast_Runner {

	/**
	 * Run forecast for all products with forecast_enable_reorder = yes.
	 */
	public static function update_all_products() {
		if ( ! function_exists('wc_get_products') ) return;

		$args = array(
			'limit'      => -1,
			'status'     => array( 'publish', 'private' ),
			'type'       => array( 'simple', 'variation' ),
			'return'     => 'ids',
			'meta_query' => array(
				array(
					'key'     => 'forecast_enable_reorder',
					'value'   => 'yes',
					'compare' => '=',
				),
			),
		);

		$product_ids = wc_get_products( $args );
		foreach ( (array) $product_ids as $product_id ) {
			self::update_single_product( $product_id );
		}
	}

	/**
	 * Run forecast for a single product.
	 *
	 * @return bool True when processed and written; false when skipped.
	 */
	public static function update_single_product( $product_id ) {
		$product_id = absint( $product_id );
		if ( ! $product_id || ! function_exists('wc_get_product') ) return false;

		$product = wc_get_product( $product_id );
		if ( ! $product ) return false;
		if ( ! method_exists($product, 'managing_stock') || ! $product->managing_stock() ) return false;

		$gridwin  = WF_SFWF_Settings::get('grid_sales_window_days', 90);
		$timeline = WF_SFWF_Forecast_Timeline::calculate( $product_id );
		$sales    = WF_SFWF_Forecast_Sales_Metrics::calculate(
			$product_id,
			$timeline['forecast_first_sold_date'],
			$timeline['forecast_last_sold_date'],
			$gridwin
		);
		$stock    = WF_SFWF_Forecast_Stock::calculate( $product_id );
		$proj     = WF_SFWF_Forecast_Projections::calculate( $product, $sales['forecast_sales_day'] );
		$status   = WF_SFWF_Forecast_Status::calculate( $product_id );
		$flags    = WF_SFWF_Forecast_Overrides::get_flags( $product_id );

		$fields = array_merge( $timeline, $sales, $stock, $proj, $status, $flags );
		WF_SFWF_Forecast_Meta_Updater::write( $product_id, $fields );

		return true;
	}
}
