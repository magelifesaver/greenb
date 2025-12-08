<?php
/**
 * PO Helpers methods
 *
 * @since       0.5.0
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumPO\Inc
 */

namespace AtumPO\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumListTables\AtumListTable;
use Atum\Components\AtumOrders\AtumComments;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Models\Interfaces\AtumProductInterface;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Supplier;
use AtumPO\Deliveries\Deliveries;
use AtumPO\Deliveries\Items\DeliveryItemProduct;
use AtumPO\Models\POExtended;


final class Helpers {

	/**
	 * Calculate the ROQ (Recommended Order Quantity) for the specified product
	 *
	 * @since 0.5.0
	 *
	 * @param \WC_Product|AtumProductInterface $product
	 *
	 * @return int|float
	 */
	public static function get_product_roq( $product ) {

		/**
		 * Formula:
		 *
		 * ROQ = ((Last Week Sales / 7) * Suppliers Lead Time in Days) – Incoming Stock
		 */

		$supplier_id = $product->get_supplier_id();

		if ( ! $supplier_id ) {
			return 0;
		}

		$supplier  = new Supplier( $supplier_id );
		$lead_time = absint( $supplier->lead_time );

		if ( $lead_time <= 0 ) {
			return 0;
		}

		$last_week_sales = self::get_product_last_week_sales( $product );

		if ( $last_week_sales <= 0 ) {
			return 0;
		}

		// The ROQ cannot be negative.
		return round( max( 0, ( ( $last_week_sales / 7 ) * $lead_time ) - AtumHelpers::get_product_inbound_stock( $product ) ), 2, PHP_ROUND_HALF_UP );

	}

	/**
	 * Get the last week's sales for the specified product
	 *
	 * @since 0.5.0
	 *
	 * @param \WC_Product $product
	 *
	 * @return int|float
	 */
	public static function get_product_last_week_sales( $product ) {

		$product_id      = $product->get_id();
		$cache_key       = AtumCache::get_cache_key( 'product_last_week_sales', [ $product_id ] );
		$last_week_sales = AtumCache::get_cache( $cache_key, ATUM_PO_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $last_week_sales;
		}

		$last_week_sales = WidgetHelpers::get_sales_stats( apply_filters( 'atum/purchase_orders_pro/get_product_last_week_sales_args', array(
			'types'           => [ 'sales' ],
			'products'        => [ $product_id ],
			'date_start'      => '1 week ago',
			'formatted_value' => FALSE,
		) ) );

		$last_week_sales = $last_week_sales['products'] ?? 0;

		AtumCache::set_cache( $cache_key, $last_week_sales, ATUM_PO_TEXT_DOMAIN );

		return $last_week_sales;

	}

	/**
	 * Check whether the current post is a PO
	 *
	 * @since 0.7.5
	 *
	 * @return bool
	 */
	public static function is_po_post() {

		global $post_type;

		$atum_order_id = NULL;

		if ( ! empty( $_REQUEST['atum_order_id'] ) && is_numeric( $_REQUEST['atum_order_id'] ) ) {
			$atum_order_id = absint( $_REQUEST['atum_order_id'] );
		}
		elseif ( ! empty( $_REQUEST['post_id'] ) && is_numeric( $_REQUEST['post_id'] ) ) {
			$atum_order_id = absint( $_REQUEST['post_id'] );
		}
		elseif ( ! empty( $_REQUEST['po_id'] ) && is_numeric( $_REQUEST['po_id'] ) ) {
			$atum_order_id = absint( $_REQUEST['po_id'] );
		}
		elseif ( ! empty( $_REQUEST['post'] ) && is_numeric( $_REQUEST['post'] ) ) {
			$atum_order_id = absint( $_REQUEST['post'] );
		}

		if (
			( $post_type && PurchaseOrders::POST_TYPE === $post_type ) ||
			( $atum_order_id && PurchaseOrders::POST_TYPE === get_post_type( $atum_order_id ) )
		) {
			return TRUE;
		}

		return FALSE;

	}

