<?php
/**
 * Notice - Enable coupons
 *
 * @package WC_Store_Credit/Admin/Notices
 * @since   3.1.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="notice notice-warning">
	<p>
		<strong><?php esc_html_e( 'Store Credit for WooCommerce', 'woocommerce-store-credit' ); ?></strong> &#8211; <?php esc_html_e( 'WooCommerce coupons are not enabled.', 'woocommerce-store-credit' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=general#woocommerce_enable_coupons' ) ); ?>">
			<?php esc_html_e( 'Enable coupons.', 'woocommerce-store-credit' ); ?>
		</a>
	</p>
</div>
