<?php

namespace BULKY\Includes\Products;

use BULKY\Includes\Abstracts\Bulky_Abstract;
use BULKY\Includes\Helper;

defined( 'ABSPATH' ) || exit;

class Products extends Bulky_Abstract {

	protected static $instance = null;

	public function __construct() {
		$this->type             = 'products';
		$this->default_settings = [
			'edit_fields'          => [],
			'products_per_page'    => 20,
			'load_variations'      => 'yes',
			'order_by'             => 'ID',
			'order'                => 'DESC',
			'auto_save_revision'   => 60,
			'auto_remove_revision' => 30,
		];

		parent::__construct();
	}

	public static function instance() {
		return self::$instance == null ? self::$instance = new self : self::$instance;
	}

	public function get_users( $args = [] ) {
		$roles = [];

		foreach ( wp_roles()->roles as $role_name => $role_obj ) {
			if ( ! empty( $role_obj['capabilities']['edit_posts'] ) ) {
				$roles[] = $role_name;
			}
		}

		$args = wp_parse_args( $args, [ 'role__in' => $roles ] );

		return get_users( $args );
	}

	public function add_sku_slug_behavior( $behaviors, $id ) {
		if ( $id == 'sku' || $id == 'post_name' ) {
			$behaviors = [ 'in' => 'In' ] + $behaviors;
		}

		return $behaviors;
	}

	public function filter_tab() {
		add_filter( 'bulky_filter_behaviors_list', [ $this, 'add_sku_slug_behavior' ], 10, 2 );

		$users = $this->get_users( [ 'fields' => [ 'ID', 'display_name','user_email' ] ] );

		$users_options = [ '' => esc_html__( 'Author', 'bulky-woocommerce-bulk-edit-products' ) ];

		if ( ! empty( $users ) && is_array( $users ) ) {
			foreach ( $users as $user ) {
				$users_options[ $user->ID ] = $user->display_name .'( '. $user->user_email.')';
			}
		}

		$this->filter_input_element( [
			'type'  => 'text',
			'id'    => 'id',
			'label' => esc_html__( 'ID (Use comma or minus for range)', 'bulky-woocommerce-bulk-edit-products' ),
		] );

		$this->filter_input_element( [
			'type'     => 'text',
			'id'       => 'post_title',
			'label'    => esc_html__( 'Title', 'bulky-woocommerce-bulk-edit-products' ),
			'behavior' => true
		] );

		$this->filter_input_element( [
			'type'     => 'text',
			'id'       => 'post_content',
			'label'    => esc_html__( 'Content', 'bulky-woocommerce-bulk-edit-products' ),
			'behavior' => true
		] );

		$this->filter_input_element( [
			'type'     => 'text',
			'id'       => 'post_excerpt',
			'label'    => esc_html__( 'Excerpt', 'bulky-woocommerce-bulk-edit-products' ),
			'behavior' => true
		] );

		$this->filter_input_element( [
			'type'     => 'text',
			'id'       => 'post_name',
			'label'    => esc_html__( 'Slug', 'bulky-woocommerce-bulk-edit-products' ),
			'behavior' => true
		] );

		$this->filter_input_element( [
			'type'     => 'text',
			'id'       => 'sku',
			'label'    => esc_html__( 'SKU', 'bulky-woocommerce-bulk-edit-products' ),
			'behavior' => true
		] );
		?>

		<div class="two fields">
			<?php
			$this->filter_input_element( [
				'type'  => 'date',
				'id'    => 'post_date_from',
				'label' => esc_html__( 'Post date from', 'bulky-woocommerce-bulk-edit-products' )
			] );
			$this->filter_input_element( [
				'type'  => 'date',
				'id'    => 'post_date_to',
				'label' => esc_html__( 'Post date to', 'bulky-woocommerce-bulk-edit-products' )
			] );
			?>
		</div>

		<div class="two fields">
			<?php
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'regular_price_from',
				'label' => esc_html__( 'Regular price from', 'bulky-woocommerce-bulk-edit-products' )
			] );
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'regular_price_to',
				'label' => esc_html__( 'Regular price to', 'bulky-woocommerce-bulk-edit-products' )
			] );
			?>
		</div>

		<div class="two fields">
			<?php
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'sale_price_from',
				'label' => esc_html__( 'Sale price from', 'bulky-woocommerce-bulk-edit-products' )
			] );
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'sale_price_to',
				'label' => esc_html__( 'Sale price to', 'bulky-woocommerce-bulk-edit-products' )
			] );
			?>
		</div>

		<div class="two fields">
			<?php
			$this->filter_input_element( [
				'type'  => 'date',
				'id'    => 'sale_date_from',
				'label' => esc_html__( 'Sale date from', 'bulky-woocommerce-bulk-edit-products' )
			] );
			$this->filter_input_element( [
				'type'  => 'date',
				'id'    => 'sale_date_to',
				'label' => esc_html__( 'Sale date to', 'bulky-woocommerce-bulk-edit-products' )
			] );
			?>
		</div>

		<div class="two fields">
			<?php
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'stock_quantity_from',
				'label' => esc_html__( 'Stock quantity from', 'bulky-woocommerce-bulk-edit-products' )
			] );
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'stock_quantity_to',
				'label' => esc_html__( 'Stock quantity to', 'bulky-woocommerce-bulk-edit-products' )
			] );
			?>
		</div>

		<div class="two fields">
			<?php
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'width_from',
				'label' => esc_html__( 'Width from', 'bulky-woocommerce-bulk-edit-products' )
			] );
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'width_to',
				'label' => esc_html__( 'Width to', 'bulky-woocommerce-bulk-edit-products' )
			] );
			?>
		</div>

		<div class="two fields">
			<?php
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'height_from',
				'label' => esc_html__( 'Height from', 'bulky-woocommerce-bulk-edit-products' )
			] );
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'height_to',
				'label' => esc_html__( 'Height to', 'bulky-woocommerce-bulk-edit-products' )
			] );
			?>
		</div>

		<div class="two fields">
			<?php
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'length_from',
				'label' => esc_html__( 'Length from', 'bulky-woocommerce-bulk-edit-products' )
			] );
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'length_to',
				'label' => esc_html__( 'Length to', 'bulky-woocommerce-bulk-edit-products' )
			] );
			?>
		</div>

		<div class="two fields">
			<?php
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'weight_from',
				'label' => esc_html__( 'Weight from', 'bulky-woocommerce-bulk-edit-products' )
			] );
			$this->filter_input_element( [
				'type'  => 'number',
				'id'    => 'weight_to',
				'label' => esc_html__( 'Weight to', 'bulky-woocommerce-bulk-edit-products' )
			] );
			?>
		</div>

		<div class="two fields">
			<?php
			$this->filter_input_element( [
				'type'    => 'select',
				'id'      => 'type',
				'options' => [ '' => esc_html__( 'Product type', 'bulky-woocommerce-bulk-edit-products' ) ] + wc_get_product_types()
			] );
			$this->filter_input_element( [
				'type'    => 'select',
				'id'      => 'status',
				'options' => array_merge( [ '' => esc_html__( 'Product status', 'bulky-woocommerce-bulk-edit-products' ) ], Helper::post_statuses() )
			] );
			?>
		</div>

		<div class="two fields">
			<?php
			$this->filter_input_element( [
				'type'    => 'select',
				'id'      => 'stock_status',
				'options' => [ '' => esc_html__( 'Stock status', 'bulky-woocommerce-bulk-edit-products' ) ] + wc_get_product_stock_status_options()
			] );
			$this->filter_input_element( [
				'type'    => 'select',
				'id'      => 'featured',
				'options' => [
					''    => esc_html__( 'Featured', 'bulky-woocommerce-bulk-edit-products' ),
					'yes' => esc_html__( 'Yes', 'bulky-woocommerce-bulk-edit-products' ),
					'no'  => esc_html__( 'No', 'bulky-woocommerce-bulk-edit-products' ),
				]
			] );
			?>
		</div>

		<div class="two fields">
			<?php
			$this->filter_input_element( [
				'type'    => 'select',
				'id'      => 'downloadable',
				'options' => [
					''    => esc_html__( 'Downloadable', 'bulky-woocommerce-bulk-edit-products' ),
					'yes' => esc_html__( 'Yes', 'bulky-woocommerce-bulk-edit-products' ),
					'no'  => esc_html__( 'No', 'bulky-woocommerce-bulk-edit-products' ),
				]
			] );
			$this->filter_input_element( [
				'type'    => 'select',
				'id'      => 'sold_individually',
				'options' => [
					''    => esc_html__( 'Sold individually', 'bulky-woocommerce-bulk-edit-products' ),
					'yes' => esc_html__( 'Yes', 'bulky-woocommerce-bulk-edit-products' ),
					'no'  => esc_html__( 'No', 'bulky-woocommerce-bulk-edit-products' ),
				]
			] );
			?>
		</div>

		<div class="two fields">
			<?php
			$this->filter_input_element( [
				'type'    => 'select',
				'id'      => 'backorders',
				'options' => [ '' => esc_html__( 'Backorders', 'bulky-woocommerce-bulk-edit-products' ) ] + wc_get_product_backorder_options()
			] );

			$this->filter_input_element( [
				'type'    => 'select',
				'id'      => 'author',
				'options' => $users_options
			] );
			?>
		</div>

		<?php
		$this->filter_input_element( [
			'type'    => 'select',
			'id'      => 'visibility',
			'options' => [ '' => esc_html__( 'Catalog visibility', 'bulky-woocommerce-bulk-edit-products' ) ] + wc_get_product_visibility_options()
		] );

        $this->filter_input_element( [
            'type' => 'select',
            'id' => 'thumbnail_id',
            'options' => [ '' => esc_html__( 'Feature Image', 'bulky-woocommerce-bulk-edit-products' ), 'exists' => esc_html__( 'Yes', 'bulky-woocommerce-bulk-edit-products' ), 'not exists' => esc_html__( 'No', 'bulky-woocommerce-bulk-edit-products') ],
        ] );

		$this->filter_input_element( [
			'type'         => 'multi-select',
			'id'           => 'product_cat',
			'options'      => [ '' => esc_html__( 'Categories', 'bulky-woocommerce-bulk-edit-products' ) ] + Helper::get_categories(),
			'name_prefix'  => 'taxonomies',
			'operator'     => true,
			'select_class' => 'search',
		] );

		$this->filter_input_element( [
			'type'         => 'multi-select',
			'id'           => 'product_tag',
			'options'      => [ '' => esc_html__( 'Tags', 'bulky-woocommerce-bulk-edit-products' ) ] + $this->get_product_tags(),
			'name_prefix'  => 'taxonomies',
			'operator'     => true,
			'select_class' => 'search',
		] );

		$attribute_taxonomies = wc_get_attribute_taxonomies();
		foreach ( $attribute_taxonomies as $tax ) {
			$taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );
			$options  = [];
            $is_select2 = false;
			if ( taxonomy_exists( $taxonomy ) ) {
				$terms = get_terms( $taxonomy );
                if (count($terms) > 1000){
                    $is_select2 = true;
                }else {
	                foreach ( $terms as $term ) {
		                $options[ $term->slug ] = $term->name;
	                }
                }
			}

			$this->filter_input_element( [
				'type'         => 'multi-select',
				'id'           => $taxonomy,
				'options'      => !$is_select2 ? [ '' => $tax->attribute_label ] + $options : [],
				'name_prefix'  => 'taxonomies',
				'operator'     => true,
				'select_class' => $is_select2 ? 'vi-wbe-filter-select2' :'search',
				'select_placeholder' => $tax->attribute_label,
			] );
		}

		$extra_taxonomies = Helper::get_extra_product_taxonomies();
		if ( ! empty( $extra_taxonomies ) ) {
			foreach ( $extra_taxonomies as $tax => $name ) {
				$this->filter_input_element( [
					'type'        => 'multi-select',
					'id'          => $tax,
					'options'     => [ '' => $name ] + Helper::get_taxonomy_source( $tax, false ),
					'name_prefix' => 'taxonomies',
					'operator'    => true,
				] );
			}
		}
	}

	public function settings_tab() {
		$columns = $this->get_column_titles();

		$this->setting_input_element( [
			'type'  => 'checkbox',
			'id'    => 'wrap_mode',
			'label'   => esc_html__( 'Wrap mode', 'bulky-woocommerce-bulk-edit-products' )
		] );
		$this->setting_input_element( [
			'type'         => 'multi-select',
			'id'           => 'edit_fields',
			'select_class' => 'vi-wbe-select-columns-to-edit vi-wbe-select2 search',
			'label'        => esc_html__( 'Fields to edit', 'bulky-woocommerce-bulk-edit-products' ),
			'options'      => [ '' => esc_html__( 'All fields', 'bulky-woocommerce-bulk-edit-products' ) ] + $columns,
			'clear_button' => true
		] );

		$this->setting_input_element( [
			'type'         => 'multi-select',
			'id'           => 'exclude_edit_fields',
			'select_class' => 'vi-wbe-exclude-fields-to-edit vi-wbe-select2 search',
			'label'        => esc_html__( 'Exclude fields to edit', 'bulky-woocommerce-bulk-edit-products' ),
			'options'      => [ '' => esc_html__( 'No field', 'bulky-woocommerce-bulk-edit-products' ) ] + $columns,
			'clear_button' => true
		] );

		$this->setting_sort_field( $columns );

		$this->setting_input_element( [
			'type'  => 'number',
			'id'    => 'products_per_page',
			'min'   => 1,
			'label' => esc_html__( 'Products per page', 'bulky-woocommerce-bulk-edit-products' )
		] );

		$this->setting_input_element( [
			'type'    => 'select',
			'id'      => 'load_variations',
			'label'   => esc_html__( 'Load variations', 'bulky-woocommerce-bulk-edit-products' ),
			'options' => [
				'yes' => esc_html__( 'Yes', 'bulky-woocommerce-bulk-edit-products' ),
				'no'  => esc_html__( 'No', 'bulky-woocommerce-bulk-edit-products' ),
			]
		] );

		$this->setting_input_element( [
			'type'    => 'select',
			'id'      => 'order_by',
			'label'   => esc_html__( 'Order by', 'bulky-woocommerce-bulk-edit-products' ),
			'options' => [
				'ID'    => 'ID',
				'title' => esc_html__( 'Title', 'bulky-woocommerce-bulk-edit-products' ),
				'price' => esc_html__( 'Price', 'bulky-woocommerce-bulk-edit-products' ),
//				'regular_price' => esc_html__( 'Regular price', 'bulky-woocommerce-bulk-edit-products' ),
//				'sale_price'    => esc_html__( 'Sale price', 'bulky-woocommerce-bulk-edit-products' ),
				'sku'   => esc_html__( 'SKU', 'bulky-woocommerce-bulk-edit-products' ),
			]
		] );

		$this->setting_input_element( [
			'type'    => 'select',
			'id'      => 'order',
			'label'   => esc_html__( 'Order', 'bulky-woocommerce-bulk-edit-products' ),
			'options' => [ 'DESC' => 'DESC', 'ASC' => 'ASC', ]
		] );

//		$this->setting_input_element( [
//			'type'  => 'number',
//			'id'    => 'auto_save_revision',
//			'min'   => 0,
//			'max'   => 1000,
//			'label' => esc_html__( 'Time to save revision', 'bulky-woocommerce-bulk-edit-products' ),
//			'unit'  => esc_html__( 'second(s)', 'bulky-woocommerce-bulk-edit-products' ),
//		] );

		$this->setting_input_element( [
			'type'  => 'number',
			'id'    => 'auto_remove_revision',
			'min'   => 0,
			'max'   => 1000,
			'label' => esc_html__( 'Time to delete history', 'bulky-woocommerce-bulk-edit-products' ),
			'unit'  => esc_html__( 'day(s)', 'bulky-woocommerce-bulk-edit-products' ),
		] );

		$this->setting_input_element( [
			'type'  => 'checkbox',
			'id'    => 'save_filter',
			'label' => esc_html__( 'Save filter when reload page', 'bulky-woocommerce-bulk-edit-products' ),
		] );

		$this->setting_input_element( [
			'type'  => 'checkbox',
			'id'    => 'variation_filter',
			'label' => esc_html__( 'Filter include variation', 'bulky-woocommerce-bulk-edit-products' ),
		] );
	}

	public function setting_sort_field( $columns ) {
		$settings = $this->get_settings();
		$sorted   = $settings['sort_fields'] ?? [];
		$current_columns = $this->get_columns();
		$sorted   = array_merge( array_flip( $sorted ), $columns );
		?>
		<div class="field">
			<label>
				<?php esc_html_e( 'Sort fields', 'bulky-woocommerce-bulk-edit-products' ); ?>
			</label>
			<div class="vi-ui styled fluid accordion bulky-sort-fields-accordion">
				<div class="title">
					<i class="dropdown icon"></i>
				</div>
				<div class="content">
					<div id="bulky-sort-fields">
						<?php
						if ( ! empty( $sorted ) ) {
							foreach ( $sorted as $key => $name ) {
								printf( "<div class='bulky-sort-field bulky-sort-field-%s vi-ui button basic mini fluid%s'><input type='hidden' name='sort_fields[]' value='%s'>%s</div>", esc_attr( $key ), esc_attr( isset($current_columns[$key])? '':' vi-wbe-hidden' ),esc_attr( $key ), esc_html( $name ) );
							}
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function get_column_titles() {
		$columns = wp_list_pluck( $this->define_columns(), 'title' );
		unset( $columns['id'] );
		unset( $columns['parent_id'] );
		unset( $columns['post_title'] );
		unset( $columns['product_type'] );
		unset( $columns['download_file'] );
		unset( $columns['download_limit'] );
		unset( $columns['download_expiry'] );
		unset( $columns['default_attributes'] );

		return $columns;
	}

	public static function get_product_tags( $id_name = false ) {
		$tags = get_tags( [ 'taxonomy' => 'product_tag' ] );
		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				if ( $id_name ) {
					$r[] = [ 'id' => $tag->term_id, 'name' => $tag->name ];
				} else {
					$r[ $tag->term_id ] = $tag->name;
				}
			}
		}

		return $r ?? [];
	}

	public function define_columns() {
        $user_id = get_current_user_id();
		$shipping_class = [];
		$terms          = get_terms( [ 'taxonomy' => 'product_shipping_class', 'hide_empty' => false, ] );
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$shipping_class[] = [ 'id' => intval( $term->term_id ), 'name' => $term->name ];
			}
		}

		$catalog_visibility = $this->parse_to_dropdown_source( wc_get_product_visibility_options() );
		$product_types      = $this->parse_to_dropdown_source( wc_get_product_types() );
		$tax_class_options  = $this->parse_to_dropdown_source( wc_get_product_tax_class_options() );
		$stock_status       = $this->parse_to_dropdown_source( wc_get_product_stock_status_options() );
		$allow_backorder    = $this->parse_to_dropdown_source( wc_get_product_backorder_options() );

		$sub_tax_class_options    = $tax_class_options;
		$sub_tax_class_options[0] = [
			'id'   => 'parent',
			'name' => esc_html__( 'Same as parent', 'bulky-woocommerce-bulk-edit-products' )
		];

		$uu = $this->get_users();
		if ( ! empty( $uu ) ) {
			foreach ( $uu as $u ) {
				$users[] = [ 'id' => $u->data->ID, 'name' => $u->data->display_name ];
			}
		}

		$decimal_separator = wc_get_price_decimal_separator();
		$currency          = get_woocommerce_currency_symbol();
		$curency_format    = "###{$decimal_separator}#";

		$columns = [
			'id'           => [ 'type' => 'number', 'width' => 70, 'title' => 'ID', 'readOnly' => true ],
			'parent_id'    => [ 'type' => 'number', 'width' => 60, 'title' => 'Parent', 'readOnly' => true, ],
			'post_title'   => [
				'type'  => 'text',
				'width' => 200,
				'title' => esc_html__( 'Title', 'bulky-woocommerce-bulk-edit-products' ),
				'align' => 'left'
			],
			'product_type' => [
				'type'   => 'dropdown',
				'width'  => 100,
				'title'  => esc_html__( 'Product type', 'bulky-woocommerce-bulk-edit-products' ),
				'source' => $product_types
			],
			'image'        => [
				'type'   => 'custom',
				'width'  => 70,
				'title'  => esc_html__( 'Image', 'bulky-woocommerce-bulk-edit-products' ),
				'editor' => 'image'
			],
			'global_unique_id'          => [
				'type'  => 'text',
				'width' => 70,
				'title' => esc_html__( 'GTIN, UPC, EAN, or ISBN', 'bulky-woocommerce-bulk-edit-products' ),
				'align' => 'left'
			],
			'sku'          => [
				'type'  => 'text',
				'width' => 70,
				'title' => esc_html__( 'SKU', 'bulky-woocommerce-bulk-edit-products' ),
				'align' => 'left'
			],
			'post_name'    => [
				'type'  => 'text',
				'width' => 70,
				'title' => esc_html__( 'Slug', 'bulky-woocommerce-bulk-edit-products' ),
				'align' => 'left'
			],

			'post_date' => [
				'type'    => 'calendar',
				'width'   => 120,
				'title'   => esc_html__( 'Publish date', 'bulky-woocommerce-bulk-edit-products' ),
				'options' => [ 'format' => 'YYYY-MM-DD HH24:MI', 'time' => 1 ]
			],

			'post_content' => [
				'type'   => 'custom',
				'width'  => 100,
				'title'  => esc_html__( 'Description', 'bulky-woocommerce-bulk-edit-products' ),
				'align'  => 'left',
				'editor' => 'textEditor'
			],
			'post_excerpt' => [
				'type'   => 'custom',
				'width'  => 100,
				'title'  => esc_html__( 'Short Desc', 'bulky-woocommerce-bulk-edit-products' ),
				'align'  => 'left',
				'editor' => 'textEditor'
			],
			'gallery'      => [
				'type'   => 'custom',
				'width'  => 60,
				'title'  => esc_html__( 'Gallery', 'bulky-woocommerce-bulk-edit-products' ),
				'editor' => 'gallery'
			],

			'attributes' => [
				'type'   => 'custom',
				'width'  => 80,
				'title'  => esc_html__( 'Attributes', 'bulky-woocommerce-bulk-edit-products' ),
				'editor' => 'product_attributes'
			],

			'default_attributes' => [
				'type'   => 'custom',
				'width'  => 80,
				'title'  => esc_html__( 'Default attributes', 'bulky-woocommerce-bulk-edit-products' ),
				'editor' => 'default_attributes'
			],

			'grouped_products' => [
				'type'   => 'custom',
				'width'  => 100,
				'title'  => esc_html__( 'Grouped', 'bulky-woocommerce-bulk-edit-products' ),
				'editor' => 'link_products'
			],

			'product_url' => [
				'type'  => 'text',
				'width' => 100,
				'title' => esc_html__( 'Product URL', 'bulky-woocommerce-bulk-edit-products' ),
			],

			'button_text' => [
				'type'  => 'text',
				'width' => 100,
				'title' => esc_html__( 'Button text', 'bulky-woocommerce-bulk-edit-products' ),
			],

			'status'   => [
				'type'      => 'dropdown',
				'width'     => 80,
				'title'     => esc_html__( 'Status', 'bulky-woocommerce-bulk-edit-products' ),
				'source'    => $this->parse_to_dropdown_source( Helper::post_statuses() ),
				'subSource' => [
					[ 'id' => 'publish', 'name' => esc_html__( 'Enable', 'bulky-woocommerce-bulk-edit-products' ) ],
					[ 'id' => 'private', 'name' => esc_html__( 'Disable', 'bulky-woocommerce-bulk-edit-products' ) ],
				],
				'filter'    => 'sourceForVariation'
			],
			'password' => [
				'type'  => 'text',
				'width' => 100,
				'title' => esc_html__( 'Password', 'bulky-woocommerce-bulk-edit-products' )
			],
			'featured' => [
				'type'  => 'checkbox',
				'width' => 60,
				'title' => esc_html__( 'Featured', 'bulky-woocommerce-bulk-edit-products' )
			],

			'regular_price' => [
				'type'       => 'number',
				'width'      => 110,
				'title'      => esc_html__( 'Regular price', 'bulky-woocommerce-bulk-edit-products' ) . sprintf( ' (%s)', esc_html( $currency ) ),
				'allowEmpty' => true,
				'currency'   => $decimal_separator
			],

			'sale_price' => [
				'type'       => 'number',
				'width'      => 90,
				'title'      => esc_html__( 'Sale price', 'bulky-woocommerce-bulk-edit-products' ) . sprintf( ' (%s)', esc_html( $currency ) ),
				'allowEmpty' => true,
				'currency'   => $decimal_separator,
				'id'         => 'sale_price'
			],

			'sale_date_from' => [
				'type'    => 'calendar',
				'width'   => 100,
				'title'   => esc_html__( 'Sale date from', 'bulky-woocommerce-bulk-edit-products' ),
				'options' => [ 'format' => 'YYYY-MM-DD' ]
			],

			'sale_date_to' => [
				'type'    => 'calendar',
				'width'   => 100,
				'title'   => esc_html__( 'Sale date to', 'bulky-woocommerce-bulk-edit-products' ),
				'options' => [ 'format' => 'YYYY-MM-DD' ]
			],

			'manage_stock'      => [
				'type'  => 'checkbox',
				'width' => 70,
				'title' => esc_html__( 'Manage stock', 'bulky-woocommerce-bulk-edit-products' )
			],
			'stock'             => [
				'type'  => 'number',
				'width' => 70,
				'title' => esc_html__( 'Stock', 'bulky-woocommerce-bulk-edit-products' )
			],
			'stock_status'      => [
				'type'   => 'dropdown',
				'width'  => 100,
				'title'  => esc_html__( 'Stock status', 'bulky-woocommerce-bulk-edit-products' ),
				'source' => $stock_status
			],
			'allow_backorder'   => [
				'type'   => 'dropdown',
				'width'  => 80,
				'title'  => esc_html__( 'Allow backorder', 'bulky-woocommerce-bulk-edit-products' ),
				'source' => $allow_backorder
			],
			'sold_individually' => [
				'type'  => 'checkbox',
				'width' => 75,
				'title' => esc_html__( 'Sold individually', 'bulky-woocommerce-bulk-edit-products' ),
			],
			'virtual'           => [
				'type'  => 'checkbox',
				'width' => 55,
				'title' => esc_html__( 'Virtual', 'bulky-woocommerce-bulk-edit-products' ),
			],

			'product_cat' => [
				'type'        => 'custom',
				'editor'      => 'select2',
				'width'       => 140,
				'title'       => esc_html__( 'Categories', 'bulky-woocommerce-bulk-edit-products' ),
				'source'      => Helper::get_categories( true ),
				'multiple'    => true,
				'allowEmpty'  => true,
				'placeholder' => esc_html__( 'Select categories', 'bulky-woocommerce-bulk-edit-products' ),
			],

			'tags' => [
				'type'         => 'custom',
				'width'        => 100,
				'title'        => esc_html__( 'Tags', 'bulky-woocommerce-bulk-edit-products' ),
				'editor'       => 'tags',
				'multiple'     => true,
				'remoteSearch' => true,
				'url'          => admin_url( 'admin-ajax.php?action=vi_wbe_search_tags&nonce=' . wp_create_nonce( 'vi_web_ajax_nonce' ) ),
			],

			'weight' => [
				'type'       => 'number',
				'allowEmpty' => true,
				'width'      => 70,
				'title'      => esc_html__( 'Weight', 'bulky-woocommerce-bulk-edit-products' )
			],
			'length' => [
				'type'       => 'number',
				'allowEmpty' => true,
				'width'      => 70,
				'title'      => esc_html__( 'Length', 'bulky-woocommerce-bulk-edit-products' )
			],
			'width'  => [
				'type'       => 'number',
				'allowEmpty' => true,
				'width'      => 70,
				'title'      => esc_html__( 'Width', 'bulky-woocommerce-bulk-edit-products' )
			],
			'height' => [
				'type'       => 'number',
				'allowEmpty' => true,
				'width'      => 70,
				'title'      => esc_html__( 'Height', 'bulky-woocommerce-bulk-edit-products' )
			],

			'upsells'     => [
				'type'   => 'custom',
				'width'  => 100,
				'title'  => esc_html__( 'Upsells', 'bulky-woocommerce-bulk-edit-products' ),
				'editor' => 'link_products'
			],
			'cross_sells' => [
				'type'   => 'custom',
				'width'  => 100,
				'title'  => esc_html__( 'Cross-sells', 'bulky-woocommerce-bulk-edit-products' ),
				'editor' => 'link_products'
			],

			'downloadable'    => [
				'type'  => 'checkbox',
				'width' => 90,
				'title' => esc_html__( 'Downloadable', 'bulky-woocommerce-bulk-edit-products' ),
			],
			'download_file'   => [
				'type'     => 'text',
				'width'    => 90,
				'title'    => esc_html__( 'Download file', 'bulky-woocommerce-bulk-edit-products' ),
				'editor'   => 'download',
				'wordWrap' => false
			],
			'download_limit'  => [
				'type'       => 'number',
				'allowEmpty' => true,
				'width'      => 90,
				'title'      => esc_html__( 'Download limit', 'bulky-woocommerce-bulk-edit-products' ),
				'mask'       => "###",
			],
			'download_expiry' => [
				'type'       => 'number',
				'allowEmpty' => true,
				'width'      => 90,
				'title'      => esc_html__( 'Download expiry', 'bulky-woocommerce-bulk-edit-products' ),
				'mask'       => "###",
			]
		];

		$tax_columns = [];
		if ( wc_tax_enabled() ) {
			$tax_columns = [
				'tax_status' => [
					'type'       => 'dropdown',
					'width'      => 90,
					'title'      => esc_html__( 'Tax status', 'bulky-woocommerce-bulk-edit-products' ),
					'source'     => [
						[
							'id'   => 'taxable',
							'name' => esc_html__( 'Taxable', 'bulky-woocommerce-bulk-edit-products' )
						],
						[
							'id'   => 'shipping',
							'name' => esc_html__( 'Shipping only', 'bulky-woocommerce-bulk-edit-products' )
						],
						[ 'id' => 'none', 'name' => esc_html__( 'None', 'bulky-woocommerce-bulk-edit-products' ) ],
					],
					'allowEmpty' => false,
				],

				'tax_class' => [
					'type'      => 'dropdown',
					'width'     => 90,
					'title'     => esc_html__( 'Tax class', 'bulky-woocommerce-bulk-edit-products' ),
					'source'    => $tax_class_options,
					'subSource' => $sub_tax_class_options,
					'filter'    => 'sourceForVariation'
				],
			];
		}

		$columns_2 = [
			'purchase_note' => [
				'type'   => 'text',
				'width'  => 90,
				'title'  => esc_html__( 'Purchase note', 'bulky-woocommerce-bulk-edit-products' ),
				'editor' => 'textEditor'
			],
			'menu_order'    => [
				'type'  => 'number',
				'width' => 70,
				'title' => esc_html__( 'Menu order', 'bulky-woocommerce-bulk-edit-products' ),
			],
			'allow_reviews' => [
				'type'    => 'checkbox',
				'width'   => 70,
				'title'   => esc_html__( 'Enable reviews', 'bulky-woocommerce-bulk-edit-products' ),
				'default' => true
			],

			'catalog_visibility' => [
				'type'   => 'dropdown',
				'width'  => 100,
				'title'  => esc_html__( 'Catalog visibility', 'bulky-woocommerce-bulk-edit-products' ),
				'source' => $catalog_visibility,
			],

			'shipping_class' => [
				'type'      => 'dropdown',
				'width'     => 100,
				'title'     => esc_html__( 'Shipping class', 'bulky-woocommerce-bulk-edit-products' ),
				'source'    => array_merge( [
					[
						'id'   => '0',
						'name' => esc_html__( 'No shipping class', 'bulky-woocommerce-bulk-edit-products' )
					]
				], $shipping_class ),
				'subSource' => array_merge( [
					[
						'id'   => '0',
						'name' => esc_html__( 'Same as parent', 'bulky-woocommerce-bulk-edit-products' )
					]
				], $shipping_class ),
				'filter'    => 'sourceForVariation'
			],
		];

        $user_product_meta_fields = get_user_meta( $user_id, 'vi_wbe_product_meta_fields', true );
		$meta_fields = ! empty( $user_product_meta_fields ) ? $user_product_meta_fields : get_option( 'vi_wbe_product_meta_fields' );

		$meta_field_columns = [];
		if ( ! empty( $meta_fields ) && is_array( $meta_fields ) ) {
			foreach ( $meta_fields as $meta_key => $meta_field ) {
				if ( empty( $meta_field['active'] ) ) {
					continue;
				}

				$args = [
					'title' => ! empty( $meta_field['column_name'] ) ? $meta_field['column_name'] : $meta_key,
					'width' => 100,
					'type'  => 'text',
				];

				switch ( $meta_field['input_type'] ) {
					case 'textinput':
						$args['type'] = 'text';
						break;

					case 'numberinput':
						$args['type'] = 'number';
						break;

					case 'checkbox':
						$args['type'] = 'checkbox';
						break;
					case 'array':
					case 'json':
						$args['type']   = 'custom';
						$args['editor'] = 'array';
						break;

					case 'calendar':
						$args['type'] = 'calendar';
						break;

					case 'texteditor':
						$args['type']   = 'custom';
						$args['editor'] = 'textEditor';
						break;

					case 'image':
						$args['type']   = 'custom';
						$args['editor'] = 'image';
						break;

					case 'select':
						$args['type']       = 'dropdown';
						$args['multiple']   = false;
						$args['allowEmpty'] = true;
						$args['source']     = $this->parse_to_dropdown_source( $meta_field['select_source'] ?? [] );
						break;

					case 'multiselect':
						$args['type']       = 'custom';
						$args['editor']     = 'select2';
						$args['multiple']   = true;
						$args['allowEmpty'] = true;
						$args['source']     = $this->parse_to_select2_source( $meta_field['select_source'] ?? [] );
						break;
                    case 'gallery':
	                    $args['type'] = 'custom';
	                    $args['editor'] = 'gallery';
                        break;
				}

				$meta_field_columns[ $meta_key ] = $args;
			}
		}
        $user_product_taxonomy_fields = get_user_meta( $user_id, 'vi_wbe_product_taxonomy_fields', true );
		$taxonomy_fields       = ! empty( $user_product_taxonomy_fields ) ? $user_product_taxonomy_fields : get_option( 'vi_wbe_product_taxonomy_fields' );
		$extra_taxonomy_fields = [];
		if ( ! empty( $taxonomy_fields ) ) {
			$extra_product_taxonomies = Helper::get_extra_product_taxonomies();
			foreach ( $taxonomy_fields as $tax ) {
				$extra_taxonomy_fields[ $tax ] = [
					'title'       => $extra_product_taxonomies[ $tax ] ?? $tax,
					'type'        => 'custom',
					'editor'      => 'select2',
					'width'       => 140,
					'source'      => Helper::get_taxonomy_source( $tax, true ),
					'multiple'    => true,
					'allowEmpty'  => true,
					'placeholder' => esc_html__( 'Select term', 'bulky-woocommerce-bulk-edit-products' ),
				];
			}
		}

		$columns = array_merge( $columns, $tax_columns, $columns_2, $meta_field_columns, $extra_taxonomy_fields );

		if ( ! empty( $columns ) && is_array( $columns ) ) {
			foreach ( $columns as $col_id => $column ) {
				$columns[ $col_id ] = apply_filters( "bulky_define_product_column", $column, $col_id );
			}
		}

		return $this->set_column_width( $user_id, 'vi_wbe_product_column_width', $columns );
	}

	public function filter_fields() {
		$defined_columns     = array_keys( $this->define_columns() );
		$edit_fields         = $this->get_setting( 'edit_fields' );
		$exclude_edit_fields = $this->get_setting( 'exclude_edit_fields' );

		$r = $defined_columns;

		if ( ! empty( $edit_fields ) && is_array( $edit_fields ) ) {
			$edit_fields = array_merge( $this->fixed_columns(), $edit_fields );
			if ( in_array( 'downloadable', $edit_fields ) ) {
				$edit_fields = array_merge( $edit_fields, $this->downloadable() );
			}

			if ( in_array( 'attributes', $edit_fields ) ) {
				$edit_fields = array_merge( $edit_fields, [ 'default_attributes' ] );
			}

			foreach ( $r as $i => $key ) { //Keep piority
				if ( $key !== false && ! in_array( $key, $edit_fields ) ) {
					unset( $r[ $i ] );
				}
			}
		}

		if ( ! empty( $exclude_edit_fields ) && is_array( $exclude_edit_fields ) ) {
			foreach ( $exclude_edit_fields as $field ) {
				$key = array_search( $field, $r );

				if ( $key !== false && isset( $r[ $key ] ) ) {
					unset( $r[ $key ] );
				}

				if ( $field === 'downloadable' ) {
					foreach ( $this->downloadable() as $value ) {
						$key2 = array_search( $value, $r );
						if ( $key2 !== false && isset( $r[ $key2 ] ) ) {
							unset( $r[ $key2 ] );
						}
					}
				}

				if ( $field === 'attributes' ) {
					$key3 = array_search( 'default_attributes', $r );
					if ( $key3 !== false && isset( $r[ $key3 ] ) ) {
						unset( $r[ $key3 ] );
					}
				}
			}
		}

		return array_values( $r );
	}

	public function fixed_columns() {
		return [ 'id', 'parent_id', 'post_title', 'product_type' ]; //'action',
	}

	public function downloadable() {
		return [ 'download_file', 'download_limit', 'download_expiry' ];
	}

	public function load_products() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$handle_product = Handle_Product::instance();
		$filter         = Filters::instance();
		$settings       = $this->get_settings();
		$page           = ! empty( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : 1;
		$orderby        = $settings['order_by'];

		$args = [
			'posts_per_page' => $settings['products_per_page'],
			'paged'          => $page,
			'paginate'       => true,
			'order'          => $settings['order'],
			'orderby'        => 'ID',
			'status'         => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit' ),
		];

		switch ( $orderby ) {
			case 'price':
				$args['orderby'] = [ 'meta_value_num' => $settings['order'] ];
				add_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'orderby_price' ] );
				break;

			case 'sku':
				$args['orderby']  = 'meta_value';
				// @codingStandardsIgnoreLine
				$args['meta_key'] = '_sku';
				break;

			default:
				$args['orderby'] = $orderby;

				break;
		}

		add_filter( 'get_the_terms', function ( $terms, $post_id, $taxonomy ) { //Fix some site can not run wp_cache_delete
			$terms = wp_get_object_terms( $post_id, $taxonomy );
			if ( ! is_wp_error( $terms ) ) {
				$term_ids = wp_list_pluck( $terms, 'term_id' );
				wp_cache_add( $post_id, $term_ids, $taxonomy . '_relationships' );
			}

			return $terms;
		}, 10, 3 );

		remove_all_filters( 'woocommerce_product_object_query_args' );
		remove_all_filters( 'woocommerce_product_object_query' );

		$args   = $filter->set_args( $args );
		$result = wc_get_products( $args );

		remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'orderby_price' ] );

		$count         = $result->total;
		$max_num_pages = $result->max_num_pages;
		$products      = $result->products;
		$products_data = $pids = $img_storage = [];

		if ( $products ) {
			foreach ( $products as $product ) {
				$pid    = $product->get_id();
				$pids[] = $pid;
				$img_id = $product->get_image_id();
				$src    = wp_get_attachment_image_url( $img_id );

				if ( $src ) {
					$img_storage[ $img_id ] = $src;
				}

				$img_ids = $product->get_gallery_image_ids( 'edit' );

				if ( ! empty( $img_ids ) && is_array( $img_ids ) ) {
					foreach ( $img_ids as $img_id ) {
						$src = wp_get_attachment_image_url( $img_id );
						if ( $src ) {
							$img_storage[ $img_id ] = $src;
						}
					}
				}

				$products_data[] = $handle_product->get_product_data_for_edit( $product );

				if ( $product->get_type() == 'variable' && $settings['load_variations'] == 'yes' ) {

					if ( ! empty( $settings['variation_filter'] ) ) {
						add_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'filter_variation' ] );
					}

					$variations = wc_get_products(
						array(
							'status'  => array( 'private', 'publish' ),
							'type'    => 'variation',
							'parent'  => $pid,
							'limit'   => - 1,
							'orderby' => array(
								'menu_order' => 'ASC',
								'ID'         => 'DESC',
							),
							'return'  => 'objects',
						)
					);

					remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'filter_variation' ] );

					if ( ! empty( $variations ) ) {
						foreach ( $variations as $variation ) {
							if ( is_object( $variation ) ) {
								$img_id = $variation->get_image_id();
								$src    = wp_get_attachment_image_url( $img_id );
								if ( $src ) {
									$img_storage[ $img_id ] = $src;
								}
								$products_data[] = $handle_product->get_product_data_for_edit( $variation );
							}
						}
					}
				}
			}
		}

		$respone_data = [
			'products'      => $products_data,
			'count'         => $count,
			'max_num_pages' => $max_num_pages,
			'img_storage'   => apply_filters( 'bulky_image_storage', $img_storage ),
		];

		if ( isset( $_POST['re_create'] ) && $_POST['re_create'] === 'true' ) {
			$columns                       = $this->get_columns();
			$id_mapping                    = array_keys( $columns );
			$respone_data['idMapping']     = $id_mapping;
			$respone_data['idMappingFlip'] = array_flip( $id_mapping );
			$respone_data['columns']       = wp_json_encode( array_values( $columns ) );
		}

		wp_send_json_success( $respone_data );
	}

	public function add_variation() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		if ( ! empty( $_POST['pid'] ) ) {
			$product_id       = sanitize_text_field( intval( $_POST['pid'] ) );
			$product_object   = wc_get_product_object( 'variable', $product_id ); // Forces type to variable in case product is unsaved.
			$variation_object = wc_get_product_object( 'variation' );
			$variation_object->set_parent_id( $product_id );
			$variation_object->set_attributes( array_fill_keys( array_map( 'sanitize_title', array_keys( $product_object->get_variation_attributes() ) ), '' ) );
			$variation_id   = $variation_object->save();
			$product        = wc_get_product( $variation_id );
			$handle_product = Handle_Product::instance();
			$products_data  = $handle_product->get_product_data_for_edit( $product );
			wp_send_json_success( $products_data );
		}
	}

	public function get_final_fields() {
		$filter_fields = $this->filter_fields();
		$sorted        = $this->get_sorted_pattern();
		$fields        = [];

		if ( ! empty( $sorted ) ) {
			foreach ( $sorted as $s_key ) {
				if ( in_array( $s_key, $filter_fields ) ) {
					$fields[] = $s_key;
					$i        = array_search( $s_key, $filter_fields );
					unset( $filter_fields[ $i ] );
				}
			}
		}

		return array_merge( $fields, $filter_fields );
	}

	public function save_products() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$products    = isset( $_POST['products'] ) ? json_decode( wp_unslash( $_POST['products'] ), true ) : '';
		$trash_ids   = ! empty( $_POST['trash'] ) ? wc_clean( wp_unslash( $_POST['trash'] ) ) : '';
		$untrash_ids = ! empty( $_POST['untrash'] ) ? wc_clean( wp_unslash( $_POST['untrash'] ) ) : '';

		$response = [];

		if ( $untrash_ids ) {
			array_map( 'wp_untrash_post', $untrash_ids );
		}

		$fields         = $this->get_final_fields();
		$handle_product = Handle_Product::instance();
		$enable_hook    = get_option( 'vi_wbe_enable_hook' );

		if ( ! empty( $products ) && is_array( $products ) ) {
			foreach ( $products as $product_data ) {
				if ( empty( $product_data[0] ) ) {
					continue;
				}
				$pid = $product_data[0] ?? '';

				$product = wc_get_product( $pid );

				if ( ! is_object( $product ) ) {
					continue;
				}

				if ( ! is_plugin_active( 'woo-product-gallery-slider\woo-product-gallery-slider.php' ) ) {
					if ( $product->get_type() === 'variation' ) {
						delete_transient( 'wpgs_product_variation_' . $product->get_parent_id());
						delete_transient( 'wpgs_product_variation_' . $pid);
					}
                }

				$new_product_type = $sku = $slug = $title = '';

				foreach ( $product_data as $key => $value ) {
					$type = $fields[ $key ] ?? '';

					if ( ! $type || $key === 0 ) {
						continue;
					}

					if ( $type === 'sku' ) {
						$sku = $value;
					}

					if ( $type === 'post_name' ) {
						$slug = $value;
					}

					if ( $type === 'post_title' ) {
						$title = $value;
					}

					$handle_product->parse_product_data_to_save( $product, $type, $value );

					if ( $type === 'product_type' && $value !== $product->get_type() ) {
						$new_product_type = $value;
					}
				}

				if ( in_array( 'sku', $fields ) ) {
					try {
						$current_sku = $product->get_sku();
						if ( $current_sku !== $sku ) {
							$product->set_sku( $sku );
						}
					} catch ( \Exception $e ) {
						$response['skuErrors'][] = $pid;
					}
				}

                if ( empty( $slug ) && empty($product->get_slug('edit')) && ! empty( $title ) ) {
	                $product->set_slug( sanitize_title( $title ) );
	                $this->generate_unique_slug( $product );
                }

				if ( ! empty( $enable_hook['woocommerce_admin_process_product_object'] ) ) {
					do_action( 'woocommerce_admin_process_product_object', $product );
				}

				$pid = $product->save();

				if ( $new_product_type ) {
					//Change product type
					if ( in_array( $new_product_type, array_keys( wc_get_product_types() ) ) ) {
						wp_set_object_terms( $pid, $new_product_type, 'product_type' );
					}
				}

				$this->call_hooks_after_product_update( $product );
			}
		}

		if ( $trash_ids ) {
			foreach ( $trash_ids as $pid ) {
				$product = wc_get_product( $pid );
				if ( $product->is_type( 'variation' ) ) {
					wp_delete_post( $pid );
				} else {
					wp_trash_post( $pid );
				}
			}
		}

		wp_send_json_success( $response );
	}

	private function call_hooks_after_product_update( &$product ) {
		$product_id  = $product->get_id();
		$pp          = get_post( $product_id );
		$enable_hook = get_option( 'vi_wbe_enable_hook' );

		if ( ! empty( $enable_hook['save_post'] ) ) {
			do_action( 'save_post', $product_id, $pp, true );
		}

		if ( ! empty( $enable_hook['save_post_product'] ) ) {
			do_action( "save_post_product", $product_id, $pp, true );
		}

		if ( ! empty( $enable_hook['edit_post'] ) ) {
			do_action( 'edit_post', $product_id, $pp );
		}

	    if ( ! empty( $enable_hook['woocommerce_process_product_meta_product_type'] ) ) {
            $product_type = $product->get_type();
            do_action( 'woocommerce_process_product_meta_' . $product_type, $product_id );
		}

		if ( $product->get_type() === 'variation' ) {
			if ( ! empty( $enable_hook['woocommerce_update_product_variation'] ) ) {
				do_action( 'woocommerce_update_product_variation', $product_id, $product );
			}
		} else {
			if ( ! empty( $enable_hook['woocommerce_update_product'] ) ) {
				do_action( 'woocommerce_update_product', $product_id, $product );
			}
		}
	}

	public function product_duplicate( $product ) {
		$products_data  = [];
		$handle_product = Handle_Product::instance();
		$settings       = $this->get_settings();
		$load_variation = $settings['load_variations'] == 'yes';

		$meta_to_exclude = array_filter(
			apply_filters( 'woocommerce_duplicate_product_exclude_meta', array(), array_map( function ( $datum ) {
				return $datum->key;
			}, $product->get_meta_data() ) )
		);

		$duplicate = clone $product;
		$duplicate->set_id( 0 );
		/* translators: %s contains the name of the original product. */
		$duplicate->set_name( sprintf( esc_html__( '%s (Copy)', 'bulky-woocommerce-bulk-edit-products' ), $duplicate->get_name() ) );
		$duplicate->set_total_sales( 0 );
		if ( '' !== $product->get_sku( 'edit' ) ) {
			$duplicate->set_sku( wc_product_generate_unique_sku( 0, $product->get_sku( 'edit' ) ) );
		}
		$duplicate->set_status( 'draft' );
		$duplicate->set_date_created( null );
		$duplicate->set_slug( '' );
		$duplicate->set_rating_counts( 0 );
		$duplicate->set_average_rating( 0 );
		$duplicate->set_review_count( 0 );

		foreach ( $meta_to_exclude as $meta_key ) {
			$duplicate->delete_meta_data( $meta_key );
		}

		do_action( 'woocommerce_product_duplicate_before_save', $duplicate, $product );

		// Save parent product.
		$duplicate->save();

		// Duplicate children of a variable product.
		if ( ! apply_filters( 'woocommerce_duplicate_product_exclude_children', false, $product ) && $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $child_id ) {
				$child           = wc_get_product( $child_id );
				$child_duplicate = clone $child;
				$child_duplicate->set_parent_id( $duplicate->get_id() );
				$child_duplicate->set_id( 0 );
				$child_duplicate->set_date_created( null );

				// If we wait and let the insertion generate the slug, we will see extreme performance degradation
				// in the case where a product is used as a template. Every time the template is duplicated, each
				// variation will query every consecutive slug until it finds an empty one. To avoid this, we can
				// optimize the generation ourselves, avoiding the issue altogether.
				$this->generate_unique_slug( $child_duplicate );

				if ( '' !== $child->get_sku( 'edit' ) ) {
					$child_duplicate->set_sku( wc_product_generate_unique_sku( 0, $child->get_sku( 'edit' ) ) );
				}

				foreach ( $meta_to_exclude as $meta_key ) {
					$child_duplicate->delete_meta_data( $meta_key );
				}

				do_action( 'woocommerce_product_duplicate_before_save', $child_duplicate, $child );

				$child_duplicate->save();

				if ( $load_variation ) {
					$products_data[] = $handle_product->get_product_data_for_edit( $child_duplicate );
				}
			}

			// Get new object to reflect new children.
			$duplicate = wc_get_product( $duplicate->get_id() );
		}

		array_unshift( $products_data, $handle_product->get_product_data_for_edit( $duplicate ) );

		return $products_data;
	}

	private function generate_unique_slug( $product ) {
	    global $wpdb;

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
	    $results1 = $wpdb->get_results(
		    $wpdb->prepare( "SELECT post_name FROM {$wpdb->posts} WHERE post_name = %s AND post_type IN ( 'product', 'product_variation' )", $product->get_slug() )
	    );

	    // The slug is already unique!
	    if ( empty( $results1 ) ) {
		    return;
	    }

	    $root_slug = preg_replace( '/-[0-9]+$/', '', $product->get_slug() );

	    //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
	    $results2 = $wpdb->get_results(
		    $wpdb->prepare( "SELECT post_name FROM {$wpdb->posts} WHERE post_name LIKE %s AND post_type IN ( 'product', 'product_variation' )", $root_slug . '%' )
	    );

	    if ( empty( $results2 ) ) {
		    return;
	    }

	    $max_suffix = 1;
	    foreach ( $results2 as $result ) {
		    // Pull a numerical suffix off the slug after the last hyphen.
		    $suffix = intval( substr( $result->post_name, strrpos( $result->post_name, '-' ) + 1 ) );
		    if ( $suffix > $max_suffix ) {
			    $max_suffix = $suffix;
		    }
	    }
	    $product->set_slug( $root_slug . '-' . ( $max_suffix + 1 ) );
    }

	public function get_history_page() {
		Product_History::instance()->get_history_page();
	}

	public function orderby_price( $query ) {
		// @codingStandardsIgnoreLine
		$query['meta_query'] = [
			'relation' => 'OR',
			[
				'key'     => '_price',
				'compare' => 'EXISTS',
			],
			[
				'key'     => '_price',
				'compare' => 'NOT EXISTS',
			]
		];

		return $query;
	}

	public function filter_variation( $query ) {
		$user_id     = get_current_user_id();
		$load_filter = get_transient( "vi_wbe_filter_data_{$user_id}" );

		if ( ! empty( $load_filter['taxonomies'] ) ) {
			$f_taxonomies = $load_filter['taxonomies'];

			foreach ( $f_taxonomies as $taxonomy => $terms ) {
				$terms = array_filter( $terms );
				if ( 'pa_' !== substr( $taxonomy, 0, 3 ) || empty( $terms ) ) {
					continue;
				}

				$mtqr = [];
				foreach ( $terms as $term ) {
					$mtqr[] = [
						'key'     => 'attribute_' . $taxonomy,
						'value'   => $term,
						'compare' => '=',
					];
				}

				if ( ! empty( $mtqr ) ) {
					$mtqr['relation']      = 'OR';
					$query['meta_query'][] = $mtqr;
				}
			}
		}

		if ( isset( $load_filter['stock_status'] ) && $load_filter['stock_status'] !== '' ) {
			$query['meta_query'][] = [
				'key'     => '_stock_status',
				'value'   => $load_filter['stock_status'],
				'compare' => '=',
			];
		}

		$this->parse_variation_filter_meta( $query, $load_filter, 'weight_from', '_weight', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'weight_to', '_weight', '<=' );

		$this->parse_variation_filter_meta( $query, $load_filter, 'length_from', '_length', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'length_to', '_length', '<=' );

		$this->parse_variation_filter_meta( $query, $load_filter, 'width_from', '_width', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'width_to', '_width', '<=' );

		$this->parse_variation_filter_meta( $query, $load_filter, 'height_from', '_height', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'height_to', '_height', '<=' );

		$this->parse_variation_filter_meta( $query, $load_filter, 'stock_quantity_from', '_stock', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'stock_quantity_to', '_stock', '<=' );

		$this->parse_variation_filter_meta( $query, $load_filter, 'regular_price_from', '_regular_price', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'regular_price_to', '_regular_price', '<=' );

		$this->parse_variation_filter_meta( $query, $load_filter, 'sale_price_from', '_sale_price', '>=' );
		$this->parse_variation_filter_meta( $query, $load_filter, 'sale_price_to', '_sale_price', '<=' );

//		$this->parse_variation_filter_meta_time( $query, $load_filter, 'sale_date_from', '_sale_price_dates_from', '>=' );
//		$this->parse_variation_filter_meta_time( $query, $load_filter, 'sale_date_to', '_sale_price_dates_to', '<=' );

		if ( ! empty( $query['meta_query'] ) ) {
			$query['meta_query']['relation'] = 'AND';
		}

		return $query;
	}

	private function parse_variation_filter_meta_time( &$query, $load_filter, $value, $key, $compare = '=' ) {

		if ( isset( $load_filter[ $value ] ) && $load_filter[ $value ] !== '' ) {
			$time = 0;
			if ( $key === '_sale_price_dates_from' ) {
				$time = strtotime( $load_filter[ $value ] );
			}

			if ( $key === '_sale_price_dates_to' ) {
				$time = strtotime( "tomorrow {$load_filter[ $value ]}" ) - 1;
			}

			$query['meta_query'][] = [
				'key'     => $key,
				'value'   => $time,
				'compare' => $compare,
			];
		}
	}

	public function parse_variation_filter_meta( &$query, $load_filter, $value, $key, $compare = '=' ) {
		if ( isset( $load_filter[ $value ] ) && $load_filter[ $value ] !== '' ) {
			$query['meta_query'][] = [
				'key'     => $key,
				'value'   => floatval( $load_filter[ $value ] ),
				'compare' => $compare,
				'type'    => 'NUMERIC',
			];
		}
	}

	public function get_sorted_pattern() {
		$sort_fields = $this->get_setting( 'sort_fields' );
		if ( ! empty( $sort_fields ) ) {
			$sort_fields        = array_merge( $this->fixed_columns(), $sort_fields );
			$downloadable_index = array_search( 'downloadable', $sort_fields );
			array_splice( $sort_fields, $downloadable_index + 1, 0, $this->downloadable() );

			$default_attributes_index = array_search( 'attributes', $sort_fields );
			array_splice( $sort_fields, $default_attributes_index + 1, 0, [ 'default_attributes' ] );
		}

		return $sort_fields;
	}
}
