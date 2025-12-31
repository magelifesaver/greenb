<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/aaa-oc-core-board-frontend.php
 * Purpose: Front-end loader for the Workflow Board – renders the board via shortcode
 *          and enqueues the same assets as the admin board.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Idempotent guard */
if ( defined( 'AAA_OC_CORE_BOARD_FRONTEND_LOADER_READY' ) ) return;
define( 'AAA_OC_CORE_BOARD_FRONTEND_LOADER_READY', true );

/** Per-file debug toggle (dev default: true) */
const DEBUG_THIS_FILE = true;

/**
 * Register the shortcode [aaa_oc_workflow_board], which outputs the board shells.
 */
add_action( 'init', function() {
    add_shortcode( 'aaa_oc_workflow_board', function( $atts = [], $content = null ) {
        ob_start();
        ?>
        <div id="aaa-oc-board-columns" style="display:flex; gap:1rem; overflow-x:auto; margin-top:1rem;">
            <!-- Orders inserted via AJAX -->
        </div>
        <div id="aaa-oc-modal"
             style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%;
                    background:rgba(0,0,0,0.7);" data-overlay="true">
            <div id="aaa-oc-modal-content"
                 style="background:#fff; width:1000px; max-width:95%; margin:50px auto; padding:10px; position:relative; border-radius:10px;">
                <button id="aaa-payrec-modal-close" style="position:absolute; top:10px; right:10px;">X</button>
                <!-- Card details injected dynamically -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    } );
} );

/**
 * Enqueue board CSS/JS when the shortcode is present on a page.
 * Uses the same assets as the admin loader (located under /assets).
 */
add_action( 'wp_enqueue_scripts', function() {
    global $post;
    if ( ! is_object( $post ) ) return;
    if ( strpos( $post->post_content ?? '', '[aaa_oc_workflow_board' ) === false ) return;

    // Assets are under the module’s /assets folder (not /hooks).
    $mod_url = trailingslashit( plugin_dir_url( __FILE__ ) . 'assets' );
    $mod_fs  = trailingslashit( dirname( __FILE__ ) . '/assets' );

    $ver = function( $rel ) use ( $mod_fs ) {
        $file = $mod_fs . ltrim( $rel, '/' );
        return file_exists( $file )
            ? (string) filemtime( $file )
            : ( defined( 'AAA_OC_VERSION' ) ? AAA_OC_VERSION : '1.0.0' );
    };

    // CSS
    wp_enqueue_style( 'aaa-oc-board-css',        $mod_url . 'css/board.css',           [], $ver( 'css/board.css' ) );
    wp_enqueue_style( 'aaa-oc-board-header-fix', $mod_url . 'css/board-header-fix.css',[], $ver( 'css/board-header-fix.css' ) );

    // JS – match dependencies used by the admin board
    wp_enqueue_script( 'aaa-oc-board-toolbar',        $mod_url . 'js/board-toolbar.js',        [ 'jquery' ], $ver( 'js/board-toolbar.js' ), true );
    wp_enqueue_script( 'aaa-oc-board-actions',        $mod_url . 'js/board-actions.js',        [ 'jquery', 'aaa-oc-board-toolbar' ], $ver( 'js/board-actions.js' ), true );
    wp_enqueue_script( 'aaa-oc-board-filters',        $mod_url . 'js/board-filters.js',        [ 'jquery', 'aaa-oc-board-toolbar' ], $ver( 'js/board-filters.js' ), true );
    wp_enqueue_script( 'aaa-oc-board-hide-completed', $mod_url . 'js/board-hide-completed.js', [ 'jquery', 'aaa-oc-board-toolbar' ], $ver( 'js/board-hide-completed.js' ), true );

    // Board runtime
    wp_enqueue_script( 'aaa-oc-board',            $mod_url . 'js/board.js',            [ 'jquery' ], $ver( 'js/board.js' ), true );
    wp_enqueue_script( 'aaa-oc-board-print',      $mod_url . 'js/board-print.js',      [ 'jquery' ], $ver( 'js/board-print.js' ), true );
    wp_enqueue_script( 'aaa-oc-board-header-fix-js', $mod_url . 'js/board-header-fix.js', [ 'jquery', 'aaa-oc-board-toolbar', 'aaa-oc-board-filters', 'aaa-oc-board-actions' ], $ver( 'js/board-header-fix.js' ), true );
    wp_enqueue_script( 'aaa-oc-board-admin-notes',  $mod_url . 'js/board-admin-notes.js', [ 'jquery' ], $ver( 'js/board-admin-notes.js' ), true );

    // Localize runtime data to match the admin environment
    wp_localize_script( 'aaa-oc-board', 'AAA_OC_Vars', [
        'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
        'nonce'         => wp_create_nonce( 'aaa_oc_ajax_nonce' ),
        'showCountdown' => (int) get_option( 'aaa_oc_show_countdown_bar', 0 ),
        'pollInterval'  => 60,
        'adminEditBase' => admin_url( 'post.php?action=edit&post=' ),
    ] );

    if ( DEBUG_THIS_FILE && function_exists('aaa_oc_log') ) {
        aaa_oc_log( '[BoardFrontEnd] enqueued board assets.' );
    }
} );

/**
 * Relax capability checks for aaa_oc_* AJAX actions (dev/staging only).
 * This lets the board fetch data without requiring a logged-in user.
 */
add_filter( 'user_has_cap', function( $allcaps, $caps, $args, $user ) {
    if ( ! empty( $_REQUEST['action'] ) && strpos( (string) $_REQUEST['action'], 'aaa_oc_' ) === 0 ) {
        $allcaps['manage_woocommerce'] = true;
        $allcaps['manage_options']    = true;
    }
    return $allcaps;
}, 10, 4 );
