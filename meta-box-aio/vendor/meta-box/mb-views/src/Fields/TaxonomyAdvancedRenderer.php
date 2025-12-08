<?php
namespace MBViews\Fields;

class TaxonomyAdvancedRenderer extends BaseRenderer {
	public static function get_single_value( $value = null ) {
		// Groups send ID, normal fields send term object.
		$value = get_term( $value );
		if ( ! $value || is_wp_error( $value ) ) {
			return null;
		}
		$value = (array) $value;
		return array_merge( $value, [
			'id'  => $value['term_id'],
			'url' => get_term_link( $value['term_id'] ),
		] );
	}
}
