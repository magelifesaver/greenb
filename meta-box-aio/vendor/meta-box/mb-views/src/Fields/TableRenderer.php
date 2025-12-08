<?php
/**
 * Renderer for fields that output as table: fieldset text, text list.
 */

namespace MBViews\Fields;

use RWMB_Field;

class TableRenderer extends BaseRenderer {
	public static function parse( $value, $field ) {
		return RWMB_Field::call( 'format_value', $field, $value, [], null );
	}
}
