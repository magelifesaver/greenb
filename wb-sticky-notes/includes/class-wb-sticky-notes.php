<?php

/**
 * The file that defines the core plugin class
 *
 *
 * @link       https://wordpress.org/plugins/wb-sticky-notes
 * @since      1.0.0
 *
 * @package    Wb_Sticky_Notes
 * @subpackage Wb_Sticky_Notes/includes
 */

/**
 * The core plugin class.
 *
 *
 *
 * @since      1.0.0
 * @package    Wb_Sticky_Notes
 * @subpackage Wb_Sticky_Notes/includes
 * @author     Web Builder 143 
 */
class Wb_Sticky_Notes {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wb_Sticky_Notes_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	public static $themes=array(
		'yellow',
		'white',
		'purple',
		'pink',
		'green',
		'blue',
		'orange',
		'grey',
	);

	public static $fonts=array(
		'default',
		'indieflower',
		'dancingscript',
		'amatic',
		'marckscript',
		'patrickhand',
		'mrdafoe',
	);

	public static $status=array(
		'active'=>1,
		'archive'=>2,
	);

	public static $settings=null; 

	public static $notes_tb='wb_stn_notes';

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WB_STICKY_NOTES_VERSION' ) ) {
			$this->version = WB_STICKY_NOTES_VERSION;
        } else {
            // Fallback version when the constant is not defined. Update to
            // reflect the current plugin version.
            $this->version = '1.2.7';
        }
		$this->plugin_name =WB_STICKY_PLUGIN_NAME;


		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_ajax_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wb_Sticky_Notes_Loader. Orchestrates the hooks of the plugin.
	 * - Wb_Sticky_Notes_i18n. Defines internationalization functionality.
	 * - Wb_Sticky_Notes_Admin. Defines all hooks for the admin area.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wb-sticky-notes-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wb-sticky-notes-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wb-sticky-notes-admin.php';

		/**
		 * The class responsible for defining methods for ajax functionality.
		 */
		require_once plugin_dir_path( __FILE__) . 'class-wb-sticky-notes-ajax.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/classes/class-wb-sticky-notes-feedback.php';

		$this->loader = new Wb_Sticky_Notes_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wb_Sticky_Notes_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() 
	{
		$plugin_i18n = new Wb_Sticky_Notes_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Initiating main ajax hook
	 *
	 * @since    1.0.0
	 */
	private function define_ajax_hooks()
	{
		$plugin_ajax=new Wb_Sticky_Notes_Ajax($this->get_plugin_name(),$this->get_version());
		$this->loader->add_action('wp_ajax_wb_stn',$plugin_ajax,'ajax_main');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wb_Sticky_Notes_Admin( $this->get_plugin_name(), $this->get_version());
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'admin_menu' );
		// Add plugin settings link:
		$this->loader->add_filter('plugin_action_links_'.plugin_basename(WB_STN_PLUGIN_FILENAME),$plugin_admin,'plugin_action_links');

		if(is_blog_admin())
		{
			$this->loader->add_action( 'admin_bar_menu', $plugin_admin, 'admin_bar_menu',71);
		}

		$settings=self::get_settings();
		$enable=0;
		if($settings['enable']==1)
		{
			$enable=1;
		}else
		{
			if(isset($_GET['page']) && $_GET['page']=='wb-sticky-notes')
			{
				$enable=1;
			}
		}
		if($enable==1)
		{
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		}
	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 * @since    1.1.4 	Role checking added.
	 */
	public function run() {
		$current_user = wp_get_current_user();
		$settings = self::get_settings();
		$allow_to_use = true;

		// User role check needed.
		if ( isset($settings['role_name']) && is_array($settings['role_name']) && !empty($settings['role_name']) && is_array($current_user->roles) ) {		
			$allow_to_use = ! empty( array_intersect( $current_user->roles, $settings['role_name'] ) );
		}

		if ( $allow_to_use ) {
			$this->loader->run();
		}
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Get settings
	 *
	 * @since    1.0.0
	 */
	public static function get_settings()
	{
		if(is_null(self::$settings))
		{
			$default_settings=self::default_settings();
			$settings=get_option(WB_STN_SETTINGS);
			self::$settings=wp_parse_args($settings,$default_settings); 
		}		
		return self::$settings;
	}

	/**
	 * Update settings
	 *
	 * @since    1.0.0
	 */
	public static function update_settings($settings)
	{
		update_option(WB_STN_SETTINGS,$settings);
		self::$settings=$settings;
		return $settings;
	}

	/**
	 * Default settings
	 *
	 * @since    1.0.0
	 */
	public static function default_settings()
	{
		return array(
			'enable'=>1,
			'floating_button'=>1,
			'theme'=>0, //first color: yellow
			'font_family'=>0, //first font
			'font_size'=>'16',
			'width'=>'250',
			'height'=>'250',
			'postop'=>'100',
			'posleft'=>'200',
			'z_index'=>'10000',
			'role_name'=> array(
				'administrator', 
				'editor', 
				'author', 
				'contributor',
				'shop_manager', 
			),
			'hide_on_these_pages' => array(), /** @since 1.2.0 */
		);
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wb_Sticky_Notes_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
