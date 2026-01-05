<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AAA_API_Lokey_REST_Products_Helpers {
	public static function apply_product_fields( $p, $request ) {
		$map = array('name'=>'set_name','status'=>'set_status','sku'=>'set_sku');
		foreach ( $map as $k => $m ) {
			if ( null === $request->get_param( $k ) ) { continue; }
			$v = (string) $request->get_param( $k );
			if ( 'status' === $k ) {
				$v = sanitize_key( $v );
				if ( ! $v || 'none' === $v ) { continue; }
				$p->{$m}( $v );
				continue;
			}
			$p->{$m}( sanitize_text_field( $v ) );
		}
		if ( null !== $request->get_param( 'regular_price' ) ) { $p->set_regular_price( wc_format_decimal( $request->get_param( 'regular_price' ) ) ); }
		if ( null !== $request->get_param( 'sale_price' ) ) { $p->set_sale_price( wc_format_decimal( $request->get_param( 'sale_price' ) ) ); }
		if ( null !== $request->get_param( 'manage_stock' ) ) { $p->set_manage_stock( (bool) $request->get_param( 'manage_stock' ) ); }
		if ( null !== $request->get_param( 'stock_quantity' ) ) { $p->set_stock_quantity( (int) $request->get_param( 'stock_quantity' ) ); }
		if ( null !== $request->get_param( 'stock_status' ) ) { $p->set_stock_status( sanitize_key( (string) $request->get_param( 'stock_status' ) ) ); }
		if ( null !== $request->get_param( 'description' ) ) { $p->set_description( wp_kses_post( (string) $request->get_param( 'description' ) ) ); }
		if ( null !== $request->get_param( 'short_description' ) ) { $p->set_short_description( wp_kses_post( (string) $request->get_param( 'short_description' ) ) ); }
	}

	public static function query_products( $request, $pp, $pg ) {
		$q = array('post_type'=>'product','posts_per_page'=>$pp,'paged'=>$pg,'s'=>sanitize_text_field((string)$request->get_param('search')),'post_status'=>sanitize_key((string)$request->get_param('status')));
		if ( empty( $q['post_status'] ) ) { $q['post_status'] = 'any'; }
		if ( $sku = sanitize_text_field( (string) $request->get_param('sku') ) ) { $q['meta_query'] = array(array('key'=>'_sku','value'=>$sku,'compare'=>'=')); }
		$tax = array();
		if ( $c = $request->get_param('category') ) { $tax[] = array('taxonomy'=>'product_cat','field'=>is_numeric($c)?'term_id':'slug','terms'=>is_numeric($c)?(int)$c:sanitize_title($c)); }
		$settings = get_option( AAA_API_LOKEY_OPTION, array() );
		$brand_tax = isset( $settings['brand_taxonomy'] ) ? sanitize_key( (string) $settings['brand_taxonomy'] ) : '';
		if ( $brand_tax && $b = $request->get_param('brand') ) { $tax[] = array('taxonomy'=>$brand_tax,'field'=>is_numeric($b)?'term_id':'slug','terms'=>is_numeric($b)?(int)$b:sanitize_title($b)); }
		if ( $tax ) { $q['tax_query'] = $tax; }
		return new WP_Query( $q );
	}

	public static function apply_taxonomies( $product, $request ) {
		$settings = get_option( AAA_API_LOKEY_OPTION, array() );
		$brand_tax = isset( $settings['brand_taxonomy'] ) ? sanitize_key( (string) $settings['brand_taxonomy'] ) : '';
		$taxes = array();
		if ( null !== $request->get_param('categories') ) { $taxes['product_cat'] = (array) $request->get_param('categories'); }
		if ( null !== $request->get_param('tags') ) { $taxes['product_tag'] = (array) $request->get_param('tags'); }
		if ( $brand_tax && null !== $request->get_param('brands') ) { $taxes[ $brand_tax ] = (array) $request->get_param('brands'); }
		$more = $request->get_param('taxonomies');
		if ( is_array( $more ) ) { foreach ( $more as $t => $terms ) { $taxes[ sanitize_key( $t ) ] = (array) $terms; } }
		if ( ! $taxes ) { return; }
		$create = ! empty( $request->get_param('create_terms') );
		foreach ( $taxes as $t => $terms ) {
			if ( ! taxonomy_exists( $t ) ) { continue; }
			wp_set_object_terms( $product->get_id(), self::term_ids( $t, $terms, $create ), $t, false );
		}
	}

	public static function apply_attributes( $product, $request ) {
		$raw = $request->get_param('attributes');
		if ( ! is_array( $raw ) ) { return; }
		$create = ! empty( $request->get_param('create_terms') );
		$attrs = array(); $pos = 0;
		foreach ( $raw as $a ) {
			if ( ! is_array( $a ) ) { continue; }
			$attr = new WC_Product_Attribute();
			$opt  = isset( $a['options'] ) && is_array( $a['options'] ) ? $a['options'] : array();
			$tax  = isset( $a['taxonomy'] ) ? sanitize_key( (string) $a['taxonomy'] ) : '';
			if ( $tax && taxonomy_exists( $tax ) ) {
				$attr->set_name( $tax );
				$attr->set_id( function_exists('wc_attribute_taxonomy_id_by_name') ? (int) wc_attribute_taxonomy_id_by_name( substr( $tax, 3 ) ) : 0 );
				$attr->set_options( self::term_ids( $tax, $opt, $create ) );
			} else {
				$name = isset( $a['name'] ) ? sanitize_text_field( (string) $a['name'] ) : '';
				if ( ! $name ) { continue; }
				$attr->set_name( $name );
				$attr->set_options( array_values( array_filter( array_map( 'sanitize_text_field', $opt ) ) ) );
			}
			$attr->set_position( $pos++ );
			$attr->set_visible( ! empty( $a['visible'] ) );
			$attr->set_variation( ! empty( $a['variation'] ) );
			$attrs[] = $attr;
		}
		$product->set_attributes( $attrs );
	}

	public static function term_ids( $taxonomy, $terms, $create ) {
		$ids = array();
		foreach ( (array) $terms as $t ) {
			if ( is_numeric( $t ) ) { $ids[] = (int) $t; continue; }
			$slug = sanitize_title( (string) $t );
			$ex = term_exists( $slug, $taxonomy );
			if ( $ex && ! is_wp_error( $ex ) ) { $ids[] = (int) ( is_array($ex) ? $ex['term_id'] : $ex ); continue; }
			if ( $create ) {
				$in = wp_insert_term( sanitize_text_field( (string) $t ), $taxonomy, array('slug'=>$slug) );
				if ( ! is_wp_error( $in ) && ! empty( $in['term_id'] ) ) { $ids[] = (int) $in['term_id']; }
			}
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	public static function format_product( $p, $include_atum ) {
		$id = $p ? $p->get_id() : 0;
		if ( ! $id ) { return array(); }
		$out = array(
			'id'=>$id,'name'=>$p->get_name(),'type'=>$p->get_type(),'status'=>$p->get_status(),'sku'=>$p->get_sku(),
			'regular_price'=>$p->get_regular_price(),'sale_price'=>$p->get_sale_price(),'price'=>$p->get_price(),
			'manage_stock'=>$p->get_manage_stock(),'stock_quantity'=>$p->get_stock_quantity(),'stock_status'=>$p->get_stock_status(),
			'description'=>$p->get_description(),'short_description'=>$p->get_short_description(),
		);
		$out['taxonomies'] = array('product_cat'=>wp_get_object_terms($id,'product_cat',array('fields'=>'ids')),'product_tag'=>wp_get_object_terms($id,'product_tag',array('fields'=>'ids')));
		$settings = get_option( AAA_API_LOKEY_OPTION, array() );
		$brand_tax = isset( $settings['brand_taxonomy'] ) ? sanitize_key( (string) $settings['brand_taxonomy'] ) : '';
		if ( $brand_tax && taxonomy_exists( $brand_tax ) ) { $out['taxonomies'][$brand_tax] = wp_get_object_terms( $id, $brand_tax, array('fields'=>'ids') ); }
		$out['attributes'] = array();
		foreach ( $p->get_attributes() as $a ) {
			$out['attributes'][] = array('name'=>$a->get_name(),'visible'=>$a->get_visible(),'variation'=>$a->get_variation(),'options'=>$a->get_options());
		}
		if ( $include_atum && AAA_API_Lokey_Atum_Bridge::is_active() ) { $out['atum'] = AAA_API_Lokey_Atum_Bridge::read_row( $id ); }
		return $out;
	}
}
