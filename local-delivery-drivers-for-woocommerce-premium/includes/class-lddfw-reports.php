<?php
/**
 * Plugin Reports.
 *
 * All the screens functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */

/**
 * Plugin Reports.
 *
 * All the Reports functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Reports {
	/**
	 * Drivers status orders.
	 *
	 * @param int    $driver_id driver user id.
	 * @param string $status status.
	 * @param array  $array array.
	 * @since 1.1.0
	 * @return html
	 */
	public function driver_status_orders( $driver_id, $status, $array ) {
		$orders = 0;
		foreach ( $array as $row ) {
			if ( '' === $driver_id ) {

				if ( $row->post_status === $status ) {
					$orders = $row->orders;
					break;
				}
			} else {
				if ( $row->post_status === $status && $driver_id === $row->driver_id ) {
					$orders = $row->orders;
					break;
				}
			}
		}
		return $orders;
	}

	/**
	 * Drivers orders dashboard report.
	 *
	 * @since 1.1.0
	 */
	public function claim_orders_dashboard_report() {
		$orders       = new LDDFW_Orders();
		$report_array = $orders->lddfw_claim_orders_dashboard_report_query();
		echo '<h2>' . esc_html( __( 'Orders without drivers', 'lddfw' ) ) . '</h2>
		<table class="wp-list-table widefat fixed striped table-view-list posts">
		<thead>
			<tr>
				<th class="manage-column column-primary ">' . esc_html( __( 'Ready for claim', 'lddfw' ) ) . '</td>
				<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Driver assigned', 'lddfw' ) ) . '</td>
				<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Out for delivery', 'lddfw' ) ) . '</td>
				<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Delivered today', 'lddfw' ) ) . '</td>
				<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Failed delivery', 'lddfw' ) ) . '</td>
				<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Total', 'lddfw' ) ) . '</td>
			</tr>
		</thead>
		<tbody>';

		$lddfw_driver_assigned_status  = get_option( 'lddfw_driver_assigned_status', '' );
		$lddfw_out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
		$lddfw_failed_attempt_status   = get_option( 'lddfw_failed_attempt_status', '' );
		$lddfw_delivered_status        = get_option( 'lddfw_delivered_status', '' );

		// Get claim orders.
		$processing_status = '';
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$processing_status = $orders->lddfw_claim_orders_counts__premium_only();
			}
		}

		if ( empty( $report_array ) && empty( $processing_status ) ) {
			echo '
		<tr>
			<td colspan="6" class="lddfw-text-center">' . esc_html( __( 'No orders', 'lddfw' ) ) . '</td>
		</tr>';
		} else {

				$out_for_delivery_orders = 0;
				$driver_assigned_orders  = 0;
				$failed_attempt_orders   = 0;
				$delivered_orders        = 0;
				$total                   = 0;

			if ( lddfw_fs()->is__premium_only() ) {
				if ( lddfw_fs()->can_use_premium_code() ) {
					if ( ! empty( $report_array ) ) {
						$out_for_delivery_orders = $this->driver_status_orders( '', $lddfw_out_for_delivery_status, $report_array );
						$driver_assigned_orders  = $this->driver_status_orders( '', $lddfw_driver_assigned_status, $report_array );
						$failed_attempt_orders   = $this->driver_status_orders( '', $lddfw_failed_attempt_status, $report_array );
						$delivered_orders        = $this->driver_status_orders( '', $lddfw_delivered_status, $report_array );
					}
					$total = $processing_status + $out_for_delivery_orders + $driver_assigned_orders + $failed_attempt_orders + $delivered_orders;
				}
			}

			echo '
				<tr>
					<td class="title column-title has-row-actions column-primary" data-colname="' . esc_html( __( 'Ready for claim', 'lddfw' ) ) . '" >
					<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
					' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_processing_status' ) ) . '&post_type=shop_order&lddfw_orders_filter=-2">' . $processing_status . '</a>' ) . '
					</td>
					<td data-colname="' . esc_html( __( 'Driver assigned', 'lddfw' ) ) . '" class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_driver_assigned_status' ) ) . '&post_type=shop_order&lddfw_orders_filter=-2">' . $driver_assigned_orders . '</a>' ) . '</td>
					<td data-colname="' . esc_html( __( 'Out for delivery', 'lddfw' ) ) . '" class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_out_for_delivery_status' ) ) . '&post_type=shop_order&lddfw_orders_filter=-2">' . $out_for_delivery_orders . '</a>' ) . '</td>
					<td data-colname="' . esc_html( __( 'Delivered today', 'lddfw' ) ) . '" class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '&lddfw_to_date=' . date_i18n( 'Y-m-d' ) . '&lddfw_from_date=' . date_i18n( 'Y-m-d' ) . '&post_type=shop_order&lddfw_orders_filter=-2">' . $delivered_orders . '</a>' ) . '</td>
					<td data-colname="' . esc_html( __( 'Failed delivery', 'lddfw' ) ) . '" class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_failed_attempt_status' ) ) . '&post_type=shop_order&lddfw_orders_filter=-2">' . $failed_attempt_orders . '</a>' ) . '</td>
					<td data-colname="' . esc_html( __( 'Total', 'lddfw' ) ) . '" class="lddfw-text-center">' . lddfw_admin_premium_feature( $total ) . '</td>
				</tr>';
			echo '</tbody>';
		}
		echo '</table>';
	}

	/**
	 * Drivers orders dashboard report.
	 *
	 * @since 1.1.0
	 */
	public function drivers_orders_dashboard_report() {
		$orders       = new LDDFW_Orders();
		$report_array = $orders->lddfw_drivers_orders_dashboard_report_query();

		echo '<h2>' . esc_html( __( 'Drivers orders', 'lddfw' ) ) . '</h2>
	<table class="wp-list-table widefat fixed striped table-view-list posts">
	<thead>
		<tr>
			<th class="manage-column column-primary ">' . esc_html( __( 'Drivers', 'lddfw' ) ) . '</td>
			<th class="manage-column column-primary lddfw-text-center ">' . esc_html( __( 'Phone', 'lddfw' ) ) . '</td>
			<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Driver assigned', 'lddfw' ) ) . '</td>
			<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Out for delivery', 'lddfw' ) ) . '</td>
			<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Delivered today', 'lddfw' ) ) . '</td>
			<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Failed delivery', 'lddfw' ) ) . '</td>
			<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Total', 'lddfw' ) ) . '</td>
		</tr>
	</thead>
	<tbody>';

		$lddfw_driver_assigned_status  = get_option( 'lddfw_driver_assigned_status', '' );
		$lddfw_out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
		$lddfw_failed_attempt_status   = get_option( 'lddfw_failed_attempt_status', '' );
		$lddfw_delivered_status        = get_option( 'lddfw_delivered_status', '' );

		$last_driver                   = '';
		$out_for_delivery_orders_total = 0;
		$driver_assigned_orders_total  = 0;
		$failed_attempt_orders_total   = 0;
		$delivered_orders_total        = 0;
		$total                         = 0;
		$driver_counter                = 0;
		$sub_total                     = 0;
		if ( empty( $report_array ) ) {
			echo '
		<tr>
			<td colspan="7" class="lddfw-text-center">' . esc_html( __( 'No orders', 'lddfw' ) ) . '</td>
		</tr>';
		} else {

			foreach ( $report_array as $row ) {
				$driver_id = $row->driver_id;
				if ( $last_driver !== $driver_id ) {

					++$driver_counter;

					$out_for_delivery_orders = '';
					$driver_assigned_orders  = '';
					$failed_attempt_orders   = '';
					$delivered_orders        = '';

					if ( lddfw_fs()->is__premium_only() ) {
						if ( lddfw_fs()->can_use_premium_code() ) {

							$out_for_delivery_orders = $this->driver_status_orders( $driver_id, $lddfw_out_for_delivery_status, $report_array );
							$driver_assigned_orders  = $this->driver_status_orders( $driver_id, $lddfw_driver_assigned_status, $report_array );
							$failed_attempt_orders   = $this->driver_status_orders( $driver_id, $lddfw_failed_attempt_status, $report_array );
							$delivered_orders        = $this->driver_status_orders( $driver_id, $lddfw_delivered_status, $report_array );

							$sub_total                      = $out_for_delivery_orders + $driver_assigned_orders + $failed_attempt_orders + $delivered_orders;
							$total                         += $sub_total;
							$out_for_delivery_orders_total += $out_for_delivery_orders;
							$driver_assigned_orders_total  += $driver_assigned_orders;
							$failed_attempt_orders_total   += $failed_attempt_orders;
							$delivered_orders_total        += $delivered_orders;

						}
					}

					$phone = get_user_meta( $driver_id, 'billing_phone', true );

					$last_driver = $driver_id;
					echo '
				<tr>
					<td class="title column-title has-row-actions column-primary" data-colname="' . esc_html( __( 'Driver', 'lddfw' ) ) . '" >
						<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
						' . $row->driver_name . '
					</td>
				 	<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Phone', 'lddfw' ) ) . '"><a href="tel:' . $phone . '">' . $phone . '</a></td>
					<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Driver assigned', 'lddfw' ) ) . '">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_driver_assigned_status' ) ) . '&post_type=shop_order&lddfw_orders_filter=' . esc_attr( $driver_id ) . '">' . $driver_assigned_orders . '</a>' ) . '</td>
					<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Out for delivery', 'lddfw' ) ) . '">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_out_for_delivery_status' ) ) . '&post_type=shop_order&lddfw_orders_filter=' . esc_attr( $driver_id ) . '">' . $out_for_delivery_orders . '</a>' ) . '</td>
					<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Delivered today', 'lddfw' ) ) . '">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '&lddfw_to_date=' . date_i18n( 'Y-m-d' ) . '&lddfw_from_date=' . date_i18n( 'Y-m-d' ) . '&post_type=shop_order&lddfw_orders_filter=' . esc_attr( $driver_id ) . '">' . $delivered_orders . '</a>' ) . '</td>
					<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Failed delivery', 'lddfw' ) ) . '">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_failed_attempt_status' ) ) . '&post_type=shop_order&lddfw_orders_filter=' . esc_attr( $driver_id ) . '">' . $failed_attempt_orders . '</a>' ) . '</td>
					<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Total', 'lddfw' ) ) . '">' . lddfw_admin_premium_feature( $sub_total ) . '</td>
				</tr>';
				}
			}
		}
		echo '</tbody>
		<tfoot>
			<td class="title column-title has-row-actions column-primary">' . $driver_counter . ' ';
		if ( 1 < $driver_counter ) {
			echo esc_html( __( 'Drivers', 'lddfw' ) );
		} else {
			echo esc_html( __( 'Driver', 'lddfw' ) );
		}
			echo '</td>
			<td class="lddfw-text-center"> </td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_driver_assigned_status' ) ) . '&post_type=shop_order&lddfw_orders_filter=-1">' . $driver_assigned_orders_total . '</a>' ) . '</td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_out_for_delivery_status' ) ) . '&post_type=shop_order&lddfw_orders_filter=-1">' . $out_for_delivery_orders_total . '</a>' ) . '</td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '&lddfw_to_date=' . date_i18n( 'Y-m-d' ) . '&lddfw_from_date=' . date_i18n( 'Y-m-d' ) . '&post_type=shop_order&lddfw_orders_filter=-1">' . $delivered_orders_total . '</a>' ) . '</td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_failed_attempt_status' ) ) . '&post_type=shop_order&lddfw_orders_filter=-1">' . $failed_attempt_orders_total . '</a>' ) . '</td>
			<td class="lddfw-text-center">' . lddfw_admin_premium_feature( $total ) . '</td>
		</tfoot>
	</table>';
	}

	/**
	 * Drivers refund query.
	 *
	 * @param date $fromdate fromdate.
	 * @param date $todate todate.
	 * @param int  $driver_id driver user id.
	 * @deprecated 1.7.5
	 * @since 1.1.2
	 * @return html
	 */
	public function lddfw_drivers_refund_query( $fromdate, $todate, $driver_id = '' ) {
		global $wpdb;

		$driver_query = '';
		if ( '' !== $driver_id ) {
			$driver_query = $wpdb->prepare( 'pm.meta_value = %s and', array( $driver_id ) );
		}

		if ( lddfw_is_hpos_enabled() ) {
			// For HPOS-enabled environments.
			$query = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT om.meta_value as driver_id,
					COALESCE(SUM(omr.meta_value),0) as refund
					FROM ' . $wpdb->prefix . 'wc_orders as o
					INNER JOIN ' . $wpdb->prefix . 'wc_orders_meta om ON o.id = om.order_id AND om.meta_key = \'lddfw_driverid\'
					INNER JOIN ' . $wpdb->prefix . 'wc_orders_meta om1 ON o.id = om1.order_id AND om1.meta_key = \'lddfw_delivered_date\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders o2 ON o.id = o2.parent_order_id
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta omr ON o2.id = omr.order_id AND omr.meta_key = \'_refund_amount\'
					WHERE ' . $driver_query . ' o.status = %s AND CAST(om1.meta_value AS DATE) >= %s AND CAST(om1.meta_value AS DATE) <= %s
					GROUP BY om.meta_value
					ORDER BY om.meta_value',
					array(
						get_option( 'lddfw_delivered_status', '' ),
						$fromdate,
						$todate,
					)
				)
			);
		} else {
			// For non-HPOS environments.

			$query = $wpdb->get_results(
				$wpdb->prepare(
					'select pm.meta_value as driver_id,
				COALESCE(SUM( pm5.meta_value ),0) as refund  
				from ' . $wpdb->prefix . 'posts p
				inner join ' . $wpdb->prefix . 'postmeta pm on p.id=pm.post_id and pm.meta_key = \'lddfw_driverid\'
				inner join ' . $wpdb->prefix . 'postmeta pm1 on p.id=pm1.post_id and pm1.meta_key = \'lddfw_delivered_date\'
				left join ' . $wpdb->prefix . 'posts p2 on p.id=p2.post_parent
				left join ' . $wpdb->prefix . 'postmeta pm5 on p2.id=pm5.post_id and pm5.meta_key = \'_refund_amount\'
				where ' . $driver_query . ' p.post_type=\'shop_order\' and
				( p.post_status = %s and CAST( pm1.meta_value AS DATE ) >= %s and CAST( pm1.meta_value AS DATE ) <= %s )
				group by pm.meta_value
				order by pm.meta_value ',
					array(
						get_option( 'lddfw_delivered_status', '' ),
						$fromdate,
						$todate,
					)
				)
			); // db call ok; no-cache ok.
		}
		return $query;
	}

	/**
	 * Drivers commissions query.
	 *
	 * @param date $fromdate fromdate.
	 * @param date $todate todate.
	 * @param int  $driver_id driver user id.
	 * @since 1.1.2
	 * @return html
	 */
	public function lddfw_drivers_commission_query( $fromdate, $todate, $driver_id = '' ) {
		global $wpdb;

		$driver_query = '';
		if ( '' !== $driver_id ) {
			$driver_query = $wpdb->prepare( ' AND driver_id = %d ', array( $driver_id ) );
		}

		if ( lddfw_is_hpos_enabled() ) {
			// Query for HPOS-enabled environments.
			$query = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT 
						o.driver_id,
						COALESCE(SUM(o.driver_commission),0) as commission,
						COUNT(o.order_id) as orders,
						COALESCE(SUM(o.order_total - o.order_refund_amount),0) as orders_total,
						COALESCE(SUM(o.order_shipping_amount),0) as shipping_total
					FROM ' . $wpdb->prefix . 'wc_orders wo
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					WHERE wo.status = %s
					' . $driver_query . '
					AND CAST(o.delivered_date AS DATE) BETWEEN %s AND %s
					GROUP BY o.driver_id
					ORDER BY o.driver_id',
					array( get_option( 'lddfw_delivered_status', '' ), $fromdate, $todate )
				)
			);
		} else {
			// For non-HPOS environments.

			$query = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT 
				 driver_id,
				COALESCE(SUM( driver_commission ),0) as commission ,
				count(p.ID) as orders,
				COALESCE(SUM( order_total - order_refund_amount ),0)  as orders_total ,
				COALESCE(SUM( order_shipping_amount   ),0) as shipping_total
			 	FROM ' . $wpdb->prefix . 'posts p INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o
				ON p.ID = o.order_id
				WHERE
				p.post_type = \'shop_order\'
				AND p.post_status = %s
				' . $driver_query . '
				AND CAST(delivered_date AS DATE) BETWEEN %s AND %s
				GROUP BY driver_id
				ORDER BY driver_id
			    ',
					array( get_option( 'lddfw_delivered_status', '' ), $fromdate, $todate )
				)
			); // db call ok; no-cache ok.
		}
		return $query;
	}

	/**
	 * Drivers commissions query.
	 *
	 * @param date $fromdate fromdate.
	 * @param date $todate todate.
	 * @param int  $driver_id driver user id.
	 * @since 1.1.2
	 * @return html
	 */
	public function payment_methods_query( $fromdate, $todate, $driver_id = '' ) {
		global $wpdb;

		$driver_query = '';
		if ( '' !== $driver_id ) {
			$driver_query = $wpdb->prepare( ' AND driver_id = %d ', array( $driver_id ) );
		}

		if ( lddfw_is_hpos_enabled() ) {
			// Adjusted query for HPOS-enabled environments.
			$query = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT 
						o.driver_id,
						IFNULL(om2.meta_value, \'\') AS order_payment_method,
						COALESCE(SUM(om1.meta_value - IFNULL(om3.meta_value, 0)),0) AS orders_total
					FROM ' . $wpdb->prefix . 'wc_orders wo
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta om1 ON wo.id = om1.order_id AND om1.meta_key = \'order_total\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta om2 ON wo.id = om2.order_id AND om2.meta_key = \'_payment_method_title\'
					LEFT JOIN ' . $wpdb->prefix . 'wc_orders_meta om3 ON wo.id = om3.order_id AND om3.meta_key = \'order_refund_amount\'
					WHERE wo.status = %s
					' . $driver_query . '
					AND CAST(o.delivered_date AS DATE) BETWEEN %s AND %s
					GROUP BY o.driver_id, om2.meta_value
					ORDER BY o.driver_id, om2.meta_value',
					array( get_option( 'lddfw_delivered_status', '' ), $fromdate, $todate )
				)
			);
		} else {
			// Original query for non-HPOS environments.

			$query = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT 
				driver_id,
				IFNULL( order_payment_method, \'\') as order_payment_method ,
				COALESCE(SUM( order_total - order_refund_amount ),0)  as orders_total
			 	FROM ' . $wpdb->prefix . 'posts p INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o
				ON p.ID = o.order_id
				WHERE
				p.post_type = \'shop_order\'
				AND p.post_status = %s
				' . $driver_query . '
				AND CAST(delivered_date AS DATE) BETWEEN %s AND %s
				GROUP BY driver_id,order_payment_method
				ORDER BY driver_id, order_payment_method
			    ',
					array( get_option( 'lddfw_delivered_status', '' ), $fromdate, $todate )
				)
			); // db call ok; no-cache ok.
		}
		return $query;
	}

	/**
	 * Drivers commissions report.
	 *
	 * @since 1.1.0
	 */
	public function drivers_commissions_report() {
		$currency_symbol        = lddfw_currency_symbol();
		$lddfw_dates_range      = ( isset( $_GET['lddfw_dates_range'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_dates_range'] ) ) : 'today';
		$lddfw_dates_range_from = ( isset( $_GET['lddfw_dates_range_from'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_dates_range_from'] ) ) : date_i18n( 'Y-m-d' );
		$lddfw_dates_range_to   = ( isset( $_GET['lddfw_dates_range_to'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_dates_range_to'] ) ) : date_i18n( 'Y-m-d' );

		// This week dates.
		$current_week       = get_weekstartend( date_i18n( 'Y-m-d' ), '' );
		$current_start_week = date( 'Y-m-d', $current_week['start'] );
		$current_end_week   = date( 'Y-m-d', $current_week['end'] );

		// Last week dates.
		$previous_start_week = date( 'Y-m-d', strtotime( $current_start_week . ' -7 day' ) );
		$previous_end_week   = date( 'Y-m-d', strtotime( $current_end_week . ' -7 day' ) );

		// Commission query.
		$report_array = $this->lddfw_drivers_commission_query( $lddfw_dates_range_from, $lddfw_dates_range_to );

		// Payment methods.
		$payments_report_array = $this->payment_methods_query( $lddfw_dates_range_from, $lddfw_dates_range_to );
		$gateways              = WC()->payment_gateways->payment_gateways();
		$payment_options       = array();

		// Create array of payment methods from query.
		foreach ( $payments_report_array as $row ) {
			
			$payment_method_id = $row->order_payment_method;
			$payment_title     = empty( $gateways[ $payment_method_id ]->title ) ? esc_attr( __( 'No payment', 'lddfw' ) ) : $gateways[ $payment_method_id ]->title;
			if ( ! in_array( $payment_method_id, $payment_options ) ) {
				$payment_options[ $payment_method_id ] = $payment_title;
			}
		}

		echo '<h2>' . esc_html( __( 'Drivers commissions', 'lddfw' ) ) . '</h2>
	<div id="lddfw_dates_range_wrap">
		<form method="GET" action="">
		<div id="lddfw_dates_range_select">' . esc_html( __( 'Dates', 'lddfw' ) ) . '
			<select class="custom-select custom-select-lg" name="lddfw_dates_range" id="lddfw_dates_range" data="' . lddfw_drivers_page_url( 'lddfw_screen=delivered' ) . '">
				<option ' . selected( $lddfw_dates_range, 'today', false ) . ' fromdate="' . date_i18n( 'Y-m-d' ) . '" todate="' . date_i18n( 'Y-m-d' ) . '" value="today">' . esc_html( __( 'Today', 'lddfw' ) ) . '</option>
				<option ' . selected( $lddfw_dates_range, 'yesterday', false ) . ' fromdate="' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . '" todate="' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . '" value="yesterday">' . esc_html( __( 'Yesterday', 'lddfw' ) ) . '</option>
				<option ' . selected( $lddfw_dates_range, 'thisweek', false ) . '  fromdate="' . $current_start_week . '" todate="' . $current_end_week . '"  value="thisweek">' . esc_html( __( 'This week', 'lddfw' ) ) . '</option>
				<option ' . selected( $lddfw_dates_range, 'lastweek', false ) . '  fromdate="' . $previous_start_week . '" todate="' . $previous_end_week . '"  value="lastweek">' . esc_html( __( 'Last week', 'lddfw' ) ) . '</option>
				<option ' . selected( $lddfw_dates_range, 'thismonth', false ) . '  fromdate="' . date_i18n( 'Y-m-d', strtotime( 'first day of this month' ) ) . '" todate="' . date_i18n( 'Y-m-d', strtotime( 'last day of this month' ) ) . '"  value="thismonth">' . esc_html( __( 'This month', 'lddfw' ) ) . '</option>
				<option ' . selected( $lddfw_dates_range, 'lastmonth', false ) . '  fromdate="' . date_i18n( 'Y-m-d', strtotime( 'first day of last month' ) ) . '" todate="' . date_i18n( 'Y-m-d', strtotime( 'last day of last month' ) ) . '"  value="lastmonth">' . esc_html( __( 'Last month', 'lddfw' ) ) . '</option>
				<option ' . selected( $lddfw_dates_range, 'custom', false ) . '  value="custom">' . esc_html( __( 'Custom', 'lddfw' ) ) . '</option>
			</select>
		</div>
		<input type="hidden" name="page" value="lddfw-reports" >
		<div id="lddfw_dates_custom_range" style="display:none">
		' . esc_html( __( 'From', 'lddfw' ) ) . ' <input type = "text" value="' . $lddfw_dates_range_from . '" class="lddfw-datepicker" name="lddfw_dates_range_from" id = "lddfw_dates_range_from" >
		' . esc_html( __( 'To', 'lddfw' ) ) . ' <input type = "text" value="' . $lddfw_dates_range_to . '" class="lddfw-datepicker"  name="lddfw_dates_range_to" id = "lddfw_dates_range_to" >
		</div>
		<input type="submit" name="submit" id="lddfw_dates_range_submit" class="button button-primary" value="' . esc_html( __( 'Send', 'lddfw' ) ) . '">
		</form>
		<div style="margin-top: 6px;font-size: 16px;">' . esc_html( __( 'From', 'lddfw' ) ) . ' <b>' . date( lddfw_date_format( 'date' ), strtotime( $lddfw_dates_range_from ) ) . '</b> ' . esc_html( __( 'To', 'lddfw' ) ) . ' <b>' . date( lddfw_date_format( 'date' ), strtotime( $lddfw_dates_range_to ) ) . '</b></div>
	</div>
';

		echo '
	<table class="wp-list-table widefat fixed striped table-view-list posts">
	<thead>
		<tr>
			<th class="manage-column column-primary ">' . esc_html( __( 'Drivers', 'lddfw' ) ) . '</td>
			<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Orders', 'lddfw' ) ) . '</td>';

			$coulmn_counter = 0;
		foreach ( $payment_options as $payment_id => $payment_name ) {
			if ( '' !== $payment_name ) {
				echo '<th class="manage-column column-primary lddfw-text-center">' . esc_html( $payment_name ) . '</td>';
				$coulmn_counter ++;
			}
		}

			echo '
			<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Orders Total', 'lddfw' ) ) . '</td>
			<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Shipping Total', 'lddfw' ) ) . '</td>
			<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Commission', 'lddfw' ) ) . '</td>
		</tr>
	</thead>
	<tbody>';

		$last_driver    = '';
		$commission     = 0;
		$orders_price   = 0;
		$shipping_price = 0;
		$orders_counter = 0;
		$driver_counter = 0;

		$commission_total     = 0;
		$orders_counter_total = 0;
		$orders_total         = 0;
		$shipping_total       = 0;
		$total_payments_array = array();
		if ( empty( $report_array ) ) {
			echo '
		<tr>
			<td colspan="' . esc_attr( $coulmn_counter + 5 ) . '" class="lddfw-text-center">' . esc_html( __( 'No orders', 'lddfw' ) ) . '</td>
		</tr>';
		} else {
			foreach ( $report_array as $row ) {
				$driver_id = $row->driver_id;
				if ( $last_driver !== $driver_id ) {
					$driver      = get_userdata( $driver_id );
					$driver_name = ( ! empty( $driver ) ) ? $driver->display_name : '';
					++$driver_counter;

					if ( lddfw_fs()->is__premium_only() ) {
						if ( lddfw_fs()->can_use_premium_code() ) {
							$orders_counter        = $row->orders;
							$orders_price          = $row->orders_total;
							$shipping_price        = $row->shipping_total;
							$commission            = $row->commission;
							$orders_counter_total += $orders_counter;
							$commission_total     += $commission;
							$orders_total         += $orders_price;
							$shipping_total       += $shipping_price;
						}
					}
					$last_driver = $driver_id;
					echo '
				<tr>
					<td class="title column-title has-row-actions column-primary" data-colname="' . esc_html( __( 'Driver', 'lddfw' ) ) . '" >' . $driver_name . '<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>
					<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Orders', 'lddfw' ) ) . '" >' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '&post_type=shop_order&lddfw_from_date=' . $lddfw_dates_range_from . '&lddfw_to_date=' . $lddfw_dates_range_to . '&lddfw_orders_filter=' . esc_attr( $driver_id ) . '">' . $orders_counter . '</a>' ) . '</td>';
					foreach ( $payment_options as $payment_id => $payment_name ) {
						echo '<td class="manage-column column-primary lddfw-text-center">';
						if ( ! empty( $payments_report_array ) ) {
							foreach ( $payments_report_array as $payment_row ) {
								if ( $payment_row->driver_id === $driver_id && $payment_row->order_payment_method === $payment_id ) {
									echo lddfw_admin_premium_feature( wc_price( $payment_row->orders_total ) );
									if ( array_key_exists( $payment_id, $total_payments_array ) ) {
										$total_payments_array[ $payment_id ] += $payment_row->orders_total;
									} else {
										$total_payments_array[ $payment_id ] = $payment_row->orders_total;
									}
									break;
								}
							}
						}
						echo '</td>';
					}
					echo '
					<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Orders Price', 'lddfw' ) ) . '" >' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '&post_type=shop_order&lddfw_from_date=' . $lddfw_dates_range_from . '&lddfw_to_date=' . $lddfw_dates_range_to . '&lddfw_orders_filter=' . esc_attr( $driver_id ) . '">' . wc_price( $orders_price ) . '</a>' ) . '</td>
					<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Shipping Price', 'lddfw' ) ) . '" >' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '&post_type=shop_order&lddfw_from_date=' . $lddfw_dates_range_from . '&lddfw_to_date=' . $lddfw_dates_range_to . '&lddfw_orders_filter=' . esc_attr( $driver_id ) . '">' . wc_price( $shipping_price ) . '</a>' ) . '</td>
					<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Commission', 'lddfw' ) ) . '" >' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '&post_type=shop_order&lddfw_from_date=' . $lddfw_dates_range_from . '&lddfw_to_date=' . $lddfw_dates_range_to . '&lddfw_orders_filter=' . esc_attr( $driver_id ) . '">' . wc_price( $commission ) . '</a>' ) . '</td>
				</tr>';
				}
			}
		}
		echo '</tbody>';
		if ( function_exists( 'wc_price' ) ) {
			echo '<tfoot>
			<td class="title column-title has-row-actions column-primary" data-colname="' . esc_html( __( 'Driver', 'lddfw' ) ) . '" >' . $driver_counter . ' ' . esc_html( __( 'Drivers', 'lddfw' ) ) . '</td>
			<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Orders', 'lddfw' ) ) . '">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '&post_type=shop_order&lddfw_from_date=' . $lddfw_dates_range_from . '&lddfw_to_date=' . $lddfw_dates_range_to . '&lddfw_orders_filter=-1">' . $orders_counter_total . '</a>' ) . '</td>';

			foreach ( $payment_options as $payment_id => $payment_name ) {
				echo '<td class="lddfw-text-center">';
				if ( ! empty( $total_payments_array[ $payment_id ] ) ) {
					echo lddfw_admin_premium_feature( wc_price( $total_payments_array[ $payment_id ] ) );
				}
				echo '</td>';
			}
			echo '
			<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Orders Price', 'lddfw' ) ) . '">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '&post_type=shop_order&lddfw_from_date=' . $lddfw_dates_range_from . '&lddfw_to_date=' . $lddfw_dates_range_to . '&lddfw_orders_filter=-1">' . wc_price( $orders_total ) . '</a>' ) . '</td>
			<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Shipping Price', 'lddfw' ) ) . '">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '&post_type=shop_order&lddfw_from_date=' . $lddfw_dates_range_from . '&lddfw_to_date=' . $lddfw_dates_range_to . '&lddfw_orders_filter=-1">' . wc_price( $shipping_total ) . '</a>' ) . '</td>
			<td class="lddfw-text-center" data-colname="' . esc_html( __( 'Commission', 'lddfw' ) ) . '">' . lddfw_admin_premium_feature( '<a href="edit.php?post_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '&post_type=shop_order&lddfw_from_date=' . $lddfw_dates_range_from . '&lddfw_to_date=' . $lddfw_dates_range_to . '&lddfw_orders_filter=-1">' . wc_price( $commission_total ) . '</a>' ) . '</td>
		</tfoot>';
		}
		echo '</table>';
	}

	/**
	 * Admin dashboard screen.
	 *
	 * @since 1.1.0
	 */
	public function screen_dashboard() {
		echo '<div class="wrap">
		<h1 class="wp-heading-inline">' . esc_html( __( 'Dashboard', 'lddfw' ) ) . '</h1>
		  ' . LDDFW_Admin::lddfw_admin_plugin_bar() . '
		  <hr class="wp-header-end">';
		  echo $this->drivers_orders_dashboard_report();
		  echo $this->claim_orders_dashboard_report();
		  echo $this->drivers_dashboard_report();
		  echo '
		</div>';
	}

	/**
	 * Admin report screen.
	 *
	 * @since 1.1.0
	 */
	public function screen_reports() {
		echo '<div class="wrap">
		<h1 class="wp-heading-inline">' . esc_html( __( 'Reports', 'lddfw' ) ) . '</h1>
		  ' . LDDFW_Admin::lddfw_admin_plugin_bar() . '
		  <hr class="wp-header-end">';
		  echo $this->drivers_commissions_report();
		  echo '
		</div>';
	}

	/**
	 * Drivers dashboard report.
	 *
	 * @since 1.1.0
	 */
	public function drivers_dashboard_report() {
		$drivers = LDDFW_Driver::lddfw_get_drivers();
		echo '
		<h2 style="margin-bottom: 0px;margin-top: 28px;">' . esc_html( __( 'Active drivers', 'lddfw' ) ) . '
		<a href="user-new.php" class="page-title-action" >' . esc_html( __( 'Add new driver', 'lddfw' ) ) . '</a>
		</h2>
		<ul class="subsubsub">
			<li class="all"><a href="users.php?role=driver">' . esc_html( __( 'All drivers', 'lddfw' ) ) . '</a></li>
		</ul>
		<table class="wp-list-table widefat fixed striped table-view-list posts">
		<thead>
			<tr>
				<th class="manage-column column-primary ">' . esc_html( __( 'Drivers', 'lddfw' ) ) . '</td>
				<th>' . esc_html( __( 'Phone', 'lddfw' ) ) . '</td>
				<th>' . esc_html( __( 'Email', 'lddfw' ) ) . '</td>
				<th>' . esc_html( __( 'Address', 'lddfw' ) ) . '</td>
				<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Availability', 'lddfw' ) ) . '</td>
				<th class="manage-column column-primary lddfw-text-center">' . esc_html( __( 'Claim orders', 'lddfw' ) ) . '</td>
			</tr>
		</thead>
		<tbody>';
		$total_driver = 0;

		if ( empty( $drivers ) ) {
			echo '
			<tr>
				<td colspan="6" class="lddfw-text-center">' . esc_html( __( 'No drivers', 'lddfw' ) ) . '</td>
			</tr>';
		} else {
			foreach ( $drivers as $driver ) {

				/**
				 * Driver data.
				 */
				$driver_id            = $driver->ID;
				$lddfw_driver_account = get_user_meta( $driver_id, 'lddfw_driver_account', true );

				// Activate exiting drivers account that added before version 1.1.0.
				if ( '' === $lddfw_driver_account ) {
					update_user_meta( $driver_id, 'lddfw_driver_account', '1' );
					$lddfw_driver_account = get_user_meta( $driver_id, 'lddfw_driver_account', true );
				}

				if ( '1' === $lddfw_driver_account ) {
					$email             = $driver->user_email;
					$full_name         = $driver->display_name;
					$availability      = get_user_meta( $driver_id, 'lddfw_driver_availability', true );
					$driver_claim      = get_user_meta( $driver_id, 'lddfw_driver_claim', true );
					$phone             = get_user_meta( $driver_id, 'billing_phone', true );
					$billing_address_1 = get_user_meta( $driver_id, 'billing_address_1', true );
					$billing_address_2 = get_user_meta( $driver_id, 'billing_address_2', true );
					$billing_city      = get_user_meta( $driver_id, 'billing_city', true );
					$billing_company   = get_user_meta( $driver_id, 'billing_company', true );
					$availability_icon = '';
					$driver_claim_icon = '';

					/**
					 * Driver billing address.
					 */
					$billing_address = '';
					if ( '' !== $billing_company ) {
						$billing_address = $billing_address . $billing_company . ', ';
					}
					if ( '' !== $billing_address_1 ) {
						$billing_address = $billing_address . $billing_address_1;
					}
					if ( '' !== $billing_address_2 ) {
						$billing_address = $billing_address . ', ' . $billing_address_2;
					}
					if ( '' !== $billing_city ) {
						$billing_address = $billing_address . ', ' . $billing_city;
					}

					$total_driver++;

					if ( lddfw_fs()->is__premium_only() ) {
						if ( lddfw_fs()->can_use_premium_code() ) {
							/**
							 * Driver status icons and counters.
							 */
							if ( '1' === $availability ) {
								$availability_icon = '<a href="#" class="lddfw_availability_icon lddfw_icon lddfw_active" driver_id="' . esc_attr( $driver_id ) . '" ><i class="lddfw-toggle-on"></i></a>';
							} else {
								$availability_icon = '<a href="#" class="lddfw_availability_icon lddfw_icon" driver_id="' . esc_attr( $driver_id ) . '" ><i class="lddfw-toggle-off"></i></a>';
							}

							if ( '1' === $driver_claim ) {
								$driver_claim_icon = '<a href="#" class="lddfw_claim_icon lddfw_icon lddfw_active" driver_id="' . esc_attr( $driver_id ) . '" ><i class="lddfw-toggle-on"></i></a>';
							} else {
								$driver_claim_icon = '<a href="#" class="lddfw_claim_icon lddfw_icon" driver_id="' . esc_attr( $driver_id ) . '" ><i class="lddfw-toggle-off"></i></a>';
							}
						}
					}
					echo '
				<tr>
					<td class="title column-title has-row-actions column-primary" data-colname="' . esc_html( __( 'Driver', 'lddfw' ) ) . '" >
						<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
						<a href="' . get_edit_user_link( $driver_id ) . '">' . esc_html( $full_name ) . '</a>
					</td>
					<td data-colname="' . esc_html( __( 'Phone', 'lddfw' ) ) . '"><a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a></td>
					<td data-colname="' . esc_html( __( 'Email', 'lddfw' ) ) . '" ><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></td>
					<td data-colname="' . esc_html( __( 'Address', 'lddfw' ) ) . '">' . $billing_address . '</td>
					<td data-colname="' . esc_html( __( 'Availability', 'lddfw' ) ) . '" class="lddfw-text-center">' . lddfw_admin_premium_feature( $availability_icon ) . '</td>
					<td data-colname="' . esc_html( __( 'Claim orders', 'lddfw' ) ) . '" class="lddfw-text-center">' . lddfw_admin_premium_feature( $driver_claim_icon ) . '</td>
				</tr>';
				}
			}
		}
			echo '</tbody>
				<tfoot>
					<td class="title column-title has-row-actions column-primary">' . $total_driver . ' ';
		if ( 1 < $total_driver ) {
			echo esc_html( __( 'Drivers', 'lddfw' ) );
		} else {
			echo esc_html( __( 'Driver', 'lddfw' ) );
		}
					echo '</td>
					<td></td>
					<td></td>
					<td></td>
					<td class = "lddfw-text-center">' . lddfw_admin_premium_feature( '<span id="lddfw_available_counter"></span> ' . esc_html( __( 'Availables', 'lddfw' ) ) . ' |  <span id="lddfw_unavailable_counter"></span> ' . esc_html( __( 'Unavailables', 'lddfw' ) ) ) . '</td>
					<td class = "lddfw-text-center">' . lddfw_admin_premium_feature( '<span id="lddfw_claim_counter"></span> ' . esc_html( __( 'Can claim', 'lddfw' ) ) . ' | <span id="lddfw_unclaim_counter"></span> ' . esc_html( __( 'Can\'t claim', 'lddfw' ) ) ) . '</td>
				</tfoot>
			</table>';
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
					echo '<script>
					jQuery(document).ready(
						function($) {
							lddfw_counters();
						});
					</script>';
			}
		}

			echo '<div class="driver_app">
					<img alt="' . esc_attr( 'Drivers app', 'lddfw' ) . '" title="' . esc_attr( 'Drivers app', 'lddfw' ) . '" src="' . esc_attr( plugins_url() . '/' . LDDFW_FOLDER . '/public/images/drivers_app.png?ver=' . LDDFW_VERSION ) . '">
					<p>
						<b><a target="_blank" href="' . lddfw_drivers_page_url( '' ) . '">' . lddfw_drivers_page_url( '' ) . '</a></b><br>' .
						 sprintf( esc_html( __( 'The link above is the delivery driver\'s Mobile-Friendly panel URL. %1$s The delivery drivers can access it from their mobile phones. %2$s', 'lddfw' ) ), '<br>', '<br>' ) .
						 sprintf( esc_html( __( 'Notice: If you want to be logged in as an administrator and to check the drivers\' panel on the same device, %1$s %2$syou must work with two different browsers otherwise you will log out from the admin panel and the drivers\' panel won\'t function correctly.%3$s', 'lddfw' ) ), '<br>', '<b>', '</b>' ) . '
		 			</p>
				</div>
				';

	}
}
