<?php
/**
 * Plugin Name: AAA Daily Reporting (v3.1 Updated) (XHV98-ADMIN)
 * Description: Updated v3 loader with sorting JS support + email delivery. Report only generates on user action.
 * Version: 3.1.1
 * Author: WebMaster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$composer = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
if ( file_exists( $composer ) ) {
    require_once $composer;
} else {
    // Show an admin notice if someone forgot to `composer install`
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>AAA Daily Reporting:</strong> Missing vendor/autoload.php. Please run <code>composer install</code> in the plugin root.</p></div>';
    } );
}


// 1) Load installer
require_once plugin_dir_path( __FILE__ ) . 'includes/class-aaa-report-table-installer.php';
AAA_Report_Table_Installer::init();



// 3) Enqueue sorting CSS/JS on the report page
add_action( 'admin_enqueue_scripts', 'aaa_enqueue_report_assets' );
function aaa_enqueue_report_assets( $hook_suffix ) {
    // only run on our Daily Report v3 page
    if ( 'toplevel_page_aaa-daily-report-v3' !== $hook_suffix ) {
        return;
    }

    // scripts
    wp_enqueue_script(
        'aaa-report-sorting',
        plugin_dir_url( __FILE__ ) . 'assets/js/report-sorting.js',
        [],
        null,
        true
    );
    wp_enqueue_script(
        'aaa-report-accordion',
        plugin_dir_url( __FILE__ ) . 'assets/js/report-accordion.js',
        [ 'jquery' ],
        filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/report-accordion.js' ),
        true
    );

    // styles
    $css_path = plugin_dir_path( __FILE__ ) . 'assets/css/report-style.css';
    wp_enqueue_style(
        'aaa-report-style',
        plugin_dir_url( __FILE__ ) . 'assets/css/report-style.css',
        [],
        file_exists( $css_path ) ? filemtime( $css_path ) : false,
        'all'
    );
}

// 4) Admin menus
add_action( 'admin_menu', function() {
    add_menu_page(
        'AAA Daily Report (v3)',
        'Daily Report (v3)',
        'manage_woocommerce',
        'aaa-daily-report-v3',
        'aaa_render_daily_report_page_v3',
        'dashicons-chart-line',
        59
    );
    add_submenu_page(
        'aaa-daily-report-v3',
        'View Report',
        'View Report',
        'manage_woocommerce',
        'aaa-daily-report-v3',
        'aaa_render_daily_report_page_v3'
    );
    add_submenu_page(
        'aaa-daily-report-v3',
        'Email Settings',
        'Email Settings',
        'manage_woocommerce',
        'aaa-report-email-settings',
        'aaa_render_email_settings_page'
    );
});

// 5) Enqueue email-settings JS
add_action( 'admin_enqueue_scripts', function() {
    if ( empty( $_GET['page'] ) || $_GET['page'] !== 'aaa-report-email-settings' ) {
        return;
    }
    wp_enqueue_script(
        'aaa-email-settings-js',
        plugins_url( 'assets/js/email-settings.js', __FILE__ ),
        [ 'jquery' ],
        '3.1.2',
        true
    );
    wp_localize_script( 'aaa-email-settings-js', 'aaaEmailSettings', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'aaa_email_settings_nonce' ),
    ] );
});

// 6) Include all report modules
foreach ( [
    'report-export-xlsx',
    'email-settings-page',
    'report-orders-v3',
    'report-summary',
    'report-summary-top',
    'report-products',
    'report-products-summary',
    'report-customers-v2',
    'report-payments-v2',
    'report-brand-summary-v2',
    'report-category-summary-v2',
    'report-brands-categories-v2',
    'report-refunds-v2',
    'report-delivery-city',
    'email-scheduler',
    'report-tip-distribution',
] as $m ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/' . $m . '.php';
}

/**
 * Persist report data into our custom aaa_* tables.
 */
