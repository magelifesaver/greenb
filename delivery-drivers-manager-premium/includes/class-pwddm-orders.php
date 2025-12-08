<?php
/**
 * Orders page.
 *
 * All the orders functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */

/**
 * Orders class.
 *
 * All the orders functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */
class PWDDM_Orders {

	/**
	 * Dashboard claim report query.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function pwddm_claim_orders_dashboard_report_query() {
		global $wpdb;

		if ( pwddm_is_hpos_enabled() ) {

				// Query for HPOS-enabled environments
				$query = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT o.status AS post_status, COUNT(*) AS orders
						FROM ' . $wpdb->prefix . 'wc_orders o
						LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta om ON o.id = om.order_id AND om.meta_key = \'lddfw_driverid\'
						LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta om1 ON o.id = om1.order_id AND om1.meta_key = \'lddfw_delivered_date\'
						WHERE o.type = \'shop_order\' AND ( om.meta_value IS NULL OR om.meta_value = \'-1\' OR om.meta_value = \'\' ) AND
						(
							o.status IN (%s, %s, %s, %s) OR
							( o.status = %s AND CAST( om1.meta_value AS DATE ) >= %s AND CAST( om1.meta_value AS DATE ) <= %s )
						)
						GROUP BY o.status',
						array(
							get_option( 'lddfw_processing_status', '' ),
							get_option( 'lddfw_driver_assigned_status', '' ),
							get_option( 'lddfw_out_for_delivery_status', '' ),
							get_option( 'lddfw_failed_attempt_status', '' ),
							get_option( 'lddfw_delivered_status', '' ),
							date_i18n( 'Y-m-d' ),
							date_i18n( 'Y-m-d' ),
						)
					)
				);

		} else {
			// Non-HPOS environment query

			$query = $wpdb->get_results(
				$wpdb->prepare(
					'select post_status, count(*) as orders from ' . $wpdb->prefix . 'posts p
				left join ' . $wpdb->prefix . 'postmeta pm on p.id=pm.post_id and pm.meta_key = \'lddfw_driverid\'
				left join ' . $wpdb->prefix . 'postmeta pm1 on p.id=pm1.post_id and pm1.meta_key = \'lddfw_delivered_date\'
				where post_type=\'shop_order\' and ( pm.meta_value is null or pm.meta_value = \'-1\' or pm.meta_value = \'\' ) and
				(
					post_status in (%s,%s,%s,%s) or
					( post_status = %s and CAST( pm1.meta_value AS DATE ) >= %s and CAST( pm1.meta_value AS DATE ) <= %s )
				)
				group by post_status',
					array(
						get_option( 'lddfw_processing_status', '' ),
						get_option( 'lddfw_driver_assigned_status', '' ),
						get_option( 'lddfw_out_for_delivery_status', '' ),
						get_option( 'lddfw_failed_attempt_status', '' ),
						get_option( 'lddfw_delivered_status', '' ),
						date_i18n( 'Y-m-d' ),
						date_i18n( 'Y-m-d' ),
					)
				)
			); // db call ok; no-cache ok.
		}
		return $query;
	}


	/**
	 * Drivers orders dashboard report query.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function pwddm_drivers_orders_dashboard_report_query() {
			global $wpdb;

		if ( pwddm_is_hpos_enabled() ) {

			 // Query adapted for HPOS-enabled environments.
			$query = $wpdb->get_results(
				$wpdb->prepare(
					'select l.driver_id, wo.status as post_status , count(*) as orders
						from ' . $wpdb->prefix . 'wc_orders wo
						inner join ' . $wpdb->prefix . 'lddfw_orders l ON wo.id = l.order_id
						where wo.type=\'shop_order\' and l.driver_id > 0 and
						(
							wo.status in (%s,%s,%s) or
							( wo.status = %s AND CAST(l.delivered_date AS DATE) BETWEEN %s AND %s )
						)
						group by l.driver_id, wo.status
						order by l.driver_id',
					array(
						get_option( 'lddfw_driver_assigned_status', '' ),
						get_option( 'lddfw_out_for_delivery_status', '' ),
						get_option( 'lddfw_failed_attempt_status', '' ),
						get_option( 'lddfw_delivered_status', '' ),
						date_i18n( 'Y-m-d' ),
						date_i18n( 'Y-m-d' ),
					)
				)
			); // db call ok; no-cache ok.

		} else {
			 // Original query for non-HPOS environments.
			$query = $wpdb->get_results(
				$wpdb->prepare(
					'select o.driver_id , post_status , count(*) as orders 
				from ' . $wpdb->prefix . 'posts p
				inner join ' . $wpdb->prefix . 'lddfw_orders o ON p.ID = o.order_id
				where post_type=\'shop_order\' and o.driver_id > 0 and
				(
					post_status in (%s,%s,%s) or
					( post_status = %s AND CAST(delivered_date AS DATE) BETWEEN %s AND %s )
				)
				group by o.driver_id, post_status
				order by o.driver_id ',
					array(
						get_option( 'lddfw_driver_assigned_status', '' ),
						get_option( 'lddfw_out_for_delivery_status', '' ),
						get_option( 'lddfw_failed_attempt_status', '' ),
						get_option( 'lddfw_delivered_status', '' ),
						date_i18n( 'Y-m-d' ),
						date_i18n( 'Y-m-d' ),
					)
				)
			); // db call ok; no-cache ok.
		}
		return $query;

	}
	/**
	 * Orders count query.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return html
	 */
	public function pwddm_orders_count_query( $driver_id ) {
		global $wpdb;

		if ( pwddm_is_hpos_enabled() ) {
			// Query adapted for HPOS-enabled environments.
			$query = $wpdb->get_results(
				$wpdb->prepare(
					'select wo.status as post_status, count(*) as orders
					from ' . $wpdb->prefix . 'wc_orders wo
					inner join ' . $wpdb->prefix . 'wc_orders_meta wom on wo.id=wom.order_id and wom.meta_key = \'pwddm_managerid\' and wom.meta_value = %s
					left join ' . $wpdb->prefix . 'wc_orders_meta wom1 on wo.id=wom1.order_id and wom1.meta_key = \'pwddm_delivered_date\'
					where wo.type=\'shop_order\' and
					(
						wo.status in (%s,%s,%s) or
						( wo.status = %s and CAST( wom1.meta_value AS DATE ) >= %s and CAST( wom1.meta_value AS DATE ) <= %s )
					)
					group by wo.status',
					array(
						$driver_id,
						get_option( 'lddfw_driver_assigned_status', '' ),
						get_option( 'lddfw_out_for_delivery_status', '' ),
						get_option( 'lddfw_failed_attempt_status', '' ),
						get_option( 'lddfw_delivered_status', '' ),
						date_i18n( 'Y-m-d' ),
						date_i18n( 'Y-m-d' ),
					)
				)
			); // db call ok; no-cache ok.
		} else {
			// Original query for non-HPOS environments.

			$query = $wpdb->get_results(
				$wpdb->prepare(
					'select post_status , count(*) as orders from ' . $wpdb->prefix . 'posts p
				inner join ' . $wpdb->prefix . 'postmeta pm on p.id=pm.post_id and pm.meta_key = \'pwddm_managerid\' and pm.meta_value = %s
				left join ' . $wpdb->prefix . 'postmeta pm1 on p.id=pm1.post_id and pm1.meta_key = \'pwddm_delivered_date\'
				where post_type=\'shop_order\' and
				(
					post_status in (%s,%s,%s) or
					( post_status = %s and CAST( pm1.meta_value AS DATE ) >= %s and CAST( pm1.meta_value AS DATE ) <= %s )
				)
				group by post_status',
					array(
						$driver_id,
						get_option( 'lddfw_driver_assigned_status', '' ),
						get_option( 'lddfw_out_for_delivery_status', '' ),
						get_option( 'lddfw_failed_attempt_status', '' ),
						get_option( 'lddfw_delivered_status', '' ),
						date_i18n( 'Y-m-d' ),
						date_i18n( 'Y-m-d' ),
					)
				)
			); // db call ok; no-cache ok.
		}

		return $query;

	}


