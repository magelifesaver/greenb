<?php
namespace MBViews\Blocks;

use WP_REST_Server;
use MBViews\Renderer;

class View {
	private $renderer;

	public function __construct( Renderer $renderer ) {
		$this->renderer = $renderer;

		$this->register_block();
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_block(): void {
		register_block_type( MBV_DIR . '/blocks/view/build', [
			'render_callback' => [ $this, 'render_block' ],
		] );
	}

	public function register_routes(): void {
		register_rest_route( 'mbv', 'list', [
			'method'              => WP_REST_Server::READABLE,
			'permission_callback' => [ $this, 'has_permission' ],
			'callback'            => [ $this, 'get_views' ],
		] );
	}

	public function has_permission(): bool {
		return current_user_can( 'edit_posts' );
	}

	public function get_views(): array {
		$views = get_posts( [
			'post_type'              => 'mb-views',
			'posts_per_page'         => -1,
			'post_status'            => [ 'publish', 'draft' ],

			'orderby'                => 'title',
			'order'                  => 'ASC',
			'nopaging'               => true,

			'no_found_rows'          => false,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		] );

		$result = [];
		foreach ( $views as $v ) {
			$result[] = [
				'id'    => $v->ID,
				'title' => $v->post_title,
			];
		}

		return $result;
	}

	public function render_block( $attributes ) {
		$id = $attributes['id'] ?? 0;

		return $id ? $this->renderer->render( $id ) : __( '(No view selected)', 'mb-views' );
	}
}
