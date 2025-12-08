<?php
namespace MBViews\Renderer;

use MB_Relationships_API;

class Relationship extends Base {
	private $relationship;

	public function set_relationship( $relationship ) {
		$this->relationship = $relationship;
	}

	protected function get_data() {
		$this->data = [
			'from' => $this->get_side_data( 'from' ),
			'to'   => $this->get_side_data( 'to' ),
		];
	}

	private function get_side_data( $target ) {
		$source      = 'from' === $target ? 'to' : 'from';
		$settings    = $this->relationship[ $target ];
		$object_type = $settings['object_type'];

		$items = MB_Relationships_API::get_connected( [
			'id'    => $this->relationship['id'],
			$source => get_queried_object_id(),
		] );

		switch ( $object_type ) {
			case 'post':
				$items = array_map( function( $post ) {
					$post_object = new Post( $this->meta_box_renderer );
					$post_object->set_post( $post );
					return $post_object;
				}, $items );
				break;
			case 'term':
				$items = array_map( function( $term ) {
					$term_object = new Term( $this->meta_box_renderer );
					$term_object->set_term( $term );
					return $term_object;
				}, $items );
				break;
			case 'user':
				$items = array_map( function( $user ) {
					$user_object = new User( $this->meta_box_renderer );
					$user_object->set_user_id( $user->ID );
					return $user_object;
				}, $items );
				break;
		}
		return $items;
	}
}
