<?php
namespace MBViews;

class ConditionalLogic {
	public function __construct() {
		add_filter( 'rwmb_outside_conditions', [ $this, 'add_conditions' ] );
	}

	public function add_conditions( $conditions ) {
		// Insert loop button.
		$conditions['.mbv-insert-loop'] = [
			'visible' => [ '#type', '!=', 'singular' ],
		];

		// Field checkbox.
		$conditions['.mbv-field-checkbox-checked']   = [
			'visible' => [ '#mbv-field-checkbox-output', 'custom' ],
		];
		$conditions['.mbv-field-checkbox-unchecked'] = [
			'visible' => [ '#mbv-field-checkbox-output', 'custom' ],
		];

		// Field date.
		$conditions['.mbv-field-date-custom-format'] = [
			'visible' => [ '#mbv-field-date-format', 'custom' ],
		];

		// Field map.
		$conditions['.mbv-field-map-width']        = [
			'visible' => [ '#mbv-field-map-output', 'map_custom' ],
		];
		$conditions['.mbv-field-map-height']       = [
			'visible' => [ '#mbv-field-map-output', 'map_custom' ],
		];
		$conditions['.mbv-field-map-zoom']         = [
			'visible' => [ '#mbv-field-map-output', 'map_custom' ],
		];
		$conditions['.mbv-field-map-marker-icon']  = [
			'visible' => [ '#mbv-field-map-output', 'map_custom' ],
		];
		$conditions['.mbv-field-map-marker-title'] = [
			'visible' => [ '#mbv-field-map-output', 'map_custom' ],
		];
		$conditions['.mbv-field-map-info-window']  = [
			'visible' => [ '#mbv-field-map-output', 'map_custom' ],
		];

		// Field post: date & modified date.
		$conditions['.mbv-field-post-date-format']        = [
			'visible' => [ '#mbv-field-post-output', 'in', [ 'date', 'modified_date' ] ],
		];
		$conditions['.mbv-field-post-date-custom-format'] = [
			'visible' => [ '#mbv-field-post-date-format', 'custom' ],
		];

		// Field post: thumbnail.
		$conditions['.mbv-field-post-thumbnail-size']   = [
			'visible' => [ '#mbv-field-post-output', 'thumbnail' ],
		];
		$conditions['.mbv-field-post-thumbnail-output'] = [
			'visible' => [ '#mbv-field-post-output', 'thumbnail' ],
		];

		// Field post: term list.
		$conditions['.mbv-field-post-terms-taxonomy']  = [
			'visible' => [ '#mbv-field-post-output', 'terms' ],
		];
		$conditions['.mbv-field-post-terms-before']    = [
			'visible' => [ '#mbv-field-post-output', 'terms' ],
		];
		$conditions['.mbv-field-post-terms-separator'] = [
			'visible' => [ '#mbv-field-post-output', 'terms' ],
		];
		$conditions['.mbv-field-post-terms-after']     = [
			'visible' => [ '#mbv-field-post-output', 'terms' ],
		];

		// Field user.
		$conditions['.mbv-field-user-avatar-size'] = [
			'visible' => [ '#mbv-field-user-output', 'avatar' ],
		];
		return $conditions;
	}
}
