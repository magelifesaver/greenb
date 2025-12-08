<?php
/**
 * Plugin Name: AAA FluentCRM Birthday Flags
 * Description: Tag contacts for upcoming/today birthdays using FluentCRM tags. Fully configurable tag selections and window (days).
 * Version: 1.1.0
 * Author: Workflow
 *
 * File Path: wp-content/plugins/aaa-fluentcrm-birthday-flags/aaa-fluentcrm-birthday-flags.php
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'AAA_FCRM_BDAY_DEBUG', true );
define( 'AAA_FCRM_BDAY_CRON', 'aaa_fcrm_birthday_flags_run' );
define( 'AAA_FCRM_BDAY_OPT_WINDOW',  'aaa_fcrm_bday_window_days' ); // int
define( 'AAA_FCRM_BDAY_OPT_TAGMAP',  'aaa_fcrm_bday_tag_map' );     // array

register_activation_hook( __FILE__, function () {
    if ( AAA_FCRM_BDAY_DEBUG ) error_log('[AAA-FCRM-BDAY] Activating & scheduling daily task');

    if ( false === get_option( AAA_FCRM_BDAY_OPT_WINDOW, false ) ) {
        update_option( AAA_FCRM_BDAY_OPT_WINDOW, 7 ); // default 7 days
    }
    if ( false === get_option( AAA_FCRM_BDAY_OPT_TAGMAP, false ) ) {
        update_option( AAA_FCRM_BDAY_OPT_TAGMAP, array(
            'upcoming_add'    => array( 'birthday-upcoming' ),
            'upcoming_remove' => array( 'birthday-today' ),
            'today_add'       => array( 'birthday-today' ),
            'today_remove'    => array( 'birthday-upcoming' ),
        ) );
    }
    if ( ! wp_next_scheduled( AAA_FCRM_BDAY_CRON ) ) {
        wp_schedule_event( time() + 300, 'daily', AAA_FCRM_BDAY_CRON );
    }
});

register_deactivation_hook( __FILE__, function () {
    if ( AAA_FCRM_BDAY_DEBUG ) error_log('[AAA-FCRM-BDAY] Deactivating & clearing schedule');
    if ( $ts = wp_next_scheduled( AAA_FCRM_BDAY_CRON ) ) {
        wp_unschedule_event( $ts, AAA_FCRM_BDAY_CRON );
    }
});

/**
 * Admin: settings page (under FluentCRM) — use a high priority so it appears near the bottom.
 */
add_action( 'admin_menu', function () {
    add_submenu_page(
        'fluentcrm-admin',
        'Birthday Flags',
        'Birthday Flags',
        'manage_options',
        'aaa-fcrm-birthday-flags',
        'aaa_fcrm_bday_settings_page'
    );
}, 999 );

/**
 * Render multi-select for tags.
 */
function aaa_fcrm_bday_render_tag_multiselect( $name, $selected_slugs, $all_tags ) {
    $selected_slugs = is_array($selected_slugs) ? $selected_slugs : array();
    echo '<select name="tags['.esc_attr($name).'][]" multiple size="8" style="min-width:320px">';
    foreach ( $all_tags as $tag ) {
        $slug = $tag['slug'];
        $title = $tag['title'];
        $sel = in_array( $slug, $selected_slugs, true ) ? ' selected' : '';
        echo '<option value="'.esc_attr($slug).'"'.$sel.'>'.esc_html($title).' ('.esc_html($slug).')</option>';
    }
    echo '</select>';
}

/**
 * Settings page callback.
 */
