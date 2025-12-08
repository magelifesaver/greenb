<?php
/**
 * Plugin Name: AAA FluentCRM Age Classifier
 * Description: Computes age & bracket from FluentCRM date_of_birth, writes to FluentCRM custom fields, and manages MEDICAL/RECREATIONAL + bracket + DOB Missing tags. Daily cron + “Run Now”.
 * Version: 1.0.1
 * Author: Workflow
 *
 * File Path: wp-content/plugins/aaa-fluentcrm-age-classifier/aaa-fluentcrm-age-classifier.php
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Debug toggle */
define( 'AAA_FCRM_AGE_DEBUG', true );

/** Options / hooks */
define( 'AAA_FCRM_AGE_OPT',  'aaa_fcrm_age_settings' );
define( 'AAA_FCRM_AGE_CRON', 'aaa_fcrm_age_classifier_run' );

/** Defaults */
function aaa_fcrm_age_defaults() {
    return array(
        // CSV; max is exclusive; "+" is open-ended. Order doesn't matter.
        'brackets' => '18-21,21-30,30-40,40-50,50-60,60-70,70+',

        // Tag slugs (titles auto-generated)
        'medical_tag'      => 'medical',
        'recreational_tag' => 'recreational',
        'dob_missing_tag'  => 'dob-missing',

        // Bracket tag prefixes
        'bracket_tag_slug_prefix'  => 'age-',
        'bracket_tag_title_prefix' => 'Age ',

        // Detach competing tags each run
        'strict_detach' => 1
    );
}
function aaa_fcrm_age_get_settings() {
    $saved = get_option( AAA_FCRM_AGE_OPT, array() );
    return wp_parse_args( is_array( $saved ) ? $saved : array(), aaa_fcrm_age_defaults() );
}

/** Activate / Deactivate */
register_activation_hook( __FILE__, function () {
    if ( false === get_option( AAA_FCRM_AGE_OPT, false ) ) {
        update_option( AAA_FCRM_AGE_OPT, aaa_fcrm_age_defaults() );
    }
    // schedule daily near midnight local (better for rollovers)
    if ( ! wp_next_scheduled( AAA_FCRM_AGE_CRON ) ) {
        $tz = wp_timezone();
        $first = (new DateTimeImmutable('tomorrow 00:07:00', $tz))->getTimestamp();
        wp_schedule_event( $first, 'daily', AAA_FCRM_AGE_CRON );
    }
});
register_deactivation_hook( __FILE__, function () {
    if ( $ts = wp_next_scheduled( AAA_FCRM_AGE_CRON ) ) {
        wp_unschedule_event( $ts, AAA_FCRM_AGE_CRON );
    }
});

/** Admin menu (same as birthday add-on): FluentCRM → Age Classifier (bottom) */
add_action( 'admin_menu', function () {
    add_submenu_page(
        'fluentcrm-admin',
        'Age Classifier',
        'Age Classifier',
        'manage_options',
        'aaa-fluentcrm-age-classifier',
        'aaa_fcrm_age_settings_page'
    );
}, 999 );

