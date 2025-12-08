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
 * "My account > Store credit" dashboard section template.
 *
 * @package WC_Store_Credit/Templates/My Account
 * @version 4.0.0
 */

$coupons = wc_store_credit_get_customer_coupons( get_current_user_id() );

if ( ! empty( $coupons ) && 'yes' === get_option( 'wc_store_credit_show_my_account', 'yes' ) ) :

	?>
	<h3><?php esc_html_e( 'Store credit', 'woocommerce-store-credit' ); ?></h3>
	<p>
		<?php
		printf(
			/* translators: Placeholder: %s - store-credit endpoint */
			wp_kses_post( __( 'You have <a href="%s">store credit coupons</a> available to spend on your next purchase.', 'woocommerce-store-credit' ) ),
			esc_url( wc_get_endpoint_url( 'store-credit' ) )
		);
		?>
	</p>
	<?php

endif;
