<?php
/**
 * Pick&Pack + Atum Action Logs integration
 *
 * @since       1.3.4
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumLogs
 * @subpackage  Integrations
 */

namespace AtumLogs\Integrations;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Models\AtumOrderModel;
use AtumLogs\Models\LogEntry;
use AtumLogs\Models\LogModel;
use AtumPickPack\PickPackOrders\Exports\PPOExport;
use AtumPickPack\PickPackOrders\Items\PPOItemProduct;
use AtumPickPack\PickPackOrders\Models\PickPackOrder;
use AtumPickPack\PickPackOrders\Models\PPOItem;
use AtumPickPack\PickPackOrders\PickPackOrders;


class PickPack {

	/**
	 * The singleton instance holder
	 *
	 * @var PickPack
	 */
	private static $instance;

	/**
	 * PickPack singleton constructor
	 *
	 * @since 1.3.4
	 */
	private function __construct() {

		if ( is_admin() ) {
			$this->register_admin_hooks();
		}

	}

	/**
	 * Register the hooks for the admin side
	 *
	 * @since 1.3.4
	 */
	public function register_admin_hooks() {

		// Pick Pack texts.
		add_filter( 'atum/logs/get_entry_text', array( $this, 'get_entry_text' ), 10, 3 );

		// Pick Pack params.
		add_filter( 'atum/logs/get_entry_params', array( $this, 'get_entry_params' ), 10, 3 );

		// Log for create new Picking list.
		add_action( 'atum/pick_pack/ajax/after_create_picking_list', array( $this, 'log_create_picking_list' ), 10, 2 );

		// Add Stock Takes to log method for trash/untrash/delete.
		add_filter( 'atum/action_logs/entity_data', array( $this, 'add_picking_list_trash_del' ), 10, 3 );

		// Log for Picking List PDF export.
		add_action( 'atum/pick_pack/generate_ppo_pdf', array( $this, 'log_export_pdf' ) );

		// Log for packing items.
		add_action( 'atum/pick_pack/ajax/after_packing_item', array( $this, 'log_pack_item' ), 10, 3 );

		// Log completed order.
		add_action( 'atum/pick_pack/ajax/complete_order', array( $this, 'log_complete_order' ) );

		// Log completed Picking List.
		add_action( 'atum/pick_pack/after_complete_picking_list', array( $this, 'log_complete_picking_list' ) );

		// Log status changes.
		add_action( 'atum/atum_order_model/update_status', array( $this, 'log_pp_mark_atum_order' ), 10, 2 );

	}

