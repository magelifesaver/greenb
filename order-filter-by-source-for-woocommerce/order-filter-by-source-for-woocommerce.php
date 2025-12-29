<?php
/*
	Plugin name: Order Filter by Source for WooCommerce
	Description: Allows to filter orders by the order attribution source in the dashboard.
	Version: 1.0
	Author: Misha Rudrastyh
	Author URI: https://rudrastyh.com
	Requires Plugins: woocommerce
	License: GPL v2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
	Text domain: order-filter-by-source-for-woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Automattic\WooCommerce\Utilities\OrderUtil;

if( ! class_exists( 'rudrUtmSourceOrderFilter' ) ) {
	class rudrUtmSourceOrderFilter {

		public function __construct() {
			add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'select' ), 25, 2 );
			add_action( 'restrict_manage_posts', array( $this, 'select' ), 25, 2 );

			add_action( 'woocommerce_order_list_table_prepare_items_query_args', array( $this, 'filter_hpos' ), 25 );
			add_action( 'pre_get_posts', array( $this, 'filter_cpt' ), 25 );

		}

		public function select( $post_type, $which ) {

			global $wpdb;

			if( 'shop_order' !== $post_type ) {
				return;
			}

			if( OrderUtil::custom_orders_table_usage_is_enabled() ) {

				$utm_sources = wp_cache_get( 'usof_utm_sources' );
				if( false === $utm_sources ) {
					$utm_sources = $wpdb->get_col(
						"
						SELECT DISTINCT meta_value
						FROM {$wpdb->prefix}wc_orders_meta
						WHERE meta_key = '_wc_order_attribution_utm_source'
						ORDER BY meta_value
						"
					);
					wp_cache_set( 'usof_utm_sources', $utm_sources );
				}

			} else {

				$utm_sources = wp_cache_get( 'usof_utm_sources' );
				if( false === $utm_sources ) {
					$utm_sources = $wpdb->get_col(
						"
						SELECT DISTINCT meta_value
						FROM {$wpdb->postmeta}
						WHERE meta_key = '_wc_order_attribution_utm_source'
						ORDER BY meta_value
						"
					);
					wp_cache_set( 'usof_utm_sources', $utm_sources );
				}

			}

			$current = isset( $_GET[ 'utm_source_filter' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'utm_source_filter' ] ) ) : '';

			if( $utm_sources ) {
				?><select name="utm_source_filter"><option value=""><?php esc_html_e( 'Filter by source', 'order-filter-by-source-for-woocommerce' ) ?></option>
					<?php
						foreach( $utm_sources as $utm_source ) {
							$utm_source_label = str_starts_with( $utm_source, '(' ) ? ucfirst( trim( $utm_source, '()' ) ) : $utm_source;
							?><option value="<?php echo esc_attr( $utm_source ) ?>"<?php selected( $current, $utm_source ) ?>><?php echo esc_html( $utm_source_label ) ?></option><?php
						}
					?>
				</select><?php
			}

		}

		public function filter_hpos( $query_args ) {

			// get current meta query value
			$meta_query = isset( $query_args[ 'meta_query' ] ) ? $query_args[ 'meta_query' ] : array();
			$utm_source_filter = ! empty( $_GET[ 'utm_source_filter' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'utm_source_filter' ] ) ) : '';

			if( $utm_source_filter ) {
				$meta_query[] = array(
					'key' => '_wc_order_attribution_utm_source',
					'value' => $utm_source_filter,
					'compare' => 'LIKE',
				);
			}

			$query_args[ 'meta_query' ] = $meta_query;

			return $query_args;

		}

		public function filter_cpt( $query ) {

			if( ! is_admin() ) {
				return;
			}

			global $pagenow;

			if( 'edit.php' !== $pagenow || 'shop_order' !== $query->get( 'post_type' ) ) {
				return;
			}

			$meta_query = $query->get( 'meta_query' ) ? $query->get( 'meta_query' ) : array();
			$utm_source_filter = ! empty( $_GET[ 'utm_source_filter' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'utm_source_filter' ] ) ) : '';

			if( $utm_source_filter ) {

				$meta_query[] = array(
					'key' => '_wc_order_attribution_utm_source',
					'value' => $utm_source_filter,
					'compare' => 'LIKE',
				);

			}

			$query->set( 'meta_query', $meta_query );

		}


	}
	new rudrUtmSourceOrderFilter;

}
