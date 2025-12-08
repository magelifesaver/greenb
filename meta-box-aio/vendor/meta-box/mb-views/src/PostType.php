<?php
namespace MBViews;

class PostType {
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_filter( 'post_updated_messages', [ $this, 'updated_messages' ] );
	}

	public function register_post_type() {
		$post_type = 'mb-views';

		$labels = [
			'name'               => _x( 'Views', 'post type general name', 'mb-views' ),
			'singular_name'      => _x( 'View', 'post type singular name', 'mb-views' ),
			'menu_name'          => _x( 'Views', 'admin menu', 'mb-views' ),
			'name_admin_bar'     => _x( 'Views', 'add new on admin bar', 'mb-views' ),
			'add_new'            => _x( 'Add New', 'mb-views', 'mb-views' ),
			'add_new_item'       => __( 'Add New View', 'mb-views' ),
			'new_item'           => __( 'New View', 'mb-views' ),
			'edit_item'          => __( 'Edit View', 'mb-views' ),
			'view_item'          => __( 'View View', 'mb-views' ),
			'all_items'          => __( 'Views', 'mb-views' ),
			'search_items'       => __( 'Search Views', 'mb-views' ),
			'parent_item_colon'  => __( 'Parent Views:', 'mb-views' ),
			'not_found'          => __( 'No views found.', 'mb-views' ),
			'not_found_in_trash' => __( 'No views found in Trash.', 'mb-views' ),
		];

		$args = [
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'meta-box',
			'rewrite'         => false,
			'capability_type' => 'post',
			'supports'        => [ 'title', 'revisions' ],

			'map_meta_cap'    => true,
			'capabilities'    => [
				// Meta capabilities.
				'edit_post'              => 'edit_mb_views',
				'read_post'              => 'read_mb_views',
				'delete_post'            => 'delete_mb_views',

				// Primitive capabilities used outside of map_meta_cap():
				'edit_posts'             => 'manage_options',
				'edit_others_posts'      => 'manage_options',
				'publish_posts'          => 'manage_options',
				'read_private_posts'     => 'manage_options',

				// Primitive capabilities used within map_meta_cap():
				'read'                   => 'read',
				'delete_posts'           => 'manage_options',
				'delete_private_posts'   => 'manage_options',
				'delete_published_posts' => 'manage_options',
				'delete_others_posts'    => 'manage_options',
				'edit_private_posts'     => 'manage_options',
				'edit_published_posts'   => 'manage_options',
				'create_posts'           => 'manage_options',
			],
		];

		register_post_type( $post_type, $args );
	}

	public function updated_messages( $messages ) {
		$messages['mb-views'] = [
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'View updated.', 'mb-views' ),
			2  => __( 'Custom field updated.', 'mb-views' ),
			3  => __( 'Custom field deleted.', 'mb-views' ),
			4  => __( 'View updated.', 'mb-views' ),
			// @codingStandardsIgnoreLine.
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'View restored to revision from %s', 'mb-views' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'View published.', 'mb-views' ),
			7  => __( 'View saved.', 'mb-views' ),
			8  => __( 'View submitted.', 'mb-views' ),
			9  => __( 'View scheduled.', 'mb-views' ),
			10 => __( 'View draft updated.', 'mb-views' ),
		];

		return $messages;
	}
}
