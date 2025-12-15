<?php
/**
 * Plugin Name: AAA FluentCRM Birthday Flags (XHV98-FLU)
 * Description: Tag contacts for upcoming/today birthdays using FluentCRM tags. Fully configurable tag selections and window (days).
 * Version: 1.1.1
 * Author: Webmaster
 * Text Domain: aaa-fluentcrm-birthday-flags
 *
 * File Path: wp-content/plugins/aaa-fluentcrm-birthday-flags/aaa-fluentcrm-birthday-flags.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* -------------------------------------------------------------------------
   CONSTANTS
---------------------------------------------------------------------------*/
// Toggle verbose logging. When true, messages are written to error_log().
define( 'AAA_FCRM_BDAY_DEBUG', true );
// The hook name used for cron scheduling.
define( 'AAA_FCRM_BDAY_CRON', 'aaa_fcrm_birthday_flags_run' );
// Option storing the upcoming window in days (integer)
define( 'AAA_FCRM_BDAY_OPT_WINDOW',  'aaa_fcrm_bday_window_days' );
// Option storing the tag map (array of slugs)
define( 'AAA_FCRM_BDAY_OPT_TAGMAP',  'aaa_fcrm_bday_tag_map' );
// Option storing the daily run time. Blank disables automatic scheduling. Format HH:MM (24h)
define( 'AAA_FCRM_BDAY_OPT_RUNTIME', 'aaa_fcrm_bday_run_time' );

/* -------------------------------------------------------------------------
   ACTIVATION / DEACTIVATION
---------------------------------------------------------------------------*/
register_activation_hook( __FILE__, function () {
    if ( AAA_FCRM_BDAY_DEBUG ) {
        error_log('[AAA-FCRM-BDAY] Activating');
    }

    if ( false === get_option( AAA_FCRM_BDAY_OPT_WINDOW, false ) ) {
        update_option( AAA_FCRM_BDAY_OPT_WINDOW, 7 );
    }

    if ( false === get_option( AAA_FCRM_BDAY_OPT_TAGMAP, false ) ) {
        update_option( AAA_FCRM_BDAY_OPT_TAGMAP, array(
            'upcoming_add'    => array( 'birthday-upcoming' ),
            'upcoming_remove' => array( 'birthday-today' ),
            'today_add'       => array( 'birthday-today' ),
            'today_remove'    => array( 'birthday-upcoming' ),
        ) );
    }

    if ( false === get_option( AAA_FCRM_BDAY_OPT_RUNTIME, false ) ) {
        update_option( AAA_FCRM_BDAY_OPT_RUNTIME, '' );
    }

    if ( $ts = wp_next_scheduled( AAA_FCRM_BDAY_CRON ) ) {
        wp_unschedule_event( $ts, AAA_FCRM_BDAY_CRON );
    }
});

register_deactivation_hook( __FILE__, function () {
    if ( AAA_FCRM_BDAY_DEBUG ) {
        error_log('[AAA-FCRM-BDAY] Deactivating & clearing schedule');
    }
    if ( $ts = wp_next_scheduled( AAA_FCRM_BDAY_CRON ) ) {
        wp_unschedule_event( $ts, AAA_FCRM_BDAY_CRON );
    }
});

/* -------------------------------------------------------------------------
   ADMIN: settings page (under FluentCRM)
---------------------------------------------------------------------------*/
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
 * Render a multi-select input for selecting tags.
 *
 * @param string $name
 * @param array  $selected_slugs
 * @param array  $all_tags
 */
function aaa_fcrm_bday_render_tag_multiselect( $name, $selected_slugs, $all_tags ) {
    $selected_slugs = is_array( $selected_slugs ) ? $selected_slugs : array();

    echo '<select name="tags[' . esc_attr( $name ) . '][]" multiple size="8" style="min-width:320px">';
    foreach ( $all_tags as $tag ) {
        $slug  = $tag['slug'];
        $title = $tag['title'];

        // Use WP helper to output the selected attribute (avoids Plugin Check “$sel not escaped” complaint).
        $selected_attr = selected( in_array( $slug, $selected_slugs, true ), true, false );

        printf(
            '<option value="%s"%s>%s (%s)</option>',
            esc_attr( $slug ),
            $selected_attr,
            esc_html( $title ),
            esc_html( $slug )
        );
    }
    echo '</select>';
}

