<?php
/**
 * Jobs Scheduler
 *
 * Handles scheduling of individual vector sync jobs.  Each job
 * corresponds to a custom post type entry and is scheduled based on
 * its configuration.  Events are created for the initial import and
 * recurring updates.  Scheduling is updated whenever a job is saved.
 */
class Vector_Sync_Jobs_Scheduler {
    /**
     * Hook into WordPress to register cron actions and schedule jobs
     * on activation.  Also unschedule on deactivation.
     */
    public static function init() {
        // Register the cron actions for job execution.  Activation/deactivation
        // hooks are registered in vector-sync.php to ensure they run during
        // plugin activation.
        add_action( 'vector_sync_initial_job', array( __CLASS__, 'run_job' ) );
        add_action( 'vector_sync_recurring_job', array( __CLASS__, 'run_job' ) );
        // When a job is deleted, remove its schedule and settings.
        add_action( 'before_delete_post', array( __CLASS__, 'on_delete_post' ) );
    }

    /**
     * On activation, ensure the jobs table exists and schedule all
     * existing jobs.
     */
    public static function activate() {
        // Create jobs table.
        Vector_Sync_Jobs_DB::create_table();
        // Schedule existing published jobs.
        $jobs = get_posts( array(
            'post_type'      => 'vector_sync_job',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ) );
        foreach ( $jobs as $job ) {
            $settings = Vector_Sync_Jobs_DB::get_job_settings( $job->ID );
            if ( ! empty( $settings ) ) {
                self::schedule_job( $job->ID, $settings );
            }
        }
    }

    /**
     * Unschedule all jobs on deactivation.
     */
    public static function deactivate() {
        $jobs = get_posts( array(
            'post_type'      => 'vector_sync_job',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        ) );
        foreach ( $jobs as $job ) {
            self::unschedule_job( $job->ID );
        }
    }

    /**
     * Called when a vector sync job post is deleted.  Ensures its
     * scheduled events are removed and DB settings cleaned up.
     *
     * @param int $post_id ID of the post being deleted.
     */
    public static function on_delete_post( $post_id ) {
        $post = get_post( $post_id );
        if ( $post && 'vector_sync_job' === $post->post_type ) {
            self::unschedule_job( $post_id );
            Vector_Sync_Jobs_DB::delete_job_settings( $post_id );
        }
    }

    /**
     * Schedule the given job based on its settings.  Any previously
     * scheduled events for this job are unscheduled first.  Events are
     * created for the initial import and the recurring updates.  If
     * schedule_time is empty, no events are scheduled.
     *
     * @param int   $job_id   Post ID of the job.
     * @param array $settings Job settings array.
     */
    public static function schedule_job( $job_id, array $settings ) {
        // Unschedule existing events.
        self::unschedule_job( $job_id );
        // Determine schedule time and recurrence.
        $time_str   = $settings['schedule_time'] ?? '';
        $recurrence = $settings['recurrence'] ?? 'hourly';
        if ( empty( $time_str ) ) {
            return;
        }
        // Compute next timestamp based on the selected time and local
        // timezone.  Use current day; if the time has already passed,
        // schedule for the next day.
        $now      = current_time( 'timestamp' );
        $date_str = date( 'Y-m-d', $now );
        $next     = strtotime( $date_str . ' ' . $time_str );
        if ( $next <= $now ) {
            $next = strtotime( '+1 day', $next );
        }
        // Schedule single event for initial import.
        if ( ! wp_next_scheduled( 'vector_sync_initial_job', array( $job_id ) ) ) {
            wp_schedule_single_event( $next, 'vector_sync_initial_job', array( $job_id ) );
        }
        // Schedule recurring events.  Recurring events start after the
        // initial import.  WordPress will run the event at the next
        // occurrence based on the recurrence.
        if ( in_array( $recurrence, array( 'hourly', 'twicedaily', 'daily' ), true ) ) {
            if ( ! wp_next_scheduled( 'vector_sync_recurring_job', array( $job_id ) ) ) {
                wp_schedule_event( $next, $recurrence, 'vector_sync_recurring_job', array( $job_id ) );
            }
        }
    }

    /**
     * Unschedule any events associated with the given job ID.  This
     * removes both initial and recurring events if they exist.
     *
     * @param int $job_id Job ID whose events should be unscheduled.
     */
    public static function unschedule_job( $job_id ) {
        // Unschedule initial event.
        $timestamp = wp_next_scheduled( 'vector_sync_initial_job', array( $job_id ) );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'vector_sync_initial_job', array( $job_id ) );
        }
        // Unschedule recurring event.
        $timestamp = wp_next_scheduled( 'vector_sync_recurring_job', array( $job_id ) );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'vector_sync_recurring_job', array( $job_id ) );
        }
    }

    /**
     * Run the job.  Called by cron.  Retrieves job settings and
     * performs the sync.  If the job specifies a new vector space
     * name, this method attempts to create the space first.  After
     * successful creation, the job settings are updated.
     *
     * @param int $job_id Post ID of the job.
     */
    public static function run_job( $job_id ) {
        $settings = Vector_Sync_Jobs_DB::get_job_settings( $job_id );
        if ( empty( $settings ) ) {
            return;
        }
        // If a new vector space name is provided and no existing id
        // chosen, attempt to create the space before syncing.
        if ( ! empty( $settings['new_vector_space_name'] ) && empty( $settings['vector_space'] ) ) {
            $api = new Vector_Sync_Api_Client();
            $create = $api->create_vector_space( $settings['service'], $settings['new_vector_space_name'] );
            if ( ! is_wp_error( $create ) && isset( $create['id'] ) ) {
                $settings['vector_space'] = $create['id'];
                // Save updated settings and reschedule future events to use
                // the new vector space.
                Vector_Sync_Jobs_DB::save_job_settings( $job_id, $settings );
                self::schedule_job( $job_id, $settings );
            } else {
                // Could not create space; abort this run.
                return;
            }
        }
        // Import data based on job configuration.  Data Manager will
        // handle statuses and date filters.  Use only the fields
        // relevant for import.
        $options = array(
            'service'      => $settings['service'],
            'vector_space' => $settings['vector_space'],
            'post_types'   => array( $settings['post_type'] ),
            'meta_fields'  => $settings['meta_fields'],
            'statuses'     => $settings['statuses'],
            'start_date'   => $settings['start_date'],
            'schedule_time'=> $settings['schedule_time'],
            'recurrence'   => $settings['recurrence'],
        );
        // For orders, we use order_start_date as start date.
        if ( in_array( $settings['post_type'], array( 'shop_order', 'shop_order_refund' ), true ) ) {
            $options['start_date'] = $settings['order_start_date'];
        }
        $manager = new Vector_Sync_Data_Manager();
        $manager->import_existing_data( $options );
    }
}