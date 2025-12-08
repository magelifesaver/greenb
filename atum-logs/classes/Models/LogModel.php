<?php
/**
 * The Model class for Log
 *
 * @package        AtumLogs
 * @subpackage     Models
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2025 Stock Management Labs™
 *
 * @since          0.0.1
 */

namespace AtumLogs\Models;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumCache;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Settings\Settings as AtumSettings;
use AtumLogs\Inc\Helpers;


final class LogModel {

	/**
	 * The db table where the Atum Action Logs are stored
	 *
	 * @var string
	 */
	private static $logs_table = 'atum_logs';


	/**
	 * Log sources
	 */
	const SRC_WC   = 'woocommerce';
	const SRC_ATUM = 'atum';
	const SRC_MI   = 'multi-inventory';
	const SRC_PL   = 'product-levels';
	const SRC_EP   = 'export-pro';
	const SRC_AL   = 'action-logs';
	const SRC_PO   = 'purchase-orders';
	const SRC_ST   = 'stock-takes';
	const SRC_PP   = 'pick-pack';

	/**
	 * Consts logs modules
	 */
	const MOD_WC_PRODUCT_DATA    = 'wc-product-data';
	const MOD_ATUM_PRODUCT_DATA  = 'atum-product-data';
	const MOD_MI_PRODUCT_DATA    = 'mi-product-data';
	const MOD_PL_PRODUCT_DATA    = 'pl-product-data';
	const MOD_WC_ORDERS          = 'wc-orders';
	const MOD_MI_ORDERS          = 'mi-orders';
	const MOD_PL_ORDERS          = 'pl-orders';
	const MOD_ST_ORDERS          = 'st-orders';
	const MOD_PP_ORDERS          = 'pp-orders';
	const MOD_PURCHASE_ORDERS    = 'purchase-orders';
	const MOD_MI_PURCHASE_ORDERS = 'mi-purchase-orders';
	const MOD_PO_PURCHASE_ORDERS = 'po-purchase-orders';
	const MOD_INVENTORY_LOGS     = 'inventory-logs';
	const MOD_MI_INVENTORY_LOGS  = 'mi-inventory-logs';
	const MOD_WC_SETTINGS        = 'wc-settings';
	const MOD_ATUM_SETTINGS      = 'atum-settings';
	const MOD_MI_SETTINGS        = 'mi-settings';
	const MOD_PL_SETTINGS        = 'pl-settings';
	const MOD_AL_SETTINGS        = 'al-settings';
	const MOD_PO_SETTINGS        = 'po-settings';
	const MOD_ST_SETTINGS        = 'st-settings';
	const MOD_SUPPLIERS          = 'suppliers';
	const MOD_ADDONS             = 'addons';
	const MOD_LOCATIONS          = 'locations';
	const MOD_CATEGORIES         = 'categories';
	const MOD_COUPONS            = 'couppons';
	const MOD_STOCK_CENTRAL      = 'stock-central';
	const MOD_MI_STOCK_CENTRAL   = 'mi-stock-central';
	const MOD_PL_STOCK_CENTRAL   = 'pl-stock-central';
	const MOD_MAN_CENTRAL        = 'manufacturing-central';
	const MOD_MI_MAN_CENTRAL     = 'mi-manufacturing-central';
	const MOD_EXPORT             = 'export';
	const MOD_IMPORT             = 'import';


	/*****************
	 * LOGS CRUD
	 *****************/

