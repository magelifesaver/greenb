<?php
namespace MBViews\Fields\Video;

use MBViews\Fields\BaseRenderer;
use RWMB_Video_Field;

class Renderer extends BaseRenderer {
	public static function get_single_value( $value ) {
		// Groups send ID, normal fields send array of video info.
		$value = isset( $value['ID'] ) ? $value['ID'] : $value;
		$value = RWMB_Video_Field::file_info( $value );

		if ( ! is_array( $value ) ) {
			return;
		}

		return array_merge( $value, [
			'rendered' => RWMB_Video_Field::format_clone_value( null, [ $value ], null, null ),
		] );
	}
}
