<?php

namespace BULKY\Includes\Reviews;

defined( 'ABSPATH' ) || exit;

class Handle_Review {
	protected static $instance = null;
	protected $fields;
	protected $meta_fields;

	public function __construct() {
		$user_id           = get_current_user_id();
		$user_meta_fields  = get_user_meta( $user_id, 'vi_wbe_review_meta_fields', true );
		$this->fields      = Reviews::instance()->filter_fields();
		$this->meta_fields = empty( $user_meta_fields ) ? get_option( 'vi_wbe_review_meta_fields' ) : $user_meta_fields;
	}

	public static function instance() {
		return null === self::$instance ? self::$instance = new self : self::$instance;
	}

	public function get_comment_data( \WP_Comment $comment, $fields ) {
		$comment_approved_data = array(
			1      => 'approve',
			0      => 'pending',
			'spam' => 'spam',
		);

		$p_data = [];
		foreach ( $fields as $field ) {
			switch ( $field ) {
				case 'id':
					$p_data[] = $comment->comment_ID;
					break;
				case 'comment_type':
					$p_data[] = 'comment' === $comment->comment_type ? 'reply' : $comment->comment_type;
					break;
				case 'product_id':
					$p_data[] = $comment->comment_post_ID;
					break;
				case 'product_title':
					$product_id = $comment->comment_post_ID;
					$product = wc_get_product($product_id);
					$p_data[] = $product ? $product->get_name():'';
					break;
				case 'content':
					$p_data[] = $comment->comment_content;
					break;
				case 'author_name':
					$p_data[] = $comment->comment_author;
					break;
				case 'author_email':
					$p_data[] = $comment->comment_author_email;
					break;
				case 'author_url':
					$p_data[] = $comment->comment_author_url;
					break;
				case 'status':
					$p_data[] = $comment_approved_data[ $comment->comment_approved ];
					break;
				case 'review_date':
					$p_data[] = $comment->comment_date;
					break;
				case 'rating':
					$p_data[] = get_comment_meta( $comment->comment_ID, 'rating', true );
					break;
				case 'verified':
					$p_data[] = get_comment_meta( $comment->comment_ID, 'verified', true );
					break;
				default:
					if ( ! empty( $this->meta_fields[ $field ] ) ) {
						$meta_type = $this->meta_fields[ $field ]['input_type'];
						$data      = get_comment_meta( $comment->comment_ID, $field, true );
						if ( $meta_type == 'json' && ! is_array( $data ) ) {
							$data = json_decode( $data, true );
						}
					}
					$p_data[] = $data ?? '';
			}
		}

		return $p_data;
	}

	public function get_comment_data_for_edit( $comment ) {
		return $this->get_comment_data( $comment, $this->fields );
	}

	public function parse_review_data_to_save( $comment, $type, $value, &$p_data ) {

		$comment_approved_reverse_data = array(
			'approve' => 1,
			'pending' => 0,
			'spam'    => 'spam',
		);

		switch ( $type ) {
			case 'id':
			case 'product_id':
			case 'product_title':
			case 'comment_type':
				break;
			case 'content':
				$p_data['comment_content'] = $value;
				break;
			case 'author_name':
				$p_data['comment_author'] = $value;
				break;
			case 'author_email':
				$p_data['comment_author_email'] = $value;
				break;
			case 'author_url':
				$p_data['comment_author_url'] = $value;
				break;
			case 'status':
				$p_data['comment_approved'] = $comment_approved_reverse_data[ $value ];
				break;
			case 'review_date':
				$p_data['comment_date'] = $value;
				break;
			case 'rating':
				update_comment_meta( $comment->comment_ID, 'rating', $value );
				break;
			case 'verified':
				if ( 'true' === $value ) {
					$value = 1;
				}
				if ( 'false' === $value ) {
					$value = '';
				}
				update_comment_meta( $comment->comment_ID, 'verified', $value );
				break;
			default:
				$meta_fields = get_option( 'vi_wbe_review_meta_fields' );

				if ( ! empty( $meta_fields ) && is_array( $meta_fields ) && in_array( $type, array_keys( $meta_fields ) ) ) {
					$data_type = $meta_fields[ $type ]['input_type'] ?? '';
					$pid       = $comment->comment_ID;

					if ( $data_type ) {
						if ( $data_type === 'json' ) {
							$value = wp_json_encode( $value );
						}
						update_comment_meta( $pid, $type, $value );
					}
				}
		}
	}
}