function aaa_persist_report( string $report_date, array $orders ): int {
    global $wpdb;
    $prefix = $wpdb->prefix;

    // 1) Master record
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$prefix}aaa_daily_report WHERE report_date = %s",
        $report_date
    ) );

    // 2) Aggregates
    $total_orders  = count( $orders );
    $total_revenue = 0;
    $total_qty     = 0;
    $cust_ids      = [];
    foreach ( $orders as $order ) {
        $total_revenue += (float) $order->get_total();
        foreach ( $order->get_items() as $item ) {
            $total_qty += $item->get_quantity();
        }
        $cust_ids[] = $order->get_customer_id();
    }
    $unique_customers = count( array_unique( $cust_ids ) );

    $master = [
        'report_date'     => $report_date,
        'created_at'      => current_time( 'mysql' ),
        'total_orders'    => $total_orders,
        'total_revenue'   => $total_revenue,
        'total_qty'       => $total_qty,
        'total_customers' => $unique_customers,
    ];

    // 3) Insert or update master
    if ( $exists ) {
        $wpdb->update( "{$prefix}aaa_daily_report", $master, [ 'id' => $exists ] );
        $report_id = $exists;
    } else {
        $wpdb->insert( "{$prefix}aaa_daily_report", $master );
        $report_id = $wpdb->insert_id;
    }

    // 4) Flush detail tables
    $wpdb->delete( "{$prefix}aaa_report_orders", [ 'report_id' => $report_id ] );
    $wpdb->delete( "{$prefix}aaa_report_product_sales", [ 'report_id' => $report_id ] );

    // 5) Insert order details + product‐sales details
    foreach ( $orders as $order ) {
        // -- build every value --
        $order_id    = $order->get_id();
        $date_obj    = $order->get_date_created();
        $order_date  = $date_obj->date_i18n( 'Y-m-d H:i:s' );
        $status      = ucfirst( $order->get_status() );
        $external    = $order->get_meta( '_external_order_number', true ) ?: '—';
        $customer    = $order->get_formatted_billing_full_name();
        $source      = $order->get_created_via() ?: 'unknown';
        $subtotal    = (float) $order->get_subtotal();
        $total       = (float) $order->get_total();
        $discount    = (float) $order->get_discount_total();
        $pct_off     = $subtotal > 0 ? round( ( $discount / $subtotal ) * 100, 1 ) : 0;
        $website_tip = (float) $order->get_meta( '_wpslash_tip', true );
        // fetch epayment tip from your index table
        $payment_row = (array) $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aaa_oc_payment_index WHERE order_id = %d",
                $order_id
            ),
            ARRAY_A
        );
        $epayment_tip = isset( $payment_row['epayment_tip'] ) ? (float) $payment_row['epayment_tip'] : 0;
        $shipping     = (float) $order->get_shipping_total();
        // fees for net_sales
        $fees_total = array_sum( array_map(
            fn( $f ) => (float) $f->get_total(),
            $order->get_fees()
        ) );
        $net_sales = $subtotal - $discount + $fees_total;
        // COGS & profit
        $qty    = 0;
        $unique = 0;
        $cogs   = 0;
        foreach ( $order->get_items() as $item ) {
            $unique++;
            $qty += $item->get_quantity();
            $product = $item->get_product();
            if ( $product ) {
                $atum_cost = $wpdb->get_var( $wpdb->prepare(
                    "SELECT purchase_price FROM {$wpdb->prefix}atum_product_data WHERE product_id = %d",
                    $product->get_id()
                ) );
                $meta_cost = get_post_meta( $product->get_id(), '_cogs_total_value', true );
                if ( $atum_cost !== null && $atum_cost !== '' ) {
                    $unit_cost = (float) $atum_cost;
                } elseif ( $meta_cost !== '' ) {
                    $unit_cost = (float) $meta_cost;
                } else {
                    $unit_cost = (float) $product->get_price() * 0.5;
                }
                $cogs += $unit_cost * $item->get_quantity();
            }
        }
        $profit       = $total - $cogs;
        $store_credit = (float) $order->get_meta( '_funds_used', true );
        $payment_method       = $order->get_payment_method_title();
        // build Real Payment string
