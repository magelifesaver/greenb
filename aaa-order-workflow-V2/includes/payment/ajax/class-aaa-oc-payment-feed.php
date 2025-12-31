<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payment/ajax/class-aaa-oc-payment-feed.php
 * Purpose: AJAX endpoint to fetch latest "payment-confirmation" posts for the Board sidebar feed.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'AAA_OC_Payment_Feed_Ajax' ) ) :

class AAA_OC_Payment_Feed_Ajax {

	const DEBUG_THIS_FILE = true;
	const CPT             = 'payment-confirmation';

	public static function init() : void {
		add_action( 'wp_ajax_aaa_oc_payment_feed', [ __CLASS__, 'handle' ] );
		// (No wp_ajax_nopriv — admin-only UI.)
	}

	public static function handle() : void {
		// Basic capability + nonce.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'aaa_oc_ajax_nonce' ) ) {
			wp_send_json_error( 'bad_nonce', 400 );
		}

		$args = [
			'post_type'      => self::CPT,
			'post_status'    => [ 'publish', 'pending', 'draft' ],
			'orderby'        => 'date',
			'order'          => 'DESC',
			'posts_per_page' => 20,
			'no_found_rows'  => true,
			'fields'         => 'ids',
		];

		$q = new WP_Query( $args );
		$out = [];

		if ( $q->have_posts() ) {
			foreach ( $q->posts as $pid ) {
				$title = get_the_title( $pid );
				$date  = get_post_time( 'Y-m-d H:i', true, $pid ); // GMT
				$link  = get_edit_post_link( $pid, '' ); // open in admin editor
				$out[] = [
					'id'    => (int) $pid,
					'title' => $title ? $title : 'Untitled',
					'date'  => get_date_from_gmt( $date, 'M j, Y g:i a' ),
					'link'  => $link ? $link : admin_url( 'post.php?post=' . (int) $pid . '&action=edit' ),
				];
			}
		}

		if ( self::DEBUG_THIS_FILE ) {
			error_log( '[AAA_OC][PAYFEED] returned ' . count( $out ) . ' rows' );
		}

		wp_send_json_success( $out );
	}
}

AAA_OC_Payment_Feed_Ajax::init();

endif;
