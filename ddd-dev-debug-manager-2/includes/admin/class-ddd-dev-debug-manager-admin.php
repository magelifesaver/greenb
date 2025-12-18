<?php
/**
 * Admin page handler for the DDD Dev Debug Manager plugin.
 *
 * Registers a page under Tools and outputs the HTML interface where
 * administrators can view and tail the debug.log file, clear the log,
 * download it as a zipped archive, and manage snapshots. File operations
 * and AJAX handlers live in DDD_Dev_Debug_Manager_File and
 * DDD_Dev_Debug_Manager_Snapshot.
 *
 * @package DDD_Dev_Debug_Manager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DDD_Dev_Debug_Manager_Admin {
    /**
     * Instantiate the admin handler and load dependencies.
     */
    public function __construct() {
        // Load controllers for file operations and snapshots.
        require_once DDD_DEBUG_MANAGER_DIR . 'includes/admin/class-ddd-dev-debug-manager-file.php';
        require_once DDD_DEBUG_MANAGER_DIR . 'includes/admin/class-ddd-dev-debug-manager-snapshot.php';
        new DDD_Dev_Debug_Manager_File();
        new DDD_Dev_Debug_Manager_Snapshot();
        add_action( 'admin_menu', array( $this, 'register_tools_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Register our management page under Tools.
     */
    public function register_tools_page() {
        add_management_page(
            __( 'Dev Debug Manager', 'ddd-dev-debug-manager' ),
            __( 'Dev Debug Manager', 'ddd-dev-debug-manager' ),
            'manage_options',
            'ddd-dev-debug-manager',
            array( $this, 'render_page' )
        );
    }

    /**
     * Enqueue JavaScript and styles on our admin page.
     *
     * @param string $hook Current admin page hook suffix.
     */
    public function enqueue_assets( $hook ) {
        if ( 'tools_page_ddd-dev-debug-manager' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'ddd-debug-manager-style', DDD_DEBUG_MANAGER_URL . 'assets/css/debug-manager.css', array(), DDD_DEBUG_MANAGER_VERSION );
        wp_enqueue_script( 'ddd-debug-manager-js', DDD_DEBUG_MANAGER_URL . 'assets/js/debug-manager.js', array( 'jquery' ), DDD_DEBUG_MANAGER_VERSION, true );
        // Localize script with dynamic values. Provide the AJAX URL and nonces.
        wp_localize_script( 'ddd-debug-manager-js', 'DDD_Debug_Manager', array(
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce_tail' => wp_create_nonce( 'ddd_debug_manager_tail' ),
            'start_text' => __( 'Start Live Tail', 'ddd-dev-debug-manager' ),
            'stop_text'  => __( 'Stop Live Tail', 'ddd-dev-debug-manager' ),
        ) );
    }

    /**
     * Render the page content for the debug manager.
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ddd-dev-debug-manager' ) );
        }
        // Determine the log path and download URL using the static method.
        $log_file = DDD_Dev_Debug_Manager_File::get_log_path();
        $exists   = is_readable( $log_file );
        $download_url = '';
        if ( $exists ) {
            $download_url = wp_nonce_url( admin_url( 'admin-ajax.php?action=ddd_debug_manager_download' ), 'ddd_debug_manager_download', 'nonce' );
        }
        // Generate URLs and nonces for snapshot and log clearing.
        $snapshot_nonce       = wp_create_nonce( 'ddd_debug_manager_snapshot' );
        $clear_snapshot_nonce = wp_create_nonce( 'ddd_debug_manager_clear_snapshot' );
        $clear_log_nonce      = wp_create_nonce( 'ddd_debug_manager_clear_log' );
        $base_snapshot_url    = admin_url( 'admin-ajax.php?action=ddd_debug_manager_snapshot' );
        $snapshot_raw_url     = add_query_arg( array( 'nonce' => $snapshot_nonce, 'exclude_duplicates' => 0 ), $base_snapshot_url );
        $snapshot_unique_url  = add_query_arg( array( 'nonce' => $snapshot_nonce, 'exclude_duplicates' => 1 ), $base_snapshot_url );
        $clear_snapshot_url   = add_query_arg( array( 'nonce' => $clear_snapshot_nonce ), admin_url( 'admin-ajax.php?action=ddd_debug_manager_clear_snapshot' ) );
        $clear_log_url        = add_query_arg( array( 'nonce' => $clear_log_nonce ), admin_url( 'admin-ajax.php?action=ddd_debug_manager_clear_log' ) );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Dev Debug Manager', 'ddd-dev-debug-manager' ); ?></h1>
            <p><?php esc_html_e( 'View your debug.log file below. Use the live tail button to watch new entries appear in real‑time. Use the Download button to obtain a zip archive split into smaller parts.', 'ddd-dev-debug-manager' ); ?></p>
            <?php if ( ! $exists ) : ?>
                <div class="notice notice-error">
                    <p><?php esc_html_e( 'The debug.log file could not be found or is not readable. Ensure that WP_DEBUG_LOG is enabled and that the file is in the wp-content directory or at the path specified in wp-config.php.', 'ddd-dev-debug-manager' ); ?></p>
                </div>
            <?php else : ?>
                <div class="ddd-debug-actions">
                    <!-- Tail, duplicate filter, clear log, and zip download -->
                    <button id="ddd-debug-tail-btn" class="button button-secondary" type="button"><?php echo esc_html( __( 'Start Live Tail', 'ddd-dev-debug-manager' ) ); ?></button>
                    <label for="ddd-tail-unique" style="margin-left:10px;">
                        <input type="checkbox" id="ddd-tail-unique" /> <?php esc_html_e( 'Filter Duplicates', 'ddd-dev-debug-manager' ); ?>
                    </label>
                    <button id="ddd-clear-log-btn" class="button button-secondary" type="button" data-clear-log-url="<?php echo esc_url( $clear_log_url ); ?>" style="margin-left:10px;"><?php esc_html_e( 'Clear Log', 'ddd-dev-debug-manager' ); ?></button>
                    <span id="ddd-clear-log-message" style="margin-left:10px;"></span>
                    <?php if ( $download_url ) : ?>
                        <a href="<?php echo esc_url( $download_url ); ?>" class="button button-primary" id="ddd-debug-download-btn" style="margin-left:10px;"><?php echo esc_html( __( 'Download Logs (zip)', 'ddd-dev-debug-manager' ) ); ?></a>
                    <?php endif; ?>
                </div>
                <pre id="ddd-debug-content"></pre>
                <?php
                // Snapshot buttons and cache controls.
                ?>
                <div class="ddd-debug-snapshot-actions" style="margin-top:1em;">
                    <strong><?php esc_html_e( 'Snapshot Tools:', 'ddd-dev-debug-manager' ); ?></strong><br />
                    <a href="<?php echo esc_url( $snapshot_raw_url ); ?>" class="button" style="margin-right:5px;"><?php esc_html_e( 'Download Snapshot', 'ddd-dev-debug-manager' ); ?></a>
                    <a href="<?php echo esc_url( $snapshot_unique_url ); ?>" class="button" style="margin-right:5px;"><?php esc_html_e( 'Snapshot (no duplicates)', 'ddd-dev-debug-manager' ); ?></a>
                    <button type="button" id="ddd-clear-snapshot-btn" class="button" data-clear-url="<?php echo esc_url( $clear_snapshot_url ); ?>" style="margin-right:5px;"><?php esc_html_e( 'Clear Snapshot Cache', 'ddd-dev-debug-manager' ); ?></button>
                    <span id="ddd-clear-snapshot-message"></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}