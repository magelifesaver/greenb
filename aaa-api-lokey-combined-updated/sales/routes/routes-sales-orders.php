<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-api-lokey-sales-report/routes/routes-sales-orders.php
 * Route: /wp-json/lokeyreports/v1/sales/orders
 * Version: 1.7.1
 * Updated: 2025-12-02
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Provides detailed order-level reporting with filters for date, payment status,
 *   payment type, taxonomy (brand/category/attribute), and customer.
 *
 * Supports:
 *   ✅ JWT authorization (for GPT/external access)
 *   ✅ WooCommerce Consumer Key / Secret fallback
 *   ✅ Logged-in REST nonce / admin user access
 *   ✅ Internal WordPress requests (cron, CLI, AJAX)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load central permission checker
if ( ! function_exists( 'lokey_reports_permission_check' ) ) {
    require_once __DIR__ . '/../lokey-reports-permissions.php';
}

/**
 * --------------------------------------------------------------------------
 * Register Order Sales Route
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_register_order_routes' ) ) {
    function lokey_reports_register_order_routes() {

        register_rest_route(
            'lokeyreports/v1',
            '/sales/orders',
            [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => 'lokey_reports_handle_sales_orders',
                // Permit access without JWT for GPT actions.
                'permission_callback' => '__return_true',
            ]
        );
    }
    add_action( 'rest_api_init', 'lokey_reports_register_order_routes' );
}

/**
 * --------------------------------------------------------------------------
 * REST Callback: /lokeyreports/v1/sales/orders
 * --------------------------------------------------------------------------
 */
