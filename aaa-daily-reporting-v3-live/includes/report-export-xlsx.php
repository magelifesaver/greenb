<?php
/**
 * File: includes/report-export-xlsx.php
 * Description: Handles raw XLSX download for AAA Daily Reporting (all sections)
 * Version: 1.2.0
 * Path: aaa-daily-reporting/includes/report-export-xlsx.php
 */

if ( ! defined('ABSPATH') ) {
    exit;
}

// Load PhpSpreadsheet
require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

add_action('admin_init', 'aaa_handle_xlsx_export');
function aaa_handle_xlsx_export() {
    // Suppress warnings/notices to prevent corruption of the XLSX binary
    error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');

    // Clear any accidental output before streaming
    if ( ob_get_length() ) {
        ob_end_clean();
    }
    if ( empty($_GET['download_xlsx']) || empty($_GET['report_date']) ) {
        return;
    }

    $date  = sanitize_text_field(wp_unslash($_GET['report_date']));
    $nonce = wp_unslash($_GET['_wpnonce'] ?? '');
    if ( ! wp_verify_nonce($nonce, 'aaa_download_report_' . $date) ) {
        wp_die('Invalid download request.');
    }

    if ( ob_get_length() ) {
        ob_end_clean();
    }
    nocache_headers();

    $orders = aaa_get_orders_for_date($date);

    $spreadsheet = new Spreadsheet();
    $spreadsheet->getProperties()
        ->setCreator('AAA Daily Reporting')
        ->setTitle("Daily Report1 {$date}");

    // 1. Metrics
    $metricsSheet = $spreadsheet->getActiveSheet();
    $metricsSheet->setTitle('Metrics1');
    $metricsData = aaa_generate_metrics($orders);
    aaa_populate_sheet($metricsSheet, ['Metric','Value'], $metricsData);

    // 2. Top Summary (New vs Returning)
    $topSheet = $spreadsheet->createSheet();
    $topSheet->setTitle('Customer Summary');
    $topData = aaa_generate_customer_overview($orders);
    aaa_populate_sheet($topSheet, array_keys($topData[0] ?? []), $topData);

    // 3. Orders List
    $ordersSheet = $spreadsheet->createSheet();
    $ordersSheet->setTitle('Orders');
    $ordersList  = aaa_generate_orders_list( $orders );
    aaa_populate_sheet(
        $ordersSheet,
        array_keys( $ordersList[0] ?? [] ),
        $ordersList
    );

    // — Apply percent format to the “% Off” column (column K) —
    $highestRow = $ordersSheet->getHighestRow();
    $ordersSheet
        ->getStyle( "K2:K{$highestRow}" )
        ->getNumberFormat()
        ->setFormatCode(
            \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_00
        );

    // 4. Product Breakdown (each sale)
    $prodBreak = $spreadsheet->createSheet();
    $prodBreak->setTitle('Product Breakdown');
    $prodData = aaa_generate_product_breakdown($orders);
    aaa_populate_sheet($prodBreak, array_keys($prodData[0] ?? []), $prodData);

    // 5. Product Summary (totals per product)
    $prodSummary = $spreadsheet->createSheet();
    $prodSummary->setTitle('Product Summary');
    $prodSummaryData = aaa_generate_product_summary($orders);
    aaa_populate_sheet($prodSummary, array_keys($prodSummaryData[0] ?? []), $prodSummaryData);

    // 6. Customer Details
    $custSheet = $spreadsheet->createSheet();
    $custSheet->setTitle('Customers');
    $custData = aaa_generate_customer_list($orders);
    aaa_populate_sheet($custSheet, array_keys($custData[0] ?? []), $custData);

    // 7. Delivery Cities
    $citySheet = $spreadsheet->createSheet();
    $citySheet->setTitle('Delivery Cities');
    $cityData = aaa_generate_delivery_city_report($orders);
    aaa_populate_sheet($citySheet, array_keys($cityData[0] ?? []), $cityData);

    // 8. Payment Methods
    $paySheet = $spreadsheet->createSheet();
    $paySheet->setTitle('Payment Methods');
    $payData = aaa_generate_payment_summary($orders);
    aaa_populate_sheet($paySheet, array_keys($payData[0] ?? []), $payData);

	// 9. Brand Summary sheet
	$brandSheet = $spreadsheet->createSheet();
	$brandSheet->setTitle('Brand Summary');
	$brandData = aaa_generate_brand_summary( $orders );
	aaa_populate_sheet( $brandSheet, array_keys( $brandData[0] ?? [] ), $brandData );

	// 10. Category Summary sheet
	$catSheet = $spreadsheet->createSheet();
	$catSheet->setTitle('Category Summary');
	$catData = aaa_generate_category_summary( $orders );
	aaa_populate_sheet( $catSheet, array_keys( $catData[0] ?? [] ), $catData );

    // 10. Refunds & Cancels
    $refSheet = $spreadsheet->createSheet();
    $refSheet->setTitle('Refunds & Cancels');
    $refData = aaa_generate_refunds_and_cancels($date);
    aaa_populate_sheet($refSheet, array_keys($refData[0] ?? []), $refData);

    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="aaa-report-' . esc_attr($date) . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/** Populate sheet with headers and data */
function aaa_populate_sheet($sheet, $headers, $rows) {
    $sheet->fromArray($headers, null, 'A1');
    $r = 2;
    foreach ($rows as $row) {
        $sheet->fromArray(array_values($row), null, "A{$r}");
        $r++;
    }
}

// Generators
function aaa_generate_metrics($orders) {
    $total = count($orders);
    $gross = array_sum(array_map(fn($o)=>(float)$o->get_total(), $orders));
    $disc  = array_sum(array_map(fn($o)=>(float)$o->get_discount_total(), $orders));
    return [
        ['Metric'=>'Total Orders','Value'=>$total],
        ['Metric'=>'Gross Revenue','Value'=>$gross],
        ['Metric'=>'Total Discounts','Value'=>$disc],
        ['Metric'=>'Net Revenue','Value'=> $gross - $disc],
        ['Metric'=>'Average Order Value','Value'=> $total? round($gross/$total,2):0],
    ];
}

function aaa_generate_customer_overview($orders) {
    $new=0; $existing=0;
    foreach ($orders as $o) {
        $user = $o->get_user_id();
        if ( $user && wc_customer_bought_product('', $user, $o->get_id()) ) {
            $existing++;
        } else {
            $new++;
        }
    }
    $total = $new + $existing;
    return [[
        'Type'=>'New Customers','Count'=>$new,'Percent'=> $total? round(100*$new/$total,1):0
    ],[
        'Type'=>'Existing Customers','Count'=>$existing,'Percent'=> $total? round(100*$existing/$total,1):0
    ]];
}
/**
 * Build a flat array of orders for both UI and XLSX export.
 *
 * @param WC_Order[] $orders
 * @return array[]
 */
function aaa_generate_orders_list( array $orders ): array {
    $rows = [];

    foreach ( $orders as $o ) {
        // 1) Date & weekday
        $created = $o->get_date_created();
        $weekday = $created ? $created->date_i18n( 'l' ) : '';
        $date    = $created ? $created->date_i18n( 'n/j' ) : '';
        $when    = trim( $weekday . ' ' . $date );

        // 2) External Order #
        $ext_num = $o->get_meta( '_external_order_number', true ) ?: '—';

        // 3) Customer & Created Via
        $customer  = $o->get_formatted_billing_full_name();
        $via       = $o->get_created_via();
        $via_label = $via === 'admin'
            ? 'Admin'
            : ( $via === 'checkout'
                ? 'Customer'
                : ucfirst( $via )
              );

        // 4) Totals, discounts, tip & store credit
        $total        = (float) $o->get_total();
        $discount     = (float) $o->get_discount_total();
        $tip          = (float) $o->get_meta( '_wpslash_tip', true );
        $store_credit = (float) $o->get_meta( '_funds_used', true );

        // 5) Percent off as a fraction (0–1)
        $pct_off = $total > 0 ? ( $discount / $total ) : 0;

        // 6) Attribution source
        $src_type   = $o->get_meta( '_wc_order_attribution_source_type', true );
        $utm_source = $o->get_meta( '_wc_order_attribution_utm_source', true );
        if ( $src_type === 'typein' ) {
            $source = 'Direct';
        } elseif ( $src_type === 'admin' && $ext_num !== '—' ) {
            $source = 'Marketplace';
        } elseif ( $src_type === 'admin' ) {
            $source = 'Admin Direct';
        } elseif ( $src_type === 'organic' ) {
            $source = ucfirst( $utm_source ) . ' Organic';
        } else {
            $source = 'Other';
        }

        // 7) COGS & profit
        $cogs = 0;
        foreach ( $o->get_items() as $item ) {
            $qty     = $item->get_quantity();
            $product = $item->get_product();
            if ( $product ) {
                global $wpdb;
                $atum_table = $wpdb->prefix . 'atum_product_data';
                $unit_cost  = (float) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT purchase_price FROM {$atum_table} WHERE product_id = %d",
                        $product->get_id()
                    )
                );
                if ( ! $unit_cost ) {
                    $meta_cost = get_post_meta( $product->get_id(), '_cogs_total_value', true );
                    if ( $meta_cost !== '' ) {
                        $unit_cost = (float) $meta_cost;
                    } else {
                        $sale  = (float) $product->get_sale_price();
                        $reg   = (float) $product->get_regular_price();
                        $unit_cost = $sale > 0 ? $sale * 0.5 : $reg * 0.5;
                    }
                }
                $cogs += $unit_cost * $qty;
            }
        }
        $profit = $total - $cogs;

        // 8) Item counts
        $items_qty    = 0;
        foreach ( $o->get_items() as $item ) {
            $items_qty += $item->get_quantity();
        }
        $items_unique = count( $o->get_items() );

        // 9) Time & time-to-complete
        $time     = $created ? $created->date_i18n( 'H:i' ) : '';
        $start_ts = $created?->getTimestamp() ?: 0;
        $end_dt   = $o->get_date_completed() ?: $o->get_date_paid();
        $end_ts   = $end_dt?->getTimestamp() ?: 0;
        $minutes  = ( $start_ts && $end_ts )
            ? round( ( $end_ts - $start_ts ) / 60 )
            : '';

        // 10) Payment & City
        $payment = $o->get_payment_method_title();
        $city    = $o->get_shipping_city();

        // Build row, matching your <th> sequence exactly
        $rows[] = [
            'Date'                  => $when,
            'Order ID'              => $o->get_id(),
            'External Order #'      => $ext_num,
            'Customer'              => $customer,
            'Created Via'           => $via_label,
            'Total'                 => $total,
            'Discount'              => $discount,
            'Tip'                   => $tip,
            'Store Credit'          => $store_credit,
            'Source'                => $source,
            '% Off'                 => $pct_off,
            'COGS'                  => $cogs,
            'Profit'                => $profit,
            '# Items'               => $items_qty,
            'Unique Items'          => $items_unique,
            'Payment Method'        => $payment,
            'City'                  => $city,
            'Time'                  => $time,
            'Time to Complete (min)'=> $minutes,
        ];
    }

    // — Append Totals row —
    $sum_total         = array_sum( array_column( $rows, 'Total' ) );
    $sum_discount      = array_sum( array_column( $rows, 'Discount' ) );
    $sum_tip           = array_sum( array_column( $rows, 'Tip' ) );
    $sum_store_credit  = array_sum( array_column( $rows, 'Store Credit' ) );
    $sum_cogs          = array_sum( array_column( $rows, 'COGS' ) );
    $sum_profit        = array_sum( array_column( $rows, 'Profit' ) );

    $rows[] = [
        'Date'                  => 'Totals',
        'Order ID'              => '',
        'External Order #'      => '',
        'Customer'              => '',
        'Created Via'           => '',
        'Total'                 => $sum_total,
        'Discount'              => $sum_discount,
        'Tip'                   => $sum_tip,
        'Store Credit'          => $sum_store_credit,
        'Source'                => '',
        '% Off'                 => '',
        'COGS'                  => $sum_cogs,
        'Profit'                => $sum_profit,
        '# Items'               => '',
        'Unique Items'          => '',
        'Payment Method'        => '',
        'City'                  => '',
        'Time'                  => '',
        'Time to Complete (min)'=> '',
    ];

    return $rows;
}

