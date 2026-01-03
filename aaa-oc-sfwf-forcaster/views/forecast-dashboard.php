<?php
/**
 * Version: 1.3.0 (2025-12-31)
 *
 * This release improves horizontal scrolling and header rendering.  Column
 * headers no longer wrap or break words thanks to `white‑space: nowrap` on
 * header and data cells.  The table now sets its width via an inline
 * attribute (`style="width:100%"`) so DataTables can properly calculate
 * widths when `scrollX` is enabled.  See README for full changelog.
 * Modified view file for the forecast grid. This version enhances the original
 * by adding a consolidated summary meta key, proper sorting via data-order
 * attributes, coloured statuses, group-specific column classes and backgrounds,
 * and a header offset for the WordPress admin bar. See README for details.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

require_once SFWF_ROOT . 'helpers/forecast-column-definitions.php';

// Retrieve the column definitions. Each definition includes a label, type and group.
$columns = SFWF_Column_Definitions::get_columns();

// Prepare human-readable labels and tooltips for sales statuses. These will be
// used when rendering the forecast_sales_status column. Thresholds are pulled
// from settings so the tooltips reflect current configuration.
$status_labels = [
    'active'         => __( 'Active', 'aaa-wf-sfwf' ),
    'not_moving_t1'  => __( 'Not Moving Tier 1', 'aaa-wf-sfwf' ),
    'not_moving_t2'  => __( 'Not Moving Tier 2', 'aaa-wf-sfwf' ),
    'not_moving_t3'  => __( 'Not Moving Tier 3', 'aaa-wf-sfwf' ),
];
$status_tooltips = [];
if ( class_exists( 'WF_SFWF_Settings' ) ) {
    $tier1 = intval( WF_SFWF_Settings::get( 'not_moving_t1_days', 14 ) );
    $tier2 = intval( WF_SFWF_Settings::get( 'not_moving_t2_days', 30 ) );
    $tier3 = intval( WF_SFWF_Settings::get( 'not_moving_t3_after_best_sold_by', 15 ) );
    $status_tooltips = [
        'active'        => __( 'Product is selling within expected time frame.', 'aaa-wf-sfwf' ),
        'not_moving_t1' => sprintf( __( 'No sale for %d days', 'aaa-wf-sfwf' ), $tier1 ),
        'not_moving_t2' => sprintf( __( 'No sale for %d days', 'aaa-wf-sfwf' ), $tier2 ),
        'not_moving_t3' => sprintf( __( 'Expired best-sold-by window plus %d days buffer', 'aaa-wf-sfwf' ), $tier3 ),
    ];
}

// Load lists of categories and brands for filter dropdowns. Brands are loaded
// based on a configured taxonomy slug with sensible fallbacks.
$category_terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );

$brand_terms = [];
$brand_slug  = null;
if ( class_exists( 'WF_SFWF_Settings' ) ) {
    $brand_slug = WF_SFWF_Settings::get( 'brand_taxonomy_slug', '' );
}
if ( ! empty( $brand_slug ) ) {
    $brand_terms = get_terms( [ 'taxonomy' => $brand_slug, 'hide_empty' => false ] );
}
if ( empty( $brand_terms ) ) {
    $brand_terms = get_terms( [ 'taxonomy' => 'pwb-brand', 'hide_empty' => false ] );
    if ( empty( $brand_terms ) ) {
        $brand_terms = get_terms( [ 'taxonomy' => 'product_brand', 'hide_empty' => false ] );
        if ( empty( $brand_terms ) ) {
            $brand_terms = [];
        }
    }
}

// Query products that are eligible for reordering. Only simple, published
// products are included and gating logic is applied later.
$args = [
    'status' => [ 'publish' ],
    'type'   => [ 'simple' ],
    'limit'  => -1,
    'return' => 'ids',
    'meta_query' => [
        'relation' => 'OR',
        [
            'key'     => 'forecast_enable_reorder',
            'value'   => 'yes',
            'compare' => '=',
        ],
        [
            'key'     => 'forecast_is_must_stock',
            'value'   => 'yes',
            'compare' => '=',
        ],
        [
            'key'     => 'forecast_force_reorder',
            'value'   => 'yes',
            'compare' => '=',
        ],
    ],
];
$product_ids = wc_get_products( $args );

// Retrieve exclusion thresholds from settings. New or high-stock products may
// be omitted from the grid based on these thresholds.
$enable_stock_threshold = false;
$stock_threshold_qty    = 0;
$enable_new_threshold   = false;
$new_product_days       = 7;
if ( class_exists( 'WF_SFWF_Settings' ) ) {
    $enable_stock_threshold = ( WF_SFWF_Settings::get( 'enable_stock_threshold', 'no' ) === 'yes' );
    $stock_threshold_qty    = intval( WF_SFWF_Settings::get( 'stock_threshold_qty', 0 ) );
    $enable_new_threshold   = ( WF_SFWF_Settings::get( 'enable_new_product_threshold', 'no' ) === 'yes' );
    $new_product_days       = intval( WF_SFWF_Settings::get( 'new_product_days_threshold', 7 ) );
}

?>
<div class="wrap">
    <h1>Stock Forecast Report</h1>

    <?php
    // Show a count of reorder-enabled products. This count updates live via JS.
    $total_products = count( $product_ids );
    ?>
    <p><strong><?php _e( 'Total Products:', 'aaa-wf-sfwf' ); ?> <span id="sfwf-total-count"><?php echo intval( $total_products ); ?></span></strong></p>

    <?php
    // Build a map of group names to column indexes. Columns #0–6 (ID etc.) are
    // fixed, so group columns start at index 7. We use this map when toggling
    // column visibility via the checkboxes in the UI.
    $groups = [];
    $i      = 7;
    foreach ( $columns as $def ) {
        $g = isset( $def['group'] ) ? $def['group'] : 'Other';
        $groups[ $g ][] = $i++;
    }
    ?>

    <!-- Forecast action controls: rebuild all or just flagged products -->
    <div style="margin-bottom: 1em;">
        <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block; margin-right: 10px;">
            <?php wp_nonce_field( 'sfwf_run_forecast', 'sfwf_run_forecast_nonce' ); ?>
            <input type="hidden" name="action" value="sfwf_run_forecast" />
            <input type="hidden" name="mode" value="rebuild_all" />
            <?php submit_button( __( 'Rebuild All', 'aaa-wf-sfwf' ), 'secondary', 'submit', false ); ?>
        </form>
        <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
            <?php wp_nonce_field( 'sfwf_run_forecast', 'sfwf_run_forecast_nonce' ); ?>
            <input type="hidden" name="action" value="sfwf_run_forecast" />
            <input type="hidden" name="mode" value="rebuild_flagged" />
            <?php submit_button( __( 'Update Flagged', 'aaa-wf-sfwf' ), 'secondary', 'submit', false ); ?>
        </form>
        <?php if ( isset( $_GET['forecast_scheduled'] ) && $_GET['forecast_scheduled'] === '1' ) : ?>
            <span class="sfwf-scheduled-notice" style="margin-left:10px; color:#007cba;"><?php esc_html_e( 'Forecast scheduled! It will run in the background shortly.', 'aaa-wf-sfwf' ); ?></span>
        <?php endif; ?>
    </div>

    <!-- Filter dropdowns -->
    <div class="sfwf-filter-bar" style="margin-bottom: 0.5em;">
        <label>
            <?php esc_html_e( 'Stock Status:', 'aaa-wf-sfwf' ); ?>
            <select id="sfwf-filter-stock" style="min-width:120px;">
                <option value=""><?php esc_html_e( 'All', 'aaa-wf-sfwf' ); ?></option>
                <option value="instock"><?php esc_html_e( 'In Stock', 'aaa-wf-sfwf' ); ?></option>
                <option value="outofstock"><?php esc_html_e( 'Out of Stock', 'aaa-wf-sfwf' ); ?></option>
            </select>
        </label>
        <label style="margin-left:10px;">
            <?php esc_html_e( 'Category:', 'aaa-wf-sfwf' ); ?>
            <select id="sfwf-filter-category" style="min-width:160px;">
                <option value=""><?php esc_html_e( 'All', 'aaa-wf-sfwf' ); ?></option>
                <?php foreach ( $category_terms as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->name ); ?>"><?php echo esc_html( $term->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left:10px;">
            <?php esc_html_e( 'Brand:', 'aaa-wf-sfwf' ); ?>
            <select id="sfwf-filter-brand" style="min-width:160px;">
                <option value=""><?php esc_html_e( 'All', 'aaa-wf-sfwf' ); ?></option>
                <?php foreach ( $brand_terms as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->name ); ?>"><?php echo esc_html( $term->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left:10px;">
            <?php esc_html_e( 'Last Sold Older Than:', 'aaa-wf-sfwf' ); ?>
            <select id="sfwf-filter-last-sold-days" style="min-width:120px;">
                <option value=""><?php esc_html_e( 'All', 'aaa-wf-sfwf' ); ?></option>
                <option value="7">7 <?php esc_html_e( 'days', 'aaa-wf-sfwf' ); ?></option>
                <option value="14">14 <?php esc_html_e( 'days', 'aaa-wf-sfwf' ); ?></option>
                <option value="30">30 <?php esc_html_e( 'days', 'aaa-wf-sfwf' ); ?></option>
                <option value="45">45 <?php esc_html_e( 'days', 'aaa-wf-sfwf' ); ?></option>
                <option value="60">60 <?php esc_html_e( 'days', 'aaa-wf-sfwf' ); ?></option>
                <option value="90">90 <?php esc_html_e( 'days', 'aaa-wf-sfwf' ); ?></option>
                <option value="120">120 <?php esc_html_e( 'days', 'aaa-wf-sfwf' ); ?></option>
                <option value="180">180 <?php esc_html_e( 'days', 'aaa-wf-sfwf' ); ?></option>
            </select>
        </label>
    </div>

    <!-- Column group toggles -->
    <div class="sfwf-group-toggle-bar" style="margin-bottom: 0.5em;">
        <?php foreach ( $groups as $group_name => $indices ) : ?>
            <label style="margin-right: 10px;">
                <input type="checkbox" class="sfwf-group-toggle" data-group="<?php echo esc_attr( $group_name ); ?>" checked />
                <?php echo esc_html( $group_name ); ?>
            </label>
        <?php endforeach; ?>
    </div>

    <table id="sfwf-forecast-table" class="wp-list-table widefat fixed striped" style="width:100%">
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th><?php esc_html_e( 'ID', 'aaa-wf-sfwf' ); ?></th>
                <th><?php esc_html_e( 'Product', 'aaa-wf-sfwf' ); ?></th>
                <th><?php esc_html_e( 'Last Processed', 'aaa-wf-sfwf' ); ?></th>
                <!-- Hidden filter columns: Stock, Category, Brand -->
                <th style="display:none;"><?php esc_html_e( 'Stock', 'aaa-wf-sfwf' ); ?></th>
                <th style="display:none;"><?php esc_html_e( 'Category', 'aaa-wf-sfwf' ); ?></th>
                <th style="display:none;"><?php esc_html_e( 'Brand', 'aaa-wf-sfwf' ); ?></th>
                <?php foreach ( $columns as $key => $col ) :
                    $g_name = isset( $col['group'] ) ? $col['group'] : 'Other';
                    $group_slug = sanitize_html_class( $g_name );
                    ?>
                    <th class="sfwf-group-<?php echo esc_attr( $group_slug ); ?>"><?php echo esc_html( $col['label'] ); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $row_num = 1;
            foreach ( $product_ids as $product_id ) :
                $product = wc_get_product( $product_id );
                if ( ! $product ) {
                    continue;
                }
                // Determine control flags for this product.
                $enable_reorder = get_post_meta( $product_id, 'forecast_enable_reorder', true );
                $do_not_reorder = get_post_meta( $product_id, 'forecast_do_not_reorder', true );
                $must_stock     = get_post_meta( $product_id, 'forecast_is_must_stock', true );
                $force_reorder  = get_post_meta( $product_id, 'forecast_force_reorder', true );

                // Apply gating logic: exclude if Do Not Reorder is yes and no override is present;
                // exclude if enable_reorder is not yes and no override.
                $override = ( $must_stock === 'yes' || $force_reorder === 'yes' );
                if ( $do_not_reorder === 'yes' && ! $override ) {
                    continue;
                }
                if ( $enable_reorder !== 'yes' && ! $override ) {
                    continue;
                }

                // Exclude by stock threshold
                if ( $enable_stock_threshold && $stock_threshold_qty > 0 ) {
                    $qty = $product->get_stock_quantity();
                    if ( ! is_null( $qty ) && $qty >= $stock_threshold_qty ) {
                        continue;
                    }
                }
                // Exclude new products by threshold
                if ( $enable_new_threshold && $new_product_days > 0 ) {
                    $first_sold_date = get_post_meta( $product_id, 'forecast_first_sold_date', true );
                    if ( empty( $first_sold_date ) && class_exists( 'WF_SFWF_Forecast_Timeline' ) ) {
                        $timeline = WF_SFWF_Forecast_Timeline::get_timeline( $product_id );
                        if ( isset( $timeline['forecast_first_sold_date'] ) ) {
                            $first_sold_date = $timeline['forecast_first_sold_date'];
                        }
                    }
                    if ( $first_sold_date ) {
                        $days_since = floor( ( current_time( 'timestamp' ) - strtotime( $first_sold_date ) ) / DAY_IN_SECONDS );
                        if ( $days_since <= $new_product_days ) {
                            continue;
                        }
                    }
                }

                // Compute days since last sold for filtering (used in custom filter)
                $last_sold_date_meta   = get_post_meta( $product_id, 'forecast_last_sold_date', true );
                $days_since_last_sold  = '';
                if ( ! empty( $last_sold_date_meta ) ) {
                    $timestamp = strtotime( $last_sold_date_meta );
                    if ( $timestamp ) {
                        $days_since_last_sold = floor( ( current_time( 'timestamp' ) - $timestamp ) / DAY_IN_SECONDS );
                    }
                }

                // Determine stock status for row styling
                $stock_status = $product->get_stock_status();
                $row_class    = '';
                if ( $stock_status === 'outofstock' ) {
                    $row_class = 'sfwf-stock-outofstock';
                } elseif ( $stock_status === 'instock' ) {
                    $row_class = 'sfwf-stock-instock';
                }
                // Determine sales status for row or cell styling
                $sales_status = get_post_meta( $product_id, 'forecast_sales_status', true );
                $sales_status = $sales_status ? $sales_status : 'active';
                // Add sales class to row_class for convenience (row could have multiple classes)
                $row_class .= ' sfwf-sales-' . sanitize_html_class( $sales_status );
                ?>
                <tr data-last-sold-days="<?php echo esc_attr( $days_since_last_sold ); ?>" class="<?php echo esc_attr( trim( $row_class ) ); ?>">
                    <td><?php echo intval( $row_num++ ); ?></td>
                    <td><?php echo esc_html( $product_id ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>">
                            <?php echo esc_html( $product->get_name() ); ?>
                        </a>
                    </td>
                    <td>
                        <?php
                        if ( class_exists( 'WF_SFWF_Forecast_Index' ) ) {
                            $lp = WF_SFWF_Forecast_Index::get_last_processed( $product_id );
                            echo $lp ? esc_html( date_i18n( 'M j, Y H:i', strtotime( $lp ) ) ) : '—';
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <!-- Hidden filter cells: Stock status, Categories, Brands -->
                    <td style="display:none;">
                        <?php echo esc_html( $stock_status ); ?>
                    </td>
                    <td style="display:none;">
                        <?php
                        $cats = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'names' ] );
                        echo esc_html( implode( ', ', (array) $cats ) );
                        ?>
                    </td>
                    <td style="display:none;">
                        <?php
                        // Determine brand terms from configured slug or fallback slugs
                        $brand_terms_list = [];
                        $configured_slug  = null;
                        if ( class_exists( 'WF_SFWF_Settings' ) ) {
                            $configured_slug = WF_SFWF_Settings::get( 'brand_taxonomy_slug', '' );
                        }
                        if ( ! empty( $configured_slug ) ) {
                            $brand_terms_list = get_the_terms( $product_id, $configured_slug );
                        }
                        if ( empty( $brand_terms_list ) || is_wp_error( $brand_terms_list ) ) {
                            $brand_terms_list = get_the_terms( $product_id, 'pwb-brand' );
                            if ( empty( $brand_terms_list ) || is_wp_error( $brand_terms_list ) ) {
                                $brand_terms_list = get_the_terms( $product_id, 'product_brand' );
                                if ( empty( $brand_terms_list ) || is_wp_error( $brand_terms_list ) ) {
                                    $brand_terms_list = [];
                                }
                            }
                        }
                        $brand_names = [];
                        foreach ( (array) $brand_terms_list as $b ) {
                            if ( is_object( $b ) ) {
                                $brand_names[] = $b->name;
                            }
                        }
                        echo esc_html( implode( ', ', $brand_names ) );
                        ?>
                    </td>
                    <?php foreach ( $columns as $key => $col ) :
                        $v = get_post_meta( $product_id, $key, true );
                        // Build the CSS class for this cell based on its group
                        $g_name      = isset( $col['group'] ) ? $col['group'] : 'Other';
                        $group_slug  = sanitize_html_class( $g_name );
                        $td_classes  = 'sfwf-group-' . $group_slug;
                        // Additional classes for status or flags
                        // Special handling for forecast_sales_status: assign cell class and tooltip
                        if ( $key === 'forecast_sales_status' ) {
                            $status = $v ?: 'active';
                            $label  = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : esc_html( $status );
                            $tip    = isset( $status_tooltips[ $status ] ) ? $status_tooltips[ $status ] : '';
                            // Determine sort order for statuses so that active ranks highest
                            $status_order = [ 'active' => 0, 'not_moving_t1' => 1, 'not_moving_t2' => 2, 'not_moving_t3' => 3 ];
                            $order_value  = isset( $status_order[ $status ] ) ? $status_order[ $status ] : 99;
                            $cell_class   = 'sfwf-sales-' . sanitize_html_class( $status );
                            if ( $tip ) {
                                $content = '<span title="' . esc_attr( $tip ) . '">' . esc_html( $label ) . '</span>';
                            } else {
                                $content = esc_html( $label );
                            }
                            echo '<td class="' . esc_attr( $td_classes . ' ' . $cell_class ) . '" data-order="' . esc_attr( $order_value ) . '">' . $content . '</td>';
                            continue;
                        }
                        // Render interactive flag checkboxes
                        if ( in_array( $key, [ 'forecast_do_not_reorder', 'forecast_is_must_stock', 'forecast_force_reorder' ], true ) ) {
                            $checked = ( $v === 'yes' ) ? 'checked' : '';
                            echo '<td class="' . esc_attr( $td_classes ) . '"><input type="checkbox" class="sfwf-toggle-flag" data-product-id="' . esc_attr( $product_id ) . '" data-key="' . esc_attr( $key ) . '" ' . $checked . ' /></td>';
                            continue;
                        }
                        // Compute display and sort values based on column type
                        $display_value = '—';
                        $order_value   = '';
                        switch ( $col['type'] ) {
                            case 'currency':
                                if ( $v !== '' && $v !== null ) {
                                    $order_value   = floatval( $v );
                                    $display_value = wc_price( $v );
                                }
                                break;
                            case 'percent':
                                if ( $v !== '' && $v !== null ) {
                                    $order_value   = floatval( $v );
                                    $display_value = round( $v, 1 ) . '%';
                                }
                                break;
                            case 'boolean':
                                $order_value   = ( $v === 'yes' ) ? 1 : 0;
                                $display_value = ( $v === 'yes' ) ? '✔' : '';
                                break;
                            case 'number':
                                if ( is_numeric( $v ) ) {
                                    $order_value   = floatval( $v );
                                    $display_value = round( $v, 2 );
                                }
                                break;
                            case 'date':
                                if ( $v ) {
                                    $timestamp    = strtotime( $v );
                                    if ( $timestamp ) {
                                        $order_value   = $timestamp;
                                        $display_value = date_i18n( 'M j, Y', $timestamp );
                                    }
                                }
                                break;
                            default:
                                if ( $v !== '' && $v !== null ) {
                                    $order_value   = $v;
                                    $display_value = esc_html( $v );
                                }
                                break;
                        }
                        // Build data-order attribute only when an order value is available
                        $data_attr = ( $order_value !== '' || $order_value === 0 ) ? ' data-order="' . esc_attr( $order_value ) . '"' : '';
                        echo '<td class="' . esc_attr( $td_classes ) . '"' . $data_attr . '>' . $display_value . '</td>';
                    endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Load DataTables for enhanced grid features -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.2.4/css/fixedHeader.dataTables.min.css">
<style>
/* Provide a consistent baseline font size */
#sfwf-forecast-table th,
#sfwf-forecast-table td {
    font-size: 14px;
}

