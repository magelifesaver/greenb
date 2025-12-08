<?php
/**
 * Extends the Log Registry's List Table and exports it as HTML report
 *
 * @package         AtumLogs\Reports
 * @subpackage      Reports
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.4.1
 */

namespace AtumLogs\Reports;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use AtumLogs\LogRegistry\Lists\ListTable;
use AtumLogs\Models\LogEntry;
use AtumLogs\Models\LogModel;


class LogsReport extends ListTable {

	/**
	 * Max length for the product titles in reports
	 *
	 * @var int
	 */
	protected $title_max_length;

	/**
	 * Report table flag
	 *
	 * @var bool
	 */
	protected static $is_report = TRUE;

	/**
	 * HtmlReport Constructor
	 *
	 * The child class should call this constructor from its own constructor to override the default $args
	 *
	 * @since 0.4.1
	 *
	 * @param array|string $args          {
	 *      Array or string of arguments.
	 *
	 *      @type bool   $show_cb           Optional. Whether to show the row selector checkbox as first table column.
	 *      @type bool   $show_controlled   Optional. Whether to show items controlled by ATUM or not.
	 *      @type int    $per_page          Optional. The number of posts to show per page (-1 for no pagination).
	 *      @type array  $selected          Optional. The posts selected on the list table.
	 *      @type array  $excluded          Optional. The posts excluded from the list table.
	 * }
	 */
	public function __construct( $args = array() ) {

		if ( isset( $args['title_max_length'] ) ) {
			$this->title_max_length = absint( $args['title_max_length'] );
		}

		parent::__construct( $args );

		unset( self::$table_columns['actions'] );
	}

	/**
	 * Generate the table navigation above or below the table
	 * Just the parent function but removing the nonce fields that are not required here
	 *
	 * @since 0.4.1
	 *
	 * @param string $which 'top' or 'bottom' table nav.
	 */
	protected function display_tablenav( $which ) {
		// Table nav not needed in reports.
	}
	
	/**
	 * Extra controls to be displayed in table nav sections
	 *
	 * @since 0.4.1
	 *
	 * @param string $which 'top' or 'bottom' table nav.
	 */
	protected function extra_tablenav( $which ) {
		// Extra table nav not needed in reports.
	}

	/**
	 * Generate row actions div
	 *
	 * @since 0.4.1
	 *
	 * @param array $actions        The list of actions.
	 * @param bool  $always_visible Whether the actions should be always visible.
	 */
	protected function row_actions( $actions, $always_visible = false ) {
		// Row actions not needed in reports.
	}

	/**
	 * All columns are sortable by default except cb and thumbnail
	 *
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle),
	 * you will need to register it here. This should return an array where the
	 * key is the column that needs to be sortable, and the value is db column to
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 *
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 *
	 * @since 0.4.1
	 */
	public function get_sortable_columns() {
		return array();
	}

	/**
	 * Loads the current log
	 *
	 * @since 0.4.1
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 */
	public function single_row( $item ) {

		$this->log         = LogModel::get_log_data( $item->id );
		$view              = ! empty( $_REQUEST['view'] ) ? esc_attr( $_REQUEST['view'] ) : 'all';
		$this->allow_calcs = FALSE;
		$row_classes       = [];

		if ( 'deleted' !== $view ) {
			if ( 0 === intval( $item->read ) ) {
				$row_classes[] = 'log_unread';
			}
			if ( intval( $item->featured ) ) {
				$row_classes[] = 'log_featured';
			}
		}

		$row_class = ' class="main-row ' . ( empty( $row_class ) ? '' : implode( ' ', $row_classes ) ) . '"';

		// Output the row.
		echo '<tr data-id="' . $item->id . '"' . $row_class . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->single_row_columns( $item );
		echo '</tr>';

		// Reset the child value.
		$this->is_child = FALSE;

	}

	/**
	 * Displays the Entry/Action column
	 *
	 * @param object $item
	 *
	 * @return mixed|void
	 * @since 0.4.1
	 */
	protected function column_entry( $item ) {

		$slug   = stripslashes( $this->log->entry );
		$format = LogEntry::get_text( $slug );
		$params = LogEntry::parse_params( $slug, $this->log->data, TRUE );

		$entry = empty( $params ) ? $format : vsprintf( $format, $params );

		if ( $this->title_max_length && mb_strlen( $entry ) > $this->title_max_length ) {
			$entry = trim( mb_substr( $entry, 0, $this->title_max_length ) ) . '...';
		}

		return apply_filters( 'atum/logs_registry_list/column_entry', $entry, $item, $this->log, $this );
	}


	/**
	 * Displays the Time Ago column
	 *
	 * @param object $item
	 *
	 * @return mixed|void
	 * @since 0.4.1
	 */
	protected function column_time( $item ) {

		$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ?: 'Y/m/d H:i:s';

		$time = gmdate( $date_format, $this->log->time );

		return apply_filters( 'atum/logs_registry_list/column_time', $time, $item, $this->log, $this );
	}

	/**
	 * Get an associative array ( id => link ) with the list of available views on this table
	 *
	 * @since 0.4.1
	 */
	protected function get_views() {
		// Views not needed in reports.
		return apply_filters( 'atum/logs/data_export/html_report/views', array() );
	}

	/**
	 * Adds the data needed for ajax filtering, sorting and pagination and displays the table
	 *
	 * @since 0.4.1
	 */
	public function display() {

		// Add the report template.
		ob_start();
		parent::display();

		// The title column cannot be disabled, so we must add 1 to the count.
		$columns     = count( self::$table_columns ) + 1;
		$count_views = $this->count_views;

		$report = str_replace( '<br>', '', ob_get_clean() );

		AtumHelpers::load_view( ATUM_LOGS_PATH . 'views/reports/log-registry-report-html', compact( 'report', 'columns', 'count_views' ) );

	}
	
}
