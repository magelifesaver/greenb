<?php
// @version 2.0.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_Page_Click_Manager {
    public static function init() {
        if ( empty( self::settings()['enabled'] ) ) {
            return;
        }
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function settings(): array {
        $d = [
            'enabled' => 0,
            'token' => '',
            'email_enabled' => 0,
            'email_to' => '',
            'email_subject' => 'Wrong-site navigation click detected',
            'email_cooldown_seconds' => 3600,
            'anonymize_ip' => 0,
            'debug_enabled' => 0,
        ];
        $s = DDD_DT_Options::get( 'ddd_page_click_manager_settings', [], 'global' );
        $s = is_array( $s ) ? array_merge( $d, $s ) : $d;
        if ( empty( $s['token'] ) ) {
            $s['token'] = wp_generate_password( 18, false, false );
        }
        return $s;
    }

    public static function register_routes() {
        register_rest_route( 'ddd-pcm/v1', '/log', [
            'methods' => 'POST',
            'permission_callback' => [ __CLASS__, 'permission_check' ],
            'callback' => [ __CLASS__, 'handle_log' ],
        ] );
    }

    public static function permission_check( WP_REST_Request $request ) {
        $s = self::settings();
        $data = json_decode( (string) $request->get_body(), true );
        $token = is_array( $data ) && isset( $data['token'] ) ? sanitize_text_field( (string) $data['token'] ) : '';
        return ( $token !== '' && $token === (string) $s['token'] );
    }

    public static function handle_log( WP_REST_Request $request ) {
        $s = self::settings();
        $data = (array) ( $request->get_json_params() ?: [] );

        $entry = [
            'utc_time' => sanitize_text_field( (string) ( $data['utc_time'] ?? gmdate( 'c' ) ) ),
            'ip' => self::client_ip( ! empty( $s['anonymize_ip'] ) ),
            'dest_url' => esc_url_raw( (string) ( $data['dest_url'] ?? '' ) ),
            'reason' => sanitize_text_field( (string) ( $data['reason'] ?? '' ) ),
            'current_url' => esc_url_raw( (string) ( $data['current_url'] ?? '' ) ),
            'referrer' => esc_url_raw( (string) ( $data['referrer'] ?? '' ) ),
            'user_agent' => sanitize_text_field( (string) ( $data['user_agent'] ?? '' ) ),
            'site' => [ 'blog_id' => function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0, 'home' => home_url() ],
        ];

        DDD_DT_Logger::write( 'page_click_manager', 'Click event received', $entry, 'info' );

        if ( ! empty( $s['email_enabled'] ) ) {
            self::maybe_email_admin( $entry, $s );
        }

        return new WP_REST_Response( [ 'ok' => true ], 200 );
    }

    private static function client_ip( bool $anonymize ): string {
        $ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        if ( $anonymize && $ip && strpos( $ip, '.' ) !== false ) {
            $parts = explode( '.', $ip );
            if ( count( $parts ) === 4 ) {
                $parts[3] = '0';
                return implode( '.', $parts );
            }
        }
        return $ip;
    }

    private static function maybe_email_admin( array $entry, array $s ) {
        $key_raw = (string) $entry['ip'] . '|' . (string) $entry['dest_url'] . '|' . (string) $entry['reason'];
        $t_key = 'ddd_pcm_email_' . substr( md5( $key_raw ), 0, 12 );
        if ( get_transient( $t_key ) ) return;

        $cool = max( 60, absint( $s['email_cooldown_seconds'] ?? 3600 ) );
        set_transient( $t_key, 1, $cool );

        $to = sanitize_email( (string) ( $s['email_to'] ?? '' ) );
        if ( ! $to ) $to = get_option( 'admin_email' );
        $subject = sanitize_text_field( (string) ( $s['email_subject'] ?? 'Wrong-site navigation click detected' ) );
        $body = "Wrong-site navigation click detected\n\n" . wp_json_encode( $entry, JSON_PRETTY_PRINT );
        wp_mail( $to, $subject, $body );
    }
}
