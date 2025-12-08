<?php
namespace MBViews;

use WP_Query;

class Export {
	public function __construct() {
		add_filter( 'post_row_actions', [ $this, 'add_export_link' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'export' ] );
	}

	public function add_export_link( $actions, $post ) {
		if ( 'mb-views' === $post->post_type ) {
			$url               = wp_nonce_url( add_query_arg( [
				'action' => 'mbv-export',
				'post[]' => $post->ID,
			] ), 'bulk-posts' ); // @see WP_List_Table::display_tablenav()
			$actions['export'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Export', 'mb-views' ) . '</a>';
		}

		return $actions;
	}

	public function export() {
		$action  = isset( $_REQUEST['action'] ) && 'mbv-export' === $_REQUEST['action'];
		$action2 = isset( $_REQUEST['action2'] ) && 'mbv-export' === $_REQUEST['action2'];

		if ( ( ! $action && ! $action2 ) || empty( $_REQUEST['post'] ) ) {
			return;
		}

		check_ajax_referer( 'bulk-posts' );

		$post_ids = wp_parse_id_list( wp_unslash( $_REQUEST['post'] ) );

		$query = new WP_Query( [
			'post_type'              => 'mb-views',
			'post__in'               => $post_ids,
			'posts_per_page'         => count( $post_ids ),
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		] );

		$data = [];
		foreach ( $query->posts as $post ) {
			$data[] = [
				'post_type'             => 'mb-views',
				'post_title'            => $post->post_title,
				'post_date'             => $post->post_date,
				'post_status'           => $post->post_status,
				'post_content'          => $post->post_content,
				'post_excerpt'          => $post->post_excerpt,
				'post_content_filtered' => $post->post_content_filtered,
				'settings'              => [
					'code'               => get_post_meta( $post->ID, 'code', true ),
					'mode'               => get_post_meta( $post->ID, 'mode', true ),
					'type'               => get_post_meta( $post->ID, 'type', true ),
					'position'           => get_post_meta( $post->ID, 'position', true ),
					'action_type'        => get_post_meta( $post->ID, 'action_type', true ),
					'action_priority'    => get_post_meta( $post->ID, 'action_priority', true ),
					'singular_locations' => get_post_meta( $post->ID, 'singular_locations', true ),
				],
			];
		}

		$file_name = 'views-exported';
		if ( count( $post_ids ) === 1 ) {
			$data      = reset( $data );
			$post      = $query->posts[0];
			$file_name = $post->post_name ?: sanitize_key( $post->post_title );
		}

		$data = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );

		header( 'Content-Type: application/octet-stream' );
		header( "Content-Disposition: attachment; filename=$file_name.json" );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $data ) );
		echo $data;
		die;
	}
}
