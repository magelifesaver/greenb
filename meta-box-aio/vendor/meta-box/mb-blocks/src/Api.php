<?php

namespace MBBlocks;

class Api {
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		// Register POST /mb-blocks/v1/block/:id/get_form endpoint
		// This endpoint is used to fill the meta box with block data and return the HTML
		// back to the client.
		register_rest_route( 'mb-blocks/v1', '/block/(?P<id>.+)/get_form', [ 
			'methods' => \WP_REST_Server::EDITABLE,
			'callback' => [ $this, 'get_form' ],
			'permission_callback' => 'is_user_logged_in',
		] );
	}

	/**
	 * Get the meta box form for the block.
	 * This function is used to fill the meta box with block data and return the HTML back to the client.
	 */
	public function get_form( \WP_REST_Request $request ) {
		$data = $request->get_json_params();

		$attributes = $data['attributes'] ?? [];
		$attributes = wp_unslash( $attributes );

		// Because default value are MB parsed value so we neeed to 
		// remove default attributes so Meta Box can fill in the correct values
		$block = mb_get_block( $data['block'] );

		// Get related meta box
		$meta_box_id = str_replace( 'meta-box/', '', $data['block'] );
		$meta_box    = rwmb_get_registry( 'meta_box' )->get( $meta_box_id );

		if ( empty ( $meta_box ) || ! isset( $meta_box->meta_box['type'] ) || $meta_box->meta_box['type'] !== 'block' ) {
			return new \WP_REST_Response( [ 'html' => '' ] );
		}
		
		$meta_box->set_block_data( $attributes );

		ob_start();
		// Set correct get_param ID from Ajax request to make get_param meta storage get correct values.
		$meta_box->object_id = $data['post_id'];
		$meta_box->show();
		$html = ob_get_clean();

		return new \WP_REST_Response( compact( 'html' ) );
	}
}