/* Prevent header and cell text from wrapping or hyphenating.  Without this,
   long column names (e.g. "Sales/Day (Lifetime)") break onto multiple lines,
   making the grid difficult to read.  Using nowrap ensures each column
   remains on one line and horizontal scrolling handles overflow. */
#sfwf-forecast-table th,
#sfwf-forecast-table td {
    white-space: nowrap;
    word-break: keep-all;
}
/* Colour rows based on stock status; these colours reflect urgency (red) vs availability (green) */
.sfwf-stock-instock td {
    color: #008000; /* green for items in stock */
    font-weight: 500;
}
.sfwf-stock-outofstock td {
    color: #c0392b; /* red for out-of-stock items */
    font-weight: 500;
}
/* Colour cells based on sales status; using orange/red/grey indicates how slow the product is moving */
td.sfwf-sales-active {
    color: #008000;
    background-color: #f6fff6;
}
td.sfwf-sales-not_moving_t1 {
    color: #e67e22;
    background-color: #fff9f0;
}
td.sfwf-sales-not_moving_t2 {
    color: #c0392b;
    background-color: #fff5f5;
}
td.sfwf-sales-not_moving_t3 {
    color: #7f8c8d;
    background-color: #f7f7f7;
}
/* Group backgrounds: assign subtle colours and left borders to visually separate groups */
.sfwf-group-Inventory    { background-color: #fafafa; }
.sfwf-group-Sales        { background-color: #fff7f0; border-left: 2px solid #e0e0e0; }
.sfwf-group-Forecast     { background-color: #f0f7ff; border-left: 2px solid #e0e0e0; }
.sfwf-group-Financial    { background-color: #f9f9ff; border-left: 2px solid #e0e0e0; }
.sfwf-group-Flags        { background-color: #fffaf9; border-left: 2px solid #e0e0e0; }
.sfwf-group-Lifecycle    { background-color: #f5f5f5; border-left: 2px solid #e0e0e0; }
.sfwf-group-aip_forecast_summary,
.sfwf-group-AIP_Summary,
.sfwf-group-AIPSummary   { background-color: #f6f9f6; border-left: 2px solid #e0e0e0; }
/* DataTables fixed header offset when admin bar is present */
.dataTables_wrapper .fixedHeader-floating {
    top: 0;
    z-index: 9999;
}
.admin-bar .dataTables_wrapper .fixedHeader-floating { top: 32px; }
@media screen and (max-width: 782px) {
    .admin-bar .dataTables_wrapper .fixedHeader-floating { top: 46px; }
}

/* Ensure the scroll container grows to fit all columns.  By setting the
   internal table width to max-content, DataTables will not constrain the
   width to 100%, allowing unlimited horizontal scrolling when there are
   many columns.  This rule targets both the scroll head and body tables. */
#sfwf-forecast-table_wrapper .dataTables_scrollHeadInner,
#sfwf-forecast-table_wrapper .dataTables_scrollBody table {
    width: max-content !important;
}
</style>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/fixedheader/3.2.4/js/dataTables.fixedHeader.min.js"></script>
<script>
jQuery(document).ready(function($) {
    // Initialise DataTable with hidden filter columns and scrollX for horizontal scrolling.
    var table = $('#sfwf-forecast-table').DataTable({
        pageLength: 25,
        order: [],
        stateSave: false,
        // Allow DataTables to calculate column widths based on content.  This
        // prevents the table from collapsing columns when horizontal scroll is
        // enabled.  Combined with the width="100%" attribute on the table,
        // autoWidth lets the grid expand beyond the viewport and enables
        // unlimited horizontal scrolling.
        autoWidth: false,
        responsive: false,
        scrollX: true,
        scrollCollapse: false,
        columnDefs: [
            { targets: [4, 5, 6], visible: false, searchable: true }
        ],
        fixedHeader: {
            header: true,
            headerOffset: function() {
                // Use the height of the admin bar when present; fallback to 32px
                var offset = $('#wpadminbar').outerHeight();
                return offset ? offset : 32;
            }
        }
    });
    // Apply FixedHeader plugin for sticky headers (already configured above)

    // Update the displayed total count whenever the table is drawn (initial load, filtering, sorting).
    function updateRowCount() {
        var count = table.rows({ filter: 'applied' }).count();
        $('#sfwf-total-count').text(count);
    }
    updateRowCount();
    table.on('draw', function () {
        updateRowCount();
    });

    // Nonce for flag toggling
    var sfwfFlagNonce = '<?php echo esc_js( wp_create_nonce( 'sfwf_toggle_flag_nonce' ) ); ?>';

    // Toggle flag event handler
    $('#sfwf-forecast-table').on('change', '.sfwf-toggle-flag', function() {
        var checkbox   = $(this);
        var productId  = checkbox.data('product-id');
        var metaKey    = checkbox.data('key');
        var value      = checkbox.is(':checked') ? 'yes' : 'no';
        checkbox.prop('disabled', true);
        $.post( ajaxurl, {
            action:   'sfwf_toggle_flag',
            security: sfwfFlagNonce,
            product_id: productId,
            meta_key: metaKey,
            value: value
        }, function( response ) {
            checkbox.prop('disabled', false);
        } ).fail(function() {
            checkbox.prop('checked', !checkbox.is(':checked'));
            checkbox.prop('disabled', false);
            alert('Failed to update flag.');
        });
    });

    // Column group toggles: show/hide column groups when checkboxes change
    var sfwfGroupColumns = <?php echo wp_json_encode( $groups ); ?>;
    $('.sfwf-group-toggle').on('change', function() {
        var group   = $(this).data('group');
        var columns = sfwfGroupColumns[group] || [];
        var visible = $(this).is(':checked');
        columns.forEach(function(colIndex) {
            table.column(colIndex).visible(visible);
        });
    });

    // Filtering by stock, category, brand using hidden columns
    $('#sfwf-filter-stock').on('change', function() {
        var val = $(this).val();
        table.column(4).search(val).draw();
    });
    $('#sfwf-filter-category').on('change', function() {
        var val = $(this).val();
        table.column(5).search(val).draw();
    });
    $('#sfwf-filter-brand').on('change', function() {
        var val = $(this).val();
        table.column(6).search(val).draw();
    });

    // Custom filter for days since last sold
    var sfwfLastSoldThreshold = null;
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if ( sfwfLastSoldThreshold === null ) {
            return true;
        }
        var row = table.row(dataIndex).node();
        var days = parseInt( $(row).data('last-sold-days'), 10 );
        if ( isNaN(days) ) {
            return false;
        }
        return days > sfwfLastSoldThreshold;
    });
    $('#sfwf-filter-last-sold-days').on('change', function() {
        var val = $(this).val();
        if ( val === '' ) {
            sfwfLastSoldThreshold = null;
        } else {
            var num = parseInt( val, 10 );
            sfwfLastSoldThreshold = isNaN(num) ? null : num;
        }
        table.draw();
    });
});
</script>
