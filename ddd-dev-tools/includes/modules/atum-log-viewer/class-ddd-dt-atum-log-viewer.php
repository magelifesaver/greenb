<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

require_once DDD_DT_DIR . 'includes/modules/atum-log-viewer/class-ddd-dt-atum-logs.php';

class DDD_DT_ATUM_Log_Viewer {
    public static function settings(): array {
        $d = [
            'enabled'            => 0,
            'use_datatables_cdn' => 0,
            'max_rows'           => 500,
            'debug_enabled'      => 0,
        ];
        $s = DDD_DT_Options::get( 'ddd_atum_log_viewer_settings', [], 'global' );
        return is_array( $s ) ? array_merge( $d, $s ) : $d;
    }

    public static function init() {
        if ( ! is_admin() ) {
            return;
        }
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    public static function is_enabled(): bool {
        return ! empty( self::settings()['enabled'] );
    }

    public static function enqueue_assets( $hook ) {
        if ( 'tools_page_ddd-dev-tools' !== $hook ) {
            return;
        }
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
        if ( 'atum_log_viewer' !== $tab ) {
            return;
        }

        wp_enqueue_style( 'ddd-dt-atum-log-viewer', DDD_DT_URL . 'assets/css/atum-log-viewer.css', [], DDD_DT_VERSION );

        $s = self::settings();
        if ( empty( $s['enabled'] ) || empty( $s['use_datatables_cdn'] ) ) {
            return;
        }

        wp_enqueue_style( 'ddd-dt-datatables', 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css', [], DDD_DT_VERSION );
        wp_enqueue_script( 'ddd-dt-datatables', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', [ 'jquery' ], DDD_DT_VERSION, true );
        wp_enqueue_script( 'ddd-dt-atum-log-viewer', DDD_DT_URL . 'assets/js/atum-log-viewer.js', [ 'jquery', 'ddd-dt-datatables' ], DDD_DT_VERSION, true );
    }
}
