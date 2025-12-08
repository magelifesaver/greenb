<?php
namespace MBViews;

use WP_Query;

class ContentLoader {
	private $renderer;
	private $type;
	private $query_args = [];
	private $validator;
	private $views;

	public function __construct( $renderer, $type ) {
		$this->renderer  = $renderer;
		$this->type      = $type;
		$this->validator = new Location\Validator( $type );

		$this->query_args = [
			'post_type'              => 'mb-views',
			'post_status'            => 'publish',
			'nopaging'               => true,

			'no_found_rows'          => true,
			'update_post_term_cache' => false,

			'orderby'                => 'menu_order',
			'order'                  => 'ASC',

			'meta_query'             => [ // @codingStandardsIgnoreLine.
				[
					'key'   => 'type',
					'value' => $this->type,
				],
				[
					'key'   => 'mode',
					'value' => 'content',
				],
			],
		];

		add_filter( 'the_content', [ $this, 'change_content' ] );
	}

	public function change_content( $content ) {
		if ( ! $this->validator->is_template_type() ) {
			return $content;
		}
		$this->get_views();
		return array_reduce( $this->views, [ $this, 'apply_view' ], $content );
	}

	private function get_views() {
		if ( null !== $this->views ) {
			return;
		}
		$query       = new WP_Query( $this->query_args );
		$this->views = [];
		foreach ( $query->posts as $view ) {
			$this->validator->set_view( $view );
			if ( $this->validator->validate() ) {
				$this->views[] = $view;
			}
		}
	}

	private function apply_view( $content, $view ) {
		$template = $this->renderer->render( $view );
		$position = get_post_meta( $view->ID, 'position', true );
		if ( 'before_content' === $position ) {
			$content = $template . $content;
		} elseif ( 'after_content' === $position ) {
			$content = $content . $template;
		} elseif ( 'replace_content' === $position ) {
			$content = $template;
		}
		return $content;
	}
}
