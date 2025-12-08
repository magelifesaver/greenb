<?php

namespace BULKY\Includes;

use Automattic\Jetpack\Constants;
use BULKY\Includes\Coupons\Coupon_Data_Store;
use BULKY\Includes\Coupons\Coupons;
use BULKY\Includes\Coupons\Handle_Coupon;
use BULKY\Includes\Orders\Order_Data_Store;
use BULKY\Includes\Orders\Orders;
use BULKY\Includes\Products\Handle_Product;
use BULKY\Includes\Products\Product_Data_Store;
use BULKY\Includes\Products\Products;
use BULKY\Includes\Reviews\Reviews;

defined( 'ABSPATH' ) || exit;

class Ajax {

	protected static $instance = null;
	protected $variation_ids = [];
	protected $type;
	protected $history;
	protected $action;

	public function __construct() {
		if ( wp_doing_ajax() ) {
			//phpcs:ignore WordPress.Security.NonceVerification.Missing
			$count_post = count( $_POST, COUNT_RECURSIVE );
			if ( $count_post > ini_get( "max_input_vars" ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'max_input_vars is not enough:', 'bulky-woocommerce-bulk-edit-products' ) . $count_post ] );
			}
			add_action( 'wp_ajax_vi_wbe_ajax', [ $this, 'ajax_action' ] );
		}

		add_action( 'product_variation_linked', [ $this, 'get_linked_variations' ] );
	}

	public static function instance() {
		return self::$instance == null ? self::$instance = new self : self::$instance;
	}

	public function define_actions() {
		return [
			'set_full_screen_option',
			'load_products',
			'save_settings',
			'add_filter_data',
			'save_products',
			'search_taxonomy',
			'search_tags',
			'search_products',
			'add_variation',
			'link_all_variations',
			'get_meta_fields',
			'save_meta_fields',
			'save_taxonomy_fields',
			'add_new_product',
			'add_new_coupon',
			'add_new_reply',
			'add_new_attribute',
			'duplicate_product',
			'add_order_note',
			'delete_order_note',
			'resize_column'
		];
	}

	public function history_actions() {
		return [
			'auto_save_revision',
			'recover_history',
			'view_history_point',
			'revert_history_single_product',
			'revert_history_all_products',
			'revert_history_product_attribute',
			'load_history_page',
		];
	}

	public function define_order_actions() {
		return array_keys( Helper::order_actions() );
	}

	public function ajax_action() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		if ( empty( $_POST['sub_action'] ) || ! current_user_can( apply_filters( 'villatheme_bulky_ajax_action_capability', BULKY_CONST['capability'], $_POST['sub_action']) ) ) {
//		if ( empty( $_POST['sub_action'] ) || ! current_user_can( BULKY_CONST['capability'] ) ) {
			return;
		}

		$actions = array_merge( $this->define_actions(), $this->history_actions(), $this->define_order_actions() );

		$ajax_action = sanitize_text_field( $_POST['sub_action'] );

		if ( in_array( $ajax_action, $this->define_order_actions() ) ) {
			$this->order_actions( $ajax_action );
			wp_die();
		}

		if ( ! ( in_array( $ajax_action, $actions ) && method_exists( $this, $ajax_action ) ) ) {
			wp_send_json_error( esc_html__( 'Method is not exist', 'bulky-woocommerce-bulk-edit-products' ) );
		}

		$this->type = sanitize_text_field( $_POST['type'] ?? '' );

		if ( in_array( $ajax_action, $this->history_actions() ) ) {
			$this->history = Helper::get_history_instance( $this->type );
		}

		$this->$ajax_action();


		wp_die();
	}

	public function resize_column() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );
		$column_id = isset( $_POST['column_id'] ) ? sanitize_text_field( wp_unslash( $_POST['column_id'] ) ) : '';
		$column_width = isset( $_POST['column_width'] ) ? sanitize_text_field( wp_unslash( $_POST['column_width'] ) ) : '';

		switch ( $this->type ) {
			case 'orders':
				Orders::instance()->resize_column( $column_id, $column_width, 'vi_wbe_order_column_width' );
				break;
			case 'coupons':
				Coupons::instance()->resize_column( $column_id, $column_width, 'vi_wbe_coupon_column_width' );
				break;
			case 'reviews':
				Reviews::instance()->resize_column( $column_id, $column_width, 'vi_wbe_review_column_width' );
				break;
			default:
				Products::instance()->resize_column( $column_id, $column_width, 'vi_wbe_product_column_width' );
				break;
		}
		wp_die();
	}

	public function set_full_screen_option() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$status = sanitize_text_field( wp_unslash( $_POST['status'] ) );
		$status = $status === 'true' ? true : false;
		update_option( 'vi_wbe_full_screen_option', $status );
	}

	public function save_settings() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		if ( isset( $_POST['fields'] ) ) {
			wp_parse_str( $_POST['fields'], $new_options );

			$option_name = $this->type == 'products' ? "vi_wbe_settings" : "vi_wbe_{$this->type}_settings";
			$user_id = get_current_user_id();
			if ($user_id){
				$old_options = get_user_meta($user_id, $option_name, true);
			}
			if (empty($old_options) || !is_array($old_options)) {
				$old_options = get_option($option_name );
			}
			$new_options = wc_clean( $new_options );

			$old_edit_fields = $old_options['edit_fields'] ?? [];
			$new_edit_fields = $new_options['edit_fields'] ?? [];

			$old_exclude_edit_fields = $old_options['exclude_edit_fields'] ?? [];
			$new_exclude_edit_fields = $new_options['exclude_edit_fields'] ?? [];

			$old_sort_fields = $old_options['sort_fields'] ?? [];
			$new_sort_fields = $new_options['sort_fields'] ?? [];

			$edit_fields_compare         = ! empty( array_merge( array_diff( $old_edit_fields, $new_edit_fields ), array_diff( $new_edit_fields, $old_edit_fields ) ) );
			$exclude_edit_fields_compare = ! empty( array_merge( array_diff( $old_exclude_edit_fields, $new_exclude_edit_fields ), array_diff( $new_exclude_edit_fields, $old_exclude_edit_fields ) ) );
			$sort_fields_compare         = wp_json_encode( $old_sort_fields ) != wp_json_encode( $new_sort_fields );
			if ($user_id){
				update_user_meta( $user_id, $option_name, $new_options );
				if (($new_options['auto_remove_revision']??'') !=($old_options['auto_remove_revision']??'')){
					$old_options1 = get_option($option_name,[] );
					$old_options1['auto_remove_revision'] = $new_options['auto_remove_revision']??'';
					update_option( $option_name, $old_options1 );
				}
			}else {
				update_option( $option_name, $new_options );
			}

			wp_send_json_success( [
				'settings'     => $new_options,
				'fieldsChange' => $edit_fields_compare || $exclude_edit_fields_compare || $sort_fields_compare,
				'fieldsRefresh' => ((($old_options['wrap_mode'] ??'') != ($new_options['wrap_mode'] ?? ''))||
				                    (($old_options['order_by'] ??'') != ($new_options['order_by']??''))||
				                    (($old_options['order'] ??'') != ($new_options['order']??''))||
				                    ($this->type == 'products' && ((($old_options['products_per_page']??'') != ($new_options['products_per_page'] ??''))||
				                   (($old_options['load_variations']??'') != ($new_options['load_variations']??'')))))
			] );
		}
	}

	public function add_new_product() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		if ( empty( $_POST['product_name'] ) ) {
			return;
		}
		$product_name = sanitize_text_field( wp_unslash( $_POST['product_name'] ) );
		$product      = new \WC_Product();
		$product->set_name( $product_name );

		if ( 'yes' === get_option( 'woocommerce_enable_reviews', 'yes' ) ) {
			$product->set_reviews_allowed( true );
		}

		$pid            = $product->save();
		$product        = wc_get_product( $pid );
		$handle_product = Handle_Product::instance();
		$products_data  = $handle_product->get_product_data_for_edit( $product );
		wp_send_json_success( $products_data );
	}

	public function add_variation() {
		Products::instance()->add_variation();
	}

	public function add_new_coupon() {
		$coupon        = new \WC_Coupon();
		$id            = $coupon->save();
		$handle_coupon = Handle_Coupon::instance();
		$post          = get_post( $id );
		$coupon_data   = $handle_coupon->get_coupon_data_for_edit( $coupon, $post );
		wp_send_json_success( $coupon_data );
	}

	private function add_new_reply() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		if ( empty( $_POST['content'] ) ) {
			wp_die( esc_html__( 'Error: Please type your review text.', 'bulky-woocommerce-bulk-edit-products' ) );
		}
		// WordPress core already sanitizes `content` during the `pre_comment_content` hook, which is why it's not needed here, {@see wp_filter_comment()} and {@see kses_init_filters()}.
		$comment_content = wp_unslash( $_POST['content'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$user = wp_get_current_user();

		if ( $user->exists() ) {
			$user_ID              = $user->ID;
			$comment_author       = wp_slash( $user->display_name );
			$comment_author_email = wp_slash( $user->user_email );
			$comment_author_url   = wp_slash( $user->user_url );

			kses_remove_filters(); // Start with a clean slate.
			kses_init_filters();   // Set up the filters.
			remove_filter( 'pre_comment_content', 'wp_filter_post_kses' );
			add_filter( 'pre_comment_content', 'wp_filter_kses' );

		} else {
			wp_die( esc_html__( 'Sorry, you must be logged in to reply to a review.', 'bulky-woocommerce-bulk-edit-products' ) );
		}

		if ( ! empty( $_POST['new_comments'] ) ) {
			$new_comments = $_POST['new_comments'];

			foreach ( $new_comments as $new_comment ) {
				$comment_id = wc_clean( $new_comment['comment_id'] );
				$comment_post_ID = wc_clean( $new_comment['product_id'] );
				$comment_type = 'comment';
				$product = get_post( $comment_post_ID );


				if ( 'product' !== get_post_type( $product ) ) {
					continue;
				}

				if ( ! current_user_can( 'edit_post', $comment_post_ID ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
					continue;
				}

				if ( empty( $product->post_status ) || in_array( $product->post_status, array( 'draft', 'pending', 'trash' ), true ) ) {
					continue;
				}

				if ( empty( $comment_id ) || ! current_user_can( 'edit_comment', $comment_id ) ) {
					continue;
				}

				$comment_parent = 0;

				$comment_parent = absint( $comment_id );

				$comment_auto_approved = false;
				$commentdata           = compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID' );

				// Automatically approve parent comment.
				$parent = get_comment( $comment_parent );

				if ( $parent && $parent->comment_approved === '0' && $parent->comment_post_ID === $comment_post_ID ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
					if ( ! current_user_can( 'edit_comment', $parent->comment_ID ) ) {
						wp_die( -1 );
					}

					if ( wp_set_comment_status( $parent, 'approve' ) ) {
						$comment_auto_approved = true;
					}
				}

				$comment_id = wp_new_comment( $commentdata );

				if ( is_wp_error( $comment_id ) ) {
					wp_die( esc_html( $comment_id->get_error_message() ) );
				}

				$comment = get_comment( $comment_id );

				if ( ! $comment ) {
					wp_die( 1 );
				}
			}
			wp_send_json_success( esc_html__( 'Add reply successfully', 'bulky-woocommerce-bulk-edit-products' ) );

		}
	}

	public function load_products() {

		switch ( $this->type ) {
			case 'orders':
				Orders::instance()->load_orders();
				break;
			case 'coupons':
				Coupons::instance()->load_coupons();
				break;
			case 'reviews':
				Reviews::instance()->load_reviews();
				break;
			default:
				Products::instance()->load_products();
				break;
		}
	}

	public function save_products() {
		switch ( $this->type ) {
			case 'orders':
				Orders::instance()->save_orders();
				break;
			case 'coupons':
				Coupons::instance()->save_coupons();
				break;
			case 'reviews':
				Reviews::instance()->save_reviews();
				break;
			default:
				Products::instance()->save_products();
				break;
		}
	}

	public function add_filter_data() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		if ( empty( $_POST['filter_data'] ) ) {
			wp_send_json_error();
		}

		wp_parse_str( $_POST['filter_data'], $filter_data );

		$filter_data = wc_clean( $filter_data );
		$user_id     = get_current_user_id();

		$option_name = $this->type == 'products' ? "vi_wbe_filter_data_{$user_id}" : "vi_wbe_filter_{$this->type}_data_{$user_id}";

		set_transient( $option_name, $filter_data, DAY_IN_SECONDS );

		$this->load_products();
	}

	public function search_taxonomy() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$taxonomy_type = isset( $_POST['taxonomy_type'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy_type'] ) ) : '';
		$r = [];
		$terms   = get_terms( [
			'taxonomy'   => $taxonomy_type,
			'search'     => $search,
			'hide_empty' => false,
		] );
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $tmp ) {
				$r[] = [ 'id' => $tmp->slug, 'text' => $tmp->name ];
			}
		}
		wp_send_json( $r );
	}
	public function search_tags() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$tags   = get_tags( [
			'taxonomy'   => 'product_tag',
			'search'     => $search,
			'hide_empty' => false,
		] );

		$r = [];
		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				$r[] = [ 'id' => $tag->term_id, 'text' => $tag->name ];
			}
		}

		wp_send_json( $r );
	}

	public function search_products() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$products   = array();
		$term       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$data_store = \WC_Data_Store::load( 'product' );
		$ids        = $data_store->search_products( $term, '', true, false, 30 );

		foreach ( $ids as $id ) {
			$product_object = wc_get_product( $id );

			if ( ! wc_products_array_filter_readable( $product_object ) ) {
				continue;
			}

			$formatted_name = $product_object->get_formatted_name();
			$managing_stock = $product_object->managing_stock();

			if ( $managing_stock && ! empty( $_GET['display_stock'] ) ) {
				$stock_amount = $product_object->get_stock_quantity();
				/* Translators: %d stock amount */
				$formatted_name .= ' &ndash; ' . sprintf( __( 'Stock: %d', 'bulky-woocommerce-bulk-edit-products' ), wc_format_stock_quantity_for_display( $stock_amount, $product_object ) );
			}

			$products[ $product_object->get_id() ] = rawurldecode( wp_strip_all_tags( $formatted_name ) );
		}

		wp_send_json( $products );
	}

	private function duplicate_product() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );
		$pid     = sanitize_text_field( $_POST['product_id'] ?? '' );
		$product = wc_get_product( $pid );

		if ( ! $product ) {
			wp_send_json_error();
		}

		$duplicate = Products::instance()->product_duplicate( $product );
		wp_send_json_success( $duplicate );
	}

	public function link_all_variations() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		wc_maybe_define_constant( 'WC_MAX_LINKED_VARIATIONS', 50 );
		wc_set_time_limit( 0 );

		$post_id = isset( $_POST['pid'] ) ? intval( $_POST['pid'] ) : 0;

		if ( ! $post_id ) {
			wp_die();
		}

		$product    = wc_get_product( $post_id );
		$data_store = $product->get_data_store();

		if ( ! is_callable( array( $data_store, 'create_all_product_variations' ) ) ) {
			wp_die();
		}

		$data_store->create_all_product_variations( $product, Constants::get_constant( 'WC_MAX_LINKED_VARIATIONS' ) );
		$data_store->sort_all_product_variations( $product->get_id() );

		$products_data = [];
		if ( ! empty( $this->variation_ids ) ) {
			$handle_product = Handle_Product::instance();
			foreach ( $this->variation_ids as $vid ) {
				$product         = wc_get_product( $vid );
				$products_data[] = $handle_product->get_product_data_for_edit( $product );
			}
		}
		wp_send_json_success( $products_data );
	}

	public function get_linked_variations( $variation_id ) {
		$this->variation_ids[] = $variation_id;
	}

	public function get_meta_fields() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$current_meta_fields = isset( $_POST['current_meta_fields'] ) ? wc_clean( wp_unslash( $_POST['current_meta_fields'] ) ) : [];

		global $wpdb;

		$post_type = $exclude_meta_key = $query = '';

		switch ( $this->type ) {
			case 'products':
				$post_type     = "product', 'product_variation";
				$product_data  = Product_Data_Store::instance();
				$meta_key_list = $product_data->get_internal_meta_keys();
				$key_index     = array_search( 'total_sales', $meta_key_list );
				unset( $meta_key_list[ $key_index ] );
				$exclude_meta_key = implode( "','", $meta_key_list );
				break;

			case 'orders':
				$post_type        = 'shop_order';
				$order_data       = Order_Data_Store::instance();
				$exclude_meta_key = implode( "','", $order_data->get_internal_meta_keys() );

				if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$query = "select distinct ordermeta.meta_key, ordermeta.meta_value from {$wpdb->prefix}wc_orders_meta as ordermeta left join {$wpdb->prefix}wc_orders as orders on(ordermeta.order_id = orders.id)
					where orders.type in('{$post_type}') and ordermeta.meta_key not in('{$exclude_meta_key}')
					and ordermeta.meta_key not like '%wpfoof-exclude-product%' and ordermeta.meta_key not like '%wpfoof-identifier_exists%' and ordermeta.meta_value is not null
					group by ordermeta.meta_key";
				}
				break;

			case 'coupons':
				$post_type        = 'shop_coupon';
				$coupon_data      = Coupon_Data_Store::instance();
				$exclude_meta_key = implode( "','", $coupon_data->get_internal_meta_keys() );
				break;

			case 'reviews':
				$post_type        = 'reviews';
				$query = " select distinct commentmeta.meta_key, commentmeta.meta_value FROM {$wpdb->commentmeta} as commentmeta
							 left join {$wpdb->comments} as comments
							 on (commentmeta.comment_id = comments.comment_ID)
							 where comments.comment_type = 'review' AND commentmeta.meta_key not in('rating','verified') and commentmeta.meta_value is not null
							 group by commentmeta.meta_key";
				break;
		}

		if ( ! $query ) {
			$query = "select distinct postmeta.meta_key, postmeta.meta_value from {$wpdb->postmeta} as postmeta left join {$wpdb->posts} as posts on(postmeta.post_id = posts.ID) 
					where posts.post_type in('{$post_type}') and postmeta.meta_key not in('{$exclude_meta_key}') 
					and postmeta.meta_key not like '%wpfoof-exclude-product%' and postmeta.meta_key not like '%wpfoof-identifier_exists%' and postmeta.meta_value is not null 
					group by postmeta.meta_key";
		}
		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$metadata = $wpdb->get_results( $query, ARRAY_A );
		$meta_arr = [];

		if ( ! empty( $metadata ) && is_array( $metadata ) ) {
			foreach ( $metadata as $meta ) {
				$meta_value = $meta['meta_value'];
				$meta_key   = $meta['meta_key'];

				// @codingStandardsIgnoreLine
				$meta_arr[ $meta_key ]['meta_value'] = $meta_value;

				if ( ! $meta_value ) {
					$meta_arr[ $meta_key ]['input_type'] = 'textinput';
					continue;
				}

				$meta_value_unserialize = maybe_unserialize( $meta_value );
				if ( is_array( $meta_value_unserialize ) ) {
					$meta_arr[ $meta_key ]['input_type'] = 'array';
					continue;
				}

				$meta_value_json = json_decode( $meta_value, true );
				if ( is_array( $meta_value_json ) ) {
					$meta_arr[ $meta_key ]['input_type'] = 'json';
					continue;
				}

				$meta_arr[ $meta_key ]['input_type'] = 'string';

			}
		}

		$meta_arr = wp_parse_args( $current_meta_fields, $meta_arr );
		$meta_arr = apply_filters( 'bulky_filter_meta_arr', $meta_arr );
		if ( ! empty( $meta_arr ) ) {
			wp_send_json_success( $meta_arr );
		} else {
			wp_send_json_error( [ 'message' => esc_html__( 'No meta field was found', 'bulky-woocommerce-bulk-edit-products' ) ] );
		}
	}

	public function save_meta_fields() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$meta_fields = isset( $_POST['meta_fields'] ) ? wc_clean( wp_unslash( $_POST['meta_fields'] ) ) : [];
		$type        = substr( $this->type, 0, - 1 );

		if ( ! empty( $meta_fields ) ) {
			foreach ( $meta_fields as $key => $data ) {
				if ( empty( $data['select_options'] ) ) {
					continue;
				}

				$select_options = sanitize_textarea_field( wp_unslash( $_POST['meta_fields'][ $key ]['select_options'] ) );

				$meta_fields[ $key ]['select_options'] = $select_options;

				$options = explode( "\n", $select_options );

				foreach ( $options as $option ) {
					$opt = explode( ':', $option );
					if ( ! empty( $opt[0] ) ) {
						$k = trim( $opt[0] );

						$meta_fields[ $key ]['select_source'][ $k ] = isset( $opt[1] ) && $opt[1] !== '' ? trim( $opt[1] ) : $k;
					}
				}
			}
		}

		uasort( $meta_fields, function ( $a, $b ) {
			$a_active = $a['active'] ?? 0;
			$b_active = $b['active'] ?? 0;

			return (int) $a_active < (int) $b_active;
		} );

		update_option( "vi_wbe_{$type}_meta_fields", $meta_fields );
		update_user_meta( get_current_user_id(), "vi_wbe_{$type}_meta_fields", $meta_fields );
		wp_send_json_success();
	}

	public function auto_save_revision() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$data = ! empty( $_POST['data'] ) ? Helper::sanitize( $_POST['data'] ) : '';
		$page = ! empty( $_POST['page'] ) ? sanitize_text_field( $_POST['page'] ) : 1;

		$this->history->set( $data );
		$count_page = $this->history->count_history_pages();

		ob_start();
		$this->history->get_history_page( $page );
		$update_pages = ob_get_clean();

		wp_send_json_success( [ 'pages' => $count_page, 'updatePage' => $update_pages ] );
	}

	public function view_history_point() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		if ( ! empty( $_POST['id'] ) ) {
			$history_id = sanitize_text_field( wp_unslash( $_POST['id'] ) );
			$r          = $this->history->compare_history_point_and_current( $history_id );
			wp_send_json_success( $r );
		}
	}

	public function revert_history_single_product() {
		$this->history->revert_single_product();
		wp_send_json_success();
	}

	public function revert_history_all_products() {
		$this->history->revert_history_all_products();
		wp_send_json_success();
	}

	public function revert_history_product_attribute() {
		$this->history->revert_history_product_attribute();
		wp_send_json_success();
	}

	public function load_history_page() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );
		$page = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : '';
		if ( $page ) {
			$this->history->get_history_page( $page );
		}
	}

	public function add_new_attribute() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		if ( current_user_can( apply_filters( 'villatheme_bulky_add_new_attribute_capability', BULKY_CONST['capability']) ) && isset( $_POST['taxonomy'], $_POST['term'] ) ) {
//		if ( current_user_can( BULKY_CONST['capability'] ) && isset( $_POST['taxonomy'], $_POST['term'] ) ) {
			$taxonomy = sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) );
			$term     = wc_clean( wp_unslash( $_POST['term'] ) );

			if ( taxonomy_exists( $taxonomy ) ) {

				$result = wp_insert_term( $term, $taxonomy );

				if ( is_wp_error( $result ) ) {
					wp_send_json_error( [ 'message' => $result->get_error_message(), ] );
				} else {
					$term = get_term_by( 'id', $result['term_id'], $taxonomy );
					wp_send_json_success( [
							'term_id' => $term->term_id,
							'name'    => $term->name,
							'slug'    => $term->slug,
						]
					);
				}
			}
		}
	}

	public function order_actions( $action ) {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		if ( empty( $_POST['order_ids'] ) ) {
			wp_send_json_error();
		}

		$order_ids = wc_clean( wp_unslash( $_POST['order_ids'] ) );
		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( 'send_order_details' === $action ) {
				do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_invoice' );

				// Send the customer invoice email.
				WC()->payment_gateways();
				WC()->shipping();
				WC()->mailer()->customer_invoice( $order );

				// Note the event.
				$order->add_order_note( esc_html__( 'Order details manually sent to customer.', 'bulky-woocommerce-bulk-edit-products' ), false, true );

				do_action( 'woocommerce_after_resend_order_email', $order, 'customer_invoice' );

				// Change the post saved message.
				add_filter( 'redirect_post_location', array( '\WC_Meta_Box_Order_Actions', 'set_email_sent_message' ) );

			} elseif ( 'send_order_details_admin' === $action ) {

				do_action( 'woocommerce_before_resend_order_emails', $order, 'new_order' );

				WC()->payment_gateways();
				WC()->shipping();
				add_filter( 'woocommerce_new_order_email_allows_resend', '__return_true' );
				WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order->get_id(), $order, true );
				remove_filter( 'woocommerce_new_order_email_allows_resend', '__return_true' );

				do_action( 'woocommerce_after_resend_order_email', $order, 'new_order' );

				// Change the post saved message.
				add_filter( 'redirect_post_location', array( '\WC_Meta_Box_Order_Actions', 'set_email_sent_message' ) );

			} elseif ( 'regenerate_download_permissions' === $action ) {

				$data_store = \WC_Data_Store::load( 'customer-download' );
				$data_store->delete_by_order_id( $order_id );
				wc_downloadable_product_permissions( $order_id, true );

			} else {
				if ( ! did_action( 'woocommerce_order_action_' . sanitize_title( $action ) ) ) {
					do_action( 'woocommerce_order_action_' . sanitize_title( $action ), $order );
				}
			}
		}

		wp_send_json_success();
	}

	public function add_order_note() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$note             = isset( $_POST['note'] ) ? wp_kses_post( trim( wp_unslash( $_POST['note'] ) ) ) : '';
		$order_ids        = isset( $_POST['ids'] ) ? wc_clean( wp_unslash( $_POST['ids'] ) ) : [];
		$is_customer_note = isset( $_POST['is_customer_note'] ) ? absint( $_POST['is_customer_note'] ) : 0;

		if ( ! $note || empty( $order_ids ) ) {
			wp_send_json_error();
		}

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			$order->add_order_note( $note, $is_customer_note, true );
		}

		wp_send_json_success();
	}

	public function delete_order_note() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		if ( empty( $_POST['id'] ) ) {
			wp_send_json_error();
		}

		$note_id = absint( $_POST['id'] );
		wc_delete_order_note( $note_id );
		wp_send_json_success();
	}

	public function save_taxonomy_fields() {
		check_ajax_referer( 'vi_wbe_nonce', 'vi_wbe_nonce' );

		$taxonomy_fields = ! empty( $_POST['taxonomy_fields'] ) ? wc_clean( wp_unslash( $_POST['taxonomy_fields'] ) ) : [];
		$taxonomies      = get_taxonomies();

		foreach ( $taxonomy_fields as $key => $tax ) {
			if ( ! in_array( $tax, $taxonomies ) ) {
				unset( $taxonomy_fields[ $key ] );
			}
		}

		$type = substr( $this->type, 0, - 1 );
		update_option( "vi_wbe_{$type}_taxonomy_fields", $taxonomy_fields );
		update_user_meta( get_current_user_id(), "vi_wbe_{$type}_taxonomy_fields", $taxonomy_fields );
		wp_send_json_success();
	}
}


