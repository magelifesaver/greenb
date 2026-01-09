<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

require_once DDD_DT_DIR . 'includes/modules/troubleshooter/class-ddd-dt-ts-targets.php';
require_once DDD_DT_DIR . 'includes/modules/troubleshooter/class-ddd-dt-ts-ajax-flush.php';
require_once DDD_DT_DIR . 'includes/modules/troubleshooter/class-ddd-dt-ts-ajax-search.php';
require_once DDD_DT_DIR . 'includes/modules/troubleshooter/class-ddd-dt-ts-ajax-view.php';

class DDD_DT_Troubleshooter {
    public static function settings(): array {
        $d = [ 'enabled' => 0, 'debug_enabled' => 0 ];
        $s = DDD_DT_Options::get( 'ddd_troubleshooter_settings', [], 'global' );
        return is_array( $s ) ? array_merge( $d, $s ) : $d;
    }

    public static function init() {
        if ( ! is_admin() ) {
            return;
        }

        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ddd_dt_ts_search', [ __CLASS__, 'ajax_search' ] );
        add_action( 'wp_ajax_ddd_dt_ts_flush', [ __CLASS__, 'ajax_flush' ] );
        add_action( 'wp_ajax_ddd_dt_ts_view', [ __CLASS__, 'ajax_view' ] );
    }

    public static function is_enabled(): bool {
        return ! empty( self::settings()['enabled'] );
    }

    public static function enqueue_assets( $hook ) {
        if ( 'tools_page_ddd-dev-tools' !== $hook ) {
            return;
        }
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
        if ( 'troubleshooter' !== $tab ) {
            return;
        }

        wp_enqueue_style( 'ddd-dt-troubleshooter', DDD_DT_URL . 'assets/css/troubleshooter.css', [], DDD_DT_VERSION );
        wp_enqueue_script( 'ddd-dt-troubleshooter', DDD_DT_URL . 'assets/js/troubleshooter.js', [ 'jquery' ], DDD_DT_VERSION, true );
        wp_localize_script(
            'ddd-dt-troubleshooter',
            'DDD_DT_TS',
            [
                'ajax_url'     => admin_url( 'admin-ajax.php' ),
                'nonce_search' => wp_create_nonce( 'ddd_dt_ts_search' ),
                'nonce_flush'  => wp_create_nonce( 'ddd_dt_ts_flush' ),
                'nonce_view'   => wp_create_nonce( 'ddd_dt_ts_view' ),
                'enabled'      => self::is_enabled() ? 1 : 0,
                'engines'      => DDD_DT_TS_Ajax_Search::available_engines(),
                'defaults'     => DDD_DT_TS_Ajax_Search::ui_defaults(),
                'env_type'     => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'unknown',
            ]
        );
    }

    public static function view_data(): array {
        return DDD_DT_TS_Targets::get_admin_view_data();
    }

    public static function ajax_search() {
        DDD_DT_TS_Ajax_Search::handle();
    }

    public static function ajax_flush() {
        DDD_DT_TS_Ajax_Flush::handle();
    }

    public static function ajax_view() {
        DDD_DT_TS_Ajax_View::handle();
    }
}
