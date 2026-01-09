<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/inc/hooks/class-aaa-oc-payconfirm-triggers.php
 * Purpose: Auto-trigger PayConfirm parsing/matching + guaranteed publish + backlog repair.
 * Notes:
 *  - Immediate process on: postie_post_after, wp_after_insert_post, transition_post_status.
 *  - No cron dependency: we invoke the processor synchronously from all hooks.
 *  - After processing, we force-publish (wp_publish_post with fallbacks).
 *  - Lightweight backlog repair runs at most once per 60s in admin/front.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AAA_OC_PayConfirm_Triggers' ) ) :

class AAA_OC_PayConfirm_Triggers {
    /**
     * Local debug flag removed. Logging will now defer to global constant `AAA_OC_PAYCONFIRM_DEBUG`.
     */
    // const DEBUG_THIS_FILE = true;

    /**
     * Initialize all hooks.
     */
    public static function init() {
        // 0) Hard rule: payconfirm posts must never be public.
        add_filter( 'wp_insert_post_data', [ __CLASS__, 'force_private_on_insert' ], 10, 2 );

        // 1) Primary: when Postie finishes creating the post
        add_action( 'postie_post_after', [ __CLASS__, 'process_from_postie' ], 20, 1 );

        // 2) Fallbacks for any creation path
        add_action( 'wp_after_insert_post',   [ __CLASS__, 'process_from_insert' ], 20, 4 );
        add_action( 'transition_post_status', [ __CLASS__, 'process_from_status' ], 20, 3 );

        // 3) Shared processor (also used by "Process Now" admin row action)
        add_action( 'aaa_oc_pc_process_post', [ __CLASS__, 'process_post' ], 10, 1 );

        // 4) Backlog repair (runs at most once per 60s)
        // Note: Removed automatic backlog repair hooks. If backfilling is desired, trigger `auto_repair_backlog` manually via a custom action.
        // add_action( 'admin_init', [ __CLASS__, 'auto_repair_backlog' ] );
        // add_action( 'wp',         [ __CLASS__, 'auto_repair_backlog' ] );
    }

    /**
     * Enforce private status at insert/update time (prevents any “publish window”).
     *
     * @param array $data    Post data to be inserted.
     * @param array $postarr Raw post array.
     *
     * @return array Modified post data.
     */
    public static function force_private_on_insert( $data, $postarr ) {
        $pt = $data['post_type'] ?? ( $postarr['post_type'] ?? '' );
        if ( 'payment-confirmation' !== $pt ) {
            return $data;
        }

        $st = $data['post_status'] ?? '';
        if ( in_array( $st, [ 'trash', 'auto-draft' ], true ) ) {
            return $data;
        }

        if ( 'private' !== $st ) {
            $data['post_status'] = 'private';
            self::log( 'Insert filter: forced private (was ' . $st . ')' );
        }
        return $data;
    }

    /* ---------- IMMEDIATE HOOKS (no cron) ---------- */

    /**
     * Process a post when Postie finishes creating it.
     *
     * @param mixed $payload Post ID or array from Postie.
     */
    public static function process_from_postie( $payload ) {
        $post_id = 0;
        if ( is_numeric( $payload ) ) {
            $post_id = (int) $payload;
        } elseif ( is_array( $payload ) ) {
            if ( ! empty( $payload['ID'] ) ) {
                $post_id = (int) $payload['ID'];
            } elseif ( ! empty( $payload['post_id'] ) ) {
                $post_id = (int) $payload['post_id'];
            }
        }
        if ( ! $post_id ) {
            self::log( 'Postie after: no post id' );
            return;
        }
        if ( ! self::is_payconfirm( $post_id ) ) {
            return;
        }
        if ( self::already_done( $post_id ) ) {
            return;
        }

        self::log( 'Postie after: process now', $post_id );
        self::process_post( $post_id );
    }

    /**
     * Process a post after insert.
     *
     * @param int     $post_id    Post ID.
     * @param WP_Post $post       Post object.
     * @param bool    $update     Whether this is an existing post being updated.
     * @param WP_Post $post_before Post object before the update.
     */
    public static function process_from_insert( $post_id, $post, $update, $post_before ) {
        if ( ! self::is_payconfirm( $post_id, $post ) ) {
            return;
        }
        if ( self::already_done( $post_id ) ) {
            return;
        }
        self::log( 'After insert: process now', $post_id );
        self::process_post( $post_id );
    }

