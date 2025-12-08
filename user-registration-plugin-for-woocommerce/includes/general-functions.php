<?php

if ( ! defined('ABSPATH') ) {
	return;
}

function is_any_field_in_dependable( $post_id ) {
	



	$dependable_values = (array) get_post_meta( $post_id , 'afreg_field_dependable_on', true ); 
	$dependable_values = custom_array_filter( $dependable_values );

	if ( isset( $dependable_values['dependable_field_id'] ) && isset( $dependable_values['checkbox'] ) ) {

		$dependable_field_option = isset( $dependable_values['dependable_field_option'] ) ? $dependable_values['dependable_field_option'] : '';
		$dependable_field_option = preg_replace( '/\s+/', ' ', trim( $dependable_field_option ) );

		$dependable_field_option = explode(',', $dependable_field_option);

		foreach ($dependable_field_option as $key => $value) {
			$dependable_field_option[ $key ] = preg_replace( '/\s+/', ' ', trim( $value ) ); 
		}
		$dependable_field_option = implode(',', $dependable_field_option);

		?>

		<input type="hidden" class="afreg-dependable-field-on" data-current_field_type="<?php echo esc_attr( get_post_meta( $post_id , 'afreg_field_type', true ) ); ?>" data-dependable_field_type="<?php echo esc_attr( get_post_meta( $dependable_values['dependable_field_id'] , 'afreg_field_type', true ) ); ?>" value ="<?php echo esc_attr( $dependable_values['dependable_field_id'] ); ?>" data-dependable_field_option="<?php echo esc_attr( $dependable_field_option ); ?>,">

		<?php 
	}

	$afreg_field_user_roles = (array) get_post_meta( $post_id , 'afreg_field_user_roles', true ); 
	$afreg_field_user_roles = custom_array_filter( $afreg_field_user_roles );

	if ( 'on' == get_post_meta( $post_id , 'afreg_is_dependable', true ) &&  !empty( $afreg_field_user_roles ) ) {  
		?>

		<input type="hidden" class="afreg-dependable-on-rules" value="<?php echo esc_attr( implode( ',' , (array) $afreg_field_user_roles ) ); ?>">

		<?php 
	}
}

function get_dependable_field_options_values( $current_post_id ) {
	$afreg_field_dependable_type = (array) get_post_meta( $current_post_id, 'afreg_field_dependable_on', true);
	$all_types                   =   'select multiselect multi_checkbox radio';

	if ( isset( $afreg_field_dependable_type['checkbox'] ) && !empty( $afreg_field_dependable_type['checkbox'] ) && !empty( $afreg_field_dependable_type['dependable_field_id'] ) ) {

		$dependable_field_id =   $afreg_field_dependable_type['dependable_field_id'];

		$dependable_field_option =   isset( $afreg_field_dependable_type['dependable_field_option'] ) ? $afreg_field_dependable_type['dependable_field_option'] : '';
		$afreg_field_file_type   = get_post_meta( $dependable_field_id , 'afreg_field_type', true );
		if ( str_contains( $all_types , $afreg_field_file_type ) && !empty( $dependable_field_option ) ) {

			$dependable_field_option = preg_replace( '/\s+/', ' ', trim( $dependable_field_option ) );
			$dependable_field_option = explode(',', $dependable_field_option);

			foreach ($dependable_field_option as $key => $value) {
				$dependable_field_option[ $key ] = preg_replace( '/\s+/', ' ', trim( $value ) ); 
			}
			$dependable_field_option = implode(',', $dependable_field_option);
			return $dependable_field_option;
		}
	}
}

function is_that_dependable( $current_post_id ) {

	$afreg_field_dependable_type = (array) get_post_meta( $current_post_id, 'afreg_field_dependable_on', true);

	if ( isset( $afreg_field_dependable_type['checkbox'] ) && !empty( $afreg_field_dependable_type['checkbox'] ) && !empty( $afreg_field_dependable_type['dependable_field_id'] ) ) {

		$dependable_field_id = (int) $afreg_field_dependable_type['dependable_field_id'];
		return $dependable_field_id;
	}
}


function get_all_dependable_post_ids() {

	$afreg_fields_all_posts = get_posts( array(
		'post_type'      => 'afreg_fields',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'fields'         => 'ids',
	) );  
	$get_all_dependable_ids = array();

	foreach ( $afreg_fields_all_posts as $current_post_id ) {

		$afreg_field_dependable_type = is_that_dependable( $current_post_id  );

		if ( $afreg_field_dependable_type   &&   !empty( $afreg_field_dependable_type ) ) {

			$get_all_dependable_ids[ $current_post_id ] = $afreg_field_dependable_type;

		}

	}

	return $get_all_dependable_ids;
}

if ( ! function_exists('custom_array_filter') ) {

	function custom_array_filter( $filters ) {

		$filters_new = (array) $filters;

		return array_filter($filters_new, function ( $current_value, $current_key ) {
			return ( '' !== $current_value  && '' !== $current_key );
		}, ARRAY_FILTER_USE_BOTH);
	}

}

function af_urf_getFieldBySlug( $slug ) {

	$args     = array(
		'name'             => $slug,
		'post_type'        => 'def_reg_fields',
		'post_status'      => 'publish',
		'suppress_filters' => false,
		'numberposts'      => 1,
	);
	$my_posts = get_posts($args);

	if ( $my_posts ) {
		return $my_posts;
	}
}