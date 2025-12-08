<?php
/**
 * The POs List Table page
 *
 * @package         AtumPO
 * @subpackage      ListTables
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.12
 */

namespace AtumPO\ListTables;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumListTables\AtumListPage;
use Atum\Components\AtumAdminNotices;
use Atum\Components\AtumListTables\AtumListTable;
use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Inc\Main as AtumMain;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumPO\Inc\Helpers;
use AtumPO\ListTables\Lists\ListTable;


class POListPage extends AtumListPage {

	/**
	 * The singleton instance holder
	 *
	 * @var POListPage
	 */
	private static $instance;

	/**
	 * The admin page slug
	 */
	const UI_SLUG = 'atum-purchase-orders';

	/**
	 * The menu order for this add-on.
	 * NOTE: using the same number that uses the free POs.
	 */
	const MENU_ORDER = 3;

	/**
	 * List data to show
	 *
	 * @var ListTable $list
	 */
	protected $list;

	/**
	 * POList singleton constructor
	 *
	 * @since 0.9.12
	 */
	private function __construct() {

		// Add the new Purchase Orders submenu to the ATUM menu.
		add_filter( 'atum/admin/menu_items', array( $this, 'add_menu' ), 1 );
		remove_filter( 'atum/admin/top_bar/menu_items', array( PurchaseOrders::get_instance(), 'add_admin_bar_link' ), 11 );

		// Remove the legacy PO's post type from the ATUM menu.
		add_filter( 'atum/order_post_type/post_type_args', function ( $args, $post_type ) {

			if ( PurchaseOrders::POST_TYPE === $post_type ) {
				$args['show_in_menu']      = FALSE;
				$args['show_in_admin_bar'] = FALSE;
			}

			return $args;

		}, 10, 2 );

		if ( is_admin() ) {

			// Activate the Purchase Orders submenu when any PO is opened.
			add_filter( 'parent_file', array( $this, 'add_parent_file' ) );
			add_filter( 'submenu_file', array( $this, 'add_submenu_file' ), 10, 2 );

			// Initialize on admin page load.
			add_action( 'load-' . AtumGlobals::ATUM_UI_HOOK . '_page_' . self::UI_SLUG, array( $this, 'screen_options' ) );
			add_action( 'load-toplevel_page_' . self::UI_SLUG, array( $this, 'screen_options' ) );

			// Allow the styled ATUM footer on the POs list page.
			add_filter( 'atum/admin/allow_styled_footer', array( $this, 'allow_styled_footer' ) );

			// Redirect to the new POs list table when accessing to the old post type list table.
			add_action( 'load-edit.php', array( $this, 'post_type_list_table_redirect' ) );

			$this->init_hooks();

		}

	}

	/**
	 * Add the Purchase Orders submenu to the ATUM menu
	 *
	 * @since 0.9.12
	 *
	 * @param array $atum_menus
	 *
	 * @return array
	 */
	public function add_menu( $atum_menus ) {

		$atum_menus[ self::UI_SLUG ] = array(
			'title'      => __( 'Purchase Orders Pro', ATUM_PO_TEXT_DOMAIN ),
			'callback'   => array( $this, 'display' ),
			'slug'       => self::UI_SLUG,
			'menu_order' => self::MENU_ORDER,
		);

		return $atum_menus;

	}

	/**
	 * Hack the parent_file in admin menu for show ATUM menu opened.
	 *
	 * @since 0.9.15
	 *
	 * @see /wp-admin/menu-header.php
	 *
	 * @param string $parent_file
	 *
	 * @return string
	 */
	public function add_parent_file( $parent_file ) {

		if ( 'edit.php?post_type=' . PurchaseOrders::POST_TYPE === $parent_file ) {
			$parent_file = AtumMain::get_main_menu_item()['slug'];
		}

		return $parent_file;
	}

