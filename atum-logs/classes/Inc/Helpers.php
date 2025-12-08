<?php
/**
 * Helper functions
 *
 * @package        AtumLogs
 * @subpackage     Inc
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2025 Stock Management Labs™
 *
 * @since          1.1.0
 */

namespace AtumLogs\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\InventoryLogs\InventoryLogs;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Supplier;
use AtumLogs\Models\LogEntry;
use AtumLogs\Models\LogModel;
use WC_Order_Item;
use \WC_DateTime;
use \DateTimeZone;


final class Helpers {

	/**
	 * Returns currently used Sources and their Modules
	 *
	 * @since 0.5.1
	 * @return array
	 */
	public static function current_source_module_map() {

		$sources = LogModel::get_values_list( 'source' );
		$src_map = array();

		foreach ( $sources as $source ) {

			$mods        = LogModel::get_values_list( 'module', 'source', $source );
			$modules     = array();
			$source_name = LogModel::get_source_name( $source );

			foreach ( $mods as $mod ) {
				$modules[] = [
					'slug' => $source . '/' . $mod,
					'name' => $source_name . ': ' . LogModel::get_module_name( $mod ),
				];
			}

			$src_map[] = [
				'slug'    => $source,
				'name'    => $source_name,
				'modules' => $modules,
			];
		}

		return $src_map;
	}

	/**
	 * Builds a dynamic dropdown for ListTable filtering
	 *
	 * @param string $selected
	 * @param string $class
	 *
	 * @return string
	 * @since 0.0.1
	 */
	public static function log_listtable_step_source_dropdown( $selected = '', $class = 'dropdown_log_source' ) {

		$data  = self::current_source_module_map();
		$extra = array();

		$output  = '<select id="atum-step-filter" name="step_filter" class="atum-enhanced-select auto-filter ' . $class . '">';
		$output .= '<option value="all" selected="selected">' . esc_html__( 'Show all', ATUM_LOGS_TEXT_DOMAIN ) . '</option>';

		if ( str_contains( $selected, '/' ) ) {
			list( $source, ) = explode( '/', $selected );
		} else {
			$source = $selected;
		}

		foreach ( $data as $dt ) {
			$output .= '<option value="' . $dt['slug'] . '" ' . selected( $selected, $dt['slug'], FALSE ) . ' data-type="source">' . esc_attr( $dt['name'] ) . '</option>';
			if ( $source === $dt['slug'] ) {
				$extra = $dt['modules'];
			}
		}
		if ( ! empty( $extra ) ) {
			foreach ( $extra as $et ) {
				$output .= '<option value="' . $et['slug'] . '" ' . selected( $selected, $et['slug'], FALSE ) . ' data-type="module">' . esc_attr( $et['name'] ) . '</option>';
			}
		}

		$output .= '</select>';

		return $output;

	}

	/**
	 * Marks a log as featured
	 *
	 * @param int $id
	 *
	 * @since 0.2.1
	 * @throws \Exception
	 */
	public static function mark_featured( $id ) {

		$log                  = LogModel::get_log_data( $id );
		$log_data             = json_decode( wp_json_encode( $log ), TRUE );
		$log_data['featured'] = 1;

		LogModel::save_log( $log_data );
	}

	/**
	 * Unmarks a log as featured
	 *
	 * @param int $id
	 *
	 * @since 0.2.1
	 * @throws \Exception
	 */
	public static function unmark_featured( $id ) {

		$log                  = LogModel::get_log_data( $id );
		$log_data             = json_decode( wp_json_encode( $log ), TRUE );
		$log_data['featured'] = 0;

		LogModel::save_log( $log_data );
	}

	/**
	 * Marks a log as read
	 *
	 * @param int $id
	 *
	 * @since 0.2.1
	 * @throws \Exception
	 */
	public static function mark_read( $id ) {

		$log              = LogModel::get_log_data( $id );
		$log_data         = json_decode( wp_json_encode( $log ), TRUE );
		$log_data['read'] = 1;

		LogModel::save_log( $log_data );
	}

	/**
	 * Unmarks a log as read
	 *
	 * @param int $id
	 *
	 * @since 0.2.1
	 * @throws \Exception
	 */
	public static function unmark_read( $id ) {

		$log              = LogModel::get_log_data( $id );
		$log_data         = json_decode( wp_json_encode( $log ), TRUE );
		$log_data['read'] = 0;

		LogModel::save_log( $log_data );
	}