// build Real Payment string (plain text amounts, with real “$”)
$real_methods = [];
$payment_map  = [
    'aaa_oc_cash_amount'       => 'COD',
    'aaa_oc_zelle_amount'      => 'Zelle',
    'aaa_oc_venmo_amount'      => 'Venmo',
    'aaa_oc_applepay_amount'   => 'ApplePay',
    'aaa_oc_cashapp_amount'    => 'CashApp',
    'aaa_oc_creditcard_amount' => 'Credit Card',
];
foreach ( $payment_map as $key => $label ) {
    $val = isset( $payment_row[ $key ] ) ? (float) $payment_row[ $key ] : 0;
    if ( $val > 0 ) {
        // 1) wc_price() → '<span>…</span>' with '&#36;'
        // 2) strip_tags() → '¢#36;42.00'
        // 3) html_entity_decode() → '$42.00'
        $plain_price = html_entity_decode( strip_tags( wc_price( $val ) ) );
        $real_methods[] = "{$label} ({$plain_price})";
    }
}
$real_payment_display = $real_methods ? implode( ', ', $real_methods ) : '—';
        $city = $order->get_shipping_city();
        $time = $date_obj->format( 'H:i' );

        // -- explicit insert into every column, in schema order --
        $wpdb->insert(
            "{$prefix}aaa_report_orders",
            [
              'report_id'             => $report_id,
              'order_date'            => $order_date,
              'status'                => $status,
              'order_id'              => $order_id,
              'external_order_number' => $external,
              'customer'              => $customer,
              'source'                => $source,
              'subtotal'              => $subtotal,
              'total'                 => $total,
              'discount'              => $discount,
              'percent_discount'      => $pct_off,
              'website_tip'           => $website_tip,
              'epayment_tip'          => $epayment_tip,
              'shipping'              => $shipping,
              'net_sales'             => $net_sales,
              'cogs'                  => $cogs,
              'profit'                => $profit,
              'items_count'           => $qty,
              'unique_items_count'    => $unique,
              'store_credit'          => $store_credit,
              'payment_method'        => $payment_method,
              'real_payment'          => $real_payment_display,
              'city'                  => $city,
              'time'                  => $time,
            ],
            [
              '%d','%s','%s','%d','%s','%s','%s',
              '%f','%f','%f','%f','%f','%f','%f','%f','%f','%f','%d','%d','%f','%s','%s','%s','%s'
            ]
        );

        // product‐sales detail remains unchanged...
        foreach ( $order->get_items() as $item ) {
            $wpdb->insert( "{$prefix}aaa_report_product_sales", [
                'report_id'     => $report_id,
                'order_item_id' => $item->get_id(),
                'product_id'    => $item->get_product_id(),
                'brand_id'      => null,
                'category_id'   => null,
                'quantity'      => $item->get_quantity(),
                'revenue'       => $item->get_total(),
            ] );
        }
    }

// 4) PRODUCT SUMMARY
	$product_summary = [];
	foreach ( $orders as $order ) {
	    foreach ( $order->get_items() as $item ) {
	        $pid = $item->get_product_id();
	        if ( ! isset( $product_summary[ $pid ] ) ) {
	            $product_summary[ $pid ] = [
	                'qty'      => 0,
	                'revenue'  => 0,
	                'orders'   => [],
	            ];
	        }
	        $product_summary[ $pid ]['qty']     += $item->get_quantity();
	        $product_summary[ $pid ]['revenue'] += $item->get_total();
	        $product_summary[ $pid ]['orders'][ $order->get_id() ] = true;
	    }
	}
	$wpdb->delete( "{$prefix}aaa_report_product_summary", ['report_id'=>$report_id] );
	foreach ( $product_summary as $pid => $data ) {
	    $wpdb->insert( "{$prefix}aaa_report_product_summary", [
	        'report_id'    => $report_id,
	        'product_id'   => $pid,
	        'total_qty'    => $data['qty'],
	        'total_revenue'=> $data['revenue'],
	        'total_orders' => count( $data['orders'] ),
	    ] );
	}

