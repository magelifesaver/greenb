<?php
/**
 * Handle barcodes for WC Orders, POs and ILs
 *
 * @since       0.0.1
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumBarcodes
 */

namespace AtumBarcodes\Entities;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumCache;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\InventoryLogs\InventoryLogs;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumBarcodes\Inc\Globals;
use AtumBarcodes\Inc\Helpers;
use AtumST\StockTakes\StockTakes;
use AtumBarcodes\Integrations\StockTakes as STIntegration;
use Automattic\WooCommerce\Admin\Overrides\Order;


class Orders {

	/**
	 * The singleton instance holder
	 *
	 * @var Orders
	 */
	private static $instance;

	/**
	 * Query var used in searches
	 */
	const BARCODE_QUERY_VAR = 'barcode';

	/**
	 * Orders singleton constructor.
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		// Add the barcode input field to the barcodes meta box in orders.
		add_action( 'atum/barcodes_pro/after_barcodes_meta_box', array( $this, 'add_barcode_input' ), 10, 2 );

		// Save the orders barcodes.
		add_action( 'woocommerce_after_order_object_save', array( $this, 'after_order_save' ), 10, 2 );
		add_action( 'atum/order/after_object_save', array( $this, 'after_order_save' ), 10, 2 );

		if ( is_admin() ) {

			// Allow searching for barcodes on WC Orders list.
			add_filter( 'woocommerce_order_table_search_query_meta_keys', array( $this, 'allowed_wc_orders_search_query_meta_keys' ) ); // HPOS support.
			add_filter( 'woocommerce_shop_order_search_fields', array( $this, 'allowed_wc_orders_search_query_meta_keys' ) );
			add_filter( 'atum/orders/search_by_column/columns', array( $this, 'add_barcode_search_column' ) );
			add_filter( 'posts_where', array( $this, 'add_order_items_where' ), 100, 2 );
			add_filter( 'woocommerce_order_list_table_prepare_items_query_args', array( $this, 'order_query_args_hpos' ), 100 );

		}

	}

	/**
	 * Add the barcode input field to the barcodes meta box in orders
	 *
	 * @since 0.1.3
	 *
	 * @param string    			   			$barcode
	 * @param \WC_Order|AtumOrderModel|\WP_Post $order
	 */
	public function add_barcode_input( $barcode, $order ) {

		if ( $order && ! ( $order instanceof \WC_Order || $order instanceof AtumOrderModel || $order instanceof \WP_Post ) ) {
			return;
		}

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( $order instanceof \WP_Post ) {
			$order_type = $order->post_type;
		}
		else {
			$order_type = $order instanceof AtumOrderModel ? $order->get_post_type() : $order->get_type();
		}

		if (
			(
				$order && in_array( $order_type, [
					'shop_order',
					'shop_order_refund',
					PurchaseOrders::POST_TYPE,
					InventoryLogs::POST_TYPE,
				] ) &&
				Helpers::is_entity_supported( $order_type )
			) ||
			(
				// HPOS support.
				AtumHelpers::is_using_hpos_tables() && Helpers::is_entity_supported( 'woocommerce_page_wc-orders' ) &&
				function_exists( 'wc_get_page_screen_id' ) && wc_get_page_screen_id( 'shop-order' ) === $screen_id &&
				( ! empty( $_GET['id'] ) || ( ! empty( $_GET['action'] ) && 'new' === $_GET['action'] ) )
			)
		) {

			$order_id = 0;
			if ( $order ) {
				$order_id = $order instanceof \WP_Post ? $order->ID : $order->get_id();
			}
			elseif ( ! empty( $_GET['id'] ) ) {
				$order_id = absint( $_GET['id'] );
			}

			AtumHelpers::load_view( ATUM_BARCODES_PATH . 'views/meta-boxes/barcode-input', compact( 'barcode', 'order_id' ) );

		}

	}

	/**
	 * Save the ATUM barcodes after saving an order object.
	 *
	 * @since 0.1.6
	 *
	 * @param \WC_Order|Order|AtumOrderModel $order
	 * @param \WC_Order_Data_Store_CPT       $data_store
	 */
	public function after_order_save( $order, $data_store = NULL ) {

		if ( ! empty( $_POST['atum_barcode'] ) ) {

            remove_action( 'woocommerce_after_order_object_save', array( $this, 'after_order_save' ) );
            remove_action( 'atum/order/after_object_save', array( $this, 'after_order_save' ) );

            $barcode       = sanitize_text_field( $_POST['atum_barcode'] );
            $order_type    = $order instanceof AtumOrderModel ? $order->get_post_type() : $order->get_type();
            $barcode_found = self::get_order_id_by_barcode( $order->get_id(), $barcode, $order_type );

            if ( ! $barcode_found ) {

                // ATUM Orders.
                if ( is_callable( array( $order, 'set_atum_barcode' ) ) ) {
                    $order->set_atum_barcode( $barcode );
                    $order->save_meta();
                }
                // WC Order.
                elseif ( is_callable( array( $order, 'update_meta_data' ) ) ) {
                    $order->update_meta_data( Globals::ATUM_BARCODE_META_KEY, $barcode );
                }

            }
            else {
                // This will add a note to the order with the error.
                throw new \Exception( __( 'Invalid or duplicated barcode.', ATUM_TEXT_DOMAIN ) );
            }

		}

	}

