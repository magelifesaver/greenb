<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://https://powerfulwp.com
 * @since      1.0.0
 *
 * @package    Scheduling_Deliveries_For_Delivery_Drivers
 * @subpackage Scheduling_Deliveries_For_Delivery_Drivers/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Scheduling_Deliveries_For_Delivery_Drivers
 * @subpackage Scheduling_Deliveries_For_Delivery_Drivers/public
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class Scheduling_Deliveries_For_Delivery_Drivers_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Scheduling_Deliveries_For_Delivery_Drivers_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Scheduling_Deliveries_For_Delivery_Drivers_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

	//	wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/scheduling-deliveries-for-delivery-drivers-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Scheduling_Deliveries_For_Delivery_Drivers_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Scheduling_Deliveries_For_Delivery_Drivers_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		//wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/scheduling-deliveries-for-delivery-drivers-public.js', array( 'jquery', 'jquery-ui-datepicker' ), $this->version, false );

	}

	public function driver_order_page_info( $order ) {

		$delivery_date = sdfdd_get_formatted_order_delivery_date_time( $order );

		if ( $delivery_date == '' ) {
			return;
		}

		$html = '<div class="col-12">
			<p id="lddfw_delivery_date">'
			. sdfdd_get_labeled_delivery_date( $delivery_date ) .
			'</p>
					</div>';
		return $html;
	}


	public function driver_orders_page_info( $order ) {

		$delivery_date = sdfdd_get_formatted_order_delivery_date_time( $order );

		if ( $delivery_date == '' ) {
			return;
		}

		$html = ' <div class="lddfw_delivery_date" style="padding-left: 45px;">'
			. sdfdd_get_labeled_delivery_date( $delivery_date ) .
			'</div>';
		return $html;
	}

	public function orders_date_time_filter() {
		global $lddfw_dates, $lddfw_screen;

		$from_date = '';
		$to_date   = '';
		$is_custom = false;

		if ( '' !== $lddfw_dates ) {
			$lddfw_dates_array = explode( ',', $lddfw_dates );
			if ( count( $lddfw_dates_array ) > 1 ) {
				$from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
				$to_date   = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[1] ) );
			} else {
				$from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
				$to_date   = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
			}
			$is_custom = true;
		}

		// This week dates.
		$current_week       = get_weekstartend( date_i18n( 'Y-m-d' ), '' );
		$current_start_week = date( 'Y-m-d', $current_week['start'] );
		$current_end_week   = date( 'Y-m-d', $current_week['end'] );
		// Last week dates.
		$previous_start_week = date( 'Y-m-d', strtotime( $current_start_week . ' -7 day' ) );
		$previous_end_week   = date( 'Y-m-d', strtotime( $current_end_week . ' -7 day' ) );
		$html                = '<div class="col-12">' . esc_html__( 'Choose a Delivery Date:', 'scheduling-deliveries-for-delivery-drivers' ) . '
		 
			<select class="custom-select custom-select-lg" style="margin-bottom:15px;" name="lddfw_delivery_dates_range" id="lddfw_delivery_dates_range" >
				<option value="" >' . esc_html( __( 'All dates', 'scheduling-deliveries-for-delivery-drivers' ) ) . '</option>
				<option value="' . date_i18n( 'Y-m-d' ) . ',' . date_i18n( 'Y-m-d' ) . '" fromdate="' . date_i18n( 'Y-m-d' ) . '" todate="' . date_i18n( 'Y-m-d' ) . '" >' . esc_html( __( 'Today', 'scheduling-deliveries-for-delivery-drivers' ) ) . '</option>
				<option value="' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . '" fromdate="' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . '" todate="' . date_i18n( 'Y-m-d', strtotime( '-1 days' ) ) . '">' . esc_html( __( 'Yesterday', 'scheduling-deliveries-for-delivery-drivers' ) ) . '</option>
				<option value="' . $current_start_week . ',' . $current_end_week . '" fromdate="' . $current_start_week . '" todate="' . $current_end_week . '">' . esc_html( __( 'This week', 'scheduling-deliveries-for-delivery-drivers' ) ) . '</option>
				<option value="' . $previous_start_week . ',' . $previous_end_week . '" fromdate="' . $previous_start_week . '" todate="' . $previous_end_week . '">' . esc_html( __( 'Last week', 'scheduling-deliveries-for-delivery-drivers' ) ) . '</option>
				<option value="' . date_i18n( 'Y-m-d', strtotime( 'first day of this month' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( 'last day of this month' ) ) . '" fromdate="' . date_i18n( 'Y-m-d', strtotime( 'first day of this month' ) ) . '" todate="' . date_i18n( 'Y-m-d', strtotime( 'last day of this month' ) ) . '" >' . esc_html( __( 'This month', 'scheduling-deliveries-for-delivery-drivers' ) ) . '</option>
				<option value="' . date_i18n( 'Y-m-d', strtotime( 'first day of last month' ) ) . ',' . date_i18n( 'Y-m-d', strtotime( 'last day of last month' ) ) . '" fromdate="' . date_i18n( 'Y-m-d', strtotime( 'first day of last month' ) ) . '" todate="' . date_i18n( 'Y-m-d', strtotime( 'last day of last month' ) ) . '" >' . esc_html( __( 'Last month', 'scheduling-deliveries-for-delivery-drivers' ) ) . '</option>
				<option value="custom">' . esc_html( __( 'Date range', 'scheduling-deliveries-for-delivery-drivers' ) ) . '</option>
			</select>
			 
			';

		$html .= '	<div id="lddfw_delivery_dates_custom" style="display:none;">
			<div class="form-group row">
				<div class="col-12">
				<input placeholder="' . esc_attr__( 'From Date', 'scheduling-deliveries-for-delivery-drivers' ) . '" type="text" id="lddfw_delivery_from_date" class="form-control" name="lddfw_dates" value="' . esc_attr( $from_date ) . '" /> 
				</div>	
			</div>	
			<div class="form-group row">
			<div class="col-12">
				<input placeholder="' . esc_attr__( 'To Date', 'scheduling-deliveries-for-delivery-drivers' ) . '"  type="text" id="lddfw_delivery_to_date"  class="form-control" name="lddfw_dates" value="' . esc_attr( $to_date ) . '" /> 
				</div>
				</div>	
			<div class="form-group row">
			<div class="col-12">
				<button class="btn btn-block  btn-primary lddfw_loader_fixed" data-url="' . esc_attr( lddfw_drivers_page_url( 'lddfw_screen=' . $lddfw_screen ) ) . '" type="button" id="filter_button">' . esc_html__( 'Send', 'scheduling-deliveries-for-delivery-drivers' ) . '</button>
				</div>
				</div>	
			</div>
 
		</div>';

		// Display the selected delivery date range if it exists.
		if ( $from_date && $to_date ) {
			$html .= '<div class="col-12">' . esc_html__( 'Delivery Date:', 'scheduling-deliveries-for-delivery-drivers' ) . ' ' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $from_date ) ) ) . ' - ' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $to_date ) ) ) . '</div>';
		}
		// JavaScript for handling the custom date range picker.
		$html .= '<script>
			jQuery(document).ready(function($) {

				$("#lddfw_delivery_dates_range").change(
					function() {
						var $lddfw_this = $("#lddfw_delivery_dates_range");
					 
						if ($lddfw_this.val() == "custom") {
							$("#lddfw_delivery_from_date").val("");
							$("#lddfw_delivery_to_date").val("");
							$("#lddfw_delivery_dates_custom").show();
						} else {
							var lddfw_fromdate = $("option:selected", $lddfw_this).attr("fromdate");
							var lddfw_todate = $("option:selected", $lddfw_this).attr("todate");
							 
							$("#lddfw_delivery_from_date").datepicker("setDate", lddfw_fromdate);
							$("#lddfw_delivery_to_date").datepicker("setDate", lddfw_todate);
							$("#filter_button").trigger("click");
							 
						}
					}
				);

				if (typeof lddfw_dates !== "undefined") {
					if (lddfw_dates != "") {
					 jQuery("#lddfw_delivery_dates_range").val(lddfw_dates);
					 if (jQuery("#lddfw_delivery_dates_range").val() == "" || jQuery("#lddfw_delivery_dates_range").val() == null) {
						  jQuery("#lddfw_delivery_dates_range").val("custom");
						  $("#lddfw_delivery_dates_custom").show();

						}
					}
				} else {
				 	jQuery("#lddfw_delivery_dates_range").val("");
				}
 

				$("#lddfw_delivery_from_date, #lddfw_delivery_to_date").datepicker({ dateFormat: "yy-mm-dd" });
	 
				 $("#filter_button").on("click", function() {
					var fromDate = $("#lddfw_delivery_from_date").val();
					var toDate = $("#lddfw_delivery_to_date").val();
					var url = $(this).attr("data-url");

					if (fromDate || toDate) {
						url += "&lddfw_dates=";
						if (fromDate) {
							url += encodeURIComponent(fromDate);
						}
						if (toDate) {
							if (fromDate) url += ",";
							url += encodeURIComponent(toDate);
						}
					}

					lddfw_show_loader($(this));
					window.location.replace(url);
				});
			});
		</script>';

		return $html;
	}

	public function orders_query_filter( $query, $status, $driver_id ) {
		global $lddfw_dates, $wpdb;
		 
		if ( empty( $lddfw_dates ) ) {
			return $query;
		} else {
			$lddfw_dates_array = explode( ',', $lddfw_dates );
			if ( count( $lddfw_dates_array ) > 1 ) {
				$from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
				$to_date   = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[1] ) );
			} else {
				$from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
				$to_date   = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
			}

			if ( sdfdd_is_hpos_enabled() ) {
				// Query for HPOS-enabled environments using `wc_orders_meta`.
				$query = $wpdb->prepare(
					'SELECT o.ID FROM ' . $wpdb->prefix . 'wc_orders o 
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders lo ON o.id = lo.order_id
					INNER JOIN ' . $wpdb->prefix . 'wc_orders_meta om ON om.order_id = o.ID 
					WHERE o.type = \'shop_order\'
					AND o.status = %s
					AND lo.driver_id = %d
					AND om.meta_key = "_lddfw_delivery_date"
					AND CAST(om.meta_value AS DATE) BETWEEN %s AND %s
					GROUP BY o.id
					ORDER BY lo.order_sort, lo.order_shipping_city',
					array( $status, $driver_id, $from_date, $to_date )
				);
			} else {
				// Original query for non-HPOS environments.
				$query = $wpdb->prepare(
					'SELECT p.ID FROM ' . $wpdb->prefix . 'posts p 
					INNER JOIN ' . $wpdb->prefix . 'lddfw_orders o ON p.ID = o.order_id
					INNER JOIN ' . $wpdb->prefix . 'postmeta pm ON pm.post_id = p.ID 
					WHERE p.post_type = \'shop_order\'
					AND p.post_status = %s
					AND o.driver_id = %d
					AND pm.meta_key = "_lddfw_delivery_date"
					AND CAST(pm.meta_value AS DATE) BETWEEN %s AND %s
					GROUP BY p.ID
					ORDER BY o.order_sort, o.order_shipping_city',
					array( $status, $driver_id, $from_date, $to_date )
				);
			}
		}
		return $query;
	}

	public function claim_orders_filter( $params ) {

		global $lddfw_dates;

		if ( '' === $lddfw_dates ) {
			return $params;
		} else {
			$lddfw_dates_array = explode( ',', $lddfw_dates );
			if ( count( $lddfw_dates_array ) > 1 ) {
				$from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
				$to_date   = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[1] ) );
			} else {
				$from_date = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
				$to_date   = date_i18n( 'Y-m-d', strtotime( $lddfw_dates_array[0] ) );
			}
		}

		 // Add or modify the meta_query
		 $params['meta_query'][] = array(
			 'key'     => '_lddfw_delivery_date',
			 'value'   => array( $from_date, $to_date ), // Example date range
			 'compare' => 'BETWEEN',
			 'type'    => 'DATE',
		 );

		 return $params;
	}


}
