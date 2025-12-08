<?php
/**
 * POs List Table's class
 *
 * @package         AtumPO\ListTables
 * @subpackage      Lists
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.12
 */

namespace AtumPO\ListTables\Lists;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumHelpGuide;
use Atum\Components\AtumListTables\AtumListTable;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Orders\SearchOrdersByColumn;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Settings\Settings as AtumSettings;
use Atum\Suppliers\Supplier;
use AtumPO\Deliveries\Items\DeliveryItemProduct;
use AtumPO\Deliveries\Models\DeliveryItemInventory;
use AtumPO\Inc\Globals;
use AtumPO\Inc\Helpers;
use AtumPO\Models\POExtended;
use AtumPO\Deliveries\Deliveries;


class ListTable extends AtumListTable {

	/**
	 * The list ID
	 *
	 * @var string
	 */
	protected $id = 'purchase-orders';

	/**
	 * The post type used to build the table (WooCommerce product)
	 *
	 * @var string
	 */
	protected $post_type = 'atum_purchase_order';

	/**
	 * Current PO being listed
	 *
	 * @var POExtended
	 */
	protected $list_item;

	/**
	 * The columns hidden by default
	 *
	 * @var array
	 */
	protected static $default_hidden_columns = [ 'id' ];

	/**
	 * What columns are searchable
	 *
	 * @var array
	 */
	protected $searchable_columns = array(
		'string'  => array(
			'_number',
			'_date_created',
			'_status',
			'_date_expected',
			'post_author',
			'_supplier',
			'_total',
		),
		'numeric' => array(),
	);

	/**
	 * Counters for views
	 *
	 * @var array
	 */
	protected $count_views = array(
		'count_all'                  => 0,
		'count_atum_pending'         => 0,
		'count_atum_new'             => 0,
		'count_atum_approval'        => 0,
		'count_atum_approved'        => 0,
		'count_atum_ordered'         => 0,
		'count_atum_vendor_received' => 0,
		'count_atum_onthewayin'      => 0,
		'count_atum_receiving'       => 0,
		'count_atum_quality_check'   => 0,
		'count_atum_added'           => 0,
		'count_atum_received'        => 0,
		'count_atum_partially_added' => 0,
		'count_atum_received'        => 0,
		'count_atum_cancelled'       => 0,
		'count_atum_returning'       => 0,
		'count_atum_returned'        => 0,
		'count_trash'                => 0,
		'count_unknown'              => 0,
	);

	/**
	 * Columns that allow totalizers with their totals
	 *
	 * @var array
	 */
	protected $totalizers = array(
		'_total' => 0,
	);

	/**
	 * All the statuses allowed for POs
	 *
	 * @var array
	 */
	protected $statuses = [];

	/**
	 * The colors for every status
	 *
	 * @var array
	 */
	protected $status_colors = [];

	/**
	 * The number of days after a PO will be considered as "Due soon"
	 *
	 * @var int
	 */
	protected $due_soon_days = 3;

	/**
	 * Hold any PO with an unknow status
	 *
	 * @var int[]
	 */
	protected $unknown_status_pos = [];

	/**
	 * The List Table help guide
	 *
	 * @var string
	 */
	protected $help_guide = 'atum_po_list_table';

	/**
	 * Whether to show or not the unmanaged counters
	 *
	 * @var bool
	 */
	protected $show_unmanaged_counters = FALSE;

	/**
	 * ListTable constructor
	 *
	 * @param array|string $args {
	 *   Array or string of arguments.
	 *
	 *   @type bool  $show_cb         Optional. Whether to show the row selector checkbox as first table column.
	 *   @type bool  $show_controlled Optional. Whether to show items controlled by ATUM or not.
	 *   @type int   $per_page        Optional. The number of posts to show per page (-1 for no pagination).
	 *   @type array $selected        Optional. The posts selected on the list table.
	 *   @type array $excluded        Optional. The posts excluded from the list table.
	 * }
	 */
	public function __construct( $args ) {

		// Prepare the table columns.
		self::$table_columns = self::get_table_columns();

		// Get the row/bulk actions.
		self::$row_actions = self::get_row_actions();

		$all_statuses = Globals::get_statuses();

		// If the require requisitioner is enabled.
		if ( AtumHelpers::get_option( 'po_required_requisition', 'no' ) === 'yes' ) {
			/* translators: the new status label */
			$all_statuses['atum_new'] = sprintf( __( '%s (requisition)', ATUM_PO_TEXT_DOMAIN ), $all_statuses['atum_new'] );
		}
		else {

			// Exclude all the requisitioner statuses.
			$requisitioner_statuses = Globals::get_requisitioner_statuses();
			foreach ( $requisitioner_statuses as $requisitioner_status ) {
				unset( $all_statuses[ $requisitioner_status ] );
			}

		}

		// Include the ID in the default PO search.
		add_filter( 'post_search_columns', array( $this, 'post_search_columns' ), 10, 3 );
		add_filter( 'atum/list_table/post_search_columns', array( $this, 'post_search_columns' ), 10, 3 );

		$this->statuses      = $all_statuses;
		$this->status_colors = Globals::get_status_colors();
		$this->due_soon_days = absint( AtumHelpers::get_option( 'po_list_due_soon_days', 3 ) );

		parent::__construct( $args );

		// Overwrite the searchable columns added by the parent constructor.
		if ( ! empty( $this->searchable_columns ) ) {
			$this->searchable_columns = (array) apply_filters( 'atum/purchase_orders_pro/default_serchable_columns', $this->searchable_columns );
		}

		$this->per_page = $args['per_page'] ?? AtumHelpers::get_option( 'po_list_posts_per_page', AtumSettings::DEFAULT_POSTS_PER_PAGE );

		// Hide columns' ids for POs list table.
		add_filter( 'atum/list_table/display_column_id', '__return_false' );

		// Fix for the POs list table ordering.
		add_filter( 'atum/list_table/meta_key_orderby', array( $this, 'po_meta_key_orderby' ), 10, 3 );

	}

	/**
	 * Prepare the table columns for the POs list table
	 *
	 * @since 0.9.12
	 *
	 * @param bool $force_defaults
	 *
	 * @return array
	 */
	public static function get_table_columns( $force_defaults = FALSE ) {

		// NAMING CONVENTION: The column names starting by underscore (_) are based on meta keys (the name must match the meta key name),
		// the column names starting with "calc_" are calculated fields and the rest are WP's standard fields
		// *** Following this convention is necessary for column sorting functionality ***!
		$table_columns = array(
			'_number'             => __( 'PO', ATUM_PO_TEXT_DOMAIN ),
			'calc_preview'        => '<span class="atum-icon atmi-eye tips" data-bs-placement="bottom" data-tip="' . esc_attr__( 'Preview PO', ATUM_PO_TEXT_DOMAIN ) . '"></span>',
			'_date_created'       => __( 'Date Created', ATUM_PO_TEXT_DOMAIN ),
			'post_author'         => __( 'Created By', ATUM_PO_TEXT_DOMAIN ),
			'_status'             => __( 'Status', ATUM_PO_TEXT_DOMAIN ),
			'_supplier'           => __( 'Supplier', ATUM_PO_TEXT_DOMAIN ),
			'_date_expected'      => __( 'Date Expected', ATUM_PO_TEXT_DOMAIN ),
			'calc_added_to_stock' => '<span class="atum-icon atmi-highlight tips" data-bs-placement="bottom" data-tip="' . esc_attr__( 'Added to Stock', ATUM_PO_TEXT_DOMAIN ) . '"></span>',
			'_total'              => __( 'Total', ATUM_PO_TEXT_DOMAIN ),
			'calc_actions'        => __( 'Actions', ATUM_PO_TEXT_DOMAIN ),
		);

		return (array) apply_filters( 'atum/purchase_orders_pro/list_table/table_columns', $table_columns );

	}

