<?php
/**
 * Plugin Name: Bulky - Bulk Edit Products for WooCommerce Premium
 * Plugin URI: https://villatheme.com/extensions/bulky-woocommerce-bulk-edit-products/
 * Description: A helpful tool that allows you to bulk edit available attributes of products such as ID, Title, Content,...
 * Version: 1.3.10
 * Author: VillaTheme
 * Author URI: https://villatheme.com
 * Text Domain: bulky-woocommerce-bulk-edit-products
 * Domain Path: /languages
 * Copyright 2021-2025 VillaTheme.com. All rights reserved.
 * Requires Plugins: woocommerce
 * Requires at least: 5.0
 * Tested up to: 6.8
 * WC requires at least: 7.0
 * WC tested up to: 10.3
 * Requires PHP: 7.0
 **/

use BULKY\Includes\Admin\Admin;
use BULKY\Includes\Ajax;
use BULKY\Includes\Enqueue;
use BULKY\Support\Define_Support;
use BULKY\Includes\Abstracts\History_Abstract;

defined( 'ABSPATH' ) || exit;

if ( is_file( plugin_dir_path( __FILE__ ) . 'autoload.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'autoload.php';
}

class Bulky_Products_Bulk_Editor {
	public $plugin_name = 'Bulky - Bulk Edit Products for WooCommerce';

	public $version = '1.3.10';

	public $conditional = '';

	protected static $instance = null;

	public function __construct() {
		$this->define();

		register_activation_hook( __FILE__, [ $this, 'active' ] );

		add_action( 'plugins_loaded', [ $this, 'init' ] );
		add_action( 'before_woocommerce_init', [ $this, 'custom_order_tables_declare_compatibility' ] );
	}

	public static function instance() {
		return self::$instance == null ? self::$instance = new self : self::$instance;
	}

	public function define() {
		define( 'BULKY_CONST', [
			'version'      => $this->version,
			'slug'         => 'bulky-woocommerce-bulk-edit-products',
			'assets_slug'  => 'bulky-woocommerce-bulk-edit-products-',
			'file'         => __FILE__,
			'basename'     => plugin_basename( __FILE__ ),
			'plugin_dir'   => plugin_dir_path( __FILE__ ),
			'includes_dir' => plugin_dir_path( __FILE__ ) . 'includes' . DIRECTORY_SEPARATOR,
			'admin_dir'    => plugin_dir_path( __FILE__ ) . 'admin' . DIRECTORY_SEPARATOR,
			'dist_dir'     => plugin_dir_path( __FILE__ ) . 'assets' . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR,
			'dist_url'     => plugins_url( 'assets/dist/', __FILE__ ),
			'libs_url'     => plugins_url( 'assets/libs/', __FILE__ ),
			'img_url'      => plugins_url( 'assets/img/', __FILE__ ),
			'capability'   => 'edit_products'
		] );
	}

	public function init() {
		if ( ! class_exists( 'VillaTheme_Require_Environment' ) ) {
			include_once BULKY_CONST['plugin_dir'] . 'support/support.php';
		}

		$environment = new VillaTheme_Require_Environment( [
				'plugin_name'     => $this->plugin_name,
				'php_version'     => '7.0',
				'wp_version'      => '5.0',
				'require_plugins' => [
					[
						'slug' => 'woocommerce',
						'name' => 'WooCommerce',
						'defined_version' => 'WC_VERSION',
						'version' => '7.0',
					],
				]
			]
		);

		if ( $environment->has_error() ) {
			return;
		}

		$this->load_class();

		add_action('init', [$this,'init_schedule']);
		add_filter( 'plugin_action_links_' . BULKY_CONST['basename'], [ $this, 'setting_link' ] );
		add_action( 'init', [ $this, 'load_text_domain' ] );
	}

	public function init_schedule(){
		add_action( 'vi_wbe_remove_history_revision', array( $this, 'remove_revision' ) );
		if ( ! wp_next_scheduled( 'vi_wbe_remove_history_revision' ) ) {
			wp_schedule_event( time(), 'daily', 'vi_wbe_remove_history_revision' );
		}
	}
	public function remove_revision(){
		BULKY\Includes\Products\Product_History::instance()->remove_revision();
		BULKY\Includes\Orders\Order_History::instance()->remove_revision();
		BULKY\Includes\Coupons\Coupon_History::instance()->remove_revision();
		BULKY\Includes\Reviews\Review_History::instance()->remove_revision();
	}
	public function setting_link( $links ) {
		$editor_link   = [ sprintf( "<a href='%1s' >%2s</a>", esc_url( admin_url( 'admin.php?page=vi_wbe_edit_products' ) ), esc_html__( 'Editor', 'bulky-woocommerce-bulk-edit-products' ) ) ];
		$settings_link = [ sprintf( "<a href='%1s' >%2s</a>", esc_url( admin_url( 'admin.php?page=vi_wbe_settings' ) ), esc_html__( 'Settings', 'bulky-woocommerce-bulk-edit-products' ) ) ];

		return array_merge( $editor_link, $settings_link, $links );
	}

	public function load_class() {
		if ( is_admin() ) {
			Enqueue::instance();
			Define_Support::instance();
			Admin::instance();
			Ajax::instance();
		}
	}

	public function load_text_domain() {
		$locale = determine_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'bulky-woocommerce-bulk-edit-products' );

		unload_textdomain( 'bulky-woocommerce-bulk-edit-products' );
		load_textdomain( 'bulky-woocommerce-bulk-edit-products', WP_LANG_DIR . '/bulky-woocommerce-bulk-edit-products/bulky-woocommerce-bulk-edit-products-' . $locale . '.mo' );
		load_plugin_textdomain( 'bulky-woocommerce-bulk-edit-products', false, plugin_basename( dirname( BULKY_CONST['file'] ) ) . '/languages' );
	}

	public function active( $network_wide ) {
		global $wpdb;
		$history = History_Abstract::instance();
		if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
			$current_blog = $wpdb->blogid;
			$blogs        = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			foreach ( $blogs as $blog ) {
				switch_to_blog( $blog );
				$history->create_database_table();
			}
			switch_to_blog( $current_blog );
		} else {
			$history->create_database_table();
		}
	}

	public function custom_order_tables_declare_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

}
Bulky_Products_Bulk_Editor::instance();

