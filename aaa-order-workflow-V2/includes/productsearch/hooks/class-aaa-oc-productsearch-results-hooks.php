<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/productsearch/hooks/class-aaa-oc-productsearch-results-hooks.php
 * Purpose: Fast search results renderer using aaa_oc_productsearch_index + EEE carousel layout.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAA_OC_ProductSearch_Results_Hooks {

	/**
	 * Wire hooks.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_intercept_search' ), 9 );
	}

	/**
	 * Intercept front-end product searches and render using fast index-based template.
	 */
	public static function maybe_intercept_search() {
		if ( is_admin() ) {
			return;
		}

		if ( ! is_search() ) {
			return;
		}

		// Only intercept product searches (or generic search with no explicit post_type).
		$post_type = get_query_var( 'post_type' );
		if ( $post_type && 'product' !== $post_type ) {
			return;
		}

		// Require ProductSearch module.
		if ( ! class_exists( 'AAA_OC_ProductSearch_Table_Installer' ) || ! class_exists( 'AAA_OC_ProductSearch_Helpers' ) ) {
			return;
		}

		$q = get_query_var( 's' );
		if ( ! $q ) {
			return;
		}
		$q = (string) $q;

		// Resolve products via index.
		$ids = AAA_OC_ProductSearch_Helpers::search_index( $q );
		if ( empty( $ids ) ) {
			// Let Woo/theme handle "no results" normally.
			return;
		}

		// Fetch display-ready rows from index.
		global $wpdb;
		$table       = $wpdb->prefix . AAA_OC_ProductSearch_Table_Installer::T_INDEX;
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql          = "SELECT * FROM {$table} WHERE product_id IN ({$placeholders}) ORDER BY title ASC";
		$rows         = $wpdb->get_results( $wpdb->prepare( $sql, $ids ), ARRAY_A );

		if ( empty( $rows ) ) {
			return;
		}

		status_header( 200 );

		// Theme chrome.
		get_header();

		echo '<main class="aaa-productsearch-results-main" style="max-width:1200px;margin:24px auto;padding:0 16px;">';

		$title = sprintf( 'Search results for "%s"', esc_html( $q ) );

		echo '<div class="eee-home-carousel" data-ehp>';
		echo '<div class="carousel-header">';
		echo '<h2>' . $title . '</h2>';
		echo '</div>';

		echo '<div class="carousel-track" data-ehp-track>';

		foreach ( $rows as $row ) {
			$pid        = (int) $row['product_id'];
			$brand_name = isset( $row['brand_name'] ) ? $row['brand_name'] : '';
			$image_url  = isset( $row['image_url'] ) ? $row['image_url'] : '';
			$title_raw  = isset( $row['title'] ) ? $row['title'] : '';
			$reg        = isset( $row['price_regular'] ) ? $row['price_regular'] : null;
			$sale       = isset( $row['price_sale'] ) ? $row['price_sale'] : null;
			$active     = isset( $row['price_active'] ) ? $row['price_active'] : null;

			if ( ( $active === null || $active === '' ) && $reg !== null && $reg !== '' ) {
				$active = $reg;
			}

			echo '<div class="carousel-item">';

			if ( $brand_name ) {
				echo '<div class="eee-brand">' . esc_html( $brand_name ) . '</div>';
			}

			echo '<a href="' . esc_url( get_permalink( $pid ) ) . '">';
			echo '<div class="eee-image-wrap">';
			if ( $image_url ) {
				echo '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $title_raw ) . '" loading="lazy" />';
			}
			echo '</div>';
			echo '<h3>' . esc_html( $title_raw ) . '</h3>';
			echo '</a>';

			echo '<div class="eee-price">';
			if ( function_exists( 'wc_price' ) ) {
				if ( $sale !== null && $sale !== '' && (float) $sale > 0 && $reg !== null && $reg !== '' && (float) $sale < (float) $reg ) {
					echo '<span class="regular">' . wc_price( (float) $reg ) . '</span>';
					echo '<span class="sale">' . wc_price( (float) $sale ) . '</span>';
				} elseif ( $active !== null && $active !== '' ) {
					echo '<span class="regular only">' . wc_price( (float) $active ) . '</span>';
				}
			} else {
				if ( $sale !== null && $sale !== '' && $reg !== null && $reg !== '' && (float) $sale < (float) $reg ) {
					echo '<span class="regular">' . esc_html( $reg ) . '</span>';
					echo '<span class="sale">' . esc_html( $sale ) . '</span>';
				} elseif ( $active !== null && $active !== '' ) {
					echo '<span class="regular only">' . esc_html( $active ) . '</span>';
				}
			}
			echo '</div>'; // .eee-price

			echo '</div>'; // .carousel-item
		}

		echo '</div>'; // .carousel-track
		echo '</div>'; // .eee-home-carousel

		echo '</main>';

		get_footer();

		exit;
	}
}