/**
 * 4. Product Breakdown (per‐order / per‐item rows) for XLSX export
 *
 * @param WC_Order[] $orders
 * @return array[]
 */
function aaa_generate_product_breakdown( array $orders ): array {
    global $wpdb;
    $rows = [];

    foreach ( $orders as $order ) {
        $order_id   = $order->get_id();
        $order_time = $order->get_date_created();

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $pid     = $product->get_id();
            $qty     = $item->get_quantity();
            $revenue = (float) $item->get_total();

            // — ATUM COGS lookup, then meta, then 50% fallback —
            $atum_table = $wpdb->prefix . 'atum_product_data';
            $atum_cost  = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT purchase_price FROM {$atum_table} WHERE product_id = %d",
                    $pid
                )
            );

            if ( $atum_cost !== null && $atum_cost !== '' ) {
                $unit_cost = (float) $atum_cost;
            } else {
                $meta_cost = get_post_meta( $pid, '_cogs_total_value', true );
                if ( $meta_cost !== '' ) {
                    $unit_cost = (float) $meta_cost;
                } else {
                    $sale_price    = (float) $product->get_sale_price();
                    $regular_price = (float) $product->get_regular_price();
                    $unit_cost     = $sale_price > 0
                        ? $sale_price * 0.5
                        : $regular_price * 0.5;
                }
            }

            $line_cost = $unit_cost * $qty;
            $profit    = $revenue - $line_cost;

            // — Build the flat row —
        $rows[] = [
            'Order ID'   => $order_id,
            'Date'       => $order_time?->date_i18n( 'Y-m-d H:i' ) ?? '',
            'Product'    => $product->get_name(),
            'SKU'        => $product->get_sku(),
            'Qty'        => $qty,
            'Revenue'    => $revenue,
            'Cost'       => $line_cost,
            'Profit'     => $profit,
            'Brand'      => aaa_get_brand_name( $pid ),
            'Category'   => aaa_get_category_path( $pid ),
            'Stock'      => (int) $product->get_stock_quantity(),
        ];
        }
    }

    // — Append Totals row —
    $sum_qty     = array_sum( array_column( $rows, 'Qty' ) );
    $sum_revenue = array_sum( array_column( $rows, 'Revenue' ) );
    $sum_cost    = array_sum( array_column( $rows, 'Cost' ) );
    $sum_profit  = array_sum( array_column( $rows, 'Profit' ) );

    $rows[] = [
        'Order ID' => '',
        'Date'     => 'Totals',
        'Product'  => '',
        'SKU'      => '',
        'Qty'      => $sum_qty,
        'Revenue'  => $sum_revenue,
        'Cost'     => $sum_cost,
        'Profit'   => $sum_profit,
        'Brand'    => '',
        'Category' => '',
        'Stock'    => '',
    ];

    return $rows;
}

