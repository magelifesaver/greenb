<?php
/**
 * LogRegistry List Table's class
 *
 * @package         AtumLogs\LogRegistry
 * @subpackage      Lists
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.0.1
 */

namespace AtumLogs\LogRegistry\Lists;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumListTables\AtumListTable;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Settings\Settings as AtumSettings;
use AtumLogs\Inc\Helpers;
use AtumLogs\Models\LogEntry;
use AtumLogs\Models\LogModel;


class ListTable extends AtumListTable {

	/**
	 * The list ID
	 *
	 * @var string
	 */
	protected $id = 'log-registry';

	/**
	 * The current template being displayed
	 *
	 * @var object
	 */
	protected $log;

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
			'user',
			'entry',
			'source',
			'module',
		),
		'numeric' => array(),
	);

	/**
	 * Whether to show the totals row
	 *
	 * @var bool
	 */
	protected $show_totals = FALSE;

	/**
	 * Whether to show or not the unmanaged counters
	 *
	 * @var bool
	 */
	protected $show_unmanaged_counters = FALSE;

	/**
	 * Counters for views
	 *
	 * @var array
	 */
	protected $count_views = array(
		'count_all'      => 0,
		'count_read'     => 0,
		'count_unread'   => 0,
		'count_featured' => 0,
		'count_deleted'  => 0,
	);

	/**
	 * ListTable constructor
	 *
	 * @param array|string $args            {
	 *   Array or string of arguments.
	 *
	 *   @type bool          $show_cb         Optional. Whether to show the row selector checkbox as first table column.
	 *   @type bool          $show_controlled Optional. Whether to show items controlled by ATUM or not.
	 *   @type int           $per_page        Optional. The number of posts to show per page (-1 for no pagination).
	 *   @type array         $selected        Optional. The posts selected on the list table.
	 *   @type array         $excluded        Optional. The posts excluded from the list table.
	 * }
	 */
	public function __construct( $args ) {

		if ( isset( $_REQUEST['action'] ) ) {
			$this->process_bulk_action();
		}

		// Prepare the table columns.
		self::$table_columns = self::get_table_columns();

		// Get the row/bulk actions.
		self::$row_actions = self::get_row_actions();

		parent::__construct( $args );
		
		// Hide columns id for Log Registry.
		add_filter( 'atum/list_table/display_column_id', '__return_false' );

		$this->per_page = $args['per_page'] ?? AtumHelpers::get_option( 'al_logs_per_page', AtumSettings::DEFAULT_POSTS_PER_PAGE );

	}

	/**
	 * Prepare the table columns for Log Registry
	 *
	 * @since 0.0.1
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
			'user'         => __( 'User', ATUM_LOGS_TEXT_DOMAIN ),
			'entry'        => __( 'Log', ATUM_LOGS_TEXT_DOMAIN ),
			'source'       => __( 'Source', ATUM_LOGS_TEXT_DOMAIN ),
			'module'       => __( 'Module', ATUM_LOGS_TEXT_DOMAIN ),
			'time'         => __( 'Date', ATUM_LOGS_TEXT_DOMAIN ),
			'calc_actions' => __( 'Actions', ATUM_LOGS_TEXT_DOMAIN ),
		);

		return (array) apply_filters( 'atum/logs_list_table/table_columns', $table_columns );

	}

	/**
	 * Returns the row actions list
	 *
	 * @since 1.2.0
	 *
	 * @return mixed|void
	 */
	public static function get_row_actions() {

		if ( ! empty( $_REQUEST['view'] ) && 'trash' === $_REQUEST['view'] ) {

			$row_actions = array(
				array(
					'name'  => 'restoreLog',
					'icon'  => 'atmi-undo',
					'label' => __( 'Restore log', ATUM_LOGS_TEXT_DOMAIN ),
				),
				array(
					'name'  => 'deletePermanently',
					'icon'  => 'atmi-trash',
					'label' => __( 'Delete permanently', ATUM_LOGS_TEXT_DOMAIN ),
				),
			);

		}
		else {

			$row_actions = array(
				array(
					'name'  => 'deleteLog',
					'icon'  => 'atmi-trash',
					'label' => __( 'Move to trash', ATUM_LOGS_TEXT_DOMAIN ),
				),
				array(
					'name'  => 'markFeatured',
					'icon'  => 'atmi-star',
					'label' => __( 'Mark as featured', ATUM_LOGS_TEXT_DOMAIN ),
				),
				array(
					'name'        => 'unmarkFeatured',
					'icon'        => 'atmi-featured',
					'label'       => __( 'Unmark as featured', ATUM_LOGS_TEXT_DOMAIN ),
					'conditional' => array(
						'class' => 'log_featured',
					),
				),
				array(
					'name'        => 'markRead',
					'icon'        => 'atmi-bookmark',
					'label'       => __( 'Mark as read', ATUM_LOGS_TEXT_DOMAIN ),
					'conditional' => array(
						'class' => 'log_unread',
					),
				),
				array(
					'name'        => 'markUnread',
					'icon'        => 'atmi-read',
					'label'       => __( 'Mark as unread', ATUM_LOGS_TEXT_DOMAIN ),
					'conditional' => array(
						'class' => 'log_read',
					),
				),
				array(
					'name'  => 'viewData',
					'icon'  => 'atmi-eye',
					'label' => __( 'View additional info', ATUM_LOGS_TEXT_DOMAIN ),
				),
			);

		}


		return apply_filters( 'atum/logs_list_table/row_actions', $row_actions );
	}

	/**
	 * Gets sortable columns
	 *
	 * @return array
	 */
	public function get_sortable_columns() {

		$columns = self::get_table_columns();
		unset( $columns['entry'] );
		unset( $columns['calc_actions'] );

		return $columns;

	}

	/**
	 * Prepare the table data
	 *
	 * @since 0.0.1
	 */
	public function prepare_items() {

		$args = $_REQUEST;

		$paged_args['paged'] = $this->get_pagenum();

		if ( $this->per_page && - 1 !== $this->per_page ) {
			$paged_args['per_page'] = $this->per_page;
		}
		if ( isset( $args['paged'] ) ) {
			unset( $args['paged'] );
		}

		$logs       = LogModel::get_logs( array_merge( $paged_args, $args ) );
		$found_logs = LogModel::get_logs( $args, TRUE );

		$this->items = $logs;

		$this->set_views_data( $args );

		$this->set_pagination_args( array(
			'total_items' => $found_logs,
			'per_page'    => $this->per_page,
			'total_pages' => - 1 === $this->per_page ? 0 : ceil( $found_logs / $this->per_page ),
		) );

	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 0.0.1
	 */
	public function no_items() {

		esc_html_e( 'No Logs found', ATUM_LOGS_TEXT_DOMAIN );

		if ( ! empty( $_REQUEST['s'] ) ) {
			/* translators: the searched query */
			printf( esc_html__( " with query '%s'", ATUM_LOGS_TEXT_DOMAIN ), esc_attr( $_REQUEST['s'] ) );
		}

	}

	/**
	 * Get a list of CSS classes for the WP_List_Table table tag. Deleted 'fixed' from standard function
	 *
	 * @since 0.0.1
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {

		$table_classes   = parent::get_table_classes();
		$table_classes[] = 'log-registry';

		return $table_classes;
	}

	/**
	 * Add the filters to the table nav
	 *
	 * @since  0.0.1
	 */
	protected function table_nav_filters() {

		// Type filtering.
		$source = isset( $_REQUEST['step_filter'] ) ? esc_attr( $_REQUEST['step_filter'] ) : '';

		echo Helpers::log_listtable_step_source_dropdown( $source ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Extra controls to be displayed in table nav sections
	 *
	 * @since 0.5.1
	 *
	 * @param string $which 'top' or 'bottom' table nav.
	 */
	protected function extra_tablenav( $which ) {
		$view = ! empty( $_REQUEST['view'] ) ? esc_attr( $_REQUEST['view'] ) : 'all';

		if ( 'top' === $which ) : ?>

			<div class="alignleft actions log-filters">
				<div class="actions-wrapper">

					<?php $this->table_nav_filters() ?>

				</div>
			</div>

		<?php endif;

		if ( 'deleted' === $view ) : ?>
			<div class="alignleft actions">
				<div class="actions-wrapper">
					<button type="button" class="delete-all-trash-logs btn btn-warning" style=""><?php echo esc_attr( __( 'Empty trash', ATUM_LOGS_TEXT_DOMAIN ) ); ?></button>
				</div>
			</div>
		<?php endif;
	}


	/**
	 * Get an associative array ( id => link ) with the list of available views on this table.
	 *
	 * @since 0.0.1
	 *
	 * @return array
	 */
	protected function get_views() {

		$views_name = array(
			'all'      => __( 'All', ATUM_LOGS_TEXT_DOMAIN ),
			'featured' => __( 'FEATURED', ATUM_LOGS_TEXT_DOMAIN ),
			'unread'   => __( 'UNREAD', ATUM_LOGS_TEXT_DOMAIN ),
			'read'     => __( 'READ', ATUM_LOGS_TEXT_DOMAIN ),
			'deleted'  => __( 'DELETED', ATUM_LOGS_TEXT_DOMAIN ),
		);

		$views = array();
		$view  = ! empty( $_REQUEST['view'] ) ? esc_attr( $_REQUEST['view'] ) : 'all';

		foreach ( $views_name as $key => $text ) {

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

			$hash_params   = http_build_query( array_merge( $query_filters, array( 'view' => $current_all ) ) );
			$views[ $key ] = '<span' . $active . '><a' . $id . $class . ' href="#" rel="address:/?' . $hash_params . '"><span' . $active . '>' . $text . ' <span class="count extra-links-container ' . $empty . '">(' . $count . ')</span></span></a></span>';

		}

		return $views;

	}

	/**
	 * Returns unread logs counter
	 *
	 * @since 0.4.1
	 *
	 * @return int
	 */
	public function get_unread_count() {

		$views = $this->count_views;

		return $views['count_unread'];
	}

	/**
	 * Set views for table filtering and calculate total value counters for pagination
	 *
	 * @since 0.0.1
	 *
	 * @param array $args WP_Query arguments.
	 */
	protected function set_views_data( $args ) {

		if ( isset( $args['view'] ) ) {
			unset( $args['view'] );
		}

		if ( isset( $args['paged'] ) ) {
			unset( $args['paged'] );
		}

		$this->count_views['count_all']      = LogModel::get_logs( $args, TRUE );
		$this->count_views['count_read']     = LogModel::get_logs( array_merge( $args, [ 'view' => 'read' ] ), TRUE );
		$this->count_views['count_unread']   = LogModel::get_logs( array_merge( $args, [ 'view' => 'unread' ] ), TRUE );
		$this->count_views['count_featured'] = LogModel::get_logs( array_merge( $args, [ 'view' => 'featured' ] ), TRUE );
		$this->count_views['count_deleted']  = LogModel::get_logs( array_merge( $args, [ 'view' => 'deleted' ] ), TRUE );

	}

	/**
	 * Loads the current template
	 *
	 * @since 0.0.1
	 *
	 * @param object $item The log db row.
	 */
	public function single_row( $item ) {

		$this->log         = LogModel::get_log_data( $item->id );
		$this->allow_calcs = FALSE;
		$row_classes       = array( ( ++ $this->row_count % 2 ? 'even' : 'odd' ) );
		$view              = ! empty( $_REQUEST['view'] ) ? esc_attr( $_REQUEST['view'] ) : 'all';

		if ( 'deleted' !== $view ) {
			if ( 0 === intval( $item->read ) ) {
				$row_classes[] = 'log_unread';
			}
			if ( intval( $item->featured ) ) {
				$row_classes[] = 'log_featured';
			}
		}

		$row_class = ' class="main-row ' . implode( ' ', $row_classes ) . '"';
		$item_data = ' data-id="' . $item->id . '"';

		if ( $item->featured ) {
			$item_data .= ' data-featured="' . $item->featured . '"';
		}

		if ( $item->read ) {
			$item_data .= ' data-read="' . $item->read . '"';
		}

		if ( $item->deleted ) {
			$item_data .= ' data-deleted="' . $item->deleted . '"';
		}

		// Output the row.
		echo '<tr' . $row_class . $item_data . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->single_row_columns( $item );
		echo '</tr>';

		// Reset the child value.
		$this->is_child = FALSE;

	}

	/**
	 * Product ID column
	 *
	 * @since 0.0.1
	 *
	 * @return int
	 */
	protected function get_current_list_item_id() {

		return $this->log->id;
	}

	/**
	 * Displays the User column
	 *
	 * @since 0.0.1
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	protected function column_user( $item ) {

		$user = get_user_by( 'id', $this->log->user_id );
		if ( FALSE === $user ) {
			$name = 'System';
			$icon = has_site_icon() ? '<img height="32" width="32" src="' . get_site_icon_url( 32 ) . '"/>' : '';
		}
		else {
			$name = $user->display_name;
			$icon = get_avatar( $user->ID, 32, '', $name );
		}
		ob_start();
		echo ( FALSE === $user ) ? '' : '<a href="' . esc_url( get_edit_user_link( $user->ID ) ) . '" target="_blank">';
		if ( 'yes' === AtumHelpers::get_option( 'al_show_avatar', 'no' ) ) {
			echo $icon; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		}
		echo ' <span>' . esc_html( $name ) . '</span>';
		echo ( FALSE === $user ) ? '' : '</a>';

		return apply_filters( 'atum/logs_registry_list/column_user', ob_get_clean(), $item, $this->log, $this );
	}

	/**
	 * Displays the module column
	 *
	 * @since 0.0.1
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	protected function column_module( $item ) {

		return apply_filters( 'atum/logs_registry_list/column_module', LogModel::get_module_name( $this->log->module ), $item, $this->log, $this );
	}

	/**
	 * Displays the source column
	 *
	 * @since 0.0.1
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	protected function column_source( $item ) {

		return apply_filters( 'atum/logs_registry_list/column_source', LogModel::get_source_name( $this->log->source ), $item, $this->log, $this );
	}

	/**
	 * Displays the Time Ago column
	 *
	 * @since 0.0.1
	 *
	 * @param object $item
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	protected function column_time( $item ) {

		return apply_filters( 'atum/logs_registry_list/column_time', Helpers::format_log_time( $this->log->time ), $item, $this->log, $this );
	}

	/**
	 * Displays the Entry/Action column
	 *
	 * @since 0.0.1
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	protected function column_entry( $item ) {

		return apply_filters( 'atum/logs_registry_list/column_entry', LogEntry::parse_text( stripslashes( $this->log->entry ), $this->log->data ), $item, $this->log, $this );
	}

	/**
	 * Displays the Data column
	 *
	 * @since 0.0.1
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	protected function column_data( $item ) {

		return apply_filters( 'atum/logs_registry_list/column_data', $this->log->data, $item, $this->log, $this );
	}

	/**
	 * Bulk actions are an associative array in the format 'slug' => 'Visible Title'
	 *
	 * @since 0.0.1
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'.
	 */
	protected function get_bulk_actions() {

		$view = ! empty( $_REQUEST['view'] ) ? esc_attr( $_REQUEST['view'] ) : 'all';

		if ( 'deleted' === $view ) {

			$bulk_actions = array(
				'undelete_logs' => __( 'Recover from trash', ATUM_LOGS_TEXT_DOMAIN ),
				'erase_logs'    => __( 'Permanently remove', ATUM_LOGS_TEXT_DOMAIN ),
			);

		}
		else {

			$bulk_actions = array(
				'mark_featured'   => __( 'Mark as featured', ATUM_LOGS_TEXT_DOMAIN ),
				'unmark_featured' => __( 'Unmark as featured', ATUM_LOGS_TEXT_DOMAIN ),
				'mark_read'       => __( 'Mark as read', ATUM_LOGS_TEXT_DOMAIN ),
				'unmark_read'     => __( 'Mark as unread', ATUM_LOGS_TEXT_DOMAIN ),
				'delete_logs'     => __( 'Move to trash', ATUM_LOGS_TEXT_DOMAIN ),
			);

		}

		return apply_filters( 'atum/logs_list_table/bulk_actions', $bulk_actions );
	}

	/**
	 * Executes bulk actions
	 *
	 * @since 0.0.1
	 */
	protected function process_bulk_action() {

		$bulk = $this->current_action();

		switch ( $bulk ) {
			case 'mark_featured':
			case 'unmark_featured':
			case 'mark_read':
			case 'unmark_read':
			case 'erase_logs':
			case 'delete_logs':
			case 'undelete_logs':
				Helpers::$bulk( $_GET['id'] );
				break;
		}
		// No return.
	}

	/**
	 * Enqueue the required scripts and styles
	 *
	 * @since 0.0.1
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		parent::enqueue_scripts( $hook );

		// List Table styles.
		wp_enqueue_style( 'atum-logs-list', ATUM_LOGS_URL . 'assets/css/atum-logs.css', [ 'atum-list' ], ATUM_LOGS_VERSION );

		wp_register_script( 'atum-logs-registry', ATUM_LOGS_URL . 'assets/js/build/atum-logs-list-tables.js', [ 'atum-list' ], ATUM_LOGS_VERSION, TRUE );

		$vars = array(
			'confirmEmptyTrash' => __( 'Do you want to permanently remove all logs in trash?', ATUM_LOGS_TEXT_DOMAIN ),
			'current_screen'    => $hook,
			'logDataNonce'      => wp_create_nonce( 'atum-list-table-nonce' ),
			'logDataTitle'      => __( 'Log Data', ATUM_LOGS_TEXT_DOMAIN ),
			'rowActions'        => self::$row_actions,
			'showAll'           => __( 'Show all', ATUM_LOGS_TEXT_DOMAIN ),
			'sourceMap'         => Helpers::current_source_module_map(),
		);

		wp_localize_script( 'atum-logs-registry', 'atumLogRegistry', $vars );
		wp_enqueue_script( 'atum-logs-registry' );

		if ( is_rtl() ) {
			wp_register_style( 'atum-logs-list-rtl', ATUM_LOGS_URL . 'assets/css/atum-logs-rtl.css', array( 'atum-logs-registry' ), ATUM_LOGS_VERSION );
			wp_enqueue_style( 'atum-logs-list-rtl' );
		}

	}

}
