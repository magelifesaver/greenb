<?php
/**
 * Renderer for fields that return array: background, map, osm.
 */

namespace MBViews\Fields;

use RWMB_Field;

class ArrayRenderer extends BaseRenderer {
	public static function get_single_value( $value ) {
		return $value ? array_merge( $value, [
			'rendered' => RWMB_Field::call( 'format_single_value', self::$field, $value, [], null ),
		] ) : null;
	}
}
