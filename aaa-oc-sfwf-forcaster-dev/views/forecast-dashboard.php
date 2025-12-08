<?php
// View file for the forecast grid. It renders a DataTable of reorder‑enabled products
// and adds controls for scheduling the forecast and toggling column groups.
if ( ! defined( 'ABSPATH' ) ) exit;

require_once SFWF_ROOT . 'helpers/forecast-column-definitions.php';

$columns = SFWF_Column_Definitions::get_columns();

// Query products with 'enable_reorder' only
$args = [
    'status' => ['publish', 'private'],
    'type'   => ['simple', 'variation'],
    'limit'  => -1,
    'return' => 'ids',
    'meta_query' => [
        [
            'key'     => 'forecast_enable_reorder',
            'value'   => 'yes',
            'compare' => '=',
        ]
    ]
];

$product_ids = wc_get_products( $args );

?>
<div class="wrap">
    <h1>Stock Forecast Report</h1>

    <?php
    // Build a map of groups to column indexes (product column is 0).
    $groups = [];
    $i      = 1;
    foreach ( $columns as $def ) {
        $g = isset( $def['group'] ) ? $def['group'] : 'Other';
        $groups[ $g ][] = $i++;
    }
    ?>

    <!-- Forecast action controls -->
    <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom: 1em;">
        <?php wp_nonce_field( 'sfwf_run_forecast', 'sfwf_run_forecast_nonce' ); ?>
        <input type="hidden" name="action" value="sfwf_run_forecast">
        <?php submit_button( __( 'Schedule Forecast', 'aaa-wf-sfwf' ), 'secondary', 'submit', false ); ?>
        <?php if ( isset( $_GET['forecast_scheduled'] ) && $_GET['forecast_scheduled'] === '1' ) {
            echo '<span class="sfwf-scheduled-notice" style="margin-left:10px; color:#007cba;">Forecast scheduled! It will run in the background shortly.</span>';
        } ?>
    </form>

    <!-- Column group toggles -->
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
                <th><?php esc_html_e( 'Product', 'aaa-wf-sfwf' ); ?></th>
                <?php foreach ( $columns as $key => $col ) : ?>
                    <th><?php echo esc_html( $col['label'] ); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $product_ids as $product_id ) :
                $product  = wc_get_product( $product_id );
                if ( ! $product ) {
                    continue;
                }
                if ( get_post_meta( $product_id, 'forecast_enable_reorder', true ) !== 'yes' ) {
                    continue;
                }
                ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>">
                            <?php echo esc_html( $product->get_name() ); ?>
                        </a>
                    </td>
                    <?php foreach ( $columns as $key => $col ) :
                        $v = get_post_meta( $product_id, $key, true );
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
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
jQuery(document).ready(function($) {
    // Initialise DataTable
    var table = $('#sfwf-forecast-table').DataTable({
        pageLength: 25,
        order: [],
        stateSave: true,
        autoWidth: false,
        responsive: true
    });

    // Mapping of group names to column indexes (1‑based because column 0 is the product name).
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
});
</script>
