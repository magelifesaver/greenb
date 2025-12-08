<?php
namespace MBBlocks;

class Loader {
	public function __construct() {
		add_filter( 'rwmb_meta_box_class_name', [ $this, 'meta_box_class_name' ], 10, 2 );
		add_filter( 'rwmb_meta_type', [ $this, 'change_meta_type' ], 10, 3 );

		add_filter( 'block_type_metadata_settings', [ $this, 'change_metadata' ], 10, 2 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
	}

	public function change_metadata( array $settings, array $metadata ): array {
		return $this->is_block_supports_metabox( $metadata ) ? $this->prepare_block_data( $settings, $metadata ) : $settings;
	}

	private function is_block_supports_metabox( array $metadata ): bool {
		return isset( $metadata['name'] ) && str_starts_with( $metadata['name'], 'meta-box/' );
	}

	/**
	 * Add meta box data to be available in block attributes when rendering with block.json.
	 * Basically, we alter the block settings to include the meta box data.
	 *
	 * @param array $settings Block settings.
	 * @param array $metadata Meta box settings.
	 * @return array $settings
	 */
	private function prepare_block_data( array $settings, array $metadata ): array {
		// "mode" is meta box specified property and not supported in block.json,
		// so we move it to the top level to compatible with block.json.
		$settings['mode']    = $settings['supports']['mode'] ?? 'edit';
		$settings['context'] = $settings['supports']['context'] ?? 'side';

		unset( $settings['supports']['mode'] );
		unset( $settings['supports']['context'] );

		$meta_box_id = str_replace( 'meta-box/', '', $metadata['name'] );

		// Support render with mb_views
		if ( ! empty( $metadata['render'] ) && is_string( $metadata['render'] ) && str_starts_with( $metadata['render'], 'view:' ) ) {
			$renderer  = apply_filters( 'mbb_block_renderer', null );
			$view_name = str_replace( 'view:', '', $metadata['render'] );

			if ( $renderer ) {
				$settings['render_callback'] = static function ( $attributes, $content, $block ) use ( $renderer, $view_name ) {
                    $attributes = $attributes['views'] ?? $attributes;

					return $renderer->render( $view_name, compact( 'attributes', 'content', 'block' ) );
				};
			}
		}

		// Add data to block attributes for backward compatibility.
		if ( isset( $settings['attributes'] ) ) {
			if ( ! isset( $settings['attributes']['id'] ) ) {
				$settings['attributes']['id'] = [
					'type'    => 'string',
					'default' => $meta_box_id,
				];
			}

			if ( ! isset( $settings['attributes']['name'] ) ) {
				$settings['attributes']['name'] = [
					'type'    => 'string',
					'default' => $meta_box_id,
				];
			}

			if ( ! isset( $settings['attributes']['data'] ) ) {
				$settings['attributes']['data'] = [
					'type'    => 'object',
					'default' => [],
				];
			}
		}

		if ( ! isset( $settings['render_callback'] ) ) {
			return $settings;
		}

		$user_defined_callback = $settings['render_callback'];

		$settings['render_callback'] = function ( $attributes, $content, $block ) use ( $settings, $user_defined_callback, $meta_box_id ) {
			$meta_box = rwmb_get_registry( 'meta_box' )->get( $meta_box_id );

			// If meta box is not found, render the block as usual.
			if ( empty( $meta_box ) || ! isset( $meta_box->meta_box['type'] ) || $meta_box->meta_box['type'] !== 'block' ) {
				return call_user_func( $user_defined_callback, $attributes, $content, $block );
			}

			return self::prepare_render_callback_data( $attributes, $content, $block, $settings, $meta_box );
		};

		return $settings;
	}

	public static function prepare_render_callback_data( $attributes, $content, $block, $settings, $meta_box ) {
		// $attributes['data'] contains raw data from the block.
		// We loop through the key and get the value from the db
		// And set it to $attributes[$key]

		// Before we do that, we need to set the block data to the meta box
		$meta_box->set_block_data( $attributes );

		foreach ( $attributes['data'] as $key => $value ) {
			// If no value is set, we feed the default value so mb_get_block_field can return the same result with
			// $attributes[$key].
			if ( ! $value ) {
				add_filter( 'rwmb_get_value', function ( $v, $field ) use ( $key, $value, $attributes ) {
					if ( isset( $field['id'] ) && $field['id'] !== $key ) {
						return $v;
					}

					return $attributes[ $key ] ?? $v;
				}, 10, 4 );
			}

			$attributes[ $key ] = mb_get_block_field( $key );
		}

        // Prepare value for mb_views if it's installed
        if ( function_exists( 'mb_views_load' ) ) {
            $attributes['views'] = $attributes;
            $mb_views_meta_box_renderer = new \MBViews\Renderer\MetaBox();

            foreach ( $attributes['data'] as $key => $value ) {
                $field = array_filter( $meta_box->meta_box['fields'], function ( $field ) use ( $key ) {
                    return $field['id'] === $key;
                } );

                $field = reset( $field );

                if (  empty( $field ) ) {
                    continue;
                }

                $attributes['views'][ $key ] = ( 'group' === $field['type'] ) ?
					$mb_views_meta_box_renderer->parse_group_value( $value, $field ) :
					$mb_views_meta_box_renderer->parse_field_value( $value, $field );
            }
        }

		// Render the block
		$rendered = call_user_func( $settings['render_callback'], $attributes, $content, $block );

		// If block is called via WP-API, thats mean we are in the editor, we need to keep <InnerBlocks /> tag.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_GET['context'] ) && $_GET['context'] === 'edit' ) {
			return $rendered;
		}

		preg_match( '#<InnerBlocks(.*?)\/>#s', $rendered, $matches );

		if ( empty ( $matches ) ) {
			return $rendered;
		}

		[ $inner_blocks, $attributes ] = $matches;

    // If the block has class, style, or id, wrap the content with a div to match with the block editor.
		// otherwise, keep the content as is to backward compatible with the previous versions.
		if ( ! empty( $attributes ) && (
			 str_contains( $attributes, 'class') ||
			 str_contains( $attributes, 'style') ||
			 str_contains( $attributes, 'id' )
		) ) {
			$content = "<div $attributes>$content</div>";
		}

		$rendered = str_replace( $inner_blocks, $content, $rendered );

		return $rendered;
	}

