<?php
/**
 * Renderer for map fields: map, osm. These fields are not cloneable.
 */

namespace MBViews\Fields;

use RWMB_Field;

class MapRenderer extends BaseRenderer {
	public static function get_single_value( $value ) {
		// Groups send location, normal fields send array of map info.
		if ( ! is_array( $value ) ) {
			list( $latitude, $longitude, $zoom ) = explode( ',', $value . ',,' );
			$value                               = compact( 'latitude', 'longitude', 'zoom' );
		}

		$field = self::$field['type'] === 'osm' ? array_merge( self::$field, [ 'api_key' => '' ] ) : self::$field;

		$render_map = array_merge( $value, [
			'rendered' => RWMB_Field::call( $field, 'render_map', implode( ',', $value ), [
				'language' => $field['language'],
				'region'   => $field['region'],
				'api_key'  => $field['api_key'],
			] ),
		] );

		if ( $field['type'] === 'map' && wp_script_is( 'rwmb-map-frontend-mbview' ) ) {
			wp_dequeue_script( 'rwmb-map-frontend' );
		} elseif ( $field['type'] === 'osm' && wp_script_is( 'rwmb-osm-frontend-mbview' ) ) {
			wp_dequeue_script( 'leaflet' );
			wp_dequeue_script( 'rwmb-osm-frontend' );
		}

		return $render_map;
	}
}