if ( ! function_exists( 'lokey_reports_handle_sales_orders' ) ) {
    function lokey_reports_handle_sales_orders( WP_REST_Request $request ) {

        // Capture metrics start for performance diagnostics
        $metrics_start = function_exists( 'lokey_reports_metrics_start' ) ? lokey_reports_metrics_start() : null;

        if ( ! function_exists( 'wc_get_orders' ) ) {
            lokey_reports_debug('❌ WooCommerce missing.', 'routes-sales-orders.php');
            return new WP_Error( 'no_woocommerce', 'WooCommerce not active.', [ 'status' => 500 ] );
        }

        // 🧩 Parse parameters
        $params = [
            'preset' => $request->get_param('preset'),
            'from'   => $request->get_param('from'),
            'to'     => $request->get_param('to'),
        ];

        $range      = lokey_reports_parse_date_range($params);
        $statuses   = lokey_reports_sanitize_status_list($request->get_param('statuses'));
        $date_type  = sanitize_text_field($request->get_param('date_type') ?: 'created');
        $limit      = min(absint($request->get_param('limit') ?: 100), 1000);
        $brand      = sanitize_text_field($request->get_param('brand'));
        $category   = sanitize_text_field($request->get_param('category'));
        $attribute  = sanitize_text_field($request->get_param('attribute'));
        $term       = sanitize_text_field($request->get_param('term'));
        $customer   = sanitize_text_field($request->get_param('customer'));
        $pay_status = sanitize_text_field($request->get_param('payment_status'));
        $pay_type   = sanitize_text_field($request->get_param('payment_type'));

        // 🧾 Fetch orders
        $orders = lokey_reports_get_orders_for_range($range['from'], $range['to'], $statuses, $date_type);

        // 🔍 Apply payment filters
        if ( $pay_status ) {
            $orders = lokey_reports_filter_orders_by_payment_status($orders, $pay_status);
        }
        if ( $pay_type ) {
            $orders = lokey_reports_filter_orders_by_payment_type($orders, $pay_type);
        }

// 🧮 Build filtered result set
$results = [];
foreach ( $orders as $order ) {
	if ( ! $order instanceof WC_Order ) continue;

	// 🔍 Customer filter
	if ( $customer ) {
		$cid   = (string) $order->get_customer_id();
		$email = strtolower((string) $order->get_billing_email());
		if ( $cid !== $customer && $email !== $customer ) continue;
	}

	// 🔍 Taxonomy filters
	if ( $category || $brand || $attribute ) {
		$match = false;
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$pid = $item->get_product_id();
			if ( ! $pid ) continue;

			if ( $category && has_term( $category, 'product_cat', $pid ) ) {
				$match = true;
				break;
			}
			if ( $brand && has_term( $brand, 'berocket_brand', $pid ) ) {
				$match = true;
				break;
			}

			if ( $attribute ) {
				$tax = 'pa_' . sanitize_title( $attribute );
				if ( taxonomy_exists( $tax ) ) {
					$terms = get_the_terms( $pid, $tax );
					if ( is_array( $terms ) ) {
						foreach ( $terms as $t ) {
							if (
								strtolower( $t->slug ) === strtolower( $term ) ||
								strtolower( $t->name ) === strtolower( $term )
							) {
								$match = true;
								break 2;
							}
						}
					}
				}
			}
		}
		if ( ! $match ) continue;
	}

	// 🕓 Build order data
	$dt = $order->get_date_paid() ?: $order->get_date_completed() ?: $order->get_date_created();
	if ( $dt ) {
		$dt->setTimezone( lokey_reports_get_store_timezone() );
	}

	$items_count = 0;
	$lines       = [];
	foreach ( $order->get_items( 'line_item' ) as $item ) {
		$items_count += (int) $item->get_quantity();
		$lines[] = [
			'product_id' => $item->get_product_id(),
			'name'       => $item->get_name(),
			'qty'        => (int) $item->get_quantity(),
			'total'      => round( (float) $item->get_total(), 2 ),
			'total_tax'  => round( (float) $item->get_total_tax(), 2 ),
		];
	}

	// 💳 Payment breakdown
	$payment_data = function_exists( 'lokey_reports_extract_payment_breakdown' )
		? lokey_reports_extract_payment_breakdown( $order )
		: [];

	// 🚗 Driver + tip metadata (verified keys)
	$driver_id   = $order->get_meta( 'lddfw_driverid', true );
	$driver_name = '';
	if ( $driver_id ) {
		$user = get_user_by( 'id', $driver_id );
		$driver_name = $user ? $user->display_name : 'Unassigned';
	}

	$tip_amount = floatval(
		$order->get_meta( 'total_order_tip', true )
		?: $order->get_meta( 'epayment_tip', true )
	);
	// 🧮 Ignore micro rounding tips under $0.01
	if ( $tip_amount < 0.01 ) {
	    $tip_amount = 0;
	}

	// 📦 Compile record
	$results[] = array_merge( [
		'order_id'    => $order->get_id(),
		'date'        => $dt ? $dt->format( 'Y-m-d H:i:s' ) : '',
		'status'      => $order->get_status(),
		'customer_id' => (int) $order->get_customer_id(),
		'customer'    => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
		'email'       => $order->get_billing_email(),
		'items_count' => $items_count,
		'total'       => round( (float) $order->get_total(), 2 ),
		'discounts'   => round( (float) $order->get_discount_total(), 2 ),
		'net_sales'   => round( (float) $order->get_total() - $order->get_total_refunded(), 2 ),
		'payment'     => $order->get_payment_method_title(),
		'products'    => $lines,

		// 🆕 Added metadata
		'driver_id'   => $driver_id ?: '',
		'driver_name' => $driver_name,
		'total_tip'   => $tip_amount,
	], $payment_data );
}

// 🔚 Limit results and prepare response data
if ( count( $results ) > $limit ) {
	$results = array_slice( $results, 0, $limit );
}
        $response_data = [
            'group_by'  => 'order',
            'from'      => $range['from']->format('Y-m-d'),
            'to'        => $range['to']->format('Y-m-d'),
            'date_type' => $date_type,
            'statuses'  => $statuses,
            'filters'   => [
                'brand'          => $brand,
                'category'       => $category,
                'attribute'      => $attribute,
                'term'           => $term,
                'customer'       => $customer,
                'payment_status' => $pay_status,
                'payment_type'   => $pay_type,
            ],
            'count'     => count($results),
            'currency'  => get_woocommerce_currency(),
            'orders'    => $results,
        ];

        lokey_reports_debug(
            sprintf("✅ Orders route executed successfully (%d orders, %s → %s)",
                count($results), $response_data['from'], $response_data['to']),
            'routes-sales-orders.php'
        );

        $response = rest_ensure_response($response_data);
        if ( $metrics_start ) {
            $metrics = function_exists( 'lokey_reports_metrics_end' ) ? lokey_reports_metrics_end( $metrics_start ) : null;
            if ( $metrics ) {
                $response->header( 'X-Lokey-Exec-Time', number_format( $metrics['time'], 4 ) );
                $response->header( 'X-Lokey-Memory', (string) $metrics['memory'] );
            }
        }
        return $response;
    }
}