	/**
	 * Get the ID for the current PO
	 *
	 * @since 0.7.6
	 *
	 * @return false|int
	 */
	public static function get_po_id() {
		return ! empty( $_POST['atum_order_id'] ) ? absint( $_POST['atum_order_id'] ) : get_the_ID();
	}

	/**
	 * Check whether the taxes are enabled for POs.
	 *
	 * @since 0.9.3
	 *
	 * @return bool
	 */
	public static function are_po_taxes_enabled() {
		return 'yes' === AtumHelpers::get_option( 'po_enable_taxes', wc_tax_enabled() ? 'yes' : 'no' );
	}

	/**
	 * Check if the current PO may use taxes.
	 *
	 * @since 0.9.21
	 *
	 * @param POExtended|null $po
	 *
	 * @return bool
	 */
	public static function may_use_po_taxes( $po = NULL ) {

		if ( is_null( $po ) ) {

			if ( ! self::get_po_id() ) {
				return self::are_po_taxes_enabled();
			}
			else {
				$po = AtumHelpers::get_atum_order_model( self::get_po_id(), TRUE, PurchaseOrders::POST_TYPE );
			}

		}

		return $po->has_taxes_enabled;

	}

	/**
	 * Get notifications for the current user.
	 *
	 * @since 0.9.6
	 *
	 * @package PO Comments
	 *
	 * @param array $args [
	 *    The notification args.
	 *    @type int    $atum_order_id
	 *    @type string $status        all|read|unread
	 *    @type string $target        all|user
	 *    @type bool   $ids_only
	 * ]
	 *
	 * @return array
	 */
	public static function get_po_notifications( $args = [] ) {

		$atum_order_id = $args['atum_order_id'] ?? FALSE;
		$status        = $args['status'] ?? 'all';
		$target        = $args['target'] ?? 'all';
		$ids_only      = $args['ids_only'] ?? TRUE;

		$user = get_current_user_id();
		$args = array(
			'post_type' => PurchaseOrders::POST_TYPE,
			/*'type'    => AtumComments::NOTES_KEY,*/
		);

		if ( $atum_order_id ) {
			$args['post_id'] = $atum_order_id;
		}

		$atum_comments = AtumComments::get_instance();

		remove_filter( 'comments_clauses', array( $atum_comments, 'exclude_atum_order_notes' ) );
		do_action( 'atum/comments/disable_translations' );
		$comments = get_comments( $args );
		do_action( 'atum/comments/enable_translations' );
		add_filter( 'comments_clauses', array( $atum_comments, 'exclude_atum_order_notes' ) );

		$result = array();

		foreach ( $comments as $comment ) {

			/**
			 * Variable definition
			 *
			 * @var \WP_Comment $comment
			 */
			$mentions    = maybe_unserialize( get_comment_meta( $comment->comment_ID, 'po_notification', TRUE ) );
			$read        = maybe_unserialize( get_comment_meta( $comment->comment_ID, 'po_read_status', TRUE ) );
			$add_comment = FALSE;

			if ( 'all' === $status ) {

				if ( 'all' === $target || isset( $mentions[0] ) || isset( $mentions[ $user ] ) ) {
					$add_comment = TRUE;
				}

			}
			else {

				if ( 'all' === $target || isset( $mentions[0] ) || isset( $mentions[ $user ] ) ) {

					if (
						( 'read' === $status && isset( $read[ $user ] ) ) ||
						( 'unread' === $status && ! isset( $read[ $user ] ) )
					) {
						$add_comment = TRUE;
					}

				}

			}

			if ( $add_comment ) {

				$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ?: 'Y/m/d H:i:s';
				$result[]    = $ids_only ? $comment->comment_ID : [
					'id'             => $comment->comment_ID,
					'order_id'       => $comment->comment_post_ID,
					/* translators: the PO ID */
					'order_name'     => sprintf( __( 'PO #%s', ATUM_PO_TEXT_DOMAIN ), $comment->comment_post_ID ),
					'link'           => get_edit_post_link( $comment->comment_post_ID ),
					'content'        => $comment->comment_content,
					'author'         => $comment->comment_author,
					'raw_date'       => $comment->comment_date,
					'formatted_date' => date_i18n( $date_format, strtotime( $comment->comment_date ) ),
					'mention'        => ( isset( $mentions[0] ) && $mentions[0] ) || isset( $mentions[ $user ] ),
				];

			}

		}

		return $result;

	}

