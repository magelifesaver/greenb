<?php
namespace MBViews\Fields;

use RWMB_Field;

class SimpleRenderer extends BaseRenderer {
	public static function get_single_value( $value ) {
		return RWMB_Field::call( 'format_single_value', self::$field, $value, [], null );
	}
}
