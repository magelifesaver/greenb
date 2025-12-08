<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/inc/class-aaa-paim-attribute-registry.php
 * Purpose: Discover available product attributes/taxonomies/meta keys (for admin picker).
 * Version: 0.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_PAIM_DEBUG_REG' ) ) { define( 'AAA_PAIM_DEBUG_REG', true ); }

class AAA_Paim_Attribute_Registry {

	public static function woo_attribute_taxonomies() : array {
		$out = [];
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) { return $out; }
		$taxes = wc_get_attribute_taxonomies();
		if ( empty( $taxes ) ) { return $out; }
		foreach ( $taxes as $t ) {
			$slug = 'pa_' . $t->attribute_name;
			if ( taxonomy_exists( $slug ) ) {
				$tax = get_taxonomy( $slug );
				$out[ $slug ] = $tax->labels->singular_name ?: $slug;
			}
		}
		return $out; // [ 'pa_flavor' => 'Flavor', ... ]
	}

	public static function other_product_taxonomies() : array {
		$out = [];
		$objs = get_object_taxonomies( 'product', 'objects' );
		if ( empty( $objs ) ) { return $out; }
		$exclude = [ 'product_cat', 'product_type', 'product_visibility', 'berocket_brand' ];
		foreach ( $objs as $slug => $obj ) {
			if ( 0 === strpos( $slug, 'pa_' ) ) { continue; }
			if ( in_array( $slug, $exclude, true ) ) { continue; }
			$out[ $slug ] = $obj->labels->singular_name ?: $slug;
		}
		return $out; // e.g., product_tag, berocket_brand, etc.
	}

	public static function discover_product_meta_keys( int $limit = 200 ) : array {
		global $wpdb;
		$cache_key = 'aaa_paim_meta_keys_v1';
		$cached = get_transient( $cache_key );
		if ( $cached && is_array( $cached ) ) { return $cached; }

		$sql = "
			SELECT DISTINCT pm.meta_key
			FROM {$wpdb->postmeta} pm
			JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE p.post_type IN ('product','product_variation')
			  AND pm.meta_key <> ''
			LIMIT %d
		";
		$rows = $wpdb->get_col( $wpdb->prepare( $sql, $limit ) );
		$public = [];
		$private = [];
		foreach ( $rows as $k ) {
			if ( strpos( $k, '_' ) === 0 ) { $private[ $k ] = $k; } else { $public[ $k ] = $k; }
		}
		$data = [ 'public' => $public, 'private' => $private ];
		set_transient( $cache_key, $data, 6 * HOUR_IN_SECONDS );
		aaa_paim_log( 'Meta keys discovered: ' . count( $rows ), 'REG' );
		return $data;
	}

	public static function product_category_dropdown_args() : array {
		return [
			'taxonomy'        => 'product_cat',
			'hide_empty'      => false,
			'name'            => 'category_term_id',
			'id'              => 'aaa-paim-category',
			'show_option_all' => '',
			'show_count'      => 0,
			'orderby'         => 'name',
			'class'           => 'aaa-paim-select',
		];
	}
}
