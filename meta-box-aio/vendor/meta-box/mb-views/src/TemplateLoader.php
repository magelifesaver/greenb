<?php
namespace MBViews;

use WP_Query;

class TemplateLoader {
	private $type;
	private $query_args = [];
	private $validator;

	public function __construct( $type ) {
		$this->type      = $type;
		$this->validator = new Location\Validator( $type );

		add_filter( 'template_include', [ $this, 'change_template' ] );

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
					'value' => $this->type,
				],
				[
					'key'     => 'mode',
					'value'   => [ 'layout', 'page' ],
					'compare' => 'IN',
				],
			],
		];
	}

	public function change_template( $template ) {
		if ( ! $this->validator->is_template_type() ) {
			return $template;
		}
		return $this->has_view() ? MBV_DIR . '/views/template.php' : $template;
	}

	private function has_view() {
		$query = new WP_Query( $this->query_args );

		if ( ! $query->have_posts() ) {
			return false;
		}
		foreach ( $query->posts as $view ) {
			$this->validator->set_view( $view );
			if ( $this->validator->validate( $view ) ) {
				// Store view for rendering.
				ActiveView::set_view( $view );
				return true;
			}
		}

		return false;
	}
}
