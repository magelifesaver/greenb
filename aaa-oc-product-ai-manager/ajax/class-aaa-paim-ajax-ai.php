<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/ajax/class-aaa-paim-ajax-ai.php
 * Purpose: AJAX to run AI fill for a product + attribute set (always JSON; user errors don't 4xx)
 * Version: 0.5.3
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_PAIM_DEBUG_AJAXAI' ) ) { define( 'AAA_PAIM_DEBUG_AJAXAI', true ); }

class AAA_Paim_Ajax_AI {

	public static function init() {
		add_action( 'wp_ajax_aaa_paim_run_ai', [ __CLASS__, 'run' ] );
	}

	public static function run() {
		// Capability
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied.' ] );
		}

		// Nonce (do not hard 400; return readable JSON instead)
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'aaa_paim_nonce' ) ) {
			wp_send_json_error( [ 'message' => 'Security check failed (nonce).' ] );
		}

		// Inputs
		$product_id = absint( $_POST['product_id'] ?? 0 );
		$set_id     = absint( $_POST['set_id'] ?? 0 );
		$ai_items   = array_map( 'sanitize_text_field', (array) ( $_POST['ai_items'] ?? [] ) );

		if ( ! $product_id || ! $set_id ) {
			wp_send_json_error( [ 'message' => 'Missing product or set.' ] );
		}

		// Correlation id for logs/UI
		$run_id = 'AI_' . wp_generate_uuid4();

		// Accept inline sources so a pre-save is not required
		if ( isset( $_POST['source_urls'] ) ) {
			update_post_meta( $product_id, '_paim_source_urls', (string) wp_unslash( $_POST['source_urls'] ) );
		}

		// If no explicit AI items, infer "missing" fields from current product values
		if ( empty( $ai_items ) ) {
			$items  = AAA_Paim_Product::get_set_items( $set_id );
			$values = AAA_Paim_Product::get_product_values( $product_id, $items );

			foreach ( $items as $it ) {
				$type = $it['object_type'];
				$key  = $it['object_key'];

				if ( 'taxonomy' === $type ) {
					$cur = isset( $values[ $key ] ) ? (array) $values[ $key ] : [];
					if ( empty( $cur ) ) {
						$ai_items[] = 'taxonomy:' . $key;
					}
				} else {
					$cur = isset( $values[ $key ] ) ? (string) $values[ $key ] : '';
					if ( '' === trim( $cur ) ) {
						$ai_items[] = 'meta:' . $key;
					}
				}
			}
		}

		if ( empty( $ai_items ) ) {
			wp_send_json_error( [ 'message' => 'Nothing to fill.', 'run_id' => $run_id ] );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && AAA_PAIM_DEBUG_AJAXAI ) {
			error_log( sprintf(
				'[AAA-PAIM][AJAX-AI][%s] START product=%d set=%d items=%s',
				$run_id, $product_id, $set_id, wp_json_encode( $ai_items )
			) );
		}

		// Execute
		$res = AAA_Paim_AI::run_for_product_set( $product_id, $set_id, $ai_items, [ 'run_id' => $run_id ] );

		if ( is_wp_error( $res ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && AAA_PAIM_DEBUG_AJAXAI ) {
				error_log( sprintf(
					'[AAA-PAIM][AJAX-AI][%s] ERROR %s: %s',
					$run_id, $res->get_error_code(), $res->get_error_message()
				) );
			}
			wp_send_json_error( [ 'message' => $res->get_error_message(), 'run_id' => $run_id ] );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && AAA_PAIM_DEBUG_AJAXAI ) {
			error_log( sprintf(
				'[AAA-PAIM][AJAX-AI][%s] DONE result=%s',
				$run_id, wp_json_encode( $res )
			) );
		}

		wp_send_json_success( [ 'result' => $res, 'run_id' => $run_id ] );
	}
}

AAA_Paim_Ajax_AI::init();
