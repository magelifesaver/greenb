<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link  https://https://powerfulwp.com
 * @since 1.0.0
 *
 * @package    Scheduling_Deliveries_For_Delivery_Drivers
 * @subpackage Scheduling_Deliveries_For_Delivery_Drivers/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Scheduling_Deliveries_For_Delivery_Drivers
 * @subpackage Scheduling_Deliveries_For_Delivery_Drivers/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class Scheduling_Deliveries_For_Delivery_Drivers {


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Scheduling_Deliveries_For_Delivery_Drivers_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( defined( 'SCHEDULING_DELIVERIES_FOR_DELIVERY_DRIVERS_VERSION' ) ) {
			$this->version = SCHEDULING_DELIVERIES_FOR_DELIVERY_DRIVERS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'scheduling-deliveries-for-delivery-drivers';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Scheduling_Deliveries_For_Delivery_Drivers_Loader. Orchestrates the hooks of the plugin.
	 * - Scheduling_Deliveries_For_Delivery_Drivers_i18n. Defines internationalization functionality.
	 * - Scheduling_Deliveries_For_Delivery_Drivers_Admin. Defines all hooks for the admin area.
	 * - Scheduling_Deliveries_For_Delivery_Drivers_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {

		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/functions.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-scheduling-deliveries-for-delivery-drivers-loader.php';

		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-scheduling-deliveries-for-delivery-drivers-metabox.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		// require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-scheduling-deliveries-for-delivery-drivers-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-scheduling-deliveries-for-delivery-drivers-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-scheduling-deliveries-for-delivery-drivers-public.php';

		$this->loader = new Scheduling_Deliveries_For_Delivery_Drivers_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Scheduling_Deliveries_For_Delivery_Drivers_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function set_locale() {

		// $plugin_i18n = new Scheduling_Deliveries_For_Delivery_Drivers_i18n();

		// $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Scheduling_Deliveries_For_Delivery_Drivers_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$meta_boxes = new Scheduling_Deliveries_For_Delivery_Drivers_MetaBoxes();
		$this->loader->add_filter( 'lddfw_delivery_driver_metabox', $meta_boxes, 'add_metaboxes', 10, 1 );
		$this->loader->add_action( 'woocommerce_process_shop_order_meta', $meta_boxes, 'save_metaboxes', 100, 2 );

		/**
		 * Bulk update
		*/
		if ( sdfdd_is_hpos_enabled() ) {
			$this->loader->add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', $plugin_admin, 'order_bluk_actions_handle', 10, 3 );
			$this->loader->add_filter( 'bulk_actions-woocommerce_page_wc-orders', $plugin_admin, 'order_bluk_actions_edit', 20, 1 );

		} else {
			$this->loader->add_filter( 'handle_bulk_actions-edit-shop_order', $plugin_admin, 'order_bluk_actions_handle', 10, 3 );
			$this->loader->add_filter( 'bulk_actions-edit-shop_order', $plugin_admin, 'order_bluk_actions_edit', 20, 1 );
		}

		/**
		 * Ajax calls
		 */
		$this->loader->add_action( 'wp_ajax_sdfdd_ajax', $plugin_admin, 'sdfdd_ajax' );
		$this->loader->add_action( 'wp_ajax_nopriv_sdfdd_ajax', $plugin_admin, 'sdfdd_ajax' );

		/**
		 * Tabs
		 */
		$this->loader->add_action( 'lddfw_settings_tabs', $plugin_admin, 'setting_tabs' );

		/**
		* Settings
	   */
		$this->loader->add_action( 'admin_init', $plugin_admin, 'settings_init' );

		/**
		 * Order columns
		 */
		if ( sdfdd_is_hpos_enabled() ) {
			$this->loader->add_action( 'woocommerce_shop_order_list_table_custom_column', $plugin_admin, 'sdfdd_orders_list_columns', 20, 2 );
			$this->loader->add_filter( 'woocommerce_shop_order_list_table_columns', $plugin_admin, 'sdfdd_orders_list_columns_order', 20 );
		} else {
			$this->loader->add_action( 'manage_shop_order_posts_custom_column', $plugin_admin, 'sdfdd_orders_list_columns', 20, 2 );
			$this->loader->add_filter( 'manage_edit-shop_order_columns', $plugin_admin, 'sdfdd_orders_list_columns_order', 20 );
		}

		// Hook into order creation from both frontend (checkout) and admin.
		$this->loader->add_action( 'woocommerce_checkout_order_created', $plugin_admin, 'sdfdd_copy_third_party_meta_on_checkout_order_created', 99, 1 );
		$this->loader->add_action( 'woocommerce_new_order', $plugin_admin, 'sdfdd_copy_third_party_meta_on_new_order', 10, 2 );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {

		$plugin_public = new Scheduling_Deliveries_For_Delivery_Drivers_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_filter( 'lddfw_driver_order_page_info', $plugin_public, 'driver_order_page_info', 10, 1 );
		$this->loader->add_filter( 'lddfw_driver_orders_page_info', $plugin_public, 'driver_orders_page_info', 10, 1 );
		$this->loader->add_filter( 'lddfw_driver_orders_date_time_filter', $plugin_public, 'orders_date_time_filter', 10 );

		$this->loader->add_filter( 'lddfw_claim_orders_filter', $plugin_public, 'claim_orders_filter', 10, 1 );
		$this->loader->add_filter( 'lddfw_orders_query_filter', $plugin_public, 'orders_query_filter', 10, 3 );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since  1.0.0
	 * @return string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Scheduling_Deliveries_For_Delivery_Drivers_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0.0
	 * @return string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
