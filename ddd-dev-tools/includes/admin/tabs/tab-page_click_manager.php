<?php
// @version 2.0.0
defined( 'ABSPATH' ) || exit;

$s = DDD_DT_Page_Click_Manager::settings();
$endpoint = home_url( '/wp-json/ddd-pcm/v1/log' );

echo '<h2>' . esc_html__( 'Page Click Manager', 'ddd-dev-tools' ) . '</h2>';
echo '<p>' . esc_html__( 'Use when you have a “wrong-site” landing page and want to record which buttons visitors click (and optionally email the admin).', 'ddd-dev-tools' ) . '</p>';

echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Endpoint:', 'ddd-dev-tools' ) . '</strong> <code>' . esc_html( $endpoint ) . '</code></p></div>';

echo '<form method="post">';
wp_nonce_field( 'ddd_dt_save', 'ddd_dt_nonce' );
echo '<input type="hidden" name="ddd_dt_action" value="save">';
echo '<input type="hidden" name="tab" value="page_click_manager">';

echo '<table class="form-table" role="presentation"><tbody>';

echo '<tr><th scope="row">' . esc_html__( 'Enable', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="enabled" value="1" ' . checked( ! empty( $s['enabled'] ), true, false ) . '> ' .
    esc_html__( 'Enable the REST endpoint', 'ddd-dev-tools' ) . '</label>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Token', 'ddd-dev-tools' ) . '</th><td>';
echo '<input type="text" class="regular-text code" name="token" value="' . esc_attr( (string) ( $s['token'] ?? '' ) ) . '">';
echo '<p class="description">' . esc_html__( 'Must match the token used by the landing page JavaScript. Treat as a shared secret.', 'ddd-dev-tools' ) . '</p>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Email notifications', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="email_enabled" value="1" ' . checked( ! empty( $s['email_enabled'] ), true, false ) . '> ' .
    esc_html__( 'Email admin when a click event is received (rate-limited)', 'ddd-dev-tools' ) . '</label>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Email to', 'ddd-dev-tools' ) . '</th><td>';
echo '<input type="email" class="regular-text" name="email_to" value="' . esc_attr( (string) ( $s['email_to'] ?? '' ) ) . '">';
echo '<p class="description">' . esc_html__( 'Leave blank to use the site admin email.', 'ddd-dev-tools' ) . '</p>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Email subject', 'ddd-dev-tools' ) . '</th><td>';
echo '<input type="text" class="regular-text" name="email_subject" value="' . esc_attr( (string) ( $s['email_subject'] ?? '' ) ) . '">';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Email cooldown (seconds)', 'ddd-dev-tools' ) . '</th><td>';
echo '<input type="number" min="60" max="86400" name="email_cooldown_seconds" value="' . esc_attr( (int) ( $s['email_cooldown_seconds'] ?? 3600 ) ) . '">';
echo '<p class="description">' . esc_html__( 'Prevents email spam for repeated clicks from the same IP/destination/reason.', 'ddd-dev-tools' ) . '</p>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Anonymize IP', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="anonymize_ip" value="1" ' . checked( ! empty( $s['anonymize_ip'] ), true, false ) . '> ' .
    esc_html__( 'Mask the last octet for IPv4 (privacy)', 'ddd-dev-tools' ) . '</label>';
echo '</td></tr>';

echo '<tr><th scope="row">' . esc_html__( 'Debug logging (module)', 'ddd-dev-tools' ) . '</th><td>';
echo '<label><input type="checkbox" name="debug_enabled" value="1" ' . checked( ! empty( $s['debug_enabled'] ), true, false ) . '> ' .
    esc_html__( 'Write received events to log files (requires Global debug ON)', 'ddd-dev-tools' ) . '</label>';
echo '</td></tr>';

echo '</tbody></table>';
submit_button( __( 'Save Changes', 'ddd-dev-tools' ) );
echo '</form>';

echo '<hr><h3>' . esc_html__( 'Regenerate token', 'ddd-dev-tools' ) . '</h3>';
echo '<form method="post">';
wp_nonce_field( 'ddd_dt_save', 'ddd_dt_nonce' );
echo '<input type="hidden" name="ddd_dt_action" value="regen_pcm_token">';
echo '<input type="hidden" name="tab" value="page_click_manager">';
submit_button( __( 'Generate new token', 'ddd-dev-tools' ), 'secondary', 'submit', false );
echo '</form>';

echo '<hr><h3>' . esc_html__( 'Example JavaScript snippet', 'ddd-dev-tools' ) . '</h3>';
$snippet = "fetch('{$endpoint}',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:'" . (string) ( $s['token'] ?? '' ) . "',dest_url:'https://example.com',reason:'wrong_site',current_url:location.href,referrer:document.referrer,user_agent:navigator.userAgent,utc_time:new Date().toISOString()})});";
echo '<textarea rows="4" class="large-text code" readonly>' . esc_textarea( $snippet ) . '</textarea>';