function aaa_fcrm_bday_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Unsplash once, then use $post everywhere for safety + Plugin Check compliance.
    $post = array();
    if ( ! empty( $_POST ) && is_array( $_POST ) ) {
        $post = wp_unslash( $_POST );
    }

    $all_tags = array();
    if ( function_exists( 'FluentCrmApi' ) ) {
        try {
            $tagApi = FluentCrmApi( 'tags' );
            $model  = $tagApi->getInstance();
            $rows   = $model->orderBy( 'title', 'asc' )->get( array( 'id', 'title', 'slug' ) );
            foreach ( $rows as $row ) {
                $all_tags[] = array(
                    'id'    => is_array( $row ) ? $row['id']    : $row->id,
                    'title' => is_array( $row ) ? $row['title'] : $row->title,
                    'slug'  => is_array( $row ) ? $row['slug']  : $row->slug,
                );
            }
        } catch ( \Throwable $e ) {
            echo '<div class="error"><p>Could not load tags: ' . esc_html( $e->getMessage() ) . '</p></div>';
        }
    } else {
        echo '<div class="error"><p>FluentCRM is not active.</p></div>';
    }

    $days      = absint( get_option( AAA_FCRM_BDAY_OPT_WINDOW, 7 ) );
    $defaults  = array(
        'upcoming_add'    => array( 'birthday-upcoming' ),
        'upcoming_remove' => array( 'birthday-today' ),
        'today_add'       => array( 'birthday-today' ),
        'today_remove'    => array( 'birthday-upcoming' ),
    );
    $cfg      = get_option( AAA_FCRM_BDAY_OPT_TAGMAP, array() );
    $cfg      = wp_parse_args( is_array( $cfg ) ? $cfg : array(), $defaults );
    $run_time = get_option( AAA_FCRM_BDAY_OPT_RUNTIME, '' );

    if ( isset( $post['aaa_fcrm_bday_save'] ) && check_admin_referer( 'aaa_fcrm_bday_save' ) ) {
        $days_in = max( 0, absint( $post['window_days'] ?? 7 ) );

        $incoming = ( isset( $post['tags'] ) && is_array( $post['tags'] ) ) ? $post['tags'] : array();
        $new_cfg  = array();

        foreach ( array( 'upcoming_add', 'upcoming_remove', 'today_add', 'today_remove' ) as $key ) {
            $vals = ( isset( $incoming[ $key ] ) && is_array( $incoming[ $key ] ) ) ? $incoming[ $key ] : array();

            $vals = array_values( array_unique( array_filter( array_map( function( $s ) {
                $s = sanitize_text_field( $s );
                return preg_match( '/^[a-z0-9\-]+$/', $s ) ? $s : '';
            }, $vals ) ) ) );

            $new_cfg[ $key ] = $vals;
        }

        update_option( AAA_FCRM_BDAY_OPT_WINDOW, $days_in );
        update_option( AAA_FCRM_BDAY_OPT_TAGMAP, $new_cfg );
        $days = $days_in;
        $cfg  = wp_parse_args( $new_cfg, $defaults );

        $run_time_in = isset( $post['run_time'] ) ? sanitize_text_field( $post['run_time'] ) : '';
        $run_time_in = trim( $run_time_in );

        if ( '' !== $run_time_in && ! preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', $run_time_in ) ) {
            $run_time_in = '';
        }

        update_option( AAA_FCRM_BDAY_OPT_RUNTIME, $run_time_in );
        $run_time = $run_time_in;

        $next_ts = wp_next_scheduled( AAA_FCRM_BDAY_CRON );
        if ( $next_ts ) {
            wp_unschedule_event( $next_ts, AAA_FCRM_BDAY_CRON );
        }

        if ( '' !== $run_time_in ) {
            try {
                $tz  = wp_timezone();
                $now = new DateTimeImmutable( 'now', $tz );
                list( $h, $m ) = array_map( 'intval', explode( ':', $run_time_in ) );
                $candidate = new DateTimeImmutable( $now->format( 'Y-m-d' ) . sprintf( ' %02d:%02d:00', $h, $m ), $tz );
                if ( $candidate <= $now ) {
                    $candidate = $candidate->modify( '+1 day' );
                }
                $ts = $candidate->getTimestamp();
            } catch ( \Throwable $e ) {
                $ts = ( new DateTimeImmutable( 'tomorrow ' . $run_time_in . ':00', wp_timezone() ) )->getTimestamp();
            }
            wp_schedule_event( $ts, 'daily', AAA_FCRM_BDAY_CRON );
        }

        $msg  = 'Saved. Window set to ' . esc_html( $days ) . ' days.';
        if ( '' !== $run_time ) {
            $msg .= ' Daily run time set to ' . esc_html( $run_time ) . ' (site timezone).';
        } else {
            $msg .= ' Automatic scheduling disabled.';
        }
        echo '<div class="updated"><p>' . esc_html( $msg ) . '</p></div>';
    }

    if ( isset( $post['aaa_fcrm_bday_run_now'] ) && check_admin_referer( 'aaa_fcrm_bday_run' ) ) {
        aaa_fcrm_bday_run();
        echo '<div class="updated"><p>Birthday flagger ran successfully.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>FluentCRM — Birthday Flags</h1>
        <?php
        $next = wp_next_scheduled( AAA_FCRM_BDAY_CRON );
        if ( $next ) {
            $local_time = wp_date( 'Y-m-d H:i:s', $next );
            $utc_time   = gmdate( 'Y-m-d H:i:s', $next );
            echo '<p><strong>Next scheduled run:</strong> ' . esc_html( $local_time ) . ' (site timezone), ' . esc_html( $utc_time ) . ' (UTC)</p>';
        } else {
            echo '<p><strong>No automatic run scheduled.</strong></p>';
        }
        ?>

        <form method="post">
            <?php wp_nonce_field( 'aaa_fcrm_bday_save' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="window_days">Upcoming Window (days)</label></th>
                    <td>
                        <input type="number" id="window_days" name="window_days" value="<?php echo esc_attr( $days ); ?>" min="0" style="width:100px">
                        <?php if ( $days === 0 ) : ?><em> (0 disables upcoming tagging)</em><?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="run_time">Daily Run Time</label></th>
                    <td>
                        <input type="time" id="run_time" name="run_time" value="<?php echo esc_attr( $run_time ); ?>" step="60">
                        <p class="description">Leave blank to disable automatic runs. Format HH:MM (site timezone).</p>
                    </td>
                </tr>

                <tr><th colspan="2"><h2 style="margin:0;">Upcoming Birthdays (1..N days)</h2></th></tr>
                <tr>
                    <th scope="row">Tags to <strong>Add</strong></th>
                    <td><?php aaa_fcrm_bday_render_tag_multiselect( 'upcoming_add', $cfg['upcoming_add'], $all_tags ); ?></td>
                </tr>
                <tr>
                    <th scope="row">Tags to <strong>Remove</strong></th>
                    <td><?php aaa_fcrm_bday_render_tag_multiselect( 'upcoming_remove', $cfg['upcoming_remove'], $all_tags ); ?></td>
                </tr>

                <tr><th colspan="2"><h2 style="margin:0;">Today’s Birthdays (day 0)</h2></th></tr>
                <tr>
                    <th scope="row">Tags to <strong>Add</strong></th>
                    <td><?php aaa_fcrm_bday_render_tag_multiselect( 'today_add', $cfg['today_add'], $all_tags ); ?></td>
                </tr>
                <tr>
                    <th scope="row">Tags to <strong>Remove</strong></th>
                    <td><?php aaa_fcrm_bday_render_tag_multiselect( 'today_remove', $cfg['today_remove'], $all_tags ); ?></td>
                </tr>
            </table>

            <p class="submit"><button class="button button-primary" name="aaa_fcrm_bday_save" value="1">Save Settings</button></p>
        </form>

        <form method="post" style="margin-top:1em;">
            <?php wp_nonce_field( 'aaa_fcrm_bday_run' ); ?>
            <button class="button" name="aaa_fcrm_bday_run_now" value="1">Run Now</button>
        </form>

        <p style="margin-top:1em;color:#555;">
            Tip: Build a FluentCRM segment with Tag filters (e.g., "has any of" your selected Upcoming tags).
        </p>
    </div>
    <?php
}

/* -------------------------------------------------------------------------
   CRON + MANUAL runner
---------------------------------------------------------------------------*/
add_action( AAA_FCRM_BDAY_CRON, 'aaa_fcrm_bday_run' );

function aaa_fcrm_bday_run() {
    if ( ! function_exists( 'FluentCrmApi' ) ) {
        if ( AAA_FCRM_BDAY_DEBUG ) {
            error_log('[AAA-FCRM-BDAY] FluentCRM not active; aborting');
        }
        return;
    }

    if ( get_transient( 'aaa_fcrm_bday_running' ) ) {
        if ( AAA_FCRM_BDAY_DEBUG ) {
            error_log('[AAA-FCRM-BDAY] Skip: previous run still in progress');
        }
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
    $cfg = wp_parse_args( is_array( $cfg ) ? $cfg : array(), $defaults );

    $slug_to_id = array();
    try {
        $tagApi = FluentCrmApi( 'tags' );
        $model  = $tagApi->getInstance();
        $rows   = $model->get( array( 'id', 'title', 'slug' ) );
        foreach ( $rows as $row ) {
            $slug_to_id[ is_array( $row ) ? $row['slug'] : $row->slug ] = (int) ( is_array( $row ) ? $row['id'] : $row->id );
        }
    } catch ( \Throwable $e ) {
        if ( AAA_FCRM_BDAY_DEBUG ) {
            error_log('[AAA-FCRM-BDAY] Could not load tags: ' . $e->getMessage());
        }
    }

    $tz    = wp_timezone();
    $today = new DateTimeImmutable( 'today', $tz );

    // Use FluentCRM model instead of direct SQL ($wpdb) to avoid unsafe query patterns.
    $contacts = array();
    try {
        $contactApi = FluentCrmApi( 'contacts' );
        $model      = $contactApi->getInstance();

        if ( method_exists( $model, 'whereNotNull' ) ) {
            $contacts = $model
                ->whereNotNull( 'date_of_birth' )
                ->where( 'date_of_birth', '!=', '' )
                ->get( array( 'id', 'date_of_birth' ) );
        }
    } catch ( \Throwable $e ) {
        if ( AAA_FCRM_BDAY_DEBUG ) {
            error_log('[AAA-FCRM-BDAY] Could not load contacts: ' . $e->getMessage());
        }
    }

    $rows = array();
    if ( is_array( $contacts ) ) {
        $rows = $contacts;
    } elseif ( is_object( $contacts ) && method_exists( $contacts, 'toArray' ) ) {
        $rows = $contacts->toArray();
    } elseif ( $contacts instanceof \Traversable ) {
        foreach ( $contacts as $c ) {
            $rows[] = $c;
        }
    }

    if ( AAA_FCRM_BDAY_DEBUG ) {
        error_log('[AAA-FCRM-BDAY] Scanning ' . count( $rows ) . ' contacts');
    }

    foreach ( $rows as $r ) {
        $id  = (int) ( is_array( $r ) ? ( $r['id'] ?? 0 ) : ( $r->id ?? 0 ) );
        $dob = is_array( $r ) ? ( $r['date_of_birth'] ?? '' ) : ( $r->date_of_birth ?? '' );

        if ( $id <= 0 || ! is_string( $dob ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dob ) ) {
            continue;
        }

        list( $y, $m, $d ) = array_map( 'intval', explode( '-', $dob ) );

        $month = $m;
        $day   = $d;
        $year  = (int) $today->format( 'Y' );
        if ( $month === 2 && $day === 29 && ! aaa_fcrm_is_leap_year( $year ) ) {
            $day = 28;
        }

        try {
            $bday_this_year = new DateTimeImmutable( sprintf( '%04d-%02d-%02d', $year, $month, $day ), $tz );
        } catch ( \Exception $e ) {
            continue;
        }

        $target    = $bday_this_year < $today ? $bday_this_year->modify( '+1 year' ) : $bday_this_year;
        $days_left = (int) $today->diff( $target )->format( '%a' );

        $add_slugs = array();
        $rem_slugs = array();

        if ( 0 === $days_left ) {
            $add_slugs = $cfg['today_add'];
            $rem_slugs = $cfg['today_remove'];
        } elseif ( $days_window > 0 && $days_left <= $days_window ) {
            $add_slugs = $cfg['upcoming_add'];
            $rem_slugs = $cfg['upcoming_remove'];
        } else {
            $rem_slugs = array_unique( array_merge(
                $cfg['upcoming_add'], $cfg['upcoming_remove'], $cfg['today_add'], $cfg['today_remove']
            ) );
            $add_slugs = array();
        }

        $add_slugs = array_values( array_diff( array_unique( $add_slugs ), $rem_slugs ) );
        $rem_slugs = array_values( array_unique( $rem_slugs ) );

        $add_ids = array();
        foreach ( $add_slugs as $s ) {
            if ( isset( $slug_to_id[ $s ] ) ) {
                $add_ids[] = $slug_to_id[ $s ];
            }
        }

        $rem_ids = array();
        foreach ( $rem_slugs as $s ) {
            if ( isset( $slug_to_id[ $s ] ) ) {
                $rem_ids[] = $slug_to_id[ $s ];
            }
        }

        try {
            $contactApi = FluentCrmApi( 'contacts' );
            $subscriber = $contactApi->getContact( $id );
            if ( ! $subscriber ) {
                continue;
            }

            if ( $add_ids ) {
                $subscriber->attachTags( $add_ids );
            }
            if ( $rem_ids ) {
                $subscriber->detachTags( $rem_ids );
            }

            if ( AAA_FCRM_BDAY_DEBUG ) {
                if ( $add_ids ) {
                    error_log('[AAA-FCRM-BDAY] #' . $id . ' + ' . implode( ',', $add_slugs ) . ' (days_left=' . $days_left . ')');
                }
                if ( $rem_ids ) {
                    error_log('[AAA-FCRM-BDAY] #' . $id . ' - ' . implode( ',', $rem_slugs ) . ' (days_left=' . $days_left . ')');
                }
            }
        } catch ( \Throwable $e ) {
            if ( AAA_FCRM_BDAY_DEBUG ) {
                error_log('[AAA-FCRM-BDAY] #' . $id . ' error: ' . $e->getMessage());
            }
        }
    }

    delete_transient( 'aaa_fcrm_bday_running' );
    if ( AAA_FCRM_BDAY_DEBUG ) {
        error_log('[AAA-FCRM-BDAY] Done');
    }
}

function aaa_fcrm_is_leap_year( $year ) {
    return ( ( $year % 4 === 0 ) && ( $year % 100 !== 0 ) ) || ( $year % 400 === 0 );
}

/* -------------------------------------------------------------------------
   SETTINGS LINK IN PLUGIN LISTING
---------------------------------------------------------------------------*/
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
    $settings_url = admin_url( 'admin.php?page=aaa-fcrm-birthday-flags' );
    $links[]      = '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'aaa-fluentcrm-birthday-flags' ) . '</a>';
    return $links;
} );
