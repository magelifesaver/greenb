<?php
namespace MBViews;

use eLightUp\Twig\Environment;
use eLightUp\Twig\Loader\FilesystemLoader;
use eLightUp\Twig\Loader\ChainLoader;

class Renderer {
	private $meta_box_renderer;

	public function __construct( $meta_box_renderer ) {
		$this->meta_box_renderer = $meta_box_renderer;
	}

	/**
	 * Render a view.
	 *
	 * @param \WP_Post|int|string $view Post object, view ID or view name (slug).
	 */
	public function render( $view, $data = [] ) {
		// Allow developers to bypass the default renderer and use an alternative method like Timber.
		if ( array_key_exists( 'mbv_render_output', $GLOBALS['wp_filter'] ) ) {
			return apply_filters( 'mbv_render_output', '', $view, $data );
		}

		// Allow developers to add Twig Filesystem Loader(s) by providing path(s).
		$paths = apply_filters( 'mbv_fs_paths', [] );

		if ( is_numeric( $view ) ) {            // Get view by ID.
			$view_post = get_post( $view );
		} elseif ( is_string( $view ) ) {       // Get view by slug.
			$view_post = get_page_by_path( $view, OBJECT, 'mb-views' );
		} elseif ( is_a( $view, 'WP_Post' ) ) { // Get the view post object.
			$view_post = $view;
		}

		$render = '';
		if ( ! empty( $view_post ) ) {
			$render = $view_post->post_name;

			// Do not render unpublished views.
			if ( $view_post->post_status !== 'publish' ) {
				return '';
			}
		} elseif ( count( $paths ) ) { // Render view with Filesytem loader.
			$render = $view;
		}

		// Return early if render template is empty
		if ( empty( $render ) ) {
			return '';
		}

		// Plugin custom loader.
		$mb_loader = new TwigLoader();

		// Filesystem Loader.
		$fs_loader = new FilesystemLoader( $paths );
		do_action( 'mbv_fs_loader_init', $fs_loader );

		$loaders = new ChainLoader( [ $mb_loader, $fs_loader ] );
		$twig    = new Environment( $loaders, [ 'autoescape' => false ] );

		// Proxy for all PHP/WordPress functions under 'mb' namespace.
		$twig->addGlobal( 'mb', new TwigProxy( $this->meta_box_renderer ) );

		$twig = apply_filters( 'mbv_twig_env', $twig );

		$data = array_merge( $this->get_data(), $data );
		$data = apply_filters( 'mbv_data', $data, $twig );

		$output = $twig->render( $render, $data );

		// Allow to run shortcodes, blocks.
		$output = do_shortcode( $output );
		$output = do_blocks( $output );

		$this->add_script_map( $output );

		return $output;
	}

	private function add_script_map( $html ) {
		global $wp_scripts;

		if ( false !== strpos( $html, 'rwmb-map-canvas' ) ) {
			if ( isset( $wp_scripts->registered['rwmb-map-frontend'] ) ) {
				if ( ! isset( $wp_scripts->registered['rwmb-map-frontend-mbview'] ) ) {
					wp_enqueue_script( 'rwmb-map-frontend-mbview', $wp_scripts->registered['rwmb-map-frontend']->src, $wp_scripts->registered['rwmb-map-frontend']->deps, $wp_scripts->registered['rwmb-map-frontend']->ver, true );
				}
				wp_dequeue_script( 'rwmb-map-frontend' );
			}
		}

		if ( false !== strpos( $html, 'rwmb-osm-canvas' ) ) {
			if ( isset( $wp_scripts->registered['rwmb-osm-frontend'] ) ) {
				if ( ! isset( $wp_scripts->registered['rwmb-osm-frontend-mbview'] ) ) {
					wp_enqueue_script( 'rwmb-osm-frontend-mbview', $wp_scripts->registered['rwmb-osm-frontend']->src, $wp_scripts->registered['rwmb-osm-frontend']->deps, $wp_scripts->registered['rwmb-osm-frontend']->ver, true );
				}
				wp_dequeue_script( 'rwmb-osm-frontend' );
			}
		}
	}

	private function get_data() {
		$data = [];
		$data = array_merge( $data, $this->get_post_data() );
		$data = array_merge( $data, $this->get_term_data() );
		$data = array_merge( $data, $this->get_user_data() );
		$data = array_merge( $data, $this->get_site_data() );
		if ( class_exists( 'MB_Relationships_API' ) ) {
			$data = array_merge( $data, $this->get_relationship_data() );
		}

		return $data;
	}

	private function get_post_data() {
		$posts = $GLOBALS['wp_query']->posts;
		if ( empty( $posts ) || ! is_array( $posts ) ) {
			$posts = [];
		}
		$posts = array_map( function( $post ) {
			$post_object = new Renderer\Post( $this->meta_box_renderer );
			$post_object->set_post( $post );
			return $post_object;
		}, $posts );

		return [
			'query' => [ 'posts' => $posts ],
			'post'  => empty( $posts ) ? null : reset( $posts ),
		];
	}

	private function get_term_data() {
		$term_object = new Renderer\Term( $this->meta_box_renderer );
		$term_object->set_term( get_queried_object() );

		return [
			'term' => $term_object,
		];
	}

	private function get_user_data() {
		$user_object = new Renderer\User( $this->meta_box_renderer );
		$user_object->set_user_id( get_current_user_id() );

		$post = get_post();
		// Return early if author has no post
		if ( empty( $post ) ) {
			return [
				'user'   => $user_object,
				'author' => null,
			];
		}
		$author_object = new Renderer\User( $this->meta_box_renderer );
		$author_object->set_user_id( $post->post_author );

		return [
			'user'   => $user_object,
			'author' => $author_object,
		];
	}

	private function get_site_data() {
		return [ 'site' => new Renderer\Site( $this->meta_box_renderer ) ];
	}

	private function get_relationship_data() {
		$relationships = array_map( function( $relationship ) {
			$relationship_object = new Renderer\Relationship( $this->meta_box_renderer );
			$relationship_object->set_relationship( $relationship );
			return $relationship_object;
		}, \MB_Relationships_API::get_all_relationships_settings() );
		return [ 'relationships' => $relationships ];
	}
}
