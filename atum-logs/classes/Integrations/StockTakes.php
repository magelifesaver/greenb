<?php
/**
 * Stock Takes + Atum Action Logs integration
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

use Atum\Addons\Addons;
use Atum\Components\AtumCache;
use AtumLogs\Inc\Helpers;
use AtumLogs\Models\LogEntry;
use AtumLogs\Models\LogModel;
use AtumST\StockTakes\Models\StockTake;
use AtumST\StockTakes\StockTakes as STStockTakes;


class StockTakes {

	/**
	 * The singleton instance holder
	 *
	 * @var StockTakes
	 */
	private static $instance;

	/**
	 * StockTakes singleton constructor
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

		// Stock Takes texts.
		add_filter( 'atum/logs/get_entry_text', array( $this, 'get_entry_text' ), 10, 3 );

		// Stock Takes params.
		add_filter( 'atum/logs/get_entry_params', array( $this, 'get_entry_params' ), 10, 3 );

		// Log create new Stock Take.
		add_action( 'atum/stock_takes/ajax/save_stock_take', array( $this, 'log_create_stock_take' ), 10, 2 );

		// Add Stock Takes to log method for trash/untrash/delete.
		add_filter( 'atum/action_logs/entity_data', array( $this, 'add_stock_takes_trash_del' ), 10, 3 );

		// Log reconcile stock for products in Stock Take.
		add_action( 'atum/stock_takes/after_reconcile_items', array( $this, 'log_reconcile_stock_take' ) );

		// Check for product stock before reconcile.
		add_action( 'atum/stock_takes/before_reconcile_item', array( $this, 'check_stock_levels' ) );

		// Log for product stock levels changes after reconcile.
		add_action( 'atum/stock_takes/after_reconcile_item', array( $this, 'log_stock_levels_after_reconcile' ), 10, 2 );
	}

	/**
	 * Add the ST text entries
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
			case LogEntry::ACTION_ST_ORDER_ADD:
				/* Translators: %s: Stock Take link */
				$text = __( 'Created the Stock Take %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_ST_ORDER_DEL:
				/* Translators: %s: Stock Take link */
				$text = __( 'Deleted Permanently the Stock Take %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_ST_ORDER_TRASH:
				/* Translators: %s: Stock Take link */
				$text = __( 'Moved to trash the Stock Take %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_ST_ORDER_UNTRASH:
				/* Translators: %s: Stock Take link */
				$text = __( 'Restored the Stock Take %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_ST_ORDER_RECONCILE:
				/* Translators: %s: Picking List link */
				$text = __( 'Reconciled the stock for the products in the Stock Take %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_ST_RECONCILE_STOCK_LVL:
				/* Translators: %1$s: Product link, %2$s: Order link */
				$text = __( 'The stock levels of product %1$s changed after reconcile the Stock Take %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case LogEntry::ACTION_ST_RECONCILE_INV_STLVL:
				/* Translators: %1$s: Inventory name, %2$s: Product link, %3$s: Order link */
				$text = __( 'The stock levels of the inventory %1$s from the product %2$s changed after reconcile the Stock Take %3$s', ATUM_LOGS_TEXT_DOMAIN );
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
			case LogEntry::ACTION_ST_ORDER_ADD:
			case LogEntry::ACTION_ST_ORDER_DEL:
			case LogEntry::ACTION_ST_ORDER_TRASH:
			case LogEntry::ACTION_ST_ORDER_UNTRASH:
			case LogEntry::ACTION_ST_ORDER_RECONCILE:
				$params = [
					'id'   => 'link',
					'name' => 'content',
				];
				break;
			case LogEntry::ACTION_ST_RECONCILE_STOCK_LVL:
				$params = [
					'product_id'   => 'link',
					'product_name' => 'content',
					'st_id'        => 'link',
					'st_name'      => 'content',
				];
				break;
			case LogEntry::ACTION_ST_RECONCILE_INV_STLVL:
				$params = [
					'inventory_name' => 'content',
					'product_id'     => 'link',
					'product_name'   => 'content',
					'st_id'          => 'link',
					'st_name'        => 'content',
				];
				break;
		}

		return $params;
	}

	/**
	 * Log for create a new Stock Take
	 *
	 * @since 1.3.4
	 *
	 * @param StockTake $stock_take
	 * @param boolean   $is_new
	 *
	 * @throws \Exception
	 */
	public function log_create_stock_take( $stock_take, $is_new ) {

		if ( $is_new && $stock_take instanceof StockTake ) {
			$log_data = [
				'source' => LogModel::SRC_ST,
				'module' => LogModel::MOD_ST_ORDERS,
				'data'   => [
					'id'   => $stock_take->get_id(),
					'name' => $stock_take->get_title(),
				],
				'entry'  => LogEntry::ACTION_ST_ORDER_ADD,
			];
			LogModel::maybe_save_log( $log_data );
		}

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
	public function add_stock_takes_trash_del( $entity_data, $post_id, $type ) {

		if ( STStockTakes::POST_TYPE === $type ) {

			$stock_take = new StockTake( $post_id );

			$entity_data = array(
				'entity' => 'ST_ORDER',
				'name'   => $stock_take->get_title(),
				'module' => LogModel::MOD_ST_ORDERS,
				'source' => LogModel::SRC_ST,
			);
		}

		return $entity_data;

	}

	/**
	 * Log for reconcile Stock Take
	 *
	 * @since 1.3.4
	 *
	 * @param StockTake $stock_take
	 *
	 * @throws \Exception
	 */
	public function log_reconcile_stock_take( $stock_take ) {

		if ( $stock_take && $stock_take instanceof StockTake ) {
			$log_data = [
				'source' => LogModel::SRC_ST,
				'module' => LogModel::MOD_ST_ORDERS,
				'data'   => [
					'id'   => $stock_take->get_id(),
					'name' => $stock_take->get_title(),
				],
				'entry'  => LogEntry::ACTION_ST_ORDER_RECONCILE,
			];
			LogModel::maybe_save_log( $log_data );
		}
	}

	/**
	 * Check product stock levels
	 *
	 * @since 1.3.4
	 *
	 * @param \WC_Product $product
	 */
	public function check_stock_levels( $product ) {
		$product = apply_filters( 'atum/action_logs/check_item_stock_levels', $product );
		Helpers::check_product_stock_levels( $product );
	}

	/**
	 * Log for product stock levels changes after reconcile
	 *
	 * @since 1.3.4
	 *
	 * @param \WC_Product $product
	 * @param StockTake   $stock_take
	 *
	 * @throws \Exception
	 */
	public function log_stock_levels_after_reconcile( $product, $stock_take ) {

		if ( apply_filters( 'atum/action_logs/log_item_stock_levels', FALSE, $product, $stock_take ) ) {
			return;
		}

		if ( $product instanceof \WC_Product && $product->exists() && $product->managing_stock() ) {

			$new_stock  = $product->get_stock_quantity();
			$new_status = $product->get_stock_status();

			$transient_key = AtumCache::get_transient_key( 'log_product_stocklevels_' . $product->get_id() );
			$stock_levels  = AtumCache::get_transient( $transient_key, TRUE );

			$old_stock  = $stock_levels['stock'];
			$old_status = $stock_levels['status'];

			if ( ! empty( $stock_levels ) && ( $new_stock !== $old_stock || $new_status !== $old_status ) ) {

				$log_data = [
					'source' => LogModel::SRC_ST,
					'module' => LogModel::MOD_ST_ORDERS,
					'entry'  => LogEntry::ACTION_ST_RECONCILE_STOCK_LVL,
					'data'   => [
						'st_id'        => $stock_take->get_id(),
						'st_name'      => $stock_take->get_title(),
						'product_id'   => $product->get_id(),
						'product_name' => $product->get_name(),
						'old_stock'    => $old_stock,
						'new_stock'    => $new_stock,
						'old_status'   => $old_status,
						'new_status'   => $new_status,
					],
				];
				if ( 'variation' === $product->get_type() ) {
					$log_data['data']['product_parent'] = $product->get_parent_id();
				}
				LogModel::maybe_save_log( $log_data );
			}

			AtumCache::delete_transients( $transient_key );
		}
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
	 * @return StockTakes instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
