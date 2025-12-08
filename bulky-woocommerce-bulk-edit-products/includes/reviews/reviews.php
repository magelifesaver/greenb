<?php

namespace BULKY\Includes\Reviews;

use BULKY\Includes\Abstracts\Bulky_Abstract;
use BULKY\Includes\Helper;

defined( 'ABSPATH' ) || exit;

class Reviews extends Bulky_Abstract {

	protected static $instance = null;

	public function __construct() {
		$this->type             = 'reviews';
		$this->default_settings = [
			'edit_fields'          => [],
			'comments_per_page'    => 20,
			'order_by'             => 'ID',
			'order'                => 'DESC',
			'auto_save_revision'   => 60,
			'auto_remove_revision' => 30,
		];

		parent::__construct();

	}

	public static function instance() {
		return self::$instance === null ? self::$instance = new self : self::$instance;
	}

	public function define_columns() {
        $user_id = get_current_user_id();
        
		$columns = [
			'id'         => [ 'type' => 'number', 'width' => 70, 'title' => 'ID', 'readOnly' => true ],
            'comment_type' => [
                    'type'     => 'text',
                    'width'    => 70,
                    'title'    => esc_html__( 'Type', 'bulky-woocommerce-bulk-edit-products' ),
                    'readOnly' => true,
            ],
            'rating' => [
                    'type'  => 'number',
                    'width' => 70,
                    'title' => esc_html__( 'Rating', 'bulky-woocommerce-bulk-edit-products' ),
            ],

            'status' => [
                    'type'      => 'dropdown',
                    'width'     => 80,
                    'title'     => esc_html__( 'Status', 'bulky-woocommerce-bulk-edit-products' ),
                    'source'    => $this->parse_to_dropdown_source( [
                            'approve' => 'Approved',
                            'pending' => 'Pending',
                            'spam'    => 'Spam'
                    ] ),
                    'subSource' => [
                            [ 'id' => 'publish', 'name' => esc_html__( 'Enable', 'bulky-woocommerce-bulk-edit-products' ) ],
                            [ 'id' => 'private', 'name' => esc_html__( 'Disable', 'bulky-woocommerce-bulk-edit-products' ) ],
                    ],
                    'filter'    => 'sourceForVariation'
            ],
			'content' => [
				'type'   => 'custom',
				'width'  => 120,
				'title'  => esc_html__( 'Content', 'bulky-woocommerce-bulk-edit-products' ),
				'align'  => 'left',
				'editor' => 'textEditor',
			],

			'author_name' => [
				'type'  => 'text',
				'width' => 100,
				'title' => esc_html__( 'Author Name', 'bulky-woocommerce-bulk-edit-products' ),
				'align' => 'left',
			],

			'author_email' => [
				'type'  => 'text',
				'width' => 100,
				'title' => esc_html__( 'Author Email', 'bulky-woocommerce-bulk-edit-products' ),
				'align' => 'left',
			],

			'author_url' => [
				'type'  => 'text',
				'width' => 100,
				'title' => esc_html__( 'Author URL', 'bulky-woocommerce-bulk-edit-products' ),
				'align' => 'left',
			],

            'product_title' => [
                    'type'     => 'text',
                    'width'    => 100,
                    'title'    => esc_html__( 'Product', 'bulky-woocommerce-bulk-edit-products' ),
                    'readOnly' => true,
            ],
            'product_id' => [
                    'type'     => 'number',
                    'width'    => 100,
                    'title'    => esc_html__( 'Product ID', 'bulky-woocommerce-bulk-edit-products' ),
                    'readOnly' => true,
            ],


			'review_date' => [
				'type'    => 'calendar',
				'width'   => 120,
				'title'   => esc_html__( 'Review Date', 'bulky-woocommerce-bulk-edit-products' ),
				'options' => [ 'format' => 'YYYY-MM-DD HH24:MI', 'time' => 1 ],
			],


			'verified' => [
				'type'  => 'checkbox',
				'width' => 90,
				'title' => esc_html__( 'Verified', 'bulky-woocommerce-bulk-edit-products' ),
			],

		];

		$user_review_meta_fields = get_user_meta( $user_id, 'vi_wbe_review_meta_fields', true );
		$meta_fields = ! empty( $user_review_meta_fields ) ? $user_review_meta_fields : get_option( 'vi_wbe_review_meta_fields' );

		$meta_field_columns = [];
		if ( ! empty( $meta_fields ) && is_array( $meta_fields ) ) {
			foreach ( $meta_fields as $meta_key => $meta_field ) {

				if ( empty( $meta_field['active'] ) ) {
					continue;
				}

				$type   = 'text';
				$editor = '';

				switch ( $meta_field['input_type'] ) {
					case 'textinput':
						$type = 'text';
						break;
					case 'numberinput':
						$type = 'number';
						break;
					case 'checkbox':
						$type = 'checkbox';
						break;
					case 'array':
					case 'json':
						$type   = 'custom';
						$editor = 'array';
						break;
					case 'calendar':
						$type = 'calendar';
						break;
					case 'texteditor':
						$type   = 'custom';
						$editor = 'textEditor';
						break;
					case 'image':
						$type   = 'custom';
						$editor = 'image';
						break;
				}

				$meta_field_columns[ $meta_key ] = [
					'title'  => ! empty( $meta_field['column_name'] ) ? $meta_field['column_name'] : $meta_key,
					'width'  => 100,
					'type'   => $type,
					'editor' => $editor,
				];
			}
		}

		$columns = array_merge( $columns, $meta_field_columns );

		return $this->set_column_width( $user_id, 'vi_wbe_review_column_width', $columns );
	}