// 5) BRAND SUMMARY (very similar to your on-screen code)
$brand_data = [];
foreach ( $orders as $order ) {
    foreach ( $order->get_items() as $item ) {
        // Get the product object (may be false if product was deleted)
        $product = $item->get_product();
        if ( ! $product ) {
            continue; // skip items for deleted/invalid products
        }

        // Fetch brand terms, skipping if there’s an error
        $brands = wp_get_post_terms( $product->get_id(), 'berocket_brand' );
        if ( is_wp_error( $brands ) ) {
            continue;
        }

        foreach ( $brands as $brand ) {
            $term_id = $brand->term_id;

            // Initialize our bucket if this is the first time we see this brand
            if ( ! isset( $brand_data[ $term_id ] ) ) {
                $brand_data[ $term_id ] = [
                    'qty'     => 0,
                    'revenue' => 0,
                    'orders'  => [], // we'll use the order IDs as keys to dedupe
                ];
            }

            // Accumulate quantities and revenue
            $brand_data[ $term_id ]['qty']     += $item->get_quantity();
            $brand_data[ $term_id ]['revenue'] += $item->get_total();

            // Record that this order contributed (deduped by key)
            $brand_data[ $term_id ]['orders'][ $order->get_id() ] = true;
        }
    }
}
	$wpdb->delete( "{$prefix}aaa_report_brand_summary", ['report_id'=>$report_id] );
	foreach ( $brand_data as $bid => $data ) {
	    $wpdb->insert( "{$prefix}aaa_report_brand_summary", [
	        'report_id'    => $report_id,
	        'brand_id'     => $bid,
	        'total_qty'    => $data['qty'],
	        'total_revenue'=> $data['revenue'],
	        'total_orders' => count( $data['orders'] ),
	    ] );
	}

// 6) CATEGORY SUMMARY
$cat_data = [];