	/**
	 * Drivers orders dashboard report.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function pwddm_managers_orders_dashboard_report_query() {
		global $wpdb;
		if ( pwddm_is_hpos_enabled() ) {
			// Query adapted for HPOS-enabled environments.
			$query = $wpdb->get_results(
				$wpdb->prepare(
					'select wom.meta_value as driver_id, wo.status as post_status, u.display_name as driver_name, count(*) as orders
					from ' . $wpdb->prefix . 'wc_orders wo
					inner join ' . $wpdb->prefix . 'wc_orders_meta wom on wo.id=wom.order_id and wom.meta_key = \'pwddm_managerid\'
					inner join ' . $wpdb->base_prefix . 'users u on u.id = wom.meta_value
					left join ' . $wpdb->prefix . 'wc_orders_meta wom1 on wo.id=wom1.order_id and wom1.meta_key = \'pwddm_delivered_date\'
					where wo.type=\'shop_order\' and
					(
						wo.status in (%s,%s,%s) or
						( wo.status = %s and CAST( wom1.meta_value AS DATE ) >= %s and CAST( wom1.meta_value AS DATE ) <= %s )
					)
					group by wom.meta_value, wo.status
					order by wom.meta_value',
					array(
						get_option( 'lddfw_driver_assigned_status', '' ),
						get_option( 'lddfw_out_for_delivery_status', '' ),
						get_option( 'lddfw_failed_attempt_status', '' ),
						get_option( 'lddfw_delivered_status', '' ),
						date_i18n( 'Y-m-d' ),
						date_i18n( 'Y-m-d' ),
					)
				)
			); // db call ok; no-cache ok.
		} else {
			// Original query for non-HPOS environments.
			$query = $wpdb->get_results(
				$wpdb->prepare(
					'select pm.meta_value driver_id , post_status, u.display_name driver_name , count(*) as orders
				from ' . $wpdb->prefix . 'posts p
				inner join ' . $wpdb->prefix . 'postmeta pm on p.id=pm.post_id and pm.meta_key = \'pwddm_managerid\'
				inner join ' . $wpdb->base_prefix . 'users u on u.id = pm.meta_value
				left join ' . $wpdb->prefix . 'postmeta pm1 on p.id=pm1.post_id and pm1.meta_key = \'pwddm_delivered_date\'
				where post_type=\'shop_order\' and
				(
					post_status in (%s,%s,%s) or
					( post_status = %s and CAST( pm1.meta_value AS DATE ) >= %s and CAST( pm1.meta_value AS DATE ) <= %s )
				)
				group by pm.meta_value, post_status
				order by pm.meta_value ',
					array(
						get_option( 'lddfw_driver_assigned_status', '' ),
						get_option( 'lddfw_out_for_delivery_status', '' ),
						get_option( 'lddfw_failed_attempt_status', '' ),
						get_option( 'lddfw_delivered_status', '' ),
						date_i18n( 'Y-m-d' ),
						date_i18n( 'Y-m-d' ),
					)
				)
			); // db call ok; no-cache ok.
		}

		return $query;

	}






	/**
	 * Assign to driver count query.
	 *
	 * @since 1.0.0
	 * @param int $driver_id driver user id.
	 * @return array
	 */
	public function pwddm_assign_to_driver_count_query( $driver_id ) {
		global $wpdb;
		if ( pwddm_is_hpos_enabled() ) {
			// Query adapted for HPOS-enabled environments.
			return $wpdb->get_results(
				$wpdb->prepare(
					'select count(*) as orders 
					from ' . $wpdb->prefix . 'wc_orders wo
					inner join ' . $wpdb->prefix . 'wc_orders_meta wom on wo.id=wom.order_id and wom.meta_key = \'lddfw_driverid\'
					where wo.type=\'shop_order\' and wo.status in (%s)
					and wom.meta_value = %s group by wo.status',
					array(
						get_option( 'lddfw_driver_assigned_status', '' ),
						$driver_id,
					)
				)
			); // db call ok; no-cache ok.
		} else {
			// Original query for non-HPOS environments.
			return $wpdb->get_results(
				$wpdb->prepare(
					'select count(*) as orders from ' . $wpdb->prefix . 'posts p
				inner join ' . $wpdb->prefix . 'postmeta pm on p.id=pm.post_id and pm.meta_key = \'lddfw_driverid\'
				where post_type=\'shop_order\' and post_status in (%s)
				and pm.meta_value = %s group by post_status',
					array(
						get_option( 'lddfw_driver_assigned_status', '' ),
						$driver_id,
					)
				)
			); // db call ok; no-cache ok.
		}
	}




	/**
	 * Orders for route plan
	 *
	 * @since 1.0.0
	 * @param int $manager_id manager user id.
	 * @return html
	 */
	public function pwddm_orders_route__premium_only( $manager_id ) {
		global $wpdb;
		$store       = new LDDFW_Store();
		$route       = new PWDDM_Route();
		$lddfw_order = new LDDFW_Order();

		// Handle orders form post.
		$this->pwddm_orders_form_post( $manager_id, 'ajax' );

		function pwddm_random_color_part() {
			return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT );
		}

		function pwddm_random_color() {
			return pwddm_random_color_part() . pwddm_random_color_part() . pwddm_random_color_part();
		}

		$pickup_colors   = array(
			'#0073b7', // Spanish Blue.
			'#000000', // Black.
			'#f7bb13', // Spanish Yellow.
			'#57b0ed', // Blue Jeans.
			'#8d8787', // Taupe Gray.
			'#9c004a', // Rose Garnet.
			'#c7387c', // Fuchsia Purple.
			'#829ab1', // Weldon Blue.
			'#be7660', // Deer.
			'#696006', // Bronze Yellow.
			'#f39c12', // Gamboge.
		);
		$pickup_color[0] = '#ed7fc4';
		$pickup_counter  = 0;