function aaa_fcrm_bday_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    // Load all FluentCRM tags for selection.
    $all_tags = array();
    if ( function_exists( 'FluentCrmApi' ) ) {
        try {
            $tagApi  = FluentCrmApi('tags');
            $model   = $tagApi->getInstance();
            $rows    = $model->orderBy('title', 'asc')->get(['id','title','slug']);
            foreach ( $rows as $row ) {
                // $row may be object (Model) or array depending on FCRM version
                $all_tags[] = array(
                    'id'    => is_array($row) ? $row['id']    : $row->id,
                    'title' => is_array($row) ? $row['title'] : $row->title,
                    'slug'  => is_array($row) ? $row['slug']  : $row->slug,
                );
            }
        } catch ( \Throwable $e ) {
            echo '<div class="error"><p>Could not load tags: '.esc_html($e->getMessage()).'</p></div>';
        }
    } else {
        echo '<div class="error"><p>FluentCRM is not active.</p></div>';
    }

    // Current settings
    $days = absint( get_option( AAA_FCRM_BDAY_OPT_WINDOW, 7 ) );
    $defaults = array(
        'upcoming_add'    => array( 'birthday-upcoming' ),
        'upcoming_remove' => array( 'birthday-today' ),
        'today_add'       => array( 'birthday-today' ),
        'today_remove'    => array( 'birthday-upcoming' ),
    );
    $cfg = get_option( AAA_FCRM_BDAY_OPT_TAGMAP, array() );
    $cfg = wp_parse_args( is_array($cfg) ? $cfg : array(), $defaults );

    // Handle save.
    if ( isset($_POST['aaa_fcrm_bday_save']) && check_admin_referer('aaa_fcrm_bday_save') ) {
        $days_in = max( 0, absint( $_POST['window_days'] ?? 7 ) );

        $incoming = $_POST['tags'] ?? array();
        $new_cfg  = array();
        foreach ( array('upcoming_add','upcoming_remove','today_add','today_remove') as $key ) {
            $vals = isset($incoming[$key]) && is_array($incoming[$key]) ? $incoming[$key] : array();
            // sanitize: keep lowercase slugs with dashes
            $vals = array_values(array_unique(array_filter(array_map(function($s){
                $s = sanitize_text_field($s);
                return preg_match('/^[a-z0-9\-]+$/', $s) ? $s : '';
            }, $vals))));
            $new_cfg[$key] = $vals;
        }

        update_option( AAA_FCRM_BDAY_OPT_WINDOW, $days_in );
        update_option( AAA_FCRM_BDAY_OPT_TAGMAP, $new_cfg );

        $days = $days_in;
        $cfg  = wp_parse_args( $new_cfg, $defaults );

        echo '<div class="updated"><p>Saved. Window set to '.esc_html($days).' days.</p></div>';
    }

    // Manual run.
    if ( isset($_POST['aaa_fcrm_bday_run_now']) && check_admin_referer('aaa_fcrm_bday_run') ) {
        aaa_fcrm_bday_run();
        echo '<div class="updated"><p>Birthday flagger ran successfully.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>FluentCRM — Birthday Flags</h1>

        <form method="post">
            <?php wp_nonce_field('aaa_fcrm_bday_save'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="window_days">Upcoming Window (days)</label></th>
                    <td>
                        <input type="number" id="window_days" name="window_days" value="<?php echo esc_attr($days); ?>" min="0" style="width:100px">
                        <?php if ( $days===0 ): ?><em> (0 disables upcoming tagging)</em><?php endif; ?>
                    </td>
                </tr>

                <tr><th colspan="2"><h2 style="margin:0;">Upcoming Birthdays (1..N days)</h2></th></tr>
                <tr>
                    <th scope="row">Tags to <strong>Add</strong></th>
                    <td><?php aaa_fcrm_bday_render_tag_multiselect('upcoming_add', $cfg['upcoming_add'], $all_tags); ?></td>
                </tr>
                <tr>
                    <th scope="row">Tags to <strong>Remove</strong></th>
                    <td><?php aaa_fcrm_bday_render_tag_multiselect('upcoming_remove', $cfg['upcoming_remove'], $all_tags); ?></td>
                </tr>

                <tr><th colspan="2"><h2 style="margin:0;">Today’s Birthdays (day 0)</h2></th></tr>
                <tr>
                    <th scope="row">Tags to <strong>Add</strong></th>
                    <td><?php aaa_fcrm_bday_render_tag_multiselect('today_add', $cfg['today_add'], $all_tags); ?></td>
                </tr>
                <tr>
                    <th scope="row">Tags to <strong>Remove</strong></th>
                    <td><?php aaa_fcrm_bday_render_tag_multiselect('today_remove', $cfg['today_remove'], $all_tags); ?></td>
                </tr>
            </table>

            <p class="submit"><button class="button button-primary" name="aaa_fcrm_bday_save" value="1">Save Settings</button></p>
        </form>

        <form method="post" style="margin-top:1em;">
            <?php wp_nonce_field('aaa_fcrm_bday_run'); ?>
            <button class="button" name="aaa_fcrm_bday_run_now" value="1">Run Now</button>
        </form>

        <p style="margin-top:1em;color:#555;">
            Tip: Build a FluentCRM segment with Tag filters (e.g., "has any of" your selected Upcoming tags).
        </p>
    </div>
    <?php
}

/**
 * Cron + Manual runner.
 */
add_action( AAA_FCRM_BDAY_CRON, 'aaa_fcrm_bday_run' );

function aaa_fcrm_bday_run() {
    if ( ! function_exists( 'FluentCrmApi' ) ) {
        if ( AAA_FCRM_BDAY_DEBUG ) error_log('[AAA-FCRM-BDAY] FluentCRM not active; aborting');
        return;
    }

    // Prevent overlaps
    if ( get_transient( 'aaa_fcrm_bday_running' ) ) {
        if ( AAA_FCRM_BDAY_DEBUG ) error_log('[AAA-FCRM-BDAY] Skip: previous run still in progress');
        return;
    }
    set_transient( 'aaa_fcrm_bday_running', 1, 5 * MINUTE_IN_SECONDS );

    $days_window = absint( get_option( AAA_FCRM_BDAY_OPT_WINDOW, 7 ) );

    $defaults = array(
        'upcoming_add'    => array( 'birthday-upcoming' ),
        'upcoming_remove' => array( 'birthday-today' ),
        'today_add'       => array( 'birthday-today' ),
        'today_remove'    => array( 'birthday-upcoming' ),
    );
    $cfg = get_option( AAA_FCRM_BDAY_OPT_TAGMAP, array() );
    $cfg = wp_parse_args( is_array($cfg) ? $cfg : array(), $defaults );

    // Build tag slug => id map once.
    $slug_to_id = array();
    try {
        $tagApi = FluentCrmApi('tags');
        $model  = $tagApi->getInstance();
        $rows   = $model->get(['id','title','slug']);
        foreach ( $rows as $row ) {
            $slug_to_id[ is_array($row) ? $row['slug'] : $row->slug ] = (int)( is_array($row) ? $row['id'] : $row->id );
        }
    } catch ( \Throwable $e ) {
        if ( AAA_FCRM_BDAY_DEBUG ) error_log('[AAA-FCRM-BDAY] Could not load tags: '.$e->getMessage());
    }

    $tz    = wp_timezone();
    $today = new DateTimeImmutable( 'today', $tz );

    global $wpdb;
    $table = $wpdb->prefix . 'fc_subscribers';
    $rows  = $wpdb->get_results( "SELECT id, date_of_birth FROM {$table} WHERE date_of_birth IS NOT NULL AND date_of_birth <> ''", ARRAY_A );

    if ( AAA_FCRM_BDAY_DEBUG ) error_log('[AAA-FCRM-BDAY] Scanning '.count($rows).' contacts');

    foreach ( $rows as $r ) {
        $id  = (int) $r['id'];
        $dob = $r['date_of_birth']; // Y-m-d

        if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) ) { continue; }
        list($y,$m,$d) = array_map('intval', explode('-', $dob));

        // Handle Feb 29 on non-leap years (use Feb 28)
        $month = $m; $day = $d;
        $year  = (int) $today->format('Y');
        if ( $month === 2 && $day === 29 && ! aaa_fcrm_is_leap_year( $year ) ) $day = 28;

        try {
            $bday_this_year = new DateTimeImmutable( sprintf('%04d-%02d-%02d', $year, $month, $day ), $tz );
        } catch ( \Exception $e ) {
            continue;
        }

        $target    = $bday_this_year < $today ? $bday_this_year->modify('+1 year') : $bday_this_year;
        $days_left = (int) $today->diff( $target )->format('%a');

        // Decide tag actions based on config
        $add_slugs = array();
        $rem_slugs = array();

        if ( 0 === $days_left ) {
            // TODAY ONLY
            $add_slugs = $cfg['today_add'];
            $rem_slugs = $cfg['today_remove'];
        } elseif ( $days_window > 0 && $days_left <= $days_window ) {
            // UPCOMING 1..N
            $add_slugs = $cfg['upcoming_add'];
            $rem_slugs = $cfg['upcoming_remove'];
        } else {
            // OUTSIDE WINDOW — remove all configured tags to keep things tidy
            $rem_slugs = array_unique(array_merge(
                $cfg['upcoming_add'], $cfg['upcoming_remove'], $cfg['today_add'], $cfg['today_remove']
            ));
            $add_slugs = array();
        }

        // Ensure we never add and remove the same slug in one pass
        $add_slugs = array_values(array_diff(array_unique($add_slugs), $rem_slugs));
        $rem_slugs = array_values(array_unique($rem_slugs));

        // Map slugs => ids (ignore missing)
        $add_ids = array();
        foreach ( $add_slugs as $s ) if ( isset($slug_to_id[$s]) ) $add_ids[] = $slug_to_id[$s];
        $rem_ids = array();
        foreach ( $rem_slugs as $s ) if ( isset($slug_to_id[$s]) ) $rem_ids[] = $slug_to_id[$s];

        try {
            $contactApi = FluentCrmApi('contacts');
            $subscriber = $contactApi->getContact( $id );
            if ( ! $subscriber ) { continue; }

            if ( $add_ids )    $subscriber->attachTags( $add_ids );
            if ( $rem_ids )    $subscriber->detachTags( $rem_ids );

            if ( AAA_FCRM_BDAY_DEBUG ) {
                if ( $add_ids ) error_log("[AAA-FCRM-BDAY] #$id + ".implode(',',$add_slugs)." (days_left=$days_left)");
                if ( $rem_ids ) error_log("[AAA-FCRM-BDAY] #$id - ".implode(',',$rem_slugs)." (days_left=$days_left)");
            }
        } catch ( \Throwable $e ) {
            if ( AAA_FCRM_BDAY_DEBUG ) error_log("[AAA-FCRM-BDAY] #$id error: ".$e->getMessage());
        }
    }

    delete_transient( 'aaa_fcrm_bday_running' );
    if ( AAA_FCRM_BDAY_DEBUG ) error_log('[AAA-FCRM-BDAY] Done');
}

/** Leap year helper */
function aaa_fcrm_is_leap_year( $year ) {
    return ( ( $year % 4 === 0 ) && ( $year % 100 !== 0 ) ) || ( $year % 400 === 0 );
}