	public function load_reviews() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$handle_comment = Handle_Review::instance();
		$filter         = Filters::instance();
		$settings       = $this->get_settings();
		$page           = ! empty( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : 1;
        $comments_per_page = intval($settings['comments_per_page']);
		$args = [
			'number'    => $comments_per_page,
			'paged'     => $page,
            'offset'           => ( $page - 1 ) * $comments_per_page,
			'post_type' => 'product',
			'post_status' => 'any',
			'order'     => $settings['order'],
			'orderby'   => $settings['order_by'],
            'parent'           => 0,
            'post__in'         => array(),
		];

		$args = $filter->set_args( $args );
        $comment_count_args = array_merge( $args, array(
                'number' => 0,
                'count'  => true,
        ) );
        unset( $comment_count_args['paged'] );
        unset( $comment_count_args['offset'] );
        $count_reviews = get_comments($comment_count_args);
        $max_num_pages = ceil($count_reviews/$comments_per_page);
        $comments = get_comments($args);
//		$result = new \WP_Comment_Query( $args );
//
//		$max_num_pages = $result->max_num_pages;
//		$comments      = $result->comments;

		$comments_data = $pids = [];
		if ( !empty($comments) ) {
			foreach ( $comments as $comment ) {
				$pid             = $comment->comment_ID;
				$pids[]          = $pid;
				$comments_data[] = $handle_comment->get_comment_data_for_edit( $comment );
			}
		}

		$respone_data = [
			'products'      => $comments_data,
			'max_num_pages' => $max_num_pages,
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

	public function fixed_columns() {
		return [ 'id', 'comment_type','rating' ];
	}

	public function filter_fields() {
		$defined_columns     = array_keys( $this->define_columns() );
		$edit_fields         = $this->get_setting( 'edit_fields' );
		$exclude_edit_fields = $this->get_setting( 'exclude_edit_fields' );

		$r = $defined_columns;

		if ( ! empty( $edit_fields ) && is_array( $edit_fields ) ) {
			$edit_fields = array_merge( $this->fixed_columns(), $edit_fields );

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
			}
		}

		return array_values( $r );

	}

	public function save_reviews() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$reviews     = isset( $_POST['products'] ) ? json_decode( wp_unslash( $_POST['products'] ), true ) : '';
		$trash_ids   = ! empty( $_POST['trash'] ) ? wc_clean( wp_unslash( $_POST['trash'] ) ) : '';
		$untrash_ids = ! empty( $_POST['untrash'] ) ? wc_clean( wp_unslash( $_POST['untrash'] ) ) : '';
		$response    = [];

		if ( $untrash_ids ) {
			array_map( 'wp_untrash_comment', $untrash_ids );
		}

		if ( $trash_ids ) {
			array_map( 'wp_trash_comment', $trash_ids );
		}

		$fields        = $this->filter_fields();
		$handle_review = Handle_Review::instance();

		if ( ! empty( $reviews ) && is_array( $reviews ) ) {
			foreach ( $reviews as $review_data ) {
				if ( empty( $review_data[0] ) ) {
					continue;
				}
				$pid = $review_data[0] ?? '';

				$comment = \WP_Comment::get_instance( $pid );

				if ( ! is_object( $comment ) ) {
					continue;
				}

				$p_data = [];
				foreach ( $review_data as $key => $value ) {
					$type = $fields[ $key ] ?? '';
					if ( ! $type || 0 === $key ) {
						continue;
					}
					$handle_review->parse_review_data_to_save( $comment, $type, $value, $p_data );
				}

				if ( ! empty( $p_data ) ) {
					$p_data['comment_ID'] = $comment->comment_ID;
					wp_update_comment( $p_data );
				}
			}
		}

		wp_send_json_success( $response );

	}

