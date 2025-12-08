<?php
namespace MBViews\Location;

use WP_REST_Server;
use WP_REST_Request;
use RWMB_Post_Field;
use RWMB_Taxonomy_Field;

class Data {
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		$params = [
			'method'              => WP_REST_Server::READABLE,
			'permission_callback' => [ $this, 'has_permission' ],
			'args'                => [
				'name'     => [
					'sanitize_callback' => 'sanitize_text_field',
				],
				'term'     => [
					'sanitize_callback' => 'sanitize_text_field',
				],
				'selected' => [
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		];
		register_rest_route( 'mbv/location', 'terms', array_merge( $params, [
			'callback' => [ $this, 'get_terms' ],
		] ) );
		register_rest_route( 'mbv/location', 'posts', array_merge( $params, [
			'callback' => [ $this, 'get_posts' ],
		] ) );
	}

	public function get_terms( WP_REST_Request $request ) {
		$search_term        = $request->get_param( 'term' );
		$name               = $request->get_param( 'name' );
		list( , $taxonomy ) = explode( ':', $name );

		$field = [
			'query_args' => [
				'taxonomy'   => $taxonomy,
				'name__like' => $search_term,
				'orderby'    => 'name',
				'number'     => 10,
			],
		];
		$data  = RWMB_Taxonomy_Field::query( null, $field );

		return array_values( $data );
	}

	public function get_posts( WP_REST_Request $request ): array {
		$search_term       = $request->get_param( 'term' );
		$name              = $request->get_param( 'name' );
		list( $post_type ) = explode( ':', $name );

		global $wpdb;
		$sql   = "SELECT ID, post_title FROM $wpdb->posts WHERE post_type=%s AND post_title LIKE '%%" . esc_sql( $search_term ) . "%%' ORDER BY post_title ASC LIMIT 10";
		$sql   = $wpdb->prepare( $sql, $post_type );

		$posts = $wpdb->get_results( $sql );

		$options = [];
		foreach ( $posts as $post ) {
			$options[] = [
				'value' => $post->ID,
				'label' => $post->post_title,
			];
		}

		return $options;
	}

	public function has_permission() {
		return current_user_can( 'manage_options' );
	}
}
