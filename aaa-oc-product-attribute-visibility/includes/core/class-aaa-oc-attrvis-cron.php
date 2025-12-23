<?php
/**
 * File: /aaa-oc-product-attribute-visibility/includes/core/class-aaa-oc-attrvis-cron.php
 * Purpose: Handles asynchronous processing via WP Cron for large fix jobs.
 *
 * WordPress requests can time out when working with many products. To avoid
 * resource issues, this class schedules and runs incremental cron jobs.  For
 * example, the “Fix all” button on the settings page kicks off a cron job
 * that processes one page of products at a time. If there are more pages to
 * scan, the cron job schedules itself again for the next page.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'AAA_OC_ATTRVIS_DEBUG_CRON' ) ) {
    define( 'AAA_OC_ATTRVIS_DEBUG_CRON', true );
}

class AAA_OC_AttrVis_Cron {
    /**
     * Name of the cron hook used for incremental fix‑all jobs.
     */
    const HOOK_FIX_ALL_PAGE = 'aaa_oc_attrvis_fix_all_page';

    /**
     * Name of the cron hook used for bulk fix of explicit product IDs.
     */
    const HOOK_FIX_IDS = 'aaa_oc_attrvis_fix_ids';

    /**
     * Option key used to store the last completed job summary.
     */
    const OPTION_LAST_RESULT = 'aaa_oc_attrvis_last_result';

    /**
     * Option key used to track progress of the ongoing fix‑all job.
     */
    const OPTION_PROGRESS = 'aaa_oc_attrvis_fix_all_progress';

    /**
     * Initialize cron hook callbacks.
     */
    public static function init() {
        add_action( self::HOOK_FIX_ALL_PAGE, array( __CLASS__, 'run_fix_all_page' ), 10, 3 );
        add_action( self::HOOK_FIX_IDS, array( __CLASS__, 'run_fix_ids' ), 10, 1 );
    }

    /**
     * Kick off a fix‑all job by scheduling the first page.  
     * This helper is invoked from the admin when the user clicks “Fix all”.
     *
     * @param int $category Optional category filter (0 for all categories).
     * @param int $batch    Optional batch size per page.
     */
    public static function schedule_fix_all( $category = 0, $batch = 50 ) {
        // Avoid duplicating jobs: check if progress is already stored.
        if ( get_option( self::OPTION_PROGRESS ) ) {
            return;
        }
        $progress = array(
            'checked'          => 0,
            'products_updated' => 0,
            'rows_changed'     => 0,
            'category'         => (int) $category,
            'batch'            => (int) $batch,
            'next_paged'       => 1,
            'started_at'       => time(),
        );
        update_option( self::OPTION_PROGRESS, $progress );
        // Schedule the first page to run as soon as possible.
        wp_schedule_single_event( time(), self::HOOK_FIX_ALL_PAGE, array( $category, 1, $batch ) );
        if ( AAA_OC_ATTRVIS_DEBUG_CRON ) {
            error_log( '[AAA_OC_ATTRVIS][cron] Scheduled fix‑all job. category=' . $category . ' batch=' . $batch );
        }
    }

    /**
     * Cron callback to process a single page of products for the fix‑all job.
     *
     * @param int $category Category term ID to filter (0 means all categories).
     * @param int $paged    Page index (1‑based) for this run.
     * @param int $batch    Number of products to process in this run.
     */
    public static function run_fix_all_page( $category = 0, $paged = 1, $batch = 50 ) {
        // Fetch progress to accumulate counts.
        $progress = get_option( self::OPTION_PROGRESS );
        if ( ! is_array( $progress ) ) {
            // Progress could be missing if job finished or was cleared.
            return;
        }
        // Run a single batch.
        $res = AAA_OC_AttrVis_Fixer::run_batch( $batch, $paged, $category, false );
        // Update cumulative counts.
        $progress['checked']          += (int) $res['checked'];
        $progress['products_updated'] += (int) $res['products_updated'];
        $progress['rows_changed']     += (int) $res['rows_changed'];
        $progress['next_paged']        = (int) $res['next_paged'];
        update_option( self::OPTION_PROGRESS, $progress );
        if ( AAA_OC_ATTRVIS_DEBUG_CRON ) {
            error_log( '[AAA_OC_ATTRVIS][cron] fix‑all page processed. paged=' . $paged . ' checked=' . $res['checked'] . ' updated=' . $res['products_updated'] . ' rows=' . $res['rows_changed'] . ' has_more=' . ( $res['has_more'] ? '1' : '0' ) );
        }
        // If there are more pages to process, schedule the next page.
        if ( ! empty( $res['has_more'] ) ) {
            wp_schedule_single_event( time() + 10, self::HOOK_FIX_ALL_PAGE, array( $category, $res['next_paged'], $batch ) );
        } else {
            // Job complete: move summary to last result and clear progress.
            $result = array(
                'type'             => 'all',
                'checked'          => (int) $progress['checked'],
                'products_updated' => (int) $progress['products_updated'],
                'rows_changed'     => (int) $progress['rows_changed'],
                'timestamp'        => time(),
            );
            update_option( self::OPTION_LAST_RESULT, $result );
            delete_option( self::OPTION_PROGRESS );
            if ( AAA_OC_ATTRVIS_DEBUG_CRON ) {
                error_log( '[AAA_OC_ATTRVIS][cron] fix‑all job complete. checked=' . $result['checked'] . ' updated=' . $result['products_updated'] . ' rows=' . $result['rows_changed'] );
            }
        }
    }

    /**
     * Schedule a cron job to fix a list of explicit product IDs.
     *
     * @param array $ids Product post IDs to fix.
     */
    public static function schedule_fix_ids( $ids ) {
        // Ensure array values are integers.
        $ids = array_map( 'intval', (array) $ids );
        // Schedule immediately; the list of IDs is passed as a single argument.
        wp_schedule_single_event( time(), self::HOOK_FIX_IDS, array( $ids ) );
        if ( AAA_OC_ATTRVIS_DEBUG_CRON ) {
            error_log( '[AAA_OC_ATTRVIS][cron] Scheduled fix for IDs: ' . implode( ',', $ids ) );
        }
    }

    /**
     * Cron callback to fix a list of explicit product IDs.
     *
     * @param array $ids List of product IDs to fix.
     */
    public static function run_fix_ids( $ids ) {
        if ( empty( $ids ) || ! is_array( $ids ) ) {
            return;
        }
        $res = AAA_OC_AttrVis_Fixer::fix_by_ids( $ids, false );
        // Store the result so an admin notice can display it.
        $result = array(
            'type'             => 'ids',
            'checked'          => (int) $res['checked'],
            'products_updated' => (int) $res['products_updated'],
            'rows_changed'     => (int) $res['rows_changed'],
            'timestamp'        => time(),
        );
        update_option( self::OPTION_LAST_RESULT, $result );
        if ( AAA_OC_ATTRVIS_DEBUG_CRON ) {
            error_log( '[AAA_OC_ATTRVIS][cron] fix‑ids job complete. checked=' . $res['checked'] . ' updated=' . $res['products_updated'] . ' rows=' . $res['rows_changed'] );
        }
    }
}