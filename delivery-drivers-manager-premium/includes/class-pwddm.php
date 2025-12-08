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
 * @package    PWDDM
 * @subpackage PWDDM/includes
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
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */

if ( ! class_exists( 'PWDDM' ) ) {

	class PWDDM {



		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since  1.0.0
		 * @access protected
		 * @var    PWDDM_Loader    $loader    Maintains and registers all hooks for the plugin.
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
			if ( defined( 'PWDDM_VERSION' ) ) {
				$this->version = PWDDM_VERSION;
			} else {
				$this->version = '1.0.0';
			}
			$this->plugin_name = 'pwddm';

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
		 * - PWDDM_Loader. Orchestrates the hooks of the plugin.
		 * - PWDDM_I18n. Defines internationalization functionality.
		 * - PWDDM_Admin. Defines all hooks for the admin area.
		 * - PWDDM_Public. Defines all hooks for the public side of the site.
		 *
		 * Create an instance of the loader which will be used to register the hooks
		 * with WordPress.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function load_dependencies() {

			/**
			 * The functions file.
			 * core plugin.
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/functions.php';

			/**
			 * The class responsible for orchestrating the actions and filters of the
			 * core plugin.
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pwddm-loader.php';

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-pwddm-admin.php';

			/**
			 * The class responsible for defining all actions that occur in the public-facing
			 * side of the site.
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-pwddm-public.php';

			/**
			 * The file responsible for defining all the metaboxes in admin panel
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/pwddm-metaboxes.php';
			/**
			 * The file responsible for manager page
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pwddm-manager.php';

			/**
			 * The file responsible for the manager login
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pwddm-login.php';

			/**
			 * The file responsible for the passwords
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pwddm-password.php';

			if ( pwddm_fs()->is__premium_only() ) {
				if ( pwddm_fs()->can_use_premium_code() ) {
					/**
					 * The file responsible for the routes
					 */
					include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pwddm-route.php';
				}
			}

			/**
			 * The file responsible for the screens
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pwddm-screens.php';
			/**
			 * The file responsible for the order
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pwddm-order.php';
			/**
			 * The file responsible for the orders
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pwddm-orders.php';
			/**
			 * The file responsible for the drivers
			 */
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pwddm-driver.php';

			/**
			 * The file responsible for the drivers
			*/
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pwddm-reports.php';

			$this->loader = new PWDDM_Loader();
		}

		/**
		 * Define the locale for this plugin for internationalization.
		 *
		 * Uses the PWDDM_I18n class in order to set the domain and to register the hook
		 * with WordPress.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function set_locale() {
			// $plugin_i18n = new PWDDM_I18n();

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
			$plugin_admin = new PWDDM_Admin( $this->get_plugin_name(), $this->get_version() );

			/**
			 * Scripts.
			 */
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

			/**
			 * Users custom columns
			 */
			$this->loader->add_filter( 'manage_users_columns', $plugin_admin, 'pwddm_users_list_columns' );
			$this->loader->add_filter( 'manage_users_custom_column', $plugin_admin, 'pwddm_users_list_columns_raw', 10, 3 );

			/**
			 * Ajax calls
			 */
			$this->loader->add_action( 'wp_ajax_pwddm_ajax', $plugin_admin, 'pwddm_ajax' );
			$this->loader->add_action( 'wp_ajax_nopriv_pwddm_ajax', $plugin_admin, 'pwddm_ajax' );

			/**
			 * Settings
			*/
			$this->loader->add_action( 'admin_init', $plugin_admin, 'pwddm_settings_init' );

			/**
			 * Users
			*/
			$this->loader->add_action( 'show_user_profile', $plugin_admin, 'pwddm_user_fields' );
			$this->loader->add_action( 'edit_user_profile', $plugin_admin, 'pwddm_user_fields' );
			$this->loader->add_action( 'personal_options_update', $plugin_admin, 'pwddm_user_fields_save' );
			$this->loader->add_action( 'edit_user_profile_update', $plugin_admin, 'pwddm_user_fields_save' );
			$this->loader->add_action( 'lddfw_driver_fields', $plugin_admin, 'pwddm_driver_fields', 10, 1 );

			/**
			 * Commision
			*/
			$this->loader->add_filter( 'lddfw_set_order_commission', $plugin_admin, 'pwddm_set_order_commission', 10, 2 );

			/**
			 * Seller drivers
			*/
			$this->loader->add_filter( 'lddfw_get_driver_seller', $plugin_admin, 'pwddm_get_driver_seller', 10, 1 );
			$this->loader->add_filter( 'lddfw_assign_driver_permission', $plugin_admin, 'pwddm_assign_driver_permission', 10, 2 );

			/**
			* Add menu
			*/
			$this->loader->add_action( 'admin_menu', $plugin_admin, 'pwddm_admin_menu', 99 );

		}

		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function define_public_hooks() {
			$plugin_public = new PWDDM_Public( $this->get_plugin_name(), $this->get_version() );
			// manager page.
			$this->loader->add_action( 'template_redirect', $plugin_public, 'pwddm_page_template_redirect' , 1 );
			//$this->loader->add_filter( 'page_template', $plugin_public, 'pwddm_page_template' );
			// Scripts.
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
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
		 * @return PWDDM_Loader    Orchestrates the hooks of the plugin.
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