    /**
     * Process a post when status transitions.
     *
     * @param string  $new  New status.
     * @param string  $old  Old status.
     * @param WP_Post $post Post object.
     */
    public static function process_from_status( $new, $old, $post ) {
        if ( ! ( $post instanceof WP_Post ) ) {
            return;
        }
        if ( ! self::is_payconfirm( $post->ID, $post ) ) {
            return;
        }
        if ( self::already_done( $post->ID ) ) {
            return;
        }
        self::log( "Status {$old}→{$new}: process now", $post->ID );
        self::process_post( $post->ID );
    }

    /**
     * Check if a post is a payment confirmation and should be processed.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Optional post object.
     *
     * @return bool Whether this is a payment-confirmation post to process.
     */
    protected static function is_payconfirm( $post_id, $post = null ) {
        $p = $post ?: get_post( $post_id );
        if ( ! $p || 'payment-confirmation' !== $p->post_type ) {
            return false;
        }
        if ( in_array( $p->post_status, [ 'auto-draft', 'trash' ], true ) ) {
            return false;
        }
        return true;
    }

    /**
     * Determine whether a post has already been processed.
     *
     * @param int $post_id Post ID.
     *
     * @return bool Whether processing is already done for this post.
     */
    protected static function already_done( $post_id ) {
        // if we already have a matched order or processed flag, skip
        if ( get_post_meta( $post_id, '_pc_matched_order_id', true ) ) {
            return true;
        }
        $proc = get_post_meta( $post_id, '_pc_processed', true );
        return ( 'done' === $proc );
    }

    /* ---------- CORE PROCESSOR ---------- */

    /**
     * Core processor: parse and match payment confirmation posts.
     *
     * @param int $post_id Post ID.
     */
    public static function process_post( $post_id ) {
        self::log( 'Processor start', $post_id );

        $post = get_post( $post_id );
        if ( ! $post ) {
            self::log( 'Abort: missing post', $post_id );
            return;
        }

        // mark in-progress (guards re-entry)
        update_post_meta( $post_id, '_pc_processed', 'in_progress' );

        // Parse (post-aware if available)
        if ( ! class_exists( 'AAA_OC_PayConfirm_Parser' ) ) {
            self::log( 'Abort: missing parser', $post_id );
            return;
        }
        $fields = method_exists( 'AAA_OC_PayConfirm_Parser', 'parse_post' )
            ? AAA_OC_PayConfirm_Parser::parse_post( $post_id )
            : AAA_OC_PayConfirm_Parser::parse( (string) $post->post_content, (string) $post->post_title );

        if ( empty( $fields ) || ! is_array( $fields ) ) {
            update_post_meta( $post_id, '_pc_processed', 'empty' );
            self::log( 'Abort: parser returned empty', $post_id );
            return;
        }

        // normalize sent_on to original email time
        $pd_gmt = get_post_field( 'post_date_gmt', $post_id );
        if ( $pd_gmt ) {
            $fields['sent_on'] = gmdate( 'Y-m-d H:i:s', strtotime( $pd_gmt ) );
        }

        // persist parsed metas + retitle
        update_post_meta( $post_id, '_pc_payment_method', $fields['payment_method']     ?? '' );
        update_post_meta( $post_id, '_pc_account_name',   $fields['account_name']       ?? '' );
        update_post_meta( $post_id, '_pc_amount',         $fields['amount']             ?? '' );
        update_post_meta( $post_id, '_pc_sent_on',        $fields['sent_on']            ?? '' );
        update_post_meta( $post_id, '_pc_txn',            $fields['transaction_number'] ?? '' );
        update_post_meta( $post_id, '_pc_memo',           $fields['memo']               ?? '' );

        if ( method_exists( 'AAA_OC_PayConfirm_Parser', 'title' ) ) {
            $new_title = AAA_OC_PayConfirm_Parser::title( $fields );
            if ( $new_title && $new_title !== $post->post_title ) {
                wp_update_post( [ 'ID' => $post_id, 'post_title' => $new_title ] );
            }
        }

        // match
        if ( ! class_exists( 'AAA_OC_PayConfirm_Matcher' ) ) {
            self::log( 'Abort: missing matcher', $post_id );
            update_post_meta( $post_id, '_pc_processed', 'done' );
            self::force_publish( $post_id ); // still force private
            return;
        }
        $result = AAA_OC_PayConfirm_Matcher::attempt( $post_id, $fields );
        update_post_meta( $post_id, '_pc_last_match_result', $result );

        $matched    = ! empty( $result['matched'] );
        $candidates = ! empty( $result['candidates'] );
        $status     = $matched ? 'matched' : ( $candidates ? 'partial' : 'unmatched' );
        $reason     = isset( $result['method'] ) ? (string) $result['method'] : ( $candidates ? 'amount_multi' : 'name_fuzzy' );
        $confidence = isset( $result['confidence'] ) ? (float) $result['confidence'] : ( $matched ? 1.0 : ( $candidates ? 0.6 : 0.4 ) );

        update_post_meta( $post_id, '_pc_match_status',     $status );
        update_post_meta( $post_id, '_pc_match_reason',     $reason );
        update_post_meta( $post_id, '_pc_match_confidence', $confidence );
        if ( $matched && ! empty( $result['order_id'] ) ) {
            update_post_meta( $post_id, '_pc_matched_order_id', (int) $result['order_id'] );
        }
        update_post_meta( $post_id, '_pc_match_method', $reason );

        update_post_meta( $post_id, '_pc_processed', 'done' );

        // force private (covers legacy “publish” behavior)
        self::force_publish( $post_id );

        self::log( "Processor end (status={$status}, reason={$reason}, conf={$confidence})", $post_id );
    }

