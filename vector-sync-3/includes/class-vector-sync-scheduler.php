<?php
/**
 * Scheduler class
 *
 * Handles registration of recurring and one‑time synchronisation events using
 * WordPress’s Cron API.  Scheduling is kept in a dedicated class to satisfy
 * the “wide & thin” architecture, ensuring this file stays focused on cron
 * functionality.  For details on WP Cron see the Plugin Handbook【357505950125911†L69-L140】.
 */
class Vector_Sync_Scheduler {
    /**
     * Names of the custom cron hooks.  These hooks are used when
     * registering events and when binding callback functions.
     */
    const HOOK_INITIAL   = 'vector_sync_initial_import';
    const HOOK_RECURRING = 'vector_sync_recurring_sync';

    /**
     * Set up cron schedules on activation.  We check whether a hook is
     * already scheduled using `wp_next_scheduled()`【357505950125911†L98-L139】 to prevent
     * duplicate events when a plugin is reactivated multiple times.
     */
    public static function activate() {
        // Default initial import schedule: run once ten minutes after
        // activation.  Developers can later reschedule via the settings page.
        if ( ! wp_next_scheduled( self::HOOK_INITIAL ) ) {
            wp_schedule_single_event( time() + 600, self::HOOK_INITIAL );
        }

        // Recurring sync every hour by default.  Users can customise this
        // per service via the settings page.  We schedule a single recurring
        // hook which will iterate through each configured service at run time.
        if ( ! wp_next_scheduled( self::HOOK_RECURRING ) ) {
            wp_schedule_event( time() + 3600, 'hourly', self::HOOK_RECURRING );
        }
    }

    /**
     * Unschedule all cron events on deactivation.  It’s important to clean up
     * scheduled hooks when the plugin is disabled【357505950125911†L166-L170】.
     */
    public static function deactivate() {
        $timestamp = wp_next_scheduled( self::HOOK_INITIAL );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::HOOK_INITIAL );
        }
        $timestamp = wp_next_scheduled( self::HOOK_RECURRING );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::HOOK_RECURRING );
        }
    }

    /**
     * Callback for when the settings are updated.  Unschedules existing cron
     * events and reschedules them according to the user’s preferences.
     *
     * @param mixed $old_value Previous value (unused).
     * @param mixed $value     New value from the settings page.
     */
    public static function settings_updated( $old_value, $value ) {
        // When settings change we reschedule our cron events.  We only
        // schedule a single initial import and a single recurring hook.  The
        // hook handlers will iterate through each service’s schedule and
        // decide whether to run.  We derive the earliest initial import time
        // from both services.
        self::deactivate();
        // Determine the soonest initial import time across services.  Fall
        // back to now + 5 minutes if no times are set.
        $earliest = time() + 300;
        foreach ( array( 'pinecone', 'openai' ) as $service ) {
            if ( ! empty( $value[ $service ]['schedule_time'] ) ) {
                $time_parts = explode( ':', $value[ $service ]['schedule_time'] );
                if ( count( $time_parts ) === 2 ) {
                    $hours   = (int) $time_parts[0];
                    $minutes = (int) $time_parts[1];
                    $next    = mktime( $hours, $minutes, 0 );
                    if ( $next <= time() ) {
                        $next = strtotime( '+1 day', $next );
                    }
                    if ( $next < $earliest ) {
                        $earliest = $next;
                    }
                }
            }
        }
        // Schedule a one‑off initial import at the earliest time.
        if ( ! wp_next_scheduled( self::HOOK_INITIAL ) ) {
            wp_schedule_single_event( $earliest, self::HOOK_INITIAL );
        }
        // Determine the shortest recurrence interval across services.  Map
        // recurrence names to seconds to compare durations.
        $intervals = array(
            'hourly'     => 3600,
            'twicedaily' => 12 * 3600,
            'daily'      => 24 * 3600,
        );
        $rec_seconds = 3600; // default hourly
        foreach ( array( 'pinecone', 'openai' ) as $service ) {
            $rec = $value[ $service ]['recurrence'] ?? 'hourly';
            if ( isset( $intervals[ $rec ] ) && $intervals[ $rec ] < $rec_seconds ) {
                $rec_seconds = $intervals[ $rec ];
            }
        }
        // Choose the appropriate schedule slug based on seconds.  WordPress
        // provides hourly, twicedaily and daily intervals by default.
        $recurrence_slug = 'hourly';
        foreach ( $intervals as $slug => $secs ) {
            if ( $secs === $rec_seconds ) {
                $recurrence_slug = $slug;
                break;
            }
        }
        if ( ! wp_next_scheduled( self::HOOK_RECURRING ) ) {
            wp_schedule_event( time() + 600, $recurrence_slug, self::HOOK_RECURRING );
        }
    }

    /**
     * Run the initial import.  This method should fetch existing posts/orders
     * and push them into the selected vector store.  It’s hooked to
     * `vector_sync_initial_import` via add_action at the bottom of this file.
     * You can set the schedule via the settings page; here we simply call
     * the Data Manager.
     */
    public static function handle_initial_import() {
        // Retrieve configuration from the custom table.
        $options = Vector_Sync_DB::get_settings();
        $manager = new Vector_Sync_Data_Manager();
        // Iterate through each service and perform import if configured.
        foreach ( array( 'pinecone', 'openai' ) as $service ) {
            if ( empty( $options[ $service ]['vector_space'] ) || empty( $options[ $service ]['post_types'] ) ) {
                continue;
            }
            // Construct a flat options array expected by the Data Manager.
            $svc_options = array(
                'service'      => $service,
                'vector_space' => $options[ $service ]['vector_space'],
                'post_types'   => $options[ $service ]['post_types'],
                'meta_fields'  => $options[ $service ]['meta_fields'] ?? array(),
                'start_date'   => $options[ $service ]['start_date'] ?? '',
            );
            $manager->import_existing_data( $svc_options );
        }
    }

    /**
     * Run the recurring sync.  This method runs on a schedule to send
     * incremental updates.  It typically synchronises new or modified posts
     * since the last run.
     */
    public static function handle_recurring_sync() {
        // Retrieve configuration from the custom table and iterate through
        // services to run incremental syncs.  Services without a vector space
        // configured are skipped.
        $options  = Vector_Sync_DB::get_settings();
        $manager  = new Vector_Sync_Data_Manager();
        foreach ( array( 'pinecone', 'openai' ) as $service ) {
            if ( empty( $options[ $service ]['vector_space'] ) || empty( $options[ $service ]['post_types'] ) ) {
                continue;
            }
            $svc_options = array(
                'service'      => $service,
                'vector_space' => $options[ $service ]['vector_space'],
                'post_types'   => $options[ $service ]['post_types'],
                'meta_fields'  => $options[ $service ]['meta_fields'] ?? array(),
                'start_date'   => $options[ $service ]['start_date'] ?? '',
            );
            $manager->sync_recent_changes( $svc_options );
        }
    }
}

// Register callbacks for the cron hooks.  These calls must occur in global
// scope so WordPress can find them when executing scheduled events.
add_action( Vector_Sync_Scheduler::HOOK_INITIAL,   array( 'Vector_Sync_Scheduler', 'handle_initial_import' ) );
add_action( Vector_Sync_Scheduler::HOOK_RECURRING, array( 'Vector_Sync_Scheduler', 'handle_recurring_sync' ) );