<?php
/**
 * Class: AAA_OC_Indexer_Settings_Page
 * File Path: /aaa-order-workflow/includes/indexers/class-aaa-oc-indexer-settings-page.php
 * Purpose: Adds admin settings page for configuring indexing parameters,
 *          running manual reindex, and clearing the index table.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Indexer_Settings_Page {

    private static string $table_name;

public function __construct() {
	global $wpdb;
	self::$table_name = $wpdb->prefix . 'aaa_oc_order_index';

	// Use a slightly later priority so it appears AFTER the Workflow Board
	add_action( 'admin_menu', [ $this, 'add_indexing_submenu' ], 20 );

	add_action( 'admin_init', [ $this, 'register_indexing_settings' ] );
	add_action( 'wp_ajax_aaa_oc_run_indexing', [ $this, 'run_indexing' ] );
	add_action( 'wp_ajax_aaa_oc_clear_index', [ $this, 'clear_index' ] );
	add_action( 'admin_notices', [ $this, 'display_admin_notices' ] );
	add_action( 'aaa_oc_scheduled_cleanup', [ $this, 'scheduled_cleanup' ] );
}

/** Add submenu: Order Indexing */
public function add_indexing_submenu() {
	add_submenu_page(
		'aaa-oc-workflow-board',
		'Workflow Order Indexing Settings',
		'WP Indexing',
		'manage_options',
		'aaa-oc-indexing-settings',
		[ $this, 'render_indexing_settings' ]
	);
}

    /** Register settings. */
    public function register_indexing_settings() {}

    /** Render settings page. */
    public function render_indexing_settings() {

        if ( isset( $_POST['aaa_oc_indexing_save_submit'] ) && check_admin_referer( 'aaa_oc_indexing_save' ) ) {
            $index_days   = isset( $_POST['aaa_oc_index_days'] ) ? (int) $_POST['aaa_oc_index_days'] : 0;
            $cleanup_days = isset( $_POST['aaa_oc_cleanup_days'] ) ? (int) $_POST['aaa_oc_cleanup_days'] : 7;

            aaa_oc_set_option( 'aaa_oc_index_days', $index_days, 'indexer' );
            aaa_oc_set_option( 'aaa_oc_cleanup_days', $cleanup_days, 'indexer' );

            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Order Indexing Settings</h1>
            <form method="post">
                <?php wp_nonce_field( 'aaa_oc_indexing_save' ); ?>
                <table class="form-table">
                    <tr>
                        <th>Index Orders from Last (X) Days</th>
                        <td>
                            <input type="number" name="aaa_oc_index_days"
                                value="<?php echo esc_attr( aaa_oc_get_option( 'aaa_oc_index_days', 'indexer', 0 ) ); ?>" />
                            <p class="description">0 = Today only, 1 = Yesterday+Today, etc.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Auto-Cleanup Every (X) Days</th>
                        <td>
                            <input type="number" name="aaa_oc_cleanup_days"
                                value="<?php echo esc_attr( aaa_oc_get_option( 'aaa_oc_cleanup_days', 'indexer', 7 ) ); ?>" />
                            <p class="description">Removes older index rows beyond X days.</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="aaa_oc_indexing_save_submit" class="button button-primary">
                        Save Settings
                    </button>
                </p>
            </form>
            <hr/>
            <h2>Manual Indexing Actions</h2>
            <button class="button button-primary aaa-oc-run-index">Run Indexing Now</button>
            <button class="button button-secondary aaa-oc-clear-index">Clear Index</button>
            <script>
            (function($){
                $('.aaa-oc-run-index').on('click', function(){
                    if (!confirm("Run order indexing now?")) return;
                    $.post(ajaxurl, { action: 'aaa_oc_run_indexing' }, function(resp){
                        alert(resp.data.message);
                        window.location.reload();
                    });
                });
                $('.aaa-oc-clear-index').on('click', function(){
                    if (!confirm("Are you sure you want to clear the entire index table?")) return;
                    $.post(ajaxurl, { action: 'aaa_oc_clear_index' }, function(resp){
                        alert(resp.data.message);
                        window.location.reload();
                    });
                });
            })(jQuery);
            </script>
        </div>
        <?php
    }

    public function run_indexing() {
        $days = (int) aaa_oc_get_option('aaa_oc_index_days', 'indexer', 0);

        try {
            $wp_timezone = new DateTimeZone( get_option('timezone_string') ?: 'UTC' );
            $start = new DateTime('today', $wp_timezone);
            if ($days > 0) {
                $start->modify("-$days days");
            }
            $date_start = $start->format('Y-m-d H:i:s');
            $date_end   = (new DateTime('now', $wp_timezone))->format('Y-m-d H:i:s');

            aaa_oc_log("[Indexing] Manual start. Range: $date_start → $date_end");

            $orders = wc_get_orders([
                'limit'        => -1,
                'orderby'      => 'date',
                'order'        => 'DESC',
                'date_created' => $date_start . '...' . $date_end,
            ]);

            $count = 0;
            if ( $orders ) {
                $indexer = new AAA_OC_Indexing();
                foreach ( $orders as $ord ) {
                    $oid = $ord->get_id();
                    if ( is_a( $ord, 'WC_Order_Refund' ) ) {
                        aaa_oc_log("[Indexing] Skipped refund #$oid.");
                        continue;
                    }
                    if ( $indexer->index_order( $oid ) ) {
                        $count++;
                    }
                }
            }

            $msg = "Indexing complete. $count orders processed.";
            aaa_oc_log("[Indexing] $msg");
            aaa_oc_set_option('aaa_oc_indexing_notice', $msg, 'indexer');
            wp_send_json_success(['message' => $msg]);
        } catch (\Throwable $e) {
            $err = "Indexing failed: " . $e->getMessage();
            aaa_oc_log("[Indexing] $err");
            aaa_oc_set_option('aaa_oc_indexing_notice', $err, 'indexer');
            wp_send_json_error(['message' => $err]);
        }
    }

    public function clear_index() {
        global $wpdb;
        try {
            $res = $wpdb->query("TRUNCATE TABLE " . self::$table_name);
            if ( false === $res ) {
                aaa_oc_log("[Indexing] ❌ TRUNCATE failed: " . $wpdb->last_error);
                aaa_oc_set_option('aaa_oc_indexing_notice', 'Error clearing index table.', 'indexer');
                wp_send_json_error(['message' => 'Error clearing index.']);
            } else {
                aaa_oc_log("[Indexing] ✅ Index table truncated.");
                aaa_oc_set_option('aaa_oc_indexing_notice', 'Order index table cleared.', 'indexer');
                wp_send_json_success(['message' => 'Order index table cleared.']);
            }
        } catch (\Throwable $e) {
            $err = "Clear failed: " . $e->getMessage();
            aaa_oc_log("[Indexing] $err");
            aaa_oc_set_option('aaa_oc_indexing_notice', $err, 'indexer');
            wp_send_json_error(['message' => $err]);
        }
    }

    public function display_admin_notices() {
        $notice = aaa_oc_get_option('aaa_oc_indexing_notice', 'indexer', '');
        if ( ! empty( $notice ) ) {
            echo '<div class="notice notice-success" style="padding:6px 10px;"><p>' . esc_html( $notice ) . '</p></div>';
            aaa_oc_delete_option('aaa_oc_indexing_notice', 'indexer');
        }
    }

    public function scheduled_cleanup() {
        global $wpdb;
        $days = (int) aaa_oc_get_option('aaa_oc_cleanup_days', 'indexer', 7);
        $limit_dt = gmdate('Y-m-d H:i:s', strtotime("-$days days"));
        aaa_oc_log("[Indexing] 🧹 scheduled_cleanup removing records older than $limit_dt");
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM " . self::$table_name . " WHERE time_published < %s",
            $limit_dt
        ));
        aaa_oc_set_option('aaa_oc_indexing_notice', "Removed orders older than $days days.", 'indexer');
    }
}
