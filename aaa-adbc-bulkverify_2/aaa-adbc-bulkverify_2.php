<?php
/**
 * File: /plugins/aaa-adbc-bulkverify-2/aaa-adbc-bulkverify_2.php
 * Plugin Name: AAA ADBC Bulk Verify v2
 * Description: Background bulk verification of billing/shipping addresses with Google Geocode API. Includes audit DB, queue management (Run Now + Clear), mass submission (filters, sorting, per-page, mass exclude), AJAX refresh, failure & exclude flags, profile links, and “skip users already in queue”.
 * Version: 4.0.0
 * Author: Workflow Delivery
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_ADBC_BulkVerify_V2 {
    /* ───────── Config ───────── */
    const DEBUG_THIS_FILE    = true;                           // requires WP_DEBUG true
    const API_KEY            = 'AIzaSyDU1COZWrht9L0tpy3hK0Fzz2OaCNyjGy4'; // testing only
    const CRON_HOOK          = 'aaa_adbc2_process_queue';      // v2-only hook
    const TABLE              = 'aaa_adbc2_jobs';               // v2-only table
    const BATCH_LIMIT        = 50;                             // jobs per batch run
    const RESCHEDULE_SECONDS = 15;                             // next batch delay

    /* ───────── Bootstrap ───────── */
    public static function init() {
        add_action( 'plugins_loaded', [ __CLASS__, 'ensure_table' ] );
        register_activation_hook( __FILE__, [ __CLASS__, 'ensure_table' ] );

        // Users → Bulk actions
        add_filter( 'bulk_actions-users', [ __CLASS__, 'register_actions' ] );
        add_filter( 'handle_bulk_actions-users', [ __CLASS__, 'handle_action' ], 10, 3 );

        // Processor (cron)
        add_action( self::CRON_HOOK, [ __CLASS__, 'process_batch' ] );

        // Admin pages
        add_action( 'admin_menu', [ __CLASS__, 'register_queue_page' ] );
        add_action( 'admin_menu', [ __CLASS__, 'register_mass_page' ] );

        // AJAX refresh (queue table body only)
        add_action( 'wp_ajax_aaa_adbc2_queue_table', [ __CLASS__, 'ajax_render_queue_table' ] );

        // Admin-post actions
        add_action( 'admin_post_aaa_adbc2_run_now',       [ __CLASS__, 'handle_run_now' ] );
        add_action( 'admin_post_aaa_adbc2_exclude_user',  [ __CLASS__, 'handle_exclude_user' ] );

        // Plugin list quick links
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [ __CLASS__, 'plugin_links' ] );
    }

    /* ───────── Utils ───────── */
    private static function log( string $msg, array $ctx = [] ) : void {
        if ( ! self::DEBUG_THIS_FILE || ! defined('WP_DEBUG') || ! WP_DEBUG ) return;
        $line = '[AAA-ADBC-BulkVerify-v2] ' . $msg;
        if ( $ctx ) $line .= ' | ' . wp_json_encode( $ctx );
        error_log( $line ); // phpcs:ignore
    }

    public static function ensure_table() {
        global $wpdb;
        $table   = $wpdb->prefix . self::TABLE;
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            scope varchar(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'queued',
            message text NULL,
            created_at datetime NOT NULL,
            processed_at datetime NULL,
            PRIMARY KEY  (id),
            KEY idx_status (status),
            KEY idx_created (created_at)
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /* ───────── Users bulk actions ───────── */
    public static function register_actions( $actions ) {
        $actions['aaa_adbc2_verify_billing']  = 'Verify Billing Addresses (v2)';
        $actions['aaa_adbc2_verify_shipping'] = 'Verify Shipping Addresses (v2)';
        $actions['aaa_adbc2_verify_both']     = 'Verify Billing + Shipping (v2)';
        return $actions;
    }

    public static function handle_action( $redirect, $action, $user_ids ) {
        if ( ! in_array( $action, [ 'aaa_adbc2_verify_billing', 'aaa_adbc2_verify_shipping', 'aaa_adbc2_verify_both' ], true ) ) {
            return $redirect;
        }
        self::ensure_table();
        global $wpdb;
        $scope = str_replace( 'aaa_adbc2_verify_', '', $action );
        $table = $wpdb->prefix . self::TABLE;

        $count = 0;
        foreach ( array_map( 'intval', (array) $user_ids ) as $uid ) {
            $ok = $wpdb->insert( $table, [
                'user_id'    => $uid,
                'scope'      => $scope,
                'status'     => 'queued',
                'created_at' => current_time('mysql'),
            ] );
            if ( $ok ) $count++;
        }
        self::log('bulk queued', ['count'=>$count,'scope'=>$scope]);

        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_single_event( time() + 5, self::CRON_HOOK );
        }
        return $redirect;
    }

    /* ───────── Manual Run Now ───────── */
    public static function handle_run_now() {
        if ( ! current_user_can('manage_options') ) wp_die('Permission denied');
        check_admin_referer('aaa_adbc2_run_now');
        self::process_batch();
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_single_event( time() + self::RESCHEDULE_SECONDS, self::CRON_HOOK );
        }
        wp_safe_redirect( admin_url('options-general.php?page=aaa-adbc2-queue&ran=1') );
        exit;
    }

    /* ───────── Exclude user (single) ───────── */
    public static function handle_exclude_user() {
        if ( ! current_user_can('manage_options') ) wp_die('Permission denied');
        $uid = (int)($_GET['uid'] ?? 0);
        check_admin_referer('aaa_adbc2_exclude_'.$uid);
        if ( $uid ) update_user_meta( $uid, '_aaa_adbc2_excluded', 'yes' );
        wp_safe_redirect( admin_url('tools.php?page=aaa-adbc2-mass&excluded=1') );
        exit;
    }

    /* ───────── Processor ───────── */
    public static function process_batch() {
        self::ensure_table();
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        $jobs = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table WHERE status='queued' ORDER BY id ASC LIMIT %d", self::BATCH_LIMIT)
        );
        self::log('process_batch', ['found'=>is_array($jobs)?count($jobs):'ERR']);
        if ( empty($jobs) ) return;

        foreach ( $jobs as $job ) {
            $scopes = ( $job->scope === 'both' ) ? ['billing','shipping'] : [ $job->scope ];
            $status = 'success'; $msg = '';
            try {
                foreach ( $scopes as $scope ) {
                    self::verify_user_scope( (int)$job->user_id, $scope );
                }
            } catch ( \Throwable $e ) {
                $status = 'failed';
                $msg    = $e->getMessage();
            }
            $wpdb->update( $table, [
                'status' => $status,
                'message' => $msg,
                'processed_at' => current_time('mysql'),
            ], [ 'id' => (int)$job->id ] );
        }

        $remain = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='queued'");
        self::log('batch_done', ['remaining'=>$remain]);
        if ( $remain > 0 ) {
            wp_schedule_single_event( time() + self::RESCHEDULE_SECONDS, self::CRON_HOOK );
        }
    }

    private static function verify_user_scope( int $uid, string $scope ) {
        $addr = [
            get_user_meta( $uid, "{$scope}_address_1", true ),
            get_user_meta( $uid, "{$scope}_address_2", true ),
            get_user_meta( $uid, "{$scope}_city",      true ),
            get_user_meta( $uid, "{$scope}_state",     true ),
            get_user_meta( $uid, "{$scope}_postcode",  true ),
            get_user_meta( $uid, "{$scope}_country",   true ),
        ];
        $addr_str = trim( implode(', ', array_filter( array_map('trim',$addr) ) ) );
        if ( $addr_str === '' ) {
            update_user_meta( $uid, '_aaa_adbc2_verify_failed', 'yes' );
            throw new \Exception("No {$scope} address for user {$uid}");
        }

        [$lat,$lng,$components,$status] = self::geocode($addr_str);
        if ( $lat && $lng ) {
            update_user_meta( $uid, "_wc_{$scope}/aaa-delivery-blocks/latitude",  $lat );
            update_user_meta( $uid, "_wc_{$scope}/aaa-delivery-blocks/longitude", $lng );
            update_user_meta( $uid, "_wc_{$scope}/aaa-delivery-blocks/coords-verified", 'yes' );
            delete_user_meta( $uid, '_aaa_adbc2_verify_failed' );
        } else {
            update_user_meta( $uid, "_wc_{$scope}/aaa-delivery-blocks/coords-verified", 'no' );
            update_user_meta( $uid, '_aaa_adbc2_verify_failed', 'yes' );
            throw new \Exception("Geocode failed: {$status}");
        }
    }

    private static function geocode( string $address ) : array {
        $url = add_query_arg([
            'address'    => rawurlencode($address),
            'key'        => self::API_KEY,
            'components' => 'country:US|administrative_area:CA',
        ], 'https://maps.googleapis.com/maps/api/geocode/json');

        $r = wp_remote_get($url, ['timeout'=>8]);
        if ( is_wp_error($r) ) return ['','','','WP_Error: '.$r->get_error_message()];
        $j = json_decode(wp_remote_retrieve_body($r), true);
        if ( empty($j['status']) || $j['status'] !== 'OK' ) {
            $msg = $j['status'] ?? 'UNKNOWN';
            if ( ! empty($j['error_message']) ) $msg .= ' - '.$j['error_message'];
            return ['','','',$msg];
        }
        $loc = $j['results'][0]['geometry']['location'] ?? [];
        $components = $j['results'][0]['address_components'] ?? [];
        return [ $loc['lat'] ?? '', $loc['lng'] ?? '', $components, 'OK' ];
    }

    /* ───────── Queue Page ───────── */
    public static function register_queue_page() {
        add_options_page(
            'Address Verification Queue (v2)',
            'Address Verification Queue (v2)',
            'manage_options',
            'aaa-adbc2-queue',
            [ __CLASS__, 'render_queue_page' ]
        );
    }

    public static function render_queue_page() {
        self::ensure_table();
        global $wpdb; $table = $wpdb->prefix . self::TABLE;

        // Clear queue postback
        if ( isset($_POST['aaa_adbc2_clear_queue']) && check_admin_referer('aaa_adbc2_clear_queue') ) {
            $wpdb->query("TRUNCATE TABLE $table");
            echo '<div class="updated"><p>Queue cleared.</p></div>';
        }

        // Stats
        $counts = [
            'total'   => (int)$wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'queued'  => (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='queued'"),
            'success' => (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='success'"),
            'failed'  => (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='failed'"),
        ];
        $jobs = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 200");

        echo '<div class="wrap"><h1>Address Verification Queue (v2)</h1>';
        echo '<p><strong>Total:</strong> '.esc_html($counts['total']).' | ';
        echo '<span style="color:orange"><strong>Queued:</strong> '.esc_html($counts['queued']).'</span> | ';
        echo '<span style="color:green"><strong>Success:</strong> '.esc_html($counts['success']).'</span> | ';
        echo '<span style="color:red"><strong>Failed:</strong> '.esc_html($counts['failed']).'</span></p>';

        // Run Now + Clear
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline-block;margin-right:10px;">';
        wp_nonce_field('aaa_adbc2_run_now');
        echo '<input type="hidden" name="action" value="aaa_adbc2_run_now">';
        echo '<button class="button button-primary">Run Now (one batch)</button>';
        echo '</form>';

        echo '<form method="post" style="display:inline-block;">';
        wp_nonce_field('aaa_adbc2_clear_queue');
        echo '<button type="submit" name="aaa_adbc2_clear_queue" class="button button-secondary" onclick="return confirm(\'Clear all jobs from the queue?\')">Clear Queue</button>';
        echo '</form>';

        echo '<br><br><table class="widefat striped"><thead><tr>
            <th>ID</th><th>User</th><th>Scope</th><th>Status</th><th>Message</th><th>Created</th><th>Processed</th>
        </tr></thead><tbody id="aaa-adbc2-queue-body">';
        self::render_queue_table_rows($jobs);
        echo '</tbody></table></div>';

        // AJAX refresh
        echo '<script>
        (function($){
            function refreshQueue(){
                $.post(ajaxurl,{action:"aaa_adbc2_queue_table"},function(resp){
                    if(resp && resp.success && resp.data && resp.data.html){
                        $("#aaa-adbc2-queue-body").html(resp.data.html);
                    }
                });
            }
            setInterval(refreshQueue,10000);
        })(jQuery);
        </script>';
    }

    private static function render_queue_table_rows($jobs){
        if ( empty($jobs) ) {
            echo '<tr><td colspan="7">No jobs.</td></tr>';
            return;
        }
        foreach ($jobs as $job){
            $row_style = ($job->status==='success') ? ' style="background-color:#e6ffe6;"'
                        : (($job->status==='failed') ? ' style="background-color:#ffe6e6;"' : '');
            printf('<tr%s><td>%d</td><td><a href="%s">%d</a></td><td>%s</td><td>%s</td><td style="max-width:420px;">%s</td><td>%s</td></tr>',
                $row_style,
                (int)$job->id,
                esc_url(get_edit_user_link((int)$job->user_id)),
                (int)$job->user_id,
                esc_html($job->scope),
                esc_html($job->status),
                esc_html((string)$job->message),
                esc_html((string)$job->processed_at)
            );
        }
    }

    public static function ajax_render_queue_table(){
        if ( ! current_user_can('manage_options') ) wp_send_json_error();
        self::ensure_table();
        global $wpdb; $table = $wpdb->prefix . self::TABLE;
        $jobs = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 200");
        ob_start(); self::render_queue_table_rows($jobs); $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }

    /* ───────── Mass Submission (Billing not verified) ───────── */
    public static function register_mass_page() {
        add_submenu_page(
            'tools.php',
            'Mass Submission v2',
            'Mass Submission v2',
            'manage_options',
            'aaa-adbc2-mass',
            [ __CLASS__, 'render_mass_page' ]
        );
    }

    public static function render_mass_page(){
        self::ensure_table();
        global $wpdb; 
        $table        = $wpdb->prefix . self::TABLE;
        $jobs_tbl     = $wpdb->prefix . self::TABLE;

        $include_failed = ! empty($_GET['show_failed']);
        $order          = ( isset($_GET['order']) && strtolower($_GET['order'])==='asc' ) ? 'ASC' : 'DESC';
        $per_page       = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 200;

        // Handle mass actions
        if ( ! empty($_POST['uids']) && check_admin_referer('aaa_adbc2_mass_submit') ) {
            $uids = array_map('intval', (array)$_POST['uids']);

            if ( isset($_POST['queue_selected']) ) {
                $queued = 0;
                foreach ($uids as $uid) {
                    // Skip if already queued (race-safety)
                    $already = (int)$wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $jobs_tbl WHERE user_id=%d AND status='queued'", $uid) );
                    if ( $already ) continue;

                    $ok = $wpdb->insert( $table, [
                        'user_id'    => $uid,
                        'scope'      => 'billing',
                        'status'     => 'queued',
                        'created_at' => current_time('mysql'),
                    ]);
                    if ( $ok ) $queued++;
                }
                if ( $queued && ! wp_next_scheduled(self::CRON_HOOK) ) {
                    wp_schedule_single_event( time()+5, self::CRON_HOOK );
                }
                echo '<div class="updated"><p>Queued '.esc_html($queued).' users.</p></div>';
            }

            if ( isset($_POST['exclude_selected']) ) {
                foreach ( $uids as $uid ) {
                    update_user_meta( $uid, '_aaa_adbc2_excluded', 'yes' );
                }
                echo '<div class="updated"><p>Excluded '.esc_html(count($uids)).' users.</p></div>';
            }
        }

        // Build list SQL (exclude verified, exclude already queued, exclude excluded, optionally exclude failed)
        $sql = "
            SELECT u.ID,u.user_login,
                   MAX(CASE WHEN um.meta_key='billing_address_1' THEN um.meta_value END) AS addr1,
                   MAX(CASE WHEN um.meta_key='billing_city' THEN um.meta_value END)        AS city,
                   MAX(CASE WHEN um.meta_key='_aaa_adbc2_verify_failed' THEN um.meta_value END) AS failed,
                   MAX(CASE WHEN um.meta_key='_aaa_adbc2_excluded' THEN um.meta_value END)     AS excluded
            FROM {$wpdb->users} u
            JOIN {$wpdb->usermeta} um ON um.user_id=u.ID
            GROUP BY u.ID
            HAVING addr1 <> ''
              AND u.ID NOT IN (
                  SELECT user_id FROM {$wpdb->usermeta}
                  WHERE meta_key='_wc_billing/aaa-delivery-blocks/coords-verified' AND meta_value='yes'
              )
              AND u.ID NOT IN (
                  SELECT user_id FROM {$jobs_tbl} WHERE status='queued'
              )
        ";
        if ( ! $include_failed ) $sql .= " AND (failed IS NULL OR failed <> 'yes')";
        $sql .= " AND (excluded IS NULL OR excluded <> 'yes')";
        $order = ($order==='ASC') ? 'ASC' : 'DESC';
        $per_page = max(1, (int)$per_page);
        $sql .= " ORDER BY u.ID {$order} LIMIT {$per_page}";

        $users = $wpdb->get_results($sql);

        echo '<div class="wrap"><h1>Mass Submission (Billing not verified)</h1>';

        // Filters
        $base_url   = remove_query_arg(['show_failed','order','per_page']);
        $toggle_url = $include_failed ? esc_url($base_url) : esc_url(add_query_arg('show_failed',1,$base_url));
        echo '<p>'.( $include_failed ? '<a href="'.$toggle_url.'">Hide failed users</a>' : '<a href="'.$toggle_url.'">Show failed users</a>' ).'</p>';

        $asc_url  = esc_url( add_query_arg('order','asc') );
        $desc_url = esc_url( add_query_arg('order','desc') );
        echo '<p>Sort by ID: <a href="'.$asc_url.'">Ascending</a> | <a href="'.$desc_url.'">Descending</a></p>';

        echo '<form method="get" style="margin-bottom:10px;">';
        echo '<input type="hidden" name="page" value="aaa-adbc2-mass" />';
        echo '<label>Users per page: <input type="number" name="per_page" value="'.esc_attr($per_page).'" min="1" max="5000" /></label> ';
        echo '<button class="button">Apply</button>';
        echo '</form>';

        // Table + mass actions
        echo '<form method="post">';
        wp_nonce_field('aaa_adbc2_mass_submit');
        echo '<table class="widefat striped"><thead><tr>
            <th><input type="checkbox" id="checkall"></th>
            <th>ID</th><th>User</th><th>Addr1</th><th>City</th><th>Failed</th><th>Exclude</th>
        </tr></thead><tbody>';

        if ( empty($users) ) {
            echo '<tr><td colspan="7">No users found with unverified billing address (respecting filters).</td></tr>';
        } else {
            foreach ( $users as $u ) {
                $exclude_url = wp_nonce_url(
                    admin_url('admin-post.php?action=aaa_adbc2_exclude_user&uid='.$u->ID),
                    'aaa_adbc2_exclude_'.$u->ID
                );
                printf(
                    '<tr>
                        <td><input type="checkbox" name="uids[]" value="%d" /></td>
                        <td><a href="%s">%d</a></td>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td><a class="button" href="%s" onclick="return confirm(\'Exclude this user from this list?\')">Exclude</a></td>
                    </tr>',
                    (int)$u->ID,
                    esc_url( get_edit_user_link( (int)$u->ID ) ),
                    (int)$u->ID,
                    esc_html( $u->user_login ),
                    esc_html( (string)$u->addr1 ),
                    esc_html( (string)$u->city ),
                    esc_html( $u->failed ? 'yes' : 'no' ),
                    esc_url( $exclude_url )
                );
            }
        }

        echo '</tbody></table>';
        echo '<p>
                <button type="submit" name="queue_selected" class="button button-primary">Queue Selected</button>
                <button type="submit" name="exclude_selected" class="button">Exclude Selected</button>
              </p>';
        echo '</form>';

        // check-all JS
        echo '<script>
            (function(){
                var all = document.getElementById("checkall");
                if(!all) return;
                all.addEventListener("change", function(){
                    document.querySelectorAll(\'input[name="uids[]"]\').forEach(function(cb){ cb.checked = all.checked; });
                });
            })();
        </script>';

        echo '</div>';
    }

    /* ───────── Plugin list links ───────── */
    public static function plugin_links($links){
        $queue_link = '<a href="'.admin_url('options-general.php?page=aaa-adbc2-queue').'">Queue</a>';
        $mass_link  = '<a href="'.admin_url('tools.php?page=aaa-adbc2-mass').'">Mass Submit</a>';
        array_unshift($links, $queue_link, $mass_link);
        return $links;
    }
}

AAA_ADBC_BulkVerify_V2::init();