	/**
	 * Hack the submenu_file in admin menu for show Purchase Orders item as current.
	 *
	 * @since 0.9.15
	 *
	 * @see /wp-admin/menu-header.php
	 *
	 * @param string $submenu_file
	 * @param string $parent_file
	 *
	 * @return string
	 */
	public function add_submenu_file( $submenu_file, $parent_file ) {

		if (
			( 'post-new.php?post_type=' . PurchaseOrders::POST_TYPE === $submenu_file || 'edit.php?post_type=' . PurchaseOrders::POST_TYPE === $submenu_file )
			&& AtumMain::get_main_menu_item()['slug'] === $parent_file
		) {
			$submenu_file = self::UI_SLUG;
		}

		return $submenu_file;
	}

	/**
	 * Display the POs List page
	 *
	 * @since 0.9.12
	 */
	public function display() {

		$this->set_per_page();
		parent::display();

		AtumHelpers::load_view( ATUM_PO_PATH . 'views/lists/list-page', array(
			'list'           => $this->list,
			'ajax'           => AtumHelpers::get_option( 'enable_ajax_filter', 'yes' ),
			'url'            => $this->get_list_table_page_url(),
			'late_pos_count' => Helpers::get_late_pos_count(),
		) );

	}

	/**
	 * Enable Screen options creating the list table before the Screen option panel is rendered and enable
	 * "per page" option. Also add help tabs and help sidebar
	 *
	 * @since 0.9.12
	 */
	public function screen_options() {

		$this->set_per_page();

		// Add "POs per page" screen option.
		$args = array(
			'label'   => __( 'POs per page', ATUM_PO_TEXT_DOMAIN ),
			'default' => $this->per_page,
			'option'  => str_replace( '-', '_', self::UI_SLUG . '_entries_per_page' ),
		);

		add_screen_option( 'per_page', $args );

		$this->list = new ListTable( [
			'singular' => 'po',
			'plural'   => 'pos',
			'per_page' => $this->per_page,
			'show_cb'  => TRUE,
			'screen'   => AtumGlobals::ATUM_UI_HOOK . '_page_' . self::UI_SLUG,
		] );

	}

	/**
	 * Allow the ATUM styled footer on POs list page
	 *
	 * @since 0.9.12
	 *
	 * @param string[] $allowed_screens
	 *
	 * @return string[]
	 */
	public function allow_styled_footer( $allowed_screens ) {

		$allowed_screens[] = AtumGlobals::ATUM_UI_HOOK . '_page_' . self::UI_SLUG;

		return $allowed_screens;
	}

	/**
	 * Redirect to the new POs list table when accessing to the old post-type list table
	 *
	 * @since 0.9.13
	 */
	public function post_type_list_table_redirect() {

		$screen = get_current_screen();

		if ( $screen && 'edit-' . PurchaseOrders::POST_TYPE === $screen->id ) {

			// Bypass the untrash action.
			if (
				! empty( $_GET['doaction'] ) && 'undo' === $_GET['doaction'] &&
				! empty( $_GET['action'] ) && 'untrash' === $_GET['action']
			) {
				return;
			}

			$redirect = $this->get_list_table_page_url();

			if ( isset( $_GET['trashed'] ) && intval( $_GET['trashed'] ) > 0 ) {
				$undo_url = '<a href="' . esc_url( wp_nonce_url( 'edit.php?post_type=' . PurchaseOrders::POST_TYPE . '&doaction=undo&action=untrash&ids=' . ( $_GET['ids'] ?? '' ), 'bulk-posts' ) ) . '">' . __( 'Undo', ATUM_PO_TEXT_DOMAIN ) . '</a>';
				/* translators: the undo URL */
				$message = sprintf( _n( 'The PO was archived successfully. %s', 'The POs were archived successfully. %s', intval( $_GET['trashed'] ), ATUM_PO_TEXT_DOMAIN ), $undo_url );
				AtumAdminNotices::add_notice( $message, 'purchase_orders_pro_archived', 'success', TRUE, TRUE );
			}

			wp_safe_redirect( $redirect );
			exit();

		}

	}


	/****************************
	 * Instance methods
	 ****************************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_PO_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_PO_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return POListPage instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
