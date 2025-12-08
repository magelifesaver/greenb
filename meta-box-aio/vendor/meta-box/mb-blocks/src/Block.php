<?php
namespace MBBlocks;

use MBBlocks\Utils\Resolver;
use RWMB_Field;

class Block extends \RW_Meta_Box {
	public $storage;

	public static function normalize( $meta_box ) {
		// Set default context to 'side'.
		if ( empty( $meta_box['context'] ) ) {
			$meta_box['context'] = 'side';
		}

		$meta_box = parent::normalize( $meta_box );

		$meta_box = wp_parse_args( $meta_box, [
			'description' => '',
			'icon'        => 'schedule',
			'category'    => 'design',
			'keywords'    => [],
			'supports'    => [],
		] );

		$meta_box = mb_merge_meta_box_with_blocks( $meta_box );

		// Block preview.
		if ( empty( $meta_box['preview'] ) ) {
			return $meta_box;
		}

		$meta_box['example'] = [
			'attributes' => [
				'data' => $meta_box['preview'],
			],
		];

		unset( $meta_box['preview'] );

		return $meta_box;
	}

	protected function object_hooks() {
		$this->register_block_type();

		add_action( 'wp_ajax_mb_blocks_save', [ $this, 'save_block' ] );
	}

	public function save_block() {
		$request = rwmb_request();

		if ( "meta-box/$this->id" !== $request->post( 'block' ) ) {
			return;
		}

		$data = $_POST;

		$data['rwmb_cleanup'] = array_map( function ( $cleanup ) {
			return stripslashes( $cleanup );
		}, $data['rwmb_cleanup'] );

		$data = $request->cleanup( $data );
		$request->set_post_data( $data );

		$this->set_block_data( $data );
		$this->save_post( $request->post( 'post_id' ) );

		$block_name = $data['block'];
		$block      = mb_get_block( $block_name );

		$attributes = $block->attributes;

		// Filter out non-attribute keys before sending it back to the client
		$data = $this->storage->get_data();
		$data = $this->sanitize_data( $data, $attributes );

		wp_send_json_success( $data );
	}

	private function register_block_type() {
		// If the block is already registered, don't register it again.
		// Blocks register via block.json file are already registered.
		if ( mb_is_block_exists( "meta-box/{$this->id}" ) ) {
			return;
		}

		$block_keys = [
			'title',
			'icon',
			'category',
			'keywords',
			'supports',
			'mode',
			'context',
			'attributes',
			'description',
		];

		$metadata = array_intersect_key( $this->meta_box, array_flip( $block_keys ) );

		$default_attributes = [
			'id'   => [
				'type'    => 'string',
				'default' => $this->id,
			],
			'name' => [
				'type'    => 'string',
				'default' => $this->id,
			],
			'data' => [
				'type'    => 'object',
				'default' => $this->example['attributes']['data'] ?? [],
			],
			'anchor' => [
				'type'    => 'string',
				'default' => '',
			],
		];

		$metadata = array_merge( $metadata, [
			// Use API version 3 for side blocks as it works with iframed content.
			'api_version'           => $metadata['context'] === 'side' ? 3 : 2,
			'editor_script_handles' => ['mb-blocks'],
			'editor_style_handles'  => ['mb-blocks'],
			'render_callback'       => [ $this, 'render' ],
			'attributes'            => ! empty( $this->attributes ) ? $this->attributes : $default_attributes,
		] );

		register_block_type( "meta-box/{$this->id}", $metadata );
	}

	public function enqueue() {
		parent::enqueue();

		if ( is_admin() && ! $this->is_edit_screen() ) {
			return;
		}

		$this->enqueue_block_assets();
	}

	public function render( $attributes = [], $content = '', $block = null ) {
		$render_callback = Loader::prepare_render_callback_data( $attributes, $content, $block, [
			'render_callback' => function ( $attributes, $content, $block ) {
				ob_start();
				$post_id    = get_the_ID();
				$is_preview = defined( 'REST_REQUEST' ) && REST_REQUEST;
				$this->render_block( $attributes, $content, $block, $is_preview, $post_id );
				$html = ob_get_clean();
				return $html;
			},
		], $this );

		return $render_callback;
	}

	public function set_block_data( &$attributes ) {
		$attributes['name'] = $attributes['name'] ?? $this->id;
		$data               = $attributes['data'] ?? $attributes;
		$this->storage->set_data( $data );
		ActiveBlock::set_block_name( $this->id );
	}

