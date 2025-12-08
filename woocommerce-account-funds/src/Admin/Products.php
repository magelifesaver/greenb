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

declare( strict_types = 1 );

namespace Kestrel\Account_Funds\Admin;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;

/**
 * Admin handler for WooCommerce products.
 *
 * @since 2.0.0
 */
final class Products {
	use Is_Handler;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param Plugin $plugin
	 */
	protected function __construct( Plugin $plugin ) {

		self::$plugin = $plugin;

		// add the store credit deposit product to the product types
		self::add_filter( 'product_type_selector', [ $this, 'add_store_credit_product_type' ] );
		// tweak the store credit deposit product data panels
		self::add_action( 'woocommerce_product_data_panels', [ $this, 'extend_simple_product_fields_to_store_credit' ] );
		self::add_action( 'woocommerce_process_product_meta_deposit', [ $this, 'save_store_credit_product' ] );
		self::add_action( 'admin_enqueue_scripts', [ $this, 'handle_store_credit_product_tax_settings' ] );
	}

	/**
	 * Add deposit product type.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, string>|mixed $types
	 * @return array<string, string>|mixed $types
	 */
	protected function add_store_credit_product_type( $types ) {

		if ( ! is_array( $types ) ) {
			return $types;
		}

		$types['deposit'] = __( 'Store credit', 'woocommerce-account-funds' );

		return $types;
	}

	/**
	 * Saves metadata for the store credit deposit product.
	 *
	 * @since 2.0.0
	 *
	 * @param int|mixed $product_id
	 * @return void
	 */
	public function save_store_credit_product( $product_id ) : void {

		if ( ! is_numeric( $product_id ) ) {
			return;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return;
		}

		$product->set_virtual( true );
		$product->save();
	}

	/**
	 * Handles product fields the same way as the simple product for store credit deposit product types.
	 *
	 * @TODO If we add a variable store credit deposit type we need to adjust this method probably.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	protected function extend_simple_product_fields_to_store_credit() : void {

		?>
		<script type="text/javascript">
			jQuery('.show_if_simple').addClass( 'show_if_deposit' );
			jQuery('#_virtual, #_downloadable').closest('label').addClass( 'hide_if_deposit' );
		</script>
		<?php
	}

	/**
	 * Enqueues admin scripts.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function handle_store_credit_product_tax_settings() : void {
		global $pagenow;

		// show inline information about the product type and a notice on the tax status field when a store credit deposit product type is selected
		if ( ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) && 'product' === get_post_type() ) { // phpcs:ignore WordPress.Security.NonceVerification

			wc_enqueue_js( "
				( function( $ ) {
					$( document ).ready( function() {
						$( '#product-type' ).on( 'change', function() {

							$( '#wc-account-funds-store-credit-product-description' ).remove();
							$( '#wc-account-funds-store-credit-product-tax-status-note' ).remove();
							$( '#wc-account-funds-store-credit-product-sale-price-note' ).remove();

							if ( $( '#product-type' ).val() === 'deposit' ) {
								$( '#general_product_data' ).prepend( '<div id=\"wc-account-funds-store-credit-product-description\" class=\"toolbar toolbar-top\"><div class=\"inline woocommerce-message\"><p class=\"help\" style=\"padding-left: 12px;\"><em>" . esc_html__( 'Store credit products can be purchased by customers to increase their store credit balance by an amount equal to the price paid.', 'woocommerce-account-funds' ) . "</em></p></div></div>' );
								$(  '<div id=\"wc-account-funds-store-credit-product-sale-price-note\"><p style=\"padding: 2px 0 0; clear: both; margin-bottom: 0;\">" . esc_html__( 'If a sale price is set, customers will pay the sale price and receive the full price amount in store credit instead.', 'woocommerce-account-funds' ) . "</p></div>' ).appendTo( $( '#_sale_price' ).closest( '.form-field' ) );
								$( '<div id=\"wc-account-funds-store-credit-product-tax-status-note\"><p style=\"padding: 2px 0 0; clear: both; margin-bottom: 0;\">" . esc_html__( 'Important note: store credit products should not be taxed if you are charging tax on the purchase of products.', 'woocommerce-account-funds' ) . "</p></div>' ).appendTo( $( '#_tax_status' ).closest( '.form-field' ) );
							}

						} ).trigger( 'change' );
					} );
				} )( jQuery );
			" );

		}

		// by default, deposit products are non-taxable
		if ( $pagenow === 'post-new.php' && isset( $_GET['post_type'] ) && 'product' === $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification

			wc_enqueue_js( "
				( function( $ ) {
					$( document ).ready( function() {
						$( '#product-type' ).on( 'change', function() {
							if ( $( '#product-type' ).val() === 'deposit' ) {
								$( '#_tax_status' ).val( 'none' ).trigger( 'change' );
							}
						} );
					} );
				} )( jQuery );
			" );
		}
	}

}

class_alias(
	__NAMESPACE__ . '\Products',
	'\WC_Account_Funds_Admin_Product'
);
