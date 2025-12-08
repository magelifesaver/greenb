<?php
/**
 * Abstract class AtumPOOrderPostType
 *
 * @package         AtumPO\Abstracts
 * @subpackage      AtumPOOrders
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.6
 */

namespace AtumPO\Abstracts\AtumPOOrders;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use Atum\Components\AtumOrders\AtumOrderPostType;
use AtumPO\Abstracts\AtumPOOrders\Models\AtumPOOrder;

abstract class AtumPOOrderPostType extends AtumOrderPostType {

	/**
	 * The capabilities used when registering the post type
	 *
	 * @var array
	 */
	protected $capabilities;


	/**
	 * Load the ATUM Order's common stuff
	 *
	 * @since 0.9.6
	 */
	protected function init() {

		// Register all the ATUM orders' hooks.
		parent::init();

		// Alter the args for the ATUM PO Order post type.
		add_filter( 'atum/order_post_type/post_type_args', array( $this, 'atum_po_order_post_type_args' ), 10, 2 );

		// Add the available ATUM PO order statuses.
		add_filter( 'atum/order_post_type/statuses', array( $this, 'atum_po_order_post_type_statuses' ), 10, 2 );

	}

	/**
	 * Alter the post type args for the ATUM PO Order
	 *
	 * @since 0.9.6
	 *
	 * @param array  $args
	 * @param string $post_type
	 *
	 * @return array
	 */
	public function atum_po_order_post_type_args( $args, $post_type ) {

		// As the ATUM PO Orders are being displayed within a PO only, they cannot be accessible from menus.
		if ( static::POST_TYPE === $post_type ) {
			$args['public']       = FALSE;
			$args['show_in_menu'] = FALSE;
			$args['show_ui']      = FALSE;
		}

		return $args;

	}

	/**
	 * Add the ATUM PO Order statuses to the get_atum_order_post_type_statuses helper
	 *
	 * @since 0.9.6
	 *
	 * @param array  $statuses
	 * @param string $post_type
	 *
	 * @return array
	 */
	public function atum_po_order_post_type_statuses( $statuses, $post_type ) {

		if ( static::POST_TYPE === $post_type ) {
			return self::get_statuses();
		}

		return $statuses;

	}

	/**
	 * Displays the meta box data
	 *
	 * @since 0.9.6
	 *
	 * @param \WP_Post $post
	 */
	public function show_data_meta_box( $post ) {
		// Not needed here.
	}

	/**
	 * Customize the columns used in the ATUM Order's list table
	 *
	 * @since 0.9.6
	 *
	 * @param array $existing_columns
	 *
	 * @return array
	 */
	public function add_columns( $existing_columns ) {
		return []; // Not needed here.
	}

	/**
	 * Add sortable ATUM PO Order columns to the list
	 *
	 * @since 0.9.6
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		return []; // Not needed here.
	}

	/**
	 * Filters and sorting handler for ATUM PO Order columns
	 *
	 * @since 0.9.6
	 *
	 * @param array $query_vars
	 *
	 * @return array
	 */
	public function request_query( $query_vars ) {
		return $query_vars; // Not needed here.
	}

	/**
	 * Specify custom bulk actions messages for the ATUM PO Order post type
	 *
	 * @since 0.9.6
	 *
	 * @param array $bulk_messages
	 * @param array $bulk_counts
	 *
	 * @return array
	 */
	public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
		return []; // Not needed here.
	}

	/**
	 * Change messages when an ATUM PO Order post type is updated
	 *
	 * @since 0.9.6
	 *
	 * @param array $messages
	 *
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		return []; // Not needed here.
	}

	/**
	 * Get the available ATUM PO Order statuses
	 *
	 * @since 0.9.6
	 *
	 * @return array
	 */
	public static function get_statuses() {

		// We don't need any specific statuses for ATUM PO Orders, so just use 'publish'.
		return array(
			'publish' => _x( 'Published', 'ATUM PO order status', ATUM_PO_TEXT_DOMAIN ),
		);

	}

	/**
	 * Get the colors for every ATUM PO Order status
	 *
	 * @since 0.9.6
	 *
	 * @return array
	 */
	public static function get_status_colors() {

		return array(
			'publish' => '#ffffff',
		);

	}

	/**
	 * Get the ATUM PO Orders assigned to the specified PO
	 *
	 * @since 0.9.1
	 *
	 * @param int $po_id
	 *
	 * @return AtumPOOrder[]
	 */
	public static function get_po_orders( $po_id ) {

		$args = array(
			'post_parent'    => $po_id,
			'post_type'      => static::POST_TYPE, // Return the appropriate order type depending on the class that is used for the call.
			'posts_per_page' => -1,
			'orderby'        => 'post_date',
			'order'          => 'ASC',
			'fields'         => 'ids',
		);

		$atum_po_order_posts = get_children( $args );
		$atum_po_orders      = [];

		foreach ( $atum_po_order_posts as $atum_po_order_id ) {
			$atum_order = AtumHelpers::get_atum_order_model( $atum_po_order_id, TRUE, static::POST_TYPE );

			if ( ! is_wp_error( $atum_order ) ) {
				$atum_po_orders[] = $atum_order;
			}
		}

		return $atum_po_orders;

	}

}