	/**
	 * Count notifications for the current user.
	 *
	 * @since 0.9.6
	 *
	 * @package PO Comments
	 *
	 * @param integer $atum_order_id
	 * @param string  $status        It can be 'all' or 'user'.
	 * @param string  $target
	 *
	 * @return integer
	 */
	public static function count_po_notifications( $atum_order_id, $status = 'all', $target = 'all' ) {

		$notifications = self::get_po_notifications( array(
			'atum_order_id' => $atum_order_id,
			'status'        => $status,
			'target'        => $target,
		) );

		return count( $notifications );

	}

	/**
	 * Get the list of users with read_order_notes capability.
	 *
	 * @since 0.9.6
	 *
	 * @return array
	 */
	public static function get_comments_users() {

		$user_results = AtumCache::get_transient( 'atum_comment_users' );

		if ( empty( $user_results ) ) {

			$user_results = array();

			$all_roles = new \WP_Roles();
			$cap_roles = array();

			foreach ( $all_roles->role_objects as $role ) {
				/**
				 * Variable definition
				 *
				 * @var \WP_Role $role
				 */
				if ( $role->has_cap( 'atum_read_order_notes' ) && $role->has_cap( 'atum_read_purchase_order' ) ) {
					$cap_roles[] = $role->name;
				}
			}

			$users = new \WP_User_Query( [
				'orderby'  => 'user_login',
				'number'   => - 1,
				'role__in' => $cap_roles,
				'fields'   => array( 'ID', 'user_login' ),
			] );

			if ( ! empty( $users->get_results() ) ) {

				foreach ( $users->get_results() as $user ) {

					$user_results[ $user->ID ] = array(
						'name'  => $user->user_login,
						'thumb' => get_avatar( $user->ID ),
					);

				}

			}

			AtumCache::set_transient( 'atum_comment_users', $user_results, DAY_IN_SECONDS * 3, TRUE );

		}

		return $user_results;

	}

	/**
	 * Get an array of the available PO PDF templates
	 *
	 * @since 0.9.7
	 *
	 * @return array[]
	 */
	public static function get_po_pdf_templates() {

		return apply_filters( 'atum/purchase_orders_pro/po_pdf_templates', array(
			'default'   => array(
				'label'   => __( 'Default', ATUM_PO_TEXT_DOMAIN ),
				'img_url' => ATUM_PO_URL . 'views/pdf-templates/default/images/thumb.jpg',
			),
			'template1' => array(
				'label'   => __( 'Template 1', ATUM_PO_TEXT_DOMAIN ),
				'img_url' => ATUM_PO_URL . 'views/pdf-templates/template1/images/thumb.jpg',
			),
			'template2' => array(
				'label'   => __( 'Template 2', ATUM_PO_TEXT_DOMAIN ),
				'img_url' => ATUM_PO_URL . 'views/pdf-templates/template2/images/thumb.jpg',
			),
		) );

	}

	/**
	 * Get an array of the available PO email templates
	 *
	 * @since 0.9.7
	 *
	 * @return array[]
	 */
	public static function get_po_email_templates() {

		return apply_filters( 'atum/purchase_orders_pro/po_email_templates', array(
			'default'   => array(
				'label'   => __( 'Default', ATUM_PO_TEXT_DOMAIN ),
				'img_url' => ATUM_PO_URL . 'views/email-templates/default/images/thumb.svg',
			),
			'template1' => array(
				'label'   => __( 'Template 1', ATUM_PO_TEXT_DOMAIN ),
				'img_url' => ATUM_PO_URL . 'views/email-templates/template1/images/thumb.svg',
			),
			'template2' => array(
				'label'   => __( 'Template 2', ATUM_PO_TEXT_DOMAIN ),
				'img_url' => ATUM_PO_URL . 'views/email-templates/template2/images/thumb.svg',
			),
		) );

	}