	/**
	 * Get a specific log
	 *
	 * @param int $log_id The ID of the log.
	 *
	 * @return array
	 * @since 0.0.1
	 */
	public static function get_log_data( $log_id ) {

		global $wpdb;

		$cache_key = AtumCache::get_cache_key( 'log', [ $log_id ] );
		$log_data  = AtumCache::get_cache( $cache_key, ATUM_LOGS_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			// phpcs:disable WordPress.DB.PreparedSQL
			$query = $wpdb->prepare( "
				SELECT *
				FROM $wpdb->prefix" . self::$logs_table . '
				WHERE id = %d 
			', $log_id );
			// phpcs:enable

			$log_data = $wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			AtumCache::set_cache( $cache_key, $log_data, ATUM_LOGS_TEXT_DOMAIN );

		}

		$log_data->data = maybe_unserialize( $log_data->data );

		return $log_data;

	}

	/**
	 * Check whether the specified source item has any log
	 *
	 * @param string $source_type . 'source' or 'user_id'.
	 * @param int    $entity_id   .
	 *
	 * @return string yes or no
	 * @since 0.0.1
	 */
	public static function source_has_log( $source_type, $entity_id ) {

		global $wpdb;

		$source_types = [ 'source', 'user_id' ];

		if ( ! in_array( $source_type, $source_types ) ) {
			return 'no';
		}

		$cache_key = AtumCache::get_cache_key( 'has_log_' . $source_type, $entity_id );
		$has_logs  = AtumCache::get_cache( $cache_key, ATUM_LOGS_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			$rowcount = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->prefix" . self::$logs_table . ' WHERE `%s` = %d', $source_type, $entity_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$has_logs = $rowcount > 0;

			AtumCache::set_cache( $cache_key, $has_logs, ATUM_LOGS_TEXT_DOMAIN );

		}

		return $has_logs;

	}

	/**
	 * Filter if data log must be saved or not
	 *
	 * @since 0.2.1
	 *
	 * @param array $log_data
	 *
	 * @return bool|int
	 *
	 * @throws \Exception
	 */
	public static function maybe_save_log( $log_data ) {

		$source = $log_data['source'];
		$module = $log_data['module'];

		$default  = [
			'value'   => 'yes',
			'options' => [
				$module => [
					'value' => 'yes',
				],
			],
		];
		$settings = AtumHelpers::get_option( 'al_register_' . $source, $default );

		if ( isset( $settings['value'] ) && 'no' === $settings['value'] ) {
			return FALSE;
		}

		if ( ! isset( $settings['options'][ $module ] ) || ( isset( $settings['options'][ $module ] ) && 'no' === $settings['options'][ $module ] ) ) {
			return FALSE;
		}

		return self::save_log( $log_data );
	}

	/**
	 * Save a log in the db
	 *
	 * @param array $log_data {
	 *                        Array of log data.
	 *
	 * @type int    $id
	 * @type string $ref
	 * @type int    $user_id
	 * @type string $module
	 * @type string $source
	 * @type int    $time
	 * @type string $entry
	 * @type int    $read
	 * @type int    $featured
	 * @type int    $deleted
	 * @type string $data
	 * }
	 *
	 * @return int|bool
	 * @throws \Exception
	 * @since 0.0.1
	 */
	public static function save_log( $log_data ) {

		global $wpdb;

		$time = date_i18n( 'U', FALSE, TRUE );

		$log_data = apply_filters( 'atum/logs/args_save_log', wp_parse_args( $log_data, array(
			'id'       => 0,
			'user_id'  => is_user_logged_in() ? get_current_user_id() : 0,
			'module'   => '',
			'source'   => '',
			'time'     => $time,
			'entry'    => '',
			'read'     => 0,
			'featured' => 0,
			'deleted'  => 0,
			'data'     => [],
		) ) );

		$log_data = self::sanitize_data_for_db( $log_data );

		// Check first whether the Atum Action Logs is already present in the db.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare(
			"SELECT id FROM $wpdb->prefix" . self::$logs_table . ' WHERE id = %s', // phpcs:ignore WordPress.DB.PreparedSQL.Prepared
			isset( $log_data['id'] ) ? $log_data['id'] : ''
		);
		// phpcs:enable

		$current_id = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		unset( $log_data['id'] );

		// Update row.
		if ( $current_id ) {

			$result = $wpdb->update(
				$wpdb->prefix . self::$logs_table,
				$log_data,
				array( 'id' => $current_id ),
				array(
					'%d', // user_id.
					'%s', // module.
					'%s', // source.
					'%d', // time.
					'%s', // entry.
					'%d', // read.
					'%d', // featured.
					'%d', // deleted.
					'%s', // data.
				),
				array( '%d' )
			);

		}
		// Insert row.
		else {

			$wpdb->insert(
				$wpdb->prefix . self::$logs_table,
				$log_data,
				array(
					'%d', // user_id.
					'%s', // module.
					'%s', // source.
					'%d', // time.
					'%s', // entry.
					'%d', // read.
					'%d', // featured.
					'%d', // deleted.
					'%s', // data.
				)
			);

			$result = $wpdb->insert_id;

			self::save_cache_data( $result, $log_data['entry'], $log_data['data'] );

		}

		do_action( 'atum/logs/after_save_log', $log_data, $result );

		return $result;

	}