	/**
	 * Filter meta box class name.
	 *
	 * @param  string $class_name Meta box class name.
	 * @param  array  $meta_box   Meta box settings.
	 * @return string
	 */
	public function meta_box_class_name( $class_name, $meta_box ) {
		if ( empty( $meta_box['type'] ) || 'block' !== $meta_box['type'] ) {
			return $class_name;
		}
		return empty( $meta_box['storage_type'] ) ? __NAMESPACE__ . '\Block' : __NAMESPACE__ . '\BlockPostMeta';
	}

	/**
	 * Filter meta type from object type and object id.
	 *
	 * @param string       $type            Meta type get from object type and object id.
	 * @param string       $object_type     Object type.
	 * @param ?string|?int $object_id Object ID.
	 *
	 * @return string
	 */
	public function change_meta_type( $type, $object_type, $object_id ): string {
		return 'block' === $object_type && is_string( $object_id ) ? str_replace( 'meta-box/', '', $object_id ) : $type;
	}

	/**
	 * Enqueue block assets for WP 6.3+.
	 *
	 * https://make.wordpress.org/core/2023/07/18/miscellaneous-editor-changes-in-wordpress-6-3/#post-editor-iframed
	 */
	public function enqueue_block_editor_assets() {
		wp_enqueue_style(
			'mb-blocks',
			MB_BLOCKS_URL . 'assets/blocks.css',
			[],
			filemtime( MB_BLOCKS_DIR . '/assets/blocks.css' )
		);

		$asset = include MB_BLOCKS_DIR . '/assets/build/index.asset.php';
		$asset['dependencies'][] = 'underscore';
		$asset['dependencies'][] = 'jquery';
		wp_enqueue_script(
			'mb-blocks',
			MB_BLOCKS_URL . 'assets/build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);


		// Pass all registered blocks to JavaScript.
		$blocks = mb_get_all_blocks();
		wp_localize_script( 'mb-blocks', 'mbBlocks', compact( 'blocks' ) );

		wp_set_script_translations( 'mb-blocks', 'mb-blocks', dirname( __DIR__ ) . '/languages' );
	}
}
