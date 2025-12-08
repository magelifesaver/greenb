<?php
namespace MBViews\Fields\Image;

use MBViews\Fields\BaseRenderer;
use RWMB_Image_Field;

class Renderer extends BaseRenderer {
	public static function get_post_thumbnail( $post = null ) {
		return self::get_single_value( get_post_thumbnail_id( $post ) );
	}

	public static function get_single_value( $value ) {
		// Groups send ID, normal fields send array of image info.
		$value   = isset( $value['ID'] ) ? $value['ID'] : $value;
		$data    = [];
		$sizes   = get_intermediate_image_sizes();
		$sizes[] = 'full';
		foreach ( $sizes as $size ) {
			$data[ $size ] = RWMB_Image_Field::file_info( $value, [ 'size' => $size ] );
		}
		return $data;
	}
}