	/**
	 * Get details of a status.
	 *
	 * @since 0.9.7
	 *
	 * @param string $status
	 *
	 * @return array
	 */
	public static function get_status_details( $status = '' ) {

		$status_colors = Globals::get_status_colors();
		$statuses      = Globals::get_statuses();
		$status_color  = $status_colors[ $status ] ?? 'transparent';
		$status_label  = $statuses[ $status ] ?? __( 'Unknown', ATUM_PO_TEXT_DOMAIN );

		return array(
			'status' => $status,
			'label'  => $status_label,
			'color'  => $status_color,
		);
	}

	/**
	 * Returns an array with the POs filtered by the atts array
	 *
	 * @since 0.9.12
	 *
	 * @param array|string $atts {
	 *      Optional. Filters for the orders' query.
	 *
	 *      @type array|string  $status            Order status(es).
	 *      @type array         $orders_in         Array of order's IDs we want to get.
	 *      @type int           $per_page          Max number of orders (-1 gets all).
	 *      @type int           $paged             The number of page.
	 *      @type string        $meta_key          Key of the meta field to filter/order (depending on orderby value).
	 *      @type mixed         $meta_value        Value of the meta field to filter/order(depending on orderby value).
	 *      @type string        $meta_type         Meta key type. Default value is 'CHAR'.
	 *      @type string        $meta_compare      Operator to test the meta value when filtering (See possible values: https://codex.wordpress.org/Class_Reference/WP_Meta_Query ).
	 *      @type array         $meta_query        Part of the query is parsed by \WP_Meta_Query.
	 *      @type string        $order             ASC/DESC, default to DESC.
	 *      @type string        $orderby           Field used to sort results (see WP_QUERY). Default to date (post_date).
	 *      @type int           $date_start        If it has value, filters the orders between this and the $order_date_end (must be a string format convertible with strtotime).
	 *      @type int           $date_end          Requires $date_start. If it has value, filters the orders completed/processed before this date (must be a string format convertible with strtotime). Default: Now.
	 *      @type string        $m                 Filter by creation date's YearMonth.
	 *      @type string        $s                 The search term.
	 *      @type string        $search_column     Whether to search in a specific column.
	 *      @type string        $fields            If empty will return all the order posts. For returning only IDs the value must be 'ids'.
	 * }
	 *
	 * @return \WC_Order|array
	 */
	public static function get_pos( $atts = array() ) {

		$atts = (array) apply_filters( 'atum/purchase_orders_pro/get_pos/params', wp_parse_args( $atts, array(
			'status'        => '',
			'orders_in'     => '',
			'per_page'      => - 1,
			'paged'         => 1,
			'meta_key'      => '',
			'meta_value'    => '',
			'meta_type'     => '',
			'meta_compare'  => '',
			'meta_query'    => [],
			'order'         => '',
			'orderby'       => '',
			'date_start'    => '',
			'date_end'      => '',
			'm'             => '',
			's'             => '',
			'search_column' => '',
			'fields'        => '',
		) ) );

		$cache_key = AtumCache::get_cache_key( 'pos_list', $atts );
		$pos       = AtumCache::get_cache( $cache_key, ATUM_PO_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $pos;
		}

		/**
		 * Extract params
		 *
		 * @var array|string  $status
		 * @var array|string  $orders_in
		 * @var int           $per_page
		 * @var int           $paged
		 * @var string        $meta_key
		 * @var mixed         $meta_value
		 * @var string        $meta_type
		 * @var string        $meta_compare
		 * @var array         $meta_query
		 * @var string        $order
		 * @var string        $orderby
		 * @var string        $date_start
		 * @var string        $date_end
		 * @var string        $s
		 * @var string        $search_column
		 * @var string        $fields
		 */
		extract( $atts );

		// WP_Query arguments.
		$args = array(
			'post_type' => PurchaseOrders::POST_TYPE,
		);

		// PO Status.
		if ( ! empty( $status ) ) {

			$valid_po_statuses = array();
			$po_statuses       = array_keys( Globals::get_statuses() );

			// Validate statuses.
			foreach ( (array) $status as $os ) {
				if ( in_array( $os, $po_statuses ) ) {
					$valid_po_statuses[] = $os;
				}
			}

			$args['post_status'] = ! empty( $valid_po_statuses ) ? $valid_po_statuses : $po_statuses;

		}
		// Unkonwn statuses?
		else {
			$args['post_status'] = 'any';
		}

		// Selected POs.
		if ( $orders_in ) {

			if ( ! is_array( $orders_in ) ) {
				$orders_in = explode( ',', $orders_in );
			}

			$args['post__in'] = array_map( 'absint', $orders_in );
		}

		$args['posts_per_page'] = intval( $per_page );
		$args['paged']          = absint( $paged );

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		if ( ! empty( $meta_key ) ) {
			$args['meta_key'] = $meta_key;
		}

		if ( ! empty( $order ) ) {
			$args['order'] = in_array( strtoupper( $order ), [ 'ASC', 'DESC' ] ) ? $order : 'DESC';
		}

		if ( ! empty( $orderby ) ) {
			$args['orderby'] = $orderby;
		}

		// Filter by date.
		if ( $date_start ) {

			$args['date_query'][] = array(
				'after'     => $date_start,
				'before'    => $date_end ?: 'now',
				'inclusive' => TRUE,
			);

		}

		// Search.
		if ( ! empty( $search_column ) ) {
			$args['search_column'] = esc_attr( stripslashes( $search_column ) );
		}

		if ( ! empty( $s ) ) {
			$args['s'] = sanitize_text_field( urldecode( stripslashes( $s ) ) );
		}

		// Return only IDs.
		if ( $fields ) {
			$args['fields'] = $fields;
		}

		$pos   = array();
		$query = new \WP_Query( $args );

		if ( $query->post_count > 0 ) {

			if ( $fields ) {
				$pos = $query->posts;
			}
			else {
				foreach ( $query->posts as $post ) {
					$pos[] = AtumHelpers::get_atum_order_model( $post->ID, FALSE, PurchaseOrders::POST_TYPE );
				}
			}

		}

		AtumCache::set_cache( $cache_key, $pos, ATUM_PO_TEXT_DOMAIN );

		return $pos;

	}

