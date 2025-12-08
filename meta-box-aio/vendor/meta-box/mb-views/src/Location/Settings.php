<?php
namespace MBViews\Location;

class Settings {
	public function __construct() {
		add_action( 'rwmb_mbv-settings_after_save_post', [ $this, 'save' ] );
	}

	public function save( $post_id ) {
		$this->save_locations( $post_id, 'singular' );
		$this->save_locations( $post_id, 'archive' );
	}

	private function save_locations( $post_id, $type ) {
		$name      = "{$type}_locations";
		$locations = rwmb_request()->post( $name );
		if ( empty( $locations ) ) {
			delete_post_meta( $post_id, $name );
		} else {
			update_post_meta( $post_id, $name, $locations );
		}
	}

	public function get_meta_box() {
		return [
			'title'      => __( 'Settings', 'mb-views' ),
			'id'         => 'mbv-settings',
			'post_types' => [ 'mb-views' ],
			'fields'     => [
				[
					'name'    => __( 'Type', 'mb-views' ),
					'id'      => 'type',
					'tooltip' => __( 'How to render the view', 'mb-views' ),
					'type'    => 'select',
					'std'     => 'custom',
					'options' => [
						'singular' => __( 'Singular', 'mb-views' ),
						'archive'  => __( 'Archive', 'mb-views' ),
						'action'   => __( 'Action', 'mb-views' ),
						'code'     => __( 'Code', 'mb-views' ),
						'custom'   => __( 'Shortcode', 'mb-views' ),
						'block'    => __( 'Block', 'mb-views' ),
					],
				],
				[
					'name'    => '&nbsp;',
					'type'    => 'custom_html',
					'std'     => __( 'Please use the shortcode to insert the view manually.', 'mb-view' ) . ( rwmb_request()->get( 'post' ) ? '' : ' ' . __( 'The shortcode is available after saving this view.', 'mb-views' ) ),
					'visible' => [ 'type', 'custom' ],
				],
				[
					'name'    => __( 'Action name', 'mb-views' ),
					'id'      => 'mbv_action', // Add prefix because 'action' is a reserved WordPress param.
					'type'    => 'text',
					'visible' => [ 'type', 'action' ],
					'tooltip' => __( 'The name of the action where the view is rendered', 'mb-views' ),
				],
				[
					'name'              => __( 'Code', 'mb-views' ),
					'id'                => 'code',
					'type'              => 'textarea',
					'sanitize_callback' => 'none',
					// Translators: %s - Link to conditional tags documentation.
					'desc'              => sprintf( __( 'Use <a href="%s" target="_blank">conditional tags</a> or any general PHP code to define the rules where the view is rendered. Do not wrap the code in opening and closing PHP tags.', 'mb-views' ), 'https://developer.wordpress.org/themes/basics/conditional-tags/' ),
					'visible'           => [ 'type', 'code' ],
					'tooltip'           => __( 'The PHP code that defines the logic where the view is rendered', 'mb-views' ),
				],
				[
					'name'    => __( 'Priority', 'mb-views' ),
					'id'      => 'action_priority',
					'type'    => 'number',
					'std'     => 10,
					'visible' => [ 'type', 'action' ],
					'tooltip' => __( 'The priority for callback to render the view', 'mb-views' ),
				],
				[
					'name'    => __( 'Run on', 'mb-views' ),
					'id'      => 'action_type',
					'type'    => 'select',
					'std'     => 'site',
					'options' => [
						'site'     => __( 'All pages', 'mb-views' ),
						'singular' => __( 'Singular pages', 'mb-views' ),
						'archive'  => __( 'Archive pages', 'mb-views' ),
					],
					'visible' => [ 'type', 'action' ],
					'tooltip' => __( 'The scope of the website to render the view', 'mb-views' ),
				],
				[
					'name'     => __( 'Location', 'mb-views' ),
					'tooltip'  => __( 'Where to show the view. For each rule, maximum 10 items are displayed. To select other items, please use the search.', 'mb-views' ),
					'type'     => 'custom_html',
					'callback' => [ $this, 'render_location_singular' ],
					'visible'  => [
						'when'     => [
							[ 'type', 'singular' ],
							[ 'action_type', 'singular' ],
						],
						'relation' => 'or',
					],
				],
				[
					'name'     => __( 'Location', 'mb-views' ),
					'type'     => 'custom_html',
					'tooltip'  => __( 'Where to show the view. For each rule, maximum 10 items are displayed. To select other items, please use the search.', 'mb-views' ),
					'callback' => [ $this, 'render_location_archive' ],
					'visible'  => [
						'when'     => [
							[ 'type', 'archive' ],
							[ 'action_type', 'archive' ],
						],
						'relation' => 'or',
					],
				],
				[
					'name'    => __( 'Render for', 'mb-views' ),
					'id'      => 'mode',
					'tooltip' => __( 'Which part of the page to render the view for', 'mb-views' ),
					'type'    => 'radio',
					'inline'  => false,
					'std'     => 'layout',
					'options' => [
						'page'    => __( 'The whole page layout, including header and footer', 'mb-views' ),
						'layout'  => __( 'The layout between header and footer', 'mb-views' ),
						'content' => __( 'Only the post content area', 'mb-views' ),
					],
					'visible' => [ 'type', 'in', [ 'singular', 'archive', 'code' ] ],
				],
				[
					'name'    => __( 'Position', 'mb-views' ),
					'id'      => 'position',
					'tooltip' => __( 'Where in the post content to render the view', 'mb-views' ),
					'type'    => 'radio',
					'inline'  => false,
					'std'     => 'after_content',
					'options' => [
						'before_content'  => __( 'Before the post content', 'mb-views' ),
						'after_content'   => __( 'After the post content', 'mb-views' ),
						'replace_content' => __( 'Replaces the post content', 'mb-views' ),
					],
					'visible' => [ 'mode', 'content' ],
				],
				[
					'name'    => __( 'Order', 'mb-views' ),
					'id'      => 'menu_order',
					'tooltip' => __( 'Defines the render order of the view', 'mb-views' ),
					'type'    => 'number',
					'desc'    => __( 'Views with a lower order will render first', 'mb-views' ),
					'visible' => [ 'type', 'not in', ['custom', 'block'] ],
				],
				[
					'name'    => __( 'Name', 'mb-views' ),
					'id'      => 'post_name',
					'tooltip' => __( 'The name of the view', 'mb-views' ),
					'type'    => 'text',
					'desc'    => __( 'View name, for including or inheriting in other views.', 'mb-views' ),
				],
			],
		];
	}

