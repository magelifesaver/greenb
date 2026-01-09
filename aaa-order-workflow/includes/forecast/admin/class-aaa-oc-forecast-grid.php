<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/class-aaa-oc-forecast-grid.php
 * Purpose: Renders the Forecast Grid admin page with checkboxes, sortable
 *          columns and bulk actions for queueing products for forecasting
 *          or purchase orders.  Uses DataTables for client‑side sorting and
 *          filtering.  Handles form submission via admin_post to enqueue
 *          selected products.
 *
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_Forecast_Grid_Admin {

    /**
     * Register admin hooks.  Adds the menu item, enqueues scripts and
     * registers the bulk action handler.
     */
    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'admin_post_aaa_oc_forecast_bulk_action', [ __CLASS__, 'handle_bulk_action' ] );
    }

    /**
     * Register the Forecast Grid submenu under WooCommerce.
     */
    public static function register_menu(): void {
        add_submenu_page(
            'woocommerce',
            __( 'Forecast Grid', 'aaa-oc-forecast' ),
            __( 'Forecast Grid', 'aaa-oc-forecast' ),
            'manage_woocommerce',
            'aaa-oc-forecast-grid',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Enqueue DataTables assets only on our grid page.  Use versions pinned
     * to avoid conflicts with other plugins.
     *
     * @param string $hook Current admin page hook.
     */
    public static function enqueue_assets( string $hook ): void {
        if ( $hook !== 'woocommerce_page_aaa-oc-forecast-grid' ) {
            return;
        }
        // Ensure dashicons are available for flag icons.
        wp_enqueue_style( 'dashicons' );
        // DataTables core
        wp_enqueue_style( 'aaa-oc-datatables', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', [], '1.13.6' );
        wp_enqueue_script( 'aaa-oc-datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', [ 'jquery' ], '1.13.6', true );
        // FixedHeader extension (optional)
        wp_enqueue_style( 'aaa-oc-dt-fixedheader', 'https://cdn.datatables.net/fixedheader/3.2.4/css/fixedHeader.dataTables.min.css', [ 'aaa-oc-datatables' ], '3.2.4' );
        wp_enqueue_script( 'aaa-oc-dt-fixedheader', 'https://cdn.datatables.net/fixedheader/3.2.4/js/dataTables.fixedHeader.min.js', [ 'aaa-oc-datatables' ], '3.2.4', true );
    }

    /**
     * Render the Forecast Grid page.  Outputs a form containing a table of
     * products and forecast metrics, along with bulk action controls.
     */
    public static function render_page(): void {
        // Verify capability
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'aaa-oc-forecast' ) );
        }
        // Fetch rows and columns
        $rows    = AAA_OC_Forecast_Indexer::get_all_rows();
        $columns = AAA_OC_Forecast_Columns::get_columns();

        // Determine which columns to hide (flags will be shown via icons instead)
        $hidden_columns = [ 'forecast_is_not_moving', 'forecast_is_stale' ];

        // Load custom labels for flags from settings
        $not_moving_label = function_exists( 'aaa_oc_get_option' ) ? aaa_oc_get_option( 'forecast_not_moving_label', 'forecast', 'Not Moving' ) : 'Not Moving';
        $stale_label      = function_exists( 'aaa_oc_get_option' ) ? aaa_oc_get_option( 'forecast_stale_label', 'forecast', 'Stale' ) : 'Stale';
        // Nonce for bulk action
        $nonce = wp_create_nonce( 'aaa_oc_forecast_bulk_action' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Forecast Grid', 'aaa-oc-forecast' ); ?></h1>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="aaa_oc_forecast_bulk_action" />
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
                <div class="tablenav top" style="margin-bottom: 10px;">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action" id="bulk-action-selector-top">
                            <option value="" selected="selected"><?php esc_html_e( 'Bulk actions', 'aaa-oc-forecast' ); ?></option>
                            <option value="queue_forecast"><?php esc_html_e( 'Queue for Forecast', 'aaa-oc-forecast' ); ?></option>
                            <option value="queue_po"><?php esc_html_e( 'Queue for Purchase Order', 'aaa-oc-forecast' ); ?></option>
                        </select>
                        <button type="submit" class="button action"><?php esc_html_e( 'Apply', 'aaa-oc-forecast' ); ?></button>
                    </div>
                </div>
                <table id="aaa-oc-forecast-table" class="display wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th scope="col" class="check-column"><input type="checkbox" id="aaa_oc_forecast_select_all" /></th>
                            <th scope="col">ID</th>
                            <th scope="col">Product</th>
                            <th scope="col">Category</th>
                            <th scope="col">Brand</th>
                            <?php foreach ( $columns as $key => $def ) : ?>
                            <?php
                                // Skip hidden flag columns; they will be represented by icons in the product cell.
                                if ( in_array( $key, $hidden_columns, true ) ) {
                                    continue;
                                }
                            ?>
                            <th scope="col" data-col="<?php echo esc_attr( $key ); ?>">
                                    <?php echo esc_html( $def['label'] ); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $rows as $row ) : ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="product_ids[]" value="<?php echo esc_attr( $row['product_id'] ); ?>" />
                                </th>
                                <td><?php echo esc_html( $row['product_id'] ); ?></td>
                                <td>
                                    <?php echo esc_html( $row['product_title'] ); ?>
                                    <?php
                                    // Append status icons inline. Use dashicons with screen reader text.
                                    if ( ( $row['forecast_is_not_moving'] ?? 0 ) ) {
                                        echo '<span class="dashicons dashicons-clock" style="margin-left:4px;" title="' . esc_attr( $not_moving_label ) . '"></span>';
                                    }
                                    if ( ( $row['forecast_is_stale'] ?? 0 ) ) {
                                        echo '<span class="dashicons dashicons-warning" style="margin-left:4px;color:#dc3232;" title="' . esc_attr( $stale_label ) . '"></span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html( $row['product_category'] ); ?></td>
                                <td><?php echo esc_html( $row['product_brand'] ); ?></td>
                                <?php foreach ( $columns as $key => $def ) : ?>
                                    <?php
                                    // Skip hidden flag columns; icons already shown
                                    if ( in_array( $key, $hidden_columns, true ) ) {
                                        continue;
                                    }
                                    $val = $row[ $key ] ?? null;
                                    // Render booleans as icons
                                    if ( $def['type'] === 'boolean' ) {
                                        echo '<td style="text-align:center;">' . ( $val ? '&#x2714;' : '&#x2014;' ) . '</td>';
                                    } else {
                                        // Format dates and numbers nicely
                                        if ( $def['type'] === 'date' && $val ) {
                                            $display = date_i18n( get_option( 'date_format' ), strtotime( $val ) );
                                        } elseif ( $def['type'] === 'currency' && $val !== null ) {
                                            $display = wc_price( $val );
                                        } elseif ( $def['type'] === 'percent' && $val !== null ) {
                                            $display = round( $val * 100, 2 ) . '%';
                                        } else {
                                            $display = $val;
                                        }
                                        echo '<td>' . esc_html( $display ) . '</td>';
                                    }
                                    ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialise DataTable with fixed header
            var table = $('#aaa-oc-forecast-table').DataTable({
                fixedHeader: true,
                order: [],
                pageLength: 25
            });
            // Select/Deselect all
            $('#aaa_oc_forecast_select_all').on('click', function(){
                var rows = table.rows({ 'search': 'applied' }).nodes();
                $('input[type="checkbox"]', rows).prop('checked', this.checked);
            });
        });
        </script>
        <?php
    }

    /**
     * Handle bulk action submissions.  Verifies the nonce and user capability,
     * then enqueues each selected product for forecasting or purchase order.
     */
    public static function handle_bulk_action(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'aaa-oc-forecast' ) );
        }
        check_admin_referer( 'aaa_oc_forecast_bulk_action' );
        $action      = $_POST['bulk_action'] ?? '';
        $product_ids = isset( $_POST['product_ids'] ) ? array_map( 'intval', (array) $_POST['product_ids'] ) : [];
        if ( empty( $action ) || empty( $product_ids ) ) {
            wp_safe_redirect( wp_get_referer() );
            exit;
        }
        $queued = 0;
        // Call new queue methods for each product. Use backwards compat wrappers.
        foreach ( $product_ids as $pid ) {
            if ( $action === 'queue_forecast' ) {
                AAA_OC_Forecast_Queue::enqueue_product( $pid );
                $queued++;
            } elseif ( $action === 'queue_po' ) {
                AAA_OC_Forecast_Queue::enqueue_po_product( $pid );
                $queued++;
            }
        }
        // Add admin notice of queued items
        add_action( 'admin_notices', function() use ( $queued, $action ) {
            $msg = ( $action === 'queue_po' ) ? __( 'products queued for Purchase Order.', 'aaa-oc-forecast' ) : __( 'products queued for Forecasting.', 'aaa-oc-forecast' );
            printf( '<div class="notice notice-success"><p>%d %s</p></div>', $queued, esc_html( $msg ) );
        } );
        wp_safe_redirect( wp_get_referer() );
        exit;
    }
}