/**
 * 5. Product Summary (totals per product) for XLSX export
 *
 * @param WC_Order[] $orders
 * @return array[]
 */
function aaa_generate_product_summary( array $orders ): array {
    global $wpdb;

    $products = [];

    foreach ( $orders as $order ) {
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $pid     = $product->get_id();
            $qty     = $item->get_quantity();
            $revenue = (float) $item->get_total();

            // 1) Unit cost same as breakdown
            $atum_table = $wpdb->prefix . 'atum_product_data';
            $atum_cost  = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT purchase_price FROM {$atum_table} WHERE product_id = %d",
                    $pid
                )
            );
            if ( $atum_cost !== null && $atum_cost !== '' ) {
                $unit_cost = (float) $atum_cost;
            } else {
                $meta_cost = get_post_meta( $pid, '_cogs_total_value', true );
                if ( $meta_cost !== '' ) {
                    $unit_cost = (float) $meta_cost;
                } else {
                    $sale_price    = (float) $product->get_sale_price();
                    $regular_price = (float) $product->get_regular_price();
                    $unit_cost     = $sale_price > 0
                        ? $sale_price * 0.5
                        : $regular_price * 0.5;
                }
            }

            // 2) Compute per‐line cost and profit
            $line_cost = $unit_cost * $qty;
            $profit    = $revenue - $line_cost;

            // 3) Initialize summary bucket
            if ( ! isset( $products[ $pid ] ) ) {
                $products[ $pid ] = [
                    'Product'    => $product->get_name(),
                    'SKU'        => $product->get_sku(),
                    'Qty'        => 0,
                    'Revenue'    => 0,
                    'Cost'       => 0,
                    'Profit'     => 0,
                    'Stock Left' => (int) $product->get_stock_quantity(),
                ];
            }

            // 4) Accumulate
            $products[ $pid ]['Qty']     += $qty;
            $products[ $pid ]['Revenue'] += $revenue;
            $products[ $pid ]['Cost']    += $line_cost;
            $products[ $pid ]['Profit']  += $profit;
        }
    }

    // sort by qty desc
    uasort( $products, fn( $a, $b ) => $b['Qty'] <=> $a['Qty'] );

    // 6) Return a zero-indexed array
    return array_values( $products );  // zero-indexed
}

