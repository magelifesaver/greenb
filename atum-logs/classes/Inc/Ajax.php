<?php
/**
 * Ajax callbacks
 *
 * @package        AtumLogs
 * @subpackage     Inc
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2025 Stock Management Labs™
 *
 * @since          0.0.1
 */

namespace AtumLogs\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Settings\Settings as AtumSettings;
use AtumLogs\LogRegistry\Lists\ListTable;
use AtumLogs\LogRegistry\LogRegistry;
use AtumLogs\Models\LogModel;

final class Ajax {

	/**
	 * The singleton instance holder
	 *
	 * @var Ajax
	 */
	private static $instance;

	/**
	 * Ajax singleton constructor
	 */
	private function __construct() {

		// Ajax callback for Log Registry List.
		add_action( 'wp_ajax_atum_fetch_log_registry_list', array( $this, 'fetch_log_registry_list' ) );

		// Apply bulk actions on ListTable components.
		add_action( 'wp_ajax_atum_apply_bulk_action', array( $this, 'apply_bulk_action' ), 1 );

		// Displays data panel in logs list/table.
		add_action( 'wp_ajax_atum_display_log_data', array( $this, 'display_log_data' ) );

		// Remove logs Tool.
		add_action( 'wp_ajax_atum_tool_al_remove_logs', array( $this, 'remove_logs_tool' ) );

	}

	/**
	 * Loads the Log Registry ListTable class and calls ajax_response method
	 *
	 * @package Log Registry
	 *
	 * @since   0.0.5
	 */
	public function fetch_log_registry_list() {

		check_ajax_referer( 'atum-list-table-nonce', 'security' );

		$args = array(
			'per_page' => ! empty( $_REQUEST['per_page'] ) ? absint( $_REQUEST['per_page'] ) : AtumHelpers::get_option( 'al_logs_per_page', AtumSettings::DEFAULT_POSTS_PER_PAGE ),
			'paged'    => ! empty( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 1,
			'screen'   => Globals::ATUM_UI_HOOK . '_page_' . LogRegistry::UI_SLUG,
			'show_cb'  => TRUE,
		);

		if ( ! empty( $_REQUEST['view'] ) && 'all_stock' === $_REQUEST['view'] ) {
			$_REQUEST['view'] = '';
		}

		do_action( 'atum/logs/ajax/logs_registry_list/before_fetch_stock', $this );

		$list = new ListTable( $args );

		$list->ajax_response();

	}

	/**
	 * Apply actions in bulk to the selected ListTable rows
	 *
	 * @package ATUM List Tables
	 *
	 * @since   0.2.1
	 */
	public function apply_bulk_action() {

		check_ajax_referer( 'atum-list-table-nonce', 'security' );

		if ( empty( $_POST['ids'] ) ) {
			wp_send_json_error( __( 'No Items Selected.', ATUM_LOGS_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['bulk_action'] ) ) {
			wp_send_json_error( __( 'Invalid bulk action.', ATUM_LOGS_TEXT_DOMAIN ) );
		}

		$ids = array_map( 'absint', $_POST['ids'] );

		switch ( $_POST['bulk_action'] ) {
			case 'mark_featured':
			case 'unmark_featured':
			case 'mark_read':
			case 'unmark_read':
			case 'erase_logs':
			case 'delete_logs':
			case 'undelete_logs':
				$bulk = $_POST['bulk_action'];

				foreach ( $ids as $id ) {

					Helpers::$bulk( $id );
				}

				break;
			case 'empty_trash':
				Helpers::empty_trash();
				break;
			default:
				return;
		}

		wp_send_json_success( __( 'Action applied to the selected logs successfully.', ATUM_LOGS_TEXT_DOMAIN ) );
		wp_die();
	}

	/**
	 * Displays data panel in logs list/table
	 *
	 * @since 0.3.2
	 */
	public function display_log_data() {

		check_ajax_referer( 'atum-list-table-nonce', 'security' );

		if ( empty( $_POST['id'] ) ) {
			wp_send_json_error( __( 'No Item Selected.', ATUM_LOGS_TEXT_DOMAIN ) );
		}

		$id  = intval( $_POST['id'] );
		$log = LogModel::get_log_data( $id );

		print wp_json_encode( $log->data );

		wp_die();
	}

	/**
	 * Removes logs from settings tool
	 *
	 * @since 0.5.1
	 *
	 * @throws \Exception
	 */
	public function remove_logs_tool() {

		check_ajax_referer( 'atum-script-runner-nonce', 'security' );

		$date1 = FALSE;
		$date2 = FALSE;

		if ( ! empty( $_POST['option'] ) ) {

			$options = $_POST['option'];

			$date1 = $options['al_tool_remove_logs_from'] ?? FALSE;
			$date2 = $options['al_tool_remove_logs_to'] ?? FALSE;
		}

		$response = Helpers::delete_range_logs( $date1, $date2 );

		if ( $response['error'] ) {
			wp_send_json_error( $response['text'] );
		}
		else {
			wp_send_json_success( $response['text'] );
		}
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
	 * @return Ajax instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
