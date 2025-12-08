<?php
namespace MBViews\Fields\File;

use MBViews\Fields\BaseRenderer;
use RWMB_File_Field;

class Renderer extends BaseRenderer {
	public static function get_single_value( $value ) {
		// Parse file info when in a group.
		return is_array( $value ) ? $value : RWMB_File_Field::file_info( $value, [], self::$field );
	}
}
