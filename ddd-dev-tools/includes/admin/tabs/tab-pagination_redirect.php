<?php
// @version 2.0.0
defined( 'ABSPATH' ) || exit;

$s = DDD_DT_Pagination_Redirect::settings();

echo '<h2>' . esc_html__( 'Pagination Redirect', 'ddd-dev-tools' ) . '</h2>';
echo '<p>' . esc_html__( 'Redirects URLs like /category/widgets/page/2/ → /category/widgets/. Useful when infinite scroll replaces paged archives or old links still exist.', 'ddd-dev-tools' ) . '</p>';

echo '<form method="post">';
wp_nonce_field( 'ddd_dt_save', 'ddd_dt_nonce' );
echo '<input type="hidden" name="ddd_dt_action" value="save">';
echo '<input type="hidden" name="tab" value="pagination_redirect">';

echo '<table class="form-table" role="presentation"><tbody>';

echo '<tr><th scope="row">' . esc_html__( 'Enable', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="enabled" value="1" ' . checked( ! empty( $s['enabled'] ), true, false ) . '> ' .
    esc_html__( 'Enable pagination redirects', 'ddd-dev-tools' ) . '</label>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Only on 404', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="only_on_404" value="1" ' . checked( ! empty( $s['only_on_404'] ), true, false ) . '> ' .
    esc_html__( 'Recommended (limits unexpected redirects)', 'ddd-dev-tools' ) . '</label>';
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
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Preserve query string', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="preserve_query" value="1" ' . checked( ! empty( $s['preserve_query'] ), true, false ) . '> ' .
    esc_html__( 'Keep the original ?query=string on the redirect', 'ddd-dev-tools' ) . '</label>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Debug logging (module)', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="debug_enabled" value="1" ' . checked( ! empty( $s['debug_enabled'] ), true, false ) . '> ' .
    esc_html__( 'Write redirect decisions to log files (requires Global debug ON)', 'ddd-dev-tools' ) . '</label>';
echo '</td></tr>';

echo '</tbody></table>';
submit_button( __( 'Save Changes', 'ddd-dev-tools' ) );
echo '</form>';