	public function render_block( $attributes = [], $content = null, $block = null, $is_preview = false, $post_id = null ) {
		$this->enqueue_block_assets();

		if ( is_string( $this->render_callback ) && str_starts_with( $this->render_callback, 'view:' ) ) {
			$renderer  = apply_filters( 'mbb_block_renderer', null );
			$view_name = str_replace( 'view:', '', $this->render_callback );

			if ( $renderer ) {
				$attributes = $attributes['views'] ?? $attributes;
				echo $renderer->render( $view_name, compact( 'attributes', 'content', 'block' ) );
				return;
			}
		}

		if ( $this->render_callback ) {
			$resolver = new Resolver();

			$resolver->bind( compact( [
				'attributes',
				'content',
				'block',
				'is_preview',
				'post_id',
			] ) );

			$resolver->resolve( $this->render_callback );

			return;
		}

		if ( file_exists( $this->render_template ) ) {
			include $this->render_template;
		} else {
			locate_template( $this->render_template, true );
		}
	}

	private function enqueue_block_assets(): void {
		$handle = "meta-box/$this->id";

		if ( $this->enqueue_style ) {
			wp_enqueue_style( $handle, $this->enqueue_style, [], $this->version );
		}

		if ( $this->enqueue_script ) {
			wp_enqueue_script( $handle, $this->enqueue_script, [ 'jquery' ], $this->version, true );
		}

		if ( $this->enqueue_assets && is_callable( $this->enqueue_assets ) ) {
			call_user_func( $this->enqueue_assets );
		}

		if ( ! is_admin() ) {
			return;
		}
		$use_fontawesome = false;
		if ( is_string( $this->icon ) && str_contains( $this->icon, 'fa-' ) ) {
			$use_fontawesome = true;
		}
		if ( is_array( $this->icon ) && ! empty( $this->icon['src'] ) && is_string( $this->icon['src'] ) && str_contains( $this->icon['src'], 'fa-' ) ) {
			$use_fontawesome = true;
		}
		if ( $use_fontawesome ) {
			wp_enqueue_style( 'fontawesome-free', 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.2/css/all.min.css', [], '5.15.2' );
		}
	}

	public function get_storage() {
		if ( null === $this->storage ) {
			$this->storage = new Storages\Attributes();
		}
		return $this->storage;
	}

	public function register_fields() {
		$field_registry = rwmb_get_registry( 'field' );

		foreach ( $this->fields as $field ) {
			$field_registry->add( $field, $this->id, 'block' );
		}
	}

	public function is_edit_screen( $screen = null ) {
		if ( ! ( $screen instanceof WP_Screen ) ) {
			$screen = get_current_screen();
		}
		return 'post' === $screen->base && use_block_editor_for_post_type( $screen->post_type );
	}

	/**
	 * We need to sanitize the data before sending it back to the client
	 * For example, if your block attribute is number, but the response is string,
	 * it won't produce the updated value in both editor and frontend.
	 *
	 * @return array
	 */
	private function sanitize_data( array $data, array $attributes ) {
		$data = $this->remove_unwanted_keys( $data );

		foreach ( $attributes as $key => $value ) {
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}

			if ( 'string' === $value['type'] ) {
				$data[ $key ] = (string) $data[ $key ];
			}

			// Cast to number, we use +0 to cast to number
			// because it can handle both integer and float
			if ( 'number' === $value['type'] ) {
				$data[ $key ] = $data[ $key ] + 0;
			}

			if ( 'boolean' === $value['type'] ) {
				$data[ $key ] = (bool) $data[ $key ];
			}

			// If the attribute is an object but the response is falsey, we need to set it to default value
			// if ( 'object' === $value['type'] && empty( $data[ $key ] ) ) {
			// $data[ $key ] = $value['default'];
			// }
		}

		if ( ! empty( $attributes['data'] ) ) {
			$data = [ 'data' => $data ];
		}

		return $data;
	}

	private function remove_unwanted_keys( array $data ) {
		$ignore_keys = [
			'post_id',
			'_wp_http_referer',
			'block',
			'action',
		];

		return array_filter( $data, function ( $value, $key ) use ( $ignore_keys ) {
			return ! str_contains( $key, 'nonce_' ) && ! in_array( $key, $ignore_keys, true );
		}, ARRAY_FILTER_USE_BOTH );
	}
}
