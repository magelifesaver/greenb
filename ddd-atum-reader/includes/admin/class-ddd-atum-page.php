<?php
/**
 * File: plugins/ddd-atum-reader/includes/admin/class-ddd-atum-page.php
 * Purpose: Register admin menu/page; enqueue assets; render via section partials.
 * Dependencies: sections in includes/admin/section/*.php; assets css/js; DDD_ATUM_Logs.
 * Needed by: Main plugin loader.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DDD_ATUM_Page {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
    }

    public static function register_menu() {
        add_menu_page(
            'ATUM Logs',
            'ATUM Logs',
            'manage_woocommerce', // allow shop managers too; change to manage_options if you want admin-only
            'ddd-atum-logs',
            [ __CLASS__, 'render_page' ],
            'dashicons-database',
            80
        );
    }

    public static function enqueue( $hook ) {
        if ( $hook !== 'toplevel_page_ddd-atum-logs' ) { return; }

        // DataTables CSS/JS + extensions (order matters)
        wp_enqueue_style( 'ddd-dt-core', 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css', [], '1.13.4' );
        wp_enqueue_style( 'ddd-dt-fixedheader', 'https://cdn.datatables.net/fixedheader/3.3.2/css/fixedHeader.dataTables.min.css', [ 'ddd-dt-core' ], '3.3.2' );
        wp_enqueue_style( 'ddd-dt-buttons', 'https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css', [ 'ddd-dt-core' ], '2.4.1' );

        wp_enqueue_style( 'ddd-atum-styles', DDD_ATUM_READER_URL . 'assets/css/ddd-atum-styles.css', [ 'ddd-dt-core' ], DDD_ATUM_READER_VERSION );

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'ddd-dt-core', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', [ 'jquery' ], '1.13.4', true );
        wp_enqueue_script( 'ddd-dt-fixedheader', 'https://cdn.datatables.net/fixedheader/3.3.2/js/dataTables.fixedHeader.min.js', [ 'ddd-dt-core' ], '3.3.2', true );
        wp_enqueue_script( 'ddd-dt-buttons', 'https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js', [ 'ddd-dt-core' ], '2.4.1', true );
        wp_enqueue_script( 'ddd-dt-jszip', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js', [], '3.10.1', true );
        wp_enqueue_script( 'ddd-dt-html5', 'https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js', [ 'ddd-dt-buttons', 'ddd-dt-jszip' ], '2.4.1', true );
        wp_enqueue_script( 'ddd-dt-print', 'https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js', [ 'ddd-dt-buttons' ], '2.4.1', true );
        // IMPORTANT: load colVis extension to avoid "Unknown button type: colvis"
        wp_enqueue_script( 'ddd-dt-colvis', 'https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js', [ 'ddd-dt-buttons' ], '2.4.1', true );

        wp_enqueue_script( 'ddd-atum-logs', DDD_ATUM_READER_URL . 'assets/js/ddd-atum-logs.js', [ 'ddd-dt-core', 'ddd-dt-fixedheader', 'ddd-dt-buttons', 'ddd-dt-html5', 'ddd-dt-print', 'ddd-dt-colvis' ], DDD_ATUM_READER_VERSION, true );

        // Localize data for JS (current user id for per-user sticky prefs)
        wp_localize_script( 'ddd-atum-logs', 'DDD_ATUM_READER_DATA', [
            'user_id' => get_current_user_id(),
        ] );
    }

    public static function render_page() {
        // Header, Content, Footer are separated for clarity.
        require_once DDD_ATUM_READER_PATH . 'includes/admin/section/section-header.php';
        require_once DDD_ATUM_READER_PATH . 'includes/admin/section/section-content.php';
        require_once DDD_ATUM_READER_PATH . 'includes/admin/section/section-footer.php';
    }
}