/**
 * 6. Customer Details (per-customer rows) for XLSX export
 *
 * @param WC_Order[] $orders
 * @return array[]
 */
function aaa_generate_customer_list( array $orders ): array {
    $data = [];

    foreach ( $orders as $order ) {
        $uid   = $order->get_user_id();
        $email = $order->get_billing_email();
        $key   = $uid ? 'user_' . $uid : 'guest_' . $email;

        if ( ! isset( $data[ $key ] ) ) {
            // Determine “Customer Since”
            if ( $uid ) {
                $user    = get_user_by( 'ID', $uid );
                $created = strtotime( $user->user_registered );
            } else {
                $created = $order->get_date_created()?->getTimestamp() ?? time();
            }

            // Find last order before today
            $prev_orders = wc_get_orders([
                'customer_id' => $uid,
                'exclude'     => [ $order->get_id() ],
                'limit'       => -1,
                'orderby'     => 'date_created',
                'order'       => 'DESC',
            ]);
            $last_seen = '—';
            foreach ( $prev_orders as $po ) {
                if ( $po->get_date_created()->format( 'Y-m-d' ) < $order->get_date_created()->format( 'Y-m-d' ) ) {
                    $last_seen = human_time_diff( $po->get_date_created()->getTimestamp(), time() ) . ' ago';
                    break;
                }
            }

            // Totals and counts
            $all_orders = wc_get_orders([
                'customer_id' => $uid,
                'limit'       => -1,
                'status'      => [ 'completed', 'processing' ],
            ]);
            $order_count = count( $all_orders );
            $total_spent = array_sum( array_map( fn( $o ) => (float) $o->get_total(), $all_orders ) );

            // Status and since formatting
            $status = ( $uid && $order_count > 1 ) ? 'Returning' : 'New';
            $since  = human_time_diff( $created, time() ) . ' ago';

            // Build the row
            $data[ $key ] = [
                'Name'                  => $order->get_formatted_billing_full_name(),
                'Email'                 => $email,
                'City'                  => $order->get_billing_city(),
                'Status'                => $status,
                'Customer Since'        => $since,
                'Last Order Before Today' => $last_seen,
                'Orders'                => $order_count,
                'Total Spent'           => $total_spent,
                'Avg Order'             => $order_count
                                            ? round( $total_spent / $order_count, 2 )
                                            : 0,
            ];
        }
    }

    return array_values( $data );
}

