<?php
use Automattic\WooCommerce\Utilities\OrderUtil;
/**
 * Update post meta.
 *
 * @return void
 */
function lddfw_update_post_meta( $order_id, $key, $value, $save = false ) {

	if ( empty( $order ) ) {
		$order = wc_get_order( $order_id );

	}

	if ( $order ) {

		$order->update_meta_data( $key, $value );

		if ( $save ) {
			$order->save();
		}
	}

	lddfw_update_sync_order( $order_id, $key, $value );
}

/**
 * Delete post meta.
 *
 * @return void
 */
function lddfw_delete_post_meta( $order_id, $key, $save = false ) {

	$order = wc_get_order( $order_id );
	if ( $order ) {
		$order->delete_meta_data( $key );

		if ( $save ) {
			$order->save();
		}
	}
	lddfw_update_sync_order( $order_id, $key, '0' );
}

/**
 * Update a order row from sync table when a order is updated.
 *
 * @global object $wpdb
 * @param type $order_id
 */
function lddfw_update_sync_order( $order_id, $key, $value ) {
	global $wpdb;

	$column = '';
	switch ( $key ) {
		case 'lddfw_order_sort':
			$column = 'order_sort';
			break;
		case 'lddfw_delivered_date':
			$column = 'delivered_date';
			break;
		case 'lddfw_driverid':
			$column = 'driver_id';
			break;
		case 'lddfw_driver_commission':
			$column = 'driver_commission';
			break;
		case 'order_refund_amount':
			$column = 'order_refund_amount';
			break;
	}

	if ( '' !== $column ) {

		if ( ! lddfw_is_order_already_exists( $order_id ) ) {
			lddfw_insert_orderid_to_sync_order( $order_id );
		}

		$table_name = $wpdb->prefix . 'lddfw_orders';
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . $table_name . '
			SET ' . $column . ' = %s
			WHERE order_id = %s',
				$value,
				$order_id
			)
		);
	}
}


/**
 * Update order row in sync table.
 *
 * @global object $wpdb
 * @param type $order_id
 */
function lddfw_update_all_sync_order( $order ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'lddfw_orders';
	$store      = new LDDFW_Store();
	$seller_id  = $store->lddfw_order_seller( $order );
	$city       = ( ! empty( $order->get_shipping_city() ) ) ? $order->get_shipping_city() : $order->get_billing_city();
	$refund     = $order->get_total_refunded();
	$wpdb->query(
		$wpdb->prepare(
			'UPDATE ' . $table_name . '
	 SET
			driver_id   = %d,
			seller_id   = %d,
			order_total = %f,
			driver_commission = %f,
			delivered_date = %s,
			order_sort = %d,
			order_refund_amount = %f,
			order_shipping_amount = %f,
			order_shipping_city = %s,
			order_payment_method = %s,
			delivery_date = %s
	 WHERE order_id = %s',
			$order->get_meta( 'lddfw_driverid' ),
			$seller_id,
			$order->get_total(),
			$order->get_meta( 'lddfw_driver_commission' ),
			$order->get_meta( 'lddfw_delivered_date' ),
			$order->get_meta( 'lddfw_order_sort' ),
			$refund,
			$order->get_shipping_total(),
			$city,
			$order->get_payment_method(),
			$order->get_meta( '_lddfw_delivery_date' ),
			$order->get_id()
		)
	);
}


 /**
  * Delete  orders and from lddfw sync table when a order is deleted
  *
  * @param int $post_id
  */
function lddfw_admin_on_delete_order( $post_id ) {
	// Check if the post is a 'shop_order' type using a utility method.
	// Make sure OrderUtil::get_order_type() is compatible with HPOS or adjust accordingly.

	if ( 'shop_order' === OrderUtil::get_order_type( $post_id ) ) {
		// Delete the main order sync data.
		lddfw_delete_sync_order( $post_id );

		// Retrieve sub-orders. Adjust 'type' as necessary to match your data structure.
		$sub_orders = wc_get_orders(
			array(
				'parent' => $post_id,
				'return' => 'objects',
			)
		);

		// Check if there are any sub-orders and iterate over them if there are.
		if ( ! empty( $sub_orders ) ) {
			foreach ( $sub_orders as $order ) {
				// Assuming lddfw_delete_sync_order accepts the order ID and works with HPOS.
				lddfw_delete_sync_order( $order->get_id() );
			}
		}
	}
}





/**
 * Delete a order row from sync table when a order is deleted from WooCommerce.
 *
 * @global object $wpdb
 * @param type $order_id
 */
function lddfw_delete_sync_order( $order_id ) {
	global $wpdb;
	$wpdb->delete( $wpdb->prefix . 'lddfw_orders', array( 'order_id' => $order_id ) );
}

/**
 * Insert new order to sync table.
 *
 * @global object $wpdb
 * @param type $order_id
 */
function lddfw_insert_sync_order_by_id( $order_id ) {
	global $wpdb;
	$order = wc_get_order( $order_id );

	if ( lddfw_is_order_already_exists( $order_id ) ) {
		lddfw_update_all_sync_order( $order );
		return;
	}

	lddfw_insert_sync_order( $order );
}


/**
 * Check if an order with same id is exists in database
 *
 * @param  int order_id
 *
 * @return boolean
 */
function lddfw_is_order_already_exists( $id ) {
	global $wpdb;

	if ( ! $id || ! is_numeric( $id ) ) {
		return false;
	}

	$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM {$wpdb->prefix}lddfw_orders WHERE order_id=%d LIMIT 1", $id ) );

	return $order_id ? true : false;
}


