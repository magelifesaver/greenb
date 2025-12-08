<?php
namespace MBViews;

use WP_Query;

class ActionLoader {
	private $renderer;
	private $query_args;

	public function __construct( $renderer ) {
		$this->renderer = $renderer;

		$this->query_args = [
			'post_type'              => 'mb-views',
			'post_status'            => 'publish',
			'nopaging'               => true,

			'no_found_rows'          => true,
			'update_post_term_cache' => false,

			'orderby'                => 'menu_order',
			'order'                  => 'DESC',

			'meta_query'             => [ // @codingStandardsIgnoreLine.
				[
					'key'   => 'type',
					'value' => 'action',
				],
				[
					'key'     => 'mbv_action',
					'compare' => 'EXISTS',
				],
			],
		];

		$query = new WP_Query( $this->query_args );

		if ( ! $query->have_posts() ) {
			return;
		}
		foreach ( $query->posts as $view ) {
			$action   = get_post_meta( $view->ID, 'mbv_action', true );
			$priority = get_post_meta( $view->ID, 'action_priority', true );
			$priority = $priority ?: 10;
			add_action( $action, function () use ( $view, $action ) {
				$this->render_view( $view, $action );
			}, $priority );
		}
	}

	public function render_view( $view, $action ) {
		$active = $this->is_view_active( $view );
		$active = apply_filters( 'mbv_location_action_active', $active, $view, $action );
		if ( $active ) {
			echo $this->renderer->render( $view ); // @codingStandardsIgnoreLine.
		}
	}

	private function is_view_active( $view ) {
		$type = get_post_meta( $view->ID, 'action_type', true );
		if ( 'site' === $type ) {
			return true;
		}
		$validator = new Location\Validator( $type );
		$validator->set_view( $view );
		return $validator->is_template_type() && $validator->validate();
	}
}
