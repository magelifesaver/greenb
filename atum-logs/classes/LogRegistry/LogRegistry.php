<?php
/**
 * The Log Registry page class
 *
 * @package         AtumLogs
 * @subpackage      LogRegistry
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.0.5
 */

namespace AtumLogs\LogRegistry;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumListTables\AtumListPage;
use Atum\Inc\Globals;
use Atum\Inc\Helpers as AtumHelpers;
use AtumLogs\LogRegistry\Lists\ListTable;


class LogRegistry extends AtumListPage {

	/**
	 * The singleton instance holder
	 *
	 * @var LogRegistry
	 */
	private static $instance;

	/**
	 * The admin page slug
	 */
	const UI_SLUG = 'atum-logs';

	/**
	 * The menu order for this add-on
	 */
	const MENU_ORDER = 71;

	/**
	 * List data to show
	 *
	 * @var ListTable $list
	 */
	protected $list;

	/**
	 * Log Registry singleton constructor
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		// Add the "Log Registry" submenu to ATUM menu.
		add_filter( 'atum/admin/menu_items', array( $this, 'add_menu' ), self::MENU_ORDER );

		if ( is_admin() ) {

			// Initialize on admin page load.
			add_action( 'load-' . Globals::ATUM_UI_HOOK . '_page_' . self::UI_SLUG, array( $this, 'screen_options' ) );
			add_action( 'load-toplevel_page_' . self::UI_SLUG, array( $this, 'screen_options' ) );

			// Setup the data export tab for Log Registry page.
			add_filter( 'atum/data_export/allowed_pages', array( $this, 'add_data_export' ) );
			add_filter( 'atum/data_export/js_settings', array( $this, 'data_export_settings' ), 10, 2 );
			add_filter( 'atum/data_export/html_report_class', array( $this, 'html_report_class' ) );
			add_filter( 'atum/data_export/report_title', array( $this, 'report_title' ) );

			// Allow the styled ATUM footer on the Log Registry page.
			add_filter( 'atum/admin/allow_styled_footer', array( $this, 'allow_styled_footer' ) );

			$this->init_hooks();

		}

	}

	/**
	 * Add the "Log Registry" submenu to the ATUM menu
	 *
	 * @since 0.0.1
	 *
	 * @param array $atum_menus
	 *
	 * @return array
	 */
	public function add_menu( $atum_menus ) {

		$atum_menus['log-registry'] = array(
			'title'      => __( 'Action Logs', ATUM_LOGS_TEXT_DOMAIN ),
			'callback'   => array( $this, 'display' ),
			'slug'       => self::UI_SLUG,
			'menu_order' => self::MENU_ORDER,
		);

		return $atum_menus;

	}

	/**
	 * Display the Log Registry admin page
	 *
	 * @since 0.0.5
	 */
	public function display() {

		$this->set_per_page( 'al_logs_per_page' );
		parent::display();

		$url = add_query_arg( 'page', self::UI_SLUG, admin_url( 'admin.php' ) );

		AtumHelpers::load_view( ATUM_LOGS_PATH . 'views/log-registry', array(
			'list' => $this->list,
			'ajax' => AtumHelpers::get_option( 'enable_ajax_filter', 'yes' ),
			'url'  => $url,
		) );

	}