/**
 * Insert a order row to sync table.
 *
 * @global object $wpdb
 * @param type $order_id
 */
function lddfw_insert_orderid_to_sync_order( $order_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'lddfw_orders';
	$wpdb->insert( $table_name, array( 'order_id' => $order_id ), array( '%d' ) );
}

/**
 * Insert a order row to sync table.
 *
 * @global object $wpdb
 * @param type $order_id
 */
function lddfw_insert_sync_order( $order ) {
	global $wpdb;
	$table_name   = $wpdb->prefix . 'lddfw_orders';
	$store        = new LDDFW_Store();
	$seller_id    = $store->lddfw_order_seller( $order );
	$city         = ( ! empty( $order->get_shipping_city() ) ) ? $order->get_shipping_city() : $order->get_billing_city();
	$order_date   = ( ! empty( $order->get_date_created() ) ) ? $order->get_date_created()->format( 'Y-m-d H:i:s' ) : '';
	$order_status = $order->get_status();
	// Make sure order status contains "wc-" prefix.
	if ( stripos( $order_status, 'wc-' ) === false ) {
		$order_status = 'wc-' . $order_status;
	}

	// Delete duplicate orders.
	lddfw_delete_sync_order( $order->get_id() );

	$wpdb->insert(
		$table_name,
		array(
			'order_id'              => $order->get_id(),
			'driver_id'             => $order->get_meta( 'lddfw_driverid' ),
			'seller_id'             => $seller_id,
			'order_total'           => $order->get_total(),
			'driver_commission'     => $order->get_meta( 'lddfw_driver_commission' ),
			'delivered_date'        => $order->get_meta( 'lddfw_delivered_date' ),
			'order_sort'            => $order->get_meta( 'lddfw_order_sort' ),
			'order_refund_amount'   => $order->get_total_refunded(),
			'order_shipping_amount' => $order->get_shipping_total(),
			'order_shipping_city'   => $city,
			'order_payment_method'  => $order->get_payment_method(),
			'delivery_date'         => $order->get_meta( '_lddfw_delivery_date' ),
		),
		array(
			'%d',
			'%d',
			'%d',
			'%f',
			'%f',
			'%s',
			'%d',
			'%f',
			'%f',
			'%s',
			'%s',
			'%s',
		)
	);
}


	/**
	 * Create drivers panel page.
	 *
	 * @return void
	 */
function lddfw_create_drivers_panel_page() {
	// Create drivers panel page for the first activation.
	if ( ! get_option( 'lddfw_delivery_drivers_page', false ) ) {
		$array   = array(
			'post_title'     => 'Delivery Driver App',
			'post_type'      => 'page',
			'post_name'      => 'driver',
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);
		$page_id = wp_insert_post( $array );
		update_option( 'lddfw_delivery_drivers_page', $page_id );
	}
}

	/**
	 * Create tracking page.
	 *
	 * @return void
	 */
function lddfw_create_tracking_page__premium_only() {
	if ( lddfw_fs()->is__premium_only() ) {
		if ( lddfw_fs()->is_plan( 'premium', true ) ) {
			// Create tracking page for the first activation.
			if ( ! get_option( 'lddfw_tracking_page', false ) ) {
				$array   = array(
					'post_title'     => 'Tracking',
					'post_type'      => 'page',
					'post_name'      => 'tracking',
					'post_status'    => 'publish',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				);
				$page_id = wp_insert_post( $array );
				update_option( 'lddfw_tracking_page', $page_id );
			}
		}
	}
}

	/**
	 * Create tracking table.
	 *
	 * @return void
	 */
function lddfw_delete_from_tracking_table__premium_only() {
	global $wpdb;
	$wpdb->query(
		'DELETE FROM ' . $wpdb->prefix . 'lddfw_tracking
		WHERE date < DATE_SUB(CURDATE(),INTERVAL 7 DAY)'
	);
}

	/**
	 * Create tracking table.
	 *
	 * @return void
	 */