/**
 * 7. Delivery City report (with totals) for XLSX export
 *
 * @param WC_Order[] $orders
 * @return array[]
 */
function aaa_generate_delivery_city_report( array $orders ): array {
    $cities = [];

    foreach ( $orders as $o ) {
        $city = $o->get_shipping_city() ?: '—';
        if ( ! isset( $cities[ $city ] ) ) {
            $cities[ $city ] = [
                'City'    => $city,
                'Orders'  => 0,
                'Revenue' => 0.0,
            ];
        }
        $cities[ $city ]['Orders']++;
        $cities[ $city ]['Revenue'] += (float) $o->get_total();
    }

    // Build flat rows
    $rows = array_values( $cities );

    // Append Totals row
    $sum_orders  = array_sum( array_column( $rows, 'Orders' ) );
    $sum_revenue = array_sum( array_column( $rows, 'Revenue' ) );
    $rows[] = [
        'City'    => 'Totals',
        'Orders'  => $sum_orders,
        'Revenue' => $sum_revenue,
    ];

    return $rows;
}


/**
 * 8. Payment Method summary (with totals & store credit) for XLSX export
 *
 * @param WC_Order[] $orders
 * @return array[]
 */
function aaa_generate_payment_summary( array $orders ): array {
    $pm                   = [];
    $total_orders         = 0;
    $total_amount         = 0.0;
    $store_credit_total   = 0.0;

    // 1) Build per-method buckets and accumulate store credit
    foreach ( $orders as $o ) {
        $store_credit_total += (float) $o->get_meta( '_funds_used', true );

        $method = $o->get_payment_method_title();
        if ( 'Store Credit' === $method ) {
            continue;
        }

        if ( ! isset( $pm[ $method ] ) ) {
            $pm[ $method ] = [
                'Method'  => $method,
                'Orders'  => 0,
                'Revenue' => 0.0,
            ];
        }

        $pm[ $method ]['Orders']++;
        $pm[ $method ]['Revenue'] += (float) $o->get_total();

        $total_orders++;
        $total_amount += (float) $o->get_total();
    }

    // 2) Build flat rows
    $rows = [];
    foreach ( $pm as $bucket ) {
        $rows[] = $bucket;
    }

    // 3) Append Totals row
    $rows[] = [
        'Method'  => 'Total',
        'Orders'  => $total_orders,
        'Revenue' => $total_amount,
    ];

    // 4) Append Store Credit Used row (if any)
    if ( $store_credit_total > 0 ) {
        $rows[] = [
            'Method'  => 'Store Credit Used',
            'Orders'  => '',                // no order count here
            'Revenue' => $store_credit_total,
        ];
    }

    return $rows;
}

