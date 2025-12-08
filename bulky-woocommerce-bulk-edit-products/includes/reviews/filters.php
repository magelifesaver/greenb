<?php

namespace BULKY\Includes\Reviews;

defined( 'ABSPATH' ) || exit;

class Filters {
	protected static $instance = null;
	public $filter;
	protected $type = 'reviews';

	public function __construct() {
		$user_id      = get_current_user_id();
		$this->filter = get_transient( "vi_wbe_filter_{$this->type}_data_{$user_id}" );
	}

	public static function instance() {
		return self::$instance == null ? self::$instance = new self : self::$instance;
	}

	public function set_args( $args ) {
		$filter = $this->filter;

		if ( empty( $filter ) ) {
			return $args;
		}

		if ( ! empty( $filter['product_id'] ) ) {
			$args['post__in'] = $filter['product_id'] ;
		}
		if ( ! empty( $filter['comment_ID'] ) && strpos( $filter['comment_ID'], '-' ) === false ) {
			$args['comment__in'] = explode( ',', str_replace( ' ', '', $filter['comment_ID'] ) );
		}

		if ( ! empty( $filter['comment_search'] ) ) {
			$args['search'] = $filter['comment_search'];
		}

		if ( ! empty( $filter['comment_approved'] ) ) {
			$args['status'] = $filter['comment_approved'];
		}

		if ( ! empty( $filter['comment_type'] ) ) {
			$args['type__in'] = $filter['comment_type'];
		}

		if ( ! empty( $filter['comment_after_date'] ) && ! empty( $filter['comment_before_date'] ) ) {
			$args['date_query'] = array(
				array(
					'after'     => $filter['comment_after_date'],
					'before'    => $filter['comment_before_date'],
					'inclusive' => true,
				),
			);
		} else {
			if ( ! empty( $filter['comment_after_date'] ) ) {
				$args['date_query'] = array(
					array(
						'after'     => $filter['comment_after_date'],
						'inclusive' => true,
					),
				);
			}
			if ( ! empty( $filter['comment_before_date'] ) ) {
				$args['date_query'] = array(
					array(
						'before'    => $filter['comment_before_date'],
						'inclusive' => true,
					),
				);
			}
		}

		if ( ! empty( $filter['rating'] ) ) {
			$args['meta_query'][] = [
				'key'     => 'rating',
				'value'   => $filter['rating'],
				'compare' => '=',
			];
		}

		if ( '' !== $filter['verified'] ) {
			$args['meta_query'][] = [
				'key'     => 'verified',
				'value'   => $filter['verified'] == 0 ? '' : $filter['verified'],
				'compare' => '=',
			];
		}

		$args['meta_query']['relation'] = 'AND';

		return $args;

	}
}