	/**
	 * Save phrase log with locale
	 *
	 * @param int    $log_id
	 * @param string $slug
	 * @param array  $data
	 * @since 0.5.1
	 */
	public static function save_cache_data( $log_id, $slug, $data ) {

		global $wpdb;

		$table_cache     = $wpdb->prefix . 'atum_logs_cache';
		$table_cache_var = $wpdb->prefix . 'atum_logs_cache_var';
		$locale          = get_locale();

		if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT `slug` FROM `$table_cache` WHERE `slug` = '%1\$s' AND `locale` = '%2\$s';", $slug, $locale ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL

			$wpdb->insert(
				$table_cache,
				array(
					'slug'   => $slug,
					'locale' => $locale,
					'entry'  => LogEntry::get_text( $slug, TRUE ),
				),
				array(
					'%s',
					'%s',
					'%s',
				)
			);

		}

		$save_data = LogEntry::parse_params( $slug, $data, TRUE, TRUE );

		if ( ! empty( $save_data ) ) {
			foreach ( $save_data as $index => $sd ) {
				$wpdb->insert(
					$table_cache_var,
					array(
						'id_log' => $log_id,
						'data'   => $sd,
					),
					array(
						'%s',
						'%s',
					)
				);
			}
		}

	}

	/**
	 * Unlink a log
	 *
	 * @param int $log_id
	 *
	 * @return int|bool
	 * @since 0.0.1
	 */
	public static function delete_log( $log_id ) {

		global $wpdb;

		$table_log       = $wpdb->prefix . self::$logs_table;
		$table_cache_var = $wpdb->prefix . 'atum_logs_cache_var';

		$columns = array(
			'id' => $log_id,
		);

		$formats = array(
			'%d',
		);

		$deleted = $wpdb->delete( $table_log, $columns, $formats );

		// Also delete id_log cache.
		$wpdb->delete( $table_cache_var, [ 'id_log' => $log_id ], $formats );

		do_action( 'atum/logs/after_delete_log', compact( 'log_id' ) );

		return $deleted;

	}

	/**
	 * Delete all log in trash
	 *
	 * @return int|bool
	 * @since 0.5.1
	 */
	public static function empty_trash() {

		global $wpdb;

		$columns = array(
			'deleted' => 1,
		);

		$formats = array(
			'%d',
		);

		$deleted = $wpdb->delete( $wpdb->prefix . self::$logs_table, $columns, $formats );

		do_action( 'atum/logs/after_empty_trash', $deleted );

		return $deleted;

	}

	/**
	 * Delete all logs
	 *
	 * @param \DateTime|false $date_from
	 * @param \DateTime|false $date_to
	 * @return int|bool
	 * @since 0.5.1
	 */
	public static function delete_logs( $date_from = FALSE, $date_to = FALSE ) {

		global $wpdb;

		$criteria = [];
		$where    = '';

		if ( FALSE !== $date_from ) {
			$criteria[] = '`time` >= ' . $date_from->getTimestamp();
		}
		if ( FALSE !== $date_to ) {
			$criteria[] = '`time` <= ' . $date_to->getTimestamp();
		}

		if ( ! empty( $criteria ) ) {
			$where = ' WHERE ' . implode( ' AND ', $criteria );
		}

		$query = 'DELETE FROM ' . $wpdb->prefix . self::$logs_table . $where; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$deleted = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		do_action( 'atum/logs/after_delete_logs', $deleted );

		return $deleted;

	}

	/**
	 * Get all logs
	 *
	 * @return array
	 * @since 0.0.1
	 *
	 * @param array $args
	 * @param bool  $count
	 */
	public static function get_logs( $args, $count = FALSE ) {

		$cache_key_args = array(
			$args['action'] ?? '',
			$args['search_column'] ?? '',
			$args['s'] ?? '',
			$args['paged'] ?? '',
			$args['view'] ?? '',
			$count,
		);

		$cache_key = AtumCache::get_cache_key( 'get_logs', $cache_key_args );
		$data      = AtumCache::get_cache( $cache_key, ATUM_LOGS_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $data;
		}

		global $wpdb;

		$log_table       = $wpdb->prefix . self::$logs_table . ' lt ';
		$cache_table     = $wpdb->prefix . 'atum_logs_cache ct ';
		$cache_var_table = $wpdb->prefix . 'atum_logs_cache_var cvt ';

		if ( isset( $args['paged'] ) ) {
			$page     = absint( $args['paged'] );
			$per_page = $args['per_page'] ?? AtumHelpers::get_option( 'al_logs_per_page', AtumSettings::DEFAULT_POSTS_PER_PAGE );
			$offset   = ( $page - 1 ) * $per_page;
			$limit    = isset( $args['per_page'] ) ? ' LIMIT ' . $offset . ', ' . $per_page : '';
		}
		else {
			$limit = '';
		}
		$join = '';

		// Order.
		if ( ! empty( $args['orderby'] ) ) {

			$order_field = esc_attr( strtolower( $args['orderby'] ) );

			switch ( $order_field ) {
				case 'date':
					$order_field = 'time';
					break;
				case 'user':
					$order_field = 'user_id';
					break;
			}

			$order   = ( ! empty( $args['order'] ) && 'ASC' === strtoupper( $args['order'] ) ) ? 'ASC' : 'DESC';
			$orderby = " ORDER BY lt.$order_field $order";

		}
		else {

			$orderby = ' ORDER BY lt.`id` DESC';

		}

		// Search.
		if ( ! empty( $args['s'] ) ) {

			$search_term = stripslashes( $args['s'] );
			$sc          = sanitize_text_field( urldecode( stripslashes( $args['search_column'] ) ) );

			if ( ! empty( $args['search_column'] ) ) {

				switch ( $args['search_column'] ) {
					case 'source':
						$sources = Helpers::search_in_array( sanitize_text_field( $search_term ), self::get_sources() );
						$where   = " WHERE lt.`$sc` IN ('" . implode( "', '", $sources ) . "')";
						break;
					case 'module':
						$modules = Helpers::search_in_array( sanitize_text_field( $search_term ), self::get_modules() );
						$where   = " WHERE lt.`$sc` IN ('" . implode( "', '", $modules ) . "')";
						break;
					case 'user':
						$users = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE display_name LIKE %s", '%' . sanitize_text_field( $search_term ) . '%' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.Prepared
						if ( str_contains( __( 'system', ATUM_LOGS_TEXT_DOMAIN ), strtolower( $search_term ) ) ) {
							$users[] = '0';
						}
						if ( ! empty( $users ) ) {
							$where = ' WHERE lt.`user_id` IN (' . implode( ',', $users ) . ')';
						}
						else {
							$where = ' WHERE 1';
						}
						break;
					case 'entry':
						$join     = ' LEFT JOIN ' . $cache_table . ' ON lt.entry = ct.slug';
						$max      = LogEntry::get_max_args();
						$field    = Helpers::query_replace( $cache_var_table, 'ct.entry', $max );
						$criteria = [];
						if ( str_contains( $search_term, '+' ) ) {
							$criteria = explode( '+', $search_term );
						}
						else {
							$criteria[] = $search_term;
						}
						$where = '';
						foreach ( $criteria as $crt ) {
							if ( 0 === strlen( $where ) ) {
								$where .= ' WHERE ';
							}
							else {
								$where .= ' AND ';
							}
							$where .= 'REPLACE( ' . $field . ",
								'%s',
								(SELECT cvt.data FROM $cache_var_table WHERE cvt.id_log = lt.id LIMIT 1 OFFSET 0)
					        ) LIKE '%" . trim( sanitize_text_field( $crt ) ) . "%' ";
						}
						break;
					case 'sku':
						$criterias    = [];
						$products_ids = $wpdb->get_col( "SELECT p.ID FROM wp_posts p LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id
            				WHERE p.post_type IN ('product', 'product_variation' ) AND pm.meta_key = '_sku' AND pm.meta_value LIKE '%$search_term%';" ); // Ensure the product exists, otherwise return empty
						foreach ( $products_ids as $product_id ) {
							$p_length    = strlen( (string) $product_id );
							$criterias[] = "lt.`data` LIKE '%s:10:\"product_id\";i:$product_id;%' OR lt.`data` LIKE '%s:10:\"product_id\";s:$p_length:\"$product_id\";%'";
						}
						if ( ! empty( $criterias ) ) {
							$where = ' WHERE (' . implode( ' OR ', $criterias ) . ') ';
						}
						else {
							$where = ' WHERE 1';
						}
						break;
					default:
						if ( str_contains( $search_term, '+' ) ) {
							$criteria = explode( '+', $search_term );
						}
						else {
							$criteria[] = $search_term;
						}
						$where = '';
						foreach ( $criteria as $crt ) {
							if ( 0 === strlen( $where ) ) {
								$where .= ' WHERE ';
							}
							else {
								$where .= ' AND ';
							}
							$where .= "lt.`$sc` LIKE '%" . sanitize_text_field( trim( $crt ) ) . "%'";
						}
						break;
				}

			} else {
				$join     = ' LEFT JOIN ' . $cache_table . ' ON lt.entry = ct.slug';
				$max      = LogEntry::get_max_args();
				$field    = Helpers::query_replace( $cache_var_table, 'ct.entry', $max );
				$criteria = [];
				if ( str_contains( $search_term, '+' ) ) {
					$criteria = explode( '+', $search_term );
				}
				else {
					$criteria[] = $search_term;
				}
				$where = '';
				foreach ( $criteria as $crt ) {
					if ( 0 === strlen( $where ) ) {
						$where .= ' WHERE ';
					}
					else {
						$where .= ' AND ';
					}

					$where .= 'REPLACE( ' . $field . ", '%s',
							(SELECT cvt.data FROM $cache_var_table WHERE cvt.id_log = lt.id LIMIT 1 OFFSET 0)
				        ) LIKE '%" . sanitize_text_field( trim( $crt ) ) . "%' ";
				}
			}

		}
		else {
			$where = ' WHERE 1';
		}

		// Step filter.
		if ( ! empty( $args['step_filter'] ) && 'all' !== $args['step_filter'] ) {
			if ( str_contains( $args['step_filter'], '/' ) ) {
				list( $source, $module ) = explode( '/', $args['step_filter'] );
			} else {
				$source = $args['step_filter'];
			}

			if ( isset( $source ) && strlen( $source ) > 0 ) {
				$where .= " AND lt.`source` = '$source'";
			}
			if ( isset( $module ) && strlen( $module ) > 0 ) {
				$where .= " AND lt.`module` = '$module'";
			}
		}

		// Deleted.
		if ( ! empty( $args['view'] ) && 'deleted' === $args['view'] ) {

			$where .= ' AND lt.`deleted` = 1';

		}
		else {

			$where .= ' AND lt.`deleted` = 0';

		}

		// View.
		if ( ! empty( $args['view'] ) && 'unread' === $args['view'] ) {

			$where .= ' AND lt.`read` = 0';

		}
		elseif ( ! empty( $args['view'] ) && 'read' === $args['view'] ) {

			$where .= ' AND lt.`read` = 1';

		}
		elseif ( ! empty( $args['view'] ) && 'featured' === $args['view'] ) {

			$where .= ' AND lt.`featured` = 1';

		}

		if ( $count ) {
			$data = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $log_table . $join . $where . ';' ) ?: 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		else {
			$data = $wpdb->get_results( 'SELECT DISTINCT lt.* FROM ' . $log_table . $join . $where . $orderby . $limit . ';' ) ?: []; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		AtumCache::set_cache( $cache_key, $data, ATUM_LOGS_TEXT_DOMAIN );

		return $data;

	}

	/**
	 * Returns a list within the values of a given field
	 *
	 * @param string       $field
	 * @param string|false $criteria_field
	 * @param string|false $criteria_value
	 *
	 * @return array
	 * @since 0.0.1
	 */
	public static function get_values_list( $field, $criteria_field = FALSE, $criteria_value = FALSE ) {

		global $wpdb;

		$log_table = $wpdb->prefix . self::$logs_table;

		if ( FALSE !== $criteria_field && FALSE !== $criteria_value ) {
			$where = "WHERE `$criteria_field` = '$criteria_value'";
		} else {
			$where = "WHERE $field IS NOT NULL ";
		}

		$query = "SELECT DISTINCT `$field` FROM $log_table $where ORDER BY `$field`";

		return $wpdb->get_col( $query ) ?: []; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	}

	/**
	 * Get logs from a given order.
	 *
	 * @since 1.1.9
	 *
	 * @param int $order_id
	 *
	 * @return array|bool|mixed|object|null
	 */
	public static function get_order_logs( $order_id ) {

		if ( ! $order_id ) {
			return [];
		}

		global $wpdb;

		$log_table  = $wpdb->prefix . self::$logs_table;
		$cache_key  = AtumCache::get_cache_key( 'order_logs_' . $order_id );
		$order_logs = AtumCache::get_cache( $cache_key, ATUM_LOGS_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			$order_length = strlen( (string) $order_id );
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$order_logs = $wpdb->get_results(
				"SELECT * FROM $log_table WHERE  `deleted` = 0 AND (`data` LIKE '%s:8:\"order_id\";i:$order_id;%' OR `data` LIKE '%s:8:\"order_id\";s:$order_length:\"$order_id\";%') ORDER BY `id` DESC;"
			) ?: [];
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			AtumCache::set_cache( $cache_key, $order_logs, ATUM_LOGS_TEXT_DOMAIN );
		}

		return $order_logs;

	}


	/************
	 * UTILITIES
	 ***********/

	/**
	 * Cleans data for db saving
	 *
	 * @param array $data
	 *
	 * @return array
	 * @since 0.3.1
	 */
	protected static function sanitize_data_for_db( $data ) {

		$sanitized_data = [];

		foreach ( $data as $key => $value ) {
			switch ( $key ) {
				case 'id':
				case 'user_id':
				case 'time':
				case 'read':
				case 'featured':
				case 'deleted':
					$sanitized_data[ $key ] = absint( $value );
					break;
				case 'module':
				case 'source':
				case 'entry':
					$sanitized_data[ $key ] = esc_sql( $value );
					break;
				case 'data':
					$sanitized_data[ $key ] = maybe_serialize( $value );
					break;
			}
		}

		return $sanitized_data;
	}


	/**
	 * Asks for the modules list of values
	 *
	 * @return array
	 * @since 0.3.1
	 */
	public static function get_modules() {

		return array(
			self::MOD_WC_PRODUCT_DATA    => __( 'Product Data', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_ATUM_PRODUCT_DATA  => __( 'Product Data', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_MI_PRODUCT_DATA    => __( 'Product Data', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_PL_PRODUCT_DATA    => __( 'Product Data', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_WC_ORDERS          => __( 'Orders', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_MI_ORDERS          => __( 'Orders', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_PL_ORDERS          => __( 'Orders', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_ST_ORDERS          => __( 'Stock Takes', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_PP_ORDERS          => __( 'Picking Lists', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_PURCHASE_ORDERS    => __( 'Purchase Orders', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_MI_PURCHASE_ORDERS => __( 'Purchase Orders', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_PO_PURCHASE_ORDERS => __( 'Purchase Orders', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_INVENTORY_LOGS     => __( 'Inventory Logs', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_MI_INVENTORY_LOGS  => __( 'Inventory Logs', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_WC_SETTINGS        => __( 'Settings', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_ATUM_SETTINGS      => __( 'Settings', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_MI_SETTINGS        => __( 'Settings', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_PL_SETTINGS        => __( 'Settings', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_AL_SETTINGS        => __( 'Settings', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_PO_SETTINGS        => __( 'Settings', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_ST_SETTINGS        => __( 'Settings', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_SUPPLIERS          => __( 'Suppliers', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_ADDONS             => __( 'Add-ons', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_LOCATIONS          => __( 'Locations', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_CATEGORIES         => __( 'Categories', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_COUPONS            => __( 'Coupons', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_STOCK_CENTRAL      => __( 'Stock Central', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_MI_STOCK_CENTRAL   => __( 'Stock Central', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_PL_STOCK_CENTRAL   => __( 'Stock Central', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_MAN_CENTRAL        => __( 'Manufacturing Central', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_MI_MAN_CENTRAL     => __( 'Manufacturing Central', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_EXPORT             => __( 'Export', ATUM_LOGS_TEXT_DOMAIN ),
			self::MOD_IMPORT             => __( 'Import', ATUM_LOGS_TEXT_DOMAIN ),
		);

	}

	/**
	 * Asks for a module name
	 *
	 * @param string $module
	 *
	 * @return string
	 * @since 0.3.1
	 */
	public static function get_module_name( $module ) {

		$modules = self::get_modules();

		return $modules[ $module ];

	}

	/**
	 * Getter for a list of valid log sources
	 *
	 * @return array
	 * @since 0.3.1
	 */
	public static function get_sources() {
		return [
			self::SRC_WC   => __( 'Woocommerce', ATUM_LOGS_TEXT_DOMAIN ),
			self::SRC_ATUM => __( 'ATUM', ATUM_LOGS_TEXT_DOMAIN ),
			self::SRC_MI   => __( 'ATUM Multi-Inventory', ATUM_LOGS_TEXT_DOMAIN ),
			self::SRC_PL   => __( 'ATUM Product Levels', ATUM_LOGS_TEXT_DOMAIN ),
			self::SRC_EP   => __( 'ATUM Export Pro', ATUM_LOGS_TEXT_DOMAIN ),
			self::SRC_AL   => __( 'ATUM Action Logs', ATUM_LOGS_TEXT_DOMAIN ),
			self::SRC_PO   => __( 'ATUM Purchase Orders', ATUM_LOGS_TEXT_DOMAIN ),
			self::SRC_ST   => __( 'ATUM Stock Takes', ATUM_LOGS_TEXT_DOMAIN ),
			self::SRC_PP   => __( 'ATUM Pick & Pack', ATUM_LOGS_TEXT_DOMAIN ),
		];
	}

	/**
	 * Asks for a source name
	 *
	 * @param string $source
	 *
	 * @return string
	 * @since 0.3.1
	 */
	public static function get_source_name( $source ) {

		$sources = self::get_sources();

		return $sources[ $source ];

	}

	/**
	 * Get the parent source of a module
	 *
	 * @since 0.5.1
	 * @param string $module
	 *
	 * @return string
	 */
	public static function get_module_parent( $module ) {
		switch ( $module ) {
			case self::MOD_WC_PRODUCT_DATA:
			case self::MOD_WC_ORDERS:
			case self::MOD_WC_SETTINGS:
			case self::MOD_CATEGORIES:
			case self::MOD_COUPONS:
				return self::SRC_WC;
			case self::MOD_MI_PRODUCT_DATA:
			case self::MOD_MI_ORDERS:
			case self::MOD_MI_PURCHASE_ORDERS:
			case self::MOD_MI_INVENTORY_LOGS:
			case self::MOD_MI_STOCK_CENTRAL:
			case self::MOD_MI_MAN_CENTRAL:
			case self::MOD_MI_SETTINGS:
				return self::SRC_MI;
			case self::MOD_PL_PRODUCT_DATA:
			case self::MOD_PL_ORDERS:
			case self::MOD_PL_STOCK_CENTRAL:
			case self::MOD_MAN_CENTRAL:
			case self::MOD_PL_SETTINGS:
				return self::SRC_PL;
			case self::MOD_EXPORT:
			case self::MOD_IMPORT:
				return self::SRC_EP;
			case self::MOD_AL_SETTINGS:
				return self::SRC_AL;
			case self::MOD_PO_SETTINGS:
			case self::MOD_PO_PURCHASE_ORDERS:
				return self::SRC_PO;
			case self::MOD_ST_SETTINGS:
			case self::MOD_ST_ORDERS:
				return self::SRC_ST;
			case self::MOD_PP_ORDERS:
				return self::SRC_PP;
			default:
				return self::SRC_ATUM;
		}
	}

	/**
	 * Checks dependency for a module log that depends from an addon
	 *
	 * @since 0.5.1
	 * @param string $entity
	 *
	 * @return bool
	 */
	public static function check_module_dependency( $entity ) {
		return self::check_source_dependency( self::get_module_parent( $entity ) );
	}

	/**
	 * Checks dependency for a source log that depends from an addon
	 *
	 * @since 0.5.1
	 * @param string $entity
	 *
	 * @return bool
	 */
	public static function check_source_dependency( $entity ) {
		switch ( $entity ) {
			case self::SRC_MI:
				return Addons::is_addon_active( 'multi_inventory' );
			case self::SRC_PL:
				return Addons::is_addon_active( 'product_levels' );
			case self::SRC_EP:
				return Addons::is_addon_active( 'export_pro' );
			case self::SRC_PO:
				return Addons::is_addon_active( 'purchase_orders' );
			case self::SRC_ST:
				return Addons::is_addon_active( 'stock_takes' );
			case self::SRC_PP:
				return Addons::is_addon_active( 'pick_pack' );
		}

		return TRUE;
	}

	/**
	 * Getter for the logs table name
	 *
	 * @return string
	 * @since 0.0.1
	 */
	public static function get_logs_table() {

		return self::$logs_table;
	}

}