	/**
	 * Get the number of Late POs.
	 *
	 * @since 0.9.14
	 *
	 * @return int
	 */
	public static function get_late_pos_count() {

		global $wpdb;

		$due_soon_days = absint( AtumHelpers::get_option( 'po_list_due_soon_days', 3 ) );
		$due_statuses  = Globals::get_due_statuses();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*) FROM $wpdb->posts p
			LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND meta_key = '_date_expected')
			WHERE meta_value != '' AND meta_value < %s AND meta_value <= NOW()  
			AND post_status IN ('" . implode( "','", $due_statuses ) . "')
			ORDER BY meta_value ASC
		", date_i18n( 'Y-m-d H:i:s', strtotime( "-$due_soon_days days" ) ) ) );
		// phpcs:enable

	}

	/**
	 * Change any PO status if it's allowed
	 *
	 * @since 0.9.15
	 *
	 * @param POExtended $po
	 * @param string     $new_status
	 *
	 * @return bool True if the status was changed or false if the change was not allowed.
	 */
	public static function maybe_change_po_status( $po, $new_status ) {

		$current_status = $po->status;

		// No change needed.
		if ( $new_status === $current_status ) {
			return TRUE;
		}

		$status_flow                = Globals::get_status_flow();
		$po_status_flow_restriction = self::get_po_status_flow_restriction( $po->get_id() );

		if (
			'yes' === $po_status_flow_restriction && ! empty( $status_flow ) &&
			( ! isset( $status_flow[ $current_status ] ) || ! in_array( $new_status, $status_flow[ $current_status ] ) )
		) {
			return FALSE;
		}

		// No matter the status flow restriction is disabled, transitioning from a recurring to non-recurring and vice-versa is not allowed.
		$returning_statuses = [ 'atum_returning', 'atum_returned' ];
		if (
			'no' === $po_status_flow_restriction && (
				( $po->is_returning() && ! in_array( $new_status, array_merge( $returning_statuses, [ 'trash' ] ) ) ) ||
				( ! $po->is_returning() && in_array( $new_status, $returning_statuses ) )
			)
		) {
			return FALSE;
		}

		if ( 'trash' === $new_status ) {
			wp_trash_post( $po->get_id() );
		}
		else {
			do_action( 'atum/purchase_orders_pro/before_po_set_status', $po, $current_status, $new_status );

			$po->set_status( $new_status );
			$po->save();
		}

		return TRUE;

	}

	/**
	 * Get a PO item's already in total units accross deliveries.
	 *
	 * @since 0.9.23
	 *
	 * @param int  $po_item_id          The PO item's ID that we want to retrieve the count.
	 * @param bool $check_stock_changed Whether to count only items that were already added to stock.
	 *
	 * @return int|float
	 */
	public static function get_po_item_already_in_total( $po_item_id, $check_stock_changed = TRUE ) {

		$cache_key  = AtumCache::get_cache_key( 'get_po_item_already_in_total', [ $po_item_id, $check_stock_changed ] );
		$already_in = AtumCache::get_cache( $cache_key, ATUM_PO_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $already_in;
		}

		global $wpdb;

		$atum_order_items_table      = $wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE;
		$atum_order_items_meta_table = $wpdb->prefix . AtumOrderPostType::ORDER_ITEM_META_TABLE;

		$joins = array(
			"LEFT JOIN $atum_order_items_meta_table itm2 ON (itm1.order_item_id = itm2.order_item_id AND itm2.meta_key = '_po_item_id')",
			"LEFT JOIN $atum_order_items_table it ON (it.order_item_id = itm1.order_item_id)",
		);

		$where = array(
			"itm1.meta_key = '_qty'",
			$wpdb->prepare( 'itm2.meta_value = %d', $po_item_id ),
			"it.order_item_type = 'delivery_item'",
		);

		if ( $check_stock_changed ) {
			$joins[] = "LEFT JOIN $atum_order_items_meta_table itm3 ON (itm1.order_item_id = itm3.order_item_id AND itm3.meta_key = '_stock_changed')";
			$where[] = "itm3.meta_value = 'yes'";
		}

		// phpcs:disable WordPress.DB.PreparedSQL
		$already_in = $wpdb->get_var(
			"SELECT SUM(itm1.meta_value) FROM $atum_order_items_meta_table itm1 \n" .
			implode( "\n", $joins ) . "\n" .
			'WHERE ' . implode( ' AND ', $where )
		);
		// phpcs:enable

		AtumCache::set_cache( $cache_key, $already_in ?? 0, ATUM_PO_TEXT_DOMAIN );

		return $already_in ?? 0;

	}

	/**
	 * Retrieve the next custom PO number to be used
	 *
	 * @since 1.0.1
	 *
	 * @param string $pattern
	 * @param int    $counter
	 * @param int    $padding_zeros
	 *
	 * @return string
	 */
	public static function get_next_po_number( $pattern = NULL, $counter = NULL, $padding_zeros = NULL ) {

		$pattern       = $pattern ?? AtumHelpers::get_option( 'po_numbering_custom_pattern', '' );
		$counter       = $counter ?? AtumHelpers::get_option( 'po_numbering_custom_counter', 1 );
		$padding_zeros = $padding_zeros ?? AtumHelpers::get_option( 'po_numbering_custom_zeros', 4 );

		$next_po_number = sanitize_text_field( $pattern );
		$next_po_number = str_replace( '{year}', date_i18n( 'Y' ), $next_po_number );
		$next_po_number = str_replace( '{counter}', str_pad( absint( $counter ), absint( $padding_zeros ), '0', STR_PAD_LEFT ), $next_po_number );

		preg_match( '#\{(.*?)\}#', $next_po_number, $match );
		$date_merge_tag = $match[1] ?? '';

		if ( $date_merge_tag ) {

			$date_format = explode( ':', $date_merge_tag )[1] ?? '';

			if ( $date_format ) {
				$next_po_number = str_replace( $match[0], date_i18n( $date_format ), $next_po_number );
			}

		}

		return $next_po_number;

	}

	/**
	 * Check if a PO number already exists for any PO and return the PO ids that have it
	 *
	 * @since 1.0.3
	 *
	 * @param string $po_number
	 * @param int    $exclude_id
	 *
	 * @return int[]
	 */
	public static function find_po_number( $po_number, $exclude_id = NULL ) {

		global $wpdb;

		$exclude_sql = $exclude_id ? $wpdb->prepare( ' AND p.ID != %d', $exclude_id ) : '';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_col( $wpdb->prepare( "
			SELECT ID FROM $wpdb->posts p
			LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND meta_key = '_number')
			WHERE p.post_type = %s AND pm.meta_value = %s $exclude_sql
		", PurchaseOrders::POST_TYPE, $po_number ) );
		// phpcs:enable

	}

	/**
	 * Get the status flow restriction option for a specific PO
	 *
	 * @since 1.0.3
	 *
	 * @param int $po_id
	 *
	 * @return false|string
	 */
	public static function get_po_status_flow_restriction( $po_id ) {
		return get_post_meta( $po_id, '_po_status_flow_restriction', TRUE ) ?: AtumHelpers::get_option( 'po_status_flow_restriction', 'yes' );
	}

	/**
	 * Calculate supplier product gross profit within PO Pro settings.
	 *
	 * @since 1.0.4
	 *
	 * @param string                           $gross_profit
	 * @param \WC_Product|AtumProductInterface $product
	 * @param float                            $price
	 * @param float                            $purchase_price
	 * @param Supplier                         $supplier
	 */
	public static function calculate_supplier_gross_profit( $gross_profit, $product, $price, $purchase_price, $supplier ) {

		$pur_tax_rates    = [];
		$wc_include_tax   = wc_prices_include_tax();
		$po_taxes_enabled = AtumHelpers::get_option( 'po_enable_taxes', wc_tax_enabled() ? 'yes' : 'no' );
		$pp_include_tax   = 'yes' === AtumHelpers::get_option( 'po_purchase_price_including_taxes', $wc_include_tax ? 'yes' : 'no' );

		// Exclude rates if prices includes them.
		if ( $wc_include_tax ) {
			$base_tax_rates = \WC_Tax::get_base_tax_rates( $product->get_tax_class() );
			$base_reg_taxes = \WC_Tax::calc_tax( $price, $base_tax_rates, TRUE );
			$price          = round( $price - array_sum( $base_reg_taxes ), absint( get_option( 'woocommerce_price_num_decimals' ) ), PHP_ROUND_HALF_UP );
		}

		// Exclude taxes from purchase prices (if needed).
		if ( $po_taxes_enabled && $pp_include_tax ) {

			if ( $supplier instanceof Supplier && $supplier->tax_rate ) {

				$pur_tax_rates = array(
					array(
						'rate'     => $supplier->tax_rate,
						'label'    => 'SUPPL',
						'shipping' => 'no',
						'compound' => 'no',
					),
				);

			}
			elseif ( $wc_include_tax && 'yes' === AtumHelpers::get_option( 'po_use_system_taxes', 'yes' ) ) {
				$pur_tax_rates = $base_tax_rates;
			}

			if ( ! empty( $pur_tax_rates ) ) {
				$base_pur_taxes = \WC_Tax::calc_tax( $purchase_price, $pur_tax_rates, TRUE );
				$purchase_price = round( $purchase_price - array_sum( $base_pur_taxes ), absint( get_option( 'woocommerce_price_num_decimals' ) ), PHP_ROUND_HALF_UP );
			}

		}

		if ( $purchase_price > 0 && $price > 0 ) {

			$gross_profit_value      = wp_strip_all_tags( wc_price( $price - $purchase_price ) );
			$gross_profit_percentage = wc_round_discount( ( 100 - ( ( $purchase_price * 100 ) / $price ) ), 2 );
			$profit_margin           = (float) AtumHelpers::get_option( 'profit_margin', 50 );
			$profit_margin_class     = $gross_profit_percentage < $profit_margin ? 'cell-red' : 'cell-green';

			if ( 'percentage' === AtumHelpers::get_option( 'gross_profit', 'percentage' ) ) {
				$gross_profit = '<span class="tips ' . $profit_margin_class . '" data-tip="' . $gross_profit_value . '">' . $gross_profit_percentage . '%</span>';
			}
			else {
				$gross_profit = '<span class="tips ' . $profit_margin_class . '" data-tip="' . $gross_profit_percentage . '%">' . $gross_profit_value . '</span>';
			}

		}
		else {
			$gross_profit = AtumListTable::EMPTY_COL;
		}

		return apply_filters( 'atum/purchase_orders_pro/calculate_supplier_gross_profit', $gross_profit, $product );

	}

	/**
	 * Get the items and quantities for any specific PO that were added to deliveries
	 *
	 * @since 1.1.2
	 *
	 * @param int $po_id The original PO ID.
	 *
	 * @return array|\WP_Error
	 */
	public static function get_delivered_po_items( $po_id ) {

		$cache_key      = AtumCache::get_cache_key( 'delivered_po_items', [ $po_id ] );
		$returned_items = AtumCache::get_cache( $cache_key, ATUM_PO_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $returned_items;
		}

		$deliveries = Deliveries::get_po_orders( $po_id );

		if ( empty( $deliveries ) ) {
			$delivered_items = new \WP_Error( 'po_no_deliveries', __( 'There are no deliveries on this PO yet.', ATUM_PO_TEXT_DOMAIN ) );
		}
		else {

			$delivered_items = [];

			foreach ( $deliveries as $delivery ) {

				$delivery_items = $delivery->get_items( array_values( apply_filters( 'atum/purchase_orders_pro/delivery/item_group_to_type', [ 'delivery_item' ] ) ) ); // Multi-Inventory support.

				if ( empty( $delivery_items ) ) {
					continue;
				}

				foreach ( $delivery_items as $item ) {

					if ( ! $item instanceof DeliveryItemProduct ) {
						continue;
					}

					$product_id = $item->get_product_id();

					if ( isset( $delivered_items[ $product_id ] ) ) {
						$delivered_items[ $product_id ] += $item->get_quantity();
					}
					else {
						$delivered_items[ $product_id ] = $item->get_quantity();
					}

				}

				$delivered_items = apply_filters( 'atum/purchase_orders_pro/delivered_po_items', $delivered_items, $delivery_items, $delivery );

			}

			if ( empty( $delivered_items ) ) {
				$delivered_items = new \WP_Error( 'po_items_not_delivered', __( 'There are no delivery items on this PO yet.', ATUM_PO_TEXT_DOMAIN ) );
			}

		}

		AtumCache::set_cache( $cache_key, $delivered_items, ATUM_PO_TEXT_DOMAIN );

		return $delivered_items;

	}

	/**
	 * Return the minimum quantity in PO item for min input attribute.
	 *
	 * @since 1.1.7
	 *
	 * @return float|int
	 */
	public static function get_minimum_quantity_to_add() {
		$stock_decimals = AtumHelpers::get_option( 'stock_quantity_decimals', 0 );

		return pow( 10, $stock_decimals * -1 );
	}

}