foreach ( $orders as $order ) {
    foreach ( $order->get_items() as $item ) {
        // Get the product object (may be false if the product was deleted)
        $product = $item->get_product();
        if ( ! $product ) {
            continue; // skip deleted/invalid products
        }

        // Fetch category terms, skip on error or empty
        $terms = get_the_terms( $product->get_id(), 'product_cat' );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            continue;
        }

        foreach ( $terms as $cat ) {
            // Initialize bucket if first time we see this category
            if ( ! isset( $cat_data[ $cat->term_id ] ) ) {
                // Find top‐level parent for labeling
                $top = $cat;
                while ( $top->parent ) {
                    $parent = get_term( $top->parent, 'product_cat' );
                    if ( is_wp_error( $parent ) || ! $parent ) {
                        break; // bail if parent lookup fails
                    }
                    $top = $parent;
                }

                $cat_data[ $cat->term_id ] = [
                    'bottom_id' => $cat->term_id,
                    'top_name'  => $top->name,
                    'qty'       => 0,
                    'revenue'   => 0,
                    'orders'    => [], // keys will be order IDs
                ];
            }

            // Accumulate quantity and revenue
            $cat_data[ $cat->term_id ]['qty']     += $item->get_quantity();
            $cat_data[ $cat->term_id ]['revenue'] += $item->get_total();

            // Record order contribution (deduped by key)
            $cat_data[ $cat->term_id ]['orders'][ $order->get_id() ] = true;
        }
    }
}
	foreach ( $cat_data as $cid => $data ) {
	    $wpdb->insert( "{$prefix}aaa_report_category_summary", [
	        'report_id'    => $report_id,
	        'category_id'  => $data['bottom_id'],
	        'total_qty'    => $data['qty'],
	        'total_revenue'=> $data['revenue'],
	        'total_orders' => count( $data['orders'] ),
	    ] );
	}

	// 7) CUSTOMER SUMMARY
	$cust_data = [];
	foreach ( $orders as $order ) {
	    $cid = $order->get_customer_id();
	    if ( ! isset( $cust_data[ $cid ] ) ) {
	        $cust_data[ $cid ] = [
	            'orders'      => [],
	            'total_spent' => 0,
	            'first_date'  => null,
	            'last_date'   => null,
	            'city'        => $order->get_billing_city(),
	        ];
	    }
	    $time = $order->get_date_created()->getTimestamp();
	    $cust_data[ $cid ]['orders'][] = $order->get_id();
	    $cust_data[ $cid ]['total_spent'] += $order->get_total();
	    if ( ! $cust_data[ $cid ]['first_date'] || $time < $cust_data[ $cid ]['first_date'] ) {
	        $cust_data[ $cid ]['first_date'] = $time;
	    }
	    if ( ! $cust_data[ $cid ]['last_date'] || $time > $cust_data[ $cid ]['last_date'] ) {
	        $cust_data[ $cid ]['last_date'] = $time;
	    }
	}
	$wpdb->delete( "{$prefix}aaa_report_customer_summary", ['report_id'=>$report_id] );
	foreach ( $cust_data as $cid => $d ) {
	    $orders_count = count( $d['orders'] );
	    $wpdb->insert( "{$prefix}aaa_report_customer_summary", [
	        'report_id'         => $report_id,
	        'customer_id'       => $cid,
	        'lifetime_orders'   => $orders_count,
	        'lifetime_spent'    => $d['total_spent'],
	        'avg_order_value'   => $orders_count ? $d['total_spent']/$orders_count : 0,
	        'first_order_date'  => date( 'Y-m-d', $d['first_date'] ),
	        'last_order_date'   => date( 'Y-m-d', $d['last_date'] ),
	        'billing_city'      => $d['city'],
	    ] );
	}

	// 8) PAYMENT SUMMARY
	$pay_data = [];
	foreach ( $orders as $order ) {
	    $method = $order->get_payment_method();
	    if ( ! isset( $pay_data[ $method ] ) ) {
	        $pay_data[ $method ] = [
	            'count'   => 0,
	            'revenue' => 0,
	        ];
	    }
	    $pay_data[ $method ]['count']++;
	    $pay_data[ $method ]['revenue'] += $order->get_total();
	}
	$wpdb->delete( "{$prefix}aaa_report_payment_summary", ['report_id'=>$report_id] );
	foreach ( $pay_data as $method => $d ) {
	    $wpdb->insert( "{$prefix}aaa_report_payment_summary", [
	        'report_id'    => $report_id,
	        'payment_method'=> $method,
	        'order_count'   => $d['count'],
	        'total_revenue' => $d['revenue'],
	    ] );
	}

	// 9) REFUNDS (requires WooCommerce refund lookup)
	$refunds = wc_get_orders([
	    'type'        => 'refund',
	    'date_query'  => [
	        [ 'after' => $report_date, 'inclusive' => true ],
	        [ 'before'=> $report_date, 'inclusive' => true ],
	    ],
	    'limit'       => -1,
	]);
	$wpdb->delete( "{$prefix}aaa_report_refunds", ['report_id'=>$report_id] );
	foreach ( $refunds as $r ) {
	    $wpdb->insert( "{$prefix}aaa_report_refunds", [
	        'report_id'   => $report_id,
	        'refund_id'   => $r->get_id(),
	        'order_id'    => $r->get_parent_id(),
	        'amount'      => $r->get_total(),
	        'reason'      => $r->get_reason() ?? '',
	        'admin_id'    => $r->get_meta('_refund_by'),
	        'refunded_at' => $r->get_date_created()->date_i18n('Y-m-d H:i:s'),
	    ] );
	}

	// 10) DELIVERY CITY SUMMARY
	$city_data = [];
	foreach ( $orders as $order ) {
	    $city = $order->get_shipping_city() ?: $order->get_billing_city();
	    if ( ! isset( $city_data[ $city ] ) ) {
	        $city_data[ $city ] = [
	            'count'   => 0,
	            'revenue' => 0,
	        ];
	    }
	    $city_data[ $city ]['count']++;
	    $city_data[ $city ]['revenue'] += $order->get_total();
	}
	$wpdb->delete( "{$prefix}aaa_report_delivery_city_summary", ['report_id'=>$report_id] );
	foreach ( $city_data as $city => $d ) {
	    $wpdb->insert( "{$prefix}aaa_report_delivery_city_summary", [
	        'report_id'    => $report_id,
	        'city'         => $city,
	        'order_count'  => $d['count'],
	        'total_revenue'=> $d['revenue'],
	    ] );
	}

	return $report_id;

}

