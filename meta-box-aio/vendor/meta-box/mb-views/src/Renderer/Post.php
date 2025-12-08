<?php
namespace MBViews\Renderer;

use MBViews\Fields\Post\Renderer;

class Post extends Base {
	private $post;

	public function set_post( $post ) {
		$this->post = $post;
	}

	protected function get_data() {
		$this->data = Renderer::get_single_value( $this->post );
		$this->data = array_merge( $this->get_fields(), $this->data );
	}

	private function get_fields() {
		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( [ 'object_type' => 'post' ] );
		$meta_boxes = array_filter( $meta_boxes, function( $meta_box ) {
			return in_array( $this->post->post_type, $meta_box->post_types, true );
		} );

		$data = [];
		foreach ( $meta_boxes as $meta_box ) {
			$data = array_merge( $data, $this->meta_box_renderer->get_data( $meta_box, 'post', $this->post->ID ) );
		}

		return $data;
	}
}
