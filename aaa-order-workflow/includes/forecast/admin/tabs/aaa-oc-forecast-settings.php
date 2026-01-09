<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/forecast/admin/tabs/aaa-oc-forecast-settings.php
 * Purpose: Renders the Forecast tab in the Workflow Settings page. Allows the
 *          merchant to customise labels and thresholds for the Not Moving and
 *          Stale product states as well as choose actions to apply when these
 *          thresholds are reached. Options are stored in the aaa_oc_options
 *          table under the "forecast" scope. Keeping this file under 150
 *          lines ensures easy maintenance.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Ensure option helpers are loaded. Options class may not be loaded when
// viewing settings pages directly. This includes functions aaa_oc_get_option
// and aaa_oc_set_option.
if ( ! function_exists( 'aaa_oc_get_option' ) ) {
    require_once plugin_dir_path( __DIR__ ) . '/../../core/options/class-aaa-oc-options.php';
    AAA_OC_Options::init();
}

// --- Handle form submission ---
if ( isset( $_POST['aaa_oc_forecast_settings_submit'] ) && check_admin_referer( 'aaa_oc_forecast_settings_nonce' ) ) {
    // Sanitize and save Not Moving fields
    $nm_label  = isset( $_POST['forecast_not_moving_label'] ) ? sanitize_text_field( wp_unslash( $_POST['forecast_not_moving_label'] ) ) : 'Not Moving';
    $nm_days   = isset( $_POST['forecast_not_moving_days'] ) ? absint( $_POST['forecast_not_moving_days'] ) : 30;
    $nm_action = isset( $_POST['forecast_not_moving_action'] ) ? sanitize_text_field( wp_unslash( $_POST['forecast_not_moving_action'] ) ) : 'review';

    // Sanitize and save Stale fields
    $st_label  = isset( $_POST['forecast_stale_label'] ) ? sanitize_text_field( wp_unslash( $_POST['forecast_stale_label'] ) ) : 'Stale';
    $st_days   = isset( $_POST['forecast_stale_days'] ) ? absint( $_POST['forecast_stale_days'] ) : 60;
    $st_action = isset( $_POST['forecast_stale_action'] ) ? sanitize_text_field( wp_unslash( $_POST['forecast_stale_action'] ) ) : 'clearance';

    // Persist values to options table under the forecast scope
    aaa_oc_set_option( 'forecast_not_moving_label', $nm_label, 'forecast' );
    aaa_oc_set_option( 'forecast_not_moving_days', $nm_days, 'forecast' );
    aaa_oc_set_option( 'forecast_not_moving_action', $nm_action, 'forecast' );

    aaa_oc_set_option( 'forecast_stale_label', $st_label, 'forecast' );
    aaa_oc_set_option( 'forecast_stale_days', $st_days, 'forecast' );
    aaa_oc_set_option( 'forecast_stale_action', $st_action, 'forecast' );

    echo '<div class="notice notice-success"><p>' . esc_html__( 'Forecast settings saved.', 'aaa-oc' ) . '</p></div>';
}

// --- Load current option values with sensible defaults ---
$nm_label  = aaa_oc_get_option( 'forecast_not_moving_label', 'forecast', 'Not Moving' );
$nm_days   = aaa_oc_get_option( 'forecast_not_moving_days', 'forecast', 30 );
$nm_action = aaa_oc_get_option( 'forecast_not_moving_action', 'forecast', 'review' );

$st_label  = aaa_oc_get_option( 'forecast_stale_label', 'forecast', 'Stale' );
$st_days   = aaa_oc_get_option( 'forecast_stale_days', 'forecast', 60 );
$st_action = aaa_oc_get_option( 'forecast_stale_action', 'forecast', 'clearance' );

// Define available actions for dropdown selects. You can customise these
// options later or map them to internal flags. Keys are stored in the
// options table; values are used for display only.
$actions = [
    'review'      => __( 'Review', 'aaa-oc' ),
    'clearance'   => __( 'Clearance', 'aaa-oc' ),
    'discontinue' => __( 'Discontinue', 'aaa-oc' ),
];

?>
<div class="wrap">
    <h2><?php esc_html_e( 'Forecast Settings', 'aaa-oc' ); ?></h2>
    <form method="post">
        <?php wp_nonce_field( 'aaa_oc_forecast_settings_nonce' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="forecast_not_moving_label"><?php esc_html_e( 'Not Moving Label', 'aaa-oc' ); ?></label>
                </th>
                <td>
                    <input name="forecast_not_moving_label" id="forecast_not_moving_label" type="text" value="<?php echo esc_attr( $nm_label ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Name used when a product hasn\'t sold within the first threshold.', 'aaa-oc' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="forecast_not_moving_days"><?php esc_html_e( 'Not Moving Threshold (days)', 'aaa-oc' ); ?></label>
                </th>
                <td>
                    <input name="forecast_not_moving_days" id="forecast_not_moving_days" type="number" min="1" value="<?php echo esc_attr( $nm_days ); ?>" />
                    <p class="description"><?php esc_html_e( 'Number of days with no sales before a product is considered not moving.', 'aaa-oc' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="forecast_not_moving_action"><?php esc_html_e( 'Action When Not Moving', 'aaa-oc' ); ?></label>
                </th>
                <td>
                    <select name="forecast_not_moving_action" id="forecast_not_moving_action">
                        <?php foreach ( $actions as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $nm_action, $key ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Choose what happens automatically when a product is marked not moving.', 'aaa-oc' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="forecast_stale_label"><?php esc_html_e( 'Stale Label', 'aaa-oc' ); ?></label>
                </th>
                <td>
                    <input name="forecast_stale_label" id="forecast_stale_label" type="text" value="<?php echo esc_attr( $st_label ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Name used when a product hasn\'t sold within the second threshold.', 'aaa-oc' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="forecast_stale_days"><?php esc_html_e( 'Stale Threshold (days)', 'aaa-oc' ); ?></label>
                </th>
                <td>
                    <input name="forecast_stale_days" id="forecast_stale_days" type="number" min="1" value="<?php echo esc_attr( $st_days ); ?>" />
                    <p class="description"><?php esc_html_e( 'Number of days with no sales before a product is considered stale.', 'aaa-oc' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="forecast_stale_action"><?php esc_html_e( 'Action When Stale', 'aaa-oc' ); ?></label>
                </th>
                <td>
                    <select name="forecast_stale_action" id="forecast_stale_action">
                        <?php foreach ( $actions as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $st_action, $key ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Choose what happens automatically when a product is marked stale.', 'aaa-oc' ); ?></p>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button type="submit" name="aaa_oc_forecast_settings_submit" class="button button-primary">
                <?php esc_html_e( 'Save Settings', 'aaa-oc' ); ?>
            </button>
        </p>
    </form>
</div>