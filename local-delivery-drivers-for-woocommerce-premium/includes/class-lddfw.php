<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
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
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */

if ( ! class_exists( 'LDDFW' ) ) {
	/**
	 * Plugin Main Class.
	 *
	 * @package    LDDFW
	 * @subpackage LDDFW/includes
	 * @author     powerfulwp <apowerfulwp@gmail.com>
	 */
	class LDDFW {



		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since  1.0.0
		 * @access protected
		 * @var    LDDFW_Loader    $loader    Maintains and registers all hooks for the plugin.
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
			if ( defined( 'LDDFW_VERSION' ) ) {
				$this->version = LDDFW_VERSION;
			} else {
				$this->version = '1.0.0';
			}
			$this->plugin_name = 'lddfw';

			$this->load_dependencies();
			$this->set_locale();
			$this->define_admin_hooks();
			$this->define_public_hooks();
			lddfw_update_db_check();
		}

		/**
		 * Load the required dependencies for this plugin.
		 *
		 * Include the following files that make up the plugin:
		 *
		 * - LDDFW_Loader. Orchestrates the hooks of the plugin.
		 * - LDDFW_I18n. Defines internationalization functionality.
		 * - LDDFW_Admin. Defines all hooks for the admin area.
		 * - LDDFW_Public. Defines all hooks for the public side of the site.
		 *
		 * Create an instance of the loader which will be used to register the hooks
		 * with WordPress.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function load_dependencies() {
			/**
			 * The function file.
			 * core plugin.
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/functions.php';

			/**
			 * The class responsible for orchestrating the actions and filters of the
			 * core plugin.
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-loader.php';

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-lddfw-admin.php';

			/**
			 * The class responsible for defining all actions that occur in the public-facing
			 * side of the site.
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-lddfw-public.php';

			/**
			 * The file responsible for defining all the metaboxes in admin panel
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/lddfw-metaboxes.php';
			/**
			 * The file responsible for driver page
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-driver.php';

			/**
			 * The file responsible for the drivers login
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-login.php';

			/**
			 * The file responsible for the passwords
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-password.php';
			/**
			 * The file responsible for the website store
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-store.php';
			/**
			 * The file responsible for the screens
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-screens.php';
			/**
			 * The file responsible for the order
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-order.php';
			/**
			 * The file responsible for the orders
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-orders.php';
			/**
			 * The file responsible for reports in admin panel
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-reports.php';

			if ( lddfw_fs()->is__premium_only() ) {
				if ( lddfw_fs()->is_plan( 'premium', true ) ) {

					/**
					 * The file responsible for media
					 */
					include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-media.php';

					/**
					 * The file responsible for tracking in admin panel
					 */
					include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-tracking.php';

					/**
					 * The file responsible for the drivers applications
					 */
					include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-application.php';

					/**
					 * The file responsible for the delivery route
					 */
					include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-route.php';

					/**
					 * The file responsible for the sms
					 */
					include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-sms.php';

					/**
					 * The file responsible for the whatsapp
					 */
					include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-whatsapp.php';

				}
			}

			$this->loader = new LDDFW_Loader();
		}

		/**
		 * Define the locale for this plugin for internationalization.
		 *
		 * Uses the LDDFW_I18n class in order to set the domain and to register the hook
		 * with WordPress.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function set_locale() {
			// $plugin_i18n = new LDDFW_I18n();
			// $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain', 20 );
		}

		/**
		 * Register all of the hooks related to the admin area functionality
		 * of the plugin.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function define_admin_hooks() {
			$plugin_admin = new LDDFW_Admin( $this->get_plugin_name(), $this->get_version() );

			/**
			 * Scripts.
			 */
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

			/**
			 * Users custom columns
			 */
			$this->loader->add_filter( 'manage_users_columns', $plugin_admin, 'lddfw_users_list_columns' );
			$this->loader->add_filter( 'manage_users_custom_column', $plugin_admin, 'lddfw_users_list_columns_raw', 10, 3 );

			/**
			 * Order columns
			 */
			if ( lddfw_is_hpos_enabled() ) {
				$this->loader->add_action( 'woocommerce_shop_order_list_table_custom_column', $plugin_admin, 'lddfw_orders_list_columns', 20, 2 );
				$this->loader->add_filter( 'woocommerce_shop_order_list_table_columns', $plugin_admin, 'lddfw_orders_list_columns_order', 20 );
			} else {
				$this->loader->add_action( 'manage_shop_order_posts_custom_column', $plugin_admin, 'lddfw_orders_list_columns', 20, 2 );
				$this->loader->add_filter( 'manage_edit-shop_order_columns', $plugin_admin, 'lddfw_orders_list_columns_order', 20 );
			}

			/**
			 * Order custom fields
			 */
			$this->loader->add_filter( 'is_protected_meta', $plugin_admin, 'lddfw_exclude_custom_fields', 10, 2 );

			/**
			 * Sortable columns
			 */
			if ( lddfw_is_hpos_enabled() ) {
				$this->loader->add_filter( 'manage_woocommerce_page_wc-orders_sortable_columns', $plugin_admin, 'lddfw_orders_list_sortable_columns' );
			} else {
				$this->loader->add_filter( 'manage_edit-shop_order_sortable_columns', $plugin_admin, 'lddfw_orders_list_sortable_columns' );
			}
			/**
			 * Ajax calls
			 */
			$this->loader->add_action( 'wp_ajax_lddfw_ajax', $plugin_admin, 'lddfw_ajax' );
			$this->loader->add_action( 'wp_ajax_nopriv_lddfw_ajax', $plugin_admin, 'lddfw_ajax' );

			/**
			 * Add menu
			*/
			$this->loader->add_action( 'admin_menu', $plugin_admin, 'lddfw_admin_menu', 99 );

			/**
			 * Settings
			*/
			$this->loader->add_action( 'admin_init', $plugin_admin, 'lddfw_settings_init' );

			$lddfw_meta_boxes = new LDDFW_MetaBoxes();
			$this->loader->add_action( 'add_meta_boxes', $lddfw_meta_boxes, 'add_metaboxes' );
			$this->loader->add_action( 'woocommerce_process_shop_order_meta', $lddfw_meta_boxes, 'save_metaboxes', 100, 2 );

			/**
			 * Status
			*/
			$this->loader->add_action( 'init', $plugin_admin, 'lddfw_order_statuses_init' );
			$this->loader->add_filter( 'wc_order_statuses', $plugin_admin, 'lddfw_order_statuses' );
			$this->loader->add_action( 'woocommerce_order_status_changed', $plugin_admin, 'lddfw_status_changed', 9, 4 );

			// Update sync table when WooCommerce order has been refunded.
			add_action( 'woocommerce_order_refunded', 'lddfw_woocommerce_order_refunded', 10, 2 );

			// Delete order from sync table when WooCommerce order has been deleted.
			add_action( 'woocommerce_before_delete_order', 'lddfw_admin_on_delete_order' );

			// Insert into sync table when WooCommerce order has been created.
			add_action( 'woocommerce_checkout_update_order_meta', 'lddfw_insert_sync_order_by_id', 20 );

			// Insert into sync table when WooCommerce order has been created by dokan.
			add_action( 'dokan_checkout_update_order_meta', 'lddfw_insert_sync_order_by_id' );

			if ( is_admin() ) {
				// Insert into sync table when WooCommerce order has been updated.
				add_action( 'woocommerce_process_shop_order_meta', 'lddfw_insert_sync_order_by_id' );
			}

			/**
			 * Users
			*/
			$this->loader->add_action( 'show_user_profile', $plugin_admin, 'lddfw_user_fields' );
			$this->loader->add_action( 'edit_user_profile', $plugin_admin, 'lddfw_user_fields' );
			$this->loader->add_action( 'personal_options_update', $plugin_admin, 'lddfw_user_fields_save' );
			$this->loader->add_action( 'edit_user_profile_update', $plugin_admin, 'lddfw_user_fields_save' );

			if ( lddfw_fs()->is__premium_only() ) {
				if ( lddfw_fs()->is_plan( 'premium', true ) ) {

					/**
					 * Shipping
					*/
					$this->loader->add_action( 'woocommerce_init', $plugin_admin, 'lddfw_shipping_settings__premium_only' );

					/**
					 * Orders filters
					*/
					if ( lddfw_is_hpos_enabled() ) {
						$this->loader->add_action( 'woocommerce_order_list_table_restrict_manage_orders', $plugin_admin, 'lddfw_orders_filter__premium_only' );
						$this->loader->add_action( 'woocommerce_order_list_table_prepare_items_query_args', $plugin_admin, 'lddfw_orders_filter_process_hpos__premium_only' );
					} else {
						$this->loader->add_action( 'restrict_manage_posts', $plugin_admin, 'lddfw_orders_filter__premium_only' );
						$this->loader->add_action( 'parse_query', $plugin_admin, 'lddfw_orders_filter_process__premium_only' );
					}

					/**
					 * Bulk update
					*/

					if ( lddfw_is_hpos_enabled() ) {
						$this->loader->add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', $plugin_admin, 'lddfw_handle_bulk_actions__premium_only', 10, 3 );
						$this->loader->add_filter( 'bulk_actions-woocommerce_page_wc-orders', $plugin_admin, 'lddfw_bulk_actions_edit__premium_only', 20, 1 );

					} else {
						$this->loader->add_filter( 'handle_bulk_actions-edit-shop_order', $plugin_admin, 'lddfw_handle_bulk_actions__premium_only', 10, 3 );
						$this->loader->add_filter( 'bulk_actions-edit-shop_order', $plugin_admin, 'lddfw_bulk_actions_edit__premium_only', 20, 1 );

					}

					/**
					 * Emails
					*/
					$this->loader->add_action( 'woocommerce_email_classes', $plugin_admin, 'lddfw_woocommerce_emails__premium_only' );
					$this->loader->add_filter( 'woocommerce_locate_template', $plugin_admin, 'lddfw_woocommerce_locate_template__premium_only', 10, 3 );
					$this->loader->add_action( 'woocommerce_email_before_order_table', $plugin_admin, 'lddfw_woocommerce_add_content_specific_email__premium_only', 20, 4 );

					/**
					 * Custom fields
					*/
					$this->loader->add_action( 'init', $plugin_admin, 'lddfw_custom_fields_posttype__premium_only' );

					/**
					 * Driver pages
					*/
					$this->loader->add_action( 'init', $plugin_admin, 'lddfw_driver_pages_posttype__premium_only' );

					// Daily event.
					$this->loader->add_action( 'lddfw_daily_event', $plugin_admin, 'lddfw_daily_event__premium_only' );
				}
			}

				// Remove 'meta_query' from unsupported arguments
				add_filter( 'woocommerce_order_data_store_cpt_query_unsupported_args', function( $args ) {
					return array_diff( $args, [ 'meta_query' ] );
				} );
		}

		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function define_public_hooks() {
			$plugin_public = new LDDFW_Public( $this->get_plugin_name(), $this->get_version() );

			// Drivers page.
			$this->loader->add_action( 'template_redirect', $plugin_public, 'lddfw_page_template_redirect' , 99 );
			//$this->loader->add_filter( 'page_template', $plugin_public, 'lddfw_page_template' , 99, 1 );

			// Scripts.
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

			// Customer order.
			$this->loader->add_action( 'woocommerce_order_details_before_order_table', $plugin_public, 'lddfw_action_order_details_before_order_table', 10, 4 );
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
		 * @return LDDFW_Loader    Orchestrates the hooks of the plugin.
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
}