/** Settings page */
function aaa_fcrm_age_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $cfg = aaa_fcrm_age_get_settings();

    // Save
    if ( isset( $_POST['aaa_fcrm_age_save'] ) && check_admin_referer( 'aaa_fcrm_age_save' ) ) {
        $in = wp_unslash( $_POST );

        $cfg['brackets'] = sanitize_text_field( $in['brackets'] ?? $cfg['brackets'] );

        $cfg['medical_tag']      = sanitize_title( $in['medical_tag']      ?? $cfg['medical_tag'] );
        $cfg['recreational_tag'] = sanitize_title( $in['recreational_tag'] ?? $cfg['recreational_tag'] );
        $cfg['dob_missing_tag']  = sanitize_title( $in['dob_missing_tag']  ?? $cfg['dob_missing_tag'] );

        // keep user-entered hyphen; don't over-sanitize here
	$prefix = isset($in['bracket_tag_slug_prefix'])
	    ? sanitize_text_field($in['bracket_tag_slug_prefix'])
	    : $cfg['bracket_tag_slug_prefix'];

	$prefix = trim($prefix);
	// ensure a single trailing hyphen so slugs look like "age-21-30"
	if ($prefix !== '' && substr($prefix, -1) !== '-') {
	    $prefix .= '-';
	}
	$cfg['bracket_tag_slug_prefix'] = $prefix;

        $cfg['bracket_tag_title_prefix'] = sanitize_text_field( $in['bracket_tag_title_prefix'] ?? $cfg['bracket_tag_title_prefix'] );

        $cfg['strict_detach'] = empty( $in['strict_detach'] ) ? 0 : 1;

        update_option( AAA_FCRM_AGE_OPT, $cfg );
        echo '<div class="updated"><p>Saved settings.</p></div>';
    }

    // Run now
    if ( isset( $_POST['aaa_fcrm_age_run_now'] ) && check_admin_referer( 'aaa_fcrm_age_run' ) ) {
        aaa_fcrm_age_run();
        echo '<div class="updated"><p>Age classification ran successfully.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>FluentCRM — Age Classifier</h1>

        <form method="post">
            <?php wp_nonce_field( 'aaa_fcrm_age_save' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="brackets">Age Brackets</label></th>
                    <td>
                        <input type="text" id="brackets" name="brackets" class="regular-text" value="<?php echo esc_attr( $cfg['brackets'] ); ?>">
                        <p class="description">CSV. Max is exclusive; “+” is open-ended. Example: <code>18-21,21-30,30-40,40-50,50-60,60-70,70+</code></p>
                    </td>
                </tr>

                <tr><th colspan="2"><h2 style="margin:0;">Tags</h2></th></tr>
                <tr>
                    <th scope="row"><label for="medical_tag">MEDICAL Tag Slug</label></th>
                    <td><input type="text" id="medical_tag" name="medical_tag" value="<?php echo esc_attr( $cfg['medical_tag'] ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="recreational_tag">RECREATIONAL Tag Slug</label></th>
                    <td><input type="text" id="recreational_tag" name="recreational_tag" value="<?php echo esc_attr( $cfg['recreational_tag'] ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="dob_missing_tag">DOB Missing Tag Slug</label></th>
                    <td><input type="text" id="dob_missing_tag" name="dob_missing_tag" value="<?php echo esc_attr( $cfg['dob_missing_tag'] ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="bracket_tag_slug_prefix">Bracket Tag Slug Prefix</label></th>
                    <td>
                        <input type="text" id="bracket_tag_slug_prefix" name="bracket_tag_slug_prefix" value="<?php echo esc_attr( $cfg['bracket_tag_slug_prefix'] ); ?>">
                        <p class="description">Final slug becomes prefix+label, e.g. <code>age-21-30</code></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="bracket_tag_title_prefix">Bracket Tag Title Prefix</label></th>
                    <td>
                        <input type="text" id="bracket_tag_title_prefix" name="bracket_tag_title_prefix" value="<?php echo esc_attr( $cfg['bracket_tag_title_prefix'] ); ?>">
                        <p class="description">Final title becomes prefix+label, e.g. <code>Age 21-30</code></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Strict Detach</th>
                    <td>
                        <label><input type="checkbox" name="strict_detach" value="1" <?php checked( 1, (int)$cfg['strict_detach'] ); ?>> Remove competing tags each run (recommended)</label>
                    </td>
                </tr>
            </table>

            <p class="submit"><button class="button button-primary" name="aaa_fcrm_age_save" value="1">Save Settings</button></p>
        </form>

        <form method="post" style="margin-top:1em;">
            <?php wp_nonce_field( 'aaa_fcrm_age_run' ); ?>
            <button class="button" name="aaa_fcrm_age_run_now" value="1">Run Now</button>
        </form>
    </div>
    <?php
}

/** Cron + Manual */
add_action( AAA_FCRM_AGE_CRON, 'aaa_fcrm_age_run' );

function aaa_fcrm_age_run() {
    if ( ! function_exists( 'FluentCrmApi' ) ) {
        if ( AAA_FCRM_AGE_DEBUG ) error_log('[AAA-FCRM-AGE] FluentCRM not active; aborting');
        return;
    }

// Prevent overlaps
if ( get_transient( 'aaa_fcrm_age_running' ) ) {
    if ( AAA_FCRM_AGE_DEBUG ) error_log('[AAA-FCRM-AGE] Skip: previous run in progress');
    return;
}
set_transient( 'aaa_fcrm_age_running', 1, 5 * MINUTE_IN_SECONDS );

// safety net: always clear the lock even on fatal errors
register_shutdown_function( function () {
    delete_transient( 'aaa_fcrm_age_running' );
} );

$cfg = aaa_fcrm_age_get_settings();
$brackets = aaa_fcrm_age_parse_brackets( $cfg['brackets'] );

// Ensure fields/tags exist (self-healing, merge-only)
aaa_fcrm_age_ensure_custom_fields( $brackets );
aaa_fcrm_age_ensure_tags( $brackets, $cfg );

// Build tag map (slug -> id)
$slug_to_id = array();
try {
    $tagApi = FluentCrmApi('tags');
    $rows   = $tagApi->getInstance()->get(['id','slug']);
    foreach ( $rows as $row ) {
        $slug_to_id[ is_array($row) ? $row['slug'] : $row->slug ] = (int)( is_array($row) ? $row['id'] : $row->id );
    }
} catch ( \Throwable $e ) {
    if ( AAA_FCRM_AGE_DEBUG ) error_log('[AAA-FCRM-AGE] Tag map load failed: '.$e->getMessage());
}

    $added = $removed = $processed = 0;

    $tz   = wp_timezone();
    $now  = new DateTimeImmutable( 'now', $tz );
    $medical      = sanitize_title( $cfg['medical_tag'] );
    $recreational = sanitize_title( $cfg['recreational_tag'] );
    $dob_missing  = sanitize_title( $cfg['dob_missing_tag'] );

    $all_bracket_slugs = array_map( function( $b ) use ( $cfg ) {
        return sanitize_title( $cfg['bracket_tag_slug_prefix'] . $b['label'] );
    }, $brackets );

    global $wpdb;
    $table = $wpdb->prefix . 'fc_subscribers';
    $last  = 0;
    $batch = 500;

    while ( true ) {
        $rows = $wpdb->get_results(
            $wpdb->prepare( "SELECT id, email, date_of_birth FROM {$table} WHERE id > %d ORDER BY id ASC LIMIT %d", $last, $batch ),
            ARRAY_A
        );
        if ( empty( $rows ) ) break;

        foreach ( $rows as $r ) {
            $processed++;
            $last = (int)$r['id'];

            $email = trim( (string)$r['email'] );
            $dob   = trim( (string)$r['date_of_birth'] );

            // compute age (whole years) or null
            $age = null;
            if ( $dob ) {
                try {
                    $d = new DateTimeImmutable( $dob, $tz );
                    if ( $d <= $now ) $age = (int) $d->diff( $now )->y;
                } catch ( \Throwable $e ) { $age = null; }
            }

            $attach_slugs = array();
            $detach_slugs = array();
            $custom_values = array();

            if ( null === $age ) {
                // Missing DOB
                $attach_slugs[] = $dob_missing;
                if ( ! empty( $cfg['strict_detach'] ) ) {
                    $detach_slugs = array_merge( $detach_slugs, array( $medical, $recreational ), $all_bracket_slugs );
                }
                $custom_values['age_years']   = '';
                $custom_values['age_bracket'] = '';
            } else {
                // Determine bracket (min inclusive, max exclusive; null max open-ended)
                $chosen_slug = ''; $chosen_label = '';
                foreach ( $brackets as $b ) {
                    if ( is_null( $b['max'] ) ) {
                        if ( $age >= $b['min'] ) { $chosen_slug = sanitize_title( $cfg['bracket_tag_slug_prefix'] . $b['label'] ); $chosen_label = $b['label']; break; }
                    } else {
                        if ( $age >= $b['min'] && $age < $b['max'] ) { $chosen_slug = sanitize_title( $cfg['bracket_tag_slug_prefix'] . $b['label'] ); $chosen_label = $b['label']; break; }
                    }
                }
                if ( $chosen_slug ) {
                    $attach_slugs[] = $chosen_slug;
                    if ( ! empty( $cfg['strict_detach'] ) ) {
                        $detach_slugs = array_merge( $detach_slugs, array_diff( $all_bracket_slugs, array( $chosen_slug ) ) );
                    }
                }

                // MEDICAL (18–20) vs RECREATIONAL (21+)
                if ( $age >= 21 ) {
                    $attach_slugs[] = $recreational;
                    if ( ! empty( $cfg['strict_detach'] ) ) $detach_slugs[] = $medical;
                } elseif ( $age >= 18 ) {
                    $attach_slugs[] = $medical;
                    if ( ! empty( $cfg['strict_detach'] ) ) $detach_slugs[] = $recreational;
                } else {
                    if ( ! empty( $cfg['strict_detach'] ) ) $detach_slugs = array_merge( $detach_slugs, array( $medical, $recreational ) );
                }

                if ( ! empty( $cfg['strict_detach'] ) ) $detach_slugs[] = $dob_missing;

                // Custom fields
                $custom_values['age_years']   = (string)$age;
                $custom_values['age_bracket'] = $chosen_label;
            }

            // normalize
            $attach_slugs = array_values(array_unique(array_diff($attach_slugs, $detach_slugs)));
            $detach_slugs = array_values(array_unique($detach_slugs));

            // Map to tag IDs
            $add_ids = array();
            foreach ($attach_slugs as $s) { 
	    if (isset($slug_to_id[$s])) $add_ids[] = $slug_to_id[$s]; }
            $rem_ids = array();
            foreach ($detach_slugs as $s) { if (isset($slug_to_id[$s])) $rem_ids[] = $slug_to_id[$s]; }

            try {
                $contactApi = FluentCrmApi('contacts');
                $subscriber = $contactApi->getContact((int)$r['id']);
                if (!$subscriber) continue;

                // SAFE MERGE: never wipe other custom_values keys
                if ($email) {
                    $existing = $subscriber->custom_values;
                    if (!is_array($existing)) {
                        $existing = json_decode((string)$existing, true);
                        if (!is_array($existing)) $existing = array();
                    }
                    foreach ($custom_values as $k => $v) {
                        $existing[$k] = $v;
                    }
                    $contactApi->createOrUpdate(array(
                        'email'         => $email,
                        'custom_values' => $existing
                    ));
                }

                if ($add_ids) { $subscriber->attachTags($add_ids); $added += count($add_ids); }
                if ($rem_ids) { $subscriber->detachTags($rem_ids); $removed += count($rem_ids); }

            } catch (\Throwable $e) {
                if (AAA_FCRM_AGE_DEBUG) error_log('[AAA-FCRM-AGE] #'.$r['id'].' error: '.$e->getMessage());
            }
        }

        if ( count( $rows ) < $batch ) break; // done
    }

    delete_transient( 'aaa_fcrm_age_running' );

    // Store summary for an admin notice
    set_transient( 'aaa_fcrm_age_last_run', array(
        'processed' => $processed,
        'tag_adds'  => $added,
        'tag_rems'  => $removed,
        'time'      => time()
    ), 3600 );

    if ( AAA_FCRM_AGE_DEBUG ) error_log('[AAA-FCRM-AGE] Done; processed='.$processed.' add='.$added.' rem='.$removed);
}

/** Friendly “last run” notice */
add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $s = get_transient( 'aaa_fcrm_age_last_run' );
    if ( ! $s ) return;
    delete_transient( 'aaa_fcrm_age_last_run' );
    echo '<div class="notice notice-success"><p><strong>Age Classifier:</strong> processed '
        .intval($s['processed']).' contacts; tags +'.intval($s['tag_adds']).' / -'.intval($s['tag_rems']).'.</p></div>';
});

