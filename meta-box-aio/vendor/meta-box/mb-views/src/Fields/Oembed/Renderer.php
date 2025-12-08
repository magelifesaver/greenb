<?php
namespace MBViews\Fields\Oembed;

use MBViews\Fields\BaseRenderer;
use RWMB_Field;

class Renderer extends BaseRenderer {
	public static function get_single_value( $value ) {
		return [
			'url'      => $value,
			'rendered' => RWMB_Field::call( 'format_single_value', self::$field, $value, '', null ),
		];
	}
}
