<?php
/**
 * Plugin Reports.
 *
 * All the screens functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */

/**
 * Plugin Reports.
 *
 * All the Reports functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */
class PWDDM_Reports {

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
	 * @return html
	 */
	public function claim_orders_dashboard_report() {
		$orders                        = new PWDDM_Orders();
		$report_array                  = $orders->pwddm_claim_orders_dashboard_report_query();
		$html                          = '<h5>' . esc_html( __( 'Orders without drivers', 'pwddm' ) ) . '</h5>
	<div class="table-responsive">
	<table class=" table  table-striped  ">
	<thead class="table-dark">
		<tr>
			<th class="manage-column column-primary ">' . esc_html( __( 'Ready for claim', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Driver assigned', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Out for delivery', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Delivered today', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Failed delivery', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Total', 'pwddm' ) ) . '</td>
		</tr>
	</thead>
	<tbody>';
		$pwddm_driver_assigned_status  = get_option( 'lddfw_driver_assigned_status', '' );
		$pwddm_out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
		$pwddm_failed_attempt_status   = get_option( 'lddfw_failed_attempt_status', '' );
		$pwddm_delivered_status        = get_option( 'lddfw_delivered_status', '' );
		$pwddm_processing_status       = get_option( 'lddfw_processing_status', '' );

		if ( empty( $report_array ) ) {
			$html .= '
		<tr>
			<td colspan="6" class="text-center">' . esc_html( __( 'No orders', 'pwddm' ) ) . '</td>
		</tr>';
		} else {

				$processing_status       = '';
				$out_for_delivery_orders = '';
				$driver_assigned_orders  = '';
				$failed_attempt_orders   = '';
				$delivered_orders        = '';
				$total                   = '';
			if ( pwddm_fs()->is__premium_only() ) {
				if ( pwddm_fs()->can_use_premium_code() ) {
					$processing_status       = $this->driver_status_orders( '', $pwddm_processing_status, $report_array );
					$out_for_delivery_orders = $this->driver_status_orders( '', $pwddm_out_for_delivery_status, $report_array );
					$driver_assigned_orders  = $this->driver_status_orders( '', $pwddm_driver_assigned_status, $report_array );
					$failed_attempt_orders   = $this->driver_status_orders( '', $pwddm_failed_attempt_status, $report_array );
					$delivered_orders        = $this->driver_status_orders( '', $pwddm_delivered_status, $report_array );
					$total                   = $processing_status + $out_for_delivery_orders + $driver_assigned_orders + $failed_attempt_orders + $delivered_orders;
				}
			}
				$html .= '
				<tr class="table-secondary">
					<td class="title column-title has-row-actions column-primary" data-colname="' . esc_html( __( 'Ready for claim', 'pwddm' ) ) . '" >
					' . pwddm_premium_feature( '<a href=" ' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=-2&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_processing_status' ) ) ) . '">' . $processing_status . '</a>' ) . '
					</td>
					<td data-colname="' . esc_html( __( 'Driver assigned', 'pwddm' ) ) . '" class="text-center">' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=-2&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_driver_assigned_status' ) ) ) . '">' . $driver_assigned_orders . '</a>' ) . '</td>
					<td data-colname="' . esc_html( __( 'Out for delivery', 'pwddm' ) ) . '" class="text-center">' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=-2&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_out_for_delivery_status' ) ) ) . '">' . $out_for_delivery_orders . '</a>' ) . '</td>
					<td data-colname="' . esc_html( __( 'Delivered today', 'pwddm' ) ) . '" class="text-center">' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=-2&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) ) . '&pwddm_dates_range=' . date_i18n( 'Y-m-d' ) . ',' . date_i18n( 'Y-m-d' ) . '">' . $delivered_orders . '</a>' ) . '</td>
					<td data-colname="' . esc_html( __( 'Failed delivery', 'pwddm' ) ) . '" class="text-center">' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=-2&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_failed_attempt_status' ) ) ) . '">' . $failed_attempt_orders . '</a>' ) . '</td>
					<td data-colname="' . esc_html( __( 'Total', 'pwddm' ) ) . '" class="text-center">' . pwddm_premium_feature( $total ) . '</td>
				</tr>';
				$html .= '</tbody>';
		}
		$html .= '</table></div>';
		return $html;
	}

	/**
	 * Drivers orders dashboard report.
	 *
	 * @since 1.1.0
	 * @return html
	 */
	public function drivers_orders_dashboard_report() {
		global   $pwddm_manager_id;
		$orders       = new PWDDM_Orders();
		$driver       = new LDDFW_Driver();
		$report_array = $orders->pwddm_drivers_orders_dashboard_report_query();
		$html         = '<h5>' . esc_html( __( 'Drivers orders', 'pwddm' ) ) . '</h5>
	<div class="table-responsive">
	<table class=" table  table-striped ">
	<thead class="table-dark">
		<tr>
			<th class="manage-column column-primary ">' . esc_html( __( 'Drivers', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center ">' . esc_html( __( 'Phone', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Driver assigned', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Out for delivery', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Delivered today', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Failed delivery', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Total', 'pwddm' ) ) . '</td>
		</tr>
	</thead>
	<tbody>';

		$pwddm_driver_assigned_status  = get_option( 'lddfw_driver_assigned_status', '' );
		$pwddm_out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
		$pwddm_failed_attempt_status   = get_option( 'lddfw_failed_attempt_status', '' );
		$pwddm_delivered_status        = get_option( 'lddfw_delivered_status', '' );

		$last_driver                   = '';
		$out_for_delivery_orders_total = 0;
		$driver_assigned_orders_total  = 0;
		$failed_attempt_orders_total   = 0;
		$delivered_orders_total        = 0;
		$total                         = 0;
		$driver_counter                = 0;
		$sub_total                     = 0;
		if ( empty( $report_array ) ) {
			$html .= '
		<tr>
			<td colspan="7" class="text-center">' . esc_html( __( 'No orders', 'pwddm' ) ) . '</td>
		</tr>';
		} else {

			foreach ( $report_array as $row ) {
				$driver_id = $row->driver_id;
				if ( $last_driver !== $driver_id ) {
					$driver      = get_user_by( 'ID', $driver_id );
					$manager_id  = get_user_meta( $driver_id, 'pwddm_manager', true );
					$manage_type = get_user_meta( $manager_id, 'pwddm_manager_drivers', true );

					if (
						( ( $manage_type === '0' || strval( $manage_type ) === '' ) && ( strval( $manager_id ) === '' ) )
						||
						( $manage_type === '2' && strval( $pwddm_manager_id ) === $manager_id )
						||
						( $manage_type === '1' && ( strval( $pwddm_manager_id ) === $manager_id || strval( $manager_id ) === '' ) )
					) {

						$driver_counter += 1;

						$out_for_delivery_orders = $this->driver_status_orders( $driver_id, $pwddm_out_for_delivery_status, $report_array );
						$driver_assigned_orders  = $this->driver_status_orders( $driver_id, $pwddm_driver_assigned_status, $report_array );
						$failed_attempt_orders   = $this->driver_status_orders( $driver_id, $pwddm_failed_attempt_status, $report_array );
						$delivered_orders        = $this->driver_status_orders( $driver_id, $pwddm_delivered_status, $report_array );

						$sub_total                      = $out_for_delivery_orders + $driver_assigned_orders + $failed_attempt_orders + $delivered_orders;
						$total                         += $sub_total;
						$out_for_delivery_orders_total += $out_for_delivery_orders;
						$driver_assigned_orders_total  += $driver_assigned_orders;
						$failed_attempt_orders_total   += $failed_attempt_orders;
						$delivered_orders_total        += $delivered_orders;

						$phone = get_user_meta( $driver_id, 'billing_phone', true );

						$last_driver = $driver_id;
						$html       .= '
				<tr>
					<td class="title column-title has-row-actions column-primary" data-colname="' . esc_html( __( 'Driver', 'pwddm' ) ) . '" >
						' . $driver->display_name . '
					</td>
				 	<td class="text-center" data-colname="' . esc_html( __( 'Phone', 'pwddm' ) ) . '"><a href="tel:' . $phone . '">' . $phone . '</a></td>
					<td class="text-center" data-colname="' . esc_html( __( 'Driver assigned', 'pwddm' ) ) . '">' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=' . esc_attr( $driver_id ) . '&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_driver_assigned_status' ) ) ) . '">' . $driver_assigned_orders . '</a>' ) . '</td>
					<td class="text-center" data-colname="' . esc_html( __( 'Out for delivery', 'pwddm' ) ) . '">' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=' . esc_attr( $driver_id ) . '&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_out_for_delivery_status' ) ) ) . '">' . $out_for_delivery_orders . '</a>' ) . '</td>
					<td class="text-center" data-colname="' . esc_html( __( 'Delivered today', 'pwddm' ) ) . '">' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=' . esc_attr( $driver_id ) . '&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '&pwddm_dates_range=' . date_i18n( 'Y-m-d' ) . ',' . date_i18n( 'Y-m-d' ) ) . '">' . $delivered_orders . '</a>' ) . '</td>
					<td class="text-center" data-colname="' . esc_html( __( 'Failed delivery', 'pwddm' ) ) . '">' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=' . esc_attr( $driver_id ) . '&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_failed_attempt_status' ) ) ) . '">' . $failed_attempt_orders . '</a>' ) . '</td>
					<td class="text-center" data-colname="' . esc_html( __( 'Total', 'pwddm' ) ) . '">' . pwddm_premium_feature( $sub_total ) . '</td>
				</tr>';
					}
				}
			}
		}
		$html .= '</tbody>
		<tfoot class="table-secondary">
			<td class="title column-title has-row-actions column-primary">' . $driver_counter . ' ' . esc_html( __( 'Drivers', 'pwddm' ) ) . '</td>
			<td class="text-center"> </td>
			<td class="text-center">' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=-1&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_driver_assigned_status' ) ) ) . '">' . $driver_assigned_orders_total . '</a>' ) . '</td>
			<td class="text-center">' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=-1&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_out_for_delivery_status' ) ) ) . '">' . $out_for_delivery_orders_total . '</a>' ) . '</td>
			<td class="text-center">' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=-1&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) . '&pwddm_dates_range=' . date_i18n( 'Y-m-d' ) . ',' . date_i18n( 'Y-m-d' ) ) . '">' . $delivered_orders_total . '</a>' ) . '</td>
			<td class="text-center">' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=-1&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_failed_attempt_status' ) ) ) . '">' . $failed_attempt_orders_total . '</a>' ) . '</td>
			<td class="text-center">' . pwddm_premium_feature( $total ) . '</td>
		</tfoot>
	</table></div>';
		return $html;
	}

	/**
	 * drivers refund query.
	 *
	 * @since 1.1.2
	 * @return html
	 */
	public function pwddm_drivers_refund_query( $fromdate, $todate ) {
		global $wpdb,$pwddm_manager_id,$pwddm_manager_drivers;
		switch ( $pwddm_manager_drivers ) {
			case '2':
				if ( pwddm_is_hpos_enabled() ) {
					// Query adapted for HPOS-enabled environments.
					$query = $wpdb->get_results(
						$wpdb->prepare(
							'select driver.meta_value as driver_id,
							COALESCE(SUM( wom5.meta_value ),0) as refund
							from ' . $wpdb->prefix . 'wc_orders wo
							inner join ' . $wpdb->prefix . 'wc_orders_meta driver on wo.id=driver.order_id and driver.meta_key = \'lddfw_driverid\'
							inner join ' . $wpdb->prefix . 'wc_orders_meta wom1 on wo.id=wom1.order_id and wom1.meta_key = \'lddfw_delivered_date\'
							left join ' . $wpdb->prefix . 'wc_orders wo2 on wo.id=wo2.parent_order_id
							left join ' . $wpdb->prefix . 'wc_orders_meta wom5 on wo2.id=wom5.order_id and wom5.meta_key = \'_refund_amount\'
							inner join ' . $wpdb->prefix . 'usermeta manager on driver.meta_value=manager.user_id and manager.meta_key = \'pwddm_manager\' and manager.meta_value = %s
							where wo.type=\'shop_order\' and
							( wo.status = %s and CAST( wom1.meta_value AS DATE ) >= %s and CAST( wom1.meta_value AS DATE ) <= %s )
							group by driver.meta_value
							order by driver.meta_value',
							array(
								$pwddm_manager_id,
								get_option( 'lddfw_delivered_status', '' ),
								$fromdate,
								$todate,
							)
						)
					); // db call ok; no-cache ok.
				} else {
					// Original query for non-HPOS environments.
					$query = $wpdb->get_results(
						$wpdb->prepare(
							'select driver.meta_value as driver_id,
						COALESCE(SUM( pm5.meta_value ),0) as refund
						from ' . $wpdb->prefix . 'posts p
						inner join ' . $wpdb->prefix . 'postmeta driver on p.id=driver.post_id and driver.meta_key = \'lddfw_driverid\'
						inner join ' . $wpdb->prefix . 'postmeta pm1 on p.id=pm1.post_id and pm1.meta_key = \'lddfw_delivered_date\'
						left join ' . $wpdb->prefix . 'posts p2 on p.id=p2.post_parent
						left join ' . $wpdb->prefix . 'postmeta pm5 on p2.id=pm5.post_id and pm5.meta_key = \'_refund_amount\'
						inner join ' . $wpdb->prefix . 'usermeta manager on driver.meta_value=manager.user_id and manager.meta_key = \'pwddm_manager\' and manager.meta_value = %s
						where   p.post_type=\'shop_order\' and
						( p.post_status = %s and CAST( pm1.meta_value AS DATE ) >= %s and CAST( pm1.meta_value AS DATE ) <= %s )
						group by driver.meta_value
						order by driver.meta_value ',
							array(
								$pwddm_manager_id,
								get_option( 'lddfw_delivered_status', '' ),
								$fromdate,
								$todate,
							)
						)
					); // db call ok; no-cache ok.
				}
				break;
			case '0':
			case '':
				if ( pwddm_is_hpos_enabled() ) {
					// Query adapted for HPOS-enabled environments.
					$query = $wpdb->get_results(
						$wpdb->prepare(
							'select driver.meta_value as driver_id,
							COALESCE(SUM( wom5.meta_value ),0) as refund
							from ' . $wpdb->prefix . 'wc_orders wo
							inner join ' . $wpdb->prefix . 'wc_orders_meta driver on wo.id=driver.order_id and driver.meta_key = \'lddfw_driverid\'
							inner join ' . $wpdb->prefix . 'wc_orders_meta wom1 on wo.id=wom1.order_id and wom1.meta_key = \'lddfw_delivered_date\'
							left join ' . $wpdb->prefix . 'wc_orders wo2 on wo.id=wo2.parent_order_id
							left join ' . $wpdb->prefix . 'wc_orders_meta wom5 on wo2.id=wom5.order_id and wom5.meta_key = \'_refund_amount\'
							left join ' . $wpdb->prefix . 'usermeta manager on driver.meta_value=manager.user_id and manager.meta_key = \'pwddm_manager\'
							where ( manager.meta_value IS NULL or manager.meta_value = \'\') and wo.type=\'shop_order\' and
							( wo.status = %s and CAST( wom1.meta_value AS DATE ) >= %s and CAST( wom1.meta_value AS DATE ) <= %s )
							group by driver.meta_value
							order by driver.meta_value',
							array(
								get_option( 'lddfw_delivered_status', '' ),
								$fromdate,
								$todate,
							)
						)
					); // db call ok; no-cache ok.
				} else {
					// Original query for non-HPOS environments.

					$query = $wpdb->get_results(
						$wpdb->prepare(
							'select driver.meta_value as driver_id,
						COALESCE(SUM( pm5.meta_value ),0) as refund
						from ' . $wpdb->prefix . 'posts p
						inner join ' . $wpdb->prefix . 'postmeta driver on p.id=driver.post_id and driver.meta_key = \'lddfw_driverid\'
						inner join ' . $wpdb->prefix . 'postmeta pm1 on p.id=pm1.post_id and pm1.meta_key = \'lddfw_delivered_date\'
						left join ' . $wpdb->prefix . 'posts p2 on p.id=p2.post_parent
						left join ' . $wpdb->prefix . 'postmeta pm5 on p2.id=pm5.post_id and pm5.meta_key = \'_refund_amount\'
						left join ' . $wpdb->prefix . 'usermeta manager on driver.meta_value=manager.user_id and manager.meta_key = \'pwddm_manager\'
						where  ( manager.meta_value IS NULL or manager.meta_value = \'\') and p.post_type=\'shop_order\' and
						( p.post_status = %s and CAST( pm1.meta_value AS DATE ) >= %s and CAST( pm1.meta_value AS DATE ) <= %s )
						group by driver.meta_value
						order by driver.meta_value ',
							array(
								get_option( 'lddfw_delivered_status', '' ),
								$fromdate,
								$todate,
							)
						)
					); // db call ok; no-cache ok.
				}
				break;
			default:
				if ( pwddm_is_hpos_enabled() ) {
					// Query adapted for HPOS-enabled environments.
					$query = $wpdb->get_results(
						$wpdb->prepare(
							'select wom.meta_value as driver_id,
						COALESCE(SUM( wom5.meta_value ),0) as refund
						from ' . $wpdb->prefix . 'wc_orders wo
						inner join ' . $wpdb->prefix . 'wc_orders_meta wom on wo.id=wom.order_id and wom.meta_key = \'lddfw_driverid\'
						inner join ' . $wpdb->prefix . 'wc_orders_meta wom1 on wo.id=wom1.order_id and wom1.meta_key = \'lddfw_delivered_date\'
						left join ' . $wpdb->prefix . 'wc_orders wo2 on wo.id=wo2.parent_order_id
						left join ' . $wpdb->prefix . 'wc_orders_meta wom5 on wo2.id=wom5.order_id and wom5.meta_key = \'_refund_amount\'
						where wo.type=\'shop_order\' and
						( wo.status = %s and CAST( wom1.meta_value AS DATE ) >= %s and CAST( wom1.meta_value AS DATE ) <= %s )
						group by wom.meta_value
						order by wom.meta_value',
							array(
								get_option( 'lddfw_delivered_status', '' ),
								$fromdate,
								$todate,
							)
						)
					); // db call ok; no-cache ok.
				} else {
					// Original query for non-HPOS environments.
					$query = $wpdb->get_results(
						$wpdb->prepare(
							'select pm.meta_value as driver_id,
					COALESCE(SUM( pm5.meta_value ),0) as refund
					from ' . $wpdb->prefix . 'posts p
					inner join ' . $wpdb->prefix . 'postmeta pm on p.id=pm.post_id and pm.meta_key = \'lddfw_driverid\'
					inner join ' . $wpdb->prefix . 'postmeta pm1 on p.id=pm1.post_id and pm1.meta_key = \'lddfw_delivered_date\'
					left join ' . $wpdb->prefix . 'posts p2 on p.id=p2.post_parent
					left join ' . $wpdb->prefix . 'postmeta pm5 on p2.id=pm5.post_id and pm5.meta_key = \'_refund_amount\'
					where p.post_type=\'shop_order\' and
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
				break;
		}
		return $query;
	}

	/**
	 * drivers commissions query.
	 *
	 * @since 1.1.2
	 * @return html
	 */
	public function pwddm_drivers_commission_query( $fromdate, $todate ) {
		global $wpdb,$pwddm_manager_id,$pwddm_manager_drivers;
		switch ( $pwddm_manager_drivers ) {
			case '2':
				if ( pwddm_is_hpos_enabled() ) {
					// Query adapted for HPOS-enabled environments.
					$query = $wpdb->get_results(
						$wpdb->prepare(
							'select driver.meta_value as driver_id,
							COALESCE(SUM( wom2.meta_value ),0) as commission,
							count(wo.id) as orders,
							COALESCE(SUM( wom3.meta_value),0) as orders_total,
							COALESCE(SUM( wom4.meta_value ),0) as shipping_total
							from ' . $wpdb->prefix . 'wc_orders wo
							inner join ' . $wpdb->prefix . 'wc_orders_meta driver on wo.id=driver.order_id and driver.meta_key = \'lddfw_driverid\'
							inner join ' . $wpdb->prefix . 'wc_orders_meta wom1 on wo.id=wom1.order_id and wom1.meta_key = \'lddfw_delivered_date\'
							left join ' . $wpdb->prefix . 'wc_orders_meta wom2 on wo.id=wom2.order_id and wom2.meta_key = \'lddfw_driver_commission\'
							left join ' . $wpdb->prefix . 'wc_orders_meta wom3 on wo.id=wom3.order_id and wom3.meta_key = \'_order_total\'
							left join ' . $wpdb->prefix . 'wc_orders_meta wom4 on wo.id=wom4.order_id and wom4.meta_key = \'_order_shipping\'
							inner join ' . $wpdb->prefix . 'usermeta manager on driver.meta_value=manager.user_id and manager.meta_key = \'pwddm_manager\' and manager.meta_value = %s
							where wo.type=\'shop_order\' and
							( wo.status = %s and CAST( wom1.meta_value AS DATE ) >= %s and CAST( wom1.meta_value AS DATE ) <= %s )
							group by driver.meta_value
							order by driver.meta_value',
							array(
								$pwddm_manager_id,
								get_option( 'lddfw_delivered_status', '' ),
								$fromdate,
								$todate,
							)
						)
					); // db call ok; no-cache ok.
				} else {
					// Original query for non-HPOS environments.

					$query = $wpdb->get_results(
						$wpdb->prepare(
							'select driver.meta_value driver_id,
						COALESCE(SUM( pm2.meta_value ),0) as commission ,
						count(p.id) as orders,
						COALESCE(SUM( pm3.meta_value),0)  as orders_total ,
						COALESCE(SUM( pm4.meta_value ),0) as shipping_total
						from ' . $wpdb->prefix . 'posts p
						inner join ' . $wpdb->prefix . 'postmeta driver on p.id=driver.post_id and driver.meta_key = \'lddfw_driverid\'
						inner join ' . $wpdb->prefix . 'postmeta pm1 on p.id=pm1.post_id and pm1.meta_key = \'lddfw_delivered_date\'
						left join ' . $wpdb->prefix . 'postmeta pm2 on p.id=pm2.post_id and pm2.meta_key = \'lddfw_driver_commission\'
						left join ' . $wpdb->prefix . 'postmeta pm3 on p.id=pm3.post_id and pm3.meta_key = \'_order_total\'
						left join ' . $wpdb->prefix . 'postmeta pm4 on p.id=pm4.post_id and pm4.meta_key = \'_order_shipping\'
						inner join ' . $wpdb->prefix . 'usermeta manager on driver.meta_value=manager.user_id and manager.meta_key = \'pwddm_manager\' and manager.meta_value = %s
						where p.post_type=\'shop_order\' and
						( p.post_status = %s and CAST( pm1.meta_value AS DATE ) >= %s and CAST( pm1.meta_value AS DATE ) <= %s )
						group by driver.meta_value
						order by driver.meta_value ',
							array(
								$pwddm_manager_id,
								get_option( 'lddfw_delivered_status', '' ),
								$fromdate,
								$todate,
							)
						)
					); // db call ok; no-cache ok.
				}
				break;
			case '0':
			case '':
				if ( pwddm_is_hpos_enabled() ) {
					// Query adapted for HPOS-enabled environments.
					$query = $wpdb->get_results(
						$wpdb->prepare(
							'select driver.meta_value as driver_id,
							COALESCE(SUM( wom2.meta_value ),0) as commission,
							count(wo.id) as orders,
							COALESCE(SUM( wom3.meta_value),0) as orders_total,
							COALESCE(SUM( wom4.meta_value ),0) as shipping_total
							from ' . $wpdb->prefix . 'wc_orders wo
							inner join ' . $wpdb->prefix . 'wc_orders_meta driver on wo.id=driver.order_id and driver.meta_key = \'lddfw_driverid\'
							inner join ' . $wpdb->prefix . 'wc_orders_meta wom1 on wo.id=wom1.order_id and wom1.meta_key = \'lddfw_delivered_date\'
							left join ' . $wpdb->prefix . 'wc_orders_meta wom2 on wo.id=wom2.order_id and wom2.meta_key = \'lddfw_driver_commission\'
							left join ' . $wpdb->prefix . 'wc_orders_meta wom3 on wo.id=wom3.order_id and wom3.meta_key = \'_order_total\'
							left join ' . $wpdb->prefix . 'wc_orders_meta wom4 on wo.id=wom4.order_id and wom4.meta_key = \'_order_shipping\'
							left join ' . $wpdb->prefix . 'usermeta manager on driver.meta_value=manager.user_id and manager.meta_key = \'pwddm_manager\'
							where ( manager.meta_value IS NULL or manager.meta_value = \'\') and wo.type=\'shop_order\' and
							( wo.status = %s and CAST( wom1.meta_value AS DATE ) >= %s and CAST( wom1.meta_value AS DATE ) <= %s )
							group by driver.meta_value
							order by driver.meta_value',
							array(
								get_option( 'lddfw_delivered_status', '' ),
								$fromdate,
								$todate,
							)
						)
					); // db call ok; no-cache ok.
				} else {
					// Original query for non-HPOS environments.

					$query = $wpdb->get_results(
						$wpdb->prepare(
							'select driver.meta_value driver_id,
						COALESCE(SUM( pm2.meta_value ),0) as commission ,
						count(p.id) as orders,
						COALESCE(SUM( pm3.meta_value),0)  as orders_total ,
						COALESCE(SUM( pm4.meta_value ),0) as shipping_total
						from ' . $wpdb->prefix . 'posts p
						inner join ' . $wpdb->prefix . 'postmeta driver on p.id=driver.post_id and driver.meta_key = \'lddfw_driverid\'
						inner join ' . $wpdb->prefix . 'postmeta pm1 on p.id=pm1.post_id and pm1.meta_key = \'lddfw_delivered_date\'
						left join ' . $wpdb->prefix . 'postmeta pm2 on p.id=pm2.post_id and pm2.meta_key = \'lddfw_driver_commission\'
						left join ' . $wpdb->prefix . 'postmeta pm3 on p.id=pm3.post_id and pm3.meta_key = \'_order_total\'
						left join ' . $wpdb->prefix . 'postmeta pm4 on p.id=pm4.post_id and pm4.meta_key = \'_order_shipping\'
						left join ' . $wpdb->prefix . 'usermeta manager on driver.meta_value=manager.user_id and manager.meta_key = \'pwddm_manager\'
						where ( manager.meta_value IS NULL or manager.meta_value = \'\') and p.post_type=\'shop_order\' and
						( p.post_status = %s and CAST( pm1.meta_value AS DATE ) >= %s and CAST( pm1.meta_value AS DATE ) <= %s )
						group by driver.meta_value
						order by driver.meta_value ',
							array(
								get_option( 'lddfw_delivered_status', '' ),
								$fromdate,
								$todate,
							)
						)
					); // db call ok; no-cache ok.

				}

				break;
			default:
				if ( pwddm_is_hpos_enabled() ) {
					// Query adapted for HPOS-enabled environments.
					$query = $wpdb->get_results(
						$wpdb->prepare(
							'select driver.meta_value as driver_id,
						COALESCE(SUM( wom2.meta_value ),0) as commission,
						count(wo.id) as orders,
						COALESCE(SUM( wom3.meta_value),0) as orders_total,
						COALESCE(SUM( wom4.meta_value ),0) as shipping_total
						from ' . $wpdb->prefix . 'wc_orders wo
						inner join ' . $wpdb->prefix . 'wc_orders_meta driver on wo.id=driver.order_id and driver.meta_key = \'lddfw_driverid\'
						inner join ' . $wpdb->prefix . 'wc_orders_meta wom1 on wo.id=wom1.order_id and wom1.meta_key = \'lddfw_delivered_date\'
						left join ' . $wpdb->prefix . 'wc_orders_meta wom2 on wo.id=wom2.order_id and wom2.meta_key = \'lddfw_driver_commission\'
						left join ' . $wpdb->prefix . 'wc_orders_meta wom3 on wo.id=wom3.order_id and wom3.meta_key = \'_order_total\'
						left join ' . $wpdb->prefix . 'wc_orders_meta wom4 on wo.id=wom4.order_id and wom4.meta_key = \'_order_shipping\'
						where wo.type=\'shop_order\' and
						( wo.status = %s and CAST( wom1.meta_value AS DATE ) >= %s and CAST( wom1.meta_value AS DATE ) <= %s )
						group by driver.meta_value
						order by driver.meta_value',
							array(
								get_option( 'lddfw_delivered_status', '' ),
								$fromdate,
								$todate,
							)
						)
					); // db call ok; no-cache ok.
				} else {
					// Original query for non-HPOS environments.
					$query = $wpdb->get_results(
						$wpdb->prepare(
							'select driver.meta_value driver_id,
					COALESCE(SUM( pm2.meta_value ),0) as commission ,
					count(p.id) as orders,
					COALESCE(SUM( pm3.meta_value),0)  as orders_total ,
					COALESCE(SUM( pm4.meta_value ),0) as shipping_total
					from ' . $wpdb->prefix . 'posts p
					inner join ' . $wpdb->prefix . 'postmeta driver on p.id=driver.post_id and driver.meta_key = \'lddfw_driverid\'
					inner join ' . $wpdb->prefix . 'postmeta pm1 on p.id=pm1.post_id and pm1.meta_key = \'lddfw_delivered_date\'
					left join ' . $wpdb->prefix . 'postmeta pm2 on p.id=pm2.post_id and pm2.meta_key = \'lddfw_driver_commission\'
					left join ' . $wpdb->prefix . 'postmeta pm3 on p.id=pm3.post_id and pm3.meta_key = \'_order_total\'
					left join ' . $wpdb->prefix . 'postmeta pm4 on p.id=pm4.post_id and pm4.meta_key = \'_order_shipping\'
					where  p.post_type=\'shop_order\' and
					( p.post_status = %s and CAST( pm1.meta_value AS DATE ) >= %s and CAST( pm1.meta_value AS DATE ) <= %s )
					group by driver.meta_value
					order by driver.meta_value ',
							array(
								get_option( 'lddfw_delivered_status', '' ),
								$fromdate,
								$todate,
							)
						)
					); // db call ok; no-cache ok.
				}
				break;
		}

		return $query;
	}

	/**
	 * Drivers commissions report.
	 *
	 * @since 1.1.0
	 * @return html
	 */
	public function drivers_commissions_report() {

		$pwddm_dates_range      = ( isset( $_POST['pwddm_dates_range'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_dates_range'] ) ) : 'today';
		$pwddm_dates_range_from = ( isset( $_POST['pwddm_dates_range_from'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_dates_range_from'] ) ) : date_i18n( 'Y-m-d' );
		$pwddm_dates_range_to   = ( isset( $_POST['pwddm_dates_range_to'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_dates_range_to'] ) ) : date_i18n( 'Y-m-d' );

		$refund_array = $this->pwddm_drivers_refund_query( $pwddm_dates_range_from, $pwddm_dates_range_to );
		$report_array = $this->pwddm_drivers_commission_query( $pwddm_dates_range_from, $pwddm_dates_range_to );
		$html         = '<h5>' . esc_html( __( 'Drivers commissions', 'pwddm' ) ) . '</h5>
	<div id="lddfw_dates_range_wrap" class="row">
	<div class="col-12">

	<form class="row g-3" method="POST" action="' . pwddm_manager_page_url( 'pwddm_screen=reports' ) . '">
	<div class="col-auto">
	  <label for="staticEmail2" >' . esc_html( __( 'Dates', 'pwddm' ) ) . '</label>
	</div>
	<div class="col-auto">
	<div id="lddfw_dates_range_select">
		<select class="form-select" name="pwddm_dates_range" id="pwddm_dates_range" >
			<option ' . selected( $pwddm_dates_range, 'today', false ) . ' fromdate="' . date_i18n( 'Y-m-d' ) . '" todate="' . date_i18n( 'Y-m-d' ) . '" value="today">' . esc_html( __( 'Today', 'pwddm' ) ) . '</option>
			<option ' . selected( $pwddm_dates_range, 'yesterday', false ) . ' fromdate="' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . '" todate="' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . '" value="yesterday">' . esc_html( __( 'Yesterday', 'pwddm' ) ) . '</option>
			<option ' . selected( $pwddm_dates_range, 'thismonth', false ) . '  fromdate="' . date_i18n( 'Y-m-d', strtotime( 'first day of this month' ) ) . '" todate="' . date_i18n( 'Y-m-d', strtotime( 'last day of this month' ) ) . '"  value="thismonth">' . esc_html( __( 'This month', 'pwddm' ) ) . '</option>
			<option ' . selected( $pwddm_dates_range, 'lastmonth', false ) . '  fromdate="' . date_i18n( 'Y-m-d', strtotime( 'first day of last month' ) ) . '" todate="' . date_i18n( 'Y-m-d', strtotime( 'last day of last month' ) ) . '"  value="lastmonth">' . esc_html( __( 'Last month', 'pwddm' ) ) . '</option>
			<option ' . selected( $pwddm_dates_range, 'custom', false ) . '  value="custom">' . esc_html( __( 'Custom', 'pwddm' ) ) . '</option>
		</select>
	</div>
	</div>

	<div class="col-auto" id="pwddm_dates_custom_range" style="display:none">
	<div   class="input-group ">
	<label>' . esc_html( __( 'From', 'pwddm' ) ) . '</label>&nbsp; <input type = "text" value="' . $pwddm_dates_range_from . '" class="pwddm-datepicker form-control " name="pwddm_dates_range_from" id = "pwddm_dates_range_from" >
	&nbsp;<label>' . esc_html( __( 'To', 'pwddm' ) ) . '</label>&nbsp; <input type = "text" value="' . $pwddm_dates_range_to . '" class="pwddm-datepicker form-control "  name="pwddm_dates_range_to" id = "pwddm_dates_range_to" >
	</div>
	</div>

	<div class="col-auto">
	<input class="btn btn-primary mb-2" type="submit" name="pwddm_submit" id="pwddm_dates_range_submit" class="button button-primary" value="' . esc_html( __( 'Send', 'pwddm' ) ) . '">
	</div>
  </form>


		</div>
		</div>
	<div class="table-responsive">
	<table class="table table-striped table-hover">
	<thead class="table-dark">
		<tr>
			<th class="manage-column column-primary ">' . esc_html( __( 'Drivers', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Orders', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Orders Total', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Shipping Total', 'pwddm' ) ) . '</td>
			<th class="manage-column column-primary text-center">' . esc_html( __( 'Commission', 'pwddm' ) ) . '</td>
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
		if ( empty( $report_array ) ) {
			$html .= '
		<tr>
			<td colspan="5" class="text-center">' . esc_html( __( 'No orders', 'pwddm' ) ) . '</td>
		</tr>';
		} else {
			foreach ( $report_array as $row ) {
				$driver_id = $row->driver_id;
				if ( $last_driver !== $driver_id ) {
					$driver          = get_userdata( $driver_id );
					$driver_name     = $driver->display_name;
					$driver_counter += 1;
					if ( pwddm_fs()->is__premium_only() ) {
						if ( pwddm_fs()->can_use_premium_code() ) {
							$orders_counter = $row->orders;
							$orders_price   = $row->orders_total;

							// Calculate refunds.
							if ( ! empty( $refund_array ) ) {
								foreach ( $refund_array as $refund_row ) {
									if ( $refund_row->driver_id === $driver_id ) {
										$orders_price = $orders_price - $refund_row->refund;
										break;
									}
								}
							}
							if ( pwddm_fs()->is__premium_only() ) {
								if ( pwddm_fs()->can_use_premium_code() ) {
									$shipping_price = $row->shipping_total;
									$commission     = $row->commission;

									$orders_counter_total += $orders_counter;
									$commission_total     += $commission;
									$orders_total         += $orders_price;
									$shipping_total       += $shipping_price;
								}
							}
						}
					}
					$last_driver = $driver_id;

					$html .= '
				<tr>
					<td class="title column-title has-row-actions column-primary" data-colname="' . esc_html( __( 'Driver', 'pwddm' ) ) . '" >' . $driver_name . '</td>
					<td class="text-center" data-colname="' . esc_html( __( 'Orders', 'pwddm' ) ) . '" >' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_dates_range=' . $pwddm_dates_range_from . ',' . $pwddm_dates_range_to . '&pwddm_orders_filter=' . esc_attr( $driver_id ) . '&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) ) . '">' . $orders_counter . '</a>' ) . '</td>
					<td class="text-center" data-colname="' . esc_html( __( 'Orders Price', 'pwddm' ) ) . '" > ' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_dates_range=' . $pwddm_dates_range_from . ',' . $pwddm_dates_range_to . '&pwddm_orders_filter=' . esc_attr( $driver_id ) . '&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) ) . '">' . wc_price( $orders_price ) . '</a>' ) . '</td>
					<td class="text-center" data-colname="' . esc_html( __( 'Shipping Price', 'pwddm' ) ) . '" > ' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_dates_range=' . $pwddm_dates_range_from . ',' . $pwddm_dates_range_to . '&pwddm_orders_filter=' . esc_attr( $driver_id ) . '&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) ) . '">' . wc_price( $shipping_price ) . '</a>' ) . '</td>
					<td class="text-center" data-colname="' . esc_html( __( 'Commission', 'pwddm' ) ) . '" > ' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_dates_range=' . $pwddm_dates_range_from . ',' . $pwddm_dates_range_to . '&pwddm_orders_filter=' . esc_attr( $driver_id ) . '&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) ) . '">' . wc_price( $commission ) . '</a>' ) . '</td>
				</tr>';
				}
			}
		}
		$html .= '</tbody>
		<tfoot class="table-secondary">
			<td class="title column-title has-row-actions column-primary" data-colname="' . esc_html( __( 'Driver', 'pwddm' ) ) . '" >' . $driver_counter . ' ' . esc_html( __( 'Drivers', 'pwddm' ) ) . '</td>
			<td class="text-center" data-colname="' . esc_html( __( 'Orders', 'pwddm' ) ) . '"> ' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=-1&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) ) . '&pwddm_dates_range=' . $pwddm_dates_range_from . ',' . $pwddm_dates_range_to . '">' . $orders_counter_total . '</a>' ) . '</td>
			<td class="text-center" data-colname="' . esc_html( __( 'Orders Price', 'pwddm' ) ) . '"> ' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=-1&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) ) . '&pwddm_dates_range=' . $pwddm_dates_range_from . ',' . $pwddm_dates_range_to . '">' . wc_price( $orders_total ) . '</a>' ) . '</td>
			<td class="text-center" data-colname="' . esc_html( __( 'Shipping Price', 'pwddm' ) ) . '"> ' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=-1&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) ) . '&pwddm_dates_range=' . $pwddm_dates_range_from . ',' . $pwddm_dates_range_to . '">' . wc_price( $shipping_total ) . '</a>' ) . '</td>
			<td class="text-center" data-colname="' . esc_html( __( 'Commission', 'pwddm' ) ) . '"> ' . pwddm_premium_feature( '<a href="' . pwddm_manager_page_url( 'pwddm_screen=orders&pwddm_orders_filter=-1&pwddm_orders_status=' . esc_attr( get_option( 'lddfw_delivered_status' ) ) ) . '&pwddm_dates_range=' . $pwddm_dates_range_from . ',' . $pwddm_dates_range_to . '">' . wc_price( $commission_total ) . '</a>' ) . '</td>
		</tfoot>
	</table></div>';
		return $html;
	}

	/**
	 * Admin dashboard screen.
	 *
	 * @since 1.1.0
	 * @return html
	 */
	public function screen_dashboard() {
		  $html  = '<div class="container">';
		  $html .= $this->drivers_orders_dashboard_report();
		  $html .= '<hr style="margin-top:30px; margin-bottom:30px">';
		  $html .= $this->claim_orders_dashboard_report();
		  $html .= '<hr style="margin-top:30px; margin-bottom:30px">';
		  $html .= $this->drivers_dashboard_report();
		  $html .= '</div>';
		return $html;
	}

	/**
	 * Admin report screen.
	 *
	 * @since 1.1.0
	 * @return html
	 */
	public function screen_reports() {
		$html  = '<div class="container">';
		$html .= $this->drivers_commissions_report();
		$html .= '
		</div>';
		return $html;
	}

	/**
	 * Drivers dashboard report.
	 *
	 * @since 1.1.0
	 * @return html
	 */
	public function drivers_dashboard_report() {
		global $pwddm_manager_id, $pwddm_manager_drivers;

		$drivers_array = PWDDM_Driver::pwddm_get_drivers( $pwddm_manager_id, 'all' );
		$drivers       = $drivers_array->get_results();

		$html = '
		<div class="row" style="margin-top:30px; margin-bottom:10px;">
		<div class="col-6">
			<h5 style="
			margin-top: 14px;
			margin-bottom: 10px;
		">' . esc_html( __( 'Active drivers', 'pwddm' ) ) . '</h5>
		</div>
		<div class="col-12 col-md-6">';
		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->is_plan( 'premium', true ) ) {
				if ( '1' === $pwddm_manager_drivers || '2' === $pwddm_manager_drivers ) {
					$html .= '<a style="float:right; margin-left:10px;"  href="' . pwddm_manager_page_url( 'pwddm_screen=newdriver' ) . '" class="btn btn-primary" >' . esc_html( __( 'Add new driver', 'pwddm' ) ) . '</a>';
				}
			}
		}
		$html        .= '<a style="float:right" class="btn btn-secondary" href="' . pwddm_manager_page_url( 'pwddm_screen=drivers' ) . '">' . esc_html( __( 'All drivers', 'pwddm' ) ) . '</a>
		</div>
		</div>
		<div class="table-responsive">
		<table class=" table  table-striped  ">
		<thead class="table-dark">
			<tr>
				<th class="manage-column column-primary " style="min-width:150px">' . esc_html( __( 'Drivers', 'pwddm' ) ) . '</td>
				<th>' . esc_html( __( 'Phone', 'pwddm' ) ) . '</td>
				<th>' . esc_html( __( 'Email', 'pwddm' ) ) . '</td>
				<th>' . esc_html( __( 'Address', 'pwddm' ) ) . '</td>
				<th class="manage-column column-primary text-center">' . esc_html( __( 'Availability', 'pwddm' ) ) . '</td>
				<th class="manage-column column-primary text-center">' . esc_html( __( 'Claim orders', 'pwddm' ) ) . '</td>
				<th class="text-center">' . esc_html( __( 'Manager', 'pwddm' ) ) . '</td>
			</tr>
		</thead>
		<tbody>';
		$total_driver = 0;

		if ( empty( $drivers ) ) {
			$html .= '
			<tr>
				<td colspan="7" class="text-center">' . esc_html( __( 'No drivers', 'pwddm' ) ) . '</td>
			</tr>';
		} else {
			foreach ( $drivers as $driver ) {

				/**
				 * Driver data.
				 */
				$driver_id            = $driver->ID;
				$manager_id           = get_user_meta( $driver_id, 'pwddm_manager', true );
				$manager_name         = get_user_meta( $manager_id, 'billing_first_name', true ) . ' ' . get_user_meta( $manager_id, 'billing_last_name', true );
				$pwddm_driver_account = get_user_meta( $driver_id, 'lddfw_driver_account', true );

				if ( '1' === $pwddm_driver_account &&
						(
							( ( $pwddm_manager_drivers === '0' || strval( $pwddm_manager_drivers ) === '' ) && ( strval( $manager_id ) === '' ) )
							||
							( $pwddm_manager_drivers === '2' && strval( $pwddm_manager_id ) === $manager_id )
							||
							( $pwddm_manager_drivers === '1' && ( strval( $pwddm_manager_id ) === $manager_id || ( strval( $manager_id === '' ) ) ) )
					)

				) {
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

					if ( pwddm_fs()->is__premium_only() ) {
						if ( pwddm_fs()->can_use_premium_code() ) {
							/**
							 * Driver status icons and counters.
							 */
							if ( '1' === $availability ) {
								$availability_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class=" text-success svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';
							} else {
								$availability_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class=" text-danger svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';
							}

							if ( '1' === $driver_claim ) {
								$driver_claim_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class=" text-success svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';
							} else {
								$driver_claim_icon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle" class=" text-danger svg-inline--fa fa-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8z"></path></svg>';
							}
						}
					}
						$icon_class = ( strval( $manager_id ) === strval( $pwddm_manager_id ) ) ? 'pwddm_user_icon' : 'pwddm_user_icon_disable';
					$html          .= '
				<tr>
					<td class="title column-title has-row-actions column-primary" data-colname="' . esc_html( __( 'Driver', 'pwddm' ) ) . '" >
						<a href="' . esc_attr( pwddm_manager_page_url( 'pwddm_screen=driver&pwddm_driverid=' . $driver_id ) ) . '">' . esc_html( $full_name ) . '</a>
					</td>
					<td data-colname="' . esc_html( __( 'Phone', 'pwddm' ) ) . '"><a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a></td>
					<td data-colname="' . esc_html( __( 'Email', 'pwddm' ) ) . '" ><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></td>
					<td data-colname="' . esc_html( __( 'Address', 'pwddm' ) ) . '">' . $billing_address . '</td>
					<td class="text-center">' . pwddm_premium_feature( '<a href="#" class="' . $icon_class . ' pwddm_availability" service="pwddm_availability" driver_id="' . esc_attr( $driver_id ) . '">' . $availability_icon . '</a>' ) . '</td>
					<td class="text-center">' . pwddm_premium_feature( '<a href="#" class="' . $icon_class . ' pwddm_claim_permission" service="pwddm_claim_permission" driver_id="' . esc_attr( $driver_id ) . '">' . $driver_claim_icon . '</a>' ) . '</td>
					<td class="text-center">' . $manager_name . '</td>
				</tr>';
				}
			}
		}
		$html     .= '</tbody>
				<tfoot class="table-secondary">
					<td class="title column-title has-row-actions column-primary">' . $total_driver . ' ' . esc_html( __( 'Drivers', 'pwddm' ) ) . '</td>
					<td></td>
					<td></td>
					<td></td>
					<td class = "text-center">' . pwddm_premium_feature( '<span id="pwddm_available_counter"></span> ' . esc_html( __( 'Availables', 'pwddm' ) ) . ' |  <span id="pwddm_unavailable_counter"></span> ' . esc_html( __( 'Unavailables', 'pwddm' ) ) ) . '</td>
					<td class = "text-center">' . pwddm_premium_feature( '<span id="pwddm_claim_counter"></span> ' . esc_html( __( 'Can claim', 'pwddm' ) ) . ' | <span id="pwddm_unclaim_counter"></span> ' . esc_html( __( 'Can\'t claim', 'pwddm' ) ) ) . '</td>
					<td></td>
				</tfoot>
			</table></div>';
			$html .= '
			<hr style="margin-top:30px; margin-bottom:30px">
			<div class="driver_app row">
					<div class="col-2 col-md-1">
					<img alt="' . esc_attr( 'Drivers app', 'pwddm' ) . '" title="' . esc_attr( 'Drivers app', 'pwddm' ) . '" src="' . esc_attr( plugins_url() . '/' . PWDDM_FOLDER . '/public/images/drivers_app.png?ver=' . PWDDM_VERSION ) . '">
					</div>
					<div class="col-10 col-md-11">
						<b><a style="color:#7191ea" target="_blank" href="' . lddfw_drivers_page_url( '' ) . '">' . lddfw_drivers_page_url( '' ) . '</a></b><br>' .
						 sprintf( esc_html( __( 'The link above is the delivery driver\'s Mobile-Friendly panel URL. %1$s The delivery drivers can access it from their mobile phones. %2$s', 'pwddm' ) ), '<br>', '<br>' ) .
						 sprintf( esc_html( __( 'Notice: If you want to be logged in as an manager and to check the drivers\' panel on the same device, %1$s %2$syou must work with two different browsers otherwise you will log out from the manager panel and the drivers\' panel won\'t function correctly.%3$s', 'pwddm' ) ), '<br>', '<b>', '</b>' ) . '
					 </div>
				</div>
				';

			return $html;
	}


	/**
	 * Admin dashboard screen.
	 *
	 * @return html
	 */
	public function admin_screen_dashboard() {
		echo '<div class="wrap">
		<h1 class="wp-heading-inline">' . esc_html( __( 'Dashboard', 'pwddm' ) ) . '</h1>
		  ' . pwddm_Admin::pwddm_admin_plugin_bar() . '
		  <hr class="wp-header-end">';
		 echo $this->admin_manager_orders_dashboard_report();
		 echo $this->admin_managers_dashboard_report();

		  echo '
		</div>';
	}

	/**
	 * manager status orders.
	 *
	 * @param  mixed $manager_id .
	 * @param  mixed $status .
	 * @param  mixed $array .
	 * @return statement
	 */
	public function manager_status_orders( $manager_id, $status, $array ) {
		$orders = 0;
		foreach ( $array as $row ) {
			if ( '' === $manager_id ) {

				if ( $row->post_status === $status ) {
					$orders = $row->orders;
					break;
				}
			} else {
				if ( $row->post_status === $status && $manager_id === $row->manager_id ) {
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
	public function admin_manager_orders_dashboard_report() {
		global $wpdb;

		if ( pwddm_is_hpos_enabled() ) {
			// Query adapted for HPOS-enabled environments.
			$report_array = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT
					u.id as manager_id, wo.status as post_status, u.display_name as manager_name, count(wo.id) as orders
					FROM ' . $wpdb->prefix . 'wc_orders wo
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON wo.id = o.order_id
					INNER JOIN ' . $wpdb->prefix . 'usermeta um ON um.user_id = o.driver_id and um.meta_key = \'pwddm_manager\'
					INNER JOIN ' . $wpdb->base_prefix . 'users u ON u.id = um.meta_value
					WHERE o.driver_id > 0 and
					wo.type = \'shop_order\' AND
					(
						wo.status in (%s,%s,%s) OR
						( wo.status = %s AND CAST(o.delivered_date AS DATE) BETWEEN %s AND %s )
					)
					group by u.id, wo.status
					order by u.id',
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

			$report_array = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT
				u.id manager_id, post_status, u.display_name manager_name , count(p.ID) as orders
				FROM ' . $wpdb->prefix . 'posts p
				INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON p.ID = o.order_id
				INNER JOIN ' . $wpdb->prefix . 'usermeta um ON um.user_id = o.driver_id and um.meta_key = \'pwddm_manager\'
				INNER JOIN ' . $wpdb->base_prefix . 'users u ON u.id = um.meta_value
				WHERE o.driver_id > 0 and
				p.post_type = \'shop_order\' AND
				(
					post_status in (%s,%s,%s) OR
					( post_status = %s AND CAST(delivered_date AS DATE) BETWEEN %s AND %s )
				)
				group by u.id, post_status
				order by u.id',
					array(
						get_option( 'lddfw_driver_assigned_status', '' ),
						get_option( 'lddfw_out_for_delivery_status', '' ),
						get_option( 'lddfw_failed_attempt_status', '' ),
						get_option( 'lddfw_delivered_status', '' ),
						date_i18n( 'Y-m-d' ),
						date_i18n( 'Y-m-d' ),
					)
				)
			); // db call ok; no
		}
		echo '<h2>' . esc_html( __( 'Manager drivers orders', 'pwddm' ) ) . '</h2>
		<table class="wp-list-table widefat fixed striped table-view-list posts">
		<thead>
			<tr>
				<th class="manage-column column-primary ">' . esc_html( __( 'Managers', 'pwddm' ) ) . '</td>
				<th class="manage-column column-primary pwddm-text-center ">' . esc_html( __( 'Phone', 'pwddm' ) ) . '</td>
				<th class="manage-column column-primary pwddm-text-center">' . esc_html( __( 'Driver assigned', 'pwddm' ) ) . '</td>
				<th class="manage-column column-primary pwddm-text-center">' . esc_html( __( 'Out for delivery', 'pwddm' ) ) . '</td>
				<th class="manage-column column-primary pwddm-text-center">' . esc_html( __( 'Delivered today', 'pwddm' ) ) . '</td>
				<th class="manage-column column-primary pwddm-text-center">' . esc_html( __( 'Failed delivery', 'pwddm' ) ) . '</td>
				<th class="manage-column column-primary pwddm-text-center">' . esc_html( __( 'Total', 'pwddm' ) ) . '</td>
			</tr>
		</thead>
		<tbody>';

		$lddfw_driver_assigned_status  = get_option( 'lddfw_driver_assigned_status', '' );
		$lddfw_out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
		$lddfw_failed_attempt_status   = get_option( 'lddfw_failed_attempt_status', '' );
		$lddfw_delivered_status        = get_option( 'lddfw_delivered_status', '' );

		$last_manager                  = '';
		$out_for_delivery_orders_total = 0;
		$driver_assigned_orders_total  = 0;
		$failed_attempt_orders_total   = 0;
		$delivered_orders_total        = 0;
		$total                         = 0;
		$manager_counter               = 0;
		$sub_total                     = 0;
		if ( empty( $report_array ) ) {
			echo '
		<tr>
			<td colspan="7" class="pwddm-text-center">' . esc_html( __( 'No orders', 'pwddm' ) ) . '</td>
		</tr>';
		} else {

			foreach ( $report_array as $row ) {
				$manager_id = $row->manager_id;
				if ( $last_manager !== $manager_id ) {

					++$manager_counter;

					$out_for_delivery_orders = '';
					$driver_assigned_orders  = '';
					$failed_attempt_orders   = '';
					$delivered_orders        = '';

					if ( pwddm_fs()->is__premium_only() ) {
						if ( pwddm_fs()->can_use_premium_code() ) {

							$out_for_delivery_orders = $this->manager_status_orders( $manager_id, $lddfw_out_for_delivery_status, $report_array );
							$driver_assigned_orders  = $this->manager_status_orders( $manager_id, $lddfw_driver_assigned_status, $report_array );
							$failed_attempt_orders   = $this->manager_status_orders( $manager_id, $lddfw_failed_attempt_status, $report_array );
							$delivered_orders        = $this->manager_status_orders( $manager_id, $lddfw_delivered_status, $report_array );

							$sub_total                      = $out_for_delivery_orders + $driver_assigned_orders + $failed_attempt_orders + $delivered_orders;
							$total                         += $sub_total;
							$out_for_delivery_orders_total += $out_for_delivery_orders;
							$driver_assigned_orders_total  += $driver_assigned_orders;
							$failed_attempt_orders_total   += $failed_attempt_orders;
							$delivered_orders_total        += $delivered_orders;

						}
					}

					$manager_name = $row->manager_name;
					$phone        = '';

					$last_manager = $manager_id;
					echo '
					<tr>
						<td class="title column-title has-row-actions column-primary" data-colname="' . esc_html( __( 'Manager', 'pwddm' ) ) . '" >
							' . $manager_name . '
						</td>
						<td class="pwddm-text-center" data-colname="' . esc_html( __( 'Phone', 'pwddm' ) ) . '"><a href="tel:' . $phone . '">' . $phone . '</a></td>
						<td class="pwddm-text-center" data-colname="' . esc_html( __( 'Driver assigned', 'pwddm' ) ) . '">' . pwddm_admin_premium_feature( $driver_assigned_orders ) . '</td>
						<td class="pwddm-text-center" data-colname="' . esc_html( __( 'Out for delivery', 'pwddm' ) ) . '">' . pwddm_admin_premium_feature( $out_for_delivery_orders ) . '</td>
						<td class="pwddm-text-center" data-colname="' . esc_html( __( 'Delivered today', 'pwddm' ) ) . '">' . pwddm_admin_premium_feature( $delivered_orders ) . '</td>
						<td class="pwddm-text-center" data-colname="' . esc_html( __( 'Failed delivery', 'pwddm' ) ) . '">' . pwddm_admin_premium_feature( $failed_attempt_orders ) . '</td>
						<td class="pwddm-text-center" data-colname="' . esc_html( __( 'Total', 'pwddm' ) ) . '">' . pwddm_admin_premium_feature( $sub_total ) . '</td>
					</tr>';
				}
			}
		}
		echo '</tbody>';

		if ( ! empty( $report_array ) ) {
			echo '	<tfoot>
					<td class="title column-title has-row-actions column-primary">' . $manager_counter . ' ' . esc_html( __( 'Managers', 'pwddm' ) ) . '</td>
					<td class="pwddm-text-center"> </td>
					<td class="pwddm-text-center">' . pwddm_admin_premium_feature( $driver_assigned_orders_total ) . '</td>
					<td class="pwddm-text-center">' . pwddm_admin_premium_feature( $out_for_delivery_orders_total ) . '</td>
					<td class="pwddm-text-center">' . pwddm_admin_premium_feature( $delivered_orders_total ) . '</td>
					<td class="pwddm-text-center">' . pwddm_admin_premium_feature( $failed_attempt_orders_total ) . '</td>
					<td class="pwddm-text-center">' . pwddm_admin_premium_feature( $total ) . '</td>
				</tfoot>';
		}

			echo '</table>';
	}
	/**
	 * Drivers dashboard report.
	 *
	 * @since 1.1.0
	 */
	public function admin_managers_dashboard_report() {
		$manager  = new PWDDM_Manger();
		$managers = $manager->pwddm_get_managers();

		echo '
		<h2 style="margin-bottom: 0px;margin-top: 28px;">' . esc_html( __( 'Active managers', 'pwddm' ) ) . '
		<a href="user-new.php" class="page-title-action" >' . esc_html( __( 'Add new manager', 'pwddm' ) ) . '</a>
		</h2>
		<ul class="subsubsub">
			<li class="all"><a href="users.php?role=Delivery_manager">' . esc_html( __( 'All managers', 'pwddm' ) ) . '</a></li>
		</ul>
		<table class="wp-list-table widefat fixed striped table-view-list posts">
		<thead>
			<tr>
				<th class="manage-column column-primary ">' . esc_html( __( 'Managers', 'pwddm' ) ) . '</td>
				<th>' . esc_html( __( 'Phone', 'pwddm' ) ) . '</td>
				<th>' . esc_html( __( 'Email', 'pwddm' ) ) . '</td>
				<th>' . esc_html( __( 'Address', 'pwddm' ) ) . '</td>
				<th class="manage-column column-primary pwddm-text-center">' . esc_html( __( 'Manage drivers', 'pwddm' ) ) . '</td>
			</tr>
		</thead>
		<tbody>';
		$total_managers = 0;

		if ( empty( $managers ) ) {
			echo '
			<tr>
				<td colspan="5" class="lddfw-text-center">' . esc_html( __( 'No managers', 'pwddm' ) ) . '</td>
			</tr>';
		} else {
			foreach ( $managers as $user ) {
				/**
				 * Manager data.
				 */
				$user_id               = $user->ID;
				$lddfw_manager_account = get_user_meta( $user_id, 'pwddm_manager_account', true );
				$pwddm_manager_drivers = get_user_meta( $user_id, 'pwddm_manager_drivers', true );

				if ( '1' === $lddfw_manager_account ) {
					$email             = $user->user_email;
					$full_name         = $user->display_name;
					$phone             = get_user_meta( $user_id, 'billing_phone', true );
					$billing_address_1 = get_user_meta( $user_id, 'billing_address_1', true );
					$billing_address_2 = get_user_meta( $user_id, 'billing_address_2', true );
					$billing_city      = get_user_meta( $user_id, 'billing_city', true );
					$billing_company   = get_user_meta( $user_id, 'billing_company', true );

					/**
					 * Manager billing address.
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

					$total_managers++;

					// Manager account type.
					if ( '1' === $pwddm_manager_drivers ) {
						$pwddm_manager_drivers_label = esc_html( __( 'Manager & Admin drivers', 'pwddm' ) );
					} elseif ( '2' === $pwddm_manager_drivers ) {
						$pwddm_manager_drivers_label = esc_html( __( 'Manager drivers only', 'pwddm' ) );
					} else {
						$pwddm_manager_drivers_label = esc_html( __( 'Admin drivers only', 'pwddm' ) );
					}

					echo '
						<tr>
							<td class="title column-title has-row-actions column-primary" data-colname="' . esc_html( __( 'Manager', 'pwddm' ) ) . '" >
								<a href="' . get_edit_user_link( $user_id ) . '">' . esc_html( $full_name ) . '</a>
							</td>
							<td data-colname="' . esc_html( __( 'Phone', 'pwddm' ) ) . '"><a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a></td>
							<td data-colname="' . esc_html( __( 'Email', 'pwddm' ) ) . '" ><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></td>
							<td data-colname="' . esc_html( __( 'Address', 'pwddm' ) ) . '">' . $billing_address . '</td>
							<td data-colname="' . esc_html( __( 'Drivers', 'pwddm' ) ) . '" class="pwddm-text-center">' . $pwddm_manager_drivers_label . '</td>
						</tr>';
				}
			}
		}
			echo '</tbody>
					<tfoot>
						<td class="title column-title has-row-actions column-primary">' . $total_managers . ' ' . esc_html( __( 'Managers', 'pwddm' ) ) . '</td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
					</tfoot>
				</table>';

			echo '<div class="manager_app">
					<img width=100 alt="' . esc_attr( 'Managers app', 'pwddm' ) . '" title="' . esc_attr( 'Managers app', 'pwddm' ) . '" src="' . esc_attr( plugins_url() . '/' . PWDDM_FOLDER . '/public/images/pwddm.png?ver=' . PWDDM_VERSION ) . '">
					<p>
						<b><a target="_blank" href="' . pwddm_manager_page_url( '' ) . '">' . pwddm_manager_page_url( '' ) . '</a></b><br>' .
						 esc_html( __( 'The link above is the delivery driver\'s manager url.', 'pwddm' ) ) . '<br>' .
						sprintf( esc_html( __( 'Notice: If you want to be logged in as an administrator and to check the managers\' panel on the same device, %1$s %2$syou must work with two different browsers otherwise you will log out from the admin panel and the managers\' panel won\'t function correctly.%3$s', 'pwddm' ) ), '<br>', '<b>', '</b>' ) . '
		 			</p>
				</div>
				';
	}
}









