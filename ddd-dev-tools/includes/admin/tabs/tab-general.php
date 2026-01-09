<?php
// @version 2.0.0
defined( 'ABSPATH' ) || exit;

$g = DDD_DT_Options::get( 'ddd_dt_general', [], 'global' );
$g = is_array( $g ) ? $g : [];
$debug = ! empty( $g['debug_enabled'] );
$mirror = ! empty( $g['mirror_error_log'] );
$max_mb = isset( $g['log_max_mb'] ) ? (int) $g['log_max_mb'] : 5;
$ret_days = isset( $g['log_retention_days'] ) ? (int) $g['log_retention_days'] : 7;

echo '<h2>' . esc_html__( 'General', 'ddd-dev-tools' ) . '</h2>';
echo '<p>' . esc_html__( 'Global settings apply to all modules.', 'ddd-dev-tools' ) . '</p>';

echo '<form method="post">';
wp_nonce_field( 'ddd_dt_save', 'ddd_dt_nonce' );
echo '<input type="hidden" name="ddd_dt_action" value="save">';
echo '<input type="hidden" name="tab" value="general">';

echo '<table class="form-table" role="presentation"><tbody>';

echo '<tr><th scope="row">' . esc_html__( 'Global debug logging', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="debug_enabled" value="1" ' . checked( $debug, true, false ) . '> ' .
    esc_html__( 'Enable file logging (requires module debug ON too)', 'ddd-dev-tools' ) . '</label>';
echo '<p class="description">' . esc_html__( 'Leave OFF on live unless actively troubleshooting.', 'ddd-dev-tools' ) . '</p>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Mirror logs to PHP error_log', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="mirror_error_log" value="1" ' . checked( $mirror, true, false ) . '> ' .
    esc_html__( 'Also write a copy to error_log (if configured)', 'ddd-dev-tools' ) . '</label>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Max log file size (MB)', 'ddd-dev-tools' ) . '</th><td>';
echo '<input type="number" min="1" max="50" name="log_max_mb" value="' . esc_attr( $max_mb ) . '">';
echo '<p class="description">' . esc_html__( 'When exceeded, a .bak copy is created and a new log starts.', 'ddd-dev-tools' ) . '</p>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Log retention (days)', 'ddd-dev-tools' ) . '</th><td>';
echo '<input type="number" min="1" max="365" name="log_retention_days" value="' . esc_attr( $ret_days ) . '">';
echo '<p class="description">' . esc_html__( 'Old log files are pruned daily while the plugin is active.', 'ddd-dev-tools' ) . '</p>';
echo '</td></tr>';

echo '</tbody></table>';

submit_button( __( 'Save Changes', 'ddd-dev-tools' ) );
echo '</form>';