	public function filter_tab() {
		$this->filter_input_element( [
			'type'  => 'text',
			'id'    => 'comment_ID',
			'label' => esc_html__( 'ID (Use comma or minus for range)', 'bulky-woocommerce-bulk-edit-products' ),
		] );

		$this->filter_input_element( [
			'type'  => 'text',
			'id'    => 'comment_search',
			'label' => esc_html__( 'Content, Author, Author Email, Author URL', 'bulky-woocommerce-bulk-edit-products' ),
		] );


		$this->filter_input_element( [
			'type'    => 'select',
			'id'      => 'comment_approved',
			'options' => [
				''        => esc_html__( 'Status', 'bulky-woocommerce-bulk-edit-products' ),
				'approve' => esc_html__( 'Approved', 'bulky-woocommerce-bulk-edit-products' ),
				'hold'    => esc_html__( 'Pending', 'bulky-woocommerce-bulk-edit-products' ),
				'spam'    => esc_html__( 'Spam', 'bulky-woocommerce-bulk-edit-products' ),
			],
		] );

		$this->filter_input_element( [
			'type'    => 'select',
			'id'      => 'comment_type',
			'options' => [
				''        => esc_html__( 'Type', 'bulky-woocommerce-bulk-edit-products' ),
				'review'  => esc_html__( 'Review', 'bulky-woocommerce-bulk-edit-products' ),
				'comment' => esc_html__( 'Reply', 'bulky-woocommerce-bulk-edit-products' ),
			],
		] );

		?>
        <div class="two fields"> <?php
			$this->filter_input_element( [
				'type'  => 'date',
				'id'    => 'comment_after_date',
				'label' => esc_html__( 'After Date', 'bulky-woocommerce-bulk-edit-products' ),
			] );

			$this->filter_input_element( [
				'type'  => 'date',
				'id'    => 'comment_before_date',
				'label' => esc_html__( 'Before Date', 'bulky-woocommerce-bulk-edit-products' ),
			] );
			?>
        </div>
		<?php
		$this->filter_input_element( [
			'type'    => 'select',
			'id'      => 'rating',
			'options' => [
				''  => esc_html__( 'Rating', 'bulky-woocommerce-bulk-edit-products' ),
				'1' => esc_html( '1' ),
				'2' => esc_html( '2' ),
				'3' => esc_html( '3' ),
				'4' => esc_html( '4' ),
				'5' => esc_html( '5' ),
			]
		] );

		$this->filter_input_element( [
			'type'    => 'select',
			'id'      => 'verified',
			'options' => [
				'1' => esc_html__( 'Yes', 'bulky-woocommerce-bulk-edit-products' ),
				'0' => esc_html__( 'No', 'bulky-woocommerce-bulk-edit-products' ),
				''  => esc_html__( 'Verified', 'bulky-woocommerce-bulk-edit-products' ),
			],
		] );
		$this->filter_input_element( [
			'type'    => 'select2',
			'type_select2'      => 'product',
			'id'      => 'product_id',
            'label' => esc_html__( 'Product', 'bulky-woocommerce-bulk-edit-products' ),
		] );

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

		$this->setting_input_element( [
			'type'  => 'number',
			'id'    => 'comments_per_page',
			'min'   => 1,
			'max'   => 50,
			'label' => esc_html__( 'Comments per page', 'bulky-woocommerce-bulk-edit-products' )
		] );

		$this->setting_input_element( [
			'type'    => 'select',
			'id'      => 'order_by',
			'label'   => esc_html__( 'Order by', 'bulky-woocommerce-bulk-edit-products' ),
			'options' => [
				'ID' => 'ID',
			]
		] );

		$this->setting_input_element( [
			'type'    => 'select',
			'id'      => 'order',
			'label'   => esc_html__( 'Order', 'bulky-woocommerce-bulk-edit-products' ),
			'options' => [
				'DESC' => 'DESC',
				'ASC'  => 'ASC',
			]
		] );

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
	}

	public function get_column_titles() {
		$columns = wp_list_pluck( $this->define_columns(), 'title' );
		unset( $columns['id'] );
		unset( $columns['comment_type'] );
		unset( $columns['rating'] );

		return $columns;
	}

	public function get_history_page() {
		Review_History::instance()->get_history_page();
	}

}