<?php
/**
 * Filepath: sfwf/settings/sfwf-settings-page.php
 * ---------------------------------------------------------------------------
 * Admin settings page for global forecast options.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function sfwf_render_settings_page() {
    // Handle save
    if ( isset($_POST['sfwf_settings_nonce']) && wp_verify_nonce($_POST['sfwf_settings_nonce'], 'sfwf_save_settings') ) {
        WF_SFWF_Settings::set('global_lead_time_days', intval($_POST['global_lead_time_days'] ?? 0));
        WF_SFWF_Settings::set('global_cost_percent', floatval($_POST['global_cost_percent'] ?? 0));
        WF_SFWF_Settings::set('global_sales_window_days', intval($_POST['global_sales_window_days'] ?? 90));
        WF_SFWF_Settings::set('global_minimum_order_qty', intval($_POST['global_minimum_order_qty'] ?? 1));
        WF_SFWF_Settings::set('global_minimum_stock', intval($_POST['global_minimum_stock'] ?? 0));
        WF_SFWF_Settings::set('grid_sales_window_days', intval($_POST['grid_sales_window_days'] ?? 180));
        WF_SFWF_Settings::set('enable_purchase_orders_globally', ($_POST['enable_purchase_orders_globally'] ?? '') === 'yes' ? 'yes' : 'no');

        // Sales status tiers
        WF_SFWF_Settings::set('not_moving_t1_days', intval($_POST['not_moving_t1_days'] ?? 14));
        WF_SFWF_Settings::set('not_moving_t2_days', intval($_POST['not_moving_t2_days'] ?? 30));
        WF_SFWF_Settings::set('not_moving_t3_after_best_sold_by', intval($_POST['not_moving_t3_after_best_sold_by'] ?? 15));

        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    // Load current values
    $lead    = WF_SFWF_Settings::get('global_lead_time_days', 7);
    $cost    = WF_SFWF_Settings::get('global_cost_percent', 50);
    $window  = WF_SFWF_Settings::get('global_sales_window_days', 90);
    $moq     = WF_SFWF_Settings::get('global_minimum_order_qty', 1);
    $minstk  = WF_SFWF_Settings::get('global_minimum_stock', 0);
    $gridwin = WF_SFWF_Settings::get('grid_sales_window_days', 180);
    $enable  = WF_SFWF_Settings::get('enable_purchase_orders_globally', 'yes');

    $tier1   = WF_SFWF_Settings::get('not_moving_t1_days', 14);
    $tier2   = WF_SFWF_Settings::get('not_moving_t2_days', 30);
    $tier3   = WF_SFWF_Settings::get('not_moving_t3_after_best_sold_by', 15);
    ?>

    <div class="wrap">
        <h1>AAA Stock Forecast Workflow - Global Settings</h1>
        <form method="POST">
            <?php wp_nonce_field('sfwf_save_settings', 'sfwf_settings_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th><label for="global_lead_time_days">Default Lead Time (days)</label></th>
                    <td><input name="global_lead_time_days" type="number" value="<?php echo esc_attr($lead); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="global_cost_percent">Fallback Cost %</label></th>
                    <td><input name="global_cost_percent" type="number" step="0.1" value="<?php echo esc_attr($cost); ?>" />%</td>
                </tr>
                <tr>
                    <th><label for="global_sales_window_days">Best Sold By (Shelf Life Days)</label></th>
                    <td><input name="global_sales_window_days" type="number" value="<?php echo esc_attr($window); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="global_minimum_order_qty">Default Minimum Order Qty</label></th>
                    <td><input name="global_minimum_order_qty" type="number" value="<?php echo esc_attr($moq); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="global_minimum_stock">Default Minimum Stock Buffer</label></th>
                    <td><input name="global_minimum_stock" type="number" value="<?php echo esc_attr($minstk); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="grid_sales_window_days">Grid Sales Window (Max Days to Look Back)</label></th>
                    <td><input name="grid_sales_window_days" type="number" value="<?php echo esc_attr($gridwin); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="enable_purchase_orders_globally">Enable PO Generation</label></th>
                    <td>
                        <select name="enable_purchase_orders_globally">
                            <option value="yes" <?php selected($enable, 'yes'); ?>>Yes</option>
                            <option value="no" <?php selected($enable, 'no'); ?>>No</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th colspan="2"><hr><strong>Sales Status Tier Thresholds</strong></th>
                </tr>
                <tr>
                    <th><label for="not_moving_t1_days">Tier 1 - No Sale After (Days)</label></th>
                    <td><input name="not_moving_t1_days" type="number" value="<?php echo esc_attr($tier1); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="not_moving_t2_days">Tier 2 - Delayed After (Days)</label></th>
                    <td><input name="not_moving_t2_days" type="number" value="<?php echo esc_attr($tier2); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="not_moving_t3_after_best_sold_by">Tier 3 - Days Past Best Sold By</label></th>
                    <td><input name="not_moving_t3_after_best_sold_by" type="number" value="<?php echo esc_attr($tier3); ?>" /></td>
                </tr>
    </table>

            <?php submit_button( 'Save Settings' ); ?>
        </form>

        <!-- Buttons to trigger forecast rebuilds -->
        <div style="margin-top: 1em;">
            <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block; margin-right:10px;">
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
            <?php
            // Show a notice if the forecast has just been scheduled
            if ( isset( $_GET['forecast_scheduled'] ) && $_GET['forecast_scheduled'] === '1' ) {
                echo '<span class="sfwf-scheduled-notice" style="margin-left:10px; color:#007cba;">' . esc_html__( 'Forecast scheduled! It will run in the background shortly.', 'aaa-wf-sfwf' ) . '</span>';
            }
            ?>
        </div>
    </div>

<?php
}
