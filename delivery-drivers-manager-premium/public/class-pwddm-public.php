<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    PWDDM
 * @subpackage PWDDM/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    PWDDM
 * @subpackage PWDDM/public
 * @author     powerfulwp <cs@powerfulwp.com>
 */
class PWDDM_Public {


	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in PWDDM_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The PWDDM_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in PWDDM_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The PWDDM_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
	}


	/**
	 * Set the driver page.
	 *
	 * @since 1.0.0
	 */
	public function pwddm_page_template( $page_template ) {
		global $post;
		if ( $post->ID === intval( get_option( 'pwddm_manager_page', '' ) ) ) {
					$page_template = WP_PLUGIN_DIR . '/' . PWDDM_FOLDER . '/index.php';
		}
		return $page_template;
	}

	/**
	 * Set the driver page.
	 *
	 * @since 1.0.0
	 */
	public function pwddm_page_template_redirect() { 
		global $post;
		if ( ! empty( $post ) ) {
			$template_path = '';

			if ( $post->ID === intval( get_option( 'pwddm_manager_page', '' ) ) ) {
				$this->pwddm_initialize_panel_data_globals();
				$template_path = WP_PLUGIN_DIR . '/' . PWDDM_FOLDER . '/index.php';
			}  

			if ( ! empty( $template_path ) && file_exists( $template_path ) ) {
				include( $template_path );
				exit();
			}
		}
		// No return needed as we exit if a template is found and included.
	}

	/**
	 * Initialize the panel data globals.
	 *
	 * @since 1.0.0
	 */
	public function pwddm_initialize_panel_data_globals() {
		global $pwddm_screen;
		global $pwddm_order_id;
		global $pwddm_reset_key;
		global $pwddm_page;
		global $pwddm_reset_login;
		global $pwddm_dates;
		global $pwddm_driverid;
		global $pwddm_status;
		global $pwddm_manager_id;
		global $pwddm_manager_name;
		global $pwddm_manager_account;
		global $pwddm_manager_drivers;
		global $pwddm_user;
		global $pwddm_manager_assigned_status_name;
		global $pwddm_out_for_delivery_status_name;
		global $pwddm_failed_attempt_status_name;
		global $pwddm_delivered_counter;
		global $pwddm_assign_to_driver_counter;
		global $pwddm_claim_orders_counter;
		global $pwddm_failed_attempt_counter;
		global $pwddm_out_for_delivery_counter;


		$pwddm_screen      = ( '' !== get_query_var( 'pwddm_screen' ) ) ? get_query_var( 'pwddm_screen' ) : 'dashboard';
		$pwddm_order_id    = get_query_var( 'pwddm_orderid' );
		$pwddm_reset_key   = get_query_var( 'pwddm_reset_key' );
		$pwddm_page        = ( '' !== get_query_var( 'pwddm_page' ) ) ? get_query_var( 'pwddm_page' ) : '1';
		$pwddm_reset_login = get_query_var( 'pwddm_reset_login' );
		$pwddm_dates       = get_query_var( 'pwddm_dates' );
		$pwddm_driverid    = get_query_var( 'pwddm_driverid' );
		$pwddm_status      = get_query_var( 'pwddm_status' );

		$pwddm_user            = wp_get_current_user();
	    $pwddm_manager_id      = $pwddm_user->ID;
		$pwddm_manager_name    = $pwddm_user->display_name;

		$pwddm_manager_account = get_user_meta( $pwddm_manager_id, 'pwddm_manager_account', true );
		$pwddm_manager_drivers = get_user_meta( $pwddm_manager_id, 'pwddm_manager_drivers', true );
	
		 	// Get the number of orders in each status.
		$pwddm_orders                   = new PWDDM_Orders();
		$pwddm_array                    = $pwddm_orders->pwddm_orders_count_query( $pwddm_manager_id );
		$pwddm_out_for_delivery_counter = 0;
		$pwddm_failed_attempt_counter   = 0;
		$pwddm_delivered_counter        = 0;
		$pwddm_assign_to_driver_counter = 0;
		$pwddm_claim_orders_counter     = 0;

		/**
		 * Set current status names
		 */
		$pwddm_manager_assigned_status_name = esc_html( __( 'Driver assigned', 'pwddm' ) );
		$pwddm_out_for_delivery_status_name = esc_html( __( 'Out for delivery', 'pwddm' ) );
		$pwddm_failed_attempt_status_name   = esc_html( __( 'Failed delivery', 'pwddm' ) );
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
							if ( $status !== esc_html( __( 'Failed Delivery Attempt', 'pwddm' ) ) ) {
								$pwddm_failed_attempt_status_name = $status;
							}
							break;
						case get_option( 'lddfw_driver_assigned_status' ):
							if ( $status !== $pwddm_manager_assigned_status_name ) {
								$pwddm_manager_assigned_status_name = $status;
							}
							break;
					}
				}
			}
		}


		foreach ( $pwddm_array as $row ) {

			switch ( $row->post_status ) {
				case get_option( 'lddfw_out_for_delivery_status' ):
					$pwddm_out_for_delivery_counter = $row->orders;
					break;
				case get_option( 'lddfw_failed_attempt_status' ):
					$pwddm_failed_attempt_counter = $row->orders;
					break;
				case get_option( 'lddfw_delivered_status' ):
					$pwddm_delivered_counter = $row->orders;
					break;
				case get_option( 'lddfw_driver_assigned_status' ):
					$pwddm_assign_to_driver_counter = $row->orders;
					break;
			}
		}


	}

}
