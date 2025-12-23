<?php
/**
 * Admin page for viewing and managing the address verification queue.
 *
 * This component provides a screen under Settings where administrators can
 * inspect queued, successful and failed verification jobs. It displays
 * basic statistics, offers a button to run one batch or all jobs, and
 * includes an AJAX refresh of the table. Clearing the queue is also
 * supported.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_BulkVerify_AdminQueue {
    /**
     * Initialise the menu and AJAX handler.
     */
    public static function init() : void {
        add_action( 'admin_menu', [ __CLASS__, 'register_page' ] );
        add_action( 'wp_ajax_aaa_am_queue_table', [ __CLASS__, 'ajax_render_queue_table' ] );
    }

    /**
     * Register an options page under Settings → Address Verification Queue.
     */
    public static function register_page() : void {
        add_options_page(
            __( 'Address Verification Queue', 'aaa-address-manager' ),
            __( 'Address Verification Queue', 'aaa-address-manager' ),
            'manage_options',
            'aaa-am-queue',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Render the queue page. Shows stats, run buttons and a table of jobs.
     */
    public static function render_page() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        AAA_BulkVerify_Core::ensure_table();
        global $wpdb;
        $table = $wpdb->prefix . AAA_BulkVerify_Core::TABLE;
        // Handle clear queue action via POST.
        if ( isset( $_POST['aaa_am_clear_queue'] ) && check_admin_referer( 'aaa_am_clear_queue' ) ) {
            $wpdb->query( "TRUNCATE TABLE {$table}" );
            echo '<div class="updated"><p>' . esc_html__( 'Queue cleared.', 'aaa-address-manager' ) . '</p></div>';
        }
        // Show notice if failures were retried.
        if ( isset( $_GET['retried'] ) ) {
            echo '<div class="updated"><p>' . esc_html__( 'Failed jobs have been requeued.', 'aaa-address-manager' ) . '</p></div>';
        }
        // Calculate stats.
        $counts = [
            'total'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
            'queued'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'queued'" ),
            'success' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'success'" ),
            'failed'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'failed'" ),
        ];
        // Fetch latest 200 jobs for display.
        $jobs = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 200" );
        echo '<div class="wrap"><h1>' . esc_html__( 'Address Verification Queue', 'aaa-address-manager' ) . '</h1>';
        echo '<p><strong>' . esc_html__( 'Total', 'aaa-address-manager' ) . ':</strong> ' . esc_html( $counts['total'] ) . ' | ';
        echo '<span style="color:orange"><strong>' . esc_html__( 'Queued', 'aaa-address-manager' ) . ':</strong> ' . esc_html( $counts['queued'] ) . '</span> | ';
        echo '<span style="color:green"><strong>' . esc_html__( 'Success', 'aaa-address-manager' ) . ':</strong> ' . esc_html( $counts['success'] ) . '</span> | ';
        echo '<span style="color:red"><strong>' . esc_html__( 'Failed', 'aaa-address-manager' ) . ':</strong> ' . esc_html( $counts['failed'] ) . '</span></p>';
        // Buttons: Run now, Run all, Clear.
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:10px;">';
        wp_nonce_field( 'aaa_am_run_now' );
        echo '<input type="hidden" name="action" value="aaa_am_run_now">';
        echo '<button class="button button-primary">' . esc_html__( 'Run Now (one batch)', 'aaa-address-manager' ) . '</button>';
        echo '</form>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:10px;">';
        wp_nonce_field( 'aaa_am_run_all' );
        echo '<input type="hidden" name="action" value="aaa_am_run_all">';
        echo '<button class="button button-primary">' . esc_html__( 'Run All (full queue)', 'aaa-address-manager' ) . '</button>';
        echo '</form>';
        // Button to retry all failed jobs. Submits to our retry_failed handler.
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:10px;">';
        wp_nonce_field( 'aaa_am_retry_failed' );
        echo '<input type="hidden" name="action" value="aaa_am_retry_failed">';
        echo '<button class="button">' . esc_html__( 'Retry Failed', 'aaa-address-manager' ) . '</button>';
        echo '</form>';
        echo '<form method="post" style="display:inline-block;">';
        wp_nonce_field( 'aaa_am_clear_queue' );
        $clear_msg = esc_js( __( 'Clear all jobs from the queue?', 'aaa-address-manager' ) );
        echo '<button type="submit" name="aaa_am_clear_queue" class="button button-secondary" onclick="return confirm(\'' . $clear_msg . '\')">' . esc_html__( 'Clear Queue', 'aaa-address-manager' ) . '</button>';
        echo '</form>';
        // Jobs table.
        echo '<br><br><table class="widefat striped"><thead><tr>';
        echo '<th>ID</th><th>' . esc_html__( 'User', 'aaa-address-manager' ) . '</th><th>' . esc_html__( 'Scope', 'aaa-address-manager' ) . '</th><th>' . esc_html__( 'Status', 'aaa-address-manager' ) . '</th><th>' . esc_html__( 'Message', 'aaa-address-manager' ) . '</th><th>' . esc_html__( 'Created', 'aaa-address-manager' ) . '</th><th>' . esc_html__( 'Processed', 'aaa-address-manager' ) . '</th>';
        echo '</tr></thead><tbody id="aaa-am-queue-body">';
        self::render_queue_rows( $jobs );
        echo '</tbody></table></div>';
        // Auto refresh via AJAX every 10 seconds.
        echo '<script>(function($){function refresh(){ $.post(ajaxurl,{action:"aaa_am_queue_table"},function(resp){ if(resp&&resp.success&&resp.data){ $("#aaa-am-queue-body").html(resp.data.html); } });} setInterval(refresh,10000); })(jQuery);</script>';
    }

    /**
     * Output table rows for the queue jobs.
     *
     * @param array $jobs Rows from the jobs table.
     */
    private static function render_queue_rows( array $jobs ) : void {
        if ( empty( $jobs ) ) {
            echo '<tr><td colspan="7">' . esc_html__( 'No jobs.', 'aaa-address-manager' ) . '</td></tr>';
            return;
        }
        foreach ( $jobs as $job ) {
            $row_style = '';
            if ( 'success' === $job->status ) {
                $row_style = ' style="background-color:#e6ffe6;"';
            } elseif ( 'failed' === $job->status ) {
                $row_style = ' style="background-color:#ffe6e6;"';
            }
            printf('<tr%s><td>%d</td><td><a href="%s">%d</a></td><td>%s</td><td>%s</td><td style="max-width:420px;">%s</td><td>%s</td><td>%s</td></tr>',
                $row_style,
                (int) $job->id,
                esc_url( get_edit_user_link( (int) $job->user_id ) ),
                (int) $job->user_id,
                esc_html( $job->scope ),
                esc_html( $job->status ),
                esc_html( (string) $job->message ),
                esc_html( $job->created_at ),
                esc_html( (string) $job->processed_at )
            );
        }
    }

    /**
     * AJAX handler to refresh the queue table body. Returns HTML via JSON.
     */
    public static function ajax_render_queue_table() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }
        AAA_BulkVerify_Core::ensure_table();
        global $wpdb;
        $table = $wpdb->prefix . AAA_BulkVerify_Core::TABLE;
        $jobs  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 200" );
        ob_start();
        self::render_queue_rows( $jobs );
        $html = ob_get_clean();
        wp_send_json_success( [ 'html' => $html ] );
    }
}