	/**
	 * Enable Screen options creating the list table before the Screen option panel is rendered and enable
	 * "per page" option. Also add help tabs and help sidebar
	 *
	 * @since 0.0.5
	 */
	public function screen_options() {

		$this->set_per_page( 'al_logs_per_page' );

		// Add "Products per page" screen option.
		$args = array(
			'label'   => __( 'Logs per page', ATUM_LOGS_TEXT_DOMAIN ),
			'default' => $this->per_page,
			'option'  => str_replace( '-', '_', self::UI_SLUG . '_entries_per_page' ),
		);

		add_screen_option( 'per_page', $args );

		/*
		 TODO: Add a help pannel to Log Registry page.
		$help_tabs = array(
			array(
				'name'  => 'general',
				'title' => __( 'General', ATUM_LOGS_TEXT_DOMAIN ),
			),
		);

		$screen = get_current_screen();

		foreach ( $help_tabs as $help_tab ) {
			$screen->add_help_tab( array_merge( array(
				'id'       => ATUM_PREFIX . __CLASS__ . '_help_tabs_' . $help_tab['name'],
				'callback' => array( $this, 'help_tabs_content' ),
			), $help_tab ) );
		}

		$screen->set_help_sidebar( AtumHelpers::load_view_to_string( ATUM_LOGS_PATH . 'views/help-tabs/log-registry/help-sidebar' ) );
		*/

		$this->list = new ListTable( [
			'singular' => 'log',
			'plural'   => 'logs',
			'per_page' => $this->per_page,
			'show_cb'  => TRUE,
			'screen'   => Globals::ATUM_UI_HOOK . '_page_' . self::UI_SLUG,
		] );

	}

	/**
	 * Display the help tabs' content
	 *
	 * @since 0.0.5
	 *
	 * @param \WP_Screen $screen The current screen.
	 * @param array      $tab    The current help tab.
	 */
	public function help_tabs_content( $screen, $tab ) {

		// AtumHelpers::load_view( ATUM_LOGS_PATH . 'views/help-tabs/log-registry/' . $tab['name'] );
		// No content.
	}

	/**
	 * Add the Data Export functionality to the Log Registry page
	 *
	 * @since 0.0.1
	 *
	 * @param array $allowed_pages
	 *
	 * @return array
	 */
	public function add_data_export( $allowed_pages ) {

		$allowed_pages[] = Globals::ATUM_UI_HOOK . '_page_' . self::UI_SLUG;
		$allowed_pages[] = 'toplevel_page_' . self::UI_SLUG;

		return $allowed_pages;

	}

	/**
	 * Customize the settings in Log Registry
	 *
	 * @since 0.0.1
	 *
	 * @param array  $js_settings
	 * @param string $page_hook
	 *
	 * @return array
	 */
	public function data_export_settings( $js_settings, $page_hook ) {
		// Only edit the settings if we are in the Log Registry page.
		if ( str_contains( $page_hook, self::UI_SLUG ) ) {
			unset( $js_settings['categories'],
				$js_settings['categoriesTitle'],
				$js_settings['productTypes'],
				$js_settings['productTypesTitle']
			);
			$js_settings['titleLength'] = __( 'Entries (max number of characters)', ATUM_LOGS_TEXT_DOMAIN );
		}

		return $js_settings;

	}

	/**
	 * Returns the Atum Action Logs class for HTML reports
	 *
	 * @since 0.4.1
	 *
	 * @param string $class_name
	 *
	 * @return string
	 */
	public function html_report_class( $class_name ) {

		if ( isset( $_GET['page'] ) && self::UI_SLUG === $_GET['page'] ) {
			return '\AtumLogs\Reports\LogsReport';
		}

		return $class_name;
	}

	/**
	 * Returns the title for the reports
	 *
	 * @since 0.0.1
	 *
	 * @param string $title
	 *
	 * @return string
	 */
	public function report_title( $title ) {

		if ( isset( $_GET['page'] ) && self::UI_SLUG === $_GET['page'] ) {
			return __( 'ATUM Log Registry Report', ATUM_LOGS_TEXT_DOMAIN );
		}

		return $title;
	}

	/**
	 * Allow the ATUM styled footer on Logs Registry page
	 *
	 * @since 0.0.1
	 *
	 * @param array $allowed_screens
	 *
	 * @return array
	 */
	public function allow_styled_footer( $allowed_screens ) {

		$allowed_screens[] = Globals::ATUM_UI_HOOK . '_page_' . self::UI_SLUG;

		return $allowed_screens;
	}


	/****************************
	 * Instance methods
	 ****************************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_LOGS_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_LOGS_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return LogRegistry instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