/**
 * Render the Daily Report page, PDF/CSV download, and on-screen accordions.
 */
function aaa_render_daily_report_page_v3() {
    // Persist first
    if ( isset( $_GET['report_date'] ) ) {
        $report_date = sanitize_text_field( $_GET['report_date'] );
        $orders = aaa_get_orders_for_date( $report_date );
        if ( ! empty( $orders ) ) {
            aaa_persist_report( $report_date, $orders );
        }
    }

    // PDF Download Handler
// PDF Download Handler
if ( isset( $_GET['download_pdf'], $_GET['report_date'] ) ) {
    // nonce check
    if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'aaa_download_report_' . $_GET['report_date'] ) ) {
        wp_die( 'Invalid request.' );
    }

    $report_date = sanitize_text_field( $_GET['report_date'] );
    $orders      = aaa_get_orders_for_date( $report_date );

    // build the same HTML you already have
    ob_start();
      echo '<h2>AAA Daily Report — ' . date_i18n( 'F j, Y', strtotime( $report_date ) ) . '</h2>';
      aaa_render_report_summary(            $orders );
      aaa_render_top_summary_section(       $orders );
      aaa_render_orders_section_v3(         $orders );
      aaa_render_product_breakdown(         $orders );
      aaa_render_product_summary_table(     $orders );
      aaa_render_customer_summary_v2(       $orders );
      aaa_render_delivery_city_report(      $orders );
      aaa_render_payment_summary_v2(        $orders );
      aaa_render_brands_categories_summary_v2( $orders );
      aaa_render_brand_summary_v2(          $orders );
      aaa_render_category_summary_v2(       $orders );
      aaa_render_refunds_and_cancels_v2(    $report_date );
      aaa_render_tip_distribution( $orders );
    $html = ob_get_clean();

    // load your admin CSS
    $css_file = plugin_dir_path( __FILE__ ) . 'assets/css/report-style.css';
    $css      = is_file( $css_file ) ? file_get_contents( $css_file ) : '';

    // tack on the PDF-only rules
    $css .= "
