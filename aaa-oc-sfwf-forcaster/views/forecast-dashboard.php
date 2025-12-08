<?php
// View file for the forecast grid. It renders a DataTable of reorder‑enabled products
// and adds controls for scheduling the forecast and toggling column groups.
if ( ! defined( 'ABSPATH' ) ) exit;

require_once SFWF_ROOT . 'helpers/forecast-column-definitions.php';

$columns = SFWF_Column_Definitions::get_columns();

// Prepare human-readable labels and descriptions for forecast sales statuses. These are
// used to provide more context in the table and tooltips. Thresholds are loaded
// from settings so the descriptions stay accurate.
$status_labels = [
    'active'         => __( 'Active', 'aaa-wf-sfwf' ),
    'not_moving_t1'  => __( 'Not Moving Tier 1', 'aaa-wf-sfwf' ),
    'not_moving_t2'  => __( 'Not Moving Tier 2', 'aaa-wf-sfwf' ),
    'not_moving_t3'  => __( 'Not Moving Tier 3', 'aaa-wf-sfwf' ),
];
$status_tooltips = [];
if ( class_exists( 'WF_SFWF_Settings' ) ) {
    $tier1  = intval( WF_SFWF_Settings::get( 'not_moving_t1_days', 14 ) );
    $tier2  = intval( WF_SFWF_Settings::get( 'not_moving_t2_days', 30 ) );
    $tier3  = intval( WF_SFWF_Settings::get( 'not_moving_t3_after_best_sold_by', 15 ) );
    $status_tooltips = [
        'active'        => __( 'Product is selling within expected time frame.', 'aaa-wf-sfwf' ),
        'not_moving_t1' => sprintf( __( 'No sale for %d days', 'aaa-wf-sfwf' ), $tier1 ),
        'not_moving_t2' => sprintf( __( 'No sale for %d days', 'aaa-wf-sfwf' ), $tier2 ),
        'not_moving_t3' => sprintf( __( 'Expired best-sold-by window plus %d days buffer', 'aaa-wf-sfwf' ), $tier3 ),
    ];
}

// Load lists of categories and brands for filtering.
// Categories use the standard product_cat taxonomy.
$category_terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );

// Brands: the taxonomy key can be configured in settings. If not set or no terms found,
// fall back to common slugs (pwb-brand, product_brand).
$brand_terms = [];
$brand_slug  = null;
if ( class_exists( 'WF_SFWF_Settings' ) ) {
    $brand_slug = WF_SFWF_Settings::get( 'brand_taxonomy_slug', '' );
}
if ( ! empty( $brand_slug ) ) {
    $brand_terms = get_terms( [ 'taxonomy' => $brand_slug, 'hide_empty' => false ] );
}
if ( empty( $brand_terms ) ) {
    // try default known slugs
    $brand_terms = get_terms( [ 'taxonomy' => 'pwb-brand', 'hide_empty' => false ] );
    if ( empty( $brand_terms ) ) {
        $brand_terms = get_terms( [ 'taxonomy' => 'product_brand', 'hide_empty' => false ] );
        if ( empty( $brand_terms ) ) {
            $brand_terms = [];
        }
    }
}