	/**
	 * Deletes a log
	 *
	 * @param int $id
	 *
	 * @since 0.2.1
	 * @throws \Exception
	 */
	public static function delete_logs( $id ) {

		$log                 = LogModel::get_log_data( $id );
		$log_data            = json_decode( wp_json_encode( $log ), TRUE );
		$log_data['deleted'] = 1;

		LogModel::save_log( $log_data );
	}

	/**
	 * Undeletes a log
	 *
	 * @param int $id
	 *
	 * @since 0.2.1
	 * @throws \Exception
	 */
	public static function undelete_logs( $id ) {

		$log                 = LogModel::get_log_data( $id );
		$log_data            = json_decode( wp_json_encode( $log ), TRUE );
		$log_data['deleted'] = 0;

		LogModel::save_log( $log_data );
	}

	/**
	 * Permanently removes a log
	 *
	 * @param int $id
	 *
	 * @since 0.3.1
	 */
	public static function erase_logs( $id ) {

		LogModel::delete_log( $id );
	}

	/**
	 * Permanently removes all logs in trash
	 *
	 * @since 0.5.1
	 */
	public static function empty_trash() {

		LogModel::empty_trash();
	}

	/**
	 * Returns the name of type from an order item
	 *
	 * @param WC_Order_Item $item
	 *
	 * @return string
	 * @since 0.3.1
	 */
	public static function get_order_item_type( $item ) {

		$type_name = '';

		if ( $item instanceof WC_Order_Item ) {

			switch ( $item->get_type() ) {
				case 'line_item':
					$type_name = __( 'Product', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case 'fee':
					$type_name = __( 'Fee', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case 'shipping':
					$type_name = __( 'Shipping Cost', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case 'tax':
					$type_name = __( 'Tax', ATUM_LOGS_TEXT_DOMAIN );
					break;
			}
		}

		return $type_name;
	}

	/**
	 * Returns post data to log
	 *
	 * @return array
	 * @since 0.5.1
	 */
	public static function get_wp_post_datas() {
		return array(
			'post_content',
			'post_excerpt',
		);
	}

	/**
	 * Returns coupon data to log
	 *
	 * @return array
	 *
	 * @since 0.5.1
	 */
	public static function get_woocommerce_coupon_data() {
		return array(
			'code',
			'amount',
			'date_expires',
			'discount_type',
			'description',
			'usage_count',
			'individual_use',
			'product_ids',
			'exclude_product_ids',
			'excluded_product_ids',
			'usage_limit',
			'usage_limit_per_user',
			'limit_usage_to_x_items',
			'free_shipping',
			'product_categories',
			'exclude_product_categories',
			'excluded_product_categories',
			'exclude_sale_items',
			'minimum_amount',
			'maximum_amount',
			'customer_email',
			'email_restrictions',
		);
	}

	/**
	 * Returns order metas used by woocommerce
	 *
	 * @return array
	 * @since 0.3.1
	 */
	public static function get_woocommerce_order_metas() {

		return array(
			'order_status',
			'_customer_provided_note',
			'_customer_user',
			'_order_key',
			'_currency',
			'_billing_first_name',
			'_billing_last_name',
			'_billing_company',
			'_billing_address_1',
			'_billing_address_2',
			'_billing_city',
			'_billing_state',
			'_billing_postcode',
			'_billing_country',
			'_billing_email',
			'_billing_phone',
			'_shipping_first_name',
			'_shipping_last_name',
			'_shipping_company',
			'_shipping_address_1',
			'_shipping_address_2',
			'_shipping_city',
			'_shipping_state',
			'_shipping_postcode',
			'_shipping_country',
			'_cart_discount',
			'_cart_discount_tax',
			'_order_shipping',
			'_order_shipping_tax',
			'_order_tax',
			'_order_total',
			'_payment_method',
			'_payment_method_title',
			'_transaction_id',
			'_customer_ip_address',
			'_customer_user_agent',
			'_created_via',
			'_order_version',
			'_prices_include_tax',
			'_date_completed',
			'_date_paid',
			'_payment_tokens',
			'_download_permissions_granted',
		);
	}

	/**
	 * Returns product metas used by woocommerce
	 *
	 * @return array
	 * @since 0.3.1
	 */
	public static function get_woocommerce_product_metas() {

		return array(
			'_visibility',
			'_sku',
			'_regular_price',
			'_sale_price',
			'_sale_price_dates_from',
			'_sale_price_dates_to',
			'total_sales',
			'_tax_status',
			'_tax_class',
			'_manage_stock',
			'_stock',
			'_stock_status',
			'_backorders',
			'_low_stock_amount',
			'_sold_individually',
			'_weight',
			'_length',
			'_width',
			'_height',
			'_upsell_ids',
			'_crosssell_ids',
			'_purchase_note',
			'_default_attributes',
			'_product_attributes',
			'_virtual',
			'_downloadable',
			'_download_limit',
			'_download_expiry',
			'_featured',
			'_downloadable_files',
			'_wc_average_rating',
			'_wc_review_count',
			'_variation_description',
			'_thumbnail_id',
			'_file_paths',
			'_product_image_gallery',
			'_product_version',
			'_wp_old_slug',
			'_edit_last',
			'product_type',
			'_menu_order',
			'_reviews_allowed',
		);
	}

	/**
	 * Returns variable product metas used by woocommerce
	 *
	 * @return array
	 * @since 0.3.1
	 */
	public static function get_woocommerce_var_product_metas() {

		return array(
			'_menu_order',
			'_sku',
			'_enabled',
			'_regular_price',
			'_sale_price',
			'_sale_price_dates_from',
			'_sale_price_dates_to',
			'_stock',
			'_backorders',
			'_stock_status',
			'_weight',
			'_length',
			'_width',
			'_height',
			'_shipping_class',
			'_tax_class',
			'_description',
		);

	}

	/**
	 * Returns inventory logs metas used by atum
	 *
	 * @return array
	 * @since 1.0.8
	 */
	public static function get_atum_inventory_logs_metas() {
		return array(
			'atum_order_type',
			'status',
			'order',
			'description',
			'date_created',
			'atum_order_note',
			'reservation_date',
			'return_date',
			'damage_date',
			'shipping_company',
			'custom_name',
		);
	}

	/**
	 * Returns product metas used by atum
	 *
	 * @return array
	 * @since 0.3.1
	 */
	public static function get_atum_product_metas() {

		return array(
			'purchase_price',
			'supplier_id',
			'supplier_sku',
			'atum_controlled',
			'out_stock_date',
			'out_stock_threshold',
			'inbound_stock',
			'stock_on_hold',
			'sold_today',
			'sales_last_days',
			'reserved_stock',
			'customer_returns',
			'warehouse_damage',
			'lost_in_post',
			'other_logs',
			'out_stock_days',
			'lost_sales',
			'has_location',
			'atum_stock_status',
			'restock_status',
		);
	}

	/**
	 * Returns supplier metas used by atum
	 *
	 * @return array
	 * @since 1.0.8
	 */
	public static function get_atum_supplier_metas() {

		return array(
			'id',
			'name',
			'code',
			'tax_number',
			'phone',
			'fax',
			'website',
			'ordering_url',
			'general_email',
			'ordering_email',
			'description',
			'currency',
			'address',
			'city',
			'country',
			'state',
			'zip_code',
			'assigned_to',
			'location',
			'discount',
			'tax_rate',
			'lead_time',
			'delivery_terms',
			'days_to_cancel',
			'cancelation_policy',
		);
	}

	/**
	 * Returns product metas used by atum
	 *
	 * @return array
	 * @since 0.3.1
	 */
	public static function get_atum_var_product_metas() {

		return array(
			'purchase_price',
			'out_stock_threshold',
			'supplier',
			'supplier_sku',
		);
	}

	/**
	 * Returns product metas used by multi-inventory
	 *
	 * @return array
	 * @since 0.3.1
	 */
	public static function get_mi_product_metas() {

		return array(
			'_multi_inventory',
			'_inventory_iteration',
			'_expirable_inventories',
			'_price_per_inventory',
			'_inventory_sorting_mode',
			'_selectable_inventories',
			'_selectable_inventories_mode',
			'_show_write_off_inventories',
			'_show_out_of_stock_inventories',
			'_low_stock_threshold_by_inventory',
		);
	}

	/**
	 * Returns product metas used by product levels
	 *
	 * @return array
	 * @since 0.3.1
	 */
	public static function get_levels_product_metas() {

		return array(
			'minimum_threshold',
			'available_to_purchase',
			'selling_priority',
			'calculated_stock',
			'bom_sellable',
		);
	}

	/**
	 * Returns PO metas
	 *
	 * @since 1.2.1
	 *
	 * @return array
	 */
	public static function get_purchase_order_metas() {
		return array(
			'date',
			'currency',
			'description',
			'multiple_suppliers',
			'supplier',
			'date_expected',
			'status',
		);
	}

	/**
	 * Returns PO metas
	 *
	 * @since 1.2.1
	 *
	 * @return array
	 */
	public static function get_purchase_order_pro_metas() {
		return array(
			'date',
			'currency',
			'description',
			'multiple_suppliers',
			'supplier',
			'date_expected',
			'status',
			'customer_name',
			'delivery_date',
			'delivery_terms',
			'delivery_to_warehouse',
			'email_template',
			'number',
			'pdf_template',
			'requisitioner',
			'sales_order_number',
			'set_default_delivery_terms',
			'set_default_description',
			'ships_from',
			'ship_via',
			'supplier_code',
			'supplier_currency',
			'supplier_discount',
			'supplier_reference',
			'supplier_reference',
			'supplier_tax_rate',
			'warehouse',
			'purchaser_city',
			'purchaser_country',
			'purchaser_name',
			'purchaser_postal_code',
			'purchaser_state',
			'purchaser_address',
		);
	}

	/**
	 * Returns product metas used by wc + atum + addons
	 *
	 * @return array
	 * @since 0.3.1
	 */
	public static function get_product_metas() {

		$metas = [];

		foreach ( self::get_wp_post_datas() as $meta ) {
			$metas[ $meta ] = 'WP';
		}
		foreach ( self::get_woocommerce_product_metas() as $meta ) {
			$metas[ $meta ] = 'Woocommerce';
		}
		foreach ( self::get_atum_product_metas() as $meta ) {
			$metas[ $meta ] = 'ATUM';
		}
		foreach ( self::get_mi_product_metas() as $meta ) {
			$metas[ $meta ] = 'Multi-Inventory';
		}
		foreach ( self::get_levels_product_metas() as $meta ) {
			$metas[ $meta ] = 'Product Levels';
		}

		return apply_filters( 'atum/logs/get-product-meta-data-list', $metas );

	}

	/**
	 * Returns product metas used by wc + atum + addons
	 *
	 * @return array
	 * @since 0.3.1
	 */
	public static function get_var_product_metas() {

		$metas = [];

		foreach ( self::get_woocommerce_var_product_metas() as $meta ) {
			$metas[ $meta ] = 'Woocommerce';
		}
		foreach ( self::get_atum_var_product_metas() as $meta ) {
			$metas[ $meta ] = 'ATUM';
		}

		return apply_filters( 'atum/logs/get-var-product-meta-data-list', $metas );

	}

	/**
	 * Builds a link
	 *
	 * @return string
	 * @since 0.3.1
	 *
	 * @param string $url
	 * @param string $text
	 */
	public static function build_link( $url, $text ) {
		if ( FALSE === wp_http_validate_url( $url ) )
			return $text;

		return '<a href="' . $url . '">' . $text . '</a>';
	}

	/**
	 * Returns list of indexes whose values matches with a string
	 *
	 * @since 0.5.1
	 *
	 * @param string $needle
	 * @param array  $haystack
	 * @return array
	 */
	public static function search_in_array( $needle, $haystack ) {

		$result = array();

		foreach ( $haystack as $i => $val ) {

			if ( str_contains( strtolower( $val ), strtolower( $needle ) ) ) {
				$result[] = $i;
			}

		}

		return $result;
	}

	/**
	 * Converts a mixed and its elements into array
	 *
	 * @param mixed $a
	 *
	 * @return array type
	 * @since 0.3.1
	 */
	public static function o2a( $a ) {

		if ( is_object( $a ) ) {
			$a = (array) $a;
		}

		if ( is_array( $a ) ) {
			foreach ( $a as $i => $d ) {
				if ( is_object( $d ) || is_array( $d ) ) {
					$a[ $i ] = self::o2a( $d );
				}
			}
		}

		return $a;
	}

	/**
	 * Returns the indexes and values from $arr_new that are not contained in $arr_old
	 *
	 * @since 1.0.7
	 *
	 * @param array $arr_new
	 * @param array $arr_old
	 *
	 * @return array
	 */
	public static function get_array_diff( $arr_new = [], $arr_old = [] ) {

		$result = [];

		foreach ( $arr_new as $i => $v ) {

			if ( is_array( $v ) ) {

				$result[ $i ] = self::get_array_diff( $v, isset( $arr_old[ $i ] ) ? $arr_old[ $i ] : [] );

			} elseif ( ! isset( $arr_old[ $i ] ) || $v !== $arr_old[ $i ] ) {

				$result[ $i ] = [
					'new_value' => $v,
					'old_value' => isset( $arr_old[ $i ] ) ? $arr_old[ $i ] : FALSE,
				];

			}

		}

		if ( is_array( $arr_old ) ) {
			foreach ( $arr_old as $i => $v ) {

				if ( is_array( $v ) ) {

					$search = self::get_array_diff( isset( $arr_new[ $i ] ) ? $arr_new[ $i ] : [], $v );

					$result[ $i ] = isset( $result[ $i ] ) ? array_merge( $result[ $i ], $search ) : $result[ $i ];

				}
				elseif ( ! isset( $arr_new[ $i ] ) || $v !== $arr_new[ $i ] ) {

					if ( ! isset( $result[ $i ] ) ) {
						$result[ $i ] = [
							'new_value' => isset( $arr_new[ $i ] ) ? $arr_new[ $i ] : FALSE,
							'old_value' => $v,
						];
					}
				}

			}
		}

		return $result;

	}

	/**
	 * Generates a multi-replace param for query criteria
	 *
	 * @since 0.5.1
	 *
	 * @param string $table
	 * @param string $field
	 * @param int    $num
	 *
	 * @return string
	 */
	public static function query_replace( $table, $field, $num = 0 ) {

		$query = ' REPLACE( ';

		if ( 1 === $num ) {
			$query .= $field . ', ';
		} else {
			$query .= self::query_replace( $table, $field, $num - 1 ) . ', ';
		}
		$query .= "'%$num\$s', ";
		$query .= "IFNULL( ( SELECT cvt.data FROM $table WHERE cvt.id_log = lt.id LIMIT 1 OFFSET " . ( $num - 1 ) . "), '' ) ";
		$query .= ')';

		return $query;
	}

	/**
	 * Search for updates in Order details
	 *
	 * @since 1.0.7
	 *
	 * @param \WC_Order $order
	 * @param array     $old_metas
	 *
	 * @return array
	 */
	public static function check_order_details( $order, $old_metas ) {

		$updates = [];

		if ( empty( $old_metas ) ) {
			return [];
		}

		foreach ( self::get_woocommerce_order_metas() as $meta ) {

			if ( 'order_status' === $meta ) {
				$new_value = $order->get_status();
			}
			elseif ( '_customer_provided_note' === $meta ) {
				$new_value = $order->get_customer_note();
			}
			elseif ( method_exists( $order, 'get_' . $meta ) ) {
				$new_value = $order->{"get_$meta"}();
			}
			elseif ( method_exists( $order, 'get' . $meta ) ) {
				$new_value = $order->{"get$meta"}();
			}
			else {
				$new_value = get_post_meta( $order->get_id(), $meta, TRUE );
			}

			/**
			 * Variable definition
			 *
			 * @var \WC_DateTime $new_value
			 * @var \WC_DateTime $old_value
			 */
			$old_value = $old_metas[ $meta ];

			if ( $old_value instanceof \WC_DateTime && $new_value instanceof \WC_DateTime && $old_value->getTimestamp() === $new_value->getTimestamp() ) {
				continue;
			}

			$equals = ( is_numeric( $new_value ) && floatval( $new_value ) === floatval( $old_metas[ $meta ] ) ) ? TRUE : FALSE;

			if ( $old_metas[ $meta ] !== $new_value && ! $equals ) {

				if ( '_payment_method' === $meta || '_payment_method_title' === $meta ) {

					if ( $payment_method_checked ) {
						continue;
					}

					$meta = '_payment_method';
					$oval = [
						'title'  => $old_metas['_payment_method_title'],
						'method' => $old_metas['_payment_method'],
					];
					$nval = [
						'title'  => $order->get_payment_method_title(),
						'method' => $order->get_payment_method(),
					];

					$payment_method_checked = TRUE;

				}
				elseif ( '_download_permissions_granted' === $meta ) {

					$reg = FALSE;
					foreach ( $order->get_items() as $item ) {

						/**
						 * Variable definition
						 *
						 * @var \WC_Order_Item_Product $item
						 */
						$product = $item->get_product();

						if ( $product instanceof \WC_Product && $product->is_downloadable() ) {
							$reg = TRUE;
							break;
						}
					}
					if ( ! $reg ) {
						continue;
					}
					$oval = $old_metas[ $meta ];
					$nval = $new_value;
				}
				elseif ( '_customer_user' === $meta ) {
					$ousr = get_userdata( $old_metas[ $meta ] );
					$nusr = get_userdata( $new_value );
					$oval = [
						'id'   => $old_metas[ $meta ],
						'name' => empty( $ousr ) ? FALSE : $ousr->display_name,
					];
					$nval = [
						'id'   => $new_value,
						'name' => empty( $nusr ) ? FALSE : $nusr->display_name,
					];
				}
				else {
					$oval = $old_metas[ $meta ];
					$nval = $new_value;
				}

				$updates[ $meta ] = [
					'field'     => $meta,
					'old_value' => $oval,
					'new_value' => $nval,
				];

			}
		}

		return $updates;
	}

	/**
	 * Log a change in a Purchase Order details
	 *
	 * @since 1.0.8
	 *
	 * @param int    $order_id
	 * @param string $field
	 * @param mixed  $old_value
	 * @param mixed  $new_value
	 *
	 * @throws \Exception
	 */
	public static function maybe_log_purchase_order_detail( $order_id, $field, $old_value, $new_value ) {

		if ( ( empty( $old_value ) && empty( $new_value ) ) || $old_value === $new_value ) {
			return;
		}

		if ( 'currency' === $field && empty( $new_value ) ) {
			return;
		}

		$old_value = apply_filters( 'atum/logs/parse_field_value', $old_value, $field );
		$new_value = apply_filters( 'atum/logs/parse_field_value', $new_value, $field );

		if ( 'supplier' === $field ) {
			if ( ! empty( $old_value ) ) {
				$supplier  = new Supplier( $old_value );
				$old_value = [
					'id'   => $old_value,
					'name' => $supplier->name,
				];
			}
			if ( ! empty( $new_value ) ) {
				$supplier  = new Supplier( $new_value );
				$new_value = [
					'id'   => $new_value,
					'name' => $supplier->name,
				];
			}
		}

		$log_data = [
			'source' => LogModel::SRC_ATUM,
			'module' => LogModel::MOD_PURCHASE_ORDERS,
			'data'   => [
				'order_id'   => $order_id,
				'order_name' => 'PO#' . $order_id,
				'field'      => $field,
				'old_value'  => $old_value,
				'new_value'  => $new_value,
			],
			'entry'  => 'status' === $field ? LogEntry::ACTION_PO_EDIT_STATUS : LogEntry::ACTION_PO_EDIT_DATA,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log a change in an Inventory Log details
	 *
	 * @since 1.0.8
	 *
	 * @param int    $order_id
	 * @param string $field
	 * @param mixed  $old_value
	 * @param mixed  $new_value
	 *
	 * @throws \Exception
	 */
	public static function maybe_log_inventory_log_detail( $order_id, $field, $old_value, $new_value ) {
		if ( ( empty( $new_value ) && empty( $old_value ) ) || $old_value === $new_value ) {
			return;
		}

		if ( 'status' === $field ) {
			$entry = LogEntry::ACTION_IL_EDIT_STATUS;
		}
		else {
			$entry = LogEntry::ACTION_IL_EDIT_DATA;
		}

		$log_data = [
			'source' => LogModel::SRC_ATUM,
			'module' => LogModel::MOD_INVENTORY_LOGS,
			'data'   => [
				'order_id'   => $order_id,
				'order_name' => 'Log#' . $order_id,
				'field'      => $field,
				'old_value'  => $old_value,
				'new_value'  => $new_value,
			],
			'entry'  => $entry,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Logs item stock changes from an ATUM Order
	 *
	 * @param AtumOrderModel $order
	 * @param string         $action
	 *
	 * @since 0.3.1
	 * @throws \Exception
	 */
	public static function il_change_stock( $order, $action ) {

		if ( isset( $_POST['atum_order_item_ids'] ) ) {
			$atum_order_item_ids = array_map( 'absint', $_POST['atum_order_item_ids'] );
		}
		elseif ( isset( $_POST['order_item_ids'] ) ) {
			$atum_order_item_ids = array_map( 'absint', $_POST['order_item_ids'] );
		}
		else {
			return;
		}
		if ( isset( $_POST['quantities'] ) ) {
			$quantities = array_map( 'wc_stock_amount', $_POST['quantities'] );
		}
		elseif ( isset( $_POST['order_item_qty'] ) ) {
			$quantities = array_map( 'wc_stock_amount', $_POST['order_item_qty'] );
		}

		$atum_order_items = $order->get_items();

		if ( ! empty( $atum_order_items ) && ! empty( $quantities ) ) {

			foreach ( $atum_order_items as $item_id => $atum_order_item ) {

				/**
				 * Variable definition
				 *
				 * @var \WC_Product $product
				 */
				$product = $atum_order_item->get_product();

				if ( $product instanceof \WC_Product && $product->exists() && $product->managing_stock() && isset( $quantities[ $item_id ] ) && $quantities[ $item_id ] > 0 ) {

					$new_stock = $product->get_stock_quantity();
					$old_stock = ( 'increase' === $action ) ? $new_stock - $quantities[ $item_id ] : $new_stock + $quantities[ $item_id ];

					$log_data = [
						'source' => LogModel::SRC_ATUM,
						'module' => LogModel::MOD_INVENTORY_LOGS,
						'data'   => [
							'order_id'      => $order->get_id(),
							'order_name'    => 'Log#' . $order->get_id(),
							'order_item_id' => $item_id,
							'product_id'    => $product->get_id(),
							'product_name'  => $product->get_name(),
							'action'        => $action,
							'old_value'     => $old_stock,
							'new_value'     => $new_stock,
						],
						'entry'  => 'increase' === $action ? LogEntry::ACTION_IL_INCREASE_STOCK : LogEntry::ACTION_IL_DECREASE_STOCK,
					];
					if ( 'variation' === $product->get_type() ) {
						$log_data['data']['product_parent'] = $product->get_parent_id();
					}
					LogModel::maybe_save_log( $log_data );

				}
			}
		}
	}

	/**
	 * Common method for log when a note is removed from an Atum Order
	 *
	 * @since 1.0.8
	 *
	 * @param AtumOrderModel $atum_order
	 * @param \WP_Comment    $comment
	 *
	 * @throws \Exception
	 */
	public static function remove_atum_order_note( $atum_order, $comment ) {

		if ( InventoryLogs::POST_TYPE === $atum_order->get_post_type() ) {

			$mod   = LogModel::MOD_INVENTORY_LOGS;
			$name  = 'Log#' . $atum_order->get_id();
			$entry = LogEntry::ACTION_IL_DEL_NOTE;

		}
		elseif ( PurchaseOrders::POST_TYPE === $atum_order->get_post_type() ) {

			$mod   = LogModel::MOD_PURCHASE_ORDERS;
			$name  = 'PO#' . $atum_order->get_id();
			$entry = LogEntry::ACTION_PO_DEL_NOTE;

		} else {

			return;

		}

		$log_data = [
			'module' => $mod,
			'source' => LogModel::SRC_ATUM,
			'entry'  => $entry,
			'data'   => [
				'order_id'   => $atum_order->get_id(),
				'order_name' => $name,
				'note'       => [
					'comment_ID'      => $comment->comment_ID,
					'comment_content' => $comment->comment_content,
					'comment_author'  => $comment->comment_author,
				],
			],
		];
		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Stores previous value before saving stock levels
	 *
	 * @param \WC_Order $order
	 *
	 * @since 0.3.1
	 */
	public static function atum_orders_register_stock_levels( $order ) {

		$atum_order_items = $order->get_items();

		if ( ! empty( $atum_order_items ) ) {

			foreach ( $atum_order_items as $item_id => $atum_order_item ) {

				/**
				 * Variable definition
				 *
				 * @var \WC_Order_Item_Product $atum_order_item
				 */
				$product = $atum_order_item->get_product();

				/**
				 * Variable definition
				 *
				 * @var \WC_Product $product
				 */

				if ( $product instanceof \WC_Product && $product->exists() && $product->managing_stock() ) {

					$old_stock = $product->get_stock_quantity();

					$transient_key = AtumCache::get_transient_key( 'log_stock_level_' . $product->get_id() );
					AtumCache::set_transient( $transient_key, $old_stock, MINUTE_IN_SECONDS, TRUE );

				}
			}
		}
	}

	/**
	 * Retrieves previous stock levels values from a product.
	 *
	 * @since 1.0.8
	 *
	 * @param \WC_Product $product
	 */
	public static function check_product_stock_levels( $product ) {

		if ( ! $product instanceof \WC_Product || ! $product->exists() )
			return;

		$product_data = [
			'stock'  => $product->get_stock_quantity(),
			'status' => $product->get_stock_status(),
		];

		$transient_key_stocklevel = AtumCache::get_transient_key( 'log_product_stocklevels_' . $product->get_id() );
		AtumCache::set_transient( $transient_key_stocklevel, $product_data, MINUTE_IN_SECONDS, TRUE );

	}

	/**
	 * Logs stock levels changes of a product.
	 *
	 * @since 1.0.8
	 *
	 * @param \WC_Product $product
	 * @param int         $order_id
	 * @param bool        $calculated_stock
	 *
	 * @throws \Exception
	 */
	public static function log_product_stock_levels( $product, $order_id, $calculated_stock = FALSE ) {

		if ( ! $product instanceof \WC_Product || ! $product->exists() ) {
			return;
		}

		$new_data = [
			'stock'  => $product->get_stock_quantity(),
			'status' => $product->get_stock_status(),
		];

		$transient_key_stocklevel = AtumCache::get_transient_key( 'log_product_stocklevels_' . $product->get_id() );
		$old_data                 = AtumCache::get_transient( $transient_key_stocklevel, TRUE );

		if ( empty( $old_data ) || ( floatval( $new_data['stock'] ) === floatval( $old_data['stock'] ) && $new_data['status'] === $old_data['status'] ) )
			return;

		if ( FALSE === $calculated_stock ) {
			$product_data = [
				'order_id'     => $order_id,
				'order_name'   => '#' . $order_id,
				'product_id'   => $product->get_id(),
				'product_name' => $product->get_formatted_name(),
			];

			$source = LogModel::SRC_WC;
			$module = LogModel::MOD_WC_ORDERS;
			$entry  = floatval( $new_data['stock'] ) > floatval( $old_data['stock'] ) ? LogEntry::ACTION_WC_ORDER_STOCK_LVL : LogEntry::ACTION_WC_ORDER_CH_STOCK_LVL;
		} else {
			$product_data = [
				'id'   => $product->get_id(),
				'name' => $product->get_name(),
			];

			$source = LogModel::SRC_PL;
			$module = LogModel::MOD_PL_PRODUCT_DATA;
			$entry  = LogEntry::ACTION_PL_STOCK_LEVEL_BOM;
		}

		$product_data = array_merge( $product_data, [
			'old_stock'  => $old_data['stock'],
			'new_stock'  => $new_data['stock'],
			'old_status' => $old_data['status'],
			'new_status' => $new_data['status'],
		] );

		if ( 'variation' === $product->get_type() ) {
			$ivar                  = ( $calculated_stock ? '' : 'product_' ) . 'parent';
			$product_data[ $ivar ] = $product->get_parent_id();
		}

		$log_data = [
			'source' => $source,
			'module' => $module,
			'entry'  => $entry,
			'data'   => $product_data,
		];
		LogModel::maybe_save_log( $log_data );

		AtumCache::delete_transients( $transient_key_stocklevel );

	}

	/**
	 * Format log time
	 *
	 * @since 1.1.9
	 *
	 * @param string $log_time
	 *
	 * @return string
	 */
	public static function format_log_time( $log_time ) {

		$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ?: 'Y/m/d H:i:s';

		$date = new \DateTime();
		$date->setTimezone( new \DateTimeZone( 'GMT' ) );
		$date->setTimestamp( $log_time );

		// Show log date in WP Timezone.
		$tz = wp_timezone_string();
		$date->setTimezone( new \DateTimeZone( $tz ) );

		$show_date = $date->format( $date_format );

		if ( 'yes' === AtumHelpers::get_option( 'al_relative_date', 'yes' ) ) {
			$time = AtumHelpers::get_relative_date( $date->format( 'Y/m/d H:i:s' ) );
			$time = '<span class="tips" data-bs-placement="top" data-tip="' . $show_date . '">' . $time . '</span>';
		}
		else {
			$time = $show_date;
		}

		return $time;

	}

	/**
	 * Removes logs permanently from tool/cli.
	 *
	 * @since 1.2.0
	 *
	 * @param false|string $date_from
	 * @param false|string $date_to
	 */
	public static function delete_range_logs( $date_from = FALSE, $date_to = FALSE ) {

		if ( ! $date_from && ! $date_to ) {
			LogModel::delete_logs();
		}
		else {
			$date1 = $date_from ? new WC_DateTime( $date_from, new DateTimeZone( 'UTC' ) ) : FALSE;
			$date2 = $date_to ? new WC_DateTime( $date_to, new DateTimeZone( 'UTC' ) ) : FALSE;

			if ( $date1 && $date2 && $date1 > $date2 ) {
				return array(
					'error' => TRUE,
					'text'  => __( 'Invalid dates', ATUM_LOGS_TEXT_DOMAIN ),
				);
			}

			LogModel::delete_logs( $date1, $date2 );
		}
		return array(
			'error' => FALSE,
			'text'  => __( 'The specified logs were successfully deleted', ATUM_LOGS_TEXT_DOMAIN ),
		);
	}

}
