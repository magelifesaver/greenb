<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    LDDFW
 * @subpackage LDDFW/admin
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Admin {
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
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in LDDFW_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The LDDFW_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'lddfw-reports' === $page ) {
			wp_enqueue_style( 'lddfw-jquery-ui', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css', array(), $this->version, 'all' );
		}

		 
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/lddfw-admin.css', array(), $this->version, 'all' );


		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				$lddfw_screen = get_current_screen();
				if ( 'user-edit' === $lddfw_screen->base ) {
					wp_enqueue_style( 'lddfw-tagify', plugins_url() . '/' . LDDFW_FOLDER  . '/public/css/tagify.css', array(), $this->version, 'all' );
				}
			}
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in LDDFW_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The LDDFW_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$script_array = array( 'jquery' );
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				$lddfw_screen = get_current_screen();
				$tab          = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
				if ( 'user-edit' === $lddfw_screen->base || 'lddfw-branding' === $tab ) {
					// add media script.
					wp_enqueue_media();
				}
				if ( 'lddfw-branding' === $tab ) {
					// add color picker script and media.
					$script_array = array( 'jquery', 'wp-color-picker' );
				}

				// Add tagify script for driver cities.
				if ( 'user-edit' === $lddfw_screen->base ) {
					 
					// Enqueue Tagify Polyfills JS (for broader browser compatibility).
					wp_enqueue_script(
						'lddfw-tagify-polyfills-js',
						plugins_url() . '/' . LDDFW_FOLDER  . '/public/js/tagify.polyfills.js',
						array(),  
						$this->version,
						true // Load in footer.
					);
					// Enqueue Tagify Core JS.
					wp_enqueue_script(
						'lddfw-tagify-js',
						plugins_url() . '/' . LDDFW_FOLDER  . '/public/js/tagify.js',
						array(),  
						$this->version,
						true // Load in footer.
					);
				}

			}
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'lddfw-reports' === $page ) {
			// Add date picker script.
			$script_array = array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' );
		}

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/lddfw-admin.js', $script_array, $this->version, false );
		wp_localize_script( $this->plugin_name, 'lddfw_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_localize_script( $this->plugin_name, 'lddfw_nonce', array( 'nonce' => esc_js( wp_create_nonce( 'lddfw-nonce' ) ) ) );
	}

	/**
	 * Service that update order status to out for delivery.
	 *
	 * @since 1.0.0
	 * @param int $driver_id ID of the user.
	 * @return json
	 */
	public function lddfw_out_for_delivery_service( $driver_id ) {
		$result = 0;
		$error  = __( 'An error occurred.', 'lddfw' );
		$user   = new WP_User( $driver_id, '', get_current_blog_id() );
		if ( in_array( 'driver', (array) $user->roles, true ) ) {
			// Security check.
			if ( isset( $_POST['lddfw_wpnonce'] ) ) {

					// Get list of orders.
					$orders_list = ( isset( $_POST['lddfw_orders_list'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_orders_list'] ) ) : '';
				if ( '' !== $orders_list ) {
					$orders_list_array = explode( ',', $orders_list );
					foreach ( $orders_list_array as $order_id ) {
						if ( '' !== $order_id ) {
							$order                   = wc_get_order( $order_id );
							$order_driverid          = $order->get_meta( 'lddfw_driverid' );
							$out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
							$driver_assigned_status  = get_option( 'lddfw_driver_assigned_status', '' );
							$current_order_status    = 'wc-' . $order->get_status();
							// Check if order belongs to driver and status is processing.
							if ( intval( $order_driverid ) === intval( $driver_id ) && $current_order_status === $driver_assigned_status ) {
								// Update order status.
								$order->update_status( $out_for_delivery_status, __( 'The delivery driver changed the order status.', 'lddfw' ) );
								$order->save();
								$result = 1;
								$error  = '<div class=\'alert alert-success alert-dismissible fade show\'>' . __( 'Orders successfully marked as out for delivery.', 'lddfw' ) . '<button type=\'button\' class=\'close\' data-dismiss=\'alert\' aria-label=\'Close\'><span aria-hidden=\'true\'>&times;</span></button></div> <a id=\'view_out_of_delivery_orders_button\' href=\'' . lddfw_drivers_page_url( 'lddfw_screen=out_for_delivery' ) . '\'  class=\'btn btn-lg lddfw_loader btn-block btn-primary\'>' . __( 'View out for delivery orders', 'lddfw' ) . '</a>';
							}
						}
					}
				} else {
					$error = __( 'Please choose the orders.', 'lddfw' );
				}
			}
		} else {
			$error = __( 'User is not a delivery driver', 'lddfw' );
		}
		return "{\"result\":\"$result\",\"error\":\"$error\"}";
	}

	/**
	 * Service that assign order to delivery driver.
	 *
	 * @since 1.0.0
	 * @param int $driver_id ID of the user.
	 * @return json
	 */
	public function lddfw_claim_orders_service__premium_only( $driver_id ) {
		$result = 0;
		$error  = __( 'An error occurred.', 'lddfw' );
		$user   = new WP_User( $driver_id, '', get_current_blog_id() );
		$driver = new LDDFW_Driver();
	
		if ( in_array( 'driver', (array) $user->roles, true ) ) {
			// Security check.
			if ( isset( $_POST['lddfw_wpnonce'] ) ) {
				$orders_list = ( isset( $_POST['lddfw_orders_list'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_orders_list'] ) ) : '';
	
				if ( '' !== $orders_list ) {
					$orders_list_array = explode( ',', $orders_list );
					$already_assigned_orders = [];
					$successfully_assigned_orders = [];
	
					foreach ( $orders_list_array as $order_id ) {
						if ( '' !== $order_id ) {
							$order = wc_get_order( $order_id );
	
							if ( false !== $order ) {
								$current_driver_id = $order->get_meta( 'lddfw_driverid' );
	
								if ( $current_driver_id ) {
									// Skip if already assigned to any driver.
									$already_assigned_orders[] = $order_id;
								} else {
									// Assign order to driver.
									$driver->assign_delivery_driver( $order_id, $driver_id, 'driver' );
									$successfully_assigned_orders[] = $order_id;
								}
							}
						}
					}
	
					// Determine the result and construct messages
					$view_orders_button = '<a id=\'view_assigned_orders_button\' href=\'' . lddfw_drivers_page_url( 'lddfw_screen=assign_to_driver' ) . '\' class=\'btn btn-lg lddfw_loader btn-block btn-primary\'>' . __( 'View assigned orders', 'lddfw' ) . '</a>';
	
					if ( empty( $successfully_assigned_orders ) ) {
						$result = 3;
						$warning_message  = __( 'No orders were assigned. All orders are already assigned to drivers.', 'lddfw' );
						$error  = '<div class=\'alert alert-warning alert-dismissible fade show\'>' . $warning_message . '<button type=\'button\' class=\'close\' data-dismiss=\'alert\' aria-label=\'Close\'><span aria-hidden=\'true\'>&times;</span></button></div>';
					} elseif ( ! empty( $successfully_assigned_orders ) && ! empty( $already_assigned_orders ) ) {
						$result = 2;
						$success_message = __( 'Some orders were successfully assigned to you.', 'lddfw' );
						$warning_message = __( 'However, some orders are already assigned to drivers and were skipped.', 'lddfw' );
						$error  = '<div class=\'alert alert-warning alert-dismissible fade show\'>' . $success_message . ' ' . $warning_message . '<button type=\'button\' class=\'close\' data-dismiss=\'alert\' aria-label=\'Close\'><span aria-hidden=\'true\'>&times;</span></button></div>' . $view_orders_button;
					} else {
						$result = 1;
						$success_message = __( 'All orders were successfully assigned to you.', 'lddfw' );
						$error  = '<div class=\'alert alert-success alert-dismissible fade show\'>' . $success_message . '<button type=\'button\' class=\'close\' data-dismiss=\'alert\' aria-label=\'Close\'><span aria-hidden=\'true\'>&times;</span></button></div>' . $view_orders_button;
					}
				} else {
					$error = __( 'Please choose the orders.', 'lddfw' );
				}
			}
		} else {
			$error = __( 'User is not a driver.', 'lddfw' );
		}
	
		return "{\"result\":\"$result\",\"error\":\"$error\"}";
	}
	

	/**
	 * The function that handles ajax requests.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function lddfw_ajax() {

		$lddfw_data_type = ( isset( $_POST['lddfw_data_type'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_data_type'] ) ) : '';
		$lddfw_obj_id    = ( isset( $_POST['lddfw_obj_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_obj_id'] ) ) : '';
		$lddfw_service   = ( isset( $_POST['lddfw_service'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_service'] ) ) : '';
		$lddfw_driver_id = ( isset( $_POST['lddfw_driver_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_id'] ) ) : '';
		$result          = 0;

		/**
		 * Security check.
		 */
		if ( ! isset( $_POST['lddfw_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lddfw_wpnonce'] ) ), 'lddfw-nonce' ) )   {
				$error = esc_js( __( 'Security Check Failure - This alert may occur when you are logged in as an administrator and as a delivery driver on the same browser and the same device. If you want to work on both panels please try to work with two different browsers.', 'lddfw' ) );
				if ( 'json' === $lddfw_data_type ) {
					echo "{\"result\":\"$result\",\"error\":\"$error\"}";
				} else {
					echo '<div class=\'alert alert-danger alert-dismissible fade show\'>' . $error . '<button type=\'button\' class=\'close\' data-dismiss=\'alert\' aria-label=\'Close\'><span aria-hidden=\'true\'>&times;</span></button></div>';
				}
				exit;
		}

		 

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				/* driver application service */
				if ( 'lddfw_application' === $lddfw_service ) {
					$application = new LDDFW_Application();
					echo $application->lddfw_application_send__premium_only();
				}
			}
		}

		/*
			Edit driver service.
		*/
		if ( 'lddfw_edit_driver' === $lddfw_service ) {
			$driver = new LDDFW_Driver();
			echo $driver->lddfw_edit_driver_service();
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {

				/* drivers routes service */
				if ( 'lddfw_start_delivery' === $lddfw_service ) {
					$lddfw_orderid = ( isset( $_POST['lddfw_orderid'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_orderid'] ) ) : '';
					$route         = new LDDFW_Route();
					echo $route->lddfw_start_delivery__premium_only( $lddfw_driver_id, $lddfw_orderid );
				}

				/* drivers routes service */
				if ( 'lddfw_drivers_routes' === $lddfw_service ) {
					$route = new LDDFW_Route();
					echo $route->lddfw_drivers_routes__premium_only( 0 );
				}

				// Set driver tracking position.
				if ( 'lddfw_driver_tracking_position' === $lddfw_service ) {
					$lddfw_latitude  = ( isset( $_POST['lddfw_latitude'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_latitude'] ) ) : '';
					$lddfw_longitude = ( isset( $_POST['lddfw_longitude'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_longitude'] ) ) : '';
					$lddfw_speed     = ( isset( $_POST['lddfw_speed'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_speed'] ) ) : '';
					if ( '' !== $lddfw_latitude && '' !== $lddfw_longitude ) {
						$tracking = new LDDFW_Tracking();
						echo $tracking->lddfw_set_driver_tracking_position( $lddfw_driver_id, $lddfw_latitude, $lddfw_longitude, $lddfw_speed );
					}
				}

				// Set tracking status.
				if ( 'lddfw_driver_tracking_status' === $lddfw_service ) {
					$lddfw_status = ( isset( $_POST['lddfw_status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_status'] ) ) : '';
					if ( '' !== $lddfw_status && '' !== $lddfw_driver_id ) {
						$tracking = new LDDFW_Tracking();
						echo $tracking->lddfw_driver_tracking_status( $lddfw_driver_id, $lddfw_status );
					}
				}

				/* Get drivers locations */
				if ( 'lddfw_drivers_locations' === $lddfw_service ) {
					$tracking = new LDDFW_Tracking();
					echo $tracking->lddfw_drivers_locations();
				}

				/* Delivery track */
				if ( 'lddfw_delivery_track' === $lddfw_service ) {
					$lddfw_orderid = ( isset( $_POST['lddfw_orderid'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_orderid'] ) ) : '';
					$tracking      = new LDDFW_Tracking();
					echo $tracking->lddfw_delivery_tracking__premium_only( $lddfw_orderid );
				}
			}
		}

		/* login driver service */
		if ( 'lddfw_login' === $lddfw_service ) {
			$login = new LDDFW_Login();
			echo $login->lddfw_login_driver();
		}

		/* send reset password link */
		if ( 'lddfw_forgot_password' === $lddfw_service ) {
			$password = new LDDFW_Password();
			echo $password->lddfw_reset_password();
		}

		/* Create a new password*/
		if ( 'lddfw_newpassword' === $lddfw_service ) {
			$password = new LDDFW_Password();
			echo $password->lddfw_new_password();
		}

		/*
		Log out driver.
		*/
		if ( 'lddfw_logout' === $lddfw_service ) {
			LDDFW_Login::lddfw_logout();
		}

		/*
			Check google keys service.
		*/
		if ( 'lddfw_check_google_keys' === $lddfw_service ) {
			$user = wp_get_current_user();
			// Check if user is admin.
			if ( in_array( 'administrator', (array) $user->roles, true ) ) {
				echo lddfw_check_server_google_keys( $lddfw_obj_id );
			}
		}

		/*
		Set driver account status.
		*/
		if ( 'lddfw_account_status' === $lddfw_service ) {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
			$user    = new WP_User( $user_id, '', get_current_blog_id() );

			// Switch to driver user if administrator is logged in.
			if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
				$user = new WP_User( $lddfw_driver_id, '', get_current_blog_id() );
			}
			// Check if user has a driver role.
			if ( in_array( 'driver', (array) $user->roles, true ) ) {
				$driver_id      = $user->ID;
				$account_status = ( isset( $_POST['lddfw_account_status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_account_status'] ) ) : '';
				update_user_meta( $driver_id, 'lddfw_driver_account', $account_status );
				$result = 1;
			}
			echo esc_html( $result );
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				/*
				Set driver claim permission.
				*/
				if ( 'lddfw_claim_permission' === $lddfw_service ) {
					$user    = wp_get_current_user();
					$user_id = $user->ID;
					$user    = new WP_User( $user_id, '', get_current_blog_id() );

					// Switch to driver user if administrator is logged in.
					if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
						$user = new WP_User( $lddfw_driver_id, '', get_current_blog_id() );
					}
					// Check if user has a driver role.
					if ( in_array( 'driver', (array) $user->roles, true ) ) {
						$driver_id = $user->ID;
						$claim     = ( isset( $_POST['lddfw_claim'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_claim'] ) ) : '';
						update_user_meta( $driver_id, 'lddfw_driver_claim', $claim );
						$result = 1;
					}
					echo esc_html( $result );
				}
			}
		}

		/*
		Set driver availability.
		*/
		if ( 'lddfw_availability' === $lddfw_service ) {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
			$user    = new WP_User( $user_id, '', get_current_blog_id() );

			// Switch to driver user if administrator is logged in.
			if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
				$user = new WP_User( $lddfw_driver_id, '', get_current_blog_id() );
			}
			// Check if user has a driver role.
			if ( in_array( 'driver', (array) $user->roles, true ) ) {
				$driver_id    = $user->ID;
				$availability = ( isset( $_POST['lddfw_availability'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_availability'] ) ) : '';
				update_user_meta( $driver_id, 'lddfw_driver_availability', $availability );
				$result = 1;
			}
			echo esc_html( $result );
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {

				// Set logo service.
				if ( 'lddfw_set_image' === $lddfw_service ) {
					$lddfw_image_id = ( isset( $_POST['lddfw_image_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_image_id'] ) ) : '';
					if ( '' !== $lddfw_image_id ) {
						$image = wp_get_attachment_image( filter_input( INPUT_POST, 'lddfw_image_id', FILTER_VALIDATE_INT ), 'medium', false, array() );
						$data  = array(
							'image' => $image,
						);
						wp_send_json_success( $data );
					} else {
						wp_send_json_error();
					}
				}

				// Plan route service.
				if ( 'lddfw_plain_route' === $lddfw_service ) {
					$user    = wp_get_current_user();
					$user_id = $user->ID;
					$user    = new WP_User( $user_id, '', get_current_blog_id() );

					// Switch to driver user if administrator is logged in.
					if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
						$user = new WP_User( $lddfw_driver_id, '', get_current_blog_id() );
					}
					// Check if user has a driver role.
					if ( in_array( 'driver', (array) $user->roles, true ) ) {
						$driver_id   = $user->ID;
						$route       = new LDDFW_Route();
						$origin      = ( isset( $_POST['lddfw_origin'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_origin'] ) ) : '';
						$destination = ( isset( $_POST['lddfw_destination'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_destination'] ) ) : '';
						echo $route->lddfw_plain_route__premium_only( $driver_id, $origin, $destination );
					}
				}

				// Get the next delivery in the route.
				if ( 'lddfw_next_delivery' === $lddfw_service ) {

					$user    = wp_get_current_user();
					$user_id = $user->ID;
					$user    = new WP_User( $user_id, '', get_current_blog_id() );

					// Switch to driver user if administrator is logged in.
					if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
						$user = new WP_User( $lddfw_driver_id, '', get_current_blog_id() );
					}
					// Check if user has driver role.
					if ( in_array( 'driver', (array) $user->roles, true ) ) {
						$driver_id = $user->ID;
						$route     = new LDDFW_Route();
						echo $route->lddfw_get_next_delivery__premium_only( $driver_id );
					}
				}

				// Set route origin.
				if ( 'lddfw_set_route' === $lddfw_service ) {
					$origin_map_address      = ( isset( $_POST['lddfw_origin_map_address'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_origin_map_address'] ) ) : '';
					$origin_address          = ( isset( $_POST['lddfw_origin_address'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_origin_address'] ) ) : '';
					$destination_map_address = ( isset( $_POST['lddfw_destination_map_address'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_destination_map_address'] ) ) : '';
					$destination_address     = ( isset( $_POST['lddfw_destination_address'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_destination_address'] ) ) : '';
					$route                   = new LDDFW_Route();
					$route_array             = array(
						'origin_map_address'      => $origin_map_address,
						'origin_address'          => $origin_address,
						'destination_map_address' => $destination_map_address,
						'destination_address'     => $destination_address,
						'date_created'            => date_i18n( 'Y-m-d H:i:s' ),
					);
					echo $route->lddfw_set_route__premium_only( $lddfw_driver_id, $route_array );
				}

				// Sort orders.
				if ( 'lddfw_sort_orders' === $lddfw_service ) {
					$origin  = ( isset( $_POST['lddfw_origin'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_origin'] ) ) : '';
					$user    = wp_get_current_user();
					$user_id = $user->ID;
					$user    = new WP_User( $user_id, '', get_current_blog_id() );

					// Switch to driver user if administrator is logged in.
					if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
						$user = new WP_User( $lddfw_driver_id, '', get_current_blog_id() );
					}
					// Check if user has a driver role.
					if ( in_array( 'driver', (array) $user->roles, true ) ) {
						$driver_id = $user->ID;
						$route     = new LDDFW_Route();
						echo $route->lddfw_sort_delivery__premium_only( $driver_id, $origin );
					}
				}

				/* claim orders service */
				if ( 'lddfw_claim_orders' === $lddfw_service ) {
					$user    = wp_get_current_user();
					$user_id = $user->ID;
					$user    = new WP_User( $user_id, '', get_current_blog_id() );

					// Switch to driver user if administrator is logged in.
					if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
						$user = new WP_User( $lddfw_driver_id, '', get_current_blog_id() );
					}
					// Check if user has a driver role.
					if ( in_array( 'driver', (array) $user->roles, true ) ) {
						$driver_id = $user->ID;
						echo $this->lddfw_claim_orders_service__premium_only( $driver_id );
					}
				}

				/* assign driver service */
				if ( 'lddfw_get_assign_to_driver' === $lddfw_service ) {

					$user    = wp_get_current_user();
					$user_id = $user->ID;
					$user    = new WP_User( $user_id, '', get_current_blog_id() );

					if ( intval( $user_id ) === intval( $lddfw_driver_id ) && in_array( 'driver', (array) $user->roles, true ) ) {
						// Check if user has a driver role.
						$orders = new LDDFW_Orders();
						echo $orders->lddfw_assign_to_driver( $lddfw_driver_id );
					}
				}

				/* Unassign driver service */
				if ( 'lddfw_unassign_driver' === $lddfw_service ) {
					$user    = wp_get_current_user();
					$user_id = $user->ID;
					$user    = new WP_User( $user_id, '', get_current_blog_id() );

					// Switch to driver user if administrator is logged in.
					if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
						$user = new WP_User( $lddfw_driver_id, '', get_current_blog_id() );
					}
					// Check if user has a driver role.
					if ( in_array( 'driver', (array) $user->roles, true ) ) {
						$driver_id = $user->ID;
						echo $this->lddfw_unassign_driver_service__premium_only( $driver_id );
					}
				}



				if ( 'lddfw_delete_delivery_image' === $lddfw_service ) {
					$delivery_image = ( isset( $_POST['lddfw_delivery_image_url'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_delivery_image_url'] ) ) : '';
					$order_id       = ( isset( $_POST['lddfw_order_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_order_id'] ) ) : '';
					$order 			= wc_get_order( $order_id );
					
					 
					
					if ( '' !== $delivery_image ) {
						$order_image = new LDDFW_Order();
						 
						if ( '' !== $delivery_image ) {
							 
							$result = $order_image->lddfw_delete_image_from_order__premium_only( $delivery_image, 'delivery_image', $order_id );
							 
							if ( $result === true ) {
								$result = 1;
							} else {
								$result = 0;
							}
							 
							 
						}
						$order->save();
						 
					}
					echo esc_html( $result );
				}
		
				if ( 'lddfw_save_delivery_image' === $lddfw_service ) {
					$delivery_image = ( isset( $_POST['lddfw_delivery_image_data'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_delivery_image_data'] ) ) : '';
					$order_id       = ( isset( $_POST['lddfw_order_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_order_id'] ) ) : '';
					$order 			= wc_get_order( $order_id );
					if ( '' !== $delivery_image ) {
						$order_image = new LDDFW_Order();
						 
						if ( '' !== $delivery_image ) {
							$result = $order_image->lddfw_add_image_to_order__premium_only( $delivery_image, 'delivery_image', $order_id );
							if ( $result === false ) {
								$result = 0;
							}
							 
							 
						}
						$order->save();
						 
					}
					echo esc_html( $result );
				}



			}
		}

		/* out for delivery service */
		if ( 'lddfw_out_for_delivery' === $lddfw_service ) {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
			$user    = new WP_User( $user_id, '', get_current_blog_id() );

				// Switch to driver user if administrator is logged in.
			if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
				$user = new WP_User( $lddfw_driver_id, '', get_current_blog_id() );
			}
			// Check if user has a driver role.
			if ( in_array( 'driver', (array) $user->roles, true ) ) {
				$driver_id = $user->ID;
				echo $this->lddfw_out_for_delivery_service( $driver_id );
			}
		}

		 

		 
	


		if ( 'lddfw_status' === $lddfw_service ) {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
			$user    = new WP_User( $user_id, '', get_current_blog_id() );


			// Switch to driver user if administrator is logged in.
			if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $lddfw_driver_id ) {
				$user = new WP_User( $lddfw_driver_id, '', get_current_blog_id() );
			}
			// Check if user has a driver role.
			if ( in_array( 'driver', (array) $user->roles, true ) ) {
				$order_id       = ( isset( $_POST['lddfw_order_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_order_id'] ) ) : '';
				$order_status   = ( isset( $_POST['lddfw_order_status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_order_status'] ) ) : '';
				$driver_id      = ( isset( $_POST['lddfw_driver_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_id'] ) ) : '';
				$note           = ( isset( $_POST['lddfw_note'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_note'] ) ) : '';
				$signature      = ( isset( $_POST['lddfw_signature'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_signature'] ) ) : '';
				
				/* Check if the variables are not empty */
				if ( '' !== $order_id && '' !== $order_status && '' !== $driver_id ) {

					$order                   = wc_get_order( $order_id );
					$order_driverid          = $order->get_meta( 'lddfw_driverid' );
					$out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
					$failed_attempt_status   = get_option( 'lddfw_failed_attempt_status', '' );
					$delivered_status        = get_option( 'lddfw_delivered_status', '' );
					$current_order_status    = 'wc-' . $order->get_status();

					/* Check if order belongs to driver and status is out for delivery */
					if ( intval( $order_driverid ) === intval( $driver_id ) && ( $current_order_status === $out_for_delivery_status || $current_order_status === $failed_attempt_status ) ) {
						/* Update order status */
						$status_note = esc_html__( 'Driver changed the order status.', 'lddfw' );
						if ( lddfw_fs()->is__premium_only() ) {
							if ( lddfw_fs()->is_plan( 'premium', true ) ) {
								/* translators: Driver name */
								$status_note = sprintf( esc_html__( 'Driver %s changed the order status.', 'lddfw' ), esc_html( $user->display_name ) );
							}
						}

						if ( '' !== $note ) {
							$driver_note = __( 'Driver note', 'lddfw' ) . ': ' . $note;
							if ( lddfw_fs()->is__premium_only() ) {
								if ( lddfw_fs()->is_plan( 'premium', true ) ) {
									/* translators: %s: driver name */
									$driver_note = sprintf( __( 'Driver %s note', 'lddfw' ), esc_html( $user->display_name ) ) . ': ' . $note;
								}
							}
							$order->update_meta_data( 'lddfw_driver_note', $note );
							$order->add_order_note( $driver_note );
						}

						if ( lddfw_fs()->is__premium_only() ) {
							if ( lddfw_fs()->is_plan( 'premium', true ) ) {

								if ( '' !== $signature ) {
									$order_image = new LDDFW_Order();
								    $order_image->lddfw_add_image_to_order__premium_only( $signature, 'signature', $order_id );
								}
							}
						}
 
						$order->save();
						$order = wc_get_order( $order_id );
						$order->update_status( $order_status, $status_note );
						$result = 1;
					}
				}
			}
			echo esc_html( $result );
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {

				if ( 'lddfw_get_drivers_list' === $lddfw_service ) {
					echo LDDFW_Driver::lddfw_driver_drivers_selectbox( LDDFW_Driver::lddfw_get_drivers(), '', $lddfw_obj_id, 'bulk' );
				}
			}
		}

		

		exit;
	}


	/**
	 * Service that Unassign the delivery driver.
	 *
	 * @param int $driver_id ID of the user.
	 * @return json
	 */
	public function lddfw_unassign_driver_service__premium_only( $driver_id ) {
		$result = 0;
		$error  = __( 'An error occurred.', 'lddfw' );
		$user   = new WP_User( $driver_id, '', get_current_blog_id() );
		if ( in_array( 'driver', (array) $user->roles, true ) ) {
			// Security check.
			if ( isset( $_POST['lddfw_wpnonce'] ) ) {
					// Get list of orders.
					$orders_list = ( isset( $_POST['lddfw_orders_list'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_orders_list'] ) ) : '';
				if ( '' !== $orders_list ) {
					$orders_list_array       = explode( ',', $orders_list );
					$lddfw_processing_status = get_option( 'lddfw_processing_status', '' );
					foreach ( $orders_list_array as $order_id ) {
						if ( '' !== $order_id ) {
							$order                  = wc_get_order( $order_id );
							$order_driverid         = $order->get_meta( 'lddfw_driverid' );
							$driver_assigned_status = get_option( 'lddfw_driver_assigned_status', '' );
							$current_order_status   = 'wc-' . $order->get_status();
							// Check if order belongs to driver and status is assigned to driver.
							if ( intval( $order_driverid ) === intval( $driver_id ) && $current_order_status === $driver_assigned_status ) {
								
								// Unassigned driver from order.
								$order->update_status( $lddfw_processing_status, __( 'The delivery driver was unassigned from the order.', 'lddfw' ) );

								$order->delete_meta_data( 'lddfw_driverid' );
								lddfw_update_sync_order( $order_id, 'lddfw_driverid', '0' );

								// Add unassigned driver details to the array.
								$unassigned_data = $order->get_meta( 'lddfw_unassigned', true );
								if ( ! is_array( $unassigned_data ) ) {
									$unassigned_data = [];
								}
								$unassigned_data[] = [
									'driver_id' => $driver_id,
									'date'      => date_i18n( 'Y-m-d H:i:s' ),
								];
								$order->update_meta_data( 'lddfw_unassigned', $unassigned_data );

								$order->save();

								// Delete driver cache.
								lddfw_delete_cache( 'driver', $driver_id );

								// Delete orders cache.
								lddfw_delete_cache( 'orders', '' );
 
								$result = 1;
								$error  = __( 'The order was successfully unassigned.', 'lddfw' );
							}
						}
					}
				} else {
					$error = __( 'Please choose an order.', 'lddfw' );
				}
			}
		} else {
			$error = __( 'User is not a delivery driver', 'lddfw' );
		}
		return "{\"result\":\"$result\",\"error\":\"$error\"}";
	}

	/**
	 * Changed status hook.
	 *
	 * @since 1.0.0
	 * @param int    $order_id order number.
	 * @param string $status_from order status from.
	 * @param string $status_to order status to.
	 * @param object $order order object.
	 * @return void
	 */
	public function lddfw_status_changed( $order_id, $status_from, $status_to, $order ) {

		// Insert order_id to sync table if not exist.
		if ( ! lddfw_is_order_already_exists( $order_id ) ) {
			lddfw_insert_orderid_to_sync_order( $order_id );
		}

		// Update sync table.
		lddfw_update_all_sync_order( $order );

		// Get order delivery driver.
		$order_driverid = $order->get_meta( 'lddfw_driverid' );

		// Delete driver cache.
		lddfw_delete_cache( 'driver', $order_driverid );

		// Delete orders cache.
		lddfw_delete_cache( 'orders', '' );

		if ( get_option( 'lddfw_processing_status', true ) === 'wc-' . $status_to ) {

			if ( lddfw_fs()->is__premium_only() ) {
				if ( lddfw_fs()->is_plan( 'premium', true ) ) {

					// Auto-assign delivery drivers.
					$lddfw_auto_assign_delivery_drivers = get_option( 'lddfw_auto_assign_delivery_drivers', '' );
					if ( '1' === $lddfw_auto_assign_delivery_drivers ) {
						// Check if order doesn't have a delivery driver assigned.
						if ( '' === $order_driverid || '-1' === $order_driverid ) {
							$drivers = new LDDFW_Driver();
							$drivers->auto_assign_delivery_drivers__premium_only( $order_id );

							// remove update order driver id action.

							LDDFW_MetaBoxes::set_allow_save_meta( false );

						}
					}
				}
			}
		}

		if ( get_option( 'lddfw_out_for_delivery_status', '' ) === 'wc-' . $status_to ) {
			if ( lddfw_fs()->is__premium_only() ) {
				if ( lddfw_fs()->is_plan( 'premium', true ) ) {

					$lddfw_whatsapp_out_for_delivery = get_option( 'lddfw_whatsapp_out_for_delivery', '' );
					if ( '1' === $lddfw_whatsapp_out_for_delivery ) {
						// Send whatsapp to cusomer.
						$whatsapp = new LDDFW_WHATSAPP();
						$result   = $whatsapp->lddfw_send_whatsapp_to_customer__premium_only( $order_id, $order, $status_to );
						$order->add_order_note( $result[1] );
					}

					$lddfw_sms_out_for_delivery = get_option( 'lddfw_sms_out_for_delivery', '' );
					if ( '1' === $lddfw_sms_out_for_delivery ) {
						// Send sms to cusomer.
						$sms    = new LDDFW_SMS();
						$result = $sms->lddfw_send_sms_to_customer__premium_only( $order_id, $order, $status_to );
						$order->add_order_note( $result[1] );
					}

					// Sent email template to wc-out-for-delivery status.
					if ( 'wc-out-for-delivery' === 'wc-' . $status_to ) {
						WC_Emails::instance();
						do_action( 'lddfw_out_for_delivery_email_notification', $order_id );
					}

					// Delete existing start delivery order meta.
					$order->delete_meta_data( '_lddfw_order_delivery_start' );
					$order->save();
				}
			}
		}

		if ( get_option( 'lddfw_delivered_status', '' ) === 'wc-' . $status_to ) {

			// Update delivered date.
			$order->update_meta_data( 'lddfw_delivered_date', date_i18n( 'Y-m-d H:i:s' ) );
			lddfw_update_sync_order( $order_id, 'lddfw_delivered_date', date_i18n( 'Y-m-d H:i:s' ) );

			if ( '' !== $order_driverid ) {

				// Delete route meta.
				$order->delete_meta_data( 'lddfw_order_origin' );

				$order->delete_meta_data( 'lddfw_order_sort' );
				lddfw_update_sync_order( $order_id, 'lddfw_order_sort', '0' );

				if ( lddfw_fs()->is__premium_only() ) {
					if ( lddfw_fs()->is_plan( 'premium', true ) ) {

						$lddfw_whatsapp_delivered = get_option( 'lddfw_whatsapp_delivered', '' );
						if ( '1' === $lddfw_whatsapp_delivered ) {
							// Send whatsapp to cusomer.
							$whatsapp = new LDDFW_WHATSAPP();
							$result   = $whatsapp->lddfw_send_whatsapp_to_customer__premium_only( $order_id, $order, $status_to );
							$order->add_order_note( $result[1] );
						}

						$lddfw_sms_delivered = get_option( 'lddfw_sms_delivered', '' );
						if ( '1' === $lddfw_sms_delivered ) {
							// Send sms to cusomer.
							$sms    = new LDDFW_SMS();
							$result = $sms->lddfw_send_sms_to_customer__premium_only( $order_id, $order, $status_to );
							$order->add_order_note( $result[1] );
						}

						$driver = new LDDFW_Driver();
						$driver->set_order_commission__premium_only( $order, $order_id );
						// Send email to admin when the order is delivered.
						WC_Emails::instance();
						do_action( 'lddfw_delivered_email_admin_notification', $order_id );
					}
				}
			}
			$order->save();
		}

		if ( get_option( 'lddfw_failed_attempt_status', '' ) === 'wc-' . $status_to ) {

			// Update failed attempt date.
			$order->update_meta_data( 'lddfw_failed_attempt_date', date_i18n( 'Y-m-d H:i:s' ) );

			// Delete route meta.
			$order->delete_meta_data( 'lddfw_order_origin' );

			$order->delete_meta_data( 'lddfw_order_sort' );

			lddfw_update_sync_order( $order_id, 'lddfw_order_sort', '0' );
			$order->save();
			if ( lddfw_fs()->is__premium_only() ) {
				if ( lddfw_fs()->is_plan( 'premium', true ) ) {

					$lddfw_whatsapp_not_delivered = get_option( 'lddfw_whatsapp_not_delivered', '' );
					if ( '1' === $lddfw_whatsapp_not_delivered ) {
						// Send whatsapp to cusomer.
						$whatsapp = new LDDFW_WHATSAPP();
						$result   = $whatsapp->lddfw_send_whatsapp_to_customer__premium_only( $order_id, $order, $status_to );
						$order->add_order_note( $result[1] );
					}

					$lddfw_sms_not_delivered = get_option( 'lddfw_sms_not_delivered', '' );
					if ( '1' === $lddfw_sms_not_delivered ) {
						// Send sms to cusomer.
						$sms    = new LDDFW_SMS();
						$result = $sms->lddfw_send_sms_to_customer__premium_only( $order_id, $order, $status_to );
						$order->add_order_note( $result[1] );
					}
				}
			}
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {

				// Sent email template to wc-failed-delivery status.
				if ( 'wc-failed-delivery' === 'wc-' . $status_to ) {
					WC_Emails::instance();
					do_action( 'lddfw_failed_delivery_email_notification', $order_id );
				}
			}
		}

	}

	/**
	 * Custom fields post type.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_custom_fields_posttype__premium_only() {
		// Set UI labels for Custom Post Type.
		$labels = array(
			'name'               => _x( 'Custom Fields', 'Post Type General Name', 'lddfw' ),
			'singular_name'      => _x( 'Custom Fields', 'Post Type Singular Name', 'lddfw' ),
			'menu_name'          => __( 'Custom Fields', 'lddfw' ),
			'parent_item_colon'  => __( 'Parent Rule', 'lddfw' ),
			'all_items'          => __( 'All Custom Fields', 'lddfw' ),
			'view_item'          => __( 'View Custom Fields', 'lddfw' ),
			'add_new_item'       => __( 'Add New Custom Fields', 'lddfw' ),
			'add_new'            => __( 'Add New', 'lddfw' ),
			'edit_item'          => __( 'Edit Custom Fields', 'lddfw' ),
			'update_item'        => __( 'Update Custom Fields', 'lddfw' ),
			'search_items'       => __( 'Search Custom Fields', 'lddfw' ),
			'not_found'          => __( 'Not Found', 'lddfw' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'lddfw' ),
		);

		// Set other options for Custom Post Type.
		$args = array(
			'label'               => __( 'Custom Fields', 'lddfw' ),
			'description'         => __( 'Custom Fields', 'lddfw' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'custom-fields' ),
			'taxonomies'          => array( 'lddfw_custom_fields_sections' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => false,
			'menu_position'       => 5,
			'can_export'          => false,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => false,
			'capability_type'     => 'page',
			'rewrite'             => false,
		);

		// Registering message post type.
		register_post_type( 'lddfw_custom_fields', $args );

		// Set UI labels for taxonomy.
		$labels = array(
			'all_items'          => __( 'All Sections', 'lddfw' ),
			'view_item'          => __( 'View section', 'lddfw' ),
			'add_new_item'       => __( 'Add New section', 'lddfw' ),
			'add_new'            => __( 'Add New', 'lddfw' ),
			'edit_item'          => __( 'Edit section', 'lddfw' ),
			'update_item'        => __( 'Update section', 'lddfw' ),
			'search_items'       => __( 'Search sections', 'lddfw' ),
			'not_found'          => __( 'Not Found', 'lddfw' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'lddfw' ),
		);

		/**
		 *  Register taxonomy.
		 */
		register_taxonomy(
			'lddfw_custom_fields_sections',
			array( 'lddfw_custom_fields' ),
			array(
				'show_in_menu'      => false,
				'hierarchical'      => false,
				'show_admin_column' => true,
				'label'             => __( 'Sections', 'lddfw' ),
				'labels'            => $labels,
				'singular_label'    => 'Section',
				'capabilities'      => array(
					'assign_terms' => 'manage_options',
					'edit_terms'   => 'none',
					'manage_terms' => 'none',
				),
				'rewrite'           => array(
					'slug'       => 'lddfw_custom_fields_sections',
					'with_front' => false,
				),
				'meta_box_cb'       => 'post_categories_meta_box',
			)
		);
		register_taxonomy_for_object_type( 'lddfw_custom_fields_sections', 'lddfw_custom_fields' );

		/**
		 *  If terms is empty, we create them.
		 */
		$terms = get_terms(
			array(
				'taxonomy'   => 'lddfw_custom_fields_sections',
				'hide_empty' => false,
			)
		);

		if ( empty( $terms ) ) {
			wp_insert_term( esc_html__( 'Orders - Driver Assigned', 'lddfw' ), 'lddfw_custom_fields_sections' );
			wp_insert_term( esc_html__( 'Orders - Out for Delivery', 'lddfw' ), 'lddfw_custom_fields_sections' );
			wp_insert_term( esc_html__( 'Orders - Failed Delivery', 'lddfw' ), 'lddfw_custom_fields_sections' );
			wp_insert_term( esc_html__( 'Orders - Delivered', 'lddfw' ), 'lddfw_custom_fields_sections' );
			wp_insert_term( esc_html__( 'Orders - Claim Orders', 'lddfw' ), 'lddfw_custom_fields_sections' );
			wp_insert_term( esc_html__( 'Order - Info', 'lddfw' ), 'lddfw_custom_fields_sections' );
			wp_insert_term( esc_html__( 'Order - Shipping Address', 'lddfw' ), 'lddfw_custom_fields_sections' );
			wp_insert_term( esc_html__( 'Order - Customer', 'lddfw' ), 'lddfw_custom_fields_sections' );
			wp_insert_term( esc_html__( 'Order - Billing Address', 'lddfw' ), 'lddfw_custom_fields_sections' );
			wp_insert_term( esc_html__( 'Order - Pickup', 'lddfw' ), 'lddfw_custom_fields_sections' );
		}
	}

	/**
	 * Driver pages post type.
	 *
	 * @since 1.6.7
	 */
	public function lddfw_driver_pages_posttype__premium_only() {
		// Set UI labels for Custom Post Type.
		$labels = array(
			'name'               => _x( 'Driver Pages', 'Post Type General Name', 'lddfw' ),
			'singular_name'      => _x( 'Driver Page', 'Post Type Singular Name', 'lddfw' ),
			'menu_name'          => __( 'Driver Pages', 'lddfw' ),
			'parent_item_colon'  => __( 'Parent Rule', 'lddfw' ),
			'all_items'          => __( 'All Items', 'lddfw' ),
			'view_item'          => __( 'View Items', 'lddfw' ),
			'add_new_item'       => __( 'Add New Item', 'lddfw' ),
			'add_new'            => __( 'Add New', 'lddfw' ),
			'edit_item'          => __( 'Edit', 'lddfw' ),
			'update_item'        => __( 'Update', 'lddfw' ),
			'search_items'       => __( 'Search', 'lddfw' ),
			'not_found'          => __( 'Not Found', 'lddfw' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'lddfw' ),
		);

		// Set other options for Custom Post Type.
		$args = array(
			'label'               => __( 'Driver Pages', 'lddfw' ),
			'description'         => __( 'Driver Pages', 'lddfw' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => false,
			'menu_position'       => 5,
			'can_export'          => false,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => false,
			'capability_type'     => 'page',
			'rewrite'             => false,
		);

		// Registering message post type.
		register_post_type( 'lddfw_driver_pages', $args );
	}

	/**
	 * Plugin status.
	 *
	 * @since 1.0.0
	 * @param array $statuses_array status array.
	 * @return array
	 */
	public function lddfw_order_statuses( $statuses_array ) {
		$lddfw_statuses = array();
		foreach ( $statuses_array as $key => $status ) {
			$lddfw_statuses[ $key ] = $status;
			if ( 'wc-processing' === $key ) {
				$lddfw_statuses['wc-driver-assigned']  = __( 'Driver Assigned', 'lddfw' );
				$lddfw_statuses['wc-out-for-delivery'] = __( 'Out for Delivery', 'lddfw' );
				$lddfw_statuses['wc-failed-delivery']  = __( 'Failed Delivery Attempt', 'lddfw' );
			}
		}
		return $lddfw_statuses;
	}

	/**
	 * Register new post status.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function lddfw_order_statuses_init() {
		register_post_status(
			'wc-out-for-delivery',
			array(
				'label'                     => __( 'Out for Delivery', 'lddfw' ),
				'public'                    => true,
				'show_in_admin_status_list' => true,
				'show_in_admin_all_list'    => true,
				'exclude_from_search'       => false,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Out for Delivery <span class="count">(%s)</span>', 'Out for Delivery <span class="count">(%s)</span>', 'lddfw' ),
			)
		);
		register_post_status(
			'wc-failed-delivery',
			array(
				'label'                     => __( 'Failed Delivery Attempt', 'lddfw' ),
				'public'                    => true,
				'show_in_admin_status_list' => true,
				'show_in_admin_all_list'    => true,
				'exclude_from_search'       => false,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Failed Delivery Attempt <span class="count">(%s)</span>', 'Failed Delivery Attempt <span class="count">(%s)</span>', 'lddfw' ),
			)
		);
		register_post_status(
			'wc-driver-assigned',
			array(
				'label'                     => __( 'Driver Assigned', 'lddfw' ),
				'public'                    => true,
				'show_in_admin_status_list' => true,
				'show_in_admin_all_list'    => true,
				'exclude_from_search'       => false,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Driver Assigned <span class="count">(%s)</span>', 'Driver Assigned <span class="count">(%s)</span>', 'lddfw' ),
			)
		);
	}


	function lddfw_sanitize_variables( $input ) {
		$new_input = array();
		if ( isset( $input['lddfw_proof_of_delivery_max_images'] ) ) {
			$new_input['lddfw_proof_of_delivery_max_images'] = absint( $input['lddfw_proof_of_delivery_max_images'] );
		}
		// Ensure the input is an array
		if ( ! is_array( $input ) ) {
			return [];
		}
	
		// Sanitize each variable
		$sanitized = array_map( 'sanitize_text_field', $input );
	

		return $sanitized;
	}

	/**
	 * Plugin register settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function lddfw_settings_init() {

		// Get settings tab.
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		register_setting( 'lddfw', 'lddfw_google_api_key' );
		register_setting( 'lddfw', 'lddfw_google_api_key_server' );
		register_setting( 'lddfw', 'lddfw_dispatch_phone_number' );
		register_setting( 'lddfw', 'lddfw_status_section' );
		register_setting( 'lddfw', 'lddfw_driver_assigned_status' );
		register_setting( 'lddfw', 'lddfw_out_for_delivery_status' );
		register_setting( 'lddfw', 'lddfw_delivered_status' );
		register_setting( 'lddfw', 'lddfw_failed_attempt_status' );
		register_setting( 'lddfw', 'lddfw_processing_status' );
		register_setting( 'lddfw', 'lddfw_delivery_drivers_page' );
		register_setting( 'lddfw-drivers-settings', 'lddfw_failed_delivery_reason_1' );
		register_setting( 'lddfw-sms-settings', 'lddfw_sms_api_auth_token' );
		register_setting( 'lddfw-whatsapp-settings', 'lddfw_whatsapp_api_auth_token' );
		register_setting( 'lddfw-branding', 'lddfw_branding_logo' );
		register_setting( 'lddfw-tracking', 'lddfw_tracking_page' );
		register_setting( 'lddfw', 'lddfw_store_address_longitude' );
		register_setting( 'lddfw', 'lddfw_store_address_latitude' );

		/**
		 * Update driver_assigned status if empty.
		 * This update will be removed in the future versions.
		 */
		$lddfw_driver_assigned_status = get_option( 'lddfw_driver_assigned_status', '' );
		if ( '' === $lddfw_driver_assigned_status ) {
			update_option( 'lddfw_driver_assigned_status', 'wc-driver-assigned' );
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				register_setting( 'lddfw-drivers-settings', 'lddfw_app_mode' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_failed_delivery_reason_2' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_failed_delivery_reason_3' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_failed_delivery_reason_4' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_failed_delivery_reason_5' );

				register_setting( 'lddfw-drivers-settings', 'lddfw_proof_of_delivery_photo' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_proof_of_delivery_signature' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_proof_of_delivery_one_mandatory' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_proof_of_delivery_max_images' );

				register_setting( 'lddfw-drivers-settings', 'lddfw_delivery_dropoff_1' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_delivery_dropoff_2' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_delivery_dropoff_3' );

				register_setting( 'lddfw-drivers-settings', 'lddfw_navigation_app' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_driver_commission_type' );
				register_setting( 'lddfw-tracking', 'lddfw_driver_photo_permission' );
				register_setting( 'lddfw-tracking', 'lddfw_driver_name_permission' );
				register_setting( 'lddfw-tracking', 'lddfw_driver_phone_permission' );

				$args = array(
					'type'              => 'number',
					'sanitize_callback' => array( $this, 'lddfw_driver_commission_value_sanitize__premium_only' ),
					'default'           => null,
				);
				register_setting( 'lddfw-drivers-settings', 'lddfw_driver_commission_value', $args );
				register_setting( 'lddfw-drivers-settings', 'lddfw_driver_commission_second_value', $args );

				register_setting( 'lddfw-drivers-settings', 'lddfw_self_assign_delivery_drivers' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_self_assign_limitation' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_driver_application' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_auto_assign_delivery_drivers' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_enable_virtual_items' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_auto_assign_method' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_auto_assign_suborders' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_driver_prices_permission' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_driver_products_permission' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_driver_commission_permission' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_driver_billing_permission' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_driver_customer_whatsapp_permission' );
				register_setting( 'lddfw-drivers-settings', 'lddfw_driver_customer_permission' );

				register_setting( 'lddfw-sms-settings', 'lddfw_sms_api_sid' );
				register_setting( 'lddfw-sms-settings', 'lddfw_sms_provider' );
				register_setting( 'lddfw-sms-settings', 'lddfw_sms_api_phone' );
				register_setting( 'lddfw-sms-settings', 'lddfw_sms_assign_to_driver' );
				register_setting( 'lddfw-sms-settings', 'lddfw_sms_assign_to_driver_template' );
				register_setting( 'lddfw-sms-settings', 'lddfw_sms_out_for_delivery' );
				register_setting( 'lddfw-sms-settings', 'lddfw_sms_out_for_delivery_template' );
				register_setting( 'lddfw-sms-settings', 'lddfw_sms_start_delivery' );
				register_setting( 'lddfw-sms-settings', 'lddfw_sms_start_delivery_template' );
				register_setting( 'lddfw-sms-settings', 'lddfw_sms_delivered' );
				register_setting( 'lddfw-sms-settings', 'lddfw_sms_delivered_template' );
				register_setting( 'lddfw-sms-settings', 'lddfw_sms_not_delivered' );
				register_setting( 'lddfw-sms-settings', 'lddfw_sms_not_delivered_template' );


				// Register settings for WhatsApp features
				register_setting( 'lddfw-whatsapp-settings', 'lddfw_whatsapp_api_sid' );
				register_setting( 'lddfw-whatsapp-settings', 'lddfw_whatsapp_provider' );
				register_setting( 'lddfw-whatsapp-settings', 'lddfw_whatsapp_api_phone' );
  

				// WhatsApp message settings with content SID and variables
				$message_settings = [
					'lddfw_whatsapp_assign_to_driver',
					'lddfw_whatsapp_out_for_delivery',
					'lddfw_whatsapp_start_delivery',
					'lddfw_whatsapp_delivered',
					'lddfw_whatsapp_not_delivered',
				];
				// Register message settings with content SID and variables
				foreach ( $message_settings as $setting ) {
					register_setting( 'lddfw-whatsapp-settings', $setting );
					register_setting( 'lddfw-whatsapp-settings', "{$setting}_content_sid" );
					register_setting( 'lddfw-whatsapp-settings', "{$setting}_variables", [
						'sanitize_callback' => [ $this, 'lddfw_sanitize_variables' ],
					] );
				}
 
				

				register_setting( 'lddfw-branding', 'lddfw_branding_background' );
				register_setting( 'lddfw-branding', 'lddfw_branding_text_color' );
				register_setting( 'lddfw-branding', 'lddfw_branding_button_color' );
				register_setting( 'lddfw-branding', 'lddfw_branding_button_background' );
				register_setting( 'lddfw-branding', 'lddfw_branding_title' );
				register_setting( 'lddfw-branding', 'lddfw_branding_subtitle' );

				register_setting( 'lddfw-tracking', 'lddfw_drivers_tracking_timing' );
				register_setting( 'lddfw-tracking', 'lddfw_drivers_tracking_interval' );
				register_setting( 'lddfw-tracking', 'lddfw_add_time_to_eta' );
			}
		}

		if ( 'lddfw-tracking' === $tab ) {

			if ( lddfw_fs()->is__premium_only() ) {
				if ( lddfw_fs()->is_plan( 'premium', true ) ) {
					// Create tracking page if not exist.
					lddfw_create_tracking_page__premium_only();
				}
			}

			// Tracking Settings.
			add_settings_section(
				'lddfw_tracking_section',
				'',
				'',
				'lddfw-tracking'
			);

			add_settings_field(
				'lddfw_tracking_page',
				__( 'Tracking page', 'lddfw' ),
				array( $this, 'lddfw_tracking_page' ),
				'lddfw-tracking',
				'lddfw_tracking_section'
			);

			add_settings_field(
				'lddfw_driver_info_permission',
				__( 'Customer permissions', 'lddfw' ),
				array( $this, 'lddfw_driver_info_permission' ),
				'lddfw-tracking',
				'lddfw_tracking_section'
			);

			add_settings_field(
				'lddfw_drivers_tracking_timing',
				__( 'Driver tracking', 'lddfw' ),
				array( $this, 'lddfw_drivers_tracking_timing' ),
				'lddfw-tracking',
				'lddfw_tracking_section'
			);

			add_settings_field(
				'lddfw_drivers_tracking_interval',
				__( 'Driver tracking interval', 'lddfw' ),
				array( $this, 'lddfw_drivers_tracking_interval' ),
				'lddfw-tracking',
				'lddfw_tracking_section'
			);

			add_settings_field(
				'lddfw_add_time_to_eta',
				__( 'Add minutes to the ETA', 'lddfw' ),
				array( $this, 'lddfw_add_time_to_eta' ),
				'lddfw-tracking',
				'lddfw_tracking_section'
			);

		}

		if ( '' === $tab ) {

			// General Settings.
			add_settings_section(
				'lddfw_setting_section',
				'',
				'',
				'lddfw'
			);

			add_settings_field(
				'lddfw_delivery_drivers_page',
				__( 'Delivery drivers page', 'lddfw' ),
				array( $this, 'lddfw_delivery_drivers_page' ),
				'lddfw',
				'lddfw_setting_section'
			);

			add_settings_field(
				'lddfw_google_api_key',
				__( 'Google API key', 'lddfw' ),
				array( $this, 'lddfw_google_api_key' ),
				'lddfw',
				'lddfw_setting_section'
			);

			add_settings_section(
				'lddfw_status_section',
				__( 'Delivery statuses', 'lddfw' ),
				'',
				'lddfw'
			);

			add_settings_field(
				'lddfw_driver_assigned_status',
				__( 'Driver assigned status', 'lddfw' ),
				array( $this, 'lddfw_driver_assigned_status' ),
				'lddfw',
				'lddfw_status_section'
			);

			add_settings_field(
				'lddfw_out_for_delivery_status',
				__( 'Out for delivery status', 'lddfw' ),
				array( $this, 'lddfw_out_for_delivery_status' ),
				'lddfw',
				'lddfw_status_section'
			);

			add_settings_field(
				'lddfw_delivered_status',
				__( 'Delivered status', 'lddfw' ),
				array( $this, 'lddfw_delivered_status' ),
				'lddfw',
				'lddfw_status_section'
			);

			add_settings_field(
				'lddfw_failed_attempt_status',
				__( 'Failed delivery attempt status', 'lddfw' ),
				array( $this, 'lddfw_failed_attempt_status' ),
				'lddfw',
				'lddfw_status_section'
			);

			add_settings_field(
				'lddfw_processing_status',
				__( 'Order processing status', 'lddfw' ),
				array( $this, 'lddfw_processing_status' ),
				'lddfw',
				'lddfw_status_section'
			);

			add_settings_section(
				'lddfw_pickup_section',
				__( 'Store address coordinates', 'lddfw' ),
				array( $this, 'lddfw_pickup_section' ),
				'lddfw'
			);

			add_settings_field(
				'lddfw_store_address_latitude',
				__( 'Latitude', 'lddfw' ),
				array( $this, 'lddfw_store_address_latitude' ),
				'lddfw',
				'lddfw_pickup_section'
			);

			add_settings_field(
				'lddfw_store_address_longitude',
				__( 'Longitude', 'lddfw' ),
				array( $this, 'lddfw_store_address_longitude' ),
				'lddfw',
				'lddfw_pickup_section'
			);

			add_settings_field(
				'lddfw_dispatch_phone_number',
				__( 'Dispatch phone number', 'lddfw' ),
				array( $this, 'lddfw_dispatch_phone_number' ),
				'lddfw',
				'lddfw_pickup_section'
			);

		}

		if ( 'lddfw-drivers-settings' === $tab ) {

			add_settings_section(
				'lddfw_delivery_panel_section',
				__( 'Drivers Panel', 'lddfw' ),
				'',
				'lddfw-drivers-settings'
			);

			add_settings_field(
				'lddfw_app_mode',
				__( 'Theme', 'lddfw' ),
				array( $this, 'lddfw_app_mode' ),
				'lddfw-drivers-settings',
				'lddfw_delivery_panel_section'
			);

			add_settings_field(
				'lddfw_navigation_app',
				__( 'Navigation APP', 'lddfw' ),
				array( $this, 'lddfw_navigation_app' ),
				'lddfw-drivers-settings',
				'lddfw_delivery_panel_section'
			);
			add_settings_field(
				'lddfw_driver_feature_permission',
				__( 'Driver permissions', 'lddfw' ),
				array( $this, 'lddfw_driver_feature_permission' ),
				'lddfw-drivers-settings',
				'lddfw_delivery_panel_section'
			);

			add_settings_section(
				'lddfw_proof_of_delivery',
				__( 'Proof of delivery', 'lddfw' ),
				'',
				'lddfw-drivers-settings'
			);

			add_settings_field(
				'lddfw_proof_of_delivery_signature_photo',
				__( 'Signature & Photo', 'lddfw' ),
				array( $this, 'lddfw_proof_of_delivery_signature_photo' ),
				'lddfw-drivers-settings',
				'lddfw_proof_of_delivery'
			);

			add_settings_field(
				'lddfw_proof_of_delivery_max_images',
				__( 'Maximum Images', 'lddfw' ),
				array( $this, 'lddfw_proof_of_delivery_max_images' ),
				'lddfw-drivers-settings',
				'lddfw_proof_of_delivery'
			);

			add_settings_section(
				'lddfw_delivery_notes',
				__( 'Ready notes for the drivers', 'lddfw' ),
				array( $this, 'lddfw_delivery_notes_section' ),
				'lddfw-drivers-settings'
			);

			add_settings_field(
				'lddfw_failed_delivery_reason_1',
				__( 'Failed delivery', 'lddfw' ),
				array( $this, 'lddfw_failed_delivery_reason_1' ),
				'lddfw-drivers-settings',
				'lddfw_delivery_notes'
			);

			add_settings_field(
				'lddfw_delivery_dropoff_1',
				__( 'Successful delivery', 'lddfw' ),
				array( $this, 'lddfw_delivery_dropoff_1' ),
				'lddfw-drivers-settings',
				'lddfw_delivery_notes'
			);

			add_settings_section(
				'lddfw_delivery_assign_section',
				__( 'Assign Drivers to Orders', 'lddfw' ),
				'',
				'lddfw-drivers-settings'
			);

			add_settings_field(
				'lddfw_self_assign_delivery_drivers',
				__( 'Drivers can claim orders', 'lddfw' ),
				array( $this, 'lddfw_self_assign_delivery_drivers' ),
				'lddfw-drivers-settings',
				'lddfw_delivery_assign_section'
			);

			add_settings_field(
				'lddfw_self_assign_limitation',
				__( 'Claim orders limitation', 'lddfw' ),
				array( $this, 'lddfw_self_assign_limitation' ),
				'lddfw-drivers-settings',
				'lddfw_delivery_assign_section'
			);

			add_settings_field(
				'lddfw_auto_assign_delivery_drivers',
				__( 'Auto-assign delivery drivers', 'lddfw' ),
				array( $this, 'lddfw_auto_assign_delivery_drivers' ),
				'lddfw-drivers-settings',
				'lddfw_delivery_assign_section'
			);
			add_settings_field(
				'lddfw_auto_assign_method',
				__( 'Auto-assign method', 'lddfw' ),
				array( $this, 'lddfw_auto_assign_method' ),
				'lddfw-drivers-settings',
				'lddfw_delivery_assign_section'
			);

			add_settings_field(
				'lddfw_auto_assign_suborders',
				__( 'Auto-assign drivers to suborders', 'lddfw' ),
				array( $this, 'lddfw_auto_assign_suborders' ),
				'lddfw-drivers-settings',
				'lddfw_delivery_assign_section'
			);

			add_settings_field(
				'lddfw_driver_application',
				__( 'New drivers application form', 'lddfw' ),
				array( $this, 'lddfw_driver_application' ),
				'lddfw-drivers-settings',
				'lddfw_delivery_assign_section'
			);

			add_settings_field(
				'lddfw_enable_virtual_items',
				__( 'Virtual items', 'lddfw' ),
				array( $this, 'lddfw_enable_virtual_items' ),
				'lddfw-drivers-settings',
				'lddfw_delivery_assign_section'
			);

			add_settings_section(
				'lddfw_delivery_commissions_section',
				__( 'Commissions', 'lddfw' ),
				'',
				'lddfw-drivers-settings'
			);

			add_settings_field(
				'lddfw_driver_commission_type',
				__( 'Driver commissions', 'lddfw' ),
				array( $this, 'lddfw_driver_commission_type' ),
				'lddfw-drivers-settings',
				'lddfw_delivery_commissions_section'
			);

		}

		if ( 'lddfw-whatsapp-settings' === $tab ) {
			add_settings_section(
				'lddfw_whatsapp_settings',
				__( 'WhatsApp Settings', 'lddfw' ),
				'',
				'lddfw-whatsapp-settings'
			);

			add_settings_field(
				'lddfw_whatsapp_provider',
				__( 'WhatsApp provider', 'lddfw' ),
				array( $this, 'lddfw_whatsapp_provider' ),
				'lddfw-whatsapp-settings',
				'lddfw_whatsapp_settings'
			);

			add_settings_field(
				'lddfw_whatsapp_api_sid',
				__( 'API SID', 'lddfw' ),
				array( $this, 'lddfw_whatsapp_api_sid' ),
				'lddfw-whatsapp-settings',
				'lddfw_whatsapp_settings'
			);

			add_settings_field(
				'lddfw_whatsapp_api_auth_token',
				__( 'API AUTH TOKEN', 'lddfw' ),
				array( $this, 'lddfw_whatsapp_api_auth_token' ),
				'lddfw-whatsapp-settings',
				'lddfw_whatsapp_settings'
			);

			add_settings_field(
				'lddfw_whatsapp_api_phone',
				__( 'WhatsApp phone number', 'lddfw' ),
				array( $this, 'lddfw_whatsapp_api_phone' ),
				'lddfw-whatsapp-settings',
				'lddfw_whatsapp_settings'
			);

			add_settings_field(
				'lddfw_whatsapp_assign_to_driver',
				__( 'WhatsApp to the driver', 'lddfw' ),
				array( $this, 'lddfw_whatsapp_assign_to_driver' ),
				'lddfw-whatsapp-settings',
				'lddfw_whatsapp_settings'
			);

			add_settings_field(
				'lddfw_whatsapp_out_for_delivery',
				__( 'WhatsApp to the customer', 'lddfw' ),
				array( $this, 'lddfw_whatsapp_out_for_delivery' ),
				'lddfw-whatsapp-settings',
				'lddfw_whatsapp_settings'
			);

		}

		if ( 'lddfw-sms-settings' === $tab ) {
			add_settings_section(
				'lddfw_sms_settings',
				__( 'SMS Settings', 'lddfw' ),
				'',
				'lddfw-sms-settings'
			);

			add_settings_field(
				'lddfw_sms_provider',
				__( 'SMS provider', 'lddfw' ),
				array( $this, 'lddfw_sms_provider' ),
				'lddfw-sms-settings',
				'lddfw_sms_settings'
			);

			add_settings_field(
				'lddfw_sms_api_sid',
				__( 'API SID', 'lddfw' ),
				array( $this, 'lddfw_sms_api_sid' ),
				'lddfw-sms-settings',
				'lddfw_sms_settings'
			);

			add_settings_field(
				'lddfw_sms_api_auth_token',
				__( 'API AUTH TOKEN', 'lddfw' ),
				array( $this, 'lddfw_sms_api_auth_token' ),
				'lddfw-sms-settings',
				'lddfw_sms_settings'
			);

			add_settings_field(
				'lddfw_sms_api_phone',
				__( 'SMS phone number', 'lddfw' ),
				array( $this, 'lddfw_sms_api_phone' ),
				'lddfw-sms-settings',
				'lddfw_sms_settings'
			);

			add_settings_field(
				'lddfw_sms_assign_to_driver',
				__( 'SMS to the driver', 'lddfw' ),
				array( $this, 'lddfw_sms_assign_to_driver' ),
				'lddfw-sms-settings',
				'lddfw_sms_settings'
			);

			add_settings_field(
				'lddfw_customer_sms',
				__( 'SMS to the customer', 'lddfw' ),
				array( $this, 'lddfw_customer_sms' ),
				'lddfw-sms-settings',
				'lddfw_sms_settings'
			);

		}

		if ( 'lddfw-branding' === $tab ) {
			add_settings_section(
				'lddfw_branding',
				__( 'Drivers initial screen', 'lddfw' ),
				'',
				'lddfw-branding'
			);

			add_settings_field(
				'lddfw_branding_logo',
				__( 'Logo', 'lddfw' ),
				array( $this, 'lddfw_branding_logo' ),
				'lddfw-branding',
				'lddfw_branding'
			);
			add_settings_field(
				'lddfw_branding_title',
				__( 'Title', 'lddfw' ),
				array( $this, 'lddfw_branding_title' ),
				'lddfw-branding',
				'lddfw_branding'
			);

			add_settings_field(
				'lddfw_branding_subtitle',
				__( 'Subtitle', 'lddfw' ),
				array( $this, 'lddfw_branding_subtitle' ),
				'lddfw-branding',
				'lddfw_branding'
			);

			add_settings_field(
				'lddfw_branding_background',
				__( 'Page background', 'lddfw' ),
				array( $this, 'lddfw_branding_background' ),
				'lddfw-branding',
				'lddfw_branding'
			);

			add_settings_field(
				'lddfw_branding_text_color',
				__( 'Text color', 'lddfw' ),
				array( $this, 'lddfw_branding_text_color' ),
				'lddfw-branding',
				'lddfw_branding'
			);
			add_settings_field(
				'lddfw_branding_button_color',
				__( 'Button text color', 'lddfw' ),
				array( $this, 'lddfw_branding_button_color' ),
				'lddfw-branding',
				'lddfw_branding'
			);
			add_settings_field(
				'lddfw_branding_button_background',
				__( 'Button background', 'lddfw' ),
				array( $this, 'lddfw_branding_button_background' ),
				'lddfw-branding',
				'lddfw_branding'
			);

		}

		do_action( 'lddfw_settings' );
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function lddfw_pickup_section() {

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$store       = new LDDFW_Store();
				$map_address = $store->lddfw_store_address( 'map_address' );
				$map_link    = '<a target="_blank" class="btn btn-secondary btn-block" href="https://www.google.com/maps/search/?api=1&query=' . esc_attr( $map_address ) . '">' . esc_html( __( 'Google Maps' ) ) . '</a>';
				?>
					<p class="lddfw_description" id="lddfw_pickup_section-description">
						<?php
							/* translators: Map link */
							echo sprintf( esc_html__( 'To get the coordinates of a place, open %s, right-click the place on the map and copy the coordinates.', 'lddfw' ), $map_link );
						?>
					</p>
				<?php
			}
		}
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function lddfw_store_address_latitude() {
		?>
				<input type='text' class='regular-text' name='lddfw_store_address_latitude' value='<?php echo esc_attr( get_option( 'lddfw_store_address_latitude', '' ) ); ?>'>
				<p class="lddfw_description" id="lddfw_store_address_latitude-description">
					<?php echo esc_html( __( 'e.g. 37.819722', 'lddfw' ) ); ?>
				</p>
				<?php
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function lddfw_store_address_longitude() {
		?>
			<input type='text' class='regular-text' name='lddfw_store_address_longitude' value='<?php echo esc_attr( get_option( 'lddfw_store_address_longitude', '' ) ); ?>'>
			<p class="lddfw_description" id="lddfw_store_address_longitude-description">
				<?php echo esc_html( __( 'e.g. -122.478611', 'lddfw' ) ); ?>
			</p>
		<?php
	}







	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function lddfw_branding_title() {

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<input type='text' class='regular-text' placeholder='<?php echo esc_attr( __( 'WELCOME', 'lddfw' ) ); ?>' name='lddfw_branding_title' value='<?php echo esc_attr( get_option( 'lddfw_branding_title', '' ) ); ?>'>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function lddfw_branding_subtitle() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<input type='text' class='regular-text' placeholder='<?php echo esc_attr( __( 'To delivery drivers manager', 'lddfw' ) ); ?>' name='lddfw_branding_subtitle' value='<?php echo esc_attr( get_option( 'lddfw_branding_subtitle', '' ) ); ?>'>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function lddfw_branding_logo() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$image_id = get_option( 'lddfw_branding_logo', '' );
				$image    = '';
				if ( intval( $image_id ) > 0 ) {
					$image = wp_get_attachment_image_src( $image_id, 'medium' )[0];
					if ( '' !== $image ) {
						$image = '<img src="' . $image . '">';
					}
				}

				echo '<div id="lddfw_branding_logo_preview" class="lddfw_media_preview">' . $image . '</div>';
				?>
		 <input type="hidden" name="lddfw_branding_logo" id="lddfw_branding_logo" value="<?php echo esc_attr( $image_id ); ?>" class="regular-text" />
		 <input type='button' class="button-primary lddfw_media_manager" value="<?php esc_attr_e( 'Select a image', 'lddfw' ); ?>" data="lddfw_branding_logo"   id="lddfw_branding_media_manager"/>
		 <input type='button' class="button-secondary lddfw_media_delete" data="lddfw_branding_logo" value="<?php esc_attr_e( 'Delete image', 'lddfw' ); ?>" id="lddfw_branding_media_delete"/>

				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}
	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function lddfw_branding_background() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<input type='text' class='lddfw-color-picker' data-default-color="#fed14c" name='lddfw_branding_background' value='<?php echo esc_attr( get_option( 'lddfw_branding_background', '' ) ); ?>'>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function lddfw_branding_text_color() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<input type='text' class='lddfw-color-picker' data-default-color="#011627" name='lddfw_branding_text_color' value='<?php echo esc_attr( get_option( 'lddfw_branding_text_color', '' ) ); ?>'>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function lddfw_branding_button_color() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<input type='text' class='lddfw-color-picker' data-default-color="#fff" name='lddfw_branding_button_color' value='<?php echo esc_attr( get_option( 'lddfw_branding_button_color', '' ) ); ?>'>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function lddfw_branding_button_background() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<input type='text' class='lddfw-color-picker' data-default-color="#0062cc" name='lddfw_branding_button_background' value='<?php echo esc_attr( get_option( 'lddfw_branding_button_background', '' ) ); ?>'>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_sms_api_sid() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<input type='text' class='regular-text' name='lddfw_sms_api_sid' value='<?php echo esc_attr( get_option( 'lddfw_sms_api_sid', '' ) ); ?>'>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin template tags.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_template_tags__premium_only() {
		$tags = [
			'<a href="#" data="[delivery_driver_first_name]">' . esc_html( __( 'Delivery Driver First Name', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[delivery_driver_last_name]">' . esc_html( __( 'Delivery Driver Last Name', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[delivery_driver_page]">' . esc_html( __( 'Delivery Driver Page', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[store_name]">' . esc_html( __( 'Store Name', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[order_id]">' . esc_html( __( 'Order Id', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[order_create_date]">' . esc_html( __( 'Order Create Date', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[order_status]">' . esc_html( __( 'Order Status', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[order_amount]">' . esc_html( __( 'Order Amount', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[order_currency]">' . esc_html( __( 'Order Currency', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[shipping_method]">' . esc_html( __( 'Shipping Method', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[payment_method]">' . esc_html( __( 'Payment Method', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[billing_first_name]">' . esc_html( __( 'Billing First Name', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[billing_last_name]">' . esc_html( __( 'Billing Last Name', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[billing_company]">' . esc_html( __( 'Billing Company', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[billing_address_1]">' . esc_html( __( 'Billing Address 1', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[billing_address_2]">' . esc_html( __( 'Billing Address 2', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[billing_city]">' . esc_html( __( 'Billing City', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[billing_state]">' . esc_html( __( 'Billing State', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[billing_postcode]">' . esc_html( __( 'Billing Postcode', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[billing_country]">' . esc_html( __( 'Billing Country', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[billing_phone]">' . esc_html( __( 'Billing Phone', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[shipping_first_name]">' . esc_html( __( 'Shipping First Name', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[shipping_last_name]">' . esc_html( __( 'Shipping Last Name', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[shipping_company]">' . esc_html( __( 'Shipping Company', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[shipping_address_1]">' . esc_html( __( 'Shipping Address 1', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[shipping_address_2]">' . esc_html( __( 'Shipping Address 2', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[shipping_city]">' . esc_html( __( 'Shipping City', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[shipping_state]">' . esc_html( __( 'Shipping State', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[shipping_postcode]">' . esc_html( __( 'Shipping Postcode', 'lddfw' ) ) . '</a>',
			'<a href="#" data="[shipping_country]">' . esc_html( __( 'Shipping Country', 'lddfw' ) ) . '</a>'
		];
	
		return implode(' | ', $tags);
	}
	
/**
 * Plugin settings for customer SMS notifications.
 *
 * This function generates the settings sections for various SMS notifications sent to customers,
 * such as when the order is out for delivery, when delivery has started, upon delivery confirmation,
 * and upon notification of non-delivery. It utilizes the lddfw_generate_sms_settings method
 * to generate each settings section.
 *
 * @since 1.0.0
 */
public function lddfw_customer_sms() {
    if ( lddfw_fs()->is__premium_only() ) {
        if ( lddfw_fs()->can_use_premium_code() ) {

            // SMS to customer when order is out for delivery.
            $this->lddfw_generate_sms_settings__premium_only(
                'sms_out_for_delivery',
                __( 'SMS to customer when order is out for delivery.', 'lddfw' ),
                __( 'Hello [billing_first_name], status of your order #[order_id] with [store_name] has been changed to [order_status].', 'lddfw' )
            );

            // SMS to customer when the driver started the delivery.
            $this->lddfw_generate_sms_settings__premium_only(
                'sms_start_delivery',
                __( 'SMS to customer when the driver started the delivery.', 'lddfw' ),
                __( 'Hello [billing_first_name], the delivery for order #[order_id] with [store_name] has been started. [estimated_time_of_arrival] [tracking_url]', 'lddfw' ),
                // Additional tags specific to this message type.
                array(
                    '[estimated_time_of_arrival]' => __( 'ETA', 'lddfw' ),
                    '[tracking_url]' => __( 'Tracking URL', 'lddfw' ),
                )
            );

            // SMS to customer upon driver confirmation of delivery.
            $this->lddfw_generate_sms_settings__premium_only(
                'sms_delivered',
                __( 'SMS to Customer upon driver confirmation of delivery.', 'lddfw' ),
                __( 'Hello [billing_first_name], your order #[order_id] from [store_name] has been successfully delivered.', 'lddfw' )
            );

            // SMS to customer upon driver notification of non-delivery.
            $this->lddfw_generate_sms_settings__premium_only(
                'sms_not_delivered',
                __( 'SMS to Customer upon driver notification of non-delivery.', 'lddfw' ),
                __( 'Hello [billing_first_name], we apologize, but your order #[order_id] from [store_name] could not be delivered as scheduled.', 'lddfw' )
            );

        }
    }
    echo lddfw_admin_premium_feature( '' );
}

 

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_self_assign_limitation() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_self_assign_limitation = get_option( 'lddfw_self_assign_limitation', '' );
				?>
				<select name="lddfw_self_assign_limitation">
					<option value=""  <?php selected( esc_attr( $lddfw_self_assign_limitation ), '' ); ?>><?php echo esc_html( __( 'Enable all orders', 'lddfw' ) ); ?></option>
					<option value="1" <?php selected( esc_attr( $lddfw_self_assign_limitation ), '1' ); ?>><?php echo esc_html( __( 'Enable orders that shipping city is the same as driver city', 'lddfw' ) ); ?></option>
					<option value="2" <?php selected( esc_attr( $lddfw_self_assign_limitation ), '2' ); ?>><?php echo esc_html( __( 'Enable orders that pickup city is the same as driver city', 'lddfw' ) ); ?></option>
					<option value="3" <?php selected( esc_attr( $lddfw_self_assign_limitation ), '3' ); ?>><?php echo esc_html( __( 'Enable orders that pickup city or shipping city is the same as driver city', 'lddfw' ) ); ?></option>
				</select>
				<p class="lddfw_description" id="lddfw_self_assign_limitation-description"><?php echo esc_html( __( 'Choose which orders the drivers will be able to claim.', 'lddfw' ) ); ?></p>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}



	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_self_assign_delivery_drivers() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_self_assign_delivery_drivers = get_option( 'lddfw_self_assign_delivery_drivers', '' );
				$checked                            = '1' === $lddfw_self_assign_delivery_drivers ? 'checked' : '';
				?>
		<label for="lddfw_self_assign_delivery_drivers">
			<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_self_assign_delivery_drivers' id='lddfw_self_assign_delivery_drivers' value='1'>
				<?php echo esc_html( __( 'Enable drivers to claim orders', 'lddfw' ) ); ?>
		</label>
		<p class="lddfw_description" id="lddfw_self_assign_delivery_drivers-description"><?php echo esc_html( __( 'Claim orders option available for processing status orders.', 'lddfw' ) ); ?></p>



				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings.
	 *
	 * @param int $param param.
	 * @since 1.1.2
	 * @return string
	 */
	public function lddfw_driver_commission_value_sanitize__premium_only( $param ) {
		if ( ! is_numeric( $param ) ) {
			$param = '';
		}
		return $param;
	}


	/**
	 * Plugin settings.
	 *
	 * @since 1.1.2
	 */
	public function lddfw_drivers_tracking_timing() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_drivers_tracking_timing = get_option( 'lddfw_drivers_tracking_timing', '' );
				?>
			<label for="lddfw_drivers_tracking_timing">
			<input <?php echo checked( esc_attr( $lddfw_drivers_tracking_timing ), '1' ); ?> type='checkbox' class='regular-text' name='lddfw_drivers_tracking_timing' id='lddfw_drivers_tracking_timing' value='1'>
				<?php echo esc_html( __( 'Enable driver tracking.', 'lddfw' ) ); ?>
			<div class="lddfw_description" id="lddfw_drivers_tracking_timing-description">
				<br>
				<p><b><?php echo esc_html( __( 'About the driver tracking:', 'lddfw' ) ); ?></b></p>
				<p>	* <?php echo esc_html( __( 'The driver must enable tracking on his driver panel.', 'lddfw' ) ); ?></p>
				<p>	* <?php echo esc_html( __( 'The tracking is enabled only in secure contexts (HTTPS), in some or all supporting browsers.', 'lddfw' ) ); ?></p>
				<p>	* <?php echo esc_html( __( 'The tracking works only when the driver uses the driver panel. When using other apps, the tracking doesn\'t work in the device\'s background. However, the driver can use pop-up view or split-screen on his mobile to keep tracking.', 'lddfw' ) ); ?></p>
			</div>
			</label>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
		?>
		<p><?php echo sprintf( esc_html( __( 'For more information about the tracking page, please %1$sclick here%2$s.', 'lddfw' ) ), '<a href="https://powerfulwp.com/docs/local-delivery-drivers-for-woocommerce-premium/getting-started/tracking/" target="_blank">', '</a>' ); ?></p>
		<?php
	}

	/**
	 * Plugin settings.
	 */
	public function lddfw_drivers_tracking_interval() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$tracking                        = new LDDFW_Tracking();
				$lddfw_drivers_tracking_interval = $tracking->get_tracking_interval();
				$array                           = $tracking->get_tracking_interval_array();
				?>
				<label for="lddfw_drivers_tracking_interval">
				<select name='lddfw_drivers_tracking_interval'>
				<?php
				foreach ( $array as $value ) {
					$seconds = $value / 1000;
					?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( esc_attr( $lddfw_drivers_tracking_interval ), esc_attr( $value ) ); ?>><?php echo esc_html( sprintf( __( 'Every %s seconds', 'lddfw' ), esc_attr( $seconds ) ) ); ?></option>
					<?php
				}
				?>
				</select>
				<div class="lddfw_description" id="lddfw_drivers_tracking_timing-description">
				<p>
					<?php echo esc_html( __( 'Please note that shorter tracking intervals will drain the driver battery far quicker and makes more requests to your server.', 'lddfw' ) ); ?>
				</p>
				</div>
			</label>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
		?>
		<?php
	}

	/**
	 * Plugin settings.
	 */
	public function lddfw_add_time_to_eta() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_add_time_to_eta = get_option( 'lddfw_add_time_to_eta', '' );
				$array                 = array( 60, 120, 180, 240, 300, 600, 900, 1800, 3600 );
				?>
				<label for="lddfw_add_time_to_eta">
				<select name='lddfw_add_time_to_eta'>
					<option value="" <?php selected( esc_attr( $lddfw_add_time_to_eta ), '' ); ?>><?php echo esc_html( __( 'None', 'lddfw' ) ); ?></option>
				<?php
				foreach ( $array as $value ) {
					$minutes = $value / 60;
					?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( esc_attr( $lddfw_add_time_to_eta ), esc_attr( $value ) ); ?>><?php echo esc_html( sprintf( __( '%s minutes', 'lddfw' ), $minutes ) ); ?></option>
					<?php
				}
				?>
				</select>
				<div class="lddfw_description" id="lddfw_add_time_to_eta-description">
				<p>
					<?php echo esc_html( __( 'Add minutes to the estimated time of arrival.', 'lddfw' ) ); ?>
				</p>
				</div>
			</label>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
		?>
		<?php
	}



	/**
	 * Plugin settings.
	 *
	 * @since 1.1.2
	 */
	public function lddfw_driver_commission_type() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_driver_commission_type         = get_option( 'lddfw_driver_commission_type', '' );
				$lddfw_driver_commission_value        = get_option( 'lddfw_driver_commission_value', '' );
				$lddfw_driver_commission_second_value = get_option( 'lddfw_driver_commission_second_value', '' );
				?>
		<label for="lddfw_driver_commission_type">
			<select name='lddfw_driver_commission_type' id='lddfw_driver_commission_type'>
				<option value="" data-second-label="" data-second-description="" data-label="" data-description="" <?php selected( esc_attr( $lddfw_driver_commission_type ), '' ); ?>><?php echo esc_html( __( 'Select', 'lddfw' ) ); ?></option>
				<option value="fixed" data-second-label="" data-second-description="" data-label="<?php echo esc_attr( lddfw_currency_symbol() ); ?>" data-description=""  <?php selected( esc_attr( $lddfw_driver_commission_type ), 'fixed' ); ?>><?php echo esc_html( __( 'Fixed Price', 'lddfw' ) ); ?></option>
				<option value="delivery_percentage" data-second-label="" data-second-description="" data-label="" data-description="<?php echo esc_attr( '%' . ' ' . esc_html( __( 'of delivery price', 'lddfw' ) ) ); ?>" <?php selected( esc_attr( $lddfw_driver_commission_type ), 'delivery_percentage' ); ?>><?php echo esc_html( __( 'Delivery total percentage', 'lddfw' ) ); ?></option>
				<option value="order_percentage" data-second-label="" data-second-description="" data-label="" data-description="<?php echo esc_attr( '%' . ' ' . esc_html( __( 'of order total', 'lddfw' ) ) ); ?>"  <?php selected( esc_attr( $lddfw_driver_commission_type ), 'order_percentage' ); ?>><?php echo esc_html( __( 'Order total percentage', 'lddfw' ) ); ?></option>
				<option value="distance" data-second-label="" data-second-description="" data-label="<?php echo esc_attr( esc_html( __( 'mile / km', 'lddfw' ) . ' x ' . ' ' . lddfw_currency_symbol() ) ); ?>" data-description="<?php echo esc_attr( '' ); ?>"  <?php selected( esc_attr( $lddfw_driver_commission_type ), 'distance' ); ?>><?php echo esc_html( __( 'Distance', 'lddfw' ) ); ?></option>
				<option value="time" data-second-label="" data-second-description="" data-label="<?php echo esc_attr( esc_html( __( 'minute', 'lddfw' ) ) . ' x ' . lddfw_currency_symbol() ); ?>" data-description="<?php echo esc_attr( '' ); ?>" <?php selected( esc_attr( $lddfw_driver_commission_type ), 'time' ); ?>><?php echo esc_html( __( 'Time', 'lddfw' ) ); ?></option>
				<option value="distance_time" data-second-label="( <?php echo esc_attr( esc_html( __( 'minute', 'lddfw' ) ) . ' x ' . lddfw_currency_symbol() ); ?>" data-second-description=")" data-label="( <?php echo esc_attr( esc_html( __( 'mile / km', 'lddfw' ) . ' x ' . ' ' . lddfw_currency_symbol() ) ); ?>" data-description=") <?php echo esc_attr( ' + ' ); ?>" <?php selected( esc_attr( $lddfw_driver_commission_type ), 'distance_time' ); ?>><?php echo esc_html( __( 'Distance + Time', 'lddfw' ) ); ?></option>
				<option value="fixed_distance" data-second-label="( <?php echo esc_attr( esc_html( __( 'mile / km', 'lddfw' ) . ' x ' . ' ' . lddfw_currency_symbol() ) ); ?>" data-second-description=")" data-label="( <?php echo esc_attr( lddfw_currency_symbol() ); ?>" data-description=") <?php echo esc_attr( ' + ' ); ?>" data-second-show="true" data-first-show="true" <?php selected( esc_attr( $lddfw_driver_commission_type ), 'fixed_distance' ); ?>><?php echo esc_html( __( 'Fixed Price + Distance', 'lddfw' ) ); ?></option>
			</select>
			<div id='lddfw_driver_commission_div' style='display:inline-block;'>

				<div id="lddfw_first_commission_input" style="display:none;display:inline-block;">
					<label id="lddfw_first_commission_label"></label>
					<input type="text" size="5" name="lddfw_driver_commission_value" id="lddfw_driver_commission_value" value="<?php echo esc_attr( $lddfw_driver_commission_value ); ?>">
					<span id="lddfw_first_commission_description"></span>
				</div>

				<div id="lddfw_second_commission_input" style="display:none;display:inline-block;">
					<label id="lddfw_second_commission_label"></label>
					<input type="text" size="5" name="lddfw_driver_commission_second_value" id="lddfw_driver_commission_second_value" value="<?php echo esc_attr( $lddfw_driver_commission_second_value ); ?>">
					<span id="lddfw_second_commission_description"></span>
				</div>

			</div>
		</label>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}



	/**
	 * Plugin settings.
	 */
	public function lddfw_app_mode() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_app_mode = get_option( 'lddfw_app_mode', '' );
				?>
				<label for="lddfw_app_mode">
				<select name='lddfw_app_mode'>
					<option value="light" <?php selected( esc_attr( $lddfw_app_mode ), 'light' ); ?>><?php echo esc_html( __( 'Light Mode', 'lddfw' ) ); ?></option>
					<option value="dark" <?php selected( esc_attr( $lddfw_app_mode ), 'dark' ); ?>><?php echo esc_html( __( 'Dark Mode', 'lddfw' ) ); ?></option>
				</select>
				</label>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_navigation_app() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_navigation_app = get_option( 'lddfw_navigation_app', '' );
				?>
		<label for="lddfw_navigation_app">
		<select name='lddfw_navigation_app'>
			<option value="wase" <?php selected( esc_attr( $lddfw_navigation_app ), 'wase' ); ?>><?php echo esc_html( __( 'Waze', 'lddfw' ) ); ?></option>
			<option value="apple_maps" <?php selected( esc_attr( $lddfw_navigation_app ), 'apple_maps' ); ?>><?php echo esc_html( __( 'Apple Maps', 'lddfw' ) ); ?></option>
			<option value="google_maps" <?php selected( esc_attr( $lddfw_navigation_app ), 'google_maps' ); ?>><?php echo esc_html( __( 'Google Maps', 'lddfw' ) ); ?></option>
		</select>
		</label>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}


	/**
	 * Plugin settings.
	 *
	 * @since 1.6.0
	 */
	public function lddfw_driver_info_permission() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_driver_photo_permission = get_option( 'lddfw_driver_photo_permission', false );
				$checked                       = false === $lddfw_driver_photo_permission || '1' === $lddfw_driver_photo_permission ? 'checked' : '';
				?>
		<fieldset>
			<label for="lddfw_driver_photo_permission">
				<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_driver_photo_permission' id='lddfw_driver_photo_permission' value='1'>
				<?php echo esc_html( __( 'View driver photo.', 'lddfw' ) ); ?>
			</label>
		</fieldset>
				<?php
				$lddfw_driver_name_permission = get_option( 'lddfw_driver_name_permission', false );
				$checked                      = false === $lddfw_driver_name_permission || '1' === $lddfw_driver_name_permission ? 'checked' : '';
				?>
		<fieldset>
			<label for="lddfw_driver_name_permission">
				<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_driver_name_permission' id='lddfw_driver_name_permission' value='1'>
				<?php echo esc_html( __( 'View driver name.', 'lddfw' ) ); ?>
			</label>
		</fieldset>
				<?php

				$lddfw_driver_phone_permission = get_option( 'lddfw_driver_phone_permission', false );
				$checked                       = false === $lddfw_driver_phone_permission || '1' === $lddfw_driver_phone_permission ? 'checked' : '';
				?>
		<fieldset>
			<label for="lddfw_driver_phone_permission">
				<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_driver_phone_permission' id='lddfw_driver_phone_permission' value='1'>
				<?php echo esc_html( __( 'View driver phone number.', 'lddfw' ) ); ?>
			</label>
		</fieldset>
		<p class="description">
				<?php echo esc_html( __( 'Allow the customer to see the driver information on the tracking page, emails and on his account.', 'lddfw' ) ); ?><br>

		</p>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.6.0
	 */
	public function lddfw_driver_feature_permission() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_driver_prices_permission = get_option( 'lddfw_driver_prices_permission', false );
				$checked                        = false === $lddfw_driver_prices_permission || '1' === $lddfw_driver_prices_permission ? 'checked' : '';
				?>
		<fieldset>
			<label for="lddfw_driver_prices_permission">
				<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_driver_prices_permission' id='lddfw_driver_prices_permission' value='1'>
				<?php echo esc_html( __( 'View pricing.', 'lddfw' ) ); ?>
			</label>
		</fieldset>
				<?php
				$lddfw_driver_products_permission = get_option( 'lddfw_driver_products_permission', false );
				$checked                          = false === $lddfw_driver_products_permission || '1' === $lddfw_driver_products_permission ? 'checked' : '';
				?>
		<fieldset>
			<label for="lddfw_driver_products_permission">
				<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_driver_products_permission' id='lddfw_driver_products_permission' value='1'>
				<?php echo esc_html( __( 'View order products.', 'lddfw' ) ); ?>
			</label>
		</fieldset>
				<?php
				$lddfw_driver_commission_permission = get_option( 'lddfw_driver_commission_permission', false );
				$checked                            = false === $lddfw_driver_commission_permission || '1' === $lddfw_driver_commission_permission ? 'checked' : '';
				?>
				<fieldset>
					<label for="lddfw_driver_commission_permission">
						<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_driver_commission_permission' id='lddfw_driver_commission_permission' value='1'>
						<?php echo esc_html( __( 'View commission.', 'lddfw' ) ); ?>
					</label>
				</fieldset>


				<?php
				$lddfw_driver_customer_permission = get_option( 'lddfw_driver_customer_permission', false );
				$checked                          = false === $lddfw_driver_customer_permission || '1' === $lddfw_driver_customer_permission ? 'checked' : '';
				?>
				<fieldset>
					<label for="lddfw_driver_customer_permission">
						<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_driver_customer_permission' id='lddfw_driver_customer_permission' value='1'>
						<?php echo esc_html( __( 'View customer details.', 'lddfw' ) ); ?>
					</label>
				</fieldset>

				<?php
				$lddfw_driver_customer_whatsapp_permission = get_option( 'lddfw_driver_customer_whatsapp_permission', false );
				$checked                                   = false === $lddfw_driver_customer_whatsapp_permission || '1' === $lddfw_driver_customer_whatsapp_permission ? 'checked' : '';
				?>
				<fieldset>
					<label for="lddfw_driver_customer_whatsapp_permission">
						<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_driver_customer_whatsapp_permission' id='lddfw_driver_customer_whatsapp_permission' value='1'>
						<?php echo esc_html( __( 'View customer WhatsApp button.', 'lddfw' ) ); ?>
					</label>
				</fieldset>

				<?php
				$lddfw_driver_billing_permission = get_option( 'lddfw_driver_billing_permission', false );
				$checked                         = false === $lddfw_driver_billing_permission || '1' === $lddfw_driver_billing_permission ? 'checked' : '';
				?>
				<fieldset>
					<label for="lddfw_driver_billing_permission">
						<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_driver_billing_permission' id='lddfw_driver_billing_permission' value='1'>
						<?php echo esc_html( __( 'View customer billing address.', 'lddfw' ) ); ?>
					</label>
				</fieldset>

				



		<p class="description"><?php echo esc_html( __( 'Allow the driver to see features on the drivers panel.', 'lddfw' ) ); ?></p>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}



	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_auto_assign_suborders() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_auto_assign_suborders = get_option( 'lddfw_auto_assign_suborders' );
				$checked                     = ( '1' === $lddfw_auto_assign_suborders || false === $lddfw_auto_assign_suborders ) ? 'checked' : '';
				?>
					<label for="lddfw_auto_assign_suborders">
						<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_auto_assign_suborders' id='lddfw_auto_assign_suborders' value='1'>
							<?php echo esc_html( __( 'Enable auto-assign drivers to suborders if they exist.', 'lddfw' ) ); ?>
					</label>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_driver_application() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_driver_application = get_option( 'lddfw_driver_application', '' );
				$checked                  = '1' === $lddfw_driver_application ? 'checked' : '';
				?>
		<label for="lddfw_driver_application">
			<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_driver_application' id='lddfw_driver_application' value='1'>
				<?php echo esc_html( __( 'Enable new drivers application form.', 'lddfw' ) ); ?>
		</label>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}



	/**
	 * Plugin settings.
	 *
	 * @since 1.5.0
	 */
	public function lddfw_auto_assign_method() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_auto_assign_method = get_option( 'lddfw_auto_assign_method', '' );
				?>
		<label for="lddfw_auto_assign_method">
		<select name='lddfw_auto_assign_method' id="lddfw_auto_assign_method">
			<option value="1" <?php selected( esc_attr( $lddfw_auto_assign_method ), '1' ); ?>><?php echo esc_html( __( 'Auto-assign by the number of orders each driver has.', 'lddfw' ) ); ?></option>
			<option value="2" <?php selected( esc_attr( $lddfw_auto_assign_method ), '2' ); ?>><?php echo esc_html( __( 'Auto-assign by shipping zip code, city, state, country, and the number of orders each driver has.', 'lddfw' ) ); ?></option>
			<option value="3" <?php selected( esc_attr( $lddfw_auto_assign_method ), '3' ); ?>><?php echo esc_html( __( 'Auto-assign by shipping city.', 'lddfw' ) ); ?></option>
			<option value="4" <?php selected( esc_attr( $lddfw_auto_assign_method ), '4' ); ?>><?php echo esc_html( __( 'Auto-assign by pickup zip code, city, state, country, and the number of orders each driver has.', 'lddfw' ) ); ?></option>
			<option value="5" <?php selected( esc_attr( $lddfw_auto_assign_method ), '5' ); ?>><?php echo esc_html( __( 'Auto-assign by pickup city.', 'lddfw' ) ); ?></option>
			<option value="6" <?php selected( esc_attr( $lddfw_auto_assign_method ), '6' ); ?>><?php echo esc_html( __( 'Auto-assign by shipping city or pickup city.', 'lddfw' ) ); ?></option>
		</select>
		</label>
		<p class="description"><?php echo esc_html( __( 'The auto-assign by address compares the address with the driver address.', 'lddfw' ) ); ?></p>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_auto_assign_delivery_drivers() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_auto_assign_delivery_drivers = get_option( 'lddfw_auto_assign_delivery_drivers', '' );
				$checked                            = '1' === $lddfw_auto_assign_delivery_drivers ? 'checked' : '';
				?>
		<label for="lddfw_auto_assign_delivery_drivers">
			<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_auto_assign_delivery_drivers' id='lddfw_auto_assign_delivery_drivers' value='1'>
				<?php echo esc_html( __( 'Enable auto-assigns delivery drivers.', 'lddfw' ) ); ?>
		</label>
		<p class="lddfw_description" id="lddfw_auto_assign_delivery_drivers-description"><?php echo esc_html( __( 'Auto-assign drivers action  happen on processing status orders.', 'lddfw' ) ); ?></p>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_enable_virtual_items() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_enable_virtual_items = get_option( 'lddfw_enable_virtual_items', '' );
				$checked                    = '1' === $lddfw_enable_virtual_items ? 'checked' : '';
				?>
		<label for="lddfw_enable_virtual_items">
			<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_enable_virtual_items' id='lddfw_enable_virtual_items' value='1'>
				<?php echo esc_html( __( 'Enable auto-assigns and claim orders options for orders that contain virtual items.', 'lddfw' ) ); ?>
		</label>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}


	/**
	 * Plugin settings for SMS to assign order to driver.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_sms_assign_to_driver() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				// Define the default template for this SMS notification.
				$default_template = __( 'Hello [delivery_driver_first_name], order #[order_id] with [store_name] has been assigned to you. [delivery_driver_page]', 'lddfw' );

				// Generate the settings using the generic method.
				$this->lddfw_generate_sms_settings__premium_only(
					'sms_assign_to_driver',
					__( 'SMS to the delivery driver when a new order is assigned.', 'lddfw' ),
					$default_template
				);
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}


	 

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_sms_api_phone() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<input type='text' class='regular-text' name='lddfw_sms_api_phone' value='<?php echo esc_attr( get_option( 'lddfw_sms_api_phone', '' ) ); ?>'>
		<p class="lddfw_description" id="lddfw-gooogle-api-key-description"><?php echo esc_html( __( 'Phone number to send SMS should be in the following format (+)(country code)(area code)(phone number) e.g +15024658206', 'lddfw' ) ); ?></p>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_sms_api_auth_token() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<input type='text' class='regular-text' name='lddfw_sms_api_auth_token' value='<?php echo esc_attr( get_option( 'lddfw_sms_api_auth_token', '' ) ); ?>'>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_sms_provider() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<select name='lddfw_sms_provider'>
			<option value="twilio" <?php selected( esc_attr( get_option( 'lddfw_sms_provider', '' ) ), 'twilio' ); ?>>twilio</option>
		</select>
		<p class="description" id="lddfw_sms_provider-description"><?php echo sprintf( esc_html( __( 'For more information about how to create an SMS account %1$sclick here%2$s.', 'lddfw' ) ), '<a href="https://powerfulwp.com/docs/local-delivery-drivers-for-woocommerce-premium/getting-started/sms-settings/" target="_blank">', '</a>' ); ?></p>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );

	}

	/**
	 * Generate SMS settings form section.
	 *
	 * This function generates the settings form section for a specific SMS message type.
	 * It outputs HTML for the settings page, allowing users to configure the SMS template
	 * used when sending SMS notifications.
	 *
	 * @param string $key             The key identifying the SMS message type (e.g., 'sms_assign_to_driver').
	 * @param string $label           The label for the message type section.
	 * @param string $default_template Optional. The default template text. Default empty string.
	 * @param array  $additional_tags Optional. Additional tags available for use in the template. Default empty array.
	 */
	public function lddfw_generate_sms_settings__premium_only( $key, $label, $default_template = '', $additional_tags = [] ) {
		// Retrieve the enabled status for this message type.
		$enabled = get_option( "lddfw_{$key}", '' );
		// Retrieve the SMS template for this message type.
		$template = get_option( "lddfw_{$key}_template", '' );

		// Determine if the checkbox should be checked based on the enabled status.
		$checked = $enabled === '1' ? 'checked' : '';
		?>
		<div class="card" style="margin-top:0px; margin-bottom:20px;">
			<!-- Toggle to enable/disable sending SMS messages for this message type -->
			<label for="lddfw_<?php echo esc_attr( $key ); ?>" class='checkbox_toggle'>
				<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name="lddfw_<?php echo esc_attr( $key ); ?>" id="lddfw_<?php echo esc_attr( $key ); ?>" value="1">
				<?php echo esc_html( $label ); ?>
			</label>
			<div class='lddfw_toggle_area' style='margin-top: 20px; <?php echo $checked ? '' : 'display:none;'; ?>'>
				<!-- Input for the SMS template -->
				<p style='margin-top:10px;margin-bottom:5px;'>
					<?php echo esc_html( __( 'SMS Template', 'lddfw' ) ); ?>
					<?php if ( $default_template ) : ?>
						<?php echo '<a href="#" class="lddfw_copy_template_to_textarea" data="' . esc_attr( $default_template ) . '" ><b>' . esc_html( __( 'Default template', 'lddfw' ) ) . '</b></a>'; ?>
					<?php endif; ?>
				</p>
				<textarea class='large-text' name="lddfw_<?php echo esc_attr( $key ); ?>_template" id="lddfw_<?php echo esc_attr( $key ); ?>_template" style='min-width: 50%; height: 75px;'><?php echo esc_textarea( $template ); ?></textarea>
				<!-- Display available tags for use in the template -->
				<a href="#" class="lddf_button_toggle" style="margin-top: 10px;">
                            <?php echo esc_js(__('Show/Hide Tags', 'lddfw')); ?>
							</a>
				<p style="display:none" class="lddfw_description lddfw_copy_tags_to_textarea" data-textarea="lddfw_<?php echo esc_attr( $key ); ?>_template">
					<?php
					echo $this->lddfw_template_tags__premium_only();
					if ( ! empty( $additional_tags ) ) {
						foreach ( $additional_tags as $tag => $description ) {
							echo ' | <a href="#" data="' . esc_attr( $tag ) . '">' . esc_html( $description ) . '</a>';
						}
					}
					?>
				</p>
			</div>
		</div>
		<?php
	}


	/**
	 * Generate WhatsApp settings form section.
	 *
	 * This function generates the settings form section for a specific WhatsApp message type.
	 * It outputs HTML for the settings page, allowing users to configure the Content SID and variables
	 * used in WhatsApp Content Templates sent via Twilio.
	 *
	 * @param string $key             The key identifying the WhatsApp message type (e.g., 'whatsapp_out_for_delivery').
	 * @param string $label           The label for the message type section.
	 * @param array  $additional_tags Optional. Additional tags available for use in variables. Default empty array.
	 */
	public function lddfw_generate_whatsapp_settings__premium_only( $key, $label , $additional_tags = [] ) {
		$enabled = get_option( "lddfw_{$key}", '' );
		$content_sid = get_option( "lddfw_{$key}_content_sid", '' );
		$variables = get_option( "lddfw_{$key}_variables", '{}' );

		// Check if the value is already an array
		if ( ! is_array( $variables ) ) {
			$variables = json_decode( $variables, true ) ?: [];
		}

		$checked = $enabled === '1' ? 'checked' : '';
		?>
<div class="card" style="margin-top:0px; margin-bottom:20px;">		 
		<label for="lddfw_<?php echo esc_attr( $key ); ?>" class='checkbox_toggle'  >
			<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name="lddfw_<?php echo esc_attr( $key ); ?>" id="lddfw_<?php echo esc_attr( $key ); ?>" value="1">
			<?php echo esc_html( $label ); ?>
		</label>
		<div class='lddfw_toggle_area ' style='margin-top: 20px;'>
			<p><?php echo esc_html( __( 'Content template SID', 'lddfw' ) ); ?></p>
			<input type='text' class='large-text' name="lddfw_<?php echo esc_attr( $key ); ?>_content_sid" id="lddfw_<?php echo esc_attr( $key ); ?>_content_sid" value="<?php echo esc_attr( $content_sid ); ?>" placeholder="<?php echo esc_attr( __( 'Enter Content SID', 'lddfw' ) ); ?>">
	
			<p style='margin-top:20px;'><?php echo esc_html( __( 'Template Variables', 'lddfw' ) ); ?></p>
			<div id="lddfw_variables_container_<?php echo esc_attr( $key ); ?>">
				<?php foreach ( $variables as $index => $value ) : ?>
					<div class="lddfw_variable_input " data-key="<?php echo esc_attr( $key ); ?>"  id="lddfw_variable_<?php echo esc_attr( $key . '_' . $index ); ?>"  >
						<label style="display:block; margin-bottom:10px;"><?php echo esc_html( sprintf( __( 'Add text or tag for variable {{%d}}', 'lddfw' ), $index + 1 ) ); ?></label>
						<textarea class="large-text" id="lddfw_<?php echo esc_attr( $key ); ?>_variables_<?php echo esc_attr( $index ); ?>" name="lddfw_<?php echo esc_attr( $key ); ?>_variables[<?php echo esc_attr( $index ); ?>]" placeholder="<?php echo esc_attr( __( 'Enter value', 'lddfw' ) ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
						
						 
						<a href="#" class="lddf_button_toggle" style="margin-top: 10px;">
                            <?php echo esc_js(__('Show/Hide Tags', 'lddfw')); ?>
							</a>

						<p style="display:none" class="lddfw_description lddfw_copy_tags_to_textarea" data-textarea="lddfw_<?php echo esc_attr( $key ); ?>_variables_<?php echo esc_attr( $index ); ?>">
							<?php 
							echo $this->lddfw_template_tags__premium_only() ; 
							if (!empty($additional_tags)) {
								foreach ($additional_tags as $tag => $description) {
									echo ' | <a href="#" data="' . esc_attr($tag) . '">' . esc_html($description) . '</a>';
								}
							}
							
							?>
							
						</p>

						<i class="lddfw_delete_variable_button" data-target="lddfw_variable_<?php echo esc_attr( $key . '_' . $index ); ?>">X</i>

						
						 
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" class="lddfw_add_variable_button button-secondary" data-container="lddfw_variables_container_<?php echo esc_attr( $key ); ?>" style="margin-top: 10px;">
				+ <?php echo esc_html( __( 'Add Variable', 'lddfw' ) ); ?>
			</button>
		</div>
 </div>
		<?php
	}
	
	/**
     * Plugin settings.
     *
     * @since 1.0.0
     */
	public function lddfw_whatsapp_assign_to_driver() {


		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {

		 
			$this->lddfw_generate_whatsapp_settings__premium_only(
				'whatsapp_assign_to_driver',
				__( 'WhatsApp to the delivery driver when a new order is assigned.', 'lddfw' )
			);
			?>
			
			<?php
		}
	}
		echo lddfw_admin_premium_feature( '' );
	}
	
	
	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_whatsapp_api_phone() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<input type='text' class='regular-text' name='lddfw_whatsapp_api_phone' value='<?php echo esc_attr( get_option( 'lddfw_whatsapp_api_phone', '' ) ); ?>'>
		<p class="lddfw_description" id="lddfw-gooogle-api-key-description"><?php echo esc_html( __( 'Phone number to send WhatsApp should be in the following format (+)(country code)(area code)(phone number) e.g +15024658206', 'lddfw' ) ); ?></p>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_whatsapp_api_auth_token() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<input type='text' class='regular-text' name='lddfw_whatsapp_api_auth_token' value='<?php echo esc_attr( get_option( 'lddfw_whatsapp_api_auth_token', '' ) ); ?>'>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_whatsapp_api_sid() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<input type='text' class='regular-text' name='lddfw_whatsapp_api_sid' value='<?php echo esc_attr( get_option( 'lddfw_whatsapp_api_sid', '' ) ); ?>'>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_whatsapp_provider() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<select name='lddfw_whatsapp_provider'>
			<option value="twilio" <?php selected( esc_attr( get_option( 'lddfw_whatsapp_provider', '' ) ), 'twilio' ); ?>>twilio</option>
		</select>
		<p class="description" id="lddfw_sms_provider-description"><?php echo sprintf( esc_html( __( 'For more information about how to create a WhatsApp account %1$sclick here%2$s.', 'lddfw' ) ), '<a href="https://powerfulwp.com/docs/local-delivery-drivers-for-woocommerce-premium/getting-started/whatsapp-settings/" target="_blank">', '</a>' ); ?></p>
			
			
			<script>
    document.addEventListener('DOMContentLoaded', function () {
        const templateTags = "<?php echo addslashes( str_replace(["\r", "\n"], '', $this->lddfw_template_tags__premium_only()) ); ?>";

        // Attach delete listeners for existing and new delete buttons
        function lddfw_add_delete_listeners() {
            document.querySelectorAll('.lddfw_delete_variable_button').forEach(function (button) {
                button.removeEventListener('click', lddfw_handle_delete); // Remove old listeners
                button.addEventListener('click', lddfw_handle_delete); // Attach new listener
            });
        }

        // Handle deleting a variable
        function lddfw_handle_delete(event) {
            const targetId = event.target.dataset.target;
            const targetElement = document.getElementById(targetId);
			 
            if (targetElement) {
                const container = targetElement.closest('.lddfw_toggle_area'); // Parent container
                targetElement.remove(); // Remove the variable element
                lddfw_fix_variable_numbers(container); // Reorder and update selectors
            }
        }

        // Reorder variable numbers and update attributes after deletion
		function lddfw_fix_variable_numbers(container) {
    const variableInputs = container.querySelectorAll('.lddfw_variable_input');
    variableInputs.forEach((input, index) => {
        // Extract the key from the data-key attribute
        const key = input.dataset.key;

        // Update IDs for the variable container
        input.id = `lddfw_variable_${key}_${index}`;

        // Update the textarea's ID and name
        const textarea = input.querySelector('textarea');
        textarea.id = `lddfw_${key}_variables_${index}`;
        textarea.name = `lddfw_${key}_variables[${index}]`;
		 
        // Update the label
        const label = input.querySelector('label');
        label.textContent = `<?php echo esc_js( __('Add text or tag for variable', 'lddfw') ); ?> {{${index + 1}}}`;

        // Update the delete button's target
        const deleteButton = input.querySelector('.lddfw_delete_variable_button');
        deleteButton.dataset.target = input.id;
    });
}



        // Add a new variable dynamically
        document.querySelectorAll('.lddfw_add_variable_button').forEach(function (button) {
            button.addEventListener('click', function () {
                const container = document.getElementById(this.dataset.container);
                const count = container.querySelectorAll('.lddfw_variable_input').length;
                const key = this.dataset.container.replace('lddfw_variables_container_', '');

                // Create new variable input block
                const newVariable = `
                    <div class="lddfw_variable_input" data-key="${key}" id="lddfw_variable_${key}_${count}">
                        <label style="display:block; margin-bottom:10px;"><?php echo esc_js( __('Add text or tag for variable', 'lddfw') ); ?> {{${count + 1}}}</label>
                        <textarea class="large-text" id="lddfw_${key}_variables_${count}" name="lddfw_${key}_variables[${count}]" placeholder="<?php echo esc_js( __('Enter value', 'lddfw') ); ?>"></textarea>
                        
						
						<a href="#"  class="lddf_button_toggle" style="margin-top: 10px;">
                            <?php echo esc_js(__('Show/Hide Tags', 'lddfw')); ?>
                        </a>

						<p style="display:none" class="lddfw_description lddfw_copy_tags_to_textarea" data-textarea="lddfw_${key}_variables_${count}">
                            ${templateTags}
                        </p>

						<i class="lddfw_delete_variable_button" data-target="lddfw_variable_${key}_${count}" >X</i>
 
                        
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', newVariable);
				//lddfw_fix_variable_numbers(container);
                lddfw_add_delete_listeners(); // Reattach delete listeners for the new variable
            });
        });

        // Attach delete listeners on page load
        lddfw_add_delete_listeners();
    });
</script>



			<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin whatsapp settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_whatsapp_out_for_delivery() {
		if (lddfw_fs()->is__premium_only()) {
			if (lddfw_fs()->can_use_premium_code()) {
				// Define sections with optional additional tags
				$sections = [
					'whatsapp_out_for_delivery' => [
						'label' => __('WhatsApp to the customer when the order is out for delivery.', 'lddfw'),
					],
					'whatsapp_start_delivery' => [
						'label' => __('WhatsApp to the customer when the driver started the delivery.', 'lddfw'),
						'additional_tags' => [
							'[estimated_time_of_arrival]' => __('ETA', 'lddfw'),
							'[tracking_url]' => __('Tracking URL', 'lddfw'),
						],
					],
					'whatsapp_delivered' => [
						'label' => __('WhatsApp to the customer upon driver confirmation of delivery.', 'lddfw'),
					],
					'whatsapp_not_delivered' => [
						'label' => __('WhatsApp to the customer upon driver notification of non-delivery.', 'lddfw'),
					],
				];
	
				// Loop through each section and generate the settings
				foreach ($sections as $key => $data) {
					$label = $data['label'];
					$additional_tags = isset($data['additional_tags']) ? $data['additional_tags'] : [];
					$this->lddfw_generate_whatsapp_settings__premium_only($key, $label, $additional_tags);
				}
			}
		}
		echo lddfw_admin_premium_feature('');
	}

	 

		/**
		 * Plugin whatsapp settings.
		 *
		 * @since 1.0.0
		 */
	public function lddfw_whatsapp_start_delivery() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_whatsapp_start_delivery_template = get_option( 'lddfw_whatsapp_start_delivery_template', '' );
				$lddfw_whatsapp_start_delivery          = get_option( 'lddfw_whatsapp_start_delivery', '' );
				$checked                                = '1' === $lddfw_whatsapp_start_delivery ? 'checked' : '';
				?>
		<label for="lddfw_whatsapp_start_delivery" class='checkbox_toggle'>
			<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_whatsapp_start_delivery' id='lddfw_whatsapp_start_delivery' value='1'>
				<?php echo esc_html( __( 'WhatsApp to the customer when delivery has been started.', 'lddfw' ) ); ?>
		</label>
		<div class='lddfw_toggle_area' style='display:none'>
			<p style='margin-top:10px;margin-bottom:5px;'><?php echo esc_html( __( 'WhatsApp template', 'lddfw' ) ); ?>
				<?php echo '<a href="#" class="lddfw_copy_template_to_textarea" data="' . esc_attr( __( 'Hello [billing_first_name], your order #[order_id] with [store_name] delivery has been started.', 'lddfw' ) ) . '" ><b>' . esc_html( __( 'Default template', 'lddfw' ) ) . '</b></a>'; ?>
		</p>
			<textarea class='regular-text' name='lddfw_whatsapp_start_delivery_template' id='lddfw_whatsapp_start_delivery_template' style='min-width: 50%; height: 75px;'><?php echo esc_textarea( $lddfw_whatsapp_start_delivery_template ); ?></textarea>
			<p class="lddfw_description lddfw_copy_tags_to_textarea" data-textarea='lddfw_whatsapp_start_delivery_template'>
				<?php
				echo $this->lddfw_template_tags__premium_only() ;
				?>
			</p>
		</div>

				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}



	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_delivery_notes_section() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				echo esc_html( __( 'These are the driver ready notes for the delivery, to delete an option please leave blank.', 'lddfw' ) );
			}
		}
	}



	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_proof_of_delivery_signature_photo() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_proof_of_delivery_signature = get_option( 'lddfw_proof_of_delivery_signature' );
				$checked                           = ( '1' === $lddfw_proof_of_delivery_signature ) ? 'checked' : '';
				?>
				<p>
					<label for="lddfw_proof_of_delivery_signature">
						<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_proof_of_delivery_signature' id='lddfw_proof_of_delivery_signature' value='1'>
							<?php echo esc_html( __( 'Taking a customer\'s signature for proof of delivery is mandatory.', 'lddfw' ) ); ?>
					</label>
				</p>

				<?php
				$lddfw_proof_of_delivery_photo = get_option( 'lddfw_proof_of_delivery_photo' );
				$checked                       = ( '1' === $lddfw_proof_of_delivery_photo ) ? 'checked' : '';
				?>
				<p>
					<label for="lddfw_proof_of_delivery_photo">
						<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_proof_of_delivery_photo' id='lddfw_proof_of_delivery_photo' value='1'>
						<?php echo esc_html( __( 'Taking a photo for proof of delivery is mandatory.', 'lddfw' ) ); ?>
					</label>
				</p>

				<?php
					$lddfw_proof_of_delivery_one_mandatory = get_option( 'lddfw_proof_of_delivery_one_mandatory' );
					$checked                               = ( '1' === $lddfw_proof_of_delivery_one_mandatory ) ? 'checked' : '';
				?>
				<p>
					<label for="lddfw_proof_of_delivery_one_mandatory">
						<input <?php echo esc_attr( $checked ); ?> type='checkbox' class='regular-text' name='lddfw_proof_of_delivery_one_mandatory' id='lddfw_proof_of_delivery_photo' value='1'>
						<?php echo esc_html( __( 'Taking a photo or signature for proof of delivery is mandatory.', 'lddfw' ) ); ?>
					</label>
				</p>
				<?php

			}
		}
		echo lddfw_admin_premium_feature( '' );
	}




	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_failed_delivery_reason_1() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<p> 1. <input type='text' class='regular-text' name='lddfw_failed_delivery_reason_1' value='<?php echo esc_attr( get_option( 'lddfw_failed_delivery_reason_1', '' ) ); ?>'></p>
		<p> 2. <input type='text' class='regular-text' name='lddfw_failed_delivery_reason_2' value='<?php echo esc_attr( get_option( 'lddfw_failed_delivery_reason_2', '' ) ); ?>'></p>
		<p> 3. <input type='text' class='regular-text' name='lddfw_failed_delivery_reason_3' value='<?php echo esc_attr( get_option( 'lddfw_failed_delivery_reason_3', '' ) ); ?>'></p>
		<p> 4. <input type='text' class='regular-text' name='lddfw_failed_delivery_reason_4' value='<?php echo esc_attr( get_option( 'lddfw_failed_delivery_reason_4', '' ) ); ?>'></p>
		<p> 5. <input type='text' class='regular-text' name='lddfw_failed_delivery_reason_5' value='<?php echo esc_attr( get_option( 'lddfw_failed_delivery_reason_5', '' ) ); ?>'></p>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_delivery_dropoff_1() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				?>
		<p>1. <input type='text' class='regular-text' name='lddfw_delivery_dropoff_1' value='<?php echo esc_attr( get_option( 'lddfw_delivery_dropoff_1', '' ) ); ?>'></p>
		<p>2. <input type='text' class='regular-text' name='lddfw_delivery_dropoff_2' value='<?php echo esc_attr( get_option( 'lddfw_delivery_dropoff_2', '' ) ); ?>'></p>
		<p>3. <input type='text' class='regular-text' name='lddfw_delivery_dropoff_3' value='<?php echo esc_attr( get_option( 'lddfw_delivery_dropoff_3', '' ) ); ?>'></p>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_dispatch_phone_number() {
		?>
		<input type='text' class='regular-text' name='lddfw_dispatch_phone_number' value='<?php echo esc_attr( get_option( 'lddfw_dispatch_phone_number', '' ) ); ?>'>
		<p class="description" id="lddfw-gooogle-api-key-description"><?php echo esc_html( __( 'Drivers can call this number if they have questions about orders.', 'lddfw' ) ); ?></p>
		<?php
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_google_api_key() {
		?>
		<p class="description" id="lddfw-gooogle-api-key-description"><?php echo sprintf( esc_html( __( 'In order to use the Google Maps API, we need to create two keys for application restrictions purposes.%1$s For more information about how to create the Google API key %2$sclick here%3$s.', 'lddfw' ) ), '<br>', '<a href="https://powerfulwp.com/docs/local-delivery-drivers-for-woocommerce-premium/getting-started/how-to-generate-and-set-google-maps-api-keys/" target="_blank">', '</a>' ); ?></p>
		<p style="margin-top:20px">
			<input type='text' class='regular-text' name='lddfw_google_api_key' id='lddfw_google_api_key' value='<?php echo esc_attr( get_option( 'lddfw_google_api_key', '' ) ); ?>'><br>
			<span class="description" id="lddfw-gooogle-api-key-description"><?php echo esc_html( __( 'Key for Maps Embed API, Maps JavaScript API, Directions API and Geocoding API. ( Application restrictions: HTTP referrers )', 'lddfw' ) ); ?></span>
		</p>
		<p style="margin-top:20px">
			<input type='text' class='regular-text' name='lddfw_google_api_key_server' id='lddfw_google_api_key_server' value='<?php echo esc_attr( get_option( 'lddfw_google_api_key_server', '' ) ); ?>'><br>
			<span class="description" id="lddfw-gooogle-api-key-description"><?php echo esc_html( __( 'Key for Maps Directions API, Distance Matrix API and Geocoding API. ( Application restrictions: IP addresses )', 'lddfw' ) ); ?></span>
		</p>
		<p style="margin-top:20px">
			<a href="#" class="button button-secondary" data-loading="<?php echo esc_attr( __( 'Loading...', 'lddfw' ) ); ?>" data-title="<?php echo esc_attr( __( 'Test results for Key', 'lddfw' ) ); ?>" data-alert="<?php echo esc_attr( __( 'Please enter both Google keys.', 'lddfw' ) ); ?>" id="lddfw_check_google_keys"><?php echo esc_html( __( 'Test Your Google Keys', 'lddfw' ) ); ?></a>
		</p>
		<div id="lddfw_check_google_keys_wrap" style="display:none;"></div>
		<?php
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_processing_status() {
		$result = '';
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$result = wc_get_order_statuses();
		}

		?>
		<select name='lddfw_processing_status'>
			<?php
			if ( ! empty( $result ) ) {
				foreach ( $result as $key => $status ) {
					?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( esc_attr( get_option( 'lddfw_processing_status', '' ) ), $key ); ?>><?php echo esc_html( $status ); ?></option>
					<?php
				}
			}
			?>
		</select>
		<p class="lddfw_description" id="lddfw-gooogle-api-key-description"><?php echo esc_html( __( 'The orders are ready for delivery and drivers are able to claim.', 'lddfw' ) ); ?></p>
		<?php
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_failed_attempt_status() {
		$result = '';
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$result = wc_get_order_statuses();
		}

		?>
		<select name='lddfw_failed_attempt_status'>
			<?php
			if ( ! empty( $result ) ) {
				foreach ( $result as $key => $status ) {
					?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( esc_attr( get_option( 'lddfw_failed_attempt_status', '' ) ), $key ); ?>><?php echo esc_html( $status ); ?></option>
					<?php
				}
			}
			?>
		</select>
		<p class="lddfw_description" id="lddfw-gooogle-api-key-description"><?php echo esc_html( __( 'The delivery driver attempted to deliver but failed.', 'lddfw' ) ); ?></p>
		<?php
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_delivered_status() {
		$result = '';
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$result = wc_get_order_statuses();
		}

		?>
		<select name='lddfw_delivered_status'>
			<?php
			if ( ! empty( $result ) ) {
				foreach ( $result as $key => $status ) {
					?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( esc_attr( get_option( 'lddfw_delivered_status', '' ) ), $key ); ?>><?php echo esc_attr( $status ); ?></option>
					<?php
				}
			}
			?>
		</select>
		<p class="lddfw_description" id="lddfw-gooogle-api-key-description"><?php echo esc_html( __( 'The shipment was delivered successfully.', 'lddfw' ) ); ?></p>
		<?php
	}



	 /**
	  * Plugin settings.
	  *
	  * @since 1.0.0
	  */
	public function lddfw_driver_assigned_status() {
		$result = '';
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$result = wc_get_order_statuses();
		}
		?>
		<select name='lddfw_driver_assigned_status'>
			<?php
			if ( ! empty( $result ) ) {
				foreach ( $result as $key => $status ) {
					?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( esc_attr( get_option( 'lddfw_driver_assigned_status', '' ) ), $key ); ?>><?php echo esc_html( $status ); ?></option>
					<?php
				}
			}
			?>
		</select>
		<p class="lddfw_description" id="lddfw-gooogle-api-key-description"><?php echo esc_html( __( 'The delivery driver was assigned to order.', 'lddfw' ) ); ?></p>
		<?php
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_out_for_delivery_status() {
		$result = '';
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$result = wc_get_order_statuses();
		}
		?>
		<select name='lddfw_out_for_delivery_status'>
			<?php
			if ( ! empty( $result ) ) {
				foreach ( $result as $key => $status ) {
					?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( esc_attr( get_option( 'lddfw_out_for_delivery_status', '' ) ), $key ); ?>><?php echo esc_html( $status ); ?></option>
					<?php
				}
			}
			?>
		</select>
		<p class="lddfw_description" id="lddfw-gooogle-api-key-description"><?php echo esc_html( __( 'The delivery driver is about to deliver the shipment.', 'lddfw' ) ); ?></p>
		<?php
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_settings_section_callback() {
		echo esc_html( __( 'This Section Description', 'lddfw' ) );
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_settings() {

		// Default variables.
		$settings_title = esc_html( __( 'General Settings', 'lddfw' ) );

		// Get the current tab from the $_GET param.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		 // Tabs array.
		 $tabs = array(
			 array(
				 'slug'  => '',
				 'label' => esc_html( __( 'General settings', 'lddfw' ) ),
				 'title' => esc_html( __( 'General settings', 'lddfw' ) ),
				 'url'   => '?page=lddfw-settings',
			 ),
		 );

		 $premium_tabs = array(
			 array(
				 'slug'  => 'lddfw-drivers-settings',
				 'label' => esc_html( __( 'Drivers settings', 'lddfw' ) ),
				 'title' => esc_html( __( 'Drivers settings', 'lddfw' ) ),
				 'url'   => '?page=lddfw-settings&tab=lddfw-drivers-settings',
			 ),
			 array(
				 'slug'  => 'lddfw-sms-settings',
				 'label' => esc_html( __( 'SMS settings', 'lddfw' ) ),
				 'title' => esc_html( __( 'SMS settings', 'lddfw' ) ),
				 'url'   => '?page=lddfw-settings&tab=lddfw-sms-settings',
			 ),
			 array(
				 'slug'  => 'lddfw-whatsapp-settings',
				 'label' => esc_html( __( 'WhatsApp settings', 'lddfw' ) ),
				 'title' => esc_html( __( 'WhatsApp settings', 'lddfw' ) ),
				 'url'   => '?page=lddfw-settings&tab=lddfw-whatsapp-settings',
			 ),
			 array(
				 'slug'  => 'lddfw-branding',
				 'label' => esc_html( __( 'Branding', 'lddfw' ) ),
				 'title' => esc_html( __( 'Branding', 'lddfw' ) ),
				 'url'   => '?page=lddfw-settings&tab=lddfw-branding',
			 ),
			 array(
				 'slug'  => 'lddfw-tracking',
				 'label' => esc_html( __( 'Tracking', 'lddfw' ) ),
				 'title' => esc_html( __( 'Tracking', 'lddfw' ) ),
				 'url'   => '?page=lddfw-settings&tab=lddfw-tracking',
			 ),
		 );
		 $tabs         = array_merge( $tabs, $premium_tabs );

		 // Tabs filter.
		 if ( has_filter( 'lddfw_settings_tabs' ) ) {
			 $tabs = apply_filters( 'lddfw_settings_tabs', $tabs );
		 }

		 foreach ( $tabs as $tab ) {
			 if ( $current_tab === $tab['slug'] ) {
				 $settings_title = $tab['title'];
				 break;
			 }
		 }

			?>
		<div class="wrap">
		<form action='options.php' method='post'>
			<h1 class="wp-heading-inline"><?php echo $settings_title; ?></h1>
			<?php
				echo self::lddfw_admin_plugin_bar();
			if ( 1 < count( $tabs ) ) {
				?>
							<nav class="nav-tab-wrapper">
						<?php
						foreach ( $tabs as $tab ) {
							$url = ( '' !== $tab['slug'] ) ? 'admin.php?page=lddfw-settings&tab=' . esc_attr( $tab['slug'] ) : 'admin.php?page=lddfw-settings';
							echo '<a href="' . esc_html( admin_url( $url ) ) . '" class="nav-tab ' . ( $current_tab === $tab['slug'] ? 'nav-tab-active' : '' ) . '">' . esc_html( $tab['label'] ) . '</a>';
						}
						?>
							</nav>
						<?php
			}

				echo '<hr class="wp-header-end">';

			foreach ( $tabs as $tab ) {
				if ( '' === $current_tab ) {
					settings_fields( 'lddfw' );
					do_settings_sections( 'lddfw' );
					break;
				} elseif ( $current_tab === $tab['slug'] ) {
					settings_fields( $tab['slug'] );
					do_settings_sections( $tab['slug'] );
					break;
				}
			}

				submit_button();

			?>
		</form>
	</div>
		<?php
	}

	/**
	 * Plugin submenu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function lddfw_admin_menu() {
		// add menu to main menu.
		 add_menu_page( esc_html( __( 'Delivery Drivers Settings', 'lddfw' ) ), esc_html( __( 'Delivery Drivers', 'lddfw' ) ), 'edit_pages', 'lddfw-dashboard', array( &$this, 'lddfw_dashboard' ), 'dashicons-location', 56 );
		 add_submenu_page( 'lddfw-dashboard', esc_html( __( 'Dashboard', 'lddfw' ) ), esc_html( __( 'Dashboard', 'lddfw' ) ), 'edit_pages', 'lddfw-dashboard', array( &$this, 'lddfw_dashboard' ) );
		 add_submenu_page( 'lddfw-dashboard', esc_html( __( 'Routes', 'lddfw' ) ), esc_html( __( 'Routes', 'lddfw' ) ), 'edit_pages', 'lddfw-routes', array( &$this, 'lddfw_routes' ) );
		 add_submenu_page( 'lddfw-dashboard', esc_html( __( 'Reports', 'lddfw' ) ), esc_html( __( 'Reports', 'lddfw' ) ), 'edit_pages', 'lddfw-reports', array( &$this, 'lddfw_reports' ) );
		 add_submenu_page( 'lddfw-dashboard', esc_html( __( 'Settings', 'lddfw' ) ), esc_html( __( 'Settings', 'lddfw' ) ), 'edit_pages', 'lddfw-settings', array( &$this, 'lddfw_settings' ) );
		 add_submenu_page( 'lddfw-dashboard', esc_html( __( 'App', 'lddfw' ) ), esc_html( __( 'App', 'lddfw' ) ), 'edit_pages', 'lddfw-app', array( &$this, 'app' ) );
		 if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				add_submenu_page( 'lddfw-dashboard', esc_html( __( 'Custom Fields', 'lddfw' ) ), esc_html( __( 'Custom Fields', 'lddfw' ) ), 'manage_options', 'edit.php?post_type=lddfw_custom_fields' );
				add_submenu_page( 'lddfw-dashboard', esc_html( __( 'Driver Pages', 'lddfw' ) ), esc_html( __( 'Driver Pages', 'lddfw' ) ), 'manage_options', 'edit.php?post_type=lddfw_driver_pages' );
			}
		}
	}

	 /**
	  * Admin plugin bar.
	  *
	  * @since 1.1.0
	  * @return html
	  */
	public static function lddfw_admin_plugin_bar() {
		return '<div class="lddfw_admin_bar">' . esc_html( __( 'Developed by', 'lddfw' ) ) . ' <a href="https://powerfulwp.com/" target="_blank">PowerfulWP</a> | <a href="https://powerfulwp.com/local-delivery-drivers-for-woocommerce-premium/" target="_blank" >' . esc_html( __( 'Premium', 'lddfw' ) ) . '</a> | <a href="https://powerfulwp.com/docs/local-delivery-drivers-for-woocommerce-premium/" target="_blank" >' . esc_html( __( 'Documents', 'lddfw' ) ) . '</a></div>';
	}


	/**
	 * Plugin dashboard.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_dashboard() {
		$dashboard = new LDDFW_Reports();
		echo $dashboard->screen_dashboard();
	}

	/**
	 * Plugin reports.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_reports() {
		$reports = new LDDFW_Reports();
		echo $reports->screen_reports();
	}

	/**
	 * Admin routes screen.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_routes() {

		if ( lddfw_is_free() ) {
			$content = lddfw_admin_premium_feature( '' ) . ' ' . esc_html( __( "View drivers' routes on a map.", 'lddfw' ) ) . '
					<hr>' . lddfw_admin_premium_feature( '' ) . ' ' . esc_html( __( "View routes' duration and distance.", 'lddfw' ) ) . '
					<hr>
					' . esc_html( __( 'Upgrading to Premium will unlock it.', 'lddfw' ) ) . '
					<br><a target="_blank" href="https://powerfulwp.com/local-delivery-drivers-for-woocommerce-premium#pricing" class="lddfw_premium_buynow">' . esc_html( __( 'UNLOCK PREMIUM', 'lddfw' ) ) . '</a>
					<br>
					<img style="max-width:100%" src="' . plugins_url() . '/' . LDDFW_FOLDER . '/public/images/routes-preview.png?ver=' . LDDFW_VERSION . '">
					';
			echo lddfw_premium_feature_notice_content( $content );
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
					$route = new LDDFW_Route();
					echo $route->lddfw_admin_routes_screen();
			}
		}
	}


	/**
	 * Users list columns.
	 *
	 * @param array $column column.
	 * @return array
	 */
	public function lddfw_users_list_columns( $column ) {
		if ( isset( $_GET['role'] ) && 'driver' === $_GET['role'] ) {

			$column['lddfw_driver_availability'] = 'Availability';
			$column['lddfw_driver_claim']        = 'Claim orders';
			$column['lddfw_driver_account']      = 'Account';

		}
		return $column;
	}

	/**
	 * Users list columns.
	 *
	 * @param string $val value.
	 * @param string $column_name column name.
	 * @param int    $user_id user id.
	 * @since 1.1.2
	 * @return html
	 */
	public function lddfw_users_list_columns_raw( $val, $column_name, $user_id ) {
		$availability_icon   = '';
		$driver_claim_icon   = '';
		$driver_account_icon = '';

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$availability   = get_user_meta( $user_id, 'lddfw_driver_availability', true );
				$driver_claim   = get_user_meta( $user_id, 'lddfw_driver_claim', true );
				$driver_account = get_user_meta( $user_id, 'lddfw_driver_account', true );
				/**
				 * Driver status icons and counters.
				 */

				if ( '1' === $availability ) {
					$availability_icon = '<a href="#" class="lddfw_availability_icon lddfw_icon lddfw_active" driver_id="' . esc_attr( $user_id ) . '" ><i class="lddfw-toggle-on"></i></a>';
				} else {
					$availability_icon = '<a href="#" class="lddfw_availability_icon lddfw_icon" driver_id="' . esc_attr( $user_id ) . '" ><i class="lddfw-toggle-off"></i></a>';
				}

				if ( '1' === $driver_claim ) {
					$driver_claim_icon = '<a href="#" class="lddfw_claim_icon lddfw_icon lddfw_active" driver_id="' . esc_attr( $user_id ) . '" ><i class="lddfw-toggle-on"></i></a>';
				} else {
					$driver_claim_icon = '<a href="#" class="lddfw_claim_icon lddfw_icon" driver_id="' . esc_attr( $user_id ) . '" ><i class="lddfw-toggle-off"></i></a>';
				}

				if ( '1' === $driver_account ) {
					$driver_account_icon = '<a href="#" class="lddfw_account_icon lddfw_icon lddfw_active" driver_id="' . esc_attr( $user_id ) . '" ><i class="lddfw-toggle-on"></i></a>';
				} else {
					$driver_account_icon = '<a href="#" class="lddfw_account_icon lddfw_icon" driver_id="' . esc_attr( $user_id ) . '" ><i class="lddfw-toggle-off"></i></a>';
				}
			}
		}
		switch ( $column_name ) {
			case 'lddfw_driver_availability':
				return lddfw_admin_premium_feature( $availability_icon );
			case 'lddfw_driver_claim':
				return lddfw_admin_premium_feature( $driver_claim_icon );
			case 'lddfw_driver_account':
				return lddfw_admin_premium_feature( $driver_account_icon );
			default:
		}

		return $val;

	}


	/**
	 * Print driver name in column
	 *
	 * @param string $column column name.
	 * @param int    $post_id post number.
	 * @since 1.0.0
	 */
	public function lddfw_orders_list_columns( $column, $post_id ) {

		switch ( $column ) {
			case 'Driver':
				$order          = wc_get_order( $post_id );
				$lddfw_driverid = $order->get_meta( 'lddfw_driverid' );
				$user           = get_user_by( 'id', $lddfw_driverid );
				if ( ! empty( $user ) ) {
					echo esc_html( $user->display_name );
				}
				break;
		}
	}

	/**
	 * Columns order
	 *
	 * @param array $columns columns array.
	 * @since 1.0.0
	 * @return array
	 */
	public function lddfw_orders_list_columns_order( $columns ) {
		$reordered_columns = array();

		// Inserting columns to a specific location.
		foreach ( $columns as $key => $column ) {
			$reordered_columns[ $key ] = $column;
			if ( 'order_status' === $key ) {
				// Inserting after "Status" column.
				$reordered_columns['Driver'] = __( 'Driver', 'lddfw' );
			}
		}
		return $reordered_columns;
	}

	/**
	 * Sortable columns
	 *
	 * @param array $columns columns array.
	 * @since 1.0.0
	 * @return array
	 */
	public function lddfw_orders_list_sortable_columns( $columns ) {
		$columns['Driver'] = 'Driver';
		return $columns;
	}

	/**
	 * Save user fields
	 *
	 * @since 1.0.0
	 * @param int $user_id user id.
	 */
	public function lddfw_user_fields_save( $user_id ) {

		// Capability Check: Ensure the current user can edit the target user.
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return; // Use return; for action hooks.
		}

		// Verify User Existence: Ensure the user being edited actually exists.
		$user_meta = get_userdata( $user_id );
		if ( ! $user_meta ) {
			// User doesn't exist, nothing to do.
			return;
		}
		$user_roles = (array) $user_meta->roles;

		// Determine Relevant Roles: Check if the user is a Driver or a configured Vendor.
		$is_driver = in_array( 'driver', $user_roles, true );
		$is_vendor = false;

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$store     = new LDDFW_Store();
				$is_vendor = in_array( $store->lddfw_vendor_role__premium_only( LDDFW_MULTIVENDOR ), (array) $user_roles, true );
			}
		}
		
		// Check if relevant roles exist. If not, no fields managed by this function need saving.
		if ( ! $is_driver && ! $is_vendor ) {
			return;
		}

		// Nonce Verification: Protect against CSRF attacks.
		// IMPORTANT: This assumes the nonce field 'lddfw_nonce_user' is added in lddfw_user_fields()
		// if the user being edited has *either* the 'driver' or 'vendor' role.
		// If the nonce is only added for 'driver', this check will incorrectly fail for vendor-only edits.
		$nonce_key = 'lddfw_nonce_user';
		if ( ! isset( $_REQUEST[ $nonce_key ] ) ) {
			// Nonce field is missing. This could indicate an issue with the form generation
			// or an attempt to bypass security. Consider logging or adding an admin notice.
			return;
		}
		$retrieved_nonce = sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_key ] ) );
		if ( ! wp_verify_nonce( $retrieved_nonce, basename( __FILE__ ) ) ) {
			// Invalid nonce. This could be a CSRF attempt or an expired form.
			// Consider logging or adding an admin notice.
			return;
		}
		
		// Save driver settings.
		if ( $is_driver  ) {
			$lddfw_driver_account      = ( isset( $_POST['lddfw_driver_account'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_account'] ) ) : '';
			$lddfw_driver_availability = ( isset( $_POST['lddfw_driver_availability'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_availability'] ) ) : '';
			update_user_meta( $user_id, 'lddfw_driver_account', $lddfw_driver_account );
			update_user_meta( $user_id, 'lddfw_driver_availability', $lddfw_driver_availability );

			if ( lddfw_fs()->is__premium_only() ) {
				if ( lddfw_fs()->can_use_premium_code() ) {
					$lddfw_driver_claim         = ( isset( $_POST['lddfw_driver_claim'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_claim'] ) ) : '';
					$lddfw_driver_image         = ( isset( $_POST['lddfw_driver_image'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_image'] ) ) : '';
					$lddfw_driver_vehicle       = ( isset( $_POST['lddfw_driver_vehicle'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_vehicle'] ) ) : '';
					$lddfw_driver_licence_plate = ( isset( $_POST['lddfw_driver_licence_plate'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_licence_plate'] ) ) : '';
					$lddfw_driver_travel_mode   = ( isset( $_POST['lddfw_driver_travel_mode'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_travel_mode'] ) ) : '';
					$lddfw_driver_app_mode      = ( isset( $_POST['lddfw_driver_app_mode'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_app_mode'] ) ) : '';
					$lddfw_driver_cities_json   = ( isset( $_POST['lddfw_driver_cities'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_driver_cities'] ) ) : '';

					update_user_meta( $user_id, 'lddfw_driver_app_mode', $lddfw_driver_app_mode );
					update_user_meta( $user_id, 'lddfw_driver_travel_mode', $lddfw_driver_travel_mode );
					update_user_meta( $user_id, 'lddfw_driver_claim', $lddfw_driver_claim );
					update_user_meta( $user_id, 'lddfw_driver_image', $lddfw_driver_image );
					update_user_meta( $user_id, 'lddfw_driver_vehicle', $lddfw_driver_vehicle );
					update_user_meta( $user_id, 'lddfw_driver_licence_plate', $lddfw_driver_licence_plate );
					

					// Decode the JSON string from Tagify
					$tags_data = json_decode( $lddfw_driver_cities_json );
					$tag_values = []; // Initialize an empty array

					if ( is_array( $tags_data ) ) {
						// Extract just the 'value' properties into a simple array
						$tag_values = array_map( function( $tag_object ) {
							return isset( $tag_object->value ) ? sanitize_text_field( $tag_object->value ) : null;
						}, $tags_data );

						// Remove any null values
						$tag_values = array_filter( $tag_values, function( $value ) {
							return $value !== null;
						} );

						// Ensure the array is re-indexed if you need consecutive keys
						$tag_values = array_values( $tag_values );
					}

					// Save the processed array (WordPress will serialize it automatically)
					update_user_meta( $user_id, 'lddfw_driver_cities', $tag_values );

				}
			}
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				// Save driver / vendor pickup coordinates.
				if ( $is_driver || $is_vendor ) {
					$latitude  = ( isset( $_POST['lddfw_address_latitude'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_address_latitude'] ) ) : '';
					$longitude = ( isset( $_POST['lddfw_address_longitude'] ) ) ? sanitize_text_field( wp_unslash( $_POST['lddfw_address_longitude'] ) ) : '';
					if ( '' === $latitude || '' === $longitude ) {
						update_user_meta( $user_id, 'lddfw_address_latitude', '' );
						update_user_meta( $user_id, 'lddfw_address_longitude', '' );
						$route = new LDDFW_Route();
						if ( $is_driver ) {
							$route->set_driver_geocode( $user_id );
						} else {
							$route->set_seller_geocode( $user_id );
						}
					} else {
						update_user_meta( $user_id, 'lddfw_address_latitude', $latitude );
						update_user_meta( $user_id, 'lddfw_address_longitude', $longitude );
					}
				}
			}
		}
	}

	/**
	 * Get user fields
	 *
	 * @since 1.0.0
	 * @param object $user user data object.
	 */
	public function lddfw_user_fields( $user ) {
		if ( in_array( 'driver', (array) $user->roles, true ) ) {
			wp_nonce_field( basename( __FILE__ ), 'lddfw_nonce_user' );
			?>
			<h3><?php echo esc_html( __( 'Delivery Driver Info', 'lddfw' ) ); ?></h3>
			<table class="form-table">
			<tr>
					<th><label for="lddfw_driver_account"><?php echo esc_html( __( 'Driver account status', 'lddfw' ) ); ?></label></th>
					<td>
						<select name="lddfw_driver_account" id="lddfw_driver_account">
							<option value="0"><?php echo esc_html( __( 'Not active', 'lddfw' ) ); ?></option>
							<?php $selected = get_user_meta( $user->ID, 'lddfw_driver_account', true ) === '1' ? 'selected' : ''; ?>
							<option <?php echo esc_attr( $selected ); ?> value="1"><?php echo esc_html( __( 'Active', 'lddfw' ) ); ?></option>
						</select>
						<p class="lddfw_description"><?php echo esc_html( __( 'Only drivers with active accounts can access the drivers\' panel.', 'lddfw' ) ); ?></p>
					</td>
			</tr>
			<tr>
					<th><label for="lddfw_driver_availability"><?php echo esc_html( __( 'Driver availability', 'lddfw' ) ); ?></label></th>
					<td>
						<select name="lddfw_driver_availability" id="lddfw_driver_availability">
							<option value="0"><?php echo esc_html( __( 'Unavailable', 'lddfw' ) ); ?></option>
							<?php $selected = get_user_meta( $user->ID, 'lddfw_driver_availability', true ) === '1' ? 'selected' : ''; ?>
							<option <?php echo esc_attr( $selected ); ?> value="1"><?php echo esc_html( __( 'Available', 'lddfw' ) ); ?></option>
						</select>
						<p class="lddfw_description"><?php echo esc_html( __( 'The delivery driver availability for work today.', 'lddfw' ) ); ?></p>
					</td>
			</tr>
			<tr>
					<th><label for="lddfw_driver_app_mode"><?php echo esc_html( __( 'Driver panel theme', 'lddfw' ) ); ?></label></th>
					<td>
							<?php
							$html = '';
							if ( lddfw_fs()->is__premium_only() ) {
								if ( lddfw_fs()->can_use_premium_code() ) {
									// Get user app mode.
									$lddfw_app_mode = get_user_meta( $user->ID, 'lddfw_driver_app_mode', true );
									// If empty get admin setting app mode.
									$lddfw_app_mode = '' === $lddfw_app_mode ? get_option( 'lddfw_app_mode', '' ) : $lddfw_app_mode;
									$html           = '
									<label for="lddfw_driver_app_mode">
									<select name="lddfw_driver_app_mode">
										<option value="light" ' . selected( esc_attr( $lddfw_app_mode ), 'light', false ) . '>' . esc_html( __( 'Light Mode', 'lddfw' ) ) . '</option>
										<option value="dark" ' . selected( esc_attr( $lddfw_app_mode ), 'dark', false ) . '>' . esc_html( __( 'Dark Mode', 'lddfw' ) ) . '</option>
									</select>
									</label>';
								}
							}
							echo lddfw_admin_premium_feature( $html );
							?>
					</td>
			</tr>
			<tr>
				<th><label for="lddfw_driver_travel_mode"><?php echo esc_html( __( 'Transportation Mode', 'lddfw' ) ); ?></label></th>
				<td>
					<?php
						$html = '';
					if ( lddfw_fs()->is__premium_only() ) {
						if ( lddfw_fs()->can_use_premium_code() ) {
							$lddfw_driver_travel_mode = get_user_meta( $user->ID, 'lddfw_driver_travel_mode', true );

							$html = '
								<select class="form-control small-select" name="lddfw_driver_travel_mode" id="lddfw_driver_travel_mode">
										<option ' . selected( 'DRIVING', $lddfw_driver_travel_mode, false ) . ' value="DRIVING"> ' . esc_html( __( 'Driving', 'lddfw' ) ) . ' </option>
										<option ' . selected( 'WALKING', $lddfw_driver_travel_mode, false ) . ' value="WALKING"> ' . esc_html( __( 'Walking', 'lddfw' ) ) . ' </option>
										<option ' . selected( 'BICYCLING', $lddfw_driver_travel_mode, false ) . ' value="BICYCLING"> ' . esc_html( __( 'Bicycling', 'lddfw' ) ) . ' </option>
										<option ' . selected( 'TRANSIT', $lddfw_driver_travel_mode, false ) . ' value="TRANSIT"> ' . esc_html( __( 'Transit', 'lddfw' ) ) . ' </option>
									</select>
									<p class="lddfw_description">' . esc_html( __( 'When you calculate directions, you may specify the transportation mode to use.', 'lddfw' ) ) . ' </p>';
						}
					}
						echo lddfw_admin_premium_feature( $html );
					?>
				</td>
			</tr>
			 
			<tr>
					<th><label for="lddfw_driver_claim"><?php echo esc_html( __( 'Driver can claim orders', 'lddfw' ) ); ?></label></th>
					<td>
					<?php
						$html = '';
					if ( lddfw_fs()->is__premium_only() ) {
						if ( lddfw_fs()->can_use_premium_code() ) {
							$selected = get_user_meta( $user->ID, 'lddfw_driver_claim', true ) === '1' ? 'selected' : '';
							$html     = '<select name="lddfw_driver_claim" id="lddfw_driver_claim">
								<option value="0">' . esc_html( __( 'No', 'lddfw' ) ) . '</option>
								<option ' . esc_attr( $selected ) . ' value="1" >' . esc_html( __( 'Yes', 'lddfw' ) ) . '</option>
								</select>
								<p class="lddfw_description">' . esc_html( __( 'Give the driver permission to claim orders.', 'lddfw' ) ) . '</p>';
						}
					}
						echo lddfw_admin_premium_feature( $html );
					?>

					</td>
			</tr>
			<tr>
				<th><label for="lddfw_driver_image"><?php echo esc_html( __( 'Driver Photo', 'lddfw' ) ); ?></label></th>
				<td>
					<?php
						$html = '';
					if ( lddfw_fs()->is__premium_only() ) {
						if ( lddfw_fs()->can_use_premium_code() ) {
							$image_id = get_user_meta( $user->ID, 'lddfw_driver_image', true );
							$image    = '';
							if ( intval( $image_id ) > 0 ) {
								$image = wp_get_attachment_image_src( $image_id, 'medium' )[0];
								if ( '' !== $image ) {
									$image = '<img src="' . $image . '">';
								}
							}
							$html = '<div id="lddfw_driver_image_preview" class="lddfw_media_preview" >' . $image . '</div>
								 <input type="hidden" name="lddfw_driver_image" id="lddfw_driver_image" value="' . esc_attr( $image_id ) . '" class="regular-text" />
								 <input type="button" class="button-primary lddfw_media_manager" data="lddfw_driver_image" value="' . esc_attr( __( 'Select a image', 'lddfw' ) ) . '" id="lddfw_driver_media_manager"/>
								 <input type="button" class="button-secondary lddfw_media_delete" data="lddfw_driver_image" value="' . esc_attr( __( 'Delete image', 'lddfw' ) ) . '" id="lddfw_driver_media_delete"/>';
						}
					}
						echo lddfw_admin_premium_feature( $html );
					?>
				</td>
			</tr>
			<tr>
				<th><label for="lddfw_driver_vehicle"><?php echo esc_html( __( 'Vehicle type', 'lddfw' ) ); ?></label></th>
				<td>
					<?php
						$html = '';
					if ( lddfw_fs()->is__premium_only() ) {
						if ( lddfw_fs()->can_use_premium_code() ) {
							$html = '<input type="text" name="lddfw_driver_vehicle" id="lddfw_driver_vehicle" value="' . get_user_meta( $user->ID, 'lddfw_driver_vehicle', true ) . '">
								<p class="lddfw_description">' . esc_html( __( 'Write the driver vehicle type / model.', 'lddfw' ) ) . '</p>';
						}
					}
						echo lddfw_admin_premium_feature( $html );
					?>
				</td>
			</tr>
			<tr>
				<th><label for="lddfw_driver_licence_plate"><?php echo esc_html( __( 'License Plate', 'lddfw' ) ); ?></label></th>
				<td>
				<?php
					$html = '';
				if ( lddfw_fs()->is__premium_only() ) {
					if ( lddfw_fs()->can_use_premium_code() ) {
						$html = '<input type="text" name="lddfw_driver_licence_plate" id="lddfw_driver_licence_plate" value="' . get_user_meta( $user->ID, 'lddfw_driver_licence_plate', true ) . '">
							<p class="lddfw_description">' . esc_html( __( 'License Plate', 'lddfw' ) ) . '</p>';
					}
				}
					echo lddfw_admin_premium_feature( $html );
				?>
				</td>
			</tr>
			<tr>
				<th><label for="lddfw_driver_cities"><?php echo esc_html( __( 'Driver Cities', 'lddfw' ) ); ?></label></th>
				<td>
					<?php
					$html = '';
					// Add premium feature check
					if ( lddfw_fs()->is__premium_only() ) {
						if ( lddfw_fs()->can_use_premium_code() ) {
							// Fetch saved cities for the user
							$saved_cities = get_user_meta( $user->ID, 'lddfw_driver_cities', true );
						 	 
							// Ensure it's a string for the value attribute
							$cities_value = is_array( $saved_cities ) ? implode( ',', $saved_cities ) : $saved_cities;
  
							// Fetch the billing city
							$billing_city = get_user_meta( $user->ID, 'billing_city', true );
							$billing_city_text = $billing_city ? sprintf( esc_html__( 'Current city: %s.', 'lddfw' ), '<strong>' . esc_html( $billing_city ) . '</strong>' ) : "";

							// Add billing city info before the input
							$html = '<p class="description">' . $billing_city_text . '</p>'; 
							
							// The Tagify input
							$html .= '<input name="lddfw_driver_cities" id="lddfw_driver_cities" value="' . esc_attr( $cities_value ) . '" class="lddfw_tagify regular-text" autofocus>';
							
							// Updated description with fallback logic
							$html .= '<p class="lddfw_description">' . esc_html( __( 'Enter the specific cities this driver operates in (separate with commas or Enter). If left empty, the driver\'s billing city will be used as their delivery area.', 'lddfw' ) ) . '</p>';
						}
					}
					// Use the premium feature output function
					echo lddfw_admin_premium_feature( $html ); 
					?>
				</td>
			</tr>
			</table>

			<?php
				// Add action.
				do_action( 'lddfw_driver_fields', $user );
			?>
			<?php
		}

		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				// Address Coordinates.
				$store = new LDDFW_Store();
				if ( in_array( 'driver', (array) $user->roles, true ) || in_array( $store->lddfw_vendor_role__premium_only( LDDFW_MULTIVENDOR ), (array) $user->roles, true ) ) {
					wp_nonce_field( basename( __FILE__ ), 'lddfw_nonce_user' );
					?>
					<h3>
					<?php
					echo ( in_array( 'driver', (array) $user->roles, true ) ) ? esc_html( __( 'Delivery driver address coordinates', 'lddfw' ) ) : esc_html( __( 'Pickup address coordinates', 'lddfw' ) );
					?>
					</h3>
					<p>
					<?php
						// Show note when google_api_key exist.
						$route                = new LDDFW_Route();
						$lddfw_google_api_key = $route->lddfw_google_api_key_server;
					if ( '' === $lddfw_google_api_key ) {
						$lddfw_google_api_key = $route->lddfw_google_api_key;
					}
					if ( '' !== $lddfw_google_api_key ) {
						echo esc_html( __( 'Leave blank for auto-filling.', 'lddfw' ) );
					}
					?>
					</p>
					<table class="form-table">
					<tr>
						<th><label for="lddfw_address_latitude"><?php echo esc_html( __( 'Latitude', 'lddfw' ) ); ?></label></th>
						<td>
						<?php
							$html = '';
						if ( lddfw_fs()->is__premium_only() ) {
							if ( lddfw_fs()->can_use_premium_code() ) {
								$html = '<input type="text" name="lddfw_address_latitude" id="lddfw_address_latitude" value="' . get_user_meta( $user->ID, 'lddfw_address_latitude', true ) . '">
										<p class="lddfw_description">' . esc_html( __( 'e.g. 37.819722', 'lddfw' ) ) . '</p>';
							}
						}
							echo lddfw_admin_premium_feature( $html );
						?>
						</td>
					</tr>
					<tr>
						<th><label for="lddfw_address_longitude"><?php echo esc_html( __( 'Longitude', 'lddfw' ) ); ?></label></th>
						<td>
						<?php
							$html = '';
						if ( lddfw_fs()->is__premium_only() ) {
							if ( lddfw_fs()->can_use_premium_code() ) {
								$html = '<input type="text" name="lddfw_address_longitude" id="lddfw_address_longitude" value="' . get_user_meta( $user->ID, 'lddfw_address_longitude', true ) . '">
									<p class="lddfw_description">' . esc_html( __( 'e.g. -122.478611', 'lddfw' ) ) . '</p>';
							}
						}
							echo lddfw_admin_premium_feature( $html );
						?>
						</td>
					</tr>
					</table>
					<?php
				}
			}
		}

	}



	/**
	 * Bulk edit assign to driver
	 *
	 * @since 1.0.0
	 * @param array $actions edit action array.
	 * @return array
	 */
	public function lddfw_bulk_actions_edit__premium_only( $actions ) {

		$lddfw_out_for_delivery_status_name = esc_html( __( 'Out for delivery', 'lddfw' ) );
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$result = wc_get_order_statuses();
			if ( ! empty( $result ) ) {
				foreach ( $result as $key => $status ) {
					switch ( $key ) {
						case get_option( 'lddfw_out_for_delivery_status' ):
							if ( $status !== $lddfw_out_for_delivery_status_name ) {
								$lddfw_out_for_delivery_status_name = $status;
							}
							break;
					}
				}
			}
		}

		$actions['mark_out_for_delivery'] = __( 'Change status to', 'lddfw' ) . ' ' . strtolower( $lddfw_out_for_delivery_status_name );
		$actions['assign_a_driver']       = __( 'Assign a driver to orders', 'lddfw' );
		return $actions;
	}

	/**
	 * Plugin custom email class
	 *
	 * @since 1.0.0
	 * @param array $email_classes email classes.
	 * @return array
	 */
	public function lddfw_woocommerce_emails__premium_only( $email_classes ) {
		// Add the email class to the list of email classes that WooCommerce loads.
		$email_classes['LDDFW_Out_For_Delivery_Email']      = include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-out-for-delivery-email.php';
		$email_classes['LDDFW_Failed_Delivery_Email']       = include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-failed-delivery-email.php';
		$email_classes['LDDFW_Delivered_Email_Admin']       = include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-delivered-email-admin.php';
		$email_classes['LDDFW_Assigned_Order_Email_Driver'] = include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-assigned-order-email-driver.php';
		$email_classes['LDDFW_Assigned_Order_Email_Vendor'] = include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-assigned-order-email-vendor.php';
		$email_classes['LDDFW_Assigned_Order_Email_Admin']  = include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-assigned-order-email-admin.php';
		$email_classes['LDDFW_Start_Delivery_Email']        = include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lddfw-start-delivery-email.php';
		return $email_classes;
	}


	/**
	 * Plugin locate template
	 *
	 * @param array  $template template data array.
	 * @param string $template_name template name.
	 * @param string $template_path template path.
	 * @return string
	 */
	public function lddfw_woocommerce_locate_template__premium_only( $template, $template_name, $template_path ) {
		global $woocommerce;

		$_template = $template;

		if ( ! $template_path ) {
			$template_path = $woocommerce->template_url;
		}

		$plugin_path = plugin_dir_path( dirname( __FILE__ ) ) . 'woocommerce/';

		// Look within passed path within the theme - this is priority.
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name,
			)
		);

		// Modification: Get the template from this plugin, if it exists.
		if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
			$template = $plugin_path . $template_name;
		}

		// Use default template.
		if ( ! $template ) {
			$template = $_template;
		}

		// Return what we found.
		return $template;
	}

	/**
	 * Plugin bulk actions , assign an order to driver
	 *
	 * @param string $redirect_to redirect to url.
	 * @param string $action action.
	 * @param array  $post_ids array of posts.
	 * @return array
	 */
	public function lddfw_handle_bulk_actions__premium_only( $redirect_to, $action, $post_ids ) {
  
		// Update order status.
		if ( 'mark_out_for_delivery' === $action ) {
			$out_for_delivery_status = get_option( 'lddfw_out_for_delivery_status', '' );
			if ( '' !== $out_for_delivery_status ) {
				foreach ( $post_ids as $post_id ) {
					$order = wc_get_order( $post_id );
					$order->update_status( $out_for_delivery_status, '' );
				}
			}
		}

		// Assign a driver to order.
		if ( 'assign_a_driver' === $action ) {
			$driver    = new LDDFW_Driver();
		  	$lddfw_driverid_action  = ( isset( $_GET['lddfw_driverid_lddfw_action'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_driverid_lddfw_action'] ) ) : '';
			$lddfw_driverid_action2 = ( isset( $_GET['lddfw_driverid_lddfw_action2'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_driverid_lddfw_action2'] ) ) : '';
			$action_get             = ( isset( $_GET['action'] ) ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
			$action2_get            = ( isset( $_GET['action2'] ) ) ? sanitize_text_field( wp_unslash( $_GET['action2'] ) ) : '';
			$driver_id              = '';
			if ( $action === $action_get && '' !== $lddfw_driverid_action ) {
				$driver_id = $lddfw_driverid_action;
			}

			if ( $action === $action2_get && '' !== $lddfw_driverid_action2 ) {
				$driver_id = $lddfw_driverid_action2;
			}

			$processed_ids = array();
			foreach ( $post_ids as $post_id ) {
				// Assign driver to order.
				$driver->assign_delivery_driver( $post_id, $driver_id, 'store' );
				if ( '' === $driver_id ) {
					/**
					 * Delete if none
					 */
					$order = wc_get_order( $post_id );
					$order->delete_meta_data( 'lddfw_driverid' );
					lddfw_update_sync_order( $post_id, 'lddfw_driverid', '0' );
					$order->save();
				}

				$processed_ids[] = $post_id;
				$redirect_to     = add_query_arg(
					array(
						'processed_count' => count( $processed_ids ),
						'processed_ids'   => implode( ',', $processed_ids ),
					),
					$redirect_to
				);
			}
		}
		return $redirect_to;
	}



	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_delivery_drivers_page() {

		$args  = array(
			'sort_order'   => 'asc',
			'sort_column'  => 'post_title',
			'hierarchical' => 1,
			'exclude'      => '',
			'include'      => '',
			'meta_key'     => '',
			'meta_value'   => '',
			'authors'      => '',
			'child_of'     => 0,
			'parent'       => -1,
			'exclude_tree' => '',
			'number'       => '',
			'offset'       => 0,
			'post_type'    => 'page',
			'post_status'  => 'publish',
		);
		$pages = get_pages( $args );

		?>
		<select name='lddfw_delivery_drivers_page'>
			<?php
			if ( ! empty( $pages ) ) {
				foreach ( $pages as $page ) {
					$page_id    = $page->ID;
					$page_title = $page->post_title;
					?>
					<option value="<?php echo esc_attr( $page_id ); ?>" <?php selected( esc_attr( get_option( 'lddfw_delivery_drivers_page', '' ) ), $page_id ); ?>><?php echo esc_html( $page_title ); ?></option>
					<?php
				}
			}
			?>
		</select>
		<p class="lddfw_description" id="lddfw-driver_app-description">
		<?php
			echo '<div class="driver_app">
				<img alt="' . esc_attr__( 'Drivers app', 'lddfw' ) . '" title="' . esc_attr__( 'Drivers app', 'lddfw' ) . '" src="' . esc_attr( plugins_url() . '/' . LDDFW_FOLDER . '/public/images/drivers_app.png?ver=' . LDDFW_VERSION ) . '">
				<p>
					<b><a target="_blank" href="' . lddfw_drivers_page_url( '' ) . '">' . lddfw_drivers_page_url( '' ) . '</a></b><br>' .
					sprintf( esc_html( __( 'The link above is the delivery driver\'s Mobile-Friendly panel URL. %1$s The delivery drivers can access it from their mobile phones. %2$s', 'lddfw' ) ), '<br>', '<br>' ) .
					sprintf( esc_html( __( 'Notice: If you want to be logged in as an administrator and to check the drivers\' panel on the same device, %1$s %2$syou must work with two different browsers otherwise you will log out from the admin panel and the drivers\' panel won\'t function correctly.%3$s', 'lddfw' ) ), '<br>', '<b>', '</b>' ) . '
				</p>
			</div>';
		?>
		</p>
		<?php
	}

		/**
		 * Plugin settings.
		 *
		 * @since 1.0.0
		 */
	public function lddfw_tracking_page() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {

				$args  = array(
					'sort_order'   => 'asc',
					'sort_column'  => 'post_title',
					'hierarchical' => 1,
					'exclude'      => '',
					'include'      => '',
					'meta_key'     => '',
					'meta_value'   => '',
					'authors'      => '',
					'child_of'     => 0,
					'parent'       => -1,
					'exclude_tree' => '',
					'number'       => '',
					'offset'       => 0,
					'post_type'    => 'page',
					'post_status'  => 'publish',
				);
				$pages = get_pages( $args );
				?>
		<select name='lddfw_tracking_page'>
				<?php
				if ( ! empty( $pages ) ) {
					foreach ( $pages as $page ) {
						$page_id    = $page->ID;
						$page_title = $page->post_title;
						?>
						<option value="<?php echo esc_attr( $page_id ); ?>" <?php selected( esc_attr( get_option( 'lddfw_tracking_page', '' ) ), $page_id ); ?>><?php echo esc_html( $page_title ); ?></option>
						<?php
					}
				}
				?>
		</select>
		<div class="lddfw_description" id="lddfw-tracking_page-description">
				<?php
				echo '<div class="tracking_page">
			<br>
			<p><b>' . esc_html( __( 'About the tracking page:', 'lddfw' ) ) . '</b></p>
					<p>* ' . esc_html( __( 'The customer\'s order tracking page is only active when the order status is out for delivery. On other statuses, the tracking page redirects to the homepage.', 'lddfw' ) ) . '</p>
					<p>* ' . esc_html( __( 'Email/SMS/WhatsApp sends the tracking URL. Please add the following [tracking_url] tag to the message.', 'lddfw' ) ) . '</p>
					<p>* ' . esc_html( __( 'For the tracking map, please enable Geocoding API on both google API keys.', 'lddfw' ) ) . '</p>
			</div>';
				?>
		</div>
				<?php
			}
		}

		echo lddfw_admin_premium_feature( '' );
	}



	/**
	 * Admin order filters.
	 *
	 * @since 1.1.0
	 */
	public function lddfw_orders_filter__premium_only() {

			$current = isset( $_GET['lddfw_orders_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_orders_filter'] ) ) : '';
			echo '<select name="lddfw_orders_filter">
			<option value="">' . __( 'Filter By', 'lddfw' ) . '</option>
			<option value="-1" ';
			echo '-1' === $current ? 'selected' : '';
			echo '>' . __( 'With drivers', 'lddfw' ) . '</option>
			<option value="-2" ';
			echo '-2' === $current ? 'selected' : '';
			echo '>' . __( 'Without drivers', 'lddfw' ) . '</option>
			';
			echo '<optgroup label="' . esc_attr( __( 'Drivers', 'lddfw' ) ) . '"></optgroup>';
			$drivers = LDDFW_Driver::lddfw_get_drivers();
		foreach ( $drivers as $driver ) {
			$driver_name = $driver->display_name;
			$selected    = ( strval( $driver->ID ) === $current ) ? 'selected' : '';
			echo '<option ' . esc_attr( $selected ) . ' value="' . esc_attr( $driver->ID ) . '">' . esc_html( $driver_name ) . '</option>';
		}
			echo '</select>';

			$lddfw_from_date = ( isset( $_GET['lddfw_from_date'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_from_date'] ) ) : '';
			$lddfw_to_date   = ( isset( $_GET['lddfw_to_date'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_to_date'] ) ) : '';
		if ( '' !== $lddfw_from_date && '' !== $lddfw_to_date ) {
			echo '<input type="hidden" name="lddfw_from_date" value="' . $lddfw_from_date . '">';
			echo '<input type="hidden" name="lddfw_to_date" value="' . $lddfw_to_date . '">';
		}

	}

	/**
	 * Add content to emails
	 *
	 * @param object $order order object.
	 * @param string $sent_to_admin text.
	 * @param string $plain_text text.
	 * @param object $email email.
	 * @since 1.3.0
	 */
	public function lddfw_woocommerce_add_content_specific_email__premium_only( $order, $sent_to_admin, $plain_text, $email ) {
		
		if ( 'customer_completed_order' === $email->id ) {
			$lddfw_driver_id = $order->get_meta( 'lddfw_driverid' );
			$email_type      = $email->email_type;

			$driver = new LDDFW_Driver();
			if ( '' !== $lddfw_driver_id ) {
				echo $driver->get_driver_info__premium_only( $lddfw_driver_id, $email_type );
				echo $driver->get_vehicle_info__premium_only( $lddfw_driver_id, $email_type );
			}
			
			if ( 'html' === $email_type ) {

					/* driver note */
					$lddfw_driver_note = $order->get_meta( 'lddfw_driver_note' );
				if ( '' !== $lddfw_driver_note ) {
					echo '<p><b>' . esc_html( __( 'Driver note', 'lddfw' ) ) . ':</b><br> ' . $lddfw_driver_note . '</p>';
				}

					// Signature.
					$lddfw_order_signature = $order->get_meta( 'lddfw_order_last_signature' );
					
				if ( '' !== $lddfw_order_signature ) {
					echo '<p><b>';
					echo esc_html( __( 'Signature', 'lddfw' ) ) . '</b><br>';
					echo '<a href="' . esc_attr( $lddfw_order_signature ) . '" target="_blank"><img style="max-width:100%" src="' . esc_attr( $lddfw_order_signature ) . '"></a>';
					echo '</p>';
				}

				// Display Delivery Photo(s).
				$lddfw_order_delivery_image_meta = $order->get_meta( 'lddfw_order_last_delivery_image', false );
				if ( ! empty( $lddfw_order_delivery_image_meta ) ) {
					// Check if this is a JSON array of images.
					if ( is_array( $lddfw_order_delivery_image_meta ) ) {
						echo esc_html( __( 'Photos', 'lddfw' ) ) . "\n";

						// Sort the meta data by ID in descending order (last added first).
						usort( $lddfw_order_delivery_image_meta, function( $a, $b ) {
							if ( ! is_a( $a, 'WC_Meta_Data' ) || ! is_a( $b, 'WC_Meta_Data' ) ) {
								return 0;
							}
							return $b->id <=> $a->id; // <-- reversed here
						});

						// Multiple images stored as WC_Meta_Data objects.
						foreach ( $lddfw_order_delivery_image_meta as $image ) {
							if ( is_a( $image, 'WC_Meta_Data' ) ) {
								$data = $image->get_data();
								if ( isset( $data['value'] ) && is_string( $data['value'] ) && filter_var( $data['value'], FILTER_VALIDATE_URL ) ) {
									echo '<p><a href="' . esc_attr( $data['value'] ) . '" target="_blank"><img style="max-width:100%" src="' . esc_attr( $data['value'] ) . '"></a></p>';
								}
							}
						}
					} elseif ( is_string( $lddfw_order_delivery_image_meta ) && filter_var( $lddfw_order_delivery_image_meta, FILTER_VALIDATE_URL ) ) {
						echo esc_html( __( 'Photo', 'lddfw' ) ) . "\n";
						// Display single image (original functionality).
						echo '<p><a href="' . esc_attr( $lddfw_order_delivery_image_meta ) . '" target="_blank"><img style="max-width:100%" src="' . esc_attr( $lddfw_order_delivery_image_meta ) . '"></a></p>';
					}
				}


			} else {

					/* driver note */
					$lddfw_driver_note = $order->get_meta( 'lddfw_driver_note' );
				if ( '' !== $lddfw_driver_note ) {
					echo esc_html( __( 'Driver note', 'lddfw' ) ) . ': ' . esc_html( $lddfw_driver_note ) . "\n";
				}

					// Signature.
					$lddfw_order_signature = $order->get_meta( 'lddfw_order_last_signature' );
				if ( '' !== $lddfw_order_signature ) {
					echo esc_html( __( 'Signature', 'lddfw' ) ) . "\n";
					echo '<a href="' . esc_attr( $lddfw_order_signature ) . '" target="_blank">' . esc_attr( $lddfw_order_signature ) . '</a>' . "\n";
				}

				// Display Delivery Photo(s).
				$lddfw_order_delivery_image_meta = $order->get_meta( 'lddfw_order_last_delivery_image', false );
				if ( ! empty( $lddfw_order_delivery_image_meta ) ) {
					// Check if this is a JSON array of images.
					if ( is_array( $lddfw_order_delivery_image_meta ) ) {
						echo esc_html( __( 'Photos', 'lddfw' ) ) . "\n";

						// Sort the meta data by ID in descending order (last added first).
						usort( $lddfw_order_delivery_image_meta, function( $a, $b ) {
							if ( ! is_a( $a, 'WC_Meta_Data' ) || ! is_a( $b, 'WC_Meta_Data' ) ) {
								return 0;
							}
							return $b->id <=> $a->id; // <-- reversed here
						});

						// Multiple images stored as WC_Meta_Data objects.
						foreach ( $lddfw_order_delivery_image_meta as $image ) {
							if ( is_a( $image, 'WC_Meta_Data' ) ) {
								$data = $image->get_data();
								if ( isset( $data['value'] ) && is_string( $data['value'] ) && filter_var( $data['value'], FILTER_VALIDATE_URL ) ) {
									echo '<a href="' . esc_attr( $data['value'] ) . '" target="_blank">' . esc_html( basename( $data['value'] ) ) . '</a>' . "\n";
								}
							}
						}
					} elseif ( is_string( $lddfw_order_delivery_image_meta ) && filter_var( $lddfw_order_delivery_image_meta, FILTER_VALIDATE_URL ) ) {
						echo esc_html( __( 'Photo', 'lddfw' ) ) . "\n";
						// Display single image (original functionality).
						echo '<a href="' . esc_attr( $lddfw_order_delivery_image_meta ) . '" target="_blank">' . esc_html( basename( $lddfw_order_delivery_image_meta ) ) . '</a>' . "\n";
					}
				}

			}
		}
	}

	/**
	 * Exclude custom fields.
	 *
	 * @param array  $protected fields array.
	 * @param string $meta_key meta key.
	 * @since 1.3.0
	 * @return array
	 */
	public function lddfw_exclude_custom_fields( $protected, $meta_key ) {

		if ( in_array( $meta_key, lddfw_allow_protected_order_custom_fields(), true ) ) {
			return false;
		}

		if ( in_array( $meta_key, array( 'lddfw_driver_commission', 'lddfw_order_route', 'lddfw_order_last_delivery_image', 'lddfw_order_delivery_image', 'lddfw_order_last_signature', 'lddfw_order_signature', 'lddfw_failed_attempt_date', 'lddfw_delivered_date', 'lddfw_driverid' ), true ) ) {
			return true;
		}

		  return $protected;
	}


	/**
	 * Shipping settings fields.
	 *
	 * @param array $settings settings array.
	 * @since 1.6.0
	 * @return array
	 */
	public function lddfw_shipping_settings_fields__premium_only( $settings ) {
			$settings['lddfw_shipping_disable_auto_assign'] = array(
				'title'       => esc_html__( 'Disable auto assign drivers for this method.', 'lddfw' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => '',
			);
			$settings['lddfw_shipping_disable_claim']       = array(
				'title'       => esc_html__( 'Disable claim orders for this method.', 'lddfw' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => '',
			);
			return $settings;
	}
	/**
	 * Shipping settings.
	 *
	 * @since 1.6.0
	 */
	public function lddfw_shipping_settings__premium_only() {
		$shipping_methods = WC()->shipping->get_shipping_methods();
		foreach ( $shipping_methods as $shipping_method ) {
			add_filter( 'woocommerce_shipping_instance_form_fields_' . $shipping_method->id, array( $this, 'lddfw_shipping_settings_fields__premium_only' ) );
		}
	}


	/**
	 * Admin order HPOS filters process.
	 *
	 * @param object $query query.
	 * @since 1.1.0
	 */
	public function lddfw_orders_filter_process_hpos__premium_only( $vars ) {
		global $pagenow, $typenow;

		if ( isset( $_GET['lddfw_orders_filter'] ) && '' !== $_GET['lddfw_orders_filter'] &&
			 ( 'shop_order' === $vars['type'] )

			) {

			 
			 
			$lddfw_orders_filter = ( isset( $_GET['lddfw_orders_filter'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_orders_filter'] ) ) : '';
			$lddfw_from_date     = ( isset( $_GET['lddfw_from_date'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_from_date'] ) ) : '';
			$lddfw_to_date       = ( isset( $_GET['lddfw_to_date'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_to_date'] ) ) : '';

			// filter by orders without drivers.
			if ( '-2' === $lddfw_orders_filter ) {
				$vars['meta_query'][] = array(
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

			// filter by orders without drivers.
			if ( '-1' === $lddfw_orders_filter ) {
				$vars['meta_query'][] = array(
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

			// filter by driver id.
			if ( 0 < intval( $lddfw_orders_filter ) ) {
				$vars['meta_query'][] = array(
					'key'     => 'lddfw_driverid',
					'value'   => $lddfw_orders_filter,
					'compare' => '=',
				);
			}

			// filter for delivered date range.
			if ( '' !== $lddfw_from_date && '' !== $lddfw_to_date ) {
				$vars['meta_query'][] = array(
					'relation' => 'and',
					array(
						'key'     => 'lddfw_delivered_date',
						'value'   => $lddfw_from_date,
						'compare' => '>=',
						'type'    => 'DATE',
					),
					array(
						'key'     => 'lddfw_delivered_date',
						'value'   => $lddfw_to_date,
						'compare' => '<=',
						'type'    => 'DATE',
					),
				);
			}
		}

		return $vars;
	}
	/**
	 * Admin order filters process.
	 *
	 * @param object $query query.
	 * @since 1.1.0
	 */
	public function lddfw_orders_filter_process__premium_only( $query ) {
		global $pagenow;

		if ( $query->is_admin && 'edit.php' === $pagenow && isset( $_GET['lddfw_orders_filter'] )
		&& '' !== $_GET['lddfw_orders_filter'] && isset( $_GET['post_type'] ) && 'shop_order' === $_GET['post_type'] ) {

			 
			  
			$lddfw_orders_filter = ( isset( $_GET['lddfw_orders_filter'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_orders_filter'] ) ) : '';
			$lddfw_from_date     = ( isset( $_GET['lddfw_from_date'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_from_date'] ) ) : '';
			$lddfw_to_date       = ( isset( $_GET['lddfw_to_date'] ) ) ? sanitize_text_field( wp_unslash( $_GET['lddfw_to_date'] ) ) : '';

			// filter by orders without drivers.
			if ( '-2' === $lddfw_orders_filter ) {
				$query->query_vars['meta_query'][] = array(
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

			// filter by orders without drivers.
			if ( '-1' === $lddfw_orders_filter ) {
				$query->query_vars['meta_query'][] = array(
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

			// filter by driver id.
			if ( 0 < intval( $lddfw_orders_filter ) ) {
				$query->query_vars['meta_query'][] = array(
					'key'     => 'lddfw_driverid',
					'value'   => $lddfw_orders_filter,
					'compare' => '=',
				);
			}

			// filter for delivered date range.
			if ( '' !== $lddfw_from_date && '' !== $lddfw_to_date ) {
				$query->query_vars['meta_query'][] = array(
					'relation' => 'and',
					array(
						'key'     => 'lddfw_delivered_date',
						'value'   => $lddfw_from_date,
						'compare' => '>=',
						'type'    => 'DATE',
					),
					array(
						'key'     => 'lddfw_delivered_date',
						'value'   => $lddfw_to_date,
						'compare' => '<=',
						'type'    => 'DATE',
					),
				);
			}
		}

	}

	/**
	 * Daily event.
	 */
	public function lddfw_daily_event__premium_only() {
		// Delete from tracking table.
		lddfw_delete_from_tracking_table__premium_only();
	}

	/**
	 * Displays the app promotion banner or modified content if the add-on plugin is active.
	 *
	 * This function checks whether the "App for Delivery Drivers Premium" plugin is active.
	 * If the plugin is active and a filter `lddfw_app_settings_content` is available, it applies the filter 
	 * to allow modification of the content. Otherwise, it displays a default promotional banner linking to the app page.
	 *
	 * @since 1.9.7
	 * @return void Outputs the promotional content or modified content.
	 */
	public function app(){
		$addon_plugin_path = 'app-for-delivery-drivers-premium/app-for-delivery-drivers.php';
		$content = '<br><a target="_blank" href="https://powerfulwp.com/app-for-delivery-drivers"><img style="max-width:100%" src="' . plugins_url() . '/' . LDDFW_FOLDER . '/public/images/app.gif?ver=' . LDDFW_VERSION . '"></a>';
			  
		if ( is_plugin_active( $addon_plugin_path ) && has_filter( 'lddfw_app_settings_content' ) ) {
			 echo apply_filters('lddfw_app_settings_content', $content);
		 } else {
			echo $content;
		}
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function lddfw_proof_of_delivery_max_images() {
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->can_use_premium_code() ) {
				$lddfw_proof_of_delivery_max_images = get_option( 'lddfw_proof_of_delivery_max_images', '1' );
				?>
				<select name="lddfw_proof_of_delivery_max_images" class="regular-text">
					<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
						<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $lddfw_proof_of_delivery_max_images, $i ); ?>><?php echo esc_html( $i ); ?></option>
					<?php endfor; ?>
				</select>
				<p class="lddfw_description" id="lddfw-max_images-description"><?php echo esc_html( __( 'Maximum number of images allowed for proof of delivery.', 'lddfw' ) ); ?></p>
				<?php
			}
		}
		echo lddfw_admin_premium_feature( '' );
	}
}
