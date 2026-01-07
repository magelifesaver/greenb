<?php
/**
 * Version: 1.4.0 (2026-01-06)
 *
 * Filepath: views/forecast-dashboard.php
 * ---------------------------------------------------------------------------
 * Renders the Stock Forecast Report grid from the custom forecast index table.
 *
 * This version reads all forecast metrics from the dedicated table created by
 * WF_SFWF_Forecast_Index_Table rather than pulling hundreds of meta keys for
 * each product.  It preserves the existing UI features: row selection,
 * filtering by stock/category/brand, column grouping, flag toggling, and
 * integration with DataTables.  Performance is dramatically improved
 * because data is typed and queried in one call.  The summary meta field
 * remains in post_meta for AI use but is not used here.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SFWF_ROOT . 'helpers/forecast-column-definitions.php';

// Retrieve column definitions.  Each definition includes label, type and group.
$columns = SFWF_Column_Definitions::get_columns();

// Preload human-readable labels and tooltips for sales status.
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

// Load lists of categories and brands for filter dropdowns.
$category_terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );

// Determine brand taxonomy slug from settings or fallbacks.
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

// Pull gating thresholds from settings for stock and new product filtering.
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

// Fetch all forecast rows from the custom table.
$rows = [];
if ( class_exists( 'WF_SFWF_Forecast_Index' ) ) {
    $rows = WF_SFWF_Forecast_Index::get_all_rows();
}

// Prepare group indices.  Column 0 is checkbox, 1 row num, 2 product ID, 3 title, 4 last processed, 5-7 hidden filters.
$groups = [];
// Groups start at index 8 because there are 8 fixed columns before dynamic ones.
$group_index = 8;
foreach ( $columns as $def ) {
    $g_name = isset( $def['group'] ) ? $def['group'] : 'Other';
    $groups[ $g_name ][] = $group_index++;
}

?>
<div class="wrap">
    <h1><?php esc_html_e( 'Stock Forecast Report', 'aaa-wf-sfwf' ); ?></h1>
    <?php
    // Show total count (will update via JS after filtering).
    $total_products = count( $rows );
    ?>
    <p><strong><?php esc_html_e( 'Total Products:', 'aaa-wf-sfwf' ); ?> <span id="sfwf-total-count"><?php echo intval( $total_products ); ?></span></strong></p>
    <?php
    // Show notices for update selected and add to PO actions.
    if ( isset( $_GET['sfwf_run_selected_done'] ) ) {
        $count = intval( $_GET['sfwf_run_selected_done'] );
        if ( $count > 0 ) {
            $msg = sprintf( _n( '%d product forecast updated.', '%d products forecast updated.', $count, 'aaa-wf-sfwf' ), $count );
            echo '<div class="notice notice-success is-dismissible" style="margin-top:10px;"><p>' . esc_html( $msg ) . '</p></div>';
        }
    }
    if ( isset( $_GET['sfwf_po_added'] ) ) {
        $count = intval( $_GET['sfwf_po_added'] );
        if ( $count > 0 ) {
            $msg = sprintf( _n( '%d product added to a purchase order.', '%d products added to a purchase order.', $count, 'aaa-wf-sfwf' ), $count );
            echo '<div class="notice notice-success is-dismissible" style="margin-top:10px;"><p>' . esc_html( $msg ) . '</p></div>';
        }
    }
    ?>

    <!-- Action buttons: rebuild all, update flagged, update selected, add to PO -->
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
        <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="sfwf-run-selected-form" style="display:inline-block; margin-left:10px;">
            <?php wp_nonce_field( 'sfwf_run_selected', 'sfwf_run_selected_nonce' ); ?>
            <input type="hidden" name="action" value="sfwf_run_selected" />
            <input type="hidden" name="product_ids" id="sfwf-selected-ids" value="" />
            <?php submit_button( __( 'Update Selected', 'aaa-wf-sfwf' ), 'secondary', 'submit', false ); ?>
        </form>
        <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="sfwf-add-to-po-form" style="display:inline-block; margin-left:10px;">
            <?php wp_nonce_field( 'sfwf_add_to_po', 'sfwf_add_to_po_nonce' ); ?>
            <input type="hidden" name="action" value="sfwf_add_to_po" />
            <input type="hidden" name="product_ids" id="sfwf-po-ids" value="" />
            <?php submit_button( __( 'Add to PO', 'aaa-wf-sfwf' ), 'secondary', 'submit', false ); ?>
        </form>
        <?php if ( isset( $_GET['forecast_scheduled'] ) && $_GET['forecast_scheduled'] === '1' ) : ?>
            <span class="sfwf-scheduled-notice" style="margin-left:10px; color:#007cba;">
                <?php esc_html_e( 'Forecast scheduled! It will run in the background shortly.', 'aaa-wf-sfwf' ); ?>
            </span>
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
            <select id="sfwf-filter-category" style="min-width:120px;">
                <option value=""><?php esc_html_e( 'All', 'aaa-wf-sfwf' ); ?></option>
                <?php foreach ( $category_terms as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->name ); ?>">
                        <?php echo esc_html( $term->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left:10px;">
            <?php esc_html_e( 'Brand:', 'aaa-wf-sfwf' ); ?>
            <select id="sfwf-filter-brand" style="min-width:120px;">
                <option value=""><?php esc_html_e( 'All', 'aaa-wf-sfwf' ); ?></option>
                <?php foreach ( $brand_terms as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->name ); ?>">
                        <?php echo esc_html( $term->name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <!-- Grid markup -->
    <div class="sfwf-table-container">
        <table id="sfwf-forecast-table" class="display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th><input type="checkbox" id="sfwf-select-all" /></th>
                    <th>#</th>
                    <th><?php esc_html_e( 'ID', 'aaa-wf-sfwf' ); ?></th>
                    <th><?php esc_html_e( 'Product', 'aaa-wf-sfwf' ); ?></th>
                    <th><?php esc_html_e( 'Last Processed', 'aaa-wf-sfwf' ); ?></th>
                    <!-- Hidden filter columns -->
                    <th style="display:none;"><?php esc_html_e( 'Stock Status', 'aaa-wf-sfwf' ); ?></th>
                    <th style="display:none;"><?php esc_html_e( 'Category', 'aaa-wf-sfwf' ); ?></th>
                    <th style="display:none;"><?php esc_html_e( 'Brand', 'aaa-wf-sfwf' ); ?></th>
                    <?php foreach ( $columns as $key => $col ) :
                        $group_slug = sanitize_html_class( $col['group'] ?? 'Other' );
                        ?>
                        <th class="sfwf-group-<?php echo esc_attr( $group_slug ); ?>">
                            <?php echo esc_html( $col['label'] ); ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $row_num = 1;
                foreach ( $rows as $row ) :
                    $product_id = intval( $row['product_id'] );
                    // Fetch the product for stock status and gating checks.
                    $product = wc_get_product( $product_id );
                    if ( ! $product ) {
                        continue;
                    }
                    // Determine overrides
                    $enable_reorder = (bool) $row['forecast_enable_reorder'];
                    $do_not_reorder = (bool) $row['forecast_do_not_reorder'];
                    $must_stock     = (bool) $row['forecast_is_must_stock'];
                    $force_reorder  = (bool) $row['forecast_force_reorder'];
                    $override       = ( $must_stock || $force_reorder );
                    // Gating logic: skip rows that should not display
                    if ( $do_not_reorder && ! $override ) {
                        continue;
                    }
                    if ( ! $enable_reorder && ! $override ) {
                        continue;
                    }
                    // Stock threshold
                    if ( $enable_stock_threshold && $stock_threshold_qty > 0 ) {
                        $qty = $row['forecast_stock_qty'];
                        if ( ! is_null( $qty ) && $qty >= $stock_threshold_qty ) {
                            continue;
                        }
                    }
                    // New product threshold using first sold date
                    if ( $enable_new_threshold && $new_product_days > 0 ) {
                        $first_sold_date = $row['forecast_first_sold_date'];
                        if ( $first_sold_date ) {
                            $days_since = floor( ( current_time( 'timestamp' ) - strtotime( $first_sold_date ) ) / DAY_IN_SECONDS );
                            if ( $days_since <= $new_product_days ) {
                                continue;
                            }
                        }
                    }
                    // Compute days since last sold for custom filter
                    $days_since_last_sold = '';
                    if ( ! empty( $row['forecast_last_sold_date'] ) ) {
                        $timestamp = strtotime( $row['forecast_last_sold_date'] );
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
                    // Determine sales status
                    $sales_status = $row['forecast_sales_status'] ?: 'active';
                    $row_class .= ' sfwf-sales-' . sanitize_html_class( $sales_status );
                    ?>
                    <tr data-last-sold-days="<?php echo esc_attr( $days_since_last_sold ); ?>" class="<?php echo esc_attr( trim( $row_class ) ); ?>">
                        <td><input type="checkbox" class="sfwf-select-row" value="<?php echo esc_attr( $product_id ); ?>" /></td>
                        <td><?php echo intval( $row_num++ ); ?></td>
                        <td><?php echo esc_html( $product_id ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>">
                                <?php echo esc_html( $row['product_title'] ); ?>
                            </a>
                        </td>
                        <td>
                            <?php
                            $lp = $row['updated_at'];
                            echo $lp ? esc_html( date_i18n( 'M j, Y H:i', strtotime( $lp ) ) ) : '—';
                            ?>
                        </td>
                        <!-- Hidden cells for filter: stock, category, brand -->
                        <td style="display:none;">
                            <?php echo esc_html( $stock_status ); ?>
                        </td>
                        <td style="display:none;">
                            <?php echo esc_html( $row['product_category'] ); ?>
                        </td>
                        <td style="display:none;">
                            <?php echo esc_html( $row['product_brand'] ); ?>
                        </td>
                        <?php foreach ( $columns as $key => $col ) :
                            $val        = $row[ $key ] ?? '';
                            $g_name     = $col['group'] ?? 'Other';
                            $group_slug = sanitize_html_class( $g_name );
                            $td_classes = 'sfwf-group-' . $group_slug;
                            // Special handling for sales status cell
                            if ( $key === 'forecast_sales_status' ) {
                                $status    = $val ?: 'active';
                                $label     = $status_labels[ $status ] ?? esc_html( $status );
                                $tip       = $status_tooltips[ $status ] ?? '';
                                $order_map = [ 'active' => 0, 'not_moving_t1' => 1, 'not_moving_t2' => 2, 'not_moving_t3' => 3 ];
                                $order_val = isset( $order_map[ $status ] ) ? $order_map[ $status ] : 99;
                                $cell_cls  = 'sfwf-sales-' . sanitize_html_class( $status );
                                $content   = $tip ? '<span title="' . esc_attr( $tip ) . '">' . esc_html( $label ) . '</span>' : esc_html( $label );
                                echo '<td class="' . esc_attr( $td_classes . ' ' . $cell_cls ) . '" data-order="' . esc_attr( $order_val ) . '">' . $content . '</td>';
                                continue;
                            }
                            // Render interactive flag checkboxes
                            if ( in_array( $key, [ 'forecast_do_not_reorder', 'forecast_is_must_stock', 'forecast_force_reorder' ], true ) ) {
                                $checked = ( (bool) $val ) ? 'checked' : '';
                                echo '<td class="' . esc_attr( $td_classes ) . '"><input type="checkbox" class="sfwf-toggle-flag" data-product-id="' . esc_attr( $product_id ) . '" data-key="' . esc_attr( $key ) . '" ' . $checked . ' /></td>';
                                continue;
                            }
                            // Determine display and order values based on type
                            $display_value = '—';
                            $order_value   = '';
                            switch ( $col['type'] ) {
                                case 'currency':
                                    if ( $val !== '' && $val !== null ) {
                                        $order_value   = floatval( $val );
                                        $display_value = wc_price( $val );
                                    }
                                    break;
                                case 'percent':
                                    if ( $val !== '' && $val !== null ) {
                                        $order_value   = floatval( $val );
                                        $display_value = round( $val, 1 ) . '%';
                                    }
                                    break;
                                case 'boolean':
                                    $order_value   = ( (bool) $val ) ? 1 : 0;
                                    $display_value = ( (bool) $val ) ? '✔' : '';
                                    break;
                                case 'number':
                                    if ( is_numeric( $val ) ) {
                                        $order_value   = floatval( $val );
                                        $display_value = round( $val, 2 );
                                    }
                                    break;
                                case 'date':
                                    if ( $val ) {
                                        $timestamp = strtotime( $val );
                                        if ( $timestamp ) {
                                            $order_value   = $timestamp;
                                            $display_value = date_i18n( 'M j, Y', $timestamp );
                                        }
                                    }
                                    break;
                                default:
                                    if ( $val !== '' && $val !== null ) {
                                        // If value contains a number at the beginning (e.g. "0.21 (85 Days)") extract it for ordering
                                        if ( preg_match( '/-?\d+(?:\.\d+)?/', (string) $val, $m ) ) {
                                            $order_value = floatval( $m[0] );
                                        } else {
                                            $order_value = $val;
                                        }
                                        $display_value = esc_html( $val );
                                    }
                                    break;
                            }
                            $data_attr = ( $order_value !== '' || $order_value === 0 ) ? ' data-order="' . esc_attr( $order_value ) . '"' : '';
                            echo '<td class="' . esc_attr( $td_classes ) . '"' . $data_attr . '>' . $display_value . '</td>';
                        endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Styles for grid presentation -->
<style>
/* Baseline font size */
#sfwf-forecast-table th,
#sfwf-forecast-table td {
    font-size: 14px;
}
/* Prevent wrapping */
#sfwf-forecast-table th,
#sfwf-forecast-table td {
    white-space: nowrap;
    word-break: keep-all;
}
/* Row colouring based on stock status */
.sfwf-stock-instock td { color: #008000; font-weight: 500; }
.sfwf-stock-outofstock td { color: #c0392b; font-weight: 500; }
/* Cell colouring based on sales status */
td.sfwf-sales-active { color: #008000; background-color: #f6fff6; }
td.sfwf-sales-not_moving_t1 { color: #e67e22; background-color: #fff9f0; }
td.sfwf-sales-not_moving_t2 { color: #c0392b; background-color: #fff5f5; }
td.sfwf-sales-not_moving_t3 { color: #7f8c8d; background-color: #f7f7f7; }
/* Group backgrounds */
.sfwf-group-Inventory    { background-color: #fafafa; }
.sfwf-group-Sales        { background-color: #fff7f0; border-left: 2px solid #e0e0e0; }
.sfwf-group-Forecast     { background-color: #f0f7ff; border-left: 2px solid #e0e0e0; }
.sfwf-group-Financial    { background-color: #f9f9ff; border-left: 2px solid #e0e0e0; }
.sfwf-group-Flags        { background-color: #fffaf9; border-left: 2px solid #e0e0e0; }
.sfwf-group-Lifecycle    { background-color: #f5f5f5; border-left: 2px solid #e0e0e0; }
.sfwf-group-aip_forecast_summary,
.sfwf-group-AIP_Summary,
.sfwf-group-AIPSummary   { background-color: #f6f9f6; border-left: 2px solid #e0e0e0; }
/* DataTables fixed header offsets */
.dataTables_wrapper .fixedHeader-floating { top: 0; z-index: 9999; }
.admin-bar .dataTables_wrapper .fixedHeader-floating { top: 32px; }
@media screen and (max-width: 782px) {
    .admin-bar .dataTables_wrapper .fixedHeader-floating { top: 46px; }
}
/* Ensure scroll container widths */
#sfwf-forecast-table_wrapper .dataTables_scrollHeadInner,
#sfwf-forecast-table_wrapper .dataTables_scrollBody table {
    min-width: 100% !important;
}
</style>

<!-- DataTables initialisation and custom behaviours -->
<script>
jQuery(document).ready(function($) {
    var table = $('#sfwf-forecast-table').DataTable({
        pageLength: 25,
        order: [],
        stateSave: false,
        autoWidth: true,
        responsive: false,
        scrollX: true,
        scrollCollapse: false,
        columnDefs: [
            { targets: [5, 6, 7], visible: false, searchable: true }
        ],
        fixedHeader: {
            header: true,
            headerOffset: function() {
                var offset = $('#wpadminbar').outerHeight();
                return offset ? offset : 32;
            }
        }
    });
    // Adjust columns after initial draw
    table.columns.adjust().draw(false);
    // Update count after filtering
    function updateRowCount() {
        var count = table.rows({ filter: 'applied' }).count();
        $('#sfwf-total-count').text(count);
    }
    updateRowCount();
    table.on('draw', function() {
        updateRowCount();
    });
    // Select all
    $('#sfwf-select-all').on('change', function() {
        var checked = $(this).is(':checked');
        $('.sfwf-select-row').prop('checked', checked);
    });
    // Collect selected IDs on form submit
    $('#sfwf-run-selected-form').on('submit', function() {
        var ids = [];
        $('.sfwf-select-row:checked').each(function() {
            ids.push($(this).val());
        });
        $('#sfwf-selected-ids').val(ids.join(','));
        return true;
    });
    $('#sfwf-add-to-po-form').on('submit', function() {
        var ids = [];
        $('.sfwf-select-row:checked').each(function() {
            ids.push($(this).val());
        });
        $('#sfwf-po-ids').val(ids.join(','));
        return true;
    });
    // Filtering by stock status
    $('#sfwf-filter-stock').on('change', function() {
        var val = $(this).val();
        table.column(5).search(val).draw();
    });
    $('#sfwf-filter-category').on('change', function() {
        var val = $(this).val();
        table.column(6).search(val).draw();
    });
    $('#sfwf-filter-brand').on('change', function() {
        var val = $(this).val();
        table.column(7).search(val).draw();
    });
    // Toggle group visibility
    // Determine group indices from PHP (embedded as JSON)
    var groupMap = <?php echo wp_json_encode( $groups ); ?>;
    $('.sfwf-group-toggle').on('change', function() {
        var group = $(this).data('group');
        var visible = $(this).is(':checked');
        var indices = groupMap[group] || [];
        indices.forEach(function(i) {
            table.column(i).visible(visible);
        });
        table.columns.adjust().draw(false);
        if (table.fixedHeader) { table.fixedHeader.adjust(); }
    });
    // Nonce for flag toggling
    var sfwfFlagNonce = '<?php echo esc_js( wp_create_nonce( 'sfwf_toggle_flag_nonce' ) ); ?>';
    $('#sfwf-forecast-table').on('change', '.sfwf-toggle-flag', function() {
        var checkbox  = $(this);
        var productId = checkbox.data('product-id');
        var metaKey   = checkbox.data('key');
        var value     = checkbox.is(':checked') ? 'yes' : 'no';
        checkbox.prop('disabled', true);
        $.post( ajaxurl, {
            action: 'sfwf_toggle_flag',
            product_id: productId,
            meta_key: metaKey,
            value: value,
            nonce: sfwfFlagNonce
        }, function(res) {
            checkbox.prop('disabled', false);
        });
    });
});
</script>