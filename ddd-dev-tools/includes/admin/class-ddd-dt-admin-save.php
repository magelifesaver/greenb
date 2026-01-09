<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_Admin_Save {
    public static function save_troubleshooter() {
        $s = DDD_DT_Troubleshooter::settings();
        $s['enabled'] = ! empty( $_POST['enabled'] ) ? 1 : 0;
        $s['debug_enabled'] = ! empty( $_POST['debug_enabled'] ) ? 1 : 0;
        DDD_DT_Options::set( 'ddd_troubleshooter_settings', $s, 'global', 0 );
    }

    public static function save_atum_log_viewer() {
        $s = DDD_DT_ATUM_Log_Viewer::settings();
        $s['enabled'] = ! empty( $_POST['enabled'] ) ? 1 : 0;
        $s['use_datatables_cdn'] = ! empty( $_POST['use_datatables_cdn'] ) ? 1 : 0;
        $s['max_rows'] = max( 50, min( 5000, absint( $_POST['max_rows'] ?? 500 ) ) );
        $s['debug_enabled'] = ! empty( $_POST['debug_enabled'] ) ? 1 : 0;
        DDD_DT_Options::set( 'ddd_atum_log_viewer_settings', $s, 'global', 0 );
    }

    public static function save_debug_log_manager() {
        $s = DDD_DT_Debug_Log_Manager::settings();
        $s['enabled'] = ! empty( $_POST['enabled'] ) ? 1 : 0;
        $s['download_chunk_mb'] = max( 1, min( 25, absint( $_POST['download_chunk_mb'] ?? 2 ) ) );
        $s['debug_enabled'] = ! empty( $_POST['debug_enabled'] ) ? 1 : 0;
        DDD_DT_Options::set( 'ddd_debug_log_manager_settings', $s, 'global', 0 );
    }

    public static function save_product_debugger() {
        $s = DDD_DT_Product_Debugger::settings();
        $s['enabled'] = ! empty( $_POST['enabled'] ) ? 1 : 0;
        $s['debug_enabled'] = ! empty( $_POST['debug_enabled'] ) ? 1 : 0;
        DDD_DT_Options::set( 'ddd_product_debugger_settings', $s, 'global', 0 );
    }

    public static function save_order_debugger() {
        $s = DDD_DT_Order_Debugger::settings();
        $s['enabled'] = ! empty( $_POST['enabled'] ) ? 1 : 0;
        $s['debug_enabled'] = ! empty( $_POST['debug_enabled'] ) ? 1 : 0;
        DDD_DT_Options::set( 'ddd_order_debugger_settings', $s, 'global', 0 );
    }
}