// Query products with 'enable_reorder' only
$args = [
    // Only published products are displayed. Private or other statuses are excluded.
    'status' => ['publish'],
    // Only include simple products in the forecast grid. Variations are excluded.
    'type'   => ['simple'],
    'limit'  => -1,
    'return' => 'ids',
    // Include products where reordering is enabled OR override flags (must stock or force reorder) are set.
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

// Settings for thresholds to exclude products from grid
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
    // Count total reorder-enabled products for quick reference.
    $total_products = count( $product_ids );
    ?>
    <p><strong><?php _e( 'Total Products:', 'aaa-wf-sfwf' ); ?> <span id="sfwf-total-count"><?php echo intval( $total_products ); ?></span></strong></p>

    <?php
    // Build a map of groups to column indexes. The first seven columns (#, ID, Product, Last Processed,
    // Stock, Category, Brand) are not part of any group. Start indexing at 7 so that the defined
    // columns map correctly. Hidden filter columns occupy positions 4–6 (zero‑based).
    $groups = [];
    $i      = 7;
    foreach ( $columns as $def ) {
        $g = isset( $def['group'] ) ? $def['group'] : 'Other';
        $groups[ $g ][] = $i++;
    }
    ?>

    <!-- Forecast action controls -->
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
        <?php if ( isset( $_GET['forecast_scheduled'] ) && $_GET['forecast_scheduled'] === '1' ) {
            echo '<span class="sfwf-scheduled-notice" style="margin-left:10px; color:#007cba;">Forecast scheduled! It will run in the background shortly.</span>';
        } ?>
    </div>

    <!-- Column group toggles -->
    <!-- Filter dropdowns -->
    <div class="sfwf-filter-bar" style="margin-bottom: 0.5em;">
        <label>
            <?php esc_html_e( 'Stock Status:', 'aaa-wf-sfwf' ); ?>
            <select id="sfwf-filter-stock" style="min-width:120px;">
                <option value="">All</option>
                <option value="instock">In Stock</option>
                <option value="outofstock">Out of Stock</option>
            </select>
        </label>
        <label style="margin-left:10px;">
            <?php esc_html_e( 'Category:', 'aaa-wf-sfwf' ); ?>
            <select id="sfwf-filter-category" style="min-width:160px;">
                <option value="">All</option>
                <?php foreach ( $category_terms as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->name ); ?>"><?php echo esc_html( $term->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left:10px;">
            <?php esc_html_e( 'Brand:', 'aaa-wf-sfwf' ); ?>
            <select id="sfwf-filter-brand" style="min-width:160px;">
                <option value="">All</option>
                <?php foreach ( $brand_terms as $term ) : ?>
                    <option value="<?php echo esc_attr( $term->name ); ?>"><?php echo esc_html( $term->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label style="margin-left:10px;">
            <?php esc_html_e( 'Last Sold Older Than:', 'aaa-wf-sfwf' ); ?>
            <select id="sfwf-filter-last-sold-days" style="min-width:120px;">
                <option value="">All</option>
                <option value="7">7 days</option>
                <option value="14">14 days</option>
                <option value="30">30 days</option>
                <option value="45">45 days</option>
                <option value="60">60 days</option>
                <option value="90">90 days</option>
                <option value="120">120 days</option>
                <option value="180">180 days</option>
            </select>
        </label>
    </div>
    <div class="sfwf-group-toggle-bar" style="margin-bottom: 0.5em;">
        <?php foreach ( $groups as $group_name => $indices ) : ?>
            <label style="margin-right: 10px;">
                <input type="checkbox" class="sfwf-group-toggle" data-group="<?php echo esc_attr( $group_name ); ?>" checked />
                <?php echo esc_html( $group_name ); ?>
            </label>
        <?php endforeach; ?>
    </div>

    <table id="sfwf-forecast-table" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th><?php esc_html_e( 'ID', 'aaa-wf-sfwf' ); ?></th>
                <th><?php esc_html_e( 'Product', 'aaa-wf-sfwf' ); ?></th>
                <th><?php esc_html_e( 'Last Processed', 'aaa-wf-sfwf' ); ?></th>
                <!-- Hidden filter columns: Stock, Category, Brand -->
                <th style="display:none;">Stock</th>
                <th style="display:none;">Category</th>
                <th style="display:none;">Brand</th>
                <?php foreach ( $columns as $key => $col ) : ?>
                    <th><?php echo esc_html( $col['label'] ); ?></th>
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
                // Determine control flags for this product
                $enable_reorder = get_post_meta( $product_id, 'forecast_enable_reorder', true );
                $do_not_reorder = get_post_meta( $product_id, 'forecast_do_not_reorder', true );
                $must_stock     = get_post_meta( $product_id, 'forecast_is_must_stock', true );
                $force_reorder  = get_post_meta( $product_id, 'forecast_force_reorder', true );

                // Apply gating logic:
                // - If product is marked as Do Not Reorder, exclude it unless Must Stock or Force Reorder is set.
                // - If Enable Reorder is not set, exclude it unless an override (Must Stock or Force Reorder) is present.
                $override = ( $must_stock === 'yes' || $force_reorder === 'yes' );
                if ( $do_not_reorder === 'yes' && ! $override ) {
                    continue;
                }
                if ( $enable_reorder !== 'yes' && ! $override ) {
                    continue;
                }

                // Exclude products by stock threshold
                if ( $enable_stock_threshold && $stock_threshold_qty > 0 ) {
                    $qty = $product->get_stock_quantity();
                    if ( ! is_null( $qty ) && $qty >= $stock_threshold_qty ) {
                        continue;
                    }
                }

                // Exclude new products by threshold
                if ( $enable_new_threshold && $new_product_days > 0 ) {
                    // Determine first sold date from meta or compute timeline
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

                ?>
                <?php
                // Compute days since last sold for filtering. Use forecast_last_sold_date meta.
                $last_sold_date_meta = get_post_meta( $product_id, 'forecast_last_sold_date', true );
                $days_since_last_sold = '';
                if ( ! empty( $last_sold_date_meta ) ) {
                    $timestamp = strtotime( $last_sold_date_meta );
                    if ( $timestamp ) {
                        $days_since_last_sold = floor( ( current_time( 'timestamp' ) - $timestamp ) / DAY_IN_SECONDS );
                    }
                }
                ?>
                <?php
                // Determine stock status for styling
                $product_obj  = $product;
                $stock_status = $product_obj ? $product_obj->get_stock_status() : '';
                $row_class    = '';
                if ( $stock_status === 'outofstock' ) {
                    $row_class = 'sfwf-stock-outofstock';
                } elseif ( $stock_status === 'instock' ) {
                    $row_class = 'sfwf-stock-instock';
                }
                ?>
                <tr data-last-sold-days="<?php echo esc_attr( $days_since_last_sold ); ?>" class="<?php echo esc_attr( $row_class ); ?>">
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
                        <?php
                        // Compute stock status: instock or outofstock
                        $stock_status = $product->get_stock_status();
                        echo esc_html( $stock_status );
                        ?>
                    </td>
                    <td style="display:none;">
                        <?php
                        // List categories names joined by comma
                        $cats = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'names' ] );
                        echo esc_html( implode( ', ', (array) $cats ) );
                        ?>
                    </td>
                    <td style="display:none;">
                        <?php
                        // Determine brand terms using configured brand taxonomy slug first.
                        $brand_terms_list = [];
                        $configured_slug  = null;
                        if ( class_exists( 'WF_SFWF_Settings' ) ) {
                            $configured_slug = WF_SFWF_Settings::get( 'brand_taxonomy_slug', '' );
                        }
                        if ( ! empty( $configured_slug ) ) {
                            $brand_terms_list = get_the_terms( $product_id, $configured_slug );
                        }
                        if ( empty( $brand_terms_list ) || is_wp_error( $brand_terms_list ) ) {
                            // fallback to default known slugs
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
                        // Special formatting for forecast_sales_status: map to human label & tooltip
                        if ( $key === 'forecast_sales_status' ) {
                            $status = $v ?: 'active';
                            $label  = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : esc_html( $status );
                            $tip    = isset( $status_tooltips[ $status ] ) ? $status_tooltips[ $status ] : '';
                            if ( $tip ) {
                                $content = '<span title="' . esc_attr( $tip ) . '">' . esc_html( $label ) . '</span>';
                            } else {
                                $content = esc_html( $label );
                            }
                            echo '<td>' . $content . '</td>';
                            continue;
                        }
                        // Render interactive checkboxes for manual flags (do not reorder, must stock, force reorder)
                        if ( in_array( $key, [ 'forecast_do_not_reorder', 'forecast_is_must_stock', 'forecast_force_reorder' ], true ) ) {
                            $checked = ( $v === 'yes' ) ? 'checked' : '';
                            echo '<td><input type="checkbox" class="sfwf-toggle-flag" data-product-id="' . esc_attr( $product_id ) . '" data-key="' . esc_attr( $key ) . '" ' . $checked . ' /></td>';
                            continue;
                        }
                        switch ( $col['type'] ) {
                            case 'currency':
                                $d = ( $v !== '' ) ? wc_price( $v ) : '—';
                                break;
                            case 'percent':
                                $d = ( $v !== '' ) ? round( $v, 1 ) . '%' : '—';
                                break;
                            case 'boolean':
                                $d = ( $v === 'yes' ) ? '✔' : '';
                                break;
                            case 'number':
                                $d = is_numeric( $v ) ? round( $v, 2 ) : '—';
                                break;
                            case 'date':
                                $d = $v ? date_i18n( 'M j, Y', strtotime( $v ) ) : '—';
                                break;
                            default:
                                $d = ( $v !== '' ) ? esc_html( $v ) : '—';
                        }
                        echo '<td>' . $d . '</td>';
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
/* Color rows based on stock status to improve readability */
.sfwf-stock-instock td {
    color: #0a0; /* green for in stock */
    font-size: 16px;
    font-weight: 500;
}
.sfwf-stock-outofstock td {
    color: #c00; /* red for out of stock */
}
</style>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/fixedheader/3.2.4/js/dataTables.fixedHeader.min.js"></script>
<script>
jQuery(document).ready(function($) {
    // Initialise DataTable with hidden filter columns. Disable stateSave so that
    // visibility and search settings do not persist between page loads. This
    // prevents columns marked as hidden from inadvertently showing on reload.
    var table = $('#sfwf-forecast-table').DataTable({
        pageLength: 25,
        order: [],
        stateSave: false,
        autoWidth: false,
        responsive: true,
        columnDefs: [
            { targets: [4, 5, 6], visible: false, searchable: true }
        ]
    });

    // Apply sticky header using DataTables FixedHeader extension
    if ( typeof $.fn.dataTable.FixedHeader !== 'undefined' ) {
        new $.fn.dataTable.FixedHeader( table );
    }

    // Update the displayed total count whenever the table is drawn (initial load, filtering, sorting).
    function updateRowCount() {
        var count = table.rows({ filter: 'applied' }).count();
        $('#sfwf-total-count').text(count);
    }
    // Initial count
    updateRowCount();
    // Update on each draw event
    table.on('draw', function () {
        updateRowCount();
    });

    // =================== Inline flag toggling ===================
    // Nonce for security: generated server-side and injected into the page. We place
    // this variable outside of event handlers so it is accessible globally.
    var sfwfFlagNonce = '<?php echo esc_js( wp_create_nonce( 'sfwf_toggle_flag_nonce' ) ); ?>';

    // Delegate change event to dynamically created checkboxes for better performance.
    $('#sfwf-forecast-table').on('change', '.sfwf-toggle-flag', function() {
        var checkbox   = $(this);
        var productId  = checkbox.data('product-id');
        var metaKey    = checkbox.data('key');
        var value      = checkbox.is(':checked') ? 'yes' : 'no';
        // Disable the checkbox temporarily to prevent rapid clicks
        checkbox.prop('disabled', true);
        $.post( ajaxurl, {
            action:   'sfwf_toggle_flag',
            security: sfwfFlagNonce,
            product_id: productId,
            meta_key: metaKey,
            value: value
        }, function( response ) {
            // Re-enable the checkbox after response
            checkbox.prop('disabled', false);
            // Optionally handle errors or success
        } ).fail(function() {
            // In case of failure, revert the checkbox state
            checkbox.prop('checked', !checkbox.is(':checked')); 
            checkbox.prop('disabled', false);
            alert('Failed to update flag.');
        });
    });

    // Mapping of group names to column indexes. The first four columns (#, ID, Product, Last Processed)
    // are not part of groups, so group columns start at index 4.
    var sfwfGroupColumns = <?php echo wp_json_encode( $groups ); ?>;

    // Toggle column visibility when checkboxes change
    $('.sfwf-group-toggle').on('change', function() {
        var group   = $(this).data('group');
        var columns = sfwfGroupColumns[group] || [];
        var visible = $(this).is(':checked');
        columns.forEach(function(colIndex) {
            // DataTables uses zero‑based indexes; the mapping already accounts for this (product column is 0)
            table.column(colIndex).visible(visible);
        });
    });

    // Filter handlers for stock, category and brand
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

    // Custom filter for last sold days
    var sfwfLastSoldThreshold = null;
    // Register a custom filtering function with DataTables. If the threshold is not set, all rows pass.
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if ( sfwfLastSoldThreshold === null ) {
            return true;
        }
        // Access row node to read last-sold-days attribute
        var row = table.row(dataIndex).node();
        var days = parseInt( $(row).data('last-sold-days'), 10 );
        if ( isNaN(days) ) {
            // If no data, exclude when threshold is set
            return false;
        }
        return days > sfwfLastSoldThreshold;
    });
    // Listen for changes on the Last Sold Days select filter
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
