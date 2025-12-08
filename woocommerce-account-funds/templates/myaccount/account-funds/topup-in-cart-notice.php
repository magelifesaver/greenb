<?php
/**
 * Account Funds for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2015-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * My Account > Top-up in cart notice.
 *
 * @since 1.0.0
 *
 * @version 2.2.0
 *
 * @var string $topup_title_in_cart
 */
?>
<p class="woocommerce-info">
	<a href="<?php echo esc_url( wc_get_page_permalink( 'cart' ) ); ?>" class="button wc-forward"><?php esc_html_e( 'View Cart', 'woocommerce-account-funds' ); ?></a>
	<?php
	/* translators: Placeholder: %s - Account funds top-up product title, e.g. "Account funds" */
	echo esc_html( sprintf( __( 'You have "%s" in your cart.', 'woocommerce-account-funds' ), $topup_title_in_cart ) );
	?>
</p>
