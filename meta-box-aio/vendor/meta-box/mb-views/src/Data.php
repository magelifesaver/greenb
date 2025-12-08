<?php
namespace MBViews;

use WP_REST_Server;
use WP_REST_Request;
use WP_Query;

class Data {
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		$params = [
			'method'              => WP_REST_Server::READABLE,
			'permission_callback' => [ $this, 'has_permission' ],
		];
		register_rest_route( 'mbv', 'meta-boxes', array_merge( $params, [
			'callback' => [ $this, 'get_meta_boxes' ],
			'args'     => [
				'object_type' => [
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] ) );
		register_rest_route( 'mbv', 'views', array_merge( $params, [
			'callback' => [ $this, 'get_views' ],
		] ) );
		if ( class_exists( 'MB_Relationships_API' ) ) {
			register_rest_route( 'mbv', 'relationships', array_merge( $params, [
				'callback' => [ $this, 'get_relationships' ],
			] ) );
		}
	}

	public function get_meta_boxes( WP_REST_Request $request ) {
		return rwmb_get_registry( 'meta_box' )->get_by( [
			'object_type' => $request->get_param( 'object_type' ),
		] );
	}

	public function get_views() {
		$args  = [
			'post_type'              => 'mb-views',
			'post_status'            => 'any',

			'order'                  => 'ASC',
			'orderby'                => 'title',
			'nopaging'               => true,

			'no_found_rows'          => false,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		];
		$query = new WP_Query( $args );
		return $query->posts;
	}

	public function get_relationships() {
		return \MB_Relationships_API::get_all_relationships_settings();
	}

	public function has_permission() {
		return current_user_can( 'manage_options' );
	}
}