		$pwddm_manager_drivers = get_user_meta( $manager_id, 'pwddm_manager_drivers', true );
		// Get url params.
		$pwddm_orders_filter = ( isset( $_POST['pwddm_orders_filter'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_orders_filter'] ) ) : '';
		$pwddm_from_date     = ( isset( $_POST['pwddm_from_date'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_from_date'] ) ) : '';
		$pwddm_to_date       = ( isset( $_POST['pwddm_to_date'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_to_date'] ) ) : '';
		$pwddm_orders_status = ( isset( $_POST['pwddm_orders_status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_orders_status'] ) ) : get_option( 'lddfw_out_for_delivery_status', '' );
		$pwddm_dates         = ( isset( $_POST['pwddm_dates_range'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_dates_range'] ) ) : '';
		$pwddm_page          = ( isset( $_POST['pwddm_page'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_page'] ) ) : 1;
		$manager_has_orders  = false;
		/**
		 * Set current status names
		 */
		$pwddm_driver_assigned_status_name  = esc_html( __( 'Driver assigned', 'pwddm' ) );
		$pwddm_out_for_delivery_status_name = esc_html( __( 'Out for delivery', 'pwddm' ) );
		$pwddm_failed_attempt_status_name   = esc_html( __( 'Failed delivery', 'pwddm' ) );
		$pwddm_processing_status_name       = esc_html( __( 'Processing', 'pwddm' ) );

		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$result = wc_get_order_statuses();
			if ( ! empty( $result ) ) {
				foreach ( $result as $key => $status ) {
					switch ( $key ) {
						case get_option( 'lddfw_out_for_delivery_status' ):
							if ( $status !== $pwddm_out_for_delivery_status_name ) {
								$pwddm_out_for_delivery_status_name = $status;
							}
							break;
						case get_option( 'lddfw_failed_attempt_status' ):
							if ( esc_html( __( 'Failed Delivery Attempt', 'pwddm' ) ) !== $status ) {
								$pwddm_failed_attempt_status_name = $status;
							}
							break;
						case get_option( 'lddfw_driver_assigned_status' ):
							if ( $status !== $pwddm_driver_assigned_status_name ) {
								$pwddm_driver_assigned_status_name = $status;
							}
							break;
					}
				}
			}
		}

		if ( '' === $pwddm_dates ) {
			$from_date = date_i18n( 'Y-m-d' );
			$to_date   = date_i18n( 'Y-m-d' );
		} else {
			$pwddm_dates_array = explode( ',', $pwddm_dates );
			if ( 1 < count( $pwddm_dates_array ) ) {
				if ( $pwddm_dates_array[0] === $pwddm_dates_array[1] ) {
					$from_date = date_i18n( 'Y-m-d', strtotime( $pwddm_dates_array[0] ) );
					$to_date   = date_i18n( 'Y-m-d', strtotime( $pwddm_dates_array[0] ) );
				} else {
					$from_date = date_i18n( 'Y-m-d', strtotime( $pwddm_dates_array[0] ) );
					$to_date   = date_i18n( 'Y-m-d', strtotime( $pwddm_dates_array[1] ) );
				}
			} else {
				$from_date = date_i18n( 'Y-m-d', strtotime( $pwddm_dates_array[0] ) );
				$to_date   = date_i18n( 'Y-m-d', strtotime( $pwddm_dates_array[0] ) );
			}
		}

			// Orders query.
			$orders_per_page = 50;
			$counter         = $pwddm_page > 1 ? $orders_per_page * ( $pwddm_page ) - $orders_per_page + 1 : 1;

			// Status.
		if ( '' !== $pwddm_orders_status ) {
			$status_array = array( $pwddm_orders_status );
		} else {
			$status_array = array(
				get_option( 'lddfw_driver_assigned_status', '' ),
				get_option( 'lddfw_out_for_delivery_status', '' ),
				get_option( 'lddfw_failed_attempt_status', '' ),
				get_option( 'lddfw_delivered_status', '' ),
				get_option( 'lddfw_processing_status', '' ),
			);
		}

			// Filter by orders without drivers.
			$no_driver_array = array();
		if ( '-2' === $pwddm_orders_filter ) {
			$no_driver_array = array(
				'relation' => 'or',
				array(
					'key'     => 'lddfw_driverid',
					'value'   => '-1',
					'compare' => '=',
				),
				array(
					'key'     => 'lddfw_driverid',
					'value'   => '',
					'compare' => '=',
				),
				array(
					'key'     => 'lddfw_driverid',
					'compare' => 'NOT EXISTS',
				),
			);
		}

			// Filter by orders without drivers.
			$filter_array = array();
		if ( '-1' === $pwddm_orders_filter ) {
			$filter_array = array(
				'relation' => 'and',
				array(
					'key'     => 'lddfw_driverid',
					'value'   => '-1',
					'compare' => '!=',
				),
				array(
					'key'     => 'lddfw_driverid',
					'compare' => 'EXISTS',
				),
			);
		}

			// Filter by driver id.
			$driver_array = array();
		if ( 0 < intval( $pwddm_orders_filter ) ) {
			$driver_array = array(
				'key'     => 'lddfw_driverid',
				'value'   => $pwddm_orders_filter,
				'compare' => '=',
			);
		}

			$date_array = array();
			// Filter for delivered date range.
		if ( '' !== $from_date && '' !== $to_date && '' !== $pwddm_dates ) {
			$date_array = array(
				'relation' => 'and',
				array(
					'key'     => 'lddfw_delivered_date',
					'value'   => $from_date,
					'compare' => '>=',
					'type'    => 'DATE',
				),
				array(
					'key'     => 'lddfw_delivered_date',
					'value'   => $to_date,
					'compare' => '<=',
					'type'    => 'DATE',
				),
			);
		}

			$params = array(
				'posts_per_page' => $orders_per_page,
				'post_status'    => $status_array,
				'post_type'      => 'shop_order',
				'paginate'       => true,
				'return'         => 'ids',
				'paged'          => $pwddm_page,
				'orderby'        => array(
					'ID' => 'DESC',
				),
			);

			$params['meta_query'] = array(
				'relation' => 'AND',
				$date_array,
				$driver_array,
				$filter_array,
				$no_driver_array,
			);

			if ( pwddm_is_hpos_enabled() ) {
				$orders       = wc_get_orders( $params );
				$orders_array = $orders->orders;
			} else {
				$orders       = new WP_Query( $params );
				$orders_array = array_map(
					function ( $order ) {
						return $order->ID;
					},
					$orders->posts
				);
			}

			$date_format = lddfw_date_format( 'date' );
			$time_format = lddfw_date_format( 'time' );

			$html = '


	<div class="row">
		<div class="col-12">';

			if ( $orders ) {

				// Pagination.
				$base       = '%#%';
				$pagination = paginate_links(
					array(
						'base'         => '%_%',
						'total'        => $orders->max_num_pages,
						'current'      => $pwddm_page,
						'format'       => '?%#%',
						'show_all'     => false,
						'type'         => 'array',
						'end_size'     => 2,
						'mid_size'     => 0,
						'prev_next'    => true,
						'prev_text'    => sprintf( '<i></i> %1$s', __( '<<', 'pwddm' ) ),
						'next_text'    => sprintf( '%1$s <i></i>', __( '>>', 'pwddm' ) ),
						'add_args'     => false,
						'add_fragment' => '',
					)
				);

				if ( ! empty( $pagination ) ) {
					$html .= '<div class="pagination text-sm-center"><nav aria-label="Page navigation" style="width:100%"><ul class="pagination justify-content-center">';
					foreach ( $pagination as $page ) {
						$html .= "<li class='page-item ";
						if ( strpos( $page, 'current' ) !== false ) {
							$html .= ' active';
						}
						$html .= "' > " . str_replace( 'page-numbers', 'page-link', $page ) . '</li>';
					}
					$html .= '</nav></div>';
				}

				$html .= '	
				<div id="route_orders_table_wrap">
					<table class="table table-striped table-hover">
					<thead class="table-dark">
						<tr>
							<th scope="row">
								<div id="pwddm_multi_checkbox_div" class="form-check custom-checkbox">
									<input value="" id="pwddm_multi_checkbox" type="checkbox" data="order_checkbox" class="form-check-input" >
									<label class="custom-control-label" for="pwddm_multi_checkbox"></label>
								</div>
							</th>
							<th scope="col" style="width:75%">' . esc_html( __( 'Order', 'pwddm' ) ) . '</th>
							<th scope="col">' . esc_html( __( 'Driver', 'pwddm' ) ) . '</th>
							<th scope="col">' . esc_html( __( 'Location', 'pwddm' ) ) . '</th>
						</tr>
					</thead>
					<tbody>';

					// Results.
				foreach ( $orders_array as $order_id ) {

					$order = wc_get_order( $order_id );
					// Check if the order is valid
					if ( ! $order ) {
						continue; // Move to the next iteration if the order is not valid
					}
					$order_date        = $order->get_date_created()->format( lddfw_date_format( 'date' ) );
					$order_status      = $order->get_status();
					$order_status_name = wc_get_order_status_name( $order_status );
					$order_number      = $order->get_order_number();
                    
					// shipping first and last name
					$shipping_first_name = $order->get_shipping_first_name();
					$shipping_last_name  = $order->get_shipping_last_name();

					// Get and format the shipping address.
					$shipping_array   = $lddfw_order->lddfw_order_address( 'shipping', $order, $order_id );
					$shipping_address = lddfw_format_address( 'address_line', $shipping_array );

					$order_driverid = $order->get_meta( 'lddfw_driverid' );
					$driver         = get_userdata( $order_driverid );

					$driver_name = ( ! empty( $driver ) ) ? $driver->display_name : '';

					$driver_manager_id = '';
					if ( '' !== $order_driverid && '-1' !== $order_driverid ) {
						$driver_manager_id = get_user_meta( $order_driverid, 'pwddm_manager', true );
					}
					if ( ( '' === $order_driverid || '-1' === $order_driverid )
					||
					(
						( ( '0' === $pwddm_manager_drivers || '' === $pwddm_manager_drivers ) && '' === strval( $driver_manager_id ) )
						||
						( '2' === $pwddm_manager_drivers && strval( $driver_manager_id ) === strval( $manager_id ) )
						||
						( '1' === $pwddm_manager_drivers && ( strval( $driver_manager_id ) === strval( $manager_id ) || '' === strval( $driver_manager_id ) ) )
					)
					) {
						$manager_has_orders        = true;
						$geocode_status            = '';
						$geocode_coordinates       = '';
						$geocode_formatted_address = '';
						$geocode_location_type     = '';
						$geocode_array             = $order->get_meta( '_lddfw_address_geocode' );

						if ( ! empty( $geocode_array ) && is_array( $geocode_array ) ) {
							if ( 'ZERO_RESULTS' === $geocode_array[0] ) {
								$geocode_location_type = 'ZERO_RESULTS';
							}
							if ( ! empty( $geocode_array[1] ) ) {
								$geocode_status            = $geocode_array[1];
								$geocode_coordinates       = $geocode_array[1];
								$geocode_formatted_address = $geocode_array[2];
								$geocode_location_type     = $geocode_array[3];
							}
						}

						// Sellers pickup address geocode.
						$seller_id = $store->lddfw_order_seller( $order );
						if ( '1' === $seller_id ) {
							$seller_id = '';
						}

						$pickup_name = '';
						if ( method_exists( $store, 'lddfw_store_name__premium_only' ) ) {
							$pickup_name = $store->lddfw_store_name__premium_only( '', $seller_id );
						}

						$pickup_address = $store->lddfw_pickup_address( 'map_address', $order, $seller_id );
						$pickup_type    = $store->get_pickup_type( $order );

						$pickup_geocode = '';
						$pickup_id      = '0';

						if ( '' !== $seller_id ) {
							$route->set_seller_geocode( $seller_id );
							$coordinates = $route->get_seller_geocode( $seller_id );
							if ( false !== $coordinates && is_array( $coordinates ) ) {
								$pickup_geocode = $coordinates[0] . ',' . $coordinates[1];
							}
							$pickup_id = '1' . $seller_id;
						} else {
							$pickup_geocode = $route->get_store_geocode();
						}

						$pickup_geocode = apply_filters( 'lddfw_get_order_pickup_geocode', $pickup_geocode, $order );

						if ( 'customer' === $pickup_type ) {
							$pickup_id   = '2' . $order_id;
							$pickup_name = 'customer';
						}

						if ( 'warehouse' === $pickup_type ) {
							$pickup_location = $order->get_meta( '_plfdd_pickup_location' );
							if ( is_numeric( $pickup_location ) ) {
								$pickup_id = '3' . $order->get_meta( '_plfdd_pickup_location' );
							}
							$pickup_name = 'warehouse';
						}

						$add_pickup_color = 1;
						foreach ( $pickup_color as $key => $value ) {
							if ( strval( $key ) === strval( $pickup_id ) ) {
								$add_pickup_color = 0;
								break;
							}
						}

						if ( 1 === $add_pickup_color ) {
							$pickup_color[ $pickup_id ] = $pickup_colors[ $pickup_counter ];
							$pickup_counter++;
						}

						// Set address by coordinates.
						$coordinates = $lddfw_order->lddfw_order_shipping_address_coordinates( $order );

						$html .= '
					<tr>
					<th scope="row">
						<div class="custom-control custom-checkbox">
							<input name="pwddm_order_id[]" order_number="' . $order_number . '" color="' . esc_attr( $pickup_color[ $pickup_id ] ) . '" pickup_address="' . esc_attr( str_replace( '+', ' ', $pickup_address ) ) . '" pickup_name ="' . esc_attr( $pickup_name ) . '" pickup_coordinates="' . esc_attr( $pickup_geocode ) . '" pickup_id="' . esc_attr( $pickup_id ) . '" formatted_address="' . esc_attr( $geocode_formatted_address ) . '" coordinates="' . esc_attr( $geocode_coordinates ) . '" value="' . esc_attr( $order_id ) . '" id="pwddm_order_id_' . esc_attr( $order_id ) . '" type="checkbox" class="order_checkbox form-check-input" >
							<label class="custom-control-label" for="pwddm_order_id_' . $counter . '"></label>
						</div>
					</th>
					<td>';

						if ( '' !== $geocode_coordinates ) {
							$html .= '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="shopping-cart" data="' . esc_attr( $order_id ) . '" class="order_cart_icon svg-inline--fa fa-shopping-cart fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="' . esc_attr( $pickup_color[ $pickup_id ] ) . '" d="M528.12 301.319l47.273-208C578.806 78.301 567.391 64 551.99 64H159.208l-9.166-44.81C147.758 8.021 137.93 0 126.529 0H24C10.745 0 0 10.745 0 24v16c0 13.255 10.745 24 24 24h69.883l70.248 343.435C147.325 417.1 136 435.222 136 456c0 30.928 25.072 56 56 56s56-25.072 56-56c0-15.674-6.447-29.835-16.824-40h209.647C430.447 426.165 424 440.326 424 456c0 30.928 25.072 56 56 56s56-25.072 56-56c0-22.172-12.888-41.332-31.579-50.405l5.517-24.276c3.413-15.018-8.002-29.319-23.403-29.319H218.117l-6.545-32h293.145c11.206 0 20.92-7.754 23.403-18.681z"></path></svg> ';
						}
						$html .= '#<a target="_blank" href="' . esc_attr( pwddm_manager_page_url( 'pwddm_screen=order&pwddm_orderid=' . $order_id ) ) . '" >' . esc_html( $order->get_order_number() ) . '</a> - ' . esc_html( $order_status_name ) . ' <br>
						' . $shipping_first_name . ' ' . $shipping_last_name . '<br>
						' . $shipping_address . '<br>';

						// Print coordinates.
						if ( '' !== $coordinates ) {
							$html .= '<span><svg style="width:14px;height:14px;color:silver" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="map-marker-alt" class="svg-inline--fa fa-map-marker-alt fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"></path></svg> ' . esc_attr( $coordinates ) . '</span> ';
						}

						$html .= '<hr>';

						// Pickup type.
						if ( 'store' === $pickup_type ) {
							if ( '' !== $pickup_geocode ) {
								$html .= '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="store" data="' . esc_attr( $pickup_id ) . '" class="order_seller_icon svg-inline--fa fa-store fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 616 512"><path fill="' . esc_attr( $pickup_color[ $pickup_id ] ) . '" d="M602 118.6L537.1 15C531.3 5.7 521 0 510 0H106C95 0 84.7 5.7 78.9 15L14 118.6c-33.5 53.5-3.8 127.9 58.8 136.4 4.5.6 9.1.9 13.7.9 29.6 0 55.8-13 73.8-33.1 18 20.1 44.3 33.1 73.8 33.1 29.6 0 55.8-13 73.8-33.1 18 20.1 44.3 33.1 73.8 33.1 29.6 0 55.8-13 73.8-33.1 18.1 20.1 44.3 33.1 73.8 33.1 4.7 0 9.2-.3 13.7-.9 62.8-8.4 92.6-82.8 59-136.4zM529.5 288c-10 0-19.9-1.5-29.5-3.8V384H116v-99.8c-9.6 2.2-19.5 3.8-29.5 3.8-6 0-12.1-.4-18-1.2-5.6-.8-11.1-2.1-16.4-3.6V480c0 17.7 14.3 32 32 32h448c17.7 0 32-14.3 32-32V283.2c-5.4 1.6-10.8 2.9-16.4 3.6-6.1.8-12.1 1.2-18.2 1.2z"></path></svg> ';
							}
							$html .= esc_html( __( 'Pickup from', 'pwddm' ) ) . ' ' . $pickup_name . '<br> ' . esc_attr( str_replace( '+', ' ', $pickup_address ) );
						} else {
							if ( '' !== $pickup_geocode ) {
								$html .= '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="store" data="' . esc_attr( $pickup_id ) . '" class="order_seller_icon svg-inline--fa fa-store fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 616 512"><path fill="' . esc_attr( $pickup_color[ $pickup_id ] ) . '" d="M602 118.6L537.1 15C531.3 5.7 521 0 510 0H106C95 0 84.7 5.7 78.9 15L14 118.6c-33.5 53.5-3.8 127.9 58.8 136.4 4.5.6 9.1.9 13.7.9 29.6 0 55.8-13 73.8-33.1 18 20.1 44.3 33.1 73.8 33.1 29.6 0 55.8-13 73.8-33.1 18 20.1 44.3 33.1 73.8 33.1 29.6 0 55.8-13 73.8-33.1 18.1 20.1 44.3 33.1 73.8 33.1 4.7 0 9.2-.3 13.7-.9 62.8-8.4 92.6-82.8 59-136.4zM529.5 288c-10 0-19.9-1.5-29.5-3.8V384H116v-99.8c-9.6 2.2-19.5 3.8-29.5 3.8-6 0-12.1-.4-18-1.2-5.6-.8-11.1-2.1-16.4-3.6V480c0 17.7 14.3 32 32 32h448c17.7 0 32-14.3 32-32V283.2c-5.4 1.6-10.8 2.9-16.4 3.6-6.1.8-12.1 1.2-18.2 1.2z"></path></svg> ';
							}
							$html .= esc_html( __( 'Pickup from', 'pwddm' ) ) . ' ' . $pickup_type . '<br> ' . esc_attr( str_replace( '+', ' ', $pickup_address ) );
						}

						$html .= '</td>
					<td>' . $driver_name . '</td>
					<td align="center" id="geocode_location_type_' . $order_id . '" data-order="' . $geocode_location_type . '" >';
						if ( '' !== $geocode_location_type ) {
							switch ( $geocode_location_type ) {
								case 'ROOFTOP':
									$html .= '<div class="pwddm_tooltip_wrap">
									<svg style="color:#5dce9c" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="check-double" class="pwddm_open_tooltip svg-inline--fa fa-check-double fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M505 174.8l-39.6-39.6c-9.4-9.4-24.6-9.4-33.9 0L192 374.7 80.6 263.2c-9.4-9.4-24.6-9.4-33.9 0L7 302.9c-9.4 9.4-9.4 24.6 0 34L175 505c9.4 9.4 24.6 9.4 33.9 0l296-296.2c9.4-9.5 9.4-24.7.1-34zm-324.3 106c6.2 6.3 16.4 6.3 22.6 0l208-208.2c6.2-6.3 6.2-16.4 0-22.6L366.1 4.7c-6.2-6.3-16.4-6.3-22.6 0L192 156.2l-55.4-55.5c-6.2-6.3-16.4-6.3-22.6 0L68.7 146c-6.2 6.3-6.2 16.4 0 22.6l112 112.2z"></path></svg>	
									';
									$html .= '<div style="display:none" class="pwddm_tooltip">' . esc_html( __( '"ROOFTOP" indicates that the returned result is a precise geocode for which we have location information accurate down to street address precision.', 'pwddm' ) ) . '</div></div>';
									break;
								case 'GEOMETRIC_CENTER':
									$html .= '<div class="pwddm_tooltip_wrap">
									<svg style="color:#17b3e8" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="check" class="pwddm_open_tooltip svg-inline--fa fa-check fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"></path></svg>
									';
									$html .= '<div style="display:none" class="pwddm_tooltip">' . esc_html( __( '"GEOMETRIC_CENTER" indicates that the returned result is the geometric center of a result such as a polyline (for example, a street) or polygon (region).', 'pwddm' ) ) . '</div></div>';
									break;
								case 'APPROXIMATE':
									$html .= '<div class="pwddm_tooltip_wrap">
									<svg style="color:#f1c900" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="exclamation" class="pwddm_open_tooltip svg-inline--fa fa-exclamation fa-w-6" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512"><path fill="currentColor" d="M176 432c0 44.112-35.888 80-80 80s-80-35.888-80-80 35.888-80 80-80 80 35.888 80 80zM25.26 25.199l13.6 272C39.499 309.972 50.041 320 62.83 320h66.34c12.789 0 23.331-10.028 23.97-22.801l13.6-272C167.425 11.49 156.496 0 142.77 0H49.23C35.504 0 24.575 11.49 25.26 25.199z"></path></svg>';

									$html .= '<div style="display:none" class="pwddm_tooltip">' . esc_html( __( '"APPROXIMATE" indicates that the returned result is approximate.', 'pwddm' ) ) . '</div></div>';
									break;
								case 'RANGE_INTERPOLATED':
									$html .= '<div class="pwddm_tooltip_wrap">
									<svg  style="color:#fe7a17" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="minus" class="pwddm_open_tooltip svg-inline--fa fa-minus fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M416 208H32c-17.67 0-32 14.33-32 32v32c0 17.67 14.33 32 32 32h384c17.67 0 32-14.33 32-32v-32c0-17.67-14.33-32-32-32z"></path></svg>';
									$html .= '<div style="display:none" class="pwddm_tooltip">' . esc_html( __( '"RANGE_INTERPOLATED" indicates that the returned result reflects an approximation (usually on a road) interpolated between two precise points (such as intersections). Interpolated results are generally returned when rooftop geocodes are unavailable for a street address.', 'pwddm' ) ) . '</div></div>';
									break;
								default:
									$html .= '<div class="pwddm_tooltip_wrap"><svg style="color:#b91700" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="times" class="pwddm_open_tooltip svg-inline--fa fa-times fa-w-11" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512"><path fill="currentColor" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path></svg>';
									$html .= '<div style="display:none" class="pwddm_tooltip">' . esc_html( __( 'Address not found.', 'pwddm' ) ) . '</div></div>';
							}
						}
						$html .= '</td>

				</tr>';
						$counter ++;
					}
				} // end while

				if ( false === $manager_has_orders ) {
					$html .= '<tr><td colspan="4">' . esc_html( __( 'There are no orders.', 'pwddm' ) ) . '</td></tr>';
				}

					$html .= '
					</table>
				</div> ';

			} else {
				$html .= '<div class="pwddm_box min pwddm_no_orders"><p>' . esc_html( __( 'There are no orders.', 'pwddm' ) ) . '</p></div>';
			}
			$html .= '
		</div>
	</div>   ';
			return $html;
	}
	/**
	 * Get orders json.
	 *
	 * @since 1.0.0
	 * @param int $manager_id manager user id.
	 * @param int $status order status.
	 */
	public function pwddm_orders_json__premium_only( $manager_id, $status = '' ) {
		$pwddm_manager_drivers = get_user_meta( $manager_id, 'pwddm_manager_drivers', true );
		$array                 = array();

		$params = array(
			'limit'      => -1,
			'status'     => get_option( 'lddfw_out_for_delivery_status', '' ),
			'type'       => 'shop_order',
			'return'     => 'ids',
			'meta_query' => array(
				$array,
			),
			'orderby'    => array(
				'driver_clause' => 'ASC',
			),
		);

		$orders = wc_get_orders( $params );

		$orders_json = '{ "data": [';
		 $counter    = 0;
		if ( ! empty( $orders ) ) {
			foreach ( $orders as $order_id ) {

				$order               = wc_get_order( $order_id );
				$order_date          = $order->get_date_created()->format( lddfw_date_format( 'date' ) );
				$order_status        = $order->get_status();
				$order_status_name   = wc_get_order_status_name( $order_status );
				$billing_address_1   = $order->get_billing_address_1();
				$billing_address_2   = $order->get_billing_address_2();
				$billing_city        = $order->get_billing_city();
				$billing_state       = $order->get_billing_state();
				$billing_postcode    = $order->get_billing_postcode();
				$billing_country     = $order->get_billing_country();
				$billing_first_name  = $order->get_billing_first_name();
				$billing_last_name   = $order->get_billing_last_name();
				$billing_company     = $order->get_billing_company();
				$shipping_company    = $order->get_shipping_company();
				$shipping_first_name = $order->get_shipping_first_name();
				$shipping_last_name  = $order->get_shipping_last_name();
				$shipping_address_1  = $order->get_shipping_address_1();
				$shipping_address_2  = $order->get_shipping_address_2();
				$shipping_city       = $order->get_shipping_city();
				$shipping_state      = $order->get_shipping_state();
				$shipping_postcode   = $order->get_shipping_postcode();
				$shipping_country    = $order->get_shipping_country();

				/**
				 * If shipping info is missing if show the billing info
				 */
				if ( '' === $shipping_first_name && '' === $shipping_address_1 ) {
					$shipping_first_name = $billing_first_name;
					$shipping_last_name  = $billing_last_name;
					$shipping_address_1  = $billing_address_1;
					$shipping_address_2  = $billing_address_2;
					$shipping_city       = $billing_city;
					$shipping_state      = $billing_state;
					$shipping_postcode   = $billing_postcode;
					$shipping_country    = $billing_country;
					$shipping_company    = $billing_company;
				}

				if ( '' !== $shipping_country ) {
					$shipping_country = WC()->countries->countries[ $shipping_country ];
				}
					$array = array(
						'first_name' => $shipping_first_name,
						'last_name'  => $shipping_last_name,
						'company'    => $shipping_company,
						'street_1'   => $shipping_address_1,
						'street_2'   => $shipping_address_2,
						'city'       => $shipping_city,
						'zip'        => $shipping_postcode,
						'country'    => $shipping_country,
						'state'      => $shipping_state,
					);

					// Format address.
					$shipping_address = lddfw_format_address( 'address_line', $array );

					$order_driverid = $order->get_meta( 'lddfw_driverid' );
					$driver         = get_userdata( $order_driverid );

					$driver_name = ( ! empty( $driver ) ) ? $driver->display_name : '';

					$driver_manager_id = '';
					if ( '' !== $order_driverid && '-1' !== $order_driverid ) {
						$driver_manager_id = get_user_meta( $order_driverid, 'pwddm_manager', true );
					}
					if ( ( '' === $order_driverid || '-1' === $order_driverid )
					||
					(
						( ( '0' === $pwddm_manager_drivers || '' === $pwddm_manager_drivers ) && '' === strval( $driver_manager_id ) )
						||
						( '2' === $pwddm_manager_drivers && strval( $driver_manager_id ) === strval( $manager_id ) )
						||
						( '1' === $pwddm_manager_drivers && ( strval( $driver_manager_id ) === strval( $manager_id ) || '' === strval( $driver_manager_id ) ) )
					)
					) {
						if ( $counter > 0 ) {
							$orders_json .= ',';
						}
						$orders_json .= '{ "order": [{ "id" : "' . $order_id . '" , "address" : "' . esc_js( $shipping_address ) . '" }] }';
						$counter++;
					}
			}

			$orders_json .= ' ] }';
			echo $orders_json;
		}
	}
 

	/**
	 * Orders_form_post
	 *
	 * @since 1.0.0
	 * @param int $manager_id manager user id.
	 * @return void
	 */
	public function pwddm_orders_form_post( $manager_id, $type ) {

		if ( isset( $_POST['pwddm_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['pwddm_wpnonce'] ) );
			if ( ! wp_verify_nonce( $nonce, 'pwddm-nonce' ) ) {
				$error = __( 'Security Check Failure - This alert may occur when you are logged in as an administrator and as a delivery driver on the same browser and the same device. If you want to work on both panels please try to work with two different browsers.', 'pwddm' );
			} else {
				$pwddm_action = ( isset( $_POST['pwddm_action'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_action'] ) ) : '';
				if ( '' !== $pwddm_action && isset( $_POST['pwddm_order_id'] ) ) {
					$driver = new LDDFW_Driver();
					foreach ( $_POST['pwddm_order_id'] as $order_id ) {
						$order_id = sanitize_text_field( wp_unslash( $order_id ) );
						$order    = wc_get_order( $order_id );
						// Update order Status.
						if ( 'mark_driver_assigned' === $pwddm_action ) {
							$order->update_status( get_option( 'lddfw_driver_assigned_status' ), __( 'Order status has been changed by manager.', 'pwddm' ) );
						} elseif ( 'mark_out_for_delivery' === $pwddm_action ) {
							$order->update_status( get_option( 'lddfw_out_for_delivery_status' ), __( 'Order status has been changed by manager.', 'pwddm' ) );
						} elseif ( 'mark_failed' === $pwddm_action ) {
							$order->update_status( get_option( 'lddfw_failed_attempt_status' ), __( 'Order status has been changed by manager.', 'pwddm' ) );
						} elseif ( 'mark_delivered' === $pwddm_action ) {
							$order->update_status( get_option( 'lddfw_delivered_status' ), __( 'Order status has been changed by manager.', 'pwddm' ) );
						} elseif ( 'mark_processing' === $pwddm_action ) {
							$order->update_status( get_option( 'lddfw_processing_status', '' ), __( 'Order status has been changed by manager.', 'pwddm' ) );
						} elseif ( 'remove_location_status' === $pwddm_action ) {

							if ( pwddm_fs()->is__premium_only() ) {
								if ( pwddm_fs()->can_use_premium_code() ) {
									$route = new PWDDM_Route();
									$route->delete_order_geocode( $order_id );
								}
							}
						} else {
							if ( '-1' === $pwddm_action ) {
								// Delete drivers.
								$order->delete_meta_data( 'lddfw_driverid' );
								lddfw_update_sync_order( $order_id, 'lddfw_driverid', '0' );
								$order->save();
							} else {
								// Assign a driver to order.
								$driver->assign_delivery_driver( $order_id, $pwddm_action, 'store' );
							}
						}
					}
					if ( 'ajax' !== $type ) {
						header( 'Location: ' . pwddm_manager_page_url( 'pwddm_screen=orders' ) );
					}
				}
			}
		}
	}

	/**
	 * Orders
	 *
	 * @since 1.0.0
	 * @param int $manager_id manager user id.
	 * @return html
	 */
	public function pwddm_orders_page( $manager_id ) {
		global $wpdb;
		// Handle orders form post.
		$this->pwddm_orders_form_post( $manager_id, '' );

		global $pwddm_page;
		$pwddm_manager_drivers = get_user_meta( $manager_id, 'pwddm_manager_drivers', true );
		// Get url params.
		$pwddm_orders_filter = ( isset( $_GET['pwddm_orders_filter'] ) ) ? sanitize_text_field( wp_unslash( $_GET['pwddm_orders_filter'] ) ) : '';
		$pwddm_from_date     = ( isset( $_GET['pwddm_from_date'] ) ) ? sanitize_text_field( wp_unslash( $_GET['pwddm_from_date'] ) ) : '';
		$pwddm_to_date       = ( isset( $_GET['pwddm_to_date'] ) ) ? sanitize_text_field( wp_unslash( $_GET['pwddm_to_date'] ) ) : '';
		$pwddm_orders_status = ( isset( $_GET['pwddm_orders_status'] ) ) ? sanitize_text_field( wp_unslash( $_GET['pwddm_orders_status'] ) ) : '';
		$pwddm_dates         = ( isset( $_GET['pwddm_dates_range'] ) ) ? sanitize_text_field( wp_unslash( $_GET['pwddm_dates_range'] ) ) : '';

		/**
		 * Set current status names
		 */
		$pwddm_driver_assigned_status_name  = esc_html( __( 'Driver assigned', 'pwddm' ) );
		$pwddm_out_for_delivery_status_name = esc_html( __( 'Out for delivery', 'pwddm' ) );
		$pwddm_failed_attempt_status_name   = esc_html( __( 'Failed delivery', 'pwddm' ) );
		$pwddm_processing_status_name       = esc_html( __( 'Processing', 'pwddm' ) );

		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$result = wc_get_order_statuses();
			if ( ! empty( $result ) ) {
				foreach ( $result as $key => $status ) {
					switch ( $key ) {
						case get_option( 'lddfw_out_for_delivery_status' ):
							if ( $status !== $pwddm_out_for_delivery_status_name ) {
								$pwddm_out_for_delivery_status_name = $status;
							}
							break;
						case get_option( 'lddfw_failed_attempt_status' ):
							if ( esc_html( __( 'Failed Delivery Attempt', 'pwddm' ) ) !== $status ) {
								$pwddm_failed_attempt_status_name = $status;
							}
							break;
						case get_option( 'lddfw_driver_assigned_status' ):
							if ( $status !== $pwddm_driver_assigned_status_name ) {
								$pwddm_driver_assigned_status_name = $status;
							}
							break;
					}
				}
			}
		}

		$user_query = PWDDM_Driver::pwddm_get_drivers( $manager_id, 'all' );
		$drivers    = $user_query->get_results();

		$html = '
		<form method="POST" name="pwddm_orders_form" id="pwddm_orders_form" action="' . pwddm_manager_page_url( 'pwddm_screen=orders' ) . '">
		<input type="hidden" name="pwddm_wpnonce" value="' . wp_create_nonce( 'pwddm-nonce' ) . '">
		<div class="row">


		<div class="col-12">
		<!-- Nav tabs -->
		<ul class="nav nav-tabs" id="myTab" role="tablist">
			<li class="nav-item" role="presentation">
				<a class="nav-link active" id="filter-tab" data-bs-toggle="tab" data-bs-target="#filter" type="button" role="tab" aria-controls="filter" aria-selected="true">' . esc_html( __( 'Filter', 'pwddm' ) ) . '</a>
			</li>
			<li class="nav-item" role="presentation">
				<a class="nav-link" id="filter-tab" data-bs-toggle="tab" data-bs-target="#bulk-action" type="button" role="tab" aria-controls="bulk-action" aria-selected="true">' . pwddm_premium_feature( '' ) . esc_html( __( 'Bulk actions', 'pwddm' ) ) . '</a>
		    </li>
		</ul>

		<!-- Tab panes -->
		<div class="tab-content" style="margin-top:10px;">
		 <div class="tab-pane " id="bulk-action" role="tabpanel" aria-labelledby="bulk-action-tab">
		 <div class="row">';

		if ( pwddm_is_free() ) {
			$content = '<div class="col-12 col-md" style="margin-bottom:10px">' .
				   '<p>' . pwddm_premium_feature( '' ) . ' ' . esc_html( __( 'Bulk assign drivers to orders.', 'pwddm' ) ) . '</p>' .
					'<p>' . pwddm_premium_feature( '' ) . ' ' . esc_html( __( 'Bulk update order statuses.', 'pwddm' ) ) . '</p>';
			$html   .= '<div class="container">' . pwddm_premium_feature_notice_content( $content ) . '</div></div>';
		}

		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->can_use_premium_code() ) {

				$html .= '<div class="col-12 col-md" style="margin-bottom:10px"><select name="pwddm_action" class="form-select">
				<option value="">' . esc_html( __( 'Bulk actions', 'pwddm' ) ) . '</option>
				<option value="mark_processing">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . esc_html( __( 'Processing', 'pwddm' ) ) . '</option>
				<option value="mark_driver_assigned">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . $pwddm_driver_assigned_status_name . '</option>
				<option value="mark_out_for_delivery">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . $pwddm_out_for_delivery_status_name . ' </option>
				<option value="mark_failed">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . $pwddm_failed_attempt_status_name . '</option>
				<option value="mark_delivered">' . esc_html( __( 'Change status to', 'pwddm' ) ) . ' ' . esc_html( __( 'Delivered', 'pwddm' ) ) . '</option>
				<option value="-1">' . esc_html( __( 'Unassign driver', 'pwddm' ) ) . '</option>';

				$last_availability = '';
				foreach ( $drivers as $driver ) {
					$driver_manager_id = get_user_meta( $driver->ID, 'pwddm_manager', true );
					if (
						( ( '0' === $pwddm_manager_drivers || '' === strval( $pwddm_manager_drivers ) ) && '' === strval( $driver_manager_id ) )
						||
						( '2' === $pwddm_manager_drivers && strval( $driver_manager_id ) === strval( $manager_id ) )
						||
						( '1' === $pwddm_manager_drivers && ( strval( $driver_manager_id ) === strval( $manager_id ) || '' === strval( $driver_manager_id ) ) )
					) {
						$driver_name    = $driver->display_name;
						$availability   = get_user_meta( $driver->ID, 'lddfw_driver_availability', true );
						$driver_account = get_user_meta( $driver->ID, 'lddfw_driver_account', true );
						$availability   = '1' === $availability ? 'Available' : 'Unavailable';
						$selected       = '';

						if ( $last_availability !== $availability ) {
							if ( '' !== $last_availability ) {
								$html .= '</optgroup>';
							}
							$html             .= '<optgroup label="' . esc_attr( $availability . ' ' . __( 'drivers', 'pwddm' ) ) . '">';
							$last_availability = $availability;
						}
						if ( '1' === $driver_account ) {
							$html .= '<option value="' . esc_attr( $driver->ID ) . '">' . esc_html( __( 'Assign to', 'pwddm' ) ) . ' ' . esc_html( $driver_name ) . '</option>';
						}
					}
				}
				$html .= '</optgroup>';

				$html .= '
			</select>
		</div>
		<div class="col-12 col-md-2" style="margin-bottom:10px">
			<button class="btn btn-primary btn-block" type="submit">' . esc_html( __( 'Apply', 'pwddm' ) ) . '</button>
		</div>';
			}
		}
		$html         .= '</div>
</div>
<div class="tab-pane active" id="filter" role="tabpanel" aria-labelledby="filter-tab">
<div class="row">
		<div class="col-12 col-md" style="margin-bottom:10px">
			<select class="form-select" id="pwddm_orders_status" name="pwddm_orders_status">
				<option value="">' . esc_attr( __( 'All Statuses', 'pwddm' ) ) . '</option>
				<option ' . selected( get_option( 'lddfw_processing_status', '' ), $pwddm_orders_status, false ) . ' value="' . get_option( 'lddfw_processing_status', '' ) . '">' . $pwddm_processing_status_name . '</option>
				<option ' . selected( get_option( 'lddfw_driver_assigned_status', '' ), $pwddm_orders_status, false ) . ' value="' . get_option( 'lddfw_driver_assigned_status', '' ) . '">' . $pwddm_driver_assigned_status_name . '</option>
				<option ' . selected( get_option( 'lddfw_out_for_delivery_status', '' ), $pwddm_orders_status, false ) . ' value="' . get_option( 'lddfw_out_for_delivery_status', '' ) . '"> ' . $pwddm_out_for_delivery_status_name . ' </option>
				<option ' . selected( get_option( 'lddfw_failed_attempt_status', '' ), $pwddm_orders_status, false ) . ' value="' . get_option( 'lddfw_failed_attempt_status', '' ) . '">' . $pwddm_failed_attempt_status_name . '</option>
				<option ' . selected( get_option( 'lddfw_delivered_status', '' ), $pwddm_orders_status, false ) . ' value="' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '">' . esc_html( __( 'Delivered', 'pwddm' ) ) . '</option>
			</select>
		</div>';
		$date_style    = get_option( 'lddfw_delivered_status', '' ) === $pwddm_orders_status ? '' : 'display:none';
		$html         .= '<div class="col-12 col-md pwddm_dates_range_col"   style="' . $date_style . ';margin-bottom:10px">
			<select class="form-select" id="pwddm_dates_range" name="pwddm_dates_range" >
				<option value="">' . esc_attr( __( 'All Dates', 'pwddm' ) ) . '</option>	
				<option ' . selected( date_i18n( 'Y-m-d' ) . ',' . date_i18n( 'Y-m-d' ), $pwddm_dates, false ) . ' value="' . date_i18n( 'Y-m-d' ) . ',' . date_i18n( 'Y-m-d' ) . '">' . esc_html( __( 'Today', 'pwddm' ) ) . '</option>
				<option ' . selected( date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ), $pwddm_dates, false ) . ' value="' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . '">' . esc_html( __( 'Yesterday', 'pwddm' ) ) . '</option>
				<option ' . selected( date_i18n( 'Y-m-d', strtotime( 'first day of this month' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( 'last day of this month' ) ), $pwddm_dates, false ) . ' value="' . date_i18n( 'Y-m-d', strtotime( 'first day of this month' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( 'last day of this month' ) ) . '">' . esc_html( __( 'This month', 'pwddm' ) ) . '</option>
				<option ' . selected( date_i18n( 'Y-m-d', strtotime( 'first day of last month' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( 'last day of last month' ) ), $pwddm_dates, false ) . ' value="' . date_i18n( 'Y-m-d', strtotime( 'first day of last month' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( 'last day of last month' ) ) . '">' . esc_html( __( 'Last month', 'pwddm' ) ) . '</option>
			</select>
		</div>

		<div class="col-12 col-md" style="margin-bottom:10px">
			<select name="pwddm_orders_filter"  id="pwddm_orders_filter" class="form-select">
				<option value="">' . __( 'Filter By', 'pwddm' ) . '</option>
				<option ' . selected( '-1', $pwddm_orders_filter, false ) . ' value="-1" ';
				$html .= '-1' === $pwddm_orders_filter ? 'selected' : '';
				$html .= '>' . __( 'With drivers', 'pwddm' ) . '</option>
				<option ' . selected( '-2', $pwddm_orders_filter, false ) . ' value="-2" ';
				$html .= '-2' === $pwddm_orders_filter ? 'selected' : '';
				$html .= '>' . __( 'Without drivers', 'pwddm' ) . '</option>
				';
				$html .= '<optgroup label="' . esc_attr( __( 'Drivers', 'pwddm' ) ) . '"></optgroup>';

		foreach ( $drivers as $driver ) {
			$driver_manager_id = get_user_meta( $driver->ID, 'pwddm_manager', true );
			if (
				( ( '0' === $pwddm_manager_drivers || '' === strval( $pwddm_manager_drivers ) ) && '' === strval( $driver_manager_id ) )
				||
				( '2' === $pwddm_manager_drivers && strval( $driver_manager_id ) === strval( $manager_id ) )
				||
				( '1' === $pwddm_manager_drivers && ( strval( $driver_manager_id ) === strval( $manager_id ) || '' === strval( $driver_manager_id ) ) )
			) {

				$driver_name = $driver->display_name;
				$selected    = ( strval( $driver->ID ) === $pwddm_orders_filter ) ? 'selected' : '';
				$html       .= '<option ' . esc_attr( $selected ) . ' value="' . esc_attr( $driver->ID ) . '">' . esc_html( $driver_name ) . '</option>';
			}
		}
				$html .= '
			</select>
		</div>';

		$html .= '<div class="col-12 col-md-2"><button class="btn btn-block btn-primary" name="pwddm_orders_filter_btn" id="pwddm_orders_filter_btn" type="submit">' . esc_html( __( 'Filter', 'pwddm' ) ) . '</button></div>';
		$html .= '</div></div></div><div class="row"><div class="pwddm_date_range col-12">';

		if ( '' === $pwddm_dates ) {
			$html     .= date_i18n( lddfw_date_format( 'date' ) );
			$from_date = date_i18n( 'Y-m-d' );
			$to_date   = date_i18n( 'Y-m-d' );
		} else {
			$pwddm_dates_array = explode( ',', $pwddm_dates );
			if ( 1 < count( $pwddm_dates_array ) ) {
				if ( $pwddm_dates_array[0] === $pwddm_dates_array[1] ) {
					$html     .= date_i18n( lddfw_date_format( 'date' ), strtotime( $pwddm_dates_array[0] ) );
					$from_date = date_i18n( 'Y-m-d', strtotime( $pwddm_dates_array[0] ) );
					$to_date   = date_i18n( 'Y-m-d', strtotime( $pwddm_dates_array[0] ) );
				} else {
					$html     .= date_i18n( lddfw_date_format( 'date' ), strtotime( $pwddm_dates_array[0] ) ) . ' - ' . date_i18n( lddfw_date_format( 'date' ), strtotime( $pwddm_dates_array[1] ) );
					$from_date = date_i18n( 'Y-m-d', strtotime( $pwddm_dates_array[0] ) );
					$to_date   = date_i18n( 'Y-m-d', strtotime( $pwddm_dates_array[1] ) );
				}
			} else {
				$html     .= date_i18n( lddfw_date_format( 'date' ), strtotime( $pwddm_dates_array[0] ) );
				$from_date = date_i18n( 'Y-m-d', strtotime( $pwddm_dates_array[0] ) );
				$to_date   = date_i18n( 'Y-m-d', strtotime( $pwddm_dates_array[0] ) );
			}
		}
		$html .= '</div>';

			// Orders query.
			$orders_per_page = 50;
			$counter         = $pwddm_page > 1 ? $orders_per_page * ( $pwddm_page ) - $orders_per_page + 1 : 1;

			// Status.
		if ( '' !== $pwddm_orders_status ) {
			$status_array = array( $pwddm_orders_status );
		} else {
			$status_array = array(
				get_option( 'lddfw_driver_assigned_status', '' ),
				get_option( 'lddfw_out_for_delivery_status', '' ),
				get_option( 'lddfw_failed_attempt_status', '' ),
				get_option( 'lddfw_delivered_status', '' ),
				get_option( 'lddfw_processing_status', '' ),
			);
		}

			// Filter by orders without drivers.
			$no_driver_array = array();
		if ( '-2' === $pwddm_orders_filter ) {
			$no_driver_array = array(
				'relation' => 'or',
				array(
					'key'     => 'lddfw_driverid',
					'value'   => '-1',
					'compare' => '=',
				),
				array(
					'key'     => 'lddfw_driverid',
					'value'   => '',
					'compare' => '=',
				),
				array(
					'key'     => 'lddfw_driverid',
					'compare' => 'NOT EXISTS',
				),
			);
		}

			// Filter by orders without drivers.
			$filter_array = array();
		if ( '-1' === $pwddm_orders_filter ) {
			$filter_array = array(
				'relation' => 'and',
				array(
					'key'     => 'lddfw_driverid',
					'value'   => '-1',
					'compare' => '!=',
				),
				array(
					'key'     => 'lddfw_driverid',
					'compare' => 'EXISTS',
				),
			);
		}

			// Filter by driver id.
			$driver_array = array();
		if ( 0 < intval( $pwddm_orders_filter ) ) {
			$driver_array = array(
				'key'     => 'lddfw_driverid',
				'value'   => $pwddm_orders_filter,
				'compare' => '=',
			);
		}

			$date_array = array();
			// Filter for delivered date range.
		if ( '' !== $from_date && '' !== $to_date && '' !== $pwddm_dates ) {
			$date_array = array(
				'relation' => 'and',
				array(
					'key'     => 'lddfw_delivered_date',
					'value'   => $from_date,
					'compare' => '>=',
					'type'    => 'DATE',
				),
				array(
					'key'     => 'lddfw_delivered_date',
					'value'   => $to_date,
					'compare' => '<=',
					'type'    => 'DATE',
				),
			);
		}

			$params = array(
				'posts_per_page' => $orders_per_page,
				'post_status'    => $status_array,
				'post_type'      => 'shop_order',
				'paginate'       => true,
				'return'         => 'ids',
				'paged'          => $pwddm_page,
				'orderby'        => array(
					'ID' => 'DESC',
				),
			);

			$params['meta_query'] = array(
				'relation' => 'AND',
				$date_array,
				$driver_array,
				$filter_array,
				$no_driver_array,
			);

			if ( pwddm_is_hpos_enabled() ) {
				$orders       = wc_get_orders( $params );
				$orders_array = $orders->orders;
			} else {
				$orders       = new WP_Query( $params );
				$orders_array = array_map(
					function ( $order ) {
						return $order->ID;
					},
					$orders->posts
				);
			}

			$date_format = lddfw_date_format( 'date' );
			$time_format = lddfw_date_format( 'time' );

			$html .= '
			</div>

		<div class="row">
		<div class="col-12">';

			if ( $orders ) {

				// Pagination.
				$base       = pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=' . $pwddm_orders_filter . '&pwddm_orders_status=' . $pwddm_orders_status . '&pwddm_from_date=' . $pwddm_from_date . '&pwddm_to_date=' . $pwddm_to_date ) . '&pwddm_page=%#%';
				$pagination = paginate_links(
					array(
						'base'         => $base,
						'total'        => $orders->max_num_pages,
						'current'      => $pwddm_page,
						'format'       => '&pwddm_page=%#%',
						'show_all'     => false,
						'type'         => 'array',
						'end_size'     => 2,
						'mid_size'     => 0,
						'prev_next'    => true,
						'prev_text'    => sprintf( '<i></i> %1$s', __( '<<', 'pwddm' ) ),
						'next_text'    => sprintf( '%1$s <i></i>', __( '>>', 'pwddm' ) ),
						'add_args'     => false,
						'add_fragment' => '',
					)
				);

				if ( ! empty( $pagination ) ) {
					$html .= '<div class="pagination text-sm-center"><nav aria-label="Page navigation" style="width:100%"><ul class="pagination justify-content-center">';
					foreach ( $pagination as $page ) {
						$html .= "<li class='page-item ";
						if ( strpos( $page, 'current' ) !== false ) {
							$html .= ' active';
						}
						$html .= "'> " . str_replace( 'page-numbers', 'page-link', $page ) . '</li>';
					}
					$html .= '</nav></div>';
				}

				$html .= '	<div class="table-responsive"><table class="table table-striped table-hover">
				<thead class="table-dark">
					<tr>
						<th scope="row">
							<div class="form-check custom-checkbox">
								<input value="" id="pwddm_multi_checkbox" type="checkbox" data="order_checkbox" class="form-check-input" >
								<label class="custom-control-label" for="pwddm_multi_checkbox"></label>
							</div>
						</th>
					 
						<th scope="col">' . esc_html( __( 'Order', 'pwddm' ) ) . '</th>
						<th scope="col">' . esc_html( __( 'Date', 'pwddm' ) ) . '</th>
						<th scope="col">' . esc_html( __( 'Customer', 'pwddm' ) ) . '</th>
						<th scope="col">' . esc_html( __( 'Shipping address', 'pwddm' ) ) . '</th>
						<th scope="col">' . esc_html( __( 'Status', 'pwddm' ) ) . '</th>
						<th scope="col">' . esc_html( __( 'Driver', 'pwddm' ) ) . '</th>
					</tr>
				</thead>
				<tbody>';

				// Results.
				foreach ( $orders_array as $order_id ) {

					$order               = wc_get_order( $order_id );
					$order_number        = $order->get_order_number();
					$order_date          = $order->get_date_created()->format( lddfw_date_format( 'date' ) );
					$order_status        = $order->get_status();
					$order_status_name   = wc_get_order_status_name( $order_status );
					$billing_address_1   = $order->get_billing_address_1();
					$billing_address_2   = $order->get_billing_address_2();
					$billing_city        = $order->get_billing_city();
					$billing_state       = $order->get_billing_state();
					$billing_postcode    = $order->get_billing_postcode();
					$billing_country     = $order->get_billing_country();
					$billing_first_name  = $order->get_billing_first_name();
					$billing_last_name   = $order->get_billing_last_name();
					$billing_company     = $order->get_billing_company();
					$shipping_company    = $order->get_shipping_company();
					$shipping_first_name = $order->get_shipping_first_name();
					$shipping_last_name  = $order->get_shipping_last_name();
					$shipping_address_1  = $order->get_shipping_address_1();
					$shipping_address_2  = $order->get_shipping_address_2();
					$shipping_city       = $order->get_shipping_city();
					$shipping_state      = $order->get_shipping_state();
					$shipping_postcode   = $order->get_shipping_postcode();
					$shipping_country    = $order->get_shipping_country();

					/**
					 * If shipping info is missing if show the billing info
					 */
					if ( '' === $shipping_first_name && '' === $shipping_address_1 ) {
						$shipping_first_name = $billing_first_name;
						$shipping_last_name  = $billing_last_name;
						$shipping_address_1  = $billing_address_1;
						$shipping_address_2  = $billing_address_2;
						$shipping_city       = $billing_city;
						$shipping_state      = $billing_state;
						$shipping_postcode   = $billing_postcode;
						$shipping_country    = $billing_country;
						$shipping_company    = $billing_company;
					}

					if ( '' !== $shipping_country ) {
						$shipping_country = WC()->countries->countries[ $shipping_country ];
					}
						$array = array(
							'first_name' => $shipping_first_name,
							'last_name'  => $shipping_last_name,
							'company'    => $shipping_company,
							'street_1'   => $shipping_address_1,
							'street_2'   => $shipping_address_2,
							'city'       => $shipping_city,
							'zip'        => $shipping_postcode,
							'country'    => $shipping_country,
							'state'      => $shipping_state,
						);

						// Format address.
						$shipping_address = $shipping_first_name . ' ' . $shipping_last_name . ', ' . lddfw_format_address( 'address_line', $array );

						$order_driverid = $order->get_meta( 'lddfw_driverid' );
						$driver         = get_userdata( $order_driverid );

						$driver_name = ( ! empty( $driver ) ) ? $driver->display_name : '';

						$driver_manager_id = '';
						if ( '' !== $order_driverid && '-1' !== $order_driverid ) {
							$driver_manager_id = get_user_meta( $order_driverid, 'pwddm_manager', true );
						}
						if ( ( '' === $order_driverid || '-1' === $order_driverid )
						||
						(
							( ( '0' === $pwddm_manager_drivers || '' === $pwddm_manager_drivers ) && '' === strval( $driver_manager_id ) )
							||
							( '2' === $pwddm_manager_drivers && strval( $driver_manager_id ) === strval( $manager_id ) )
							||
							( '1' === $pwddm_manager_drivers && ( strval( $driver_manager_id ) === strval( $manager_id ) || '' === strval( $driver_manager_id ) ) )
						)
						) {

							$html .= '
				<tr>
				<th scope="row">
					<div class="custom-control custom-checkbox">
						<input name="pwddm_order_id[]" value="' . $order_id . '" id="pwddm_order_id_' . $counter . '" type="checkbox" class="order_checkbox form-check-input" order_number="' . $order_number . '" >
						<label class="custom-control-label" for="pwddm_order_id_' . $counter . '"></label>
					</div>
				</th>
				<td><a target="_blank" href="' . esc_attr( pwddm_manager_page_url( 'pwddm_screen=order&pwddm_orderid=' . $order_id ) ) . '" >' . esc_html( $order->get_order_number() ) . '</a></td>
				<td>' . esc_html( $order_date ) . '</td>
				<td>' . $billing_first_name . ' ' . $billing_last_name . '</td>
				<td>' . $shipping_address . '</td>
				<td>' . esc_html( $order_status_name ) . '</td>
				<td>' . $driver_name . '</td>
			  </tr>';
							$counter ++;
						}
				}

				$html .= '</table></div></form>';

				if ( ! empty( $pagination ) ) {
					$html .= '<div class="pagination text-sm-center"><nav aria-label="Page navigation" style="width:100%"><ul class="pagination justify-content-center">';
					foreach ( $pagination as $page ) {
						$html .= "<li class='page-item ";
						if ( strpos( $page, 'current' ) !== false ) {
							$html .= ' active';
						}
						$html .= "'> " . str_replace( 'page-numbers', 'page-link', $page ) . '</li>';
					}
					$html .= '</nav></div>';

				}
			} else {
				$html .= '<div class="pwddm_box min pwddm_no_orders"><p>' . esc_html( __( 'There are no orders.', 'pwddm' ) ) . '</p></div>';
			}
			$html .= '</div></div>   ';
			return $html;
	}
}
