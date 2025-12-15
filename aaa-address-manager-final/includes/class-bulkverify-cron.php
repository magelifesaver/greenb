<?php
/**
 * Admin actions for running queued address verifications.
 *
 * This small component exposes two admin‑post handlers: one to run a single
 * batch of queued jobs and another to run all queued jobs in one go. It
 * delegates the actual processing to the core class and handles
 * scheduling/rescheduling. Both actions require the current user to have
 * manage_options capability and validate a nonce for security.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_BulkVerify_Cron {
    /**
     * Hook the admin post actions for run now and run all.
     */
    public static function init() : void {
        add_action( 'admin_post_aaa_am_run_now', [ __CLASS__, 'run_now' ] );
        add_action( 'admin_post_aaa_am_run_all', [ __CLASS__, 'run_all' ] );
    }

    /**
     * Process a single batch of queued jobs. After processing, schedule the
     * next batch if jobs remain. Redirect back to the queue page.
     */
    public static function run_now() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permission denied' );
        }
        check_admin_referer( 'aaa_am_run_now' );
        AAA_BulkVerify_Core::process_batch();
        // If jobs remain and no cron is scheduled, schedule the next run.
        if ( AAA_BulkVerify_Core::count_remaining_jobs() > 0 && ! wp_next_scheduled( AAA_BulkVerify_Core::CRON_HOOK ) ) {
            wp_schedule_single_event( time() + AAA_BulkVerify_Core::RESCHEDULE_SECONDS, AAA_BulkVerify_Core::CRON_HOOK );
        }
        wp_safe_redirect( admin_url( 'options-general.php?page=aaa-am-queue&ran=1' ) );
        exit;
    }

    /**
     * Continuously process batches until the queue is empty. Uses the core
     * processor in a loop. Redirects back with a ranall flag.
     */
    public static function run_all() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permission denied' );
        }
        check_admin_referer( 'aaa_am_run_all' );
        /*
         * Trigger processing for all queued jobs without blocking the current request.
         * We schedule an immediate cron event if none is pending. Subsequent batches
         * will be scheduled by the core processor until the queue empties. We avoid
         * making a remote HTTP request to wp‑cron.php here because some hosts block
         * self‑requests or return a 500 error. Instead, WordPress will fire the
         * scheduled event on the next page load or via a real system cron job.
         */
        if ( ! wp_next_scheduled( AAA_BulkVerify_Core::CRON_HOOK ) ) {
            wp_schedule_single_event( time() + 5, AAA_BulkVerify_Core::CRON_HOOK );
        }
        // Optionally trigger cron processing immediately if the function exists. This
        // will process a single batch synchronously and schedule the next one.
        if ( function_exists( 'wp_cron' ) ) {
            // Suppress any output and errors. wp_cron() returns false on failure.
            @wp_cron();
        }
        wp_safe_redirect( admin_url( 'options-general.php?page=aaa-am-queue&ranall=1' ) );
        exit;
    }
}