	public function render_location_singular() {
		ob_start();
		require MBV_DIR . '/views/location-singular.php';
		return ob_get_clean();
	}

	public function render_location_archive() {
		ob_start();
		require MBV_DIR . '/views/location-archive.php';
		return ob_get_clean();
	}

	public function get_localized_data() {
		$post_id        = rwmb_request()->get( 'post' );
		$archive_rules  = get_post_meta( $post_id, 'archive_locations', true );
		$archive_rules  = $archive_rules ?: [];
		$singular_rules = get_post_meta( $post_id, 'singular_locations', true );
		$singular_rules = $singular_rules ?: [];

		return [
			'singularLocations' => $this->get_singular_locations(),
			'archiveLocations'  => $this->get_archive_locations(),
			'singularRules'     => $singular_rules,
			'archiveRules'      => $archive_rules,
			'rest_url'          => esc_url_raw( rest_url() ),
			'rest_nonce'        => wp_create_nonce( 'wp_rest' ),
			'text'              => [
				'addGroup' => __( 'Add Rule Group', 'mb-views' ),
				'and'      => __( 'And', 'mb-views' ),
				'or'       => __( 'Or', 'mb-views' ),
				'select'   => __( 'Select', 'mb-views' ),
				'all'      => __( 'All', 'mb-views' ),
			],
		];
	}

	public static function get_singular_locations() {
		$locations = [
			[
				'label'   => __( 'General', 'mb-views' ),
				'options' => [
					[
						'value' => 'general:all',
						'label' => __( 'All Singular', 'mb-views' ),
					],
				],
			],
		];

		$unsupported = [
			// Page builders.
			'elementor_library',
			'fl-builder-template',
		];

		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		$post_types = array_diff_key( $post_types, array_flip( $unsupported ) );
		foreach ( $post_types as $slug => $post_type ) {
			$post_group = [
				'label'   => $post_type->labels->singular_name,
				'options' => [
					[
						'value' => "$slug:post",
						'label' => $post_type->labels->singular_name,
					],
				],
			];

			$options = &$post_group['options'];

			// Taxonomies.
			$taxonomies = get_object_taxonomies( $slug, 'objects' );
			foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {
				$public = $taxonomy->public && $taxonomy->show_ui;
				if ( 'post_format' === $taxonomy_slug || ! $public ) {
					continue;
				}
				$options[] = [
					'value' => "$slug:$taxonomy_slug",
					'label' => $taxonomy->labels->singular_name,
				];
			}

			$locations[] = $post_group;
		}

		return $locations;
	}

	public static function get_archive_locations() {
		$locations = [
			[
				'label'   => __( 'General', 'mb-views' ),
				'options' => [
					[
						'value' => 'general:all',
						'label' => __( 'All Archives', 'mb-views' ),
					],
					[
						'value' => 'general:author',
						'label' => __( 'Author Archives', 'mb-views' ),
					],
					[
						'value' => 'general:date',
						'label' => __( 'Date Archives', 'mb-views' ),
					],
					[
						'value' => 'general:search',
						'label' => __( 'Search Results', 'mb-views' ),
					],
				],
			],
		];

		$unsupported = [
			// WordPress built-in post types.
			'page',
			'attachment',

			// Page builders.
			'elementor_library',
			'fl-builder-template',
		];
		$post_types  = get_post_types( [ 'public' => true ], 'objects' );
		$post_types  = array_diff_key( $post_types, array_flip( $unsupported ) );
		foreach ( $post_types as $slug => $post_type ) {
			$post_group = [
				'label'   => $post_type->labels->singular_name,
				'options' => [],
			];

			$options = &$post_group['options'];

			// Post type archive.
			if ( 'post' === $slug || $post_type->has_archive ) {
				$options[] = [
					'value' => "$slug:archive",
					// Translators: %s - Post type singular name.
					'label' => sprintf( __( '%s Archive', 'mb-views' ), $post_type->labels->singular_name ),
				];
			}

			// Taxonomies archives.
			$taxonomies = get_object_taxonomies( $slug, 'objects' );
			foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {
				$public = $taxonomy->public && $taxonomy->show_ui;
				if ( 'post_format' === $taxonomy_slug || ! $public ) {
					continue;
				}
				$options[] = [
					'value' => "$slug:$taxonomy_slug",
					// Translators: %s - Taxonomy singular name.
					'label' => sprintf( __( '%s Archive', 'mb-views' ), $taxonomy->labels->singular_name ),
				];
			}

			$locations[] = $post_group;
		}

		return $locations;
	}
}
