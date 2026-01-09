<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

require_once DDD_DT_DIR . 'includes/modules/debug-log-manager/class-ddd-dt-debug-log-file.php';
require_once DDD_DT_DIR . 'includes/modules/debug-log-manager/class-ddd-dt-debug-log-snapshot.php';

class DDD_DT_Debug_Log_Manager {
    private static $file;
    private static $snapshot;

    public static function settings(): array {
        $d = [
            'enabled'           => 0,
            'download_chunk_mb' => 2,
            'debug_enabled'     => 0,
        ];
        $s = DDD_DT_Options::get( 'ddd_debug_log_manager_settings', [], 'global' );
        $s = is_array( $s ) ? array_merge( $d, $s ) : $d;
        $s['download_chunk_mb'] = max( 1, min( 25, absint( $s['download_chunk_mb'] ) ) );
        return $s;
    }

    public static function init() {
        if ( ! is_admin() ) {
            return;
        }

        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );

        if ( empty( self::settings()['enabled'] ) ) {
            return;
        }

        self::$file = new DDD_DT_Debug_Log_File();
        self::$snapshot = new DDD_DT_Debug_Log_Snapshot( self::$file->get_path() );
    }

    public static function file(): ?DDD_DT_Debug_Log_File {
        return self::$file;
    }

    public static function snapshot(): ?DDD_DT_Debug_Log_Snapshot {
        return self::$snapshot;
    }

    public static function enqueue_assets( $hook ) {
        if ( 'tools_page_ddd-dev-tools' !== $hook ) {
            return;
        }
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
        if ( 'debug_log_manager' !== $tab ) {
            return;
        }

        wp_enqueue_style( 'ddd-dt-debug-log-manager', DDD_DT_URL . 'assets/css/debug-log-manager.css', [], DDD_DT_VERSION );

        if ( empty( self::settings()['enabled'] ) ) {
            return;
        }

        wp_enqueue_script( 'ddd-dt-debug-log-manager', DDD_DT_URL . 'assets/js/debug-log-manager.js', [ 'jquery' ], DDD_DT_VERSION, true );
        wp_localize_script(
            'ddd-dt-debug-log-manager',
            'DDD_DT_DebugLog',
            [
                'ajax_url'           => admin_url( 'admin-ajax.php' ),
                'nonce_tail'         => wp_create_nonce( 'ddd_dt_dbg_tail' ),
                'nonce_snapshot'     => wp_create_nonce( 'ddd_dt_dbg_snapshot' ),
                'nonce_clear_snap'   => wp_create_nonce( 'ddd_dt_dbg_clear_snapshot' ),
                'tail_action'        => 'ddd_dt_dbg_tail',
                'snapshot_action'    => 'ddd_dt_dbg_snapshot',
                'clear_snap_action'  => 'ddd_dt_dbg_clear_snapshot',
            ]
        );
    }
}