	/**
	 * Returns the row actions list
	 *
	 * @since 0.9.12
	 *
	 * @return mixed|void
	 */
	public static function get_row_actions() {

		$statuses     = array_keys( Globals::get_statuses() );
		$due_statuses = Globals::get_due_statuses();

		if ( ! empty( $_REQUEST['view'] ) && 'trash' === $_REQUEST['view'] ) {

			$row_actions = array(
				array(
					'name'  => 'poUnarchive',
					'icon'  => 'atmi-undo',
					'label' => esc_html__( 'Unarchive', ATUM_PO_TEXT_DOMAIN ),
				),
				array(
					'name'  => 'poForceDelete',
					'icon'  => 'atmi-trash',
					'label' => esc_html__( 'Delete Permanently', ATUM_PO_TEXT_DOMAIN ),
				),
			);

		}
		else {

			$row_actions = array(
				array(
					'name'  => 'markPODraft',
					'icon'  => 'atmi-checkmark-circle',
					'label' => esc_html__( 'Mark as Draft', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_pending',
						'status_label' => esc_attr__( 'Draft', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPONew',
					'icon'  => 'atmi-checkmark-circle',
					'label' => esc_html__( 'Mark as New', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_new',
						'status_label' => esc_attr__( 'New', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPOApproval',
					'icon'  => 'atmi-checkmark-circle',
					'label' => __( 'Submit for Approval', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_approval',
						'status_label' => esc_attr__( 'Awaiting Approval', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPOApproved',
					'icon'  => 'atmi-checkmark-circle',
					'label' => __( 'Mark as New (Approved)', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_approved',
						'status_label' => esc_attr__( 'New (Approved)', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPOSent',
					'icon'  => 'atmi-checkmark-circle',
					'label' => __( 'Mark as Sent', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_ordered',
						'status_label' => esc_attr__( 'Sent', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPOReceived',
					'icon'  => 'atmi-checkmark-circle',
					'label' => __( 'Mark as Received by Vendor', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_vendor_received',
						'status_label' => esc_attr__( 'Received by Vendor', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPOOnthewayin',
					'icon'  => 'atmi-checkmark-circle',
					'label' => __( 'Mark as On the Way In', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_onthewayin',
						'status_label' => esc_attr__( 'On the Way In', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPOReceiving',
					'icon'  => 'atmi-checkmark-circle',
					'label' => __( 'Mark as Receiving', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_receiving',
						'status_label' => esc_attr__( 'Receiving', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPOPartiallyReceiving',
					'icon'  => 'atmi-checkmark-circle',
					'label' => __( 'Mark as Partially Receiving', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_part_receiving',
						'status_label' => esc_attr__( 'Partially Receiving', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPOQualityCheck',
					'icon'  => 'atmi-checkmark-circle',
					'label' => __( 'Mark as Quality Check', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_quality_check',
						'status_label' => esc_attr__( 'Quality Check', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPOAdded',
					'icon'  => 'atmi-checkmark-circle',
					'label' => __( 'Mark as Added', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_added',
						'status_label' => esc_attr__( 'Added', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPOPartiallyAdded',
					'icon'  => 'atmi-checkmark-circle',
					'label' => __( 'Mark as Partially Added', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_partially_added',
						'status_label' => esc_attr__( 'Partially Added', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPOCompleted',
					'icon'  => 'atmi-checkmark-circle',
					'label' => __( 'Mark as Completed', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_received',
						'status_label' => esc_attr__( 'Completed', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPOCancelled',
					'icon'  => 'atmi-checkmark-circle',
					'label' => __( 'Mark as Cancelled', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_cancelled',
						'status_label' => esc_attr__( 'Cancelled', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'markPOReturned',
					'icon'  => 'atmi-checkmark-circle',
					'label' => __( 'Mark as Returned', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'atum_returned',
						'status_label' => esc_attr__( 'Returned', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'        => 'createReturningPO',
					'icon'        => 'atmi-undo',
					'label'       => __( 'Create Returning PO', ATUM_PO_TEXT_DOMAIN ),
					'conditional' => [
						'data' => [
							'key'   => 'status',
							'value' => array_diff( $statuses, $due_statuses, [
								'atum_returning',
								'atum_returned',
								'atum_cancelled',
							] ),
						],
					],
				),
				array(
					'name'  => 'markPOArchived',
					'icon'  => 'atmi-trash',
					'label' => __( 'Archive', ATUM_PO_TEXT_DOMAIN ),
					'data'  => [
						'status'       => 'trash',
						'status_label' => esc_attr__( 'Archived', ATUM_PO_TEXT_DOMAIN ),
					],
				),
				array(
					'name'  => 'poPrint',
					'icon'  => 'atmi-printer',
					'label' => __( 'Print', ATUM_PO_TEXT_DOMAIN ),
				),
				array(
					'name'  => 'poClone',
					'icon'  => 'atmi-duplicate',
					'label' => __( 'Clone', ATUM_PO_TEXT_DOMAIN ),
				),
			);

			if ( 'yes' !== AtumHelpers::get_option( 'po_required_requisition', 'no' ) ) {

				$mark_approval = key( wp_list_filter( $row_actions, [ 'name' => 'markPOApproval' ] ) );
				$mark_approved = key( wp_list_filter( $row_actions, [ 'name' => 'markPOApproved' ] ) );
				unset( $row_actions[ $mark_approval ], $row_actions[ $mark_approved ] );
				$row_actions = array_values( $row_actions ); // Restore indexes to avoid sending the array as an object to JS.

				// TODO: WHAT ABOUT THE NEW (REQUISITION)?

			}

			// Add the conditional clauses for the status flow restrictions.
			$status_flow                   = Globals::get_status_flow();
			$is_status_restriction_enabled = 'yes' === AtumHelpers::get_option( 'po_status_flow_restriction', 'yes' );

			foreach ( $row_actions as &$row_action ) {

				// Only for status change actions.
				if ( ! empty( $row_action['data']['status'] ) && ! empty( $status_flow[ $row_action['data']['status'] ] ) ) {

					if ( $is_status_restriction_enabled ) {
						$value = $status_flow[ $row_action['data']['status'] ];
					}
					// NOTE: No matter whether the status flow restriction is enabled, the user cannot switch a returning PO to non-returning or vice-versa.
					else {

						switch ( $row_action['name'] ) {
							case 'markPOReturned':
								$value = $status_flow[ $row_action['data']['status'] ];
						        break;

							default:
								$value = array_diff( $statuses, [ 'atum_returning', 'atum_returned' ] );
								break;
						}

					}

					$row_action['conditional'] = array(
						'data' => array(
							'key'   => 'status',
							'value' => $value,
						),
					);

				}

			}

		}

		return apply_filters( 'atum/purchase_orders_pro/list_table/row_actions', $row_actions );

	}

	/**
	 * Prepare the table data
	 *
	 * @since 0.9.12
	 */
	public function prepare_items() {

		$args = [];
		$view = 'all';

		// Check if there are POs with unknonw statuses.
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$this->unknown_status_pos = $wpdb->get_col( $wpdb->prepare( "
			SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_status NOT IN (
			    '" . implode( "','", array_merge( array_keys( $this->statuses ), [ 'auto-draft' ] ) ) . "'
		    )
		", PurchaseOrders::POST_TYPE ) );
		// phpcs:enable

		if ( ! empty( $_REQUEST['view'] ) && 'unknown' === $_REQUEST['view'] ) {
			$view              = $_REQUEST['view'];
			$args['status']    = '';
			$args['orders_in'] = $this->unknown_status_pos;
		}
		elseif ( empty( $_REQUEST['view'] ) || 'all' === $_REQUEST['view'] || ! array_key_exists( $_REQUEST['view'], $this->statuses ) ) {
			// Do not show the archived POs within the "All" view.
			$args['status'] = array_diff( array_keys( $this->statuses ), [ 'trash' ] );
		}
		else {
			$args['status'] = $view = $_REQUEST['view'];
		}

		// Pagination.
		$args['per_page'] = $this->per_page;
		$args['paged']    = $_REQUEST['paged'] ?? 1;

		// Filter by supplier.
		if ( ! empty( $_REQUEST['supplier'] ) && is_numeric( $_REQUEST['supplier'] ) ) {
			$args['meta_query'] = array(
				array(
					'key'   => '_supplier',
					'value' => absint( $_REQUEST['supplier'] ),
					'type'  => 'NUMERIC',
				),
			);
		}

		// Filter by date range.
		if ( ! empty( $_REQUEST['date_start'] ) ) {
			$args['date_start'] = $_REQUEST['date_start'];
		}

		if ( ! empty( $_REQUEST['date_end'] ) ) {
			$args['date_end'] = $_REQUEST['date_end'];
		}

		// Sorting.
		$args['order']   = isset( $_REQUEST['order'] ) && in_array( strtoupper( $_REQUEST['order'] ), [ 'ASC', 'DESC' ] ) ? $_REQUEST['order'] : 'DESC';
		$args['orderby'] = $_REQUEST['orderby'] ?? '';

		// Searching.
		$args['s']             = $_REQUEST['s'] ?? '';
		$args['search_column'] = $_REQUEST['search_column'] ?? '';

		// Add ordering args.
		$args = apply_filters( 'atum/purchase_orders/list_table/prepare_items_args', $this->parse_orderby_args( $args ) );

		$this->items = Helpers::get_pos( $args );

		$this->set_views_data( $args );
		$found_pos = $this->count_views[ "count_$view" ] ?? 0;

		$this->set_pagination_args( array(
			'total_items' => $found_pos,
			'per_page'    => $this->per_page,
			'total_pages' => - 1 === $this->per_page ? 0 : ceil( $found_pos / $this->per_page ),
			'orderby'     => ! empty( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'date',
			'order'       => ! empty( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'desc',
		) );

	}

	/**
	 * Add the ID field to the searchable columns
	 *
	 * @since 1.2.2
	 *
	 * @param string[]                $search_columns Array of column names to be searched.
	 * @param string                  $search         Text being searched.
	 * @param \WP_Query|AtumListTable $query          The current WP_Query or List Table instance.
	 *
	 * @return string[]
	 */
	public function post_search_columns( $search_columns, $search, $query ) {

		$search_columns[] = 'ID';

		return $search_columns;

	}

	/**
	 * Search POs by: A (post_title, post_excerpt, post_content ), B (posts.ID), C (posts.title), D (other meta fields wich can be numeric or not)
	 *
	 * @since 0.9.12
	 *
	 * @param string    $search_where The search piece used in the query's SQL.
	 * @param \WP_Query $wp_query     The WP_Query object (passed by reference).
	 *
	 * @return string
	 */
	public function posts_search( $search_where, $wp_query ) {

		global $pagenow, $wpdb;

		if (
			! is_admin() || ! in_array( $pagenow, array( 'edit.php', 'admin-ajax.php' ) ) ||
			! isset( $_REQUEST['s'], $_REQUEST['action'] ) || ! str_contains( $_REQUEST['action'], ATUM_PREFIX )
		) {
			return $search_where;
		}

		// Prevent keyUp problems.
		// Scenario: do a search with s and search_column, clean s, change search_column... and you will get nothing (s still set on url).
		if ( empty( trim( $_REQUEST['s'] ) ) ) {
			return ' AND ( 1 = 1 )';
		}

		// If we don't get any result looking for a field, we must force an empty result before
		// WP tries to query {$wpdb->posts}.ID IN ( 'empty value' ), which raises an error.
		$where_without_results = " AND ( {$wpdb->posts}.ID = -1 )";

		$search_column = esc_attr( stripslashes( $_REQUEST['search_column'] ) );
		$search_term   = sanitize_text_field( urldecode( stripslashes( trim( $_REQUEST['s'] ) ) ) );

		$cache_key    = AtumCache::get_cache_key( 'pos_search', [ $search_column, $search_term ] );
		$search_where = AtumCache::get_cache( $cache_key, ATUM_PO_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $search_where;
		}

		$search_terms = $this->parse_search( $search_term );

		if ( empty( $search_terms ) ) {
			AtumCache::set_cache( $cache_key, $where_without_results, ATUM_PO_TEXT_DOMAIN );
			return $where_without_results;
		}

		//
		// Regular search in post_title, post_excerpt and post_content (with no column selected).
		// Included ID by filter and the '_number' meta field min the query.
		// Added search for products
		// --------------------------------------------------------------------------------------!
		if ( empty( $search_column ) ) {

			$search_query = $this->build_search_query( $search_terms, '', 'string', 'p' );
			$search_query .= ' OR (' . $this->build_search_query( $search_terms, '_number', 'string', 'pm', TRUE ) . ')';
			$search_query .= ' OR (' . $this->build_search_query( $search_terms, '', 'string', 'pr' ) . ')';

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$query = $wpdb->prepare( "
				SELECT p.ID FROM $wpdb->posts p
				LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id)
				LEFT JOIN `$wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . "`" . "oi ON (p.ID = oi.order_id)
				LEFT JOIN `$wpdb->atum_order_itemmeta` AS oim ON (oi.`order_item_id` = oim.`order_item_id` AND oim.`meta_key` IN ('_product_id', '_variation_id'))
				LEFT JOIN `$wpdb->posts` AS pr ON oim.`meta_value` = pr.`ID`
		        WHERE p.post_type = %s 
		        AND ( $search_query )
	        ", $this->post_type );
			// phpcs:enable

			$search_terms_ids = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( empty( $search_terms_ids ) ) {
				AtumCache::set_cache( $cache_key, $where_without_results, ATUM_PO_TEXT_DOMAIN );
				return apply_filters( 'atum/purchase_orders_pro/list_table/posts_search/where', $where_without_results, $search_column, $search_term, $search_terms, $cache_key );
			}

			$search_where = " AND ( {$wpdb->posts}.ID IN (" . implode( ',', $search_terms_ids ) . ') )';

		}
		//
		// Search by column.
		// ------------------!
		elseif ( AtumHelpers::in_multi_array( $search_column, $this->searchable_columns ) ) {

			//
			// Search by user name.
			// ---------------------!
			if ( 'post_author' === $search_column ) {

				$where = [];
				foreach ( $search_terms as $term ) {
					$where[] = "display_name LIKE '%$term%'";
				}

				// Get users.
				$user_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->users WHERE " . implode( ' OR ', $where ) ); // phpcs:ignore WordPress.DB.PreparedSQL

				if ( empty( $user_ids ) ) {
					AtumCache::set_cache( $cache_key, $where_without_results, ATUM_PO_TEXT_DOMAIN );
					return apply_filters( 'atum/purchase_orders_pro/list_table/posts_search/where', $where_without_results, $search_column, $search_term, $search_terms, $cache_key );
				}

				$search_where = " AND $wpdb->posts.post_author IN (" . implode( ',', $user_ids ) . ')';

			}
			//
			// Search by any meta field.
			// -------------------------!
			else {

				$search_op = 'AND';

				//
				// Search by PO status.
				// ---------------------!
				if ( '_status' === $search_column ) {

					// Allow searching by the status labels.
					$search_statuses = [];

					foreach ( $this->statuses as $status => $label ) {

						foreach ( $search_terms as $search_term ) {
							if ( stripos( $status, $search_term ) !== FALSE || stripos( $label, $search_term ) !== FALSE ) {
								$search_statuses[] = $status;
							}
						}

					}

					$search_terms = $search_statuses;
					$search_op    = 'OR';

				}

				$search_query = $this->build_search_query( $search_terms, $search_column, 'string', 'pm', TRUE, $search_op );
				$meta_where   = apply_filters( 'atum/purchase_orders_pro/list_table/posts_search/numeric_meta_where', $search_query, $search_column, $search_terms );

				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$query = $wpdb->prepare( "
					SELECT DISTINCT p.ID FROM $wpdb->posts p
				    LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id)
				    WHERE p.post_type = %s AND ( $meta_where )
			    ", $this->post_type );
				// phpcs:enable

				$search_terms_ids = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				if ( empty( $search_terms_ids ) ) {
					AtumCache::set_cache( $cache_key, $where_without_results, ATUM_PO_TEXT_DOMAIN );
					return apply_filters( 'atum/purchase_orders_pro/list_table/posts_search/where', $where_without_results, $search_column, $search_term, $search_terms, $cache_key );
				}

				$search_where = " AND ( $wpdb->posts.ID IN (" . implode( ',', $search_terms_ids ) . ') )';

			}

		}

		AtumCache::set_cache( $cache_key, $search_where, ATUM_PO_TEXT_DOMAIN );

		return apply_filters( 'atum/purchase_orders_pro/list_table/posts_search/where', $search_where, $search_column, $search_term, $search_terms, $cache_key );

	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 0.9.12
	 */
	public function no_items() {

		esc_html_e( 'No POs found', ATUM_PO_TEXT_DOMAIN );

		if ( ! empty( $_REQUEST['s'] ) ) {
			/* translators: the searched query */
			printf( esc_html__( " with query '%s'", ATUM_PO_TEXT_DOMAIN ), esc_attr( $_REQUEST['s'] ) );
		}

	}

	/**
	 * Get a list of CSS classes for the WP_List_Table table tag. Deleted 'fixed' from standard function
	 *
	 * @since 0.9.12
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {

		$table_classes   = parent::get_table_classes();
		$table_classes[] = 'pos-list';

		return $table_classes;
	}

	/**
	 * Add the filters to the table nav
	 *
	 * @since  0.9.12
	 */
	protected function table_nav_filters() {

		// Do not show filters on the unkown or archived view.
		if ( ! empty( $_REQUEST['view'] ) && in_array( $_REQUEST['view'], [ 'unknown', 'trash' ] ) ) {
			return;
		}

		// Supplier filtering.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo AtumHelpers::suppliers_dropdown( [
			'selected' => esc_attr( $_REQUEST['supplier'] ?? '' ),
			'enhanced' => 'yes' === AtumHelpers::get_option( 'enhanced_suppliers_filter', 'no' ),
		] );

		// Date range filtering.
		?>
		<span class="range-picker input-group">
			<input type="text" class="form-control auto-filter atum-datepicker no-drag" name="date_start" id="date_start"
				placeholder="<?php esc_attr_e( 'Date from...', ATUM_PO_TEXT_DOMAIN ) ?>"
				autocomplete="off" data-date-format="YYYY-MM-DD" data-range-max="#date_end"
				value="<?php echo esc_attr( $_REQUEST['date_start'] ?? '' ) ?>"
			>
			<span class="input-group-text"><?php esc_html_e( 'to', ATUM_PO_TEXT_DOMAIN ) ?></span>
			<input type="text" class="form-control auto-filter atum-datepicker no-drag" name="date_end" id="date_end"
				placeholder="<?php esc_attr_e( 'Date to...', ATUM_PO_TEXT_DOMAIN ) ?>"
				autocomplete="off" data-date-format="YYYY-MM-DD" data-range-min="#date_start"
				value="<?php echo esc_attr( $_REQUEST['date_end'] ?? '' ) ?>"
			>
		</span>
		<?php

		// Extra filters.
		// TODO: ADD THE LOGIC FOR ALL THE EXTRA FILTERS.
		$extra_filters = (array) apply_filters( 'atum/purchase_orders_pro/extra_filters', array(
			'due_soon' => __( 'Due Soon POs', ATUM_PO_TEXT_DOMAIN ),
			'late_po'  => __( 'Late POs', ATUM_PO_TEXT_DOMAIN ),
		) );

		$no_auto_filter = (array) apply_filters( 'atum/purchase_orders_pro/extra_filters/no_auto_filter', [] );

		?>
		<select name="extra_filter" class="wc-enhanced-select atum-enhanced-select dropdown_extra_filter auto-filter date-selector" autocomplete="off">
			<option value=""><?php esc_html_e( 'Extra filters...', ATUM_PO_TEXT_DOMAIN ) ?></option>

			<?php foreach ( $extra_filters as $extra_filter => $label ) : ?>
				<option value="<?php echo esc_attr( $extra_filter ) ?>"
					<?php selected( ! empty( $_REQUEST['extra_filter'] ) && $_REQUEST['extra_filter'] === $extra_filter, TRUE ); ?>
					<?php if ( in_array( $extra_filter, $no_auto_filter, TRUE ) ) echo ' data-auto-filter="no"' ?>
				><?php echo esc_attr( $label ) ?></option>
			<?php endforeach; ?>
		</select>
		<?php

		do_action( 'atum/purchase_orders_pro/list_table/after_nav_filters', $this );

	}

	/**
	 * Extra controls to be displayed in table nav sections
	 *
	 * @since 0.9.12
	 *
	 * @param string $which 'top' or 'bottom' table nav.
	 */
	protected function extra_tablenav( $which ) {

		if ( 'top' === $which ) : ?>

			<div class="alignleft actions">
				<div class="actions-wrapper">
					<?php $this->table_nav_filters() ?>
				</div>
			</div>

		<?php endif;

	}


	/**
	 * Get an associative array ( id => link ) with the list of available views on this table.
	 *
	 * @since 0.9.12
	 *
	 * @return array
	 */
	protected function get_views() {

		$views_names = array_merge( [ 'all' => __( 'All', ATUM_PO_TEXT_DOMAIN ) ], $this->statuses );

		$views = array();
		$view  = ! empty( $_REQUEST['view'] ) ? esc_attr( $_REQUEST['view'] ) : 'all';

		if ( ! empty( $this->unknown_status_pos ) ) {
			$views_names['unknown'] = __( 'Unknown', ATUM_PO_TEXT_DOMAIN );
		}

		foreach ( $views_names as $key => $text ) {

			$class   = $id = $active = $empty = '';
			$classes = array();

			$current_all = ! empty( $views[ $key ]['all'] ) ? $views[ $key ]['all'] : $key;

			if ( 'all' === $current_all ) {
				$count = $this->count_views['count_all'];
			}
			else {

				if ( ! empty( $views[ $key ] ) ) {
					$count = $this->count_views[ "count_{$views[ $key ]['all']}" ];
				}
				else {
					$count = $this->count_views[ "count_$key" ];
				}

				$id = ' id="' . $current_all . '"';

			}

			$query_filters = $this->query_filters;

			if ( $current_all === $view || ( ! $view && 'all' === $current_all ) ) {
				$classes[] = 'current';
				$active    = ' class="active"';
			}
			else {
				$query_filters['paged'] = 1;
			}

			if ( ! $count ) {
				$classes[] = 'empty';
				$empty     = 'empty';
			}

			if ( $classes ) {
				$class = ' class="' . implode( ' ', $classes ) . '"';
			}

			$hash_params   = http_build_query( array_merge( $query_filters, [ 'view' => $current_all ] ) );
			$views[ $key ] = '<span' . $active . '><a' . $id . $class . ' href="#" rel="address:/?' . $hash_params . '"><span' . $active . '>' . $text . ' <span class="count extra-links-container ' . $empty . '">(' . $count . ')</span></span></a></span>';

		}

		return apply_filters( 'atum/purchase_orders_pro/list_table/views', $views );

	}

	/**
	 * Set views for table filtering and calculate total value counters for pagination
	 *
	 * @since 0.9.12
	 *
	 * @param array $args WP_Query arguments.
	 */
	protected function set_views_data( $args ) {

		// Get all the possible statuses and disable pagination.
		unset( $args['paged'], $args['view'], $args['status'] );

		$args['per_page'] = -1;
		$args['fields']   = 'all'; // Return WP_Post objects instead of PO objects, so we can use wp_list_filter.

		// Add the ordering args.
		$args = $this->parse_orderby_args( $args );

		$pos = Helpers::get_pos( $args );

		$this->count_views['count_all'] = count( $pos );

		foreach ( $this->statuses as $status => $label ) {

			// The archived POs aren't shown in the "All" view.
			if ( 'trash' === $status ) {
				$this->count_views[ "count_{$status}" ] = count( Helpers::get_pos( array_merge( $args, [ 'status' => 'trash' ] ) ) );
			}
			else {
				$this->count_views[ "count_{$status}" ] = count( wp_list_filter( $pos, [ 'post_status' => $status ] ) );
			}

		}

		if ( ! empty( $this->unknown_status_pos ) ) {
			$this->count_views['count_unknown'] = count( $this->unknown_status_pos );
		}

	}

	/**
	 * Loads the current template
	 *
	 * @since 0.9.12
	 *
	 * @param \WP_Post $item The PO post item.
	 */
	public function single_row( $item ) {

		if ( $item instanceof POExtended ) {

			$this->list_item = $item;

			if ( ! $this->list_item->items_have_been_read() ) {
				$this->list_item->read_items();
			}

		}
		else {
			$this->list_item = AtumHelpers::get_atum_order_model( $item->ID, TRUE, PurchaseOrders::POST_TYPE );
		}

		if ( ! $this->list_item->exists() ) {
			return;
		}

		$this->allow_calcs = FALSE;
		$row_classes       = array( ( ++ $this->row_count % 2 ? 'even' : 'odd' ) );
		$row_class         = ' class="main-row ' . implode( ' ', $row_classes ) . '"';
		$row_data          = ' data-id="' . $this->list_item->get_id() . '" data-status="' . $this->list_item->get_status() . '"';

		// Output the row.
		echo '<tr' . $row_class . $row_data . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->single_row_columns( $item );
		echo '</tr>';

		// Reset the child value.
		$this->is_child = FALSE;

	}

	/**
	 * PO ID
	 *
	 * @since 0.9.12
	 *
	 * @return int
	 */
	protected function get_current_list_item_id() {
		return $this->list_item->id;
	}

	/**
	 * Displays the PO number column
	 *
	 * @since 0.9.12
	 *
	 * @param \WP_Post $item The PO post.
	 *
	 * @return string
	 */
	protected function column__number( $item ) {

		$po_id     = $this->list_item->get_id();
		$po_number = $this->list_item->number ?: $po_id;
		$po_number = 'trash' !== $this->list_item->post->post_status ? edit_post_link( "#$po_number", '', '', $po_id, 'row-title' ) : "<span class=\"row-title\">#$po_number</span>";

		return apply_filters( 'atum/purchase_orders_pro/list_table/column_number', $po_number, $item, $this->list_item, $this );
	}

	/**
	 * Displays the PO date created column
	 *
	 * @since 0.9.12
	 *
	 * @param \WP_Post $item The PO post.
	 *
	 * @return string
	 */
	protected function column__date_created( $item ) {

		$date_created = $this->list_item->date_created;
		$date_format  = 'Y-m-d';

		if ( $date_created instanceof \WC_DateTime ) {
			$date_created = $date_created->date_i18n( $date_format );
		}
		elseif ( $date_created ) {
			$date_created = date_i18n( $date_format, strtotime( $date_created ) );
		}
		else {
			$post         = $this->list_item->get_post();
			$date_created = date_i18n( $date_format, strtotime( $post->post_date ) );
		}

		return apply_filters( 'atum/purchase_orders_pro/list_table/column_date_created', $date_created, $item, $this->list_item, $this );

	}

	/**
	 * Displays the PO author column
	 *
	 * @since 0.9.12
	 *
	 * @param \WP_Post $item The PO post.
	 *
	 * @return string
	 */
	protected function column_post_author( $item ) {

		$po_post    = $this->list_item->get_post();
		$po_creator = get_user_by( 'id', $po_post->post_author );
		$user_link  = '<a href="' . get_edit_user_link( $po_post->post_author ) . '">' . $po_creator->display_name . '</a>';

		return apply_filters( 'atum/purchase_orders_pro/list_table/column_author', $user_link, $item, $this->list_item, $this );
	}

	/**
	 * Displays the PO status column
	 *
	 * @since 0.9.12
	 *
	 * @param \WP_Post $item The PO post.
	 *
	 * @return string
	 */
	protected function column__status( $item ) {

		$status        = 'trash' !== $this->list_item->post->post_status ? $this->list_item->status : 'trash';
		$status_exists = array_key_exists( $status, $this->statuses );
		$status_name   = $status_exists ? $this->statuses[ $status ] : __( '(Unknown)', ATUM_PO_TEXT_DOMAIN );
		$status_color  = $status_exists && array_key_exists( $status, $this->status_colors ) ? $this->status_colors[ $status ] : 'rgba(255,72,72,.5)';

		$output = sprintf(
			'<div class="order-status-container"><div class="atum-order-status"><mark class="status-%1$s tips" data-tip="%2$s" style="background-color:' . $status_color . '" data-status="%3$s"></mark></div><span>%4$s</span></div>',
			esc_attr( sanitize_html_class( $status ) ),
			esc_attr( $status_name ),
			esc_attr( $status ),
			esc_html( $status_name )
		);

		return apply_filters( 'atum/purchase_orders_pro/list_table/column_status', $output, $item, $this->list_item, $this );
	}

	/**
	 * Displays the PO supplier column
	 *
	 * @since 0.9.12
	 *
	 * @param \WP_Post $item     The PO post.
	 * @param bool     $editable
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	protected function column__supplier( $item, $editable = FALSE ) {

		$supplier      = $this->list_item->supplier;
		$supplier_name = $supplier && $supplier instanceof Supplier ? $supplier->name : self::EMPTY_COL;

		return apply_filters( 'atum/purchase_orders_pro/list_table/column_supplier', $supplier_name, $item, $this->list_item, $this );
	}

	/**
	 * Displays the PO date expected column
	 *
	 * @since 0.9.12
	 *
	 * @param \WP_Post $item The PO post.
	 *
	 * @return string
	 */
	protected function column__date_expected( $item ) {

		$date_expected = $this->list_item->date_expected;

		if ( $date_expected ) {

			$date_format = 'Y-m-d';

			if ( $date_expected instanceof \WC_DateTime ) {
				$date_expected = $date_expected->date_i18n( $date_format );
			}
			else {
				$date_expected = date_i18n( $date_format, strtotime( $date_expected ) );
			}

			// Only the "Due" POs will have the status icon.
			if ( $this->list_item->is_due() ) {

				$colors                  = Globals::get_date_expected_colors();
				$date_expected_timestamp = strtotime( $date_expected );
				$due_soon_timestamp      = strtotime( "-$this->due_soon_days days" );

				if ( $date_expected_timestamp <= time() && $date_expected_timestamp < $due_soon_timestamp ) {
					$color   = $colors['late'] ?? '';
					$tooltip = __( 'Late PO', ATUM_PO_TEXT_DOMAIN );
				}
				elseif ( $date_expected_timestamp >= $due_soon_timestamp ) {
					$color   = $colors['due_soon'] ?? '';
					$tooltip = __( 'Due soon', ATUM_PO_TEXT_DOMAIN );
				}
				else {
					$color   = $colors['in_time'] ?? '';
					$tooltip = __( 'In time', ATUM_PO_TEXT_DOMAIN );
				}

				$date_expected = '<div class="date-expected-status-container"><span class="date-expected-status atum-tooltip" 
					style="background-color:' . esc_attr( $color ) . '" title="' . esc_attr( $tooltip ) . '"></span> ' . esc_html( $date_expected ) . '</div>';

			}

		}
		else {
			$date_expected = self::EMPTY_COL;
		}

		return apply_filters( 'atum/purchase_orders_pro/list_table/column_entry', $date_expected, $item, $this->list_item, $this );
	}

	/**
	 * Displays the PO total column
	 *
	 * @since 0.9.12
	 *
	 * @param \WP_Post $item The PO post.
	 *
	 * @return string
	 */
	protected function column__total( $item ) {

		$formatted_total = $this->list_item->format_price( $this->list_item->total );

		$site_currency = get_woocommerce_currency();
		$po_total      = $this->list_item->total;

		if ( $site_currency !== $this->list_item->currency ) {

			$exchange_rate = $this->list_item->exchange_rate;

			if ( $exchange_rate > 0 ) {
				$po_total /= $exchange_rate;
			}

		}

		$this->increase_total( '_total', $po_total );

		return apply_filters( 'atum/purchase_orders_pro/list_table/column_total', $formatted_total, $item, $this->list_item, $this );
	}

	/**
	 * Displays the preview PO icon column
	 *
	 * @since 1.0.4
	 *
	 * @param \WP_Post $item The PO post.
	 *
	 * @return string
	 */
	protected function column_calc_preview( $item ) {

		$calc_preview = '<a href="#" class="preview-po"><i class="atum-icon atmi-eye tips" data-tip="' . esc_attr__( 'Preview PO', ATUM_PO_TEXT_DOMAIN ) . '"></i></a>';

		return apply_filters( 'atum/purchase_orders_pro/list_table/column_calc_preview', $calc_preview, $item, $this->list_item, $this );

	}

	/**
	 * Displays the Added-to-Stock icon column
	 *
	 * @since 1.0.3
	 *
	 * @param \WP_Post $item The PO post.
	 *
	 * @return string
	 */
	protected function column_calc_added_to_stock( $item ) {

		$added_to_stock = self::EMPTY_COL;

		if ( $this->list_item->is_returning() || $this->list_item->is_cancelled() || $this->list_item->is_due() ) {
			return $added_to_stock;
		}

		$po_items       = $this->list_item->get_items();
		$remain_items   = [];
		$received_items = [];
		$stocked_items  = [];

		if ( ! empty( $po_items ) ) {

			$deliveries = Deliveries::get_po_orders( $this->list_item->get_id() );

			if ( ! empty( $deliveries ) ) {

				foreach ( $po_items as $po_item ) {
					$po_item_id                  = $po_item->get_id();
					$remain_items[ $po_item_id ] = $po_item->get_quantity();
				}

				foreach ( $deliveries as $delivery ) {

					$delivery_items = $delivery->get_items( array_values( apply_filters( 'atum/purchase_orders_pro/delivery/item_group_to_type', [ 'delivery_item' ] ) ) );

					foreach ( $delivery_items as $delivery_item ) {

						/**
						 * Variable definition
						 *
						 * @var DeliveryItemProduct|DeliveryItemInventory $delivery_item
						 */
						$po_item_id = $delivery_item->get_po_item_id();

						if ( isset( $received_items[ $po_item_id ] ) ) {
							$received_items[ $po_item_id ] += $delivery_item->get_quantity();
						}
						else {
							$received_items[ $po_item_id ] = $delivery_item->get_quantity();
						}

						if ( 'yes' === $delivery_item->get_stock_changed() ) {

							if ( isset( $stocked_items[ $po_item_id ] ) ) {
								$stocked_items[ $po_item_id ] += $delivery_item->get_quantity();
							}
							else {
								$stocked_items[ $po_item_id ] = $delivery_item->get_quantity();
							}

							if ( isset( $remain_items[ $po_item_id ] ) ) {

								if ( $stocked_items[ $po_item_id ] >= $remain_items[ $po_item_id ] ) {
									unset( $remain_items[ $po_item_id ] );
								}
								else {
									$remain_items[ $po_item_id ] -= $stocked_items[ $po_item_id ];
								}

							}

						}

					}

				}

				if ( ! empty( $stocked_items ) ) {
					$tip            = empty( $remain_items ) ? __( 'All the delivery items were added to stock', ATUM_PO_TEXT_DOMAIN ) : __( 'Partially added to stock', ATUM_PO_TEXT_DOMAIN );
					$color          = empty( $remain_items ) ? 'success' : 'warning';
					$added_to_stock = '<i class="atum-icon atmi-highlight color-' . esc_attr( $color ) . ' tips" data-tip="' . esc_attr( $tip ) . '"></i>';
				}

			}

		}

		return apply_filters( 'atum/purchase_orders_pro/list_table/column_added_to_stock', $added_to_stock, $item, $this->list_item, $this );

	}

	/**
	 * Column for row actions
	 *
	 * @since 1.0.1
	 *
	 * @param \WP_Post $item The PO post.
	 *
	 * @return string
	 */
	public function column_calc_actions( $item ) {

		// Add separated buttons for printing and cloning.
		$actions_buttons = '<i class="print-po-action atum-icon atmi-printer atmi-tooltip" title="' . esc_attr__( 'Print', ATUM_PO_TEXT_DOMAIN ) . '"></i>';

		if ( ! $this->list_item->is_returning() && ! $this->list_item->is_cancelled() ) {
			$actions_buttons .= '<i class="clone-po-action atum-icon atmi-duplicate atmi-tooltip" title="' . esc_attr__( 'Clone', ATUM_PO_TEXT_DOMAIN ) . '"></i>';
		}

		$actions_buttons .= parent::column_calc_actions( $item );

		return apply_filters( 'atum/purchase_orders_pro/list_table/column_actions', $actions_buttons, $item, $this->list_item, $this );

	}

	/**
	 * Bulk actions are an associative array in the format 'slug' => 'Visible Title'
	 *
	 * @since 0.9.12
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'.
	 */
	protected function get_bulk_actions() {

		$view         = ! empty( $_REQUEST['view'] ) ? esc_attr( $_REQUEST['view'] ) : 'all';
		$bulk_actions = [];

		if ( 'trash' === $view ) {

			$bulk_actions = array(
				'poUnarchive'   => __( 'Unarchive', ATUM_PO_TEXT_DOMAIN ),
				'poForceDelete' => __( 'Delete Permanently', ATUM_PO_TEXT_DOMAIN ),
			);

		}
		else {

			// Get all the available bulk actions from the row actions.
			foreach ( self::$row_actions as $row_action ) {

				// TODO: HOW TO PRINT IN BULK?
				if ( 'poPrint' === $row_action['name'] ) {
					continue;
				}

				$bulk_actions[ $row_action['name'] ] = $row_action['label'];

			}

		}

		return apply_filters( 'atum/purchase_orders_pro/list_table/bulk_actions', $bulk_actions );

	}

	/**
	 * Apply an extra filter to the current List Table query
	 *
	 * @since 0.9.13
	 *
	 * @param \WP_Query $query
	 */
	public function do_extra_filter( $query ) {

		if ( PurchaseOrders::POST_TYPE !== $query->query_vars['post_type'] ) {
			return;
		}

		if ( ! empty( $query->query_vars['post__in'] ) ) {
			return;
		}

		global $wpdb;
		$extra_filter = esc_attr( $_REQUEST['extra_filter'] );

		$extra_filter_cache_key = AtumCache::get_cache_key( 'pos_list_table_extra_filter', $extra_filter );
		$filtered_pos           = AtumCache::get_cache( $extra_filter_cache_key, ATUM_PO_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			// The statuses that are considered as "Due" (not received yet).
			$due_statuses = Globals::get_due_statuses();

			switch ( $extra_filter ) {
				case 'due_soon':
					// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
					$filtered_pos = $wpdb->get_col( $wpdb->prepare( "
						SELECT ID FROM $wpdb->posts p
						LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND meta_key = '_date_expected')
						WHERE meta_value != '' AND meta_value >= %s AND meta_value < NOW() 
						AND post_status IN ('" . implode( "','", $due_statuses ) . "')
						ORDER BY meta_value ASC
					", date_i18n( 'Y-m-d H:i:s', strtotime( "-$this->due_soon_days days" ) ) ) );
					// phpcs:enable
					break;

				case 'late_po':
					// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
					$filtered_pos = $wpdb->get_col( $wpdb->prepare( "
						SELECT ID FROM $wpdb->posts p
						LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND meta_key = '_date_expected')
						WHERE meta_value != '' AND meta_value < %s AND meta_value <= NOW() 
						AND post_status IN ('" . implode( "','", $due_statuses ) . "')
						ORDER BY meta_value ASC
					", date_i18n( 'Y-m-d H:i:s', strtotime( "-$this->due_soon_days days" ) ) ) );
					// phpcs:enable
					break;

			}

			// Allow extra filters to be added externally.
			$filtered_pos = apply_filters( 'atum/purchase_orders_pro/list_table/extra_filter', $filtered_pos, $extra_filter );

			// Set the transient to expire in 40 seconds.
			AtumCache::set_cache( $extra_filter_cache_key, $filtered_pos, ATUM_PO_TEXT_DOMAIN, 40 );

		}

		// Filter the query posts by these IDs.
		if ( ! empty( $filtered_pos ) ) {
			$query->set( 'post__in', $filtered_pos );
			$query->set( 'orderby', 'post__in' );
		}
		// Force no results ("-1" never will be a post ID).
		else {
			$query->set( 'post__in', [ -1 ] );
		}

	}

	/**
	 * Prints the totals columns on totals row at table footer
	 *
	 * @since 1.0.4
	 */
	public function print_totals_columns() {

		// Does not show the totals' row if there are no results.
		if ( empty( $this->items ) ) {
			return;
		}

		/* @noinspection PhpUnusedLocalVariableInspection */
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		$group_members = wp_list_pluck( $this->group_members, 'members' );
		$column_keys   = array_keys( $columns );
		$first_column  = current( $column_keys );
		$second_column = next( $column_keys );

		// Let to adjust the totals externally if needed.
		$this->totalizers = apply_filters( 'atum/list_table/totalizers', $this->totalizers ); // Using the default ATUM hook for compatibility.

		foreach ( $columns as $column_key => $column_display ) {

			$class   = array( 'manage-column', "column-$column_key" );
			$colspan = '';

			if ( in_array( $column_key, $hidden ) ) {
				$class[] = 'hidden';
			}

			if ( $first_column === $column_key ) {

				$class[] = 'totals-heading';

				// Set a colspan of 2 if the checkbox column is present and the second column isn't hidden.
				if ( 'cb' === $first_column && ! in_array( $second_column, $hidden ) ) {
					$colspan = 'colspan="2"';
				}

				$column_display = '<span>' . __( 'Totals', ATUM_TEXT_DOMAIN ) . '</span>';

			}
			elseif ( 'cb' === $first_column && $second_column === $column_key ) {
				continue; // Get rid of the second column as the first one will have a colspan.
			}
			elseif ( in_array( $column_key, array_keys( $this->totalizers ) ) ) {

				$total = $this->totalizers[ $column_key ];

				// Show the total amount in price format and add a tooltip.
				if ( '_total' === $column_key ) {
					$tooltip        = __( "The total is being calculated in your shop's base currency and the exchange rates for every PO applied accordingly", ATUM_PO_TEXT_DOMAIN );
					$column_display = '<span class="atum-tooltip' . ( $total < 0 ? ' danger' : '' ) . '" title="' . esc_attr( $tooltip ) . '">' . wc_price( $total ) . '</span>';
				}
				else {
					$total_class    = $total < 0 ? ' class="danger"' : '';
					$column_display = "<span{$total_class}>" . round( $total, 2, PHP_ROUND_HALF_UP ) . '</span>';
				}

			}
			else {
				$column_display = '';
			}

			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}

			// Add the group key as class.
			foreach ( $group_members as $group_key => $members ) {
				if ( in_array( $column_key, $members ) ) {
					$class[] = $group_key;
					break;
				}
			}

			$tag   = 'cb' === $column_key ? 'td' : 'th';
			$scope = 'th' === $tag ? 'scope="col"' : '';

			if ( ! empty( $class ) ) {
				$class = "class='" . join( ' ', $class ) . "'";
			}

			echo "<$tag $scope $class $colspan>$column_display</th>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		}

	}

	/**
	 * Enqueue the required scripts and styles
	 *
	 * @since 0.9.12
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		parent::enqueue_scripts( $hook );

		wp_enqueue_style( 'atum-po-list', ATUM_PO_URL . 'assets/css/atum-pos-list.css', [ 'atum-list' ], ATUM_PO_VERSION );

		// TODO: DO WE NEED TO CHECK THE ITEMS BEFORE CHANGING THE PO STATUS MANUALLY?
		$statuses_with_items = [
			'atum_approval',
			'atum_approved',
			'atum_ordered',
			'atum_vendor_received',
			'atum_onthewayin',
			'atum_receiving',
			'atum_quality_check',
			'atum_added',
			'atum_partially_added',
			'atum_received',
		];

		$statuses_with_received_items = [
			'atum_quality_check',
			'atum_added',
			'atum_partially_added',
			'atum_received',
		];

		$statuses_with_stocked_items = [
			'atum_added',
			'atum_received',
		];

		wp_register_script( 'atum-purchase-orders-list', ATUM_PO_URL . 'assets/js/build/atum-po-list-table.js', [ 'atum-list' ], ATUM_PO_VERSION, TRUE );

		$user_notifications = Helpers::get_po_notifications( [
			'status'   => 'unread',
			'target'   => 'all',
			'ids_only' => FALSE,
		] );
		$atum_po_list_vars  = array(
			'areYouSure'                 => __( 'Are you sure?', ATUM_PO_TEXT_DOMAIN ),
			'atumCommentsNonce'          => wp_create_nonce( 'po-comments-nonce' ),
			'atumNotificationsNonce'     => wp_create_nonce( 'po-count-notifications-nonce' ),
			'cancel'                     => __( 'Cancel', ATUM_PO_TEXT_DOMAIN ),
			'cloneDeliveries'            => __( 'Do you also want to clone Deliveries and Invoices?', ATUM_PO_TEXT_DOMAIN ),
			'clonedSuccess'              => __( 'The selected PO was cloned successfully', ATUM_PO_TEXT_DOMAIN ),
			'continue'                   => __( 'Continue', ATUM_PO_TEXT_DOMAIN ),
			'doIt'                       => __( 'Yes, do it!', ATUM_PO_TEXT_DOMAIN ),
			'markAllAsRead'              => __( 'Mark all as read', ATUM_PO_TEXT_DOMAIN ),
			'markAsRead'                 => __( 'Mark as read', ATUM_PO_TEXT_DOMAIN ),
			'no'                         => __( 'No', ATUM_PO_TEXT_DOMAIN ),
			'noMentionComments'          => __( 'No comments with mention have been found.', ATUM_PO_TEXT_DOMAIN ),
			'notificationsTitle'         => __( 'PO Notifications', ATUM_PO_TEXT_DOMAIN ),
			'ok'                         => __( 'OK', ATUM_PO_TEXT_DOMAIN ),
			'or'                         => __( 'or', ATUM_PO_TEXT_DOMAIN ),
			'poPreviewNonce'             => wp_create_nonce( 'po-preview-nonce' ),
			'printUrl'                   => wp_nonce_url( admin_url( 'admin-ajax.php?action=atum_order_pdf&atum_order_id={poId}' ), 'atum-order-pdf' ),
			'returningPOSuccess'         => __( 'The returning PO was created successfully', ATUM_PO_TEXT_DOMAIN ),
			'save'                       => __( 'Save', ATUM_PO_TEXT_DOMAIN ),
			'search'                     => __( 'Search', ATUM_PO_TEXT_DOMAIN ),
			'searchBarcodeInPos'         => __( 'Search barcode within POs', ATUM_PO_TEXT_DOMAIN ),
			'searchBatchInPos'           => __( 'Search BATCH/LOT numbers within POs', ATUM_PO_TEXT_DOMAIN ),
			'showMentionsOnly'           => __( 'Show mentions only', ATUM_PO_TEXT_DOMAIN ),
			'statusChangeAllowedMessage' => __( 'According to the status flow restriction, this PO status can only be changed to: {values}', ATUM_PO_TEXT_DOMAIN ),
			'statusChangeMessage'        => __( 'You are going to change the PO status from <strong>"{origin}"</strong> to <strong>"{target}"</strong>', ATUM_PO_TEXT_DOMAIN ),
			'statuses'                   => Globals::get_statuses(),
			'statusChanged'              => __( 'The PO status was changed successfully', ATUM_PO_TEXT_DOMAIN ),
			'statusChangeNotAllowed'     => __( 'Status change not allowed!', ATUM_PO_TEXT_DOMAIN ),
			'statusFlow'                 => Globals::get_status_flow(),
			'statusFlowRestriction'      => AtumHelpers::get_option( 'po_status_flow_restriction', 'yes' ),
			'trackBarcode'               => __( 'Track Barcode', ATUM_PO_TEXT_DOMAIN ),
			'trackBatch'                 => __( 'Track BATCH Number', ATUM_PO_TEXT_DOMAIN ),
			'typeBarcode'                => __( 'Type the barcode to trace', ATUM_PO_TEXT_DOMAIN ),
			'typeBatchNumber'            => __( 'Type the BATCH number to trace', ATUM_PO_TEXT_DOMAIN ),
			'userNotifications'          => $user_notifications,
			'warning'                    => __( 'Warning', ATUM_PO_TEXT_DOMAIN ),
			'yes'                        => __( 'Yes', ATUM_PO_TEXT_DOMAIN ),
		);

		// Add the help guide vars if needed.
		if ( $this->help_guide ) {
			$atum_po_list_vars = array_merge( $atum_po_list_vars, AtumHelpGuide::get_instance()->get_help_guide_js_vars( $this->help_guide, $this->help_guide ) );
		}

		wp_localize_script( 'atum-purchase-orders-list', 'atumPOListTable', $atum_po_list_vars );
		wp_enqueue_script( 'atum-purchase-orders-list' );

	}

	/**
	 * Fix for the POs list table ordering.
	 *
	 * @since 1.0.4
	 *
	 * @param string        $orderby_type
	 * @param string        $orderby_field
	 * @param AtumListTable $list_table
	 *
	 * @return string
	 */
	public function po_meta_key_orderby( $orderby_type, $orderby_field, $list_table ) {
		// All the PO list columns are strings.
		return 'meta_value';
	}


}
