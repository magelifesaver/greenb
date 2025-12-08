<?php

namespace BULKY\Includes\Orders;

defined( 'ABSPATH' ) || exit;

class Filters {

	protected static $instance = null;
	protected $filter, $cache = [];
	protected $type = 'orders';

	public function __construct() {
		$user_id = get_current_user_id();
		$this->filter  = get_transient( "vi_wbe_filter_{$this->type}_data_{$user_id}" );
		add_filter( 'posts_where', [ $this, 'add_filter_to_posts_where' ] );
		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ $this, 'order_data_store_cpt_get_orders_query' ], 10, 2 );
		$hpos_custom_table = (get_option( 'woocommerce_feature_custom_order_tables_enabled' ) === 'yes' || get_option( 'woocommerce_custom_orders_table_enabled' ) === 'yes' ) && get_option( 'woocommerce_custom_orders_table_data_sync_enabled','no' ) === 'no';
		if ($hpos_custom_table){
			add_filter( 'woocommerce_order_query_args', [ $this, 'set_order_query_args' ],10,1 );
			add_filter( 'woocommerce_orders_table_query_clauses', [ $this, 'add_items_query' ] );
		}
	}

	public static function instance() {
		return null == self::$instance ? self::$instance = new self : self::$instance;
	}

	public function set_args( $args ) {
		$filter = $this->filter;
		if ( empty( $filter ) ) {
			return $args;
		}

		if ( ! empty( $filter['id'] ) && strpos( $filter['id'], '-' ) === false ) {
			$args['post__in'] = explode( ',', str_replace( ' ', '', $filter['id'] ) );
		}

		if ( ! empty( $filter['item_name'] ) ) {
			$term = $filter['item_name'];
			global $wpdb;

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.DirectQuery
			$order_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT order_id
							FROM {$wpdb->prefix}woocommerce_order_items as order_items
							WHERE order_item_name LIKE %s",
					'%' . $wpdb->esc_like( wc_clean( $term ) ) . '%'
				)
			);

			if ( ! empty( $order_ids ) ) {
				$args['post__in'] = ! empty( $args['post__in'] ) ? array_merge( $order_ids, (array) $args['post__in'] ) : $order_ids;
			}
		}
		$string_type = [ 'status' ];
		foreach ( $string_type as $key ) {
			if ( ! empty( $filter[ $key ] ) ) {
				$args[ $key ] = $filter[ $key ];
			}
		}

		return $args;
	}
	public function hpos_mapping_field($field,$result_type = 'text'){
		if (isset($this->cache['hpos_mapping_field'][$result_type][$field])){
			return $this->cache['hpos_mapping_field'][$result_type][$field];
		}
		if (!isset($this->cache['hpos_mapping_field'])){
			$this->cache['hpos_mapping_field']=[];
		}
		if (!isset($this->cache['hpos_mapping_field'][$result_type])){
			$this->cache['hpos_mapping_field'][$result_type]=[];
		}
		if ($result_type === 'text'){
			switch ($field){
				case 'post_excerpt':
					$result = 'customer_note';
					break;
				default:
					$result = trim($field,'_');
			}
		}else{
			switch ($field){
				case 'post_excerpt':
					$result = 'customer_note';
					break;
				default:
					if (strpos($field,'_billing_') === 0 || strpos($field,'_shipping_') === 0 ) {
						$result               = [];
						$result['field_name'] = str_replace( [ '_billing_', '_shipping_' ], '', $field );
						$result['type']       = strpos( $field, '_billing' )=== 0 ? 'billing' : 'shipping';
					}else{
						$result = trim($field,'_');
					}
			}
		}
		$this->cache['hpos_mapping_field'][$result_type][$field] = $result ?? $field;
		return $this->cache['hpos_mapping_field'][$result_type][$field];
	}
	public function add_items_query( $args ) {
		if ( !empty($this->filter['filter_hpos'])) {
			global $wpdb;
			if ( ! empty( $this->filter['filter_hpos']['operator'] ) ) {
				foreach ( $this->filter['filter_hpos']['operator'] as $meta_key => $operator ) {
					$value = $this->filter[ $meta_key ] ?? '';
					if ( is_array( $value ) ) {
						$value = array_filter( $value );
					}
					if ( empty( $value ) ) {
						continue;
					}
					if ( !is_array( $value ) ) {
						$value =[$value];
					}
					$compare = 'or' == $operator ? 'IN' : 'NOT IN';
					$field = $this->hpos_mapping_field($meta_key,'arr');
					$value_compare = implode( ', ', array_fill( 0, count($value ), '%s' ) );
					if (!is_array($field)){
						$args['where'] .= $wpdb->prepare(" AND {$field} {$compare} ({$value_compare})", $value);
					}elseif (isset($field['type'],$field['field_name'])){
						$table_name = "viwbe_wc_order_addresses{$meta_key}";
						$args['join'] .= " LEFT JOIN {$wpdb->prefix}wc_order_addresses {$table_name} ON {$wpdb->prefix}wc_orders.id={$table_name}.order_id";
						$args['where'] .= $wpdb->prepare(" AND {$table_name}.address_type = '%s'", $field['type']);
						$args['where'] .= $wpdb->prepare(" AND {$table_name}.{$field['field_name']} {$compare} ({$value_compare}) ", $value);
					}
				}
				unset($this->filter['filter_hpos']['operator'] );
			}
			if ( ! empty( $this->filter['filter_hpos']['behavior'] ) ) {
				foreach ( $this->filter['filter_hpos']['behavior'] as $meta_key => $behavior ) {
					$value = $this->filter[ $meta_key ] ?? '';
					if ( empty( $value ) ) {
						continue;
					}
					$compare = 'like' == $behavior ? 'LIKE' : ( 'exact' == $behavior ? '=' : 'NOT LIKE' );
					$value_compare ='exact' == $behavior ? $wpdb->esc_like($value) : '%' . $wpdb->esc_like( wc_clean( $value ) ) . '%';
					$field = $this->hpos_mapping_field($meta_key,'arr');
					if (!is_array($field)){
						$args['where'] .= $wpdb->prepare(" AND {$field} {$compare} '{$value_compare}'", $value);
					}elseif (isset($field['type'],$field['field_name'])){
						$table_name = "viwbe_wc_order_addresses_behavior{$meta_key}";
						$args['join'] .= " LEFT JOIN {$wpdb->prefix}wc_order_addresses {$table_name} ON {$wpdb->prefix}wc_orders.id={$table_name}.order_id";
						$args['where'] .= $wpdb->prepare(" AND {$table_name}.address_type = '%s'", $field['type']);
						$args['where'] .= " AND {$table_name}.{$field['field_name']} {$compare} '{$value_compare}' ";
					}
				}
			}
		}

		return $args;
	}
	public function set_order_query_args( $wp_query_args ) {
		if ( empty( $this->filter ) ) {
			return $wp_query_args;
		}
		$this->filter['filter_hpos']=[];
		if ( ! empty( $this->filter['operator'] ) ) {
			$this->filter['filter_hpos']['operator']=[];
			foreach ( $this->filter['operator'] as $meta_key => $operator ) {
				$value = $this->filter[ $meta_key ] ?? '';
				if ( is_array( $value ) ) {
					$value = array_filter( $value );
				}

				if ( empty( $value ) ) {
					continue;
				}
				if ('or' === $operator ){
					$wp_query_args[$this->hpos_mapping_field($meta_key)] = $this->filter[ $meta_key ];
				}else{
					$this->filter['filter_hpos']['operator'][$meta_key] = $operator;
				}
			}
		}

		if ( ! empty( $this->filter['behavior'] ) ) {
			$this->filter['filter_hpos']['behavior']=[];
			foreach ( $this->filter['behavior'] as $meta_key => $behavior ) {
				$value = $this->filter[ $meta_key ] ?? '';
				if ( is_array( $value ) ) {
					$value = array_filter( $value );
				}

				if ( empty( $value ) ) {
					continue;
				}
				if ( 'exact' === $behavior && $meta_key !== 'post_excerpt'){
					$wp_query_args[$this->hpos_mapping_field($meta_key)] = $this->filter[ $meta_key ];
				}else{
					$this->filter['filter_hpos']['behavior'][$meta_key] = $behavior;
				}
			}
		}
		if (!empty($this->filter['post_date_from']) || !empty($this->filter['post_date_to'])){
			$wp_query_args['date_query']= [
				'inclusive' => !empty($this->filter['post_date_from']) && !empty($this->filter['post_date_to']),  // Bao gồm cả ngày bắt đầu và kết thúc
			];
			if ( !empty($this->filter['post_date_from']) ){
				$wp_query_args['date_query']['after'] =  $this->filter['post_date_from'].' 00:00:00';
			}
			if ( !empty($this->filter['post_date_to']) ){
				$wp_query_args['date_query']['before'] =  $this->filter['post_date_to'] .' 23:59:59';
			}
		}
		return $wp_query_args;
	}

	public function order_data_store_cpt_get_orders_query( $wp_query_args, $query_vars ) {
		if ( empty( $this->filter ) ) {
			return $wp_query_args;
		}

		if ( ! empty( $this->filter['operator'] ) ) {
			foreach ( $this->filter['operator'] as $meta_key => $operator ) {
				$value = $this->filter[ $meta_key ] ?? '';
				if ( is_array( $value ) ) {
					$value = array_filter( $value );
				}

				if ( empty( $value ) ) {
					continue;
				}

				$compare = 'or' == $operator ? 'IN' : 'NOT IN';

				$wp_query_args['meta_query'][] = [
					'key'     => $meta_key,
					'value'   => $this->filter[ $meta_key ],
					'compare' => $compare
				];
			}
		}

		if ( ! empty( $this->filter['behavior'] ) ) {
			foreach ( $this->filter['behavior'] as $meta_key => $behavior ) {
				$value = $this->filter[ $meta_key ] ?? '';
				if ( is_array( $value ) ) {
					$value = array_filter( $value );
				}

				if ( empty( $value ) ) {
					continue;
				}

				$compare = 'like' == $behavior ? 'LIKE' : ( 'exact' == $behavior ? '=' : 'NOT LIKE' );

				$wp_query_args['meta_query'][] = [
					'key'     => $meta_key,
					'value'   => $this->filter[ $meta_key ],
					'compare' => $compare
				];
			}
		}

		if ( ! empty( $wp_query_args['tax_query'] ) ) {
			$wp_query_args['tax_query']['relation'] = 'AND';
		}

		return $wp_query_args;
	}

	public function add_filter_to_posts_where( $where ) {
		if ( empty( $this->filter ) ) {
			return $where;
		}

		global $wpdb;

		$filter = $this->filter;
		if ( ! empty( $filter['id'] ) && strpos( $filter['id'], '-' ) !== false ) {
			$ids      = array_filter( explode( '-', str_replace( ' ', '', $filter['id'] ) ) );
			$count_id = count( $ids );
			if ( 1 == $count_id ) {
				$id    = absint( $ids[0] );
				$where .= " AND {$wpdb->posts}.ID = {$id} ";
			} elseif ( 2 == $count_id ) {
				$start_id = absint( $ids[0] );
				$end_id   = absint( $ids[1] );

				if ( $start_id < $end_id ) {
					$where .= " AND {$wpdb->posts}.ID >= {$start_id} AND {$wpdb->posts}.ID <= {$end_id} ";
				} elseif ( $start_id > $end_id ) {
					$where .= " AND {$wpdb->posts}.ID >= {$end_id} AND {$wpdb->posts}.ID <= {$start_id} ";
				} else {
					$where .= " AND {$wpdb->posts}.ID = {$start_id} ";
				}
			}
		}

		if ( ! empty( $filter['post_date_from'] ) ) {
			$where .= " AND post_date >= '{$filter['post_date_from']}' ";
		}

		if ( ! empty( $filter['post_date_to'] ) ) {
			$where .= " AND post_date <= '{$filter['post_date_to']}' ";
		}

		$product_ids_from_range_type = $this->parse_type_range( $filter );
		if ( ! empty( $product_ids_from_range_type ) ) {
			$product_ids = -1 !== $product_ids_from_range_type ? implode( ',', $product_ids_from_range_type ) : '';
			$where       .= $product_ids ? " AND ( $wpdb->posts.ID IN($product_ids) )" : " AND (1=2)";
		}


		$where .= $this->text_search();

		unset( $this->filter );

		return $where;
	}

	public function parse_type_range( $filter ) {
		$product_ids = [];
		$flag        = 0;

		$fields = [
			[
				'from'    => 'regular_price_from',
				'to'      => 'regular_price_to',
				'metakey' => '_regular_price',
			],
		];

		foreach ( $fields as $field ) {
			if ( ! empty( $filter[ $field['from'] ] ) || ! empty( $filter[ $field['to'] ] ) ) {
				$flag ++;
				$found_ids   = $this->get_product_ids( $filter[ $field['from'] ], $filter[ $field['to'] ], $field['metakey'] );
				$product_ids = empty( $product_ids ) ? $found_ids : array_intersect( $product_ids, $found_ids );
			}
		}

		if ( $flag && empty( $product_ids ) ) {
			return - 1;
		}

		return array_values( array_unique( $product_ids ) );
	}

	public function get_product_ids( $from, $to, $meta_key ) {
		global $wpdb;

		$from = floatval( ! empty( $from ) ) ? $from : 0;
		$to   = floatval( ! empty( $to ) ) ? $to : PHP_INT_MAX;

		$query = "SELECT posts.ID FROM {$wpdb->posts} AS posts
                    LEFT JOIN {$wpdb->postmeta} AS postmeta ON ( posts.ID = postmeta.post_id )
                    WHERE posts.post_type IN ('product','product_variation')
                    AND postmeta.meta_key = '{$meta_key}' AND postmeta.meta_value BETWEEN {$from} AND {$to}";

		return $this->get_parent_product_from_query( $query );
	}

	public function get_product_ids_from_sale_schedule( $from, $to ) {
		global $wpdb;

		$from = floatval( ! empty( $from ) ) ? strtotime( $from ) : 0;
		$to   = floatval( ! empty( $to ) ) ? strtotime( "tomorrow {$to}" ) - 1 : PHP_INT_MAX;

		$query = "SELECT posts.ID FROM {$wpdb->posts} AS posts INNER JOIN {$wpdb->postmeta} AS postmeta ON ( posts.ID = postmeta.post_id )  
					INNER JOIN {$wpdb->postmeta} AS mt1 ON ( posts.ID = mt1.post_id ) 
					WHERE 1=1  AND (( postmeta.meta_key = '_sale_price_dates_from' AND CAST(postmeta.meta_value AS SIGNED) BETWEEN {$from} AND {$to} )
       								 OR ( mt1.meta_key = '_sale_price_dates_to'  AND CAST(mt1.meta_value AS SIGNED) BETWEEN {$from} AND {$to})) 
    				AND posts.post_type  IN ('product','product_variation')  GROUP BY posts.ID ";

		return $this->get_parent_product_from_query( $query );
	}

	public function get_parent_product_from_query( $query ) {
		global $wpdb;
		$all_products = $wpdb->get_results( $query, ARRAY_N ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$all_products = array_column( $all_products, 0 );

		return $this->get_final_parent_products( $all_products );
	}

	public function get_final_parent_products( $all_product_ids ) {
		if ( ! empty( $all_product_ids ) ) {
			global $wpdb;
			$string_all_products = implode( ',', $all_product_ids );
			$_query              = "SELECT posts.post_parent FROM {$wpdb->posts} AS posts WHERE posts.ID IN ({$string_all_products}) AND posts.post_parent > 0";
			$variable_products   = $wpdb->get_results( $_query, ARRAY_N ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
			$variable_products   = array_column( $variable_products, 0 );
			$product_ids         = array_merge( $variable_products, $all_product_ids );
		}

		return $product_ids ?? [];
	}

	public function text_search() {
		$items = array_map( function ( $key ) {
			return [
				'type'     => $key,
				'value'    => $this->filter[ $key ] ?? '',
				'behavior' => $this->filter['behavior'][ $key ] ?? '',
			];
		}, [ 'post_title', 'post_content', 'post_excerpt', 'post_name' ] );

		if ( empty( $items ) || ! is_array( $items ) ) {
			return '';
		}

		$where = '';

		foreach ( $items as $item ) {
			if ( ! $item['value'] && 'empty' !== $item['behavior'] ) {
				continue;
			}

			$type  = $item['type'];
			$value = sanitize_text_field( wp_specialchars_decode( trim( $item['value'] ) ) );

			$query = '';
			switch ( $item['behavior'] ) {
				case 'empty':
					$query .= "{$type} =''";
					break;

				case 'exact':
					$query .= "{$type} ='{$value}'";
					break;

				case 'not':
					$query .= "{$type} NOT LIKE '%{$value}%'";
					break;

				case 'begin':
					$query .= "{$type} REGEXP '^{$value}'";
					break;

				case 'end':
					$query .= "{$type} REGEXP '{$value}$'";
					break;

				default:
					$query .= "{$type} LIKE '%{$value}%'";
					break;
			}

			if ( $query ) {
				$where .= " AND ($query)";
			}
		}

		return $where;
	}
}