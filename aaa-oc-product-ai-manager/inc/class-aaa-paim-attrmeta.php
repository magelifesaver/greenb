<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/inc/class-aaa-paim-attrmeta.php
 * Purpose: Read attribute-level meta saved by core (aaa_attr_meta_{attribute_id})
 * Version: 0.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_Paim_AttrMeta {
	public static function by_tax( string $taxonomy ) : array {
		if ( ! function_exists( 'wc_attribute_taxonomy_id_by_name' ) ) { return []; }
		$attr_id = (int) wc_attribute_taxonomy_id_by_name( $taxonomy );
		if ( $attr_id <= 0 ) { return []; }
		$meta = get_option( 'aaa_attr_meta_' . $attr_id, [] );
		return is_array( $meta ) ? $meta : [];
	}

	public static function kind( string $taxonomy, ?string $fallback = null ) : string {
		$m = self::by_tax( $taxonomy );
		return $m['value_kind'] ?? ($fallback ?? 'taxonomy');
	}

	public static function precision( string $taxonomy ) : ?int {
		$m = self::by_tax( $taxonomy );
		return array_key_exists( 'number_precision', $m ) ? $m['number_precision'] : null;
	}

	public static function flags( string $taxonomy ) : array {
		$m = self::by_tax( $taxonomy );
		return [
			'visible'   => ! empty( $m['default_visible'] ),
			'variation' => ! empty( $m['default_variation'] ),
		];
	}

	public static function help( string $taxonomy ) : string {
		$m = self::by_tax( $taxonomy );
		return isset( $m['help_text'] ) ? (string) $m['help_text'] : '';
	}
}
