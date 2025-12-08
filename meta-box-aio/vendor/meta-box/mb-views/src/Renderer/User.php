<?php
namespace MBViews\Renderer;

use MBViews\Fields\User\Renderer;

class User extends Base {
	private $user_id;

	public function set_user_id( $user_id ) {
		$this->user_id = $user_id;
	}

	protected function get_data() {
		$this->data = Renderer::get_single_value( $this->user_id );
		$this->data = array_merge( $this->get_fields(), $this->data ?? [] );
	}

	private function get_fields() {
		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( [ 'object_type' => 'user' ] );

		$data = [];
		foreach ( $meta_boxes as $meta_box ) {
			$data = array_merge( $data, $this->meta_box_renderer->get_data( $meta_box, 'user', $this->user_id ) );
		}

		return $data;
	}
}