@page {
  size: A4 landscape;
  margin: 8mm;
}
table.widefat th {
  font-size: 10px !important;
  font-weight: 700 !important;
}
h1 {
  page-break-before: always !important;
  margin-top: 0 !important;
}
table.widefat {
  page-break-inside: avoid !important;
}
";

    // wrap in a full HTML document
    $full_html  = '<!doctype html><html><head><meta charset="utf-8">';
    $full_html .= '<style>' . $css . '</style>';
    $full_html .= '</head><body>' . $html . '</body></html>';

    // instantiate Dompdf with HTML5 parser enabled
    $options = new \Dompdf\Options();
    $options->setIsHtml5ParserEnabled( true );
    $dompdf = new \Dompdf\Dompdf( [ 'options' => $options ] );

    $dompdf->loadHtml( $full_html );
    $dompdf->setPaper( 'A4', 'landscape' );
    $dompdf->render();

    // clear any stray output buffers
    while ( ob_get_level() ) {
        ob_end_clean();
    }

    // stream the PDF to browser
    $dompdf->stream(
        'aaa-report-' . $report_date . '.pdf',
        [ 'Attachment' => 1 ]
    );
    exit;
}

	// Excel Download Handler
	if ( isset( $_GET['download_xlsx'], $_GET['report_date'] ) ) {
    if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'aaa_download_report_' . $_GET['report_date'] ) ) {
         wp_die( 'Invalid request.' );
     }
	    aaa_handle_xlsx_export();
	    exit;
	}


    // On-screen output (top of file)
    echo '<div class="wrap"><h1>AAA Daily Report (v3.1)</h1>';
    echo '<style>
      .aaa-accordion { margin-bottom:1em; border:1px solid #ddd; border-radius:4px; }
      .aaa-accordion summary { padding:12px 16px; background:#f5f7fa; cursor:pointer; position:relative; }
      .aaa-accordion summary::after { content:"▾"; position:absolute; right:16px; transition:transform .2s; }
      .aaa-accordion[open] summary::after { transform:rotate(-180deg); }
      .aaa-accordion div.content { padding:12px 16px; border-top:1px solid #ddd; }
      .aaa-accordion details:not([open]) > .content { display:none; }
    </style>';

    $report_date = isset( $_GET['report_date'] )
    ? sanitize_text_field( wp_unslash( $_GET['report_date'] ) )
        : wp_date( 'Y-m-d', current_time( 'timestamp' ) );

echo '<form method="get" style="margin-bottom:1.5em;">'
   . '<input type="hidden" name="page" value="aaa-daily-report-v3">'
   . '<label>Select Date:</label> '
   . '<input type="date" name="report_date" value="' . esc_attr( $report_date ) . '"> '
   . '<button type="submit" name="generate_report" class="button button-primary">View Report</button> '

// PDF button
   . '<a href="'
     . esc_url( add_query_arg( [
         'page'         => 'aaa-daily-report-v3',
         'report_date'  => $report_date,
         'download_pdf' => '1',
         '_wpnonce'     => wp_create_nonce( 'aaa_download_report_' . $report_date ),
       ], admin_url( 'admin.php' ) ) )
     . '" class="button">Download PDF</a> '

// Excel button
   . '<a href="'
     . esc_url( add_query_arg( [
         'page'          => 'aaa-daily-report-v3',
         'report_date'   => $report_date,
         'download_xlsx' => '1',
         '_wpnonce'      => wp_create_nonce( 'aaa_download_report_' . $report_date ),
       ], admin_url( 'admin.php' ) ) )
     . '" class="button">Download Excel</a>'

   . '</form>';

    if ( isset( $_GET['generate_report'] ) ) {
        $orders = aaa_get_orders_for_date( $report_date );
        if ( ! empty( $orders ) ) {
            echo '<details class="aaa-accordion" open><summary>Summary</summary><div class="content">';
            aaa_render_report_summary( $orders );
            echo '</div></details>';

            echo '<details class="aaa-accordion"><summary>Top Summary</summary><div class="content">';
            aaa_render_top_summary_section( $orders );
            echo '</div></details>';

            echo '<details class="aaa-accordion"><summary>Orders</summary><div class="content">';
            aaa_render_orders_section_v3( $orders );
            echo '</div></details>';

            echo '<details class="aaa-accordion"><summary>Product Breakdown By Order</summary><div class="content">';
            aaa_render_product_breakdown( $orders );
            echo '</div></details>';

            echo '<details class="aaa-accordion"><summary>Product Summary</summary><div class="content">';
            aaa_render_product_summary_table( $orders );
            echo '</div></details>';

            echo '<details class="aaa-accordion"><summary>Customers</summary><div class="content">';
            aaa_render_customer_summary_v2( $orders );
            echo '</div></details>';

            echo '<details class="aaa-accordion"><summary>Delivery City</summary><div class="content">';
            aaa_render_delivery_city_report( $orders );
            echo '</div></details>';

            echo '<details class="aaa-accordion"><summary>Payments</summary><div class="content">';
            aaa_render_payment_summary_v2( $orders );
            echo '</div></details>';

            echo '<details class="aaa-accordion"><summary>Brands &amp; Categories</summary><div class="content">';
            aaa_render_brands_categories_summary_v2( $orders );
            echo '</div></details>';
	    
	    echo '<details class="aaa-accordion"><summary>Brands</summary><div class="content">';
            aaa_render_brand_summary_v2 ( $orders );
            echo '</div></details>';
	    
	    echo '<details class="aaa-accordion"><summary>Categories</summary><div class="content">';
            aaa_render_category_summary_v2 ( $orders );
            echo '</div></details>';

            echo '<details class="aaa-accordion"><summary>Refunds &amp; Cancels</summary><div class="content">';
            aaa_render_refunds_and_cancels_v2( $report_date );
            echo '</div></details>';
	    
	    echo '<details class="aaa-accordion"><summary>Tips</summary><div class="content">';
            aaa_render_tip_distribution( $orders );
            echo '</div></details>';

        } else {
            echo '<p>No orders found for that date.</p>';
        }
    }

    echo '</div>';
}