    /**
     * Force PRIVATE helper (keeps legacy method name; behavior is “never public”).
     *
     * @param int $post_id Post ID.
     */
    protected static function force_publish( $post_id ) {
        $p = get_post( $post_id );
        if ( ! $p ) {
            return;
        }
        if ( 'private' === $p->post_status ) {
            return;
        }

        $upd = wp_update_post( [ 'ID' => $post_id, 'post_status' => 'private' ], true );
        if ( is_wp_error( $upd ) ) {
            self::log( 'Force private failed: ' . $upd->get_error_message(), $post_id );
            return;
        }
        self::log( 'Forced to private via wp_update_post()', $post_id );
    }

    /* ---------- BACKLOG REPAIR ---------- */

    /**
     * Repair backlog: ensure all payment confirmations are private and processed.
     */
    public static function auto_repair_backlog() {
        // run at most once per 60s
        if ( get_transient( 'aaa_oc_pc_repair_lock' ) ) {
            return;
        }
        set_transient( 'aaa_oc_pc_repair_lock', 1, 60 );

        // 0) force-private any accidentally published payconfirms
        $published = get_posts( [
            'post_type'      => 'payment-confirmation',
            'post_status'    => [ 'publish' ],
            'posts_per_page' => 10,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ] );
        foreach ( $published as $pid ) {
            self::log( 'Repair: forcing private for published post', (int) $pid );
            self::force_publish( (int) $pid );
        }

        // 1) process up to 10 items that never got processed
        $unproc = get_posts( [
            'post_type'      => 'payment-confirmation',
            'post_status'    => [ 'draft', 'pending', 'private' ],
            'posts_per_page' => 10,
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'     => '_pc_processed',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => '_pc_processed',
                    'value'   => [ '', 'in_progress', 'empty' ],
                    'compare' => 'IN',
                ],
            ],
            'orderby' => 'date',
            'order'   => 'DESC',
            'fields'  => 'ids',
        ] );
        foreach ( $unproc as $pid ) {
            self::log( 'Repair: processing unprocessed item', (int) $pid );
            self::process_post( (int) $pid );
        }

        // 2) force-private up to 10 already-processed non-private items
        $proc_items = get_posts( [
            'post_type'      => 'payment-confirmation',
            'post_status'    => [ 'draft', 'pending', 'private', 'publish' ],
            'posts_per_page' => 10,
            'meta_key'       => '_pc_processed',
            'meta_value'     => 'done',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ] );
        foreach ( $proc_items as $pid ) {
            self::log( 'Repair: forcing private for processed item', (int) $pid );
            self::force_publish( (int) $pid );
        }
    }

    /* ---------- utils ---------- */

    /**
     * Log debug messages if enabled.
     *
     * @param string $msg     Message to log.
     * @param int    $post_id Optional post ID for context.
     */
    protected static function log( $msg, $post_id = 0 ) {
        // Only log when the global debug constant is defined and truthy.
        if ( defined( 'AAA_OC_PAYCONFIRM_DEBUG' ) && AAA_OC_PAYCONFIRM_DEBUG ) {
            $ctx = $post_id ? " post={$post_id}" : '';
            error_log( '[AAA-OC][PayConfirm][TRIGGER]' . $ctx . ' ' . $msg );
        }
    }
}
endif;

// boot
AAA_OC_PayConfirm_Triggers::init();