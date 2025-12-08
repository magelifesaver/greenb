<?php
namespace MBViews;

class Category {
	private $slug      = 'mb-views-category';
	private $post_type = 'mb-views';

	public function __construct() {
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_filter( 'views_edit-mb-views', [ $this, 'add_categories_link' ] );
		add_action( 'restrict_manage_posts', [ $this, 'output_filter' ] );
		add_action( 'admin_print_styles-edit.php', [ $this, 'enqueue' ] );
	}

	public function register_taxonomy() {
		$labels = [
			'name'                       => _x( 'Categories', 'Views Category General Name', 'mb-views' ),
			'singular_name'              => _x( 'Category', 'Views Category Singular Name', 'mb-views' ),
			'menu_name'                  => __( 'Category', 'mb-views' ),
			'all_items'                  => __( 'All Categories', 'mb-views' ),
			'parent_item'                => __( 'Parent Category', 'mb-views' ),
			'parent_item_colon'          => __( 'Parent Category:', 'mb-views' ),
			'new_item_name'              => __( 'New Category Name', 'mb-views' ),
			'add_new_item'               => __( 'Add New Category', 'mb-views' ),
			'edit_item'                  => __( 'Edit Category', 'mb-views' ),
			'update_item'                => __( 'Update Category', 'mb-views' ),
			'view_item'                  => __( 'View Category', 'mb-views' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'mb-views' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'mb-views' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'mb-views' ),
			'popular_items'              => __( 'Popular Category', 'mb-views' ),
			'search_items'               => __( 'Search Category', 'mb-views' ),
			'not_found'                  => __( 'Not Found', 'mb-views' ),
			'no_terms'                   => __( 'No categories', 'mb-views' ),
			'items_list'                 => __( 'Categories list', 'mb-views' ),
			'items_list_navigation'      => __( 'Categories list navigation', 'mb-views' ),
		];
		$args   = [
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_in_nav_menus' => false,
			'show_admin_column' => true,
			'show_tagcloud'     => false,
			'rewrite'           => false,
		];
		register_taxonomy( $this->slug, $this->post_type, $args );
	}

	public function add_categories_link( array $views ): array {
		$views[ $this->slug ] = sprintf( '<a href="%s">%s</a>', admin_url( "edit-tags.php?taxonomy={$this->slug}&post_type={$this->post_type}" ), esc_html__( 'View Categories', 'mb-views' ) );
		return $views;
	}

	public function output_filter( string $post_type ) {
		if ( $post_type !== $this->post_type ) {
			return;
		}
		$taxonomy = get_taxonomy( $this->slug );
		wp_dropdown_categories( [
			// Translators: %s - Taxonomy label.
			'show_option_all' => sprintf( __( 'All %s', 'admin-taxonomy-filter' ), $taxonomy->label ),
			'orderby'         => 'name',
			'order'           => 'ASC',
			'hide_empty'      => false,
			'hide_if_empty'   => false,
			'selected'        => filter_input( INPUT_GET, $taxonomy->query_var ),
			'hierarchical'    => true,
			'name'            => $taxonomy->query_var,
			'taxonomy'        => $taxonomy->name,
			'value_field'     => 'slug',
		] );
	}

	public function enqueue() {
		if ( "edit-{$this->post_type}" === get_current_screen()->id ) {
			wp_enqueue_style( 'mbv-list', MBV_URL . 'assets/list.css', [], MBV_VER );
		}
	}
}