	/**
	 * Allow searching WC orders by their barcode
	 *
	 * @since 0.2.0
	 *
	 * @param string[] $meta_keys
	 *
	 * @return string[]
	 */
	public function allowed_wc_orders_search_query_meta_keys( $meta_keys ) {

		$meta_keys[] = Globals::ATUM_BARCODE_META_KEY;

		return $meta_keys;

	}

	/**
	 * Add the barcode field to the searchOrdersByColumn list
	 *
	 * @since 0.2.0
	 *
	 * @param array $columns
	 */
	public function add_barcode_search_column( $columns ) {

		$columns[ self::BARCODE_QUERY_VAR ] = __( 'Barcode', ATUM_BARCODES_TEXT_DOMAIN );

		return $columns;

	}

	/**
	 * Modify the SQL WHERE for filtering the orders by barcodes used.
	 *
	 * @since 0.2.0
	 *
	 * @param string    $where    WHERE part of the sql query.
	 * @param \WP_Query $wp_query The current wp_query object.
	 *
	 * @return string
	 */
	public function add_order_items_where( $where, $wp_query ) {

		global $typenow, $wpdb;

		$order_types_ids = apply_filters( 'atum/orders/search_by_column/order_type_ids', AtumGlobals::get_order_type_id( '' ), 100 ); // Using the ATUM filter to get the order types IDs supported by the search by column component.

		if (
			in_array( $typenow, array_keys( $order_types_ids ), TRUE ) && ! empty( $_REQUEST['atum_search_column'] ) &&
			self::BARCODE_QUERY_VAR === $_REQUEST['atum_search_column'] && ! empty( $_REQUEST['atum_post_search'] )
		) {

			$barcode       = wc_clean( $_REQUEST['atum_post_search'] );
			$order_type_id = $order_types_ids[ $typenow ];

			if ( $barcode ) {

				$matching_orders = $this->search_orders_by_product_barcode( $barcode, $typenow );

				if ( ! empty( $matching_orders ) ) {
					$matching_orders = implode( ',', $matching_orders );

					$where .= " AND ( $wpdb->posts.ID IN ($matching_orders) )";
					$where  = apply_filters( 'atum/barcodes_pro/barcode_tracking/order_items_where', $where, $barcode, $order_type_id );
				}
				else {
					$where .= ' AND 1=-1'; // Do not return any order.
				}

			}

		}

		return $where;

	}

	/**
	 * Filters the WC HPOS orders list before querying them by batch numbers used
	 *
	 * @since 1.7.7
	 *
	 * @param array $order_query_args
	 *
	 * @return array
	 */
	public function order_query_args_hpos( $order_query_args ) {

		if (
			! empty( $_REQUEST['atum_search_column'] ) && self::BARCODE_QUERY_VAR === $_REQUEST['atum_search_column'] &&
			! empty( $_REQUEST['atum_post_search'] )
		) {

			$barcode = wc_clean( $_REQUEST['atum_post_search'] );

			if ( $barcode ) {

				$matching_orders = $this->search_orders_by_product_barcode( $barcode, 'shop_order' );

				if ( ! empty( $matching_orders ) ) {

					$order_query_args['field_query'] = array(
						array(
							'field' => 'id',
							'value' => $matching_orders,
							'type'  => 'NUMERIC',
						),
					);

				}
				else {

					$order_query_args['field_query'] = array(
						array(
							'field' => 'id',
							'value' => [ '-1' ], // Do not return any order.
						),
					);

				}

			}

		}

		return $order_query_args;

	}