/**
 * 9. Brand Summary for XLSX export
 *
 * @param WC_Order[] $orders
 * @return array[]
 */
function aaa_generate_brand_summary( array $orders ): array {
    $brands = [];

    foreach ( $orders as $order ) {
        $order_id = $order->get_id();

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $qty     = $item->get_quantity();
            $revenue = (float) $item->get_total();

            // Get all assigned 'berocket_brand' terms
            $terms = wp_get_post_terms( $product->get_id(), 'berocket_brand' ) ?: [];
            foreach ( $terms as $term ) {
                $bid = $term->term_id;
                if ( ! isset( $brands[ $bid ] ) ) {
                    $brands[ $bid ] = [
                        'Brand'   => $term->name,
                        'Qty'     => 0,
                        'Revenue' => 0,
                        'Orders'  => [],
                    ];
                }
                $brands[ $bid ]['Qty']     += $qty;
                $brands[ $bid ]['Revenue'] += $revenue;
                $brands[ $bid ]['Orders'][ $order_id ] = true;
            }
        }
    }

    // Build flat rows
    $rows = [];
    foreach ( $brands as $b ) {
        $rows[] = [
            'Brand'   => $b['Brand'],
            'Qty'     => $b['Qty'],
            'Revenue' => $b['Revenue'],
            'Orders'  => count( $b['Orders'] ),
        ];
    }

    // — Append Totals row —
    $sum_qty     = array_sum( array_column( $rows, 'Qty' ) );
    $sum_revenue = array_sum( array_column( $rows, 'Revenue' ) );
    $sum_orders  = array_sum( array_column( $rows, 'Orders' ) );

    $rows[] = [
        'Brand'   => 'Totals',
        'Qty'     => $sum_qty,
        'Revenue' => $sum_revenue,
        'Orders'  => $sum_orders,
    ];

    return $rows;
    }


