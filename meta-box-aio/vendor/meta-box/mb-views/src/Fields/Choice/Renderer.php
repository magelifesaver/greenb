<?php
namespace MBViews\Fields\Choice;

use MBViews\Fields\BaseRenderer;
use RWMB_Field;

class Renderer extends BaseRenderer {
	public static function get_single_value( $value ) {
		$value = [
			'value' => $value,
			'label' => RWMB_Field::call( 'format_single_value', self::$field, $value, '', null ),
		];
		return $value;
	}
}
