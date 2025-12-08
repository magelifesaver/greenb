<?php
if ( ! function_exists( 'mb_get_block_field' ) ) {
	function mb_get_block_field( $field_id, $args = [] ) {
		$block_name          = MBBlocks\ActiveBlock::get_block_name();
		$args['object_type'] = 'block';
		return rwmb_get_value( $field_id, $args, $block_name );
	}
}

if ( ! function_exists( 'mb_the_block_field' ) ) {
	function mb_the_block_field( $field_id, $args = [], $is_echo = true ) {
		$block_name          = MBBlocks\ActiveBlock::get_block_name();
		$args['object_type'] = 'block';
		return rwmb_the_value( $field_id, $args, $block_name, $is_echo );
	}
}

if ( ! function_exists( 'mb_is_block_exists' ) ) {
	function mb_is_block_exists( $block_name ) {
		return \WP_Block_Type_Registry::get_instance()->is_registered( $block_name );
	}
}

if ( ! function_exists( 'mb_merge_meta_box_with_blocks' ) ) {
	function mb_merge_meta_box_with_blocks( array $meta_box ): array {
		if ( empty( $meta_box['type'] ) || 'block' !== $meta_box['type'] ) {
			return $meta_box;
		}

		if ( ! mb_is_block_exists( 'meta-box/' . $meta_box['id'] ) ) {
			return $meta_box;
		}

		$block    = \WP_Block_Type_Registry::get_instance()->get_registered( 'meta-box/' . $meta_box['id'] );
		$meta_box = array_merge( $meta_box, (array) $block );

		return $meta_box;
	}
}

/**
 * Get all blocks registered by Meta Box
 */
if ( ! function_exists( 'mb_get_all_blocks' ) ) {
	function mb_get_all_blocks(): array {
		$all_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

		return array_filter( $all_blocks, function ($block) {
			return str_starts_with( $block->name, 'meta-box/' );
		} );
	}
}

/**
 * Get block by name
 */
if ( ! function_exists( 'mb_get_block' ) ) {
	function mb_get_block( string $block_name ): ?\WP_Block_Type {
		return \WP_Block_Type_Registry::get_instance()->get_registered( $block_name );
	}
}