/**
 * 10. Category Summary for XLSX export
 *
 * @param WC_Order[] $orders
 * @return array[]
 */
function aaa_generate_category_summary( array $orders ): array {
    $cats = [];

    foreach ( $orders as $order ) {
        $order_id = $order->get_id();

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $qty     = $item->get_quantity();
            $revenue = (float) $item->get_total();

            // Get all assigned product_cat terms
            $terms = get_the_terms( $product->get_id(), 'product_cat' ) ?: [];
            foreach ( $terms as $term ) {
                // bottom-level name
                $bottom = $term->name;
                // climb to top-level parent
                $top = $term;
                while ( $top->parent ) {
                    $top = get_term( $top->parent, 'product_cat' );
                    if ( is_wp_error( $top ) ) {
                        break;
                    }
                }
                $top_name = $top->name;

                if ( ! isset( $cats[ $bottom ] ) ) {
                    $cats[ $bottom ] = [
                        'Bottom Category' => $bottom,
                        'Top Category'    => $top_name,
                        'Qty'             => 0,
                        'Revenue'         => 0,
                        'Orders'          => [],
                    ];
                }

                $cats[ $bottom ]['Qty']     += $qty;
                $cats[ $bottom ]['Revenue'] += $revenue;
                $cats[ $bottom ]['Orders'][ $order_id ] = true;
            }
        }
    }

    // Build flat rows
    $rows = [];
    foreach ( $cats as $c ) {
        $rows[] = [
            'Bottom Category' => $c['Bottom Category'],
            'Top Category'    => $c['Top Category'],
            'Qty'             => $c['Qty'],
            'Revenue'         => $c['Revenue'],
            'Orders'          => count( $c['Orders'] ),
        ];
    }

    // — Append Totals row —
    $sum_qty     = array_sum( array_column( $rows, 'Qty' ) );
    $sum_revenue = array_sum( array_column( $rows, 'Revenue' ) );
    $sum_orders  = array_sum( array_column( $rows, 'Orders' ) );

    $rows[] = [
        'Bottom Category' => 'Totals',
        'Top Category'    => '',
        'Qty'             => $sum_qty,
        'Revenue'         => $sum_revenue,
        'Orders'          => $sum_orders,
    ];

    return $rows;
}

function aaa_generate_refunds_and_cancels($date) {
    $args=['post_type'=>'shop_order_refund','date_query'=>[['after'=>$date.' 00:00:00','before'=>$date.' 23:59:59']]];
    $refunds=get_posts($args);
    $rows=[];
    foreach($refunds as $r){
        $amount=get_post_meta($r->ID,'_refund_amount',true);
        $reason=get_post_meta($r->ID,'refund_reason',true);
        $admin=get_userdata(get_post_meta($r->ID,'_edit_last',true))->display_name;
        $orig = $r->get_parent_id();
        $rows[]=['Date'=>$r->post_date,'Order ID'=>$orig,'Amount'=>$amount,'Reason'=>$reason,'Processed By'=>$admin];
    }
    return $rows;
}
