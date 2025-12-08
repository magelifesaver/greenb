<?php
namespace MBViews\Renderer;

use MBViews\Fields\Taxonomy\Renderer;

class Term extends Base {
	private $term;

	public function set_term( $term ) {
		$this->term = $term;
	}

	protected function get_data() {
		$this->data = Renderer::get_single_value( $this->term );
		$this->data = array_merge( $this->get_fields(), $this->data ?? [] );
	}

	private function get_fields() {
		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( [ 'object_type' => 'term' ] );

		$data = [];
		foreach ( $meta_boxes as $meta_box ) {
			$data = array_merge( $data, $this->meta_box_renderer->get_data( $meta_box, 'term', $this->term->term_id ) );
		}

		return $data;
	}
}