	/**
	 * Search orders by inner product barcode
	 *
	 * @since 0.2.0
	 *
	 * @param string $barcode
	 * @param string $post_type
	 *
	 * @return int[]
	 */
	public function search_orders_by_product_barcode( $barcode, $post_type ) {

		global $wpdb;

		$order_type_id = AtumGlobals::get_order_type_id( $post_type );

		// Special case for Stock Takes. It's easier to override this method completely than to filter it.
		if ( Addons::is_addon_active( 'stock_takes' ) && StockTakes::POST_TYPE === $post_type ) {
			return STIntegration::search_st_by_product_barcode( $barcode, $order_type_id );
		}

		$order_items_table     = 'shop_order' === $post_type ? 'woocommerce_order_items' : AtumOrderPostType::ORDER_ITEMS_TABLE;
		$order_itemsmeta_table = 'shop_order' === $post_type ? 'woocommerce_order_itemmeta' : AtumOrderPostType::ORDER_ITEM_META_TABLE;

		$atum_product_data_table = $wpdb->prefix . AtumGlobals::ATUM_PRODUCT_DATA_TABLE;
		$barcode_like_term       = "%$barcode%";

		$join = apply_filters( 'atum/barcodes_pro/orders/orders_search_join', array(
			$wpdb->prepare( "LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND meta_key = %s)", Globals::ATUM_BARCODE_META_KEY ),
			"LEFT JOIN {$wpdb->prefix}{$order_items_table} oi ON (p.ID = oi.order_id)",
			"LEFT JOIN {$wpdb->prefix}{$order_itemsmeta_table} oim ON (oi.order_item_id = oim.order_item_id AND oim.meta_key IN ('_product_id', '_variation_id'))",
		) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$where = apply_filters( 'atum/barcodes_pro/orders/orders_search_where', array(
			$wpdb->prepare( 'p.post_type = %s', $post_type ),
			"oi.order_item_type = 'line_item'",
			$wpdb->prepare( "( oim.meta_value IN (
				SELECT product_id FROM $atum_product_data_table 
                WHERE barcode LIKE %s
			)
			OR oi.order_id LIKE %s
            OR pm.meta_value LIKE %s )", $barcode_like_term, $barcode_like_term, $barcode_like_term ),
		) );

		// Return all the order ids that includes a product with the barcode or their ID matches the barcode (if has no barcode saved) or have that specific barcode.
		// phpcs:disable WordPress.DB.PreparedSQL
		return apply_filters( 'atum/barcodes_pro/orders/matching_orders', $wpdb->get_col( "
			SELECT DISTINCT p.ID 
			FROM $wpdb->posts p\n" . implode( "\n", $join ) . "\nWHERE " . implode( ' AND ', $where )
		), $barcode, $order_type_id );
		// phpcs:enable

	}

    /**
     * Check if the passed barcode is being used by another order.
     *
     * @since 1.0.0
     *
     * @param int    $order_id   Order ID to exclude from the query.
     * @param string $barcode    Will be slashed to work around https://core.trac.wordpress.org/ticket/27421.
     * @param string $order_type The order post type.
     *
     * @return int
     */
    public static function get_order_id_by_barcode( $order_id, $barcode, $order_type ) {

        $cache_key      = AtumCache::get_cache_key( 'order_id_by_barcode', [ $order_id, $barcode, $order_type ] );
        $found_order_id = AtumCache::get_cache( $cache_key, ATUM_BARCODES_TEXT_DOMAIN, FALSE, $has_cache );

        if ( ! $has_cache ) {

            global $wpdb;

            // HPOS support.
            if ( AtumHelpers::is_using_hpos_tables() && in_array( $order_type, [ 'shop_order', 'shop_order_refund' ], TRUE ) ) {

                // phpcs:disable WordPress.DB.PreparedSQL
                $found_order_id = $wpdb->get_var( $wpdb->prepare( "
                    SELECT o.id
                    FROM {$wpdb->prefix}wc_orders o
                    LEFT JOIN {$wpdb->prefix}wc_orders_meta om ON ( o.id = om.order_id AND om.meta_key = %s )
                    WHERE (om.meta_value = %s or o.id = %s) AND o.id <> %d
                    LIMIT 1",
                    Globals::ATUM_BARCODE_META_KEY,
                    wp_slash( $barcode ),
                    wp_slash( $barcode ),
                    $order_id
                ) );
                // phpcs:enable

            }
            else {

                // phpcs:disable WordPress.DB.PreparedSQL
                $found_order_id = $wpdb->get_var( $wpdb->prepare( "
                    SELECT p.ID
                    FROM $wpdb->posts p
                    LEFT JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id AND pm.meta_key = %s )
                    WHERE p.post_status != 'trash' AND (pm.meta_value = %s OR p.ID = %s) AND 
                    p.ID <> %d AND p.post_type = %s
                    LIMIT 1",
                    Globals::ATUM_BARCODE_META_KEY,
                    wp_slash( $barcode ),
                    wp_slash( $barcode ),
                    $order_id,
                    $order_type
                ) );
                // phpcs:enable

            }

            AtumCache::set_cache( $cache_key, $found_order_id, ATUM_BARCODES_TEXT_DOMAIN );

        }

        return $found_order_id;

    }


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_BARCODES_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_BARCODES_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return Orders instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