function lddfw_create_tracking_table__premium_only() {
	global $wpdb;
	include_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'lddfw_tracking (
          id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          driver_id bigint(20) DEFAULT 0,
		  latitude varchar(200) DEFAULT NULL,
		  longitude varchar(200) DEFAULT NULL,
		  speed varchar(200) DEFAULT NULL,
		  date varchar(50) DEFAULT NULL,
          PRIMARY KEY (id),
          KEY driver_id (driver_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
	dbDelta( $sql );

	// Add option that tracking table has been created.
	update_option( 'lddfw_tracking_table', '2' );
}

	/**
	 * Create order sync table
	 *
	 * @return void
	 */
function lddfw_create_sync_table() {
	global $wpdb;
	include_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'lddfw_orders (
          id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          order_id bigint(20) DEFAULT 0,
          driver_id bigint(20) DEFAULT 0,
		  seller_id bigint(20) DEFAULT 0,
		  order_total decimal(19,4) DEFAULT 0,
		  order_refund_amount decimal(19,4) DEFAULT 0,
		  order_sort bigint(20) DEFAULT 0,
		  order_shipping_amount decimal(19,4) DEFAULT 0,
		  order_shipping_city varchar(200) DEFAULT NULL,
		  driver_commission decimal(19,4) DEFAULT 0,
		  delivered_date varchar(50) DEFAULT NULL,
		  order_payment_method varchar(200) DEFAULT NULL,
		  delivery_date varchar(50) DEFAULT NULL,
          PRIMARY KEY (id),
          KEY order_id (order_id),
          KEY driver_id (driver_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
	dbDelta( $sql );

	// Add order payment method column.
	$row = $wpdb->get_results(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = '" . $wpdb->prefix . "lddfw_orders' AND column_name = 'order_payment_method'"
	);

	if ( empty( $row ) ) {
		$wpdb->query( 'ALTER TABLE ' . $wpdb->prefix . 'lddfw_orders ADD order_payment_method varchar(200) DEFAULT NULL' );
	}

	// Add order delivery_date column.
	$row = $wpdb->get_results(
		"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = '" . $wpdb->prefix . "lddfw_orders' AND column_name = 'delivery_date'"
	);

	if ( empty( $row ) ) {
		$wpdb->query( 'ALTER TABLE ' . $wpdb->prefix . 'lddfw_orders ADD delivery_date varchar(50) DEFAULT NULL' );
	}

}

	/**
	 * Check plugin db
	 *
	 * @return void
	 */

function lddfw_update_db_check() {

	if ( lddfw_fs()->is__premium_only() ) {
		if ( lddfw_fs()->is_plan( 'premium', true ) ) {

			if ( '2' !== get_option( 'lddfw_tracking_table', '' ) ) {
				// Create tracking table.
				lddfw_create_tracking_table__premium_only();

				// Create cron job.
				if ( ! wp_next_scheduled( 'lddfw_daily_event' ) ) {
					wp_schedule_event( time(), 'daily', 'lddfw_daily_event' );
				}
			}
		}
	}

	if ( '3' === get_option( 'lddfw_sync_table', '' ) || '2' === get_option( 'lddfw_sync_table', '' ) || '' === get_option( 'lddfw_sync_table', '' ) ) {
		lddfw_create_sync_table();
		lddfw_sync_table();
	}
}

	/**
	 * Sync table
	 *
	 * @return void
	 */
function lddfw_sync_table() {
	global $wpdb;
	// If plugin has been upgraded we sync table once.

	// If lddfw_sync_table is empty we truncate the table.
	if ( '' === get_option( 'lddfw_sync_table', '' ) ) {
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}lddfw_orders" );
	}

	// If lddfw_sync_table is empty sync all data.
	if ( '' === get_option( 'lddfw_sync_table', '' ) ) {
		// Sync plugin data.
		if ( lddfw_is_hpos_enabled() ) {
			// Query adapted for HPOS-enabled environments.
			$wpdb->query(
				'
					INSERT INTO ' . $wpdb->prefix . 'lddfw_orders (
						order_id,
						driver_id,
						delivered_date,
						driver_commission,
						order_sort
					)
					SELECT wo.id,
						   wom.meta_value,
						   wom2.meta_value,
						   IFNULL(wom3.meta_value, 0),
						   IFNULL(wom4.meta_value, 0)
					FROM ' . $wpdb->prefix . 'wc_orders wo
					INNER JOIN ' . $wpdb->prefix . 'wc_orders_meta wom ON wo.id = wom.order_id AND wom.meta_key = \'lddfw_driverid\' AND wom.meta_value <> \'\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta wom2 ON wo.id = wom2.order_id AND wom2.meta_key = \'lddfw_delivered_date\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta wom3 ON wo.id = wom3.order_id AND wom3.meta_key = \'lddfw_driver_commission\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta wom4 ON wo.id = wom4.order_id AND wom4.meta_key = \'lddfw_order_sort\'
					GROUP BY wo.id'
			);
		} else {
			// Original query for non-HPOS environments.

			$wpdb->query(
				'
				insert into ' . $wpdb->prefix . 'lddfw_orders (
					order_id,
					driver_id,
					delivered_date,
					driver_commission,
					order_sort
				)
				select p.ID,
				pm.meta_value,
				pm2.meta_value,
				IFNULL ( pm3.meta_value , 0 ),
				IFNULL ( pm4.meta_value , 0)
				from ' . $wpdb->prefix . 'posts p
				inner join ' . $wpdb->prefix . 'postmeta pm on p.ID = pm.post_id and pm.meta_key = \'lddfw_driverid\' and pm.meta_value <> \'\'
				left join ' . $wpdb->prefix . 'postmeta pm2 on p.ID = pm2.post_id and pm2.meta_key = \'lddfw_delivered_date\'
				left join ' . $wpdb->prefix . 'postmeta pm3 on p.ID = pm3.post_id and pm3.meta_key = \'lddfw_driver_commission\'
				left join ' . $wpdb->prefix . 'postmeta pm4 on p.ID = pm4.post_id and pm4.meta_key = \'lddfw_order_sort\'
				group by p.ID'
			);
		}

		// Remove duplicate orders.
		$wpdb->query(
			'delete t1 from ' . $wpdb->prefix . 'lddfw_orders t1
				INNER JOIN ' . $wpdb->prefix . 'lddfw_orders t2
				WHERE
    			t1.id < t2.id AND
    			t1.order_id = t2.order_id'
		);

		// Sync order data.
		if ( lddfw_is_hpos_enabled() ) {
			// Query adapted for HPOS-enabled environments.
			$wpdb->query(
				'UPDATE ' . $wpdb->prefix . 'lddfw_orders o 
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders om_total ON o.order_id = om_total.id AND om_total.type = \'shop_order\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_order_stats os ON o.order_id = os.order_id
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders wco ON o.order_id = wco.parent_order_id AND wco.type = \'shop_order_refund\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta om_refund ON wco.id = om_refund.order_id AND om_refund.meta_key = \'_refund_amount\'
					SET
					o.order_total = IFNULL(om_total.total_amount, 0),
					o.order_shipping_amount = IFNULL(os.shipping_total, 0),
					o.order_refund_amount = (SELECT SUM(IFNULL(om_refund.meta_value, 0)) FROM ' . $wpdb->prefix . 'wc_orders_meta om_refund INNER JOIN ' . $wpdb->prefix . 'wc_orders wcor ON om_refund.order_id = wcor.id WHERE wcor.parent_order_id = o.order_id AND om_refund.meta_key = \'_refund_amount\')
				'
			);
		} else {
			// Original query for non-HPOS environments.

			$wpdb->query(
				'UPDATE ' . $wpdb->prefix . 'lddfw_orders o
				left join ' . $wpdb->prefix . 'postmeta pm4 on o.order_id = pm4.post_id and pm4.meta_key = \'_order_total\'
				left join ' . $wpdb->prefix . 'postmeta pm5 on o.order_id = pm5.post_id and pm5.meta_key = \'_order_shipping\'
				left join ' . $wpdb->prefix . 'posts p2 on o.order_id=p2.post_parent and p2.post_type = \'shop_order_refund\'
				left join ' . $wpdb->prefix . 'postmeta pm6 on p2.id=pm6.post_id and pm6.meta_key = \'_refund_amount\'
				SET
				o.order_total           = IFNULL ( pm4.meta_value , 0),
				o.order_shipping_amount = IFNULL ( pm5.meta_value , 0),
				o.order_refund_amount   = IFNULL ( pm6.meta_value , 0)
				'
			);
		}

		if ( lddfw_is_hpos_enabled() ) {

			// Sync order shipping cities.
			$wpdb->query(
				'UPDATE ' . $wpdb->prefix . 'lddfw_orders o
					left join ' . $wpdb->prefix . 'wc_order_addresses pm4 on o.order_id = pm4.order_id and pm4.address_type = \'shipping\'
					left join ' . $wpdb->prefix . 'wc_order_addresses pm5 on o.order_id = pm5.order_id and pm5.address_type = \'billing\'
					SET
					o.order_shipping_city = CASE WHEN pm4.city = \'\' Or pm4.city IS NULL THEN pm5.city else pm4.city END
					'
			);
		} else {
				// Sync order shipping cities.
				$wpdb->query(
					'UPDATE ' . $wpdb->prefix . 'lddfw_orders o
						left join ' . $wpdb->prefix . 'postmeta pm4 on o.order_id = pm4.post_id and pm4.meta_key = \'_shipping_city\'
						left join ' . $wpdb->prefix . 'postmeta pm5 on o.order_id = pm5.post_id and pm5.meta_key = \'_billing_city\'
						SET
						o.order_shipping_city = CASE WHEN pm4.meta_value = \'\' Or pm4.meta_value IS NULL THEN pm5.meta_value else pm4.meta_value END
						'
				);
		}

		if ( lddfw_is_hpos_enabled() ) {

			// Sync seller.
			switch ( LDDFW_MULTIVENDOR ) {
				case 'dokan':
					$wpdb->query(
						'UPDATE ' . $wpdb->prefix . 'lddfw_orders o
						INNER JOIN ' . $wpdb->prefix . 'wc_orders_meta pm ON pm.order_id = o.order_id and pm.meta_key = \'_dokan_vendor_id\'
						SET o.seller_id = IFNULL ( pm.meta_value , 0 )
						'
					);
					break;
				case 'wcmp':
					$wpdb->query(
						'UPDATE ' . $wpdb->prefix . 'lddfw_orders o
						INNER JOIN ' . $wpdb->prefix . 'wc_orders_meta pm ON pm.order_id = o.order_id and pm.meta_key = \'_vendor_id\'
						SET o.seller_id = IFNULL ( pm.meta_value , 0 )
						'
					);
					break;
				case 'wcfm':
					$wpdb->query(
						'UPDATE ' . $wpdb->prefix . 'lddfw_orders o
						INNER JOIN ' . $wpdb->prefix . 'wcfm_marketplace_orders pm ON pm.order_id = o.order_id
						SET o.seller_id = IFNULL ( pm.vendor_id , 0 )
						'
					);
					break;
			}
		} else {

			// Sync seller.
			switch ( LDDFW_MULTIVENDOR ) {
				case 'dokan':
					$wpdb->query(
						'UPDATE ' . $wpdb->prefix . 'lddfw_orders o
						INNER JOIN ' . $wpdb->prefix . 'postmeta pm ON pm.post_iD = o.order_id and pm.meta_key = \'_dokan_vendor_id\'
						SET o.seller_id = IFNULL ( pm.meta_value , 0 )
						'
					);
					break;
				case 'wcmp':
					$wpdb->query(
						'UPDATE ' . $wpdb->prefix . 'lddfw_orders o
						INNER JOIN ' . $wpdb->prefix . 'postmeta pm ON pm.post_iD = o.order_id and pm.meta_key = \'_vendor_id\'
						SET o.seller_id = IFNULL ( pm.meta_value , 0 )
						'
					);
					break;
				case 'wcfm':
					$wpdb->query(
						'UPDATE ' . $wpdb->prefix . 'lddfw_orders o
						INNER JOIN ' . $wpdb->prefix . 'wcfm_marketplace_orders pm ON pm.order_id = o.order_id
						SET o.seller_id = IFNULL ( pm.vendor_id , 0 )
						'
					);
					break;
			}
		}

			// Add option that sync table has been synced.
			update_option( 'lddfw_sync_table', '2' );
	}

	// If lddfw_sync_table = 2 then sync payment method.
	if ( '2' === get_option( 'lddfw_sync_table', '' ) ) {

		if ( lddfw_is_hpos_enabled() ) {
			// Sync payment method.
			$wpdb->query(
				'UPDATE ' . $wpdb->prefix . 'lddfw_orders o
					left join ' . $wpdb->prefix . 'wc_orders pm4 on o.order_id = pm4.id  
					SET
					o.order_payment_method = pm4.payment_method
					'
			);
		} else {
			// Sync payment method.
			$wpdb->query(
				'UPDATE ' . $wpdb->prefix . 'lddfw_orders o
					left join ' . $wpdb->prefix . 'postmeta pm4 on o.order_id = pm4.post_id and pm4.meta_key = \'_payment_method\'
					SET
					o.order_payment_method = pm4.meta_value
					'
			);
		}

			// Add option that sync table has been synced.
			update_option( 'lddfw_sync_table', '3' );
	}

	// If lddfw_sync_table = 3 then sync delivery date.
	if ( '3' === get_option( 'lddfw_sync_table', '' ) ) {

		if ( lddfw_is_hpos_enabled() ) {
			// Sync delivery date.
			$wpdb->query(
				'UPDATE ' . $wpdb->prefix . 'lddfw_orders o
					left join ' . $wpdb->prefix . 'wc_orders_meta om ON o.id = om.order_id AND om.meta_key = \'_lddfw_delivery_date\' 
				    SET
					o.delivery_date = om.meta_value
					'
			);
		} else {
			// Sync delivery date.
			$wpdb->query(
				'UPDATE ' . $wpdb->prefix . 'lddfw_orders o
					left join ' . $wpdb->prefix . 'postmeta pm4 on o.order_id = pm4.post_id and pm4.meta_key = \'_lddfw_delivery_date\'
					SET
					o.delivery_date = pm4.meta_value
					'
			);
		}

			// Add option that sync table has been synced.
			update_option( 'lddfw_sync_table', '4' );
	}
}

	/**
	 * Update refund in sync table.
	 *
	 * @return void
	 */
function lddfw_woocommerce_order_refunded( $order_id, $refund_id ) {

	// Insert order_id to sync table if not exist.
	if ( ! lddfw_is_order_already_exists( $order_id ) ) {
		lddfw_insert_orderid_to_sync_order( $order_id );
	}

	// Update order on sync table.
	$order = wc_get_order( $order_id );
	lddfw_update_all_sync_order( $order );
}

	/**
	 * Premium feature.
	 *
	 * @param string $value text.
	 * @return html
	 */
function lddfw_admin_premium_feature( $value ) {
	$result = $value;
	if ( lddfw_is_free() ) {
		$result = '<div class="lddfw_premium_feature">
						<a class="lddfw_star_button" href="#"><svg style="color:#ffc106" width=20 aria-hidden="true" focusable="false" data-prefix="fas" data-icon="star" class=" lddfw_premium_iconsvg-inline--fa fa-star fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"> <title>' . esc_attr__( 'Premium Feature', 'lddfw' ) . '</title><path fill="currentColor" d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"></path></svg></a>
					  	<div class="lddfw_premium_feature_note" style="display:none">
						  <a href="#" class="lddfw_premium_close">
						  <svg aria-hidden="true"  width=10 focusable="false" data-prefix="fas" data-icon="times" class="svg-inline--fa fa-times fa-w-11" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512"><path fill="currentColor" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path></svg></a>
						  <h2>' . esc_html( __( 'Premium Feature', 'lddfw' ) ) . '</h2>
						  <p>' . esc_html( __( 'You Discovered a Premium Feature!', 'lddfw' ) ) . '</p>
						  <p>' . esc_html( __( 'Upgrading to Premium will unlock it.', 'lddfw' ) ) . '</p>
						  <a target="_blank" href="https://powerfulwp.com/local-delivery-drivers-for-woocommerce-premium#pricing" class="lddfw_premium_buynow">' . esc_html( __( 'UNLOCK PREMIUM', 'lddfw' ) ) . '</a>
						  </div>
					  </div>';
	}
	return $result;
}

	/**
	 * International_phone_number
	 *
	 * @param string $country_code country code.
	 * @param string $phone phone number.
	 * @return string
	 */
function lddfw_get_international_phone_number( $country_code, $phone ) {

	$phone = preg_replace( '/[^0-9+]*/', '', $phone );

	// if phone number does not include + we format the number by country calling code.
	if ( strpos( $phone, '+' ) === false && '' !== $country_code ) {
			$calling_code = WC()->countries->get_country_calling_code( $country_code );
			$calling_code = is_array( $calling_code ) ? $calling_code[0] : $calling_code;

		if ( $calling_code === '+225' ) {
			// If the country code is +225 (Côte d'Ivoire)
			// Check if the phone number starts with "0"
			if ( substr( $phone, 0, 1 ) === '0' ) {
				// Prepend "+225" and keep the leading "0" for mobile numbers
				$phone = $calling_code . $phone;
			} else {
				// If the phone number doesn't start with "0", prepend "+225" and remove any leading "0"s
				$phone = $calling_code . ltrim( $phone, '0' );
			}
		} else {
			$preg_calling_code = str_replace( '+', '', $calling_code );
			$preg              = '/^(?:\+?' . $preg_calling_code . '|0)?/';
			$phone             = preg_replace( $preg, $calling_code, $phone );
			$phone             = str_replace( $calling_code . '0', $calling_code, $phone );
		}
	}

	return $phone;

}

	/**
	 * Replace_tags
	 *
	 * @param string $content tags.
	 * @param int    $order_id the order number.
	 * @param object $order Order object.
	 * @param int    $driver_id user id number.
	 * @return array
	 */
function lddfw_replace_tags__premium_only( $content, $order_id, $order, $driver_id ) {
	$date_format                = lddfw_date_format( 'date' );
	$time_format                = lddfw_date_format( 'time' );
	$store                      = new LDDFW_Store();
	$seller_id                  = $store->lddfw_order_seller( $order );
	$store_name                 = $store->lddfw_store_name__premium_only( $order, $seller_id );
	$delivery_driver_first_name = get_user_meta( $driver_id, 'first_name', true );
	$delivery_driver_last_name  = get_user_meta( $driver_id, 'last_name', true );
	$delivery_driver_page       = lddfw_drivers_page_url( '' );

	$order_status = wc_get_order_status_name( $order->get_status() );
	$date_created = $order->get_date_created()->format( $date_format );
	$total        = $order->get_total();
	$currency     = get_woocommerce_currency();

	$billing_first_name = $order->get_billing_first_name();
	$billing_last_name  = $order->get_billing_last_name();
	$billing_company    = $order->get_billing_company();
	$billing_address_1  = $order->get_billing_address_1();
	$billing_address_2  = $order->get_billing_address_2();
	$billing_city       = $order->get_billing_city();
	$billing_country    = $order->get_billing_country();
	$billing_state      = LDDFW_Order::lddfw_states( $billing_country, $order->get_billing_state() );
	if ( '' !== $billing_country ) {
			$billing_country = WC()->countries->countries[ $billing_country ];
	}

		 $billing_postcode = $order->get_billing_postcode();
		 $billing_phone    = $order->get_billing_phone();

		 $shipping_first_name = $order->get_shipping_first_name();
		 $shipping_last_name  = $order->get_shipping_last_name();
		 $shipping_company    = $order->get_shipping_company();
		 $shipping_address_1  = $order->get_shipping_address_1();
		 $shipping_address_2  = $order->get_shipping_address_2();
		 $shipping_city       = $order->get_shipping_city();
		 $shipping_postcode   = $order->get_shipping_postcode();

		 $shipping_country = $order->get_shipping_country();
		 $shipping_state   = LDDFW_Order::lddfw_states( $shipping_country, $order->get_shipping_state() );
	if ( '' !== $shipping_country ) {
		$shipping_country = WC()->countries->countries[ $shipping_country ];
	}

	if ( in_array( 'woocommerce-extra-checkout-fields-for-brazil', LDDFW_PLUGINS, true ) ) {
		// Add shipping number to address.
		$shipping_number = $order->get_meta( '_shipping_number' );
		if ( '' !== $shipping_number && false !== $shipping_number ) {
			$shipping_address_1 .= ' ' . $shipping_number;
		}

		// Add shipping number to address.
		$billing_number = $order->get_meta( '_billing_number' );
		if ( '' !== $billing_number && false !== $billing_number ) {
			$billing_address_1 .= ' ' . $billing_number;
		}
	}

	if ( '' === $shipping_address_1 ) {
		$shipping_address_1 = $billing_address_1;
		$shipping_address_2 = $billing_address_2;
		$shipping_city      = $billing_city;
		$shipping_state     = $billing_state;
		$shipping_postcode  = $billing_postcode;
		$shipping_country   = $billing_country;
	}

		 $payment_method  = $order->get_payment_method();
		 $shipping_method = $order->get_shipping_method();

		 // ETA.
		 $estimated_time_of_arrival = '';
		 $route                     = $order->get_meta( 'lddfw_order_route' );
	if ( ! empty( $route ) ) {
		if ( isset( $route['distance_text'] ) ) {
			$duration_text = $route['distance_text'];
			if ( '' !== $duration_text ) {
				$estimated_time_of_arrival = esc_html( __( 'Estimated time of arrival', 'lddfw' ) ) . ': ' . esc_html( $route['duration_text'] );
			}
		}
	}

		 $tracking_url = lddfw_tracking_page_url__premium_only( $order_id );

		 $find = array(
			 '[tracking_url]',
			 '[estimated_time_of_arrival]',
			 '[delivery_driver_first_name]',
			 '[delivery_driver_last_name]',
			 '[delivery_driver_page]',
			 '[store_name]',
			 '[order_id]',
			 '[order_create_date]',
			 '[order_status]',
			 '[order_amount]',
			 '[order_currency]',
			 '[shipping_method]',
			 '[payment_method]',
			 '[billing_first_name]',
			 '[billing_last_name]',
			 '[billing_company]',
			 '[billing_address_1]',
			 '[billing_address_2]',
			 '[billing_city]',
			 '[billing_state]',
			 '[billing_postcode]',
			 '[billing_country]',
			 '[billing_phone]',
			 '[shipping_first_name]',
			 '[shipping_last_name]',
			 '[shipping_company]',
			 '[shipping_address_1]',
			 '[shipping_address_2]',
			 '[shipping_city]',
			 '[shipping_state]',
			 '[shipping_postcode]',
			 '[shipping_country]',

		 );

		 $replace = array(
			 $tracking_url,
			 $estimated_time_of_arrival,
			 $delivery_driver_first_name,
			 $delivery_driver_last_name,
			 $delivery_driver_page,
			 $store_name,
			 $order->get_order_number(),
			 $date_created,
			 $order_status,
			 $total,
			 $currency,
			 $shipping_method,
			 $payment_method,
			 $billing_first_name,
			 $billing_last_name,
			 $billing_company,
			 $billing_address_1,
			 $billing_address_2,
			 $billing_city,
			 $billing_state,
			 $billing_postcode,
			 $billing_country,
			 $billing_phone,
			 $shipping_first_name,
			 $shipping_last_name,
			 $shipping_company,
			 $shipping_address_1,
			 $shipping_address_2,
			 $shipping_city,
			 $shipping_state,
			 $shipping_postcode,
			 $shipping_country,
		 );

		 $content = str_replace( $find, $replace, $content );
		 return $content;
}

	/**
	 * Allowed html.
	 *
	 * @return array
	 */
function lddfw_allowed_html() {

	$allowed_tags = array(

		'a'          => array(
			'href'   => array(),
			'target' => array(),
		),
		'abbr'       => array(),
		'b'          => array(),
		'blockquote' => array(),
		'cite'       => array(),
		'code'       => array(),
		'del'        => array(),
		'dd'         => array(),
		'div'        => array(),
		'dl'         => array(),
		'dt'         => array(),
		'em'         => array(),
		'h1'         => array(),
		'h2'         => array(),
		'h3'         => array(),
		'h4'         => array(),
		'h5'         => array(),
		'h6'         => array(),
		'i'          => array(),
		'img'        => array(
			'alt'    => array(),
			'class'  => array(),
			'height' => array(),
			'src'    => array(),
			'width'  => array(),
		),
		'li'         => array(),
		'ol'         => array(),
		'p'          => array(),
		'q'          => array(),
		'span'       => array(),
		'strike'     => array(),
		'strong'     => array(),
		'ul'         => array(),
	);

	return $allowed_tags;
}


/**
 * Get tracking page URL.
 *
 * @param string|int $order_id Order ID.
 * @since 1.0.0
 */
function lddfw_tracking_page_url__premium_only( $order_id ) {
	$params = '';

	// Build params only if order is valid.
	if ( ! empty( $order_id ) && $order = wc_get_order( $order_id ) ) {
		$order_key = $order->get_order_key();
		$order_key = str_replace( 'wc_order_', '', $order_key );
		$params    = 'k=' . $order_key;
	}

	$page_id = get_option( 'lddfw_tracking_page', '' );

	// Ensure $page_id is a valid integer ID before calling get_page_link()
	if ( ! empty( $page_id ) && get_post( $page_id ) ) {
		$link = get_page_link( $page_id );
	} else {
		// Fallback or empty string to avoid error
		$link = '';
	}

	// Append parameters if we have a valid link and params
	if ( ! empty( $link ) && '' !== $params ) {
		$link = esc_url( $link ) . ( strpos( $link, '?' ) !== false ? '&' : '?' ) . $params;
	}

	return $link;
}

/**
 * Get driver app mode.
 *
 * @param string $driver_id driver_id.
 */
function lddfw_get_app_mode( $driver_id ) {
	$lddfw_app_mode = '';
	if ( lddfw_fs()->is__premium_only() ) {
		if ( lddfw_fs()->can_use_premium_code() ) {
			if ( '' !== $driver_id ) {
				// Get user app mode.
				$lddfw_app_mode = get_user_meta( $driver_id, 'lddfw_driver_app_mode', true );
			}
			// If empty get admin setting app mode.
			$lddfw_app_mode = '' === $lddfw_app_mode ? get_option( 'lddfw_app_mode', '' ) : $lddfw_app_mode;
		}
	}
	return $lddfw_app_mode;
}

/**
 * Get map language.
 */
function lddfw_get_map_language() {
	$language = get_locale();

	if ( strlen( $language ) > 0 ) {
		$language = explode( '_', $language )[0];
	} else {
		$language = 'en';
	}
	return $language;
}

/**
 * Get map center.
 */
function lddfw_get_map_center( $order_id, $driver_id ) {
	$result = '';

	// Store coordinates.
	$latitude  = get_option( 'lddfw_store_address_latitude' );
	$longitude = get_option( 'lddfw_store_address_longitude' );
	if ( '' !== $longitude && '' !== $latitude && '0' !== $longitude && '0' !== $latitude ) {
		$result = $latitude . ',' . $longitude;
	}
	return $result;
}

	/**
	 * Convert time to words.
	 *
	 * @param int $seconds seconds.
	 * @return string
	 */
function lddfw_convert_seconds_to_words( $seconds ) {
	$hours    = ( $seconds / 60 / 60 );
	$rhours   = floor( $hours );
	$minutes  = ( $hours - $rhours ) * 60;
	$rminutes = floor( $minutes );
	$result   = '';

	if ( (int) $rhours > 1 ) {
			$result = $rhours . ' ' . esc_html( __( 'hours', 'lddfw' ) ) . ' ';
	}
	if ( (int) $rhours === 1 ) {
		$result = $rhours . ' ' . esc_html( __( 'hour', 'lddfw' ) ) . ' ';
	}
	if ( (int) $rminutes > 0 ) {
		$result .= $rminutes . ' ' . esc_html( __( 'mins', 'lddfw' ) );
	}
	return $result;
}


/**
 * Allow protected order custom fields.
 *
 * @return array
 */
function lddfw_allow_protected_order_custom_fields() {
	return array( '_delivery_date', '_delivery_time_frame', '_shipping_date' );
}

/**
 * Get order custom fields.
 *
 * @since 1.1.2
 * @param int   $orderid order id.
 * @param array $posts custom fields array.
 * @return string
 */
function lddfw_order_custom_fields__premium_only( $orderid, $posts ) {
	$html             = '';
	$counter          = 0;
	$post_has_content = false;
	$order            = wc_get_order( $orderid );

	foreach ( $posts as $post ) {

		$meta = get_post_meta( $post->ID, '', true );
		$meta = array_map(
			function( $n ) {
				return $n[0];
			},
			$meta
		);

		if ( 0 < $counter && $post_has_content ) {
			$html            .= '<br>';
			$post_has_content = false;
		}

		$field_value = '';
		foreach ( $meta as $key => $value ) {

			$value = preg_replace( '/\s+/', ' ', $value );
			if ( '_' !== substr( $key, 0, 1 ) || in_array( $key, lddfw_allow_protected_order_custom_fields(), true ) ) {

				// Replace protected custome fields.
				if ( '[_' === substr( $key, 0, 2 ) && ']' === substr( $key, -1 ) ) {
					$key = substr( $key, 1, -1 );
				}

				$post_meta = $order->get_meta( $key );

				// Print string.
				if ( '' !== $post_meta & ! is_array( $post_meta ) ) {
					if ( '' !== $value ) {
						$field_value .= $value . $post_meta . ' ';
					} else {
						$field_value .= $post_meta . ' ';
					}
				}

				// Print array.
				if ( is_array( $post_meta ) ) {
					$field_array_value = '';
					foreach ( $post_meta as $post_meta_value ) {
						$post_meta_value = preg_replace( '/\s+/', ' ', $post_meta_value );
						if ( '' !== $post_meta_value & ! is_array( $post_meta_value ) ) {
							$field_array_value .= $post_meta_value . ' ';
						}
					}
					if ( '' !== $field_array_value ) {
						if ( '' !== $value ) {
							$field_value .= $value . $field_array_value . ' ';
						} else {
							$field_value .= $field_array_value . ' ';
						}
					}
				}
			}
		}

		if ( '' !== trim( $field_value ) ) {
			$html            .= $post->post_title . ': ' . $field_value;
			$post_has_content = true;
		}

		$counter++;

	}
	return $html;
}

/**
 * Check Google server key function
 *
 * @param string $lddfw_google_api_key_server Google key.
 * @return void
 */
function lddfw_check_server_google_keys( $lddfw_google_api_key_server ) {

	// Check Directions API.
	$url      = 'https://maps.googleapis.com/maps/api/directions/json?origin=Disneyland&destination=Universal+Studios+Hollywood&key=' . $lddfw_google_api_key_server;
	$response = wp_remote_get( $url );
	$result   = __( 'An unexpected error has occurred.', 'lddfw' );
	if ( ! is_wp_error( $response ) ) {
		$body   = wp_remote_retrieve_body( $response );
		$obj    = json_decode( $body );
		$result = '';
		if ( ! empty( $obj->status ) ) {
			$result .= $obj->status;
		}
		if ( ! empty( $obj->error_message ) ) {
			$result .= ', ' . $obj->error_message;
		}
	}
	echo '<p>Directions API: ' . esc_html( $result ) . '</p>';

	// Check Distance Matrix API.
	$url      = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=Washington%2C%20DC&destinations=New%20York%20City%2C%20NY&units=imperial&key=' . $lddfw_google_api_key_server;
	$response = wp_remote_get( $url );
	$result   = __( 'An unexpected error has occurred.', 'lddfw' );
	if ( ! is_wp_error( $response ) ) {
		$body   = wp_remote_retrieve_body( $response );
		$obj    = json_decode( $body );
		$result = '';
		if ( ! empty( $obj->status ) ) {
			$result .= $obj->status;
		}
		if ( ! empty( $obj->error_message ) ) {
			$result .= ', ' . $obj->error_message;
		}
	}
	echo '<p>Distance Matrix API: ' . esc_html( $result ) . '</p>';

	// Check Geocoding API.
	$url = 'https://maps.google.com/maps/api/geocode/json?sensor=false&language=en&key=' . $lddfw_google_api_key_server . '&address=Universal+Studios+Hollywood';
	$ch  = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_PROXYPORT, 3128 );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
	$response = curl_exec( $ch );

	curl_close( $ch );
	$obj    = json_decode( $response );
	$result = __( 'An unexpected error has occurred.', 'lddfw' );
	if ( json_last_error() === 0 ) {
		$result = '';
		if ( ! empty( $obj->status ) ) {
			$result .= $obj->status;
		}
		if ( ! empty( $obj->error_message ) ) {
			$result .= ', ' . $obj->error_message;
		}
	}
	echo '<p>Geocoding API: ' . esc_html( $result ) . '</p>';

}


	/**
	 * Determines whether HPOS is enabled.
	 *
	 * @return bool
	 */
function lddfw_is_hpos_enabled() : bool {
	if ( version_compare( get_option( 'woocommerce_version' ), '7.1.0' ) < 0 ) {
		return false;
	}

	if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
		return true;
	}

	return false;
}