/** Ensure custom fields exist (self-healing, flat options, select-one) */
function aaa_fcrm_age_ensure_custom_fields( $brackets ) {
    // --- HARDENED: merge-only, never remove other fields; also take a backup snapshot ---
    // Your site uses this key; keep it here so we never call saveGlobalFields.
    $opt_key = 'contact_custom_fields';

    $fields = get_option($opt_key);
    if (!is_array($fields)) $fields = [];

    // One-shot backup (keeps last snapshot) so you can roll back the registry if needed
    $bak_key = 'aaa_fcrm_age_backup_' . $opt_key;
    if (!get_option($bak_key)) {
        add_option($bak_key, $fields, '', false); // no autoload
    }

    // Index by slug
    $by = [];
    foreach ($fields as $f) {
        if (!empty($f['slug'])) $by[$f['slug']] = $f;
    }

    // Flat bracket options like ["18-21","21-30", ...]
    $flat = [];
    foreach ($brackets as $b) $flat[] = $b['label'];

    // Age (years) – merge/ensure only
    $by['age_years'] = isset($by['age_years']) ? array_merge($by['age_years'], [
        'slug'  => 'age_years',
        'label' => 'Age (years)',
        'type'  => 'number'
    ]) : [
        'slug'  => 'age_years',
        'label' => 'Age (years)',
        'type'  => 'number'
    ];

    // Age Bracket – force select-one + flat options; flatten if someone saved [{id,title}]
	$age['type'] = (isset($age['type']) && in_array($age['type'], array('select','select-one'), true))
	    ? $age['type']
	    : 'select-one';

    if (!empty($age['options']) && is_array($age['options']) && $age['options'] && is_array(reset($age['options']))) {
        $tmp=[]; foreach ($age['options'] as $o) { $tmp[] = is_array($o) ? ($o['title'] ?? ($o['id'] ?? '')) : (string)$o; }
        $age['options'] = array_values(array_filter($tmp));
    }
    if (empty($age['options'])) $age['options'] = $flat;

    $by['age_bracket'] = $age;

    // Write back: MERGE ONLY (we never drop unknown slugs)
    update_option($opt_key, array_values($by), false);
}

