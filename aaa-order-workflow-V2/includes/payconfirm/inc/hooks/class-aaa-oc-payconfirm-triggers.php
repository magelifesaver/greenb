<?php
/**
 * Purpose: Auto-trigger PayConfirm parsing/matching + guaranteed publish + backlog repair.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'AAA_OC_PayConfirm_Triggers' ) ) :
class AAA_OC_PayConfirm_Triggers {

	const DEBUG_THIS_FILE = true;

	public static function init() {
		add_action( 'postie_post_after', [ __CLASS__, 'process_from_postie' ], 20, 1 );
		add_action( 'wp_after_insert_post',   [ __CLASS__, 'process_from_insert' ], 20, 4 );
		add_action( 'transition_post_status', [ __CLASS__, 'process_from_status' ], 20, 3 );
		add_action( 'aaa_oc_pc_process_post', [ __CLASS__, 'process_post' ], 10, 1 );
		add_action( 'admin_init', [ __CLASS__, 'auto_repair_backlog' ] );
		add_action( 'wp',         [ __CLASS__, 'auto_repair_backlog' ] );
	}

	/* ---------- IMMEDIATE HOOKS (no cron) ---------- */

	public static function process_from_postie( $payload ) {
		$post_id = 0;
		if ( is_numeric( $payload ) ) {
			$post_id = (int) $payload;
		} elseif ( is_array( $payload ) ) {
			if ( ! empty( $payload['ID'] ) )          $post_id = (int) $payload['ID'];
			elseif ( ! empty( $payload['post_id'] ) ) $post_id = (int) $payload['post_id'];
		}
		if ( ! $post_id ) { self::log('Postie after: no post id'); return; }
		if ( ! self::is_payconfirm( $post_id ) ) return;
		if ( self::already_done( $post_id ) )     return;

		self::log( 'Postie after: process now', $post_id );
		self::process_post( $post_id );
	}

	public static function process_from_insert( $post_id, $post, $update, $post_before ) {
		if ( ! self::is_payconfirm( $post_id, $post ) ) return;
		if ( self::already_done( $post_id ) )          return;
		self::log( 'After insert: process now', $post_id );
		self::process_post( $post_id );
	}

	public static function process_from_status( $new, $old, $post ) {
		if ( ! ( $post instanceof WP_Post ) )          return;
		if ( ! self::is_payconfirm( $post->ID, $post ) ) return;
		if ( self::already_done( $post->ID ) )           return;
		self::log( "Status {$old}→{$new}: process now", $post->ID );
		self::process_post( $post->ID );
	}

	protected static function is_payconfirm( $post_id, $post = null ) {
		$p = $post ?: get_post( $post_id );
		if ( ! $p || $p->post_type !== 'payment-confirmation' ) return false;
		if ( in_array( $p->post_status, [ 'auto-draft', 'trash' ], true ) )   return false;
		return true;
	}

	protected static function already_done( $post_id ) {
		// if we already have a matched order or processed flag, skip
		if ( get_post_meta( $post_id, '_pc_matched_order_id', true ) ) return true;
		$proc = get_post_meta( $post_id, '_pc_processed', true );
		return ( $proc === 'done' );
	}

	/* ---------- CORE PROCESSOR ---------- */

	public static function process_post( $post_id ) {
		self::log( 'Processor start', $post_id );

		$post = get_post( $post_id );
		if ( ! $post ) { self::log( 'Abort: missing post', $post_id ); return; }

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
		if ( $pd_gmt ) $fields['sent_on'] = gmdate( 'Y-m-d H:i:s', strtotime( $pd_gmt ) );

		// persist parsed metas + retitle
		update_post_meta( $post_id, '_pc_payment_method',     $fields['payment_method']     ?? '' );
		update_post_meta( $post_id, '_pc_account_name',       $fields['account_name']       ?? '' );
		update_post_meta( $post_id, '_pc_amount',             $fields['amount']             ?? '' );
		update_post_meta( $post_id, '_pc_sent_on',            $fields['sent_on']            ?? '' );
		update_post_meta( $post_id, '_pc_txn',                $fields['transaction_number'] ?? '' );
		update_post_meta( $post_id, '_pc_memo',               $fields['memo']               ?? '' );

		if ( method_exists( 'AAA_OC_PayConfirm_Parser', 'title' ) ) {
			$new_title = AAA_OC_PayConfirm_Parser::title( $fields );
			if ( $new_title && $new_title !== $post->post_title ) {
				wp_update_post( [ 'ID' => $post_id, 'post_title' => $new_title ] );
			}
		}

		// NEW: Upsert inbox row at parse time
		if ( method_exists( 'AAA_OC_PC_Apply', 'upsert_pc_inbox' ) ) {
			AAA_OC_PC_Apply::upsert_pc_inbox( $post_id, $fields, 'parsed', null, null );
		}

		if ( ! class_exists( 'AAA_OC_PayConfirm_Matcher' ) ) {
			self::log( 'Abort: missing matcher', $post_id );
			update_post_meta( $post_id, '_pc_processed', 'done' );
			self::force_publish( $post_id );
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

		// NEW: Upsert inbox row with final status
		if ( method_exists( 'AAA_OC_PC_Apply', 'upsert_pc_inbox' ) ) {
			AAA_OC_PC_Apply::upsert_pc_inbox( $post_id, $fields, $status, $matched ? (int) $result['order_id'] : null, $confidence );
		}

		update_post_meta( $post_id, '_pc_processed', 'done' );

		self::force_publish( $post_id );

		self::log( "Processor end (status={$status}, reason={$reason}, conf={$confidence})", $post_id );
	}

	/** Publish helper with robust fallbacks + logging */
	protected static function force_publish( $post_id ) {
		$p = get_post( $post_id );
		if ( ! $p ) return;
		if ( $p->post_status === 'publish' ) return;

		// try wp_publish_post first
		if ( function_exists( 'wp_publish_post' ) ) {
			$res = wp_publish_post( $post_id );
			// wp_publish_post returns void; re-read status
			$p = get_post( $post_id );
			if ( $p && $p->post_status === 'publish' ) { self::log( 'Published via wp_publish_post()', $post_id ); return; }
		}

		// fallback to wp_update_post
		$upd = wp_update_post( [ 'ID' => $post_id, 'post_status' => 'publish' ], true );
		if ( is_wp_error( $upd ) ) {
			self::log( 'Publish failed: ' . $upd->get_error_message(), $post_id );
		} else {
			$p = get_post( $post_id );
			self::log( 'Published via wp_update_post()', $post_id );
		}
	}

	/* ---------- BACKLOG REPAIR ---------- */

	public static function auto_repair_backlog() {
		// run at most once per 60s
		if ( get_transient( 'aaa_oc_pc_repair_lock' ) ) return;
		set_transient( 'aaa_oc_pc_repair_lock', 1, 60 );

		// 1) process up to 10 drafts that never got processed
		$unproc = get_posts( [
			'post_type'      => 'payment-confirmation',
			'post_status'    => [ 'draft', 'pending' ],
			'posts_per_page' => 10,
			'meta_query'     => [
				'relation' => 'OR',
				[
					'key'     => '_pc_processed',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'   => '_pc_processed',
					'value' => [ '', 'in_progress', 'empty' ],
					'compare' => 'IN',
				],
			],
			'orderby' => 'date',
			'order'   => 'DESC',
			'fields'  => 'ids',
		] );
		foreach ( $unproc as $pid ) {
			self::log( 'Repair: processing unprocessed draft', $pid );
			self::process_post( (int) $pid );
		}

		// 2) publish up to 10 already-processed drafts
		$proc_drafts = get_posts( [
			'post_type'      => 'payment-confirmation',
			'post_status'    => [ 'draft', 'pending' ],
			'posts_per_page' => 10,
			'meta_key'       => '_pc_processed',
			'meta_value'     => 'done',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		] );
		foreach ( $proc_drafts as $pid ) {
			self::log( 'Repair: publishing processed draft', $pid );
			self::force_publish( (int) $pid );
		}
	}

	/* ---------- utils ---------- */

	protected static function log( $msg, $post_id = 0 ) {
		if ( self::DEBUG_THIS_FILE ) {
			$ctx = $post_id ? " post={$post_id}" : '';
			error_log( "[AAA-OC][PayConfirm][TRIGGER]{$ctx} {$msg}" );
		}
	}
}
endif;

// boot
AAA_OC_PayConfirm_Triggers::init();
