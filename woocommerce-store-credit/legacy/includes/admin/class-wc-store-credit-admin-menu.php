<?php
/**
 * Admin Menu.
 *
 * @package WC_Store_Credit/Admin
 * @since   3.5.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * WC_Store_Credit_Admin_Menu class.
 */
class WC_Store_Credit_Admin_Menu {

	/**
	 * Init.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	public static function init() : void {

		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 15 );
	}

	/**
	 * Registers the WordPress menu items.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	public static function register_menu() : void {

		$send_credit_page = add_submenu_page(
			'woocommerce',
			__( 'Send store credit', 'woocommerce-store-credit' ),
			__( 'Send store credit', 'woocommerce-store-credit' ),
			'manage_woocommerce',
			wc_store_credit_get_send_credit_menu_slug(),
			[ 'WC_Store_Credit_Admin_Send_Credit_Page', 'output' ]
		);

		add_action( 'load-' . $send_credit_page, [ 'WC_Store_Credit_Admin_Send_Credit_Page', 'init' ] );

		if ( function_exists( 'wc_admin_connect_page' ) ) {
			wc_admin_connect_page(
				[
					'id'        => 'store-credit-send-credit',
					'parent'    => 'store-credit',
					'screen_id' => wc_store_credit_get_send_credit_screen_id(),
					'title'     => __( 'Send store credit', 'woocommerce-store-credit' ),
				]
			);
		}
	}

	/**
	 * Registers the navigation items in the WC Navigation Menu.
	 *
	 * @since 3.5.0
	 * @deprecated 4.5.6
	 *
	 * @return void
	 */
	public static function register_nav_items() : void {

		wc_deprecated_function( __METHOD__, '4.5.6' );
	}

}

WC_Store_Credit_Admin_Menu::init();