/** Ensure tags exist (MEDICAL, RECREATIONAL, DOB Missing, and bracket tags) */
function aaa_fcrm_age_ensure_tags( $brackets, $cfg ) {
    if ( ! function_exists( 'FluentCrmApi' ) ) return;

    $toCreate = array();

    $toCreate[] = array( 'title' => strtoupper( str_replace( '-', ' ', $cfg['medical_tag'] ) ), 'slug' => sanitize_title( $cfg['medical_tag'] ) );
    $toCreate[] = array( 'title' => strtoupper( str_replace( '-', ' ', $cfg['recreational_tag'] ) ), 'slug' => sanitize_title( $cfg['recreational_tag'] ) );
    $toCreate[] = array( 'title' => 'DOB Missing', 'slug' => sanitize_title( $cfg['dob_missing_tag'] ) );

    foreach ( $brackets as $b ) {
        $slug  = sanitize_title( $cfg['bracket_tag_slug_prefix'] . $b['label'] );
        $title = $cfg['bracket_tag_title_prefix'] . $b['label'];
        $toCreate[] = array( 'title' => $title, 'slug' => $slug );
    }

    try {
        $tagApi = FluentCrmApi('tags');
        $tagApi->importBulk( $toCreate ); // idempotent
    } catch ( \Throwable $e ) {
        if ( AAA_FCRM_AGE_DEBUG ) error_log('[AAA-FCRM-AGE] ensure tags: '.$e->getMessage());
    }
}

/** Parse bracket CSV → [ ['min'=>int, 'max'=>int|null, 'label'=>string], ... ] */
function aaa_fcrm_age_parse_brackets( $csv ) {
    $csv = (string)$csv;
    $pieces = array_filter( array_map( 'trim', explode( ',', $csv ) ) );
    $out = array();

    foreach ( $pieces as $p ) {
        if ( substr( $p, -1 ) === '+' ) {
            $min = (int) preg_replace( '/\D/', '', $p );
            $out[] = array( 'min' => $min, 'max' => null, 'label' => $min . '+' );
        } else {
            if ( ! preg_match( '/^\s*(\d+)\s*-\s*(\d+)\s*$/', $p, $m ) ) continue;
            $min = (int)$m[1]; $max = (int)$m[2];
            if ( $max <= $min ) continue;
            $out[] = array( 'min' => $min, 'max' => $max, 'label' => $min . '-' . $max );
        }
    }

    usort( $out, function( $a, $b ) { return $a['min'] <=> $b['min']; } );
    return $out;
}

/** WP-CLI: wp fcrm age classify */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'fcrm age classify', function () {
        aaa_fcrm_age_run();
        WP_CLI::success('Age classification run complete.');
    } );
}
