<?php
// @version 2.0.0
defined( 'ABSPATH' ) || exit;

$s = DDD_DT_URL_Cleaner::settings();

$csv = function( $arr ) {
    if ( ! is_array( $arr ) ) return '';
    return implode( ', ', array_map( 'sanitize_key', $arr ) );
};

echo '<h2>' . esc_html__( 'URL Cleaner', 'ddd-dev-tools' ) . '</h2>';
echo '<p>' . esc_html__( 'Use when you are seeing unwanted query-based URLs being indexed or shared (e.g. add-to-cart links, layered-nav filter URLs).', 'ddd-dev-tools' ) . '</p>';
echo '<div class="notice notice-info"><p>' . esc_html__( 'Recommendation: start with “Log only” + Debug logging, confirm what would be redirected, then switch to Redirect.', 'ddd-dev-tools' ) . '</p></div>';

echo '<form method="post">';
wp_nonce_field( 'ddd_dt_save', 'ddd_dt_nonce' );
echo '<input type="hidden" name="ddd_dt_action" value="save">';
echo '<input type="hidden" name="tab" value="url_cleaner">';

echo '<table class="form-table" role="presentation"><tbody>';

echo '<tr><th scope="row">' . esc_html__( 'Enable', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="enabled" value="1" ' . checked( ! empty( $s['enabled'] ), true, false ) . '> ' .
    esc_html__( 'Enable URL cleaning', 'ddd-dev-tools' ) . '</label>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Run only on 404', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="only_on_404" value="1" ' . checked( ! empty( $s['only_on_404'] ), true, false ) . '> ' .
    esc_html__( 'Only act if the request is a 404', 'ddd-dev-tools' ) . '</label>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Mode', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="radio" name="mode" value="redirect" ' . checked( ( $s['mode'] ?? 'redirect' ) !== 'log_only', true, false ) . '> ' . esc_html__( 'Redirect', 'ddd-dev-tools' ) . '</label><br>';
echo '<label><input type="radio" name="mode" value="log_only" ' . checked( ( $s['mode'] ?? '' ) === 'log_only', true, false ) . '> ' . esc_html__( 'Log only (no redirect)', 'ddd-dev-tools' ) . '</label>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Redirect code', 'ddd-dev-tools' ) . '</th><td>';
$code = (int) ( $s['redirect_code'] ?? 301 );
echo '<select name="redirect_code">';
echo '<option value="301" ' . selected( $code, 301, false ) . '>301</option>';
echo '<option value="302" ' . selected( $code, 302, false ) . '>302</option>';
echo '</select>';
echo '<p class="description">' . esc_html__( '301 can be cached by browsers/proxies. On live, 302 is safer while testing.', 'ddd-dev-tools' ) . '</p>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Strip all parameters', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="strip_all" value="1" ' . checked( ! empty( $s['strip_all'] ), true, false ) . '> ' .
    esc_html__( 'Remove ALL query params except preserved ones (higher risk)', 'ddd-dev-tools' ) . '</label>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Debug logging (module)', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="debug_enabled" value="1" ' . checked( ! empty( $s['debug_enabled'] ), true, false ) . '> ' .
    esc_html__( 'Write redirect decisions to log files (requires Global debug ON)', 'ddd-dev-tools' ) . '</label>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Strip params (exact, CSV)', 'ddd-dev-tools' ) . '</th><td>';
echo '<textarea name="strip_exact" rows="2" class="large-text code">' . esc_textarea( $csv( $s['strip_exact'] ?? [] ) ) . '</textarea>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Strip prefixes (CSV)', 'ddd-dev-tools' ) . '</th><td>';
echo '<textarea name="strip_prefixes" rows="2" class="large-text code">' . esc_textarea( $csv( $s['strip_prefixes'] ?? [] ) ) . '</textarea>';
echo '<p class="description">' . esc_html__( 'Examples: filter_, query_type_, attribute_', 'ddd-dev-tools' ) . '</p>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Preserve params (exact, CSV)', 'ddd-dev-tools' ) . '</th><td>';
echo '<textarea name="preserve_exact" rows="2" class="large-text code">' . esc_textarea( $csv( $s['preserve_exact'] ?? [] ) ) . '</textarea>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Preserve prefixes (CSV)', 'ddd-dev-tools' ) . '</th><td>';
echo '<textarea name="preserve_prefix" rows="2" class="large-text code">' . esc_textarea( $csv( $s['preserve_prefix'] ?? [] ) ) . '</textarea>';
echo '<p class="description">' . esc_html__( 'Examples: utm_', 'ddd-dev-tools' ) . '</p>';
echo '</td></tr>';

echo '</tbody></table>';
submit_button( __( 'Save Changes', 'ddd-dev-tools' ) );
echo '</form>';
