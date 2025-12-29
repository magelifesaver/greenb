<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-workflow-ai-reports/admin/admin-page.php
 * Description: Main admin interface for AAA Workflow AI Reports.
 *              Renders tabbed settings sections for OpenAI, Permissions, and Reports.
 * Version: 2.0.0
 * Updated: 2025-12-02
 * Author: AAA Workflow DevOps
 * ============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --------------------------------------------------------------------------
 * 🧩 Render Main Admin Page
 * --------------------------------------------------------------------------
 */
function aaa_wf_ai_admin_page_render() {

        // --- Tab definitions ---
        $tabs = [
                'openai'      => __( 'OpenAI Settings', 'aaa-workflow-ai-reports' ),
                'permissions' => __( 'Permissions', 'aaa-workflow-ai-reports' ),
                'reports'     => __( 'Reports', 'aaa-workflow-ai-reports' ),
                'debug'       => __( 'Debug', 'aaa-workflow-ai-reports' ),
        ];

        // --- Determine current tab ---
        $current_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs )
                ? sanitize_key( $_GET['tab'] )
                : 'openai';

        aaa_wf_ai_debug( "Rendering admin page (tab: {$current_tab})", basename( __FILE__ ), 'admin' );

        ?>
        <div class="wrap aaa-wf-ai-reports-wrap">
                <h1>
                        <span class="dashicons dashicons-chart-bar" style="color:#2271b1;margin-right:6px;"></span>
                        <?php esc_html_e( 'AAA Workflow AI Reports', 'aaa-workflow-ai-reports' ); ?>
                </h1>

                <!-- 🔗 Tab Navigation -->
                <nav class="nav-tab-wrapper">
                        <?php foreach ( $tabs as $tab_id => $tab_name ) : ?>
                                <?php $active = ( $current_tab === $tab_id ) ? 'nav-tab-active' : ''; ?>
                                <a href="<?php echo esc_url( admin_url( "admin.php?page=aaa-workflow-ai-reports&tab={$tab_id}" ) ); ?>"
                                   class="nav-tab <?php echo esc_attr( $active ); ?>">
                                        <?php echo esc_html( $tab_name ); ?>
                                </a>
                        <?php endforeach; ?>
                </nav>

                <!-- 🧱 Tab Content -->
                <div class="aaa-wf-ai-tab-content" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-top:0;">
                        <?php
                        $tab_file = AAA_WF_AI_DIR . 'admin/tabs/settings-' . $current_tab . '.php';

                        if ( file_exists( $tab_file ) ) {
                                require $tab_file;
                                aaa_wf_ai_debug( "Loaded tab file: {$tab_file}", basename( __FILE__ ), 'admin' );
                        } else {
                                echo '<p>' . esc_html__( 'The selected settings tab could not be found.', 'aaa-workflow-ai-reports' ) . '</p>';
                                aaa_wf_ai_debug( "Missing tab file for {$current_tab}", basename( __FILE__ ), 'admin' );
                        }
                        ?>
                </div>
        </div>
        <?php
}