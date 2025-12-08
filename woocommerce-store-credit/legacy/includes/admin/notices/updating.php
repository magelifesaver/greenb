<?php
/**
 * Notice - Updating
 *
 * @package WC_Store_Credit/Admin/Notices
 * @since   2.4.0
 */

defined( 'ABSPATH' ) || exit;

$force_update_url = wp_nonce_url(
	add_query_arg( 'force_update_wc_store_credit', 'true', wc_store_credit_get_settings_url() ),
	'wc_store_credit_force_db_update',
	'wc_store_credit_force_db_update_nonce'
);

?>
<div id="message" class="updated woocommerce-message wc-connect">
	<p>
		<strong><?php esc_html_e( 'Store Credit for WooCommerce', 'woocommerce-store-credit' ); ?></strong> &#8211; <?php esc_html_e( 'Your database is being updated in the background.', 'woocommerce-store-credit' ); ?>
		<a href="<?php echo esc_url( $force_update_url ); ?>">
			<?php esc_html_e( 'Taking a while? Click here to run it now.', 'woocommerce-store-credit' ); ?>
		</a>
	</p>
</div>
