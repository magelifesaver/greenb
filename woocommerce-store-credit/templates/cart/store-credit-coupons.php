<?php
/**
 * Kestrel Store Credit for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2012-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Store credit coupons template.
 *
 * @since 4.2.0
 * @version 4.5.4
 *
 * @var WC_Coupon[] $coupons available store credit coupons
 */
?>
<div class="wc-store-credit-cart-coupons-container">
	<?php

	if ( ! empty( $coupons ) ) :

		$store_credit_notice = wc_store_credit_get_cart_title();

		if ( ! wc_has_notice( $store_credit_notice, 'notice' ) ) :
			wc_print_notice( $store_credit_notice, 'notice' );
		endif;

		?>
		<div class="wc-store-credit-cart-coupons" style="display:none">
			<?php

			/**
			 * Hook: wc_store_credit_cart_coupons_before.
			 *
			 * @since 4.2.0
			 */
			do_action( 'wc_store_credit_cart_coupons_before' );

			array_map( 'wc_store_credit_cart_coupon', $coupons );

			/**
			 * Hook: wc_store_credit_cart_coupons_after.
			 *
			 * @since 4.2.0
			 */
			do_action( 'wc_store_credit_cart_coupons_after' );

			?>
		</div>
		<?php

	endif;

	?>
</div>
<?php