	/**
	 * Add the ST entries texts
	 *
	 * @since 1.3.4
	 *
	 * @param string  $text
	 * @param string  $slug
	 * @param boolean $save
	 *
	 * @return string
	 */
	public function get_entry_text( $text, $slug, $save ) {

		switch ( $slug ) {
			case LogEntry::ACTION_PP_ORDER_ADD:
				/* Translators: %s: Picking List link */
				$text = __( 'Created the Picking List %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_PP_ORDER_DEL:
				/* Translators: %s: Picking List link */
				$text = __( 'Deleted Permanently the Picking List %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_PP_ORDER_TRASH:
				/* Translators: %s: Picking List link */
				$text = __( 'Moved to trash the Picking List %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_PP_ORDER_UNTRASH:
				/* Translators: %s: Picking List link */
				$text = __( 'Restored the Picking List %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_PP_ORDER_PRINT:
				/* Translators: %s: Picking List link */
				$text = __( 'Printed the Picking List %s to PDF', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_PP_PACKED_ORDER_ITEM:
				/* Translators: %1$s:units, %2$s:Product name, %3$s:Order link, %4$s: Picking List link */
				$text = __( 'Packed %1$s units of %2$s from the Order %3$s within the Picking List %4$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_PP_PACKED_ORDER:
				/* Translators: %1$s:Order link, %2$s: Picking List link */
				$text = __( 'The order %1$s was picked & packed within the Picking List %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_PP_COMPLETED:
				/* Translators: %s: Picking List link */
				$text = __( 'Completed the Picking List %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_PP_EDIT_STATUS:
				/* Translators: %s: pick&pack link */
				$text = __( 'Changed the Status to the Pick & Pack %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
		}

		return $text;
	}

	/**
	 * Add the ST entries params
	 *
	 * @since 1.3.4
	 *
	 * @param array   $params
	 * @param string  $slug
	 * @param boolean $save
	 *
	 * @return array
	 */
	public function get_entry_params( $params, $slug, $save ) {

		switch ( $slug ) {
			case LogEntry::ACTION_PP_ORDER_ADD:
			case LogEntry::ACTION_PP_ORDER_DEL:
			case LogEntry::ACTION_PP_ORDER_TRASH:
			case LogEntry::ACTION_PP_ORDER_UNTRASH:
			case LogEntry::ACTION_PP_ORDER_PRINT:
			case LogEntry::ACTION_PP_COMPLETED:
			case LogEntry::ACTION_PP_EDIT_STATUS:
				$params = [
					'id'   => 'link',
					'name' => 'content',
				];
				break;
			case LogEntry::ACTION_PP_PACKED_ORDER_ITEM:
				$params = [
					'qty'          => 'content',
					'product_id'   => 'link',
					'product_name' => 'content',
					'order_id'     => 'link',
					'order_name'   => 'content',
					'id'           => 'link',
					'name'         => 'content',
				];
				break;
			case LogEntry::ACTION_PP_PACKED_ORDER:
				$params = [
					'order_id'   => 'link',
					'order_name' => 'content',
					'id'         => 'link',
					'name'       => 'content',
				];
				break;
		}

		return $params;
	}

	/**
	 * Return Picking List name
	 *
	 * @since 1.3.4
	 *
	 * @param PickPackOrder $picking_list
	 *
	 * @return mixed|string
	 */
	private function get_name( $picking_list ) {
		return $picking_list->get_title() === (string) $picking_list->get_id() ? '#' . $picking_list->get_id() : $picking_list->get_title();
	}

	/**
	 * Log for create new Picking List
	 *
	 * @since 1.3.4
	 *
	 * @param PickPackOrder $picking_list
	 * @param int[]         $orders
	 *
	 * @throws \Exception
	 */
	public function log_create_picking_list( $picking_list, $orders ) {

		$log_data = [
			'source' => LogModel::SRC_PP,
			'module' => LogModel::MOD_PP_ORDERS,
			'data'   => [
				'id'     => $picking_list->get_id(),
				'name'   => $this->get_name( $picking_list ),
				'orders' => $orders,
			],
			'entry'  => LogEntry::ACTION_PP_ORDER_ADD,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Add Stock Takes to log method for trash/untrash/delete
	 *
	 * @since 1.3.4
	 *
	 * @param array  $entity_data
	 * @param int    $post_id
	 * @param string $type
	 *
	 * @return array
	 */
	public function add_picking_list_trash_del( $entity_data, $post_id, $type ) {

		if ( PickPackOrders::POST_TYPE === $type ) {

			$picking_list = new PickPackOrder( $post_id );

			$entity_data = array(
				'entity' => 'PP_ORDER',
				'name'   => $this->get_name( $picking_list ),
				'module' => LogModel::MOD_PP_ORDERS,
				'source' => LogModel::SRC_PP,
			);
		}

		return $entity_data;

	}

	/**
	 * Log for Picking list export
	 *
	 * @since 1.3.4
	 *
	 * @param PPOExport $pick_pack_export
	 *
	 * @throws \Exception
	 */
	public function log_export_pdf( $pick_pack_export ) {

		$log_data = [
			'source' => LogModel::SRC_PP,
			'module' => LogModel::MOD_PP_ORDERS,
			'data'   => [
				'id'   => $pick_pack_export->get_id(),
				'name' => $this->get_name( $pick_pack_export ),
			],
			'entry'  => LogEntry::ACTION_PP_ORDER_PRINT,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log for Packing item
	 *
	 * @since 1.3.4
	 *
	 * @param PPOItemProduct $item
	 * @param PickPackOrder  $picking_list
	 * @param int|float      $old_qty
	 *
	 * @throws \Exception
	 */
	public function log_pack_item( $item, $picking_list, $old_qty ) {

		$log_data = [
			'source' => LogModel::SRC_PP,
			'module' => LogModel::MOD_PP_ORDERS,
			'data'   => [
				'id'           => $picking_list->get_id(),
				'name'         => $this->get_name( $picking_list ),
				'product_id'   => $item->get_product_id(),
				'product_name' => $item->get_product()->get_name(),
				'order_id'     => $item->get_meta( 'shop_order' ),
				'order_name'   => '#' . $item->get_meta( 'shop_order' ),
				'qty'          => $item->get_meta( 'packed' ),
				'old_qty'      => $old_qty,
			],
			'entry'  => LogEntry::ACTION_PP_PACKED_ORDER_ITEM,
		];
		if ( 'variation' === $item->get_product()->get_type() ) {
			$log_data['data']['product_parent'] = $item->get_product()->get_parent_id();
		}

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log completed order
	 *
	 * @since 1.3.4
	 *
	 * @param \WC_Order $order
	 *
	 * @return void
	 */
	public function log_complete_order( $order ) {

		$pp_id = $order->get_meta( 'picking_list' );

		if ( $pp_id ) {
			$picking_list = new PickPackOrder( $pp_id );

			$log_data = [
				'source' => LogModel::SRC_PP,
				'module' => LogModel::MOD_PP_ORDERS,
				'data'   => [
					'id'         => $picking_list->get_id(),
					'name'       => $this->get_name( $picking_list ),
					'order_id'   => $order->get_id(),
					'order_name' => '#' . $order->get_id(),
				],
				'entry'  => LogEntry::ACTION_PP_PACKED_ORDER,
			];

			LogModel::maybe_save_log( $log_data );

		}
	}

	/**
	 * Log for complete Picking List.
	 *
	 * @since 1.3.4
	 *
	 * @param PickPackOrder $picking_list
	 */
	public function log_complete_picking_list( $picking_list ) {

		if ( PickPackOrders::FINISHED === $picking_list->get_status() ) {

			$log_data = [
				'source' => LogModel::SRC_PP,
				'module' => LogModel::MOD_PP_ORDERS,
				'data'   => [
					'id'   => $picking_list->get_id(),
					'name' => $this->get_name( $picking_list ),
				],
				'entry'  => LogEntry::ACTION_PP_COMPLETED,
			];

			LogModel::maybe_save_log( $log_data );

		}
	}

	/**
	 * Log Atum Order status as received
	 *
	 * @since 1.3.5
	 *
	 * @param AtumOrderModel $atum_order
	 * @param string         $status
	 *
	 * @throws \Exception
	 */
	public function log_pp_mark_atum_order( $atum_order, $status ) {

		if ( PickPackOrders::POST_TYPE !== $atum_order->get_post_type() ) {
			return;
		}

		$picking_list = new PickPackOrder( $atum_order->get_id() );

		$log_data = [
			'source' => LogModel::SRC_PP,
			'module' => LogModel::MOD_PP_ORDERS,
			'data'   => [
				'id'        => $picking_list->get_id(),
				'name'      => $this->get_name( $picking_list ),
				'field'     => 'status',
				'old_value' => $picking_list->get_status(),
				'new_value' => $status,
			],
			'entry'  => LogEntry::ACTION_PP_EDIT_STATUS,
		];

		LogModel::maybe_save_log( $log_data );

	}

	/********************
	 * Instance methods
	 ********************/

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
	 * @return PickPack instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
