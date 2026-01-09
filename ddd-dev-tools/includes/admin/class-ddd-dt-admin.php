<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_Admin {
    private const PAGE_SLUG = 'ddd-dev-tools';

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_post' ] );
    }

    public static function add_menu() {
        add_management_page(
            __( 'DDD Dev Tools', 'ddd-dev-tools' ),
            __( 'DDD Dev Tools', 'ddd-dev-tools' ),
            'manage_options',
            self::PAGE_SLUG,
            [ __CLASS__, 'render' ]
        );
    }

    public static function enqueue( $hook ) {
        if ( $hook !== 'tools_page_' . self::PAGE_SLUG ) return;
        wp_enqueue_style( 'ddd-dt-admin', DDD_DT_URL . 'assets/css/admin.css', [], DDD_DT_VERSION );
        wp_enqueue_script( 'ddd-dt-admin', DDD_DT_URL . 'assets/js/admin.js', [ 'jquery' ], DDD_DT_VERSION, true );
    }

    public static function handle_post() {
        if ( empty( $_GET['page'] ) || $_GET['page'] !== self::PAGE_SLUG ) return;
        if ( ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) !== 'POST' ) return;
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

        check_admin_referer( 'ddd_dt_save', 'ddd_dt_nonce' );

        $action = isset( $_POST['ddd_dt_action'] ) ? sanitize_key( wp_unslash( $_POST['ddd_dt_action'] ) ) : 'save';
        $tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'overview';

        if ( $action === 'clear_logs' ) {
            self::clear_logs();
        } elseif ( $action === 'regen_pcm_token' ) {
            self::regen_pcm_token();
        } else {
            self::save_tab( $tab );
        }

        $url = add_query_arg( [ 'page' => self::PAGE_SLUG, 'tab' => $tab, 'updated' => 1 ], admin_url( 'tools.php' ) );
        wp_safe_redirect( $url );
        exit;
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
        include DDD_DT_DIR . 'includes/admin/page.php';
    }

    private static function save_tab( string $tab ) {
        if ( $tab === 'general' ) self::save_general();
        if ( $tab === 'url_cleaner' ) self::save_url_cleaner();
        if ( $tab === 'pagination_redirect' ) self::save_pagination_redirect();
        if ( $tab === 'page_click_manager' ) self::save_page_click_manager();
        if ( $tab === 'troubleshooter' ) DDD_DT_Admin_Save::save_troubleshooter();
        if ( $tab === 'atum_log_viewer' ) DDD_DT_Admin_Save::save_atum_log_viewer();
        if ( $tab === 'debug_log_manager' ) DDD_DT_Admin_Save::save_debug_log_manager();
        if ( $tab === 'product_debugger' ) DDD_DT_Admin_Save::save_product_debugger();
        if ( $tab === 'order_debugger' ) DDD_DT_Admin_Save::save_order_debugger();
    }

    private static function save_general() {
        $g = [
            'debug_enabled'      => ! empty( $_POST['debug_enabled'] ) ? 1 : 0,
            'mirror_error_log'   => ! empty( $_POST['mirror_error_log'] ) ? 1 : 0,
            'log_max_mb'         => max( 1, min( 50, absint( $_POST['log_max_mb'] ?? 5 ) ) ),
            'log_retention_days' => max( 1, min( 365, absint( $_POST['log_retention_days'] ?? 7 ) ) ),
        ];
        DDD_DT_Options::set( 'ddd_dt_general', $g, 'global', 0 );
    }

    private static function save_url_cleaner() {
        $s = DDD_DT_URL_Cleaner::settings();
        $s['enabled'] = ! empty( $_POST['enabled'] ) ? 1 : 0;
        $s['only_on_404'] = ! empty( $_POST['only_on_404'] ) ? 1 : 0;
        $s['mode'] = ( isset( $_POST['mode'] ) && $_POST['mode'] === 'log_only' ) ? 'log_only' : 'redirect';
        $s['redirect_code'] = in_array( (int) ( $_POST['redirect_code'] ?? 301 ), [ 301, 302 ], true ) ? (int) $_POST['redirect_code'] : 301;
        $s['strip_all'] = ! empty( $_POST['strip_all'] ) ? 1 : 0;
        $s['debug_enabled'] = ! empty( $_POST['debug_enabled'] ) ? 1 : 0;
        $s['strip_exact'] = self::csv_to_keys( (string) ( $_POST['strip_exact'] ?? '' ) );
        $s['strip_prefixes'] = self::csv_to_keys( (string) ( $_POST['strip_prefixes'] ?? '' ) );
        $s['preserve_exact'] = self::csv_to_keys( (string) ( $_POST['preserve_exact'] ?? '' ) );
        $s['preserve_prefix'] = self::csv_to_keys( (string) ( $_POST['preserve_prefix'] ?? '' ) );
        DDD_DT_Options::set( 'ddd_url_cleaner_settings', $s, 'global', 0 );
    }

    private static function save_pagination_redirect() {
        $s = DDD_DT_Pagination_Redirect::settings();
        $s['enabled'] = ! empty( $_POST['enabled'] ) ? 1 : 0;
        $s['only_on_404'] = ! empty( $_POST['only_on_404'] ) ? 1 : 0;
        $s['mode'] = ( isset( $_POST['mode'] ) && $_POST['mode'] === 'log_only' ) ? 'log_only' : 'redirect';
        $s['redirect_code'] = in_array( (int) ( $_POST['redirect_code'] ?? 301 ), [ 301, 302 ], true ) ? (int) $_POST['redirect_code'] : 301;
        $s['preserve_query'] = ! empty( $_POST['preserve_query'] ) ? 1 : 0;
        $s['debug_enabled'] = ! empty( $_POST['debug_enabled'] ) ? 1 : 0;
        DDD_DT_Options::set( 'ddd_pagination_redirect_settings', $s, 'global', 0 );
    }

    private static function save_page_click_manager() {
        $s = DDD_DT_Page_Click_Manager::settings();
        $s['enabled'] = ! empty( $_POST['enabled'] ) ? 1 : 0;
        $s['email_enabled'] = ! empty( $_POST['email_enabled'] ) ? 1 : 0;
        $s['email_to'] = sanitize_email( (string) ( $_POST['email_to'] ?? '' ) );
        $s['email_subject'] = sanitize_text_field( (string) ( $_POST['email_subject'] ?? '' ) );
        $s['email_cooldown_seconds'] = max( 60, min( 86400, absint( $_POST['email_cooldown_seconds'] ?? 3600 ) ) );
        $s['anonymize_ip'] = ! empty( $_POST['anonymize_ip'] ) ? 1 : 0;
        $s['debug_enabled'] = ! empty( $_POST['debug_enabled'] ) ? 1 : 0;
        $token = sanitize_text_field( (string) ( $_POST['token'] ?? '' ) );
        if ( $token !== '' ) $s['token'] = $token;
        DDD_DT_Options::set( 'ddd_page_click_manager_settings', $s, 'global', 0 );
    }
    private static function regen_pcm_token() {
        $s = DDD_DT_Page_Click_Manager::settings();
        $s['token'] = wp_generate_password( 18, false, false );
        DDD_DT_Options::set( 'ddd_page_click_manager_settings', $s, 'global', 0 );
    }

    private static function clear_logs() {
        foreach ( DDD_DT_Logger::list_log_files() as $f ) {
            @unlink( $f );
        }
    }

    private static function csv_to_keys( string $csv ): array {
        $parts = array_filter( array_map( 'trim', explode( ',', $csv ) ) );
        $out = [];
        foreach ( $parts as $p ) {
            $p = sanitize_key( $p );
            if ( $p !== '' ) $out[] = $p;
        }
        return array_values( array_unique( array_slice( $out, 0, 100 ) ) );
    }
}
