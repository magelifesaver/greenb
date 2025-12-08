<?php
namespace MetaBox\UserProfile\Blocks;

trait DataTrait {
	private function get_data( array $exclude = [
		'rwmb-user-register', 'rwmb-user-login', 'rwmb-user-lost-password', 'rwmb-user-reset-password', 'rwmb-user-info',
	] ): array {
		// Get all meta boxes for users.
		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( [ 'object_type' => 'user' ] );

		$meta_boxes = array_filter( $meta_boxes, function ( $meta_box ) use ( $exclude ) {
			return ! in_array( $meta_box->id, $exclude );
		} );

		$field_groups = [];

		foreach ( $meta_boxes as $meta_box ) {
			$fields         = array_filter( $meta_box->fields, function ( $field ) {
				return isset( $field['id'] );
			} );
			$field_groups[] = [
				'value'  => $meta_box->id,
				'label'  => "{$meta_box->title} ({$meta_box->id})",
				'fields' => array_values( wp_list_pluck( $fields, 'id' ) ),
			];
		}

		return compact( 'field_groups' );
	}
}
