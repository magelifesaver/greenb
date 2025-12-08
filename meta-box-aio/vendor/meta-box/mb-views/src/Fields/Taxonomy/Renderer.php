<?php
namespace MBViews\Fields\Taxonomy;

use MBViews\Fields\BaseRenderer;

class Renderer extends BaseRenderer {
	public static function get_single_value( $value ) {
		if ( ! $value ) {
			return null;
		}
		$value = (array) $value;
        if ( ! isset( $value['term_id'] ) ) {
            return null;
        }
		return array_merge( $value, [
			'id'  => $value['term_id'],
			'url' => get_term_link( $value['term_id'] ),
		] );
	}
}
