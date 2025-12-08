<?php

namespace BULKY\Includes\Reviews;

use BULKY\Includes\Abstracts\History_Abstract;

defined( 'ABSPATH' ) || exit;

class Review_History extends History_Abstract {

	protected static $instance = null;

	public function __construct() {
		$this->type = 'reviews';

		parent::__construct();

	}

	public static function instance() {
		return null === self::$instance ? self::$instance = new self : self::$instance;
	}

	public function get_remove_history_time() {
		return Reviews::instance()->get_setting( 'auto_remove_revision' );
	}


	public function revert_history_product_attribute() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$pid        = ! empty( $_POST['pid'] ) ? sanitize_text_field( wp_unslash( $_POST['pid'] ) ) : '';
		$history_id = ! empty( $_POST['history_id'] ) ? sanitize_text_field( wp_unslash( $_POST['history_id'] ) ) : '';
		$attribute  = ! empty( $_POST['attribute'] ) ? sanitize_text_field( wp_unslash( $_POST['attribute'] ) ) : '';

		if ( $pid && $history_id && $attribute ) {
			$review = get_comment( $pid );

			if ( ! is_object( $review ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Review is not exist', 'bulky-woocommerce-bulk-edit-products' ) ] );
			}

			$history = $this->get_history_by_id( $history_id )->history;
			$pid     = $review->comment_ID;
			if ( isset( $history[ $pid ][ $attribute ] ) ) {
				$handle = Handle_Review::instance();
				$p_data = [];
				$handle->parse_review_data_to_save( $review, $attribute, $history[ $pid ][ $attribute ], $p_data );

				if ( ! empty( $p_data ) ) {
					$p_data['comment_ID'] = $review->comment_ID;
					wp_update_comment( $p_data );
				}
			}
		}
	}

	public function revert_history_all_products() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$history_id = ! empty( $_POST['history_id'] ) ? sanitize_text_field( wp_unslash( $_POST['history_id'] ) ) : '';
		if ( ! $history_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'No history id', 'bulky-woocommerce-bulk-edit-products' ) ] );
		}
		$history = $this->get_history_by_id( $history_id )->history;

		if ( ! empty( $history ) && is_array( $history ) ) {
			$handle = Handle_Review::instance();

			foreach ( $history as $pid => $data ) {
				$review = get_comment( $pid );

				if ( ! is_object( $review ) ) {
					continue;
				}

				if ( ! empty( $data ) && is_array( $data ) ) {
					$p_data = [];
					foreach ( $data as $type => $value ) {
						$handle->parse_review_data_to_save( $review, $type, $value, $p_data );
					}

					if ( ! empty( $p_data ) ) {
						$p_data['comment_ID'] = $review->comment_ID;
						wp_update_comment( $p_data );
					}
				}
			}
		}
	}

	public function revert_single_product() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$pid        = ! empty( $_POST['pid'] ) ? sanitize_text_field( wp_unslash( $_POST['pid'] ) ) : '';
		$history_id = ! empty( $_POST['history_id'] ) ? sanitize_text_field( wp_unslash( $_POST['history_id'] ) ) : '';

		if ( $pid && $history_id ) {
			$review = get_comment( $pid );

			if ( ! is_object( $review ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Review is not exist', 'bulky-woocommerce-bulk-edit-products' ) ] );
			}

			$history        = $this->get_history_by_id( $history_id )->history;
			$pid            = $review->comment_ID;
			$review_history = $history[ $pid ] ?? '';

			if ( ! empty( $review_history ) && is_array( $review_history ) ) {
				$handle = Handle_Review::instance();
				$p_data = [];
				foreach ( $review_history as $type => $value ) {
					$handle->parse_review_data_to_save( $review, $type, $value, $p_data );
				}

				if ( ! empty( $p_data ) ) {
					$p_data['comment_ID'] = $review->comment_ID;
					wp_update_comment( $p_data );
				}
			}
		}
	}

	public function compare_history_point_and_current( $id ) {
		$full_history = $this->get_history_by_id( $id );
		$reviews      = $full_history->history;
		$columns      = Reviews::instance()->define_columns();

		if ( ! empty( $reviews ) && is_array( $reviews ) ) {
			$r = [];
			foreach ( $reviews as $pid => $history ) {
				$review = get_comment( $pid );
				if ( ! is_object( $review ) ) {
					continue;
				}

				$fields  = array_keys( $history );
				$current = Handle_Review::instance()->get_comment_data( $review, $fields );
				$current = array_combine( $fields, $current );

				$fields_parsed = [];
				foreach ( $fields as $key ) {
					$fields_parsed[ $key ] = $columns[ $key ]['title'] ?? '';
				}

				$r[ $pid ] = [
					'name'    => esc_html__( 'Review #', 'bulky-woocommerce-bulk-edit-products' ) . $review->comment_ID,
					'fields'  => $fields_parsed,
					'history' => $history,
					'current' => $current,
				];
			}
		}

		return [
			'compare' => $r ?? '',
			'date'    => date_i18n( wc_date_format() . ' ' . wc_time_format(), $full_history->date )
		];
	}

}