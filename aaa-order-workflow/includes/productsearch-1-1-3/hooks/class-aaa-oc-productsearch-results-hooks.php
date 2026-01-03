<?php
/**
 * Plugin Name: AAA ProductSearch Results
 * Description: Ultra-light search results renderer using aaa_oc_productsearch_index and EEE carousel layout.
 * Version:     1.0.0
 * Author:      Webmaster Workflow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode: [aaa_productsearch_results]
 *
 * Uses:
 *  - Current query var ?s=... (default)
 *  - Or [aaa_productsearch_results q="vape carts"]
 *
 * Renders:
 *  - Brand (from index)
 *  - Image (from index)
 *  - Title
 *  - Price (regular/sale/active from index)
 *  - Link to product (via get_permalink)
 *
 * Depends on:
 *  - AAA_OC_ProductSearch_Table_Installer
 *  - AAA_OC_ProductSearch_Helpers
 */
class AAA_ProductSearch_Results {

	public static function init() {
		add_shortcode( 'aaa_productsearch_results', array( __CLASS__, 'shortcode' ) );
	}

	public static function shortcode( $atts ) {
		if ( ! class_exists( 'AAA_OC_ProductSearch_Table_Installer' ) || ! class_exists( 'AAA_OC_ProductSearch_Helpers' ) ) {
			return '<p>ProductSearch module not available.</p>';
		}

		$atts = shortcode_atts(
			array(
				'q'     => '',
				'title' => '',
			),
			$atts
		);

		$q = trim( (string) $atts['q'] );

		if ( $q === '' && isset( $_GET['s'] ) ) {
			$q = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}

		if ( $q === '' ) {
			return '<p>No search term provided.</p>';
		}

		// Use ProductSearch helper to resolve IDs (fast, index-only search).
		$ids = AAA_OC_ProductSearch_Helpers::search_index( $q );
		if ( empty( $ids ) ) {
			return '<p>No products found matching "' . esc_html( $q ) . '".</p>';
		}

		global $wpdb;
		$table = $wpdb->prefix . AAA_OC_ProductSearch_Table_Installer::T_INDEX;

		// Fetch full rows for these products from the index (display-ready).
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql          = "SELECT * FROM {$table} WHERE product_id IN ({$placeholders}) ORDER BY title ASC";
		$rows         = $wpdb->get_results( $wpdb->prepare( $sql, $ids ), ARRAY_A );

		if ( empty( $rows ) ) {
			return '<p>No products found matching "' . esc_html( $q ) . '".</p>';
		}

		$title = $atts['title'] !== '' ? $atts['title'] : sprintf( 'Search results for "%s"', $q );

		ob_start();
		?>
		<div class="eee-home-carousel" data-ehp>
			<div class="carousel-header">
				<h2><?php echo esc_html( $title ); ?></h2>
			</div>

			<div class="carousel-track" data-ehp-track>
				<?php foreach ( $rows as $row ) :
					$pid        = (int) $row['product_id'];
					$brand_name = isset( $row['brand_name'] ) ? $row['brand_name'] : '';
					$image_url  = isset( $row['image_url'] ) ? $row['image_url'] : '';
					$title_raw  = isset( $row['title'] ) ? $row['title'] : '';
					$reg        = isset( $row['price_regular'] ) ? $row['price_regular'] : null;
					$sale       = isset( $row['price_sale'] ) ? $row['price_sale'] : null;
					$active     = isset( $row['price_active'] ) ? $row['price_active'] : null;

					// Pick an active price if sale not set.
					if ( ( $active === null || $active === '' ) && $reg !== null && $reg !== '' ) {
						$active = $reg;
					}
					?>
					<div class="carousel-item">

						<?php if ( $brand_name ) : ?>
							<div class="eee-brand"><?php echo esc_html( $brand_name ); ?></div>
						<?php endif; ?>

						<a href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
							<div class="eee-image-wrap">
								<?php if ( $image_url ) : ?>
									<img src="<?php echo esc_url( $image_url ); ?>"
									     alt="<?php echo esc_attr( $title_raw ); ?>"
									     loading="lazy" />
								<?php endif; ?>
							</div>
							<h3><?php echo esc_html( $title_raw ); ?></h3>
						</a>

						<div class="eee-price">
							<?php
							// Use Woo formatter for currency symbol, but data comes from index.
							if ( function_exists( 'wc_price' ) ) {
								if ( $sale !== null && $sale !== '' && (float) $sale > 0 && $reg !== null && $reg !== '' && (float) $sale < (float) $reg ) {
									echo '<span class="regular">' . wc_price( (float) $reg ) . '</span>';
									echo '<span class="sale">' . wc_price( (float) $sale ) . '</span>';
								} elseif ( $active !== null && $active !== '' ) {
									echo '<span class="regular only">' . wc_price( (float) $active ) . '</span>';
								}
							} else {
								// Fallback – raw numbers.
								if ( $sale !== null && $sale !== '' && $reg !== null && $reg !== '' && (float) $sale < (float) $reg ) {
									echo '<span class="regular">' . esc_html( $reg ) . '</span>';
									echo '<span class="sale">' . esc_html( $sale ) . '</span>';
								} elseif ( $active !== null && $active !== '' ) {
									echo '<span class="regular only">' . esc_html( $active ) . '</span>';
								}
							}
							?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

AAA_ProductSearch_Results::init();
