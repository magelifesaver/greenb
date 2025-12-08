<?php

namespace BULKY\Includes\Coupons;

use BULKY\Includes\Abstracts\History_Abstract;

defined( 'ABSPATH' ) || exit;

class Coupon_History extends History_Abstract {

	protected static $instance = null;

	public function __construct() {
		$this->type = 'coupons';

		parent::__construct();
	}

	public static function instance() {
		return null == self::$instance ? self::$instance = new self : self::$instance;
	}

	public function get_remove_history_time() {
		return Coupons::instance()->get_setting( 'auto_remove_revision' );
	}

	public function revert_history_coupon_attribute() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$pid        = ! empty( $_POST['pid'] ) ? sanitize_text_field( wp_unslash( $_POST['pid'] ) ) : '';
		$history_id = ! empty( $_POST['history_id'] ) ? sanitize_text_field( wp_unslash( $_POST['history_id'] ) ) : '';
		$attribute  = ! empty( $_POST['attribute'] ) ? sanitize_text_field( wp_unslash( $_POST['attribute'] ) ) : '';

		if ( $pid && $history_id && $attribute ) {
			$coupon = new \WC_Coupon( $pid );

			if ( ! is_object( $coupon ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Coupon is not exist', 'bulky-woocommerce-bulk-edit-products' ) ] );
			}

			$history = $this->get_history_by_id( $history_id )->history;
			$pid     = $coupon->get_id();
			if ( isset( $history[ $pid ][ $attribute ] ) ) {
				$handle = Handle_Coupon::instance();
				$handle->parse_coupon_data_to_save( $coupon, $attribute, $history[ $pid ][ $attribute ] );
				$coupon->save();
			}
		}
	}

	public function revert_history_all_coupons() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$history_id = ! empty( $_POST['history_id'] ) ? sanitize_text_field( wp_unslash( $_POST['history_id'] ) ) : '';
		if ( ! $history_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'No history id', 'bulky-woocommerce-bulk-edit-products' ) ] );
		}
		$history = $this->get_history_by_id( $history_id )->history;

		if ( ! empty( $history ) && is_array( $history ) ) {
			$handle = Handle_Coupon::instance();

			foreach ( $history as $pid => $data ) {
				$coupon = new \WC_Coupon( $pid );

				if ( ! is_object( $coupon ) ) {
					continue;
				}

				if ( ! empty( $data ) && is_array( $data ) ) {
					foreach ( $data as $type => $value ) {
						$handle->parse_coupon_data_to_save( $coupon, $type, $value );
					}
				}

				$coupon->save();
			}
		}
	}

	public function revert_single_coupon() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$pid        = ! empty( $_POST['pid'] ) ? sanitize_text_field( wp_unslash( $_POST['pid'] ) ) : '';
		$history_id = ! empty( $_POST['history_id'] ) ? sanitize_text_field( wp_unslash( $_POST['history_id'] ) ) : '';

		if ( $pid && $history_id ) {
			$coupon = new \WC_Coupon( $pid );

			if ( ! is_object( $coupon ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Coupon is not exist', 'bulky-woocommerce-bulk-edit-products' ) ] );
			}

			$history        = $this->get_history_by_id( $history_id )->history;
			$pid            = $coupon->get_id();
			$coupon_history = $history[ $pid ] ?? '';

			if ( ! empty( $coupon_history ) && is_array( $coupon_history ) ) {
				$handle = Handle_Coupon::instance();
				foreach ( $coupon_history as $type => $value ) {
					$handle->parse_coupon_data_to_save( $coupon, $type, $value );
				}

				$coupon->save();
			}
		}
	}

	public function revert_history_product_attribute() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$pid        = ! empty( $_POST['pid'] ) ? sanitize_text_field( wp_unslash( $_POST['pid'] ) ) : '';
		$history_id = ! empty( $_POST['history_id'] ) ? sanitize_text_field( wp_unslash( $_POST['history_id'] ) ) : '';
		$attribute  = ! empty( $_POST['attribute'] ) ? sanitize_text_field( wp_unslash( $_POST['attribute'] ) ) : '';

		if ( $pid && $history_id && $attribute ) {
			$coupon = new \WC_Coupon( $pid );

			if ( ! is_object( $coupon ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Product is not exist', 'bulky-woocommerce-bulk-edit-products' ) ] );
			}

			$history = $this->get_history_by_id( $history_id )->history;
			$pid     = $coupon->get_id();

			if ( isset( $history[ $pid ][ $attribute ] ) ) {
				$handle = Handle_Coupon::instance();
				$handle->parse_coupon_data_to_save( $coupon, $attribute, $history[ $pid ][ $attribute ] );
				$coupon->save();
			}
		}

		wp_send_json_success();
	}

	public function revert_single_product() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$pid        = ! empty( $_POST['pid'] ) ? sanitize_text_field( wp_unslash( $_POST['pid'] ) ) : '';
		$history_id = ! empty( $_POST['history_id'] ) ? sanitize_text_field( wp_unslash( $_POST['history_id'] ) ) : '';

		if ( $pid && $history_id ) {
			$coupon = new \WC_Coupon( $pid );

			if ( ! is_object( $coupon ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Coupon is not exist', 'bulky-woocommerce-bulk-edit-products' ) ] );
			}

			$history        = $this->get_history_by_id( $history_id )->history;
			$pid            = $coupon->get_id();
			$coupon_history = $history[ $pid ] ?? '';

			if ( ! empty( $coupon_history ) && is_array( $coupon_history ) ) {
				$handle = Handle_Coupon::instance();
				foreach ( $coupon_history as $type => $value ) {
					$handle->parse_coupon_data_to_save( $coupon, $type, $value );
				}

				$coupon->save();
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
			$handle = Handle_Coupon::instance();

			foreach ( $history as $pid => $data ) {
				$post = get_post( $pid );
				if ( ! is_object( $post ) ) {
					continue;
				}

				$coupon = new \WC_Coupon( $pid );

				if ( ! empty( $data ) && is_array( $data ) ) {
					foreach ( $data as $type => $value ) {
						$handle->parse_coupon_data_to_save( $coupon, $type, $value );
					}
				}

				$coupon->save();
			}
		}
	}

	public function compare_history_point_and_current( $id ) {
		$full_history = $this->get_history_by_id( $id );
		$coupons      = $full_history->history;
		$columns      = Coupons::instance()->define_columns();

		if ( ! empty( $coupons ) && is_array( $coupons ) ) {
			$r = [];
			foreach ( $coupons as $pid => $history ) {
				$post = get_post( $pid );
				if ( ! is_object( $post ) ) {
					continue;
				}
				$coupon = new \WC_Coupon( $pid );

				$fields  = array_keys( $history );
				$current = Handle_Coupon::instance()->get_coupon_data( $coupon, $post, $fields );
				$current = array_combine( $fields, $current );

				$fields_parsed = [];
				foreach ( $fields as $key ) {
					$fields_parsed[ $key ] = $columns[ $key ]['title'] ?? '';
				}

				$r[ $pid ] = [
					'name'    => $coupon->get_code(),
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
