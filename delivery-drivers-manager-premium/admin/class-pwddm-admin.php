<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    pwddm
 * @subpackage pwddm/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    pwddm
 * @subpackage pwddm/admin
 * @author     powerfulwp <cs@powerfulwp.com>
 */
class pwddm_Admin {

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
		$this->version      = $version;
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
		   * defined in pwddm_Loader as all of the hooks are defined
		   * in that particular class.
		   *
		   * The pwddm_Loader will then create the relationship
		   * between the defined hooks and the functions defined in this
		   * class.
		   */
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/pwddm-admin.css', array(), $this->version, 'all' );
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
		 * defined in pwddm_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The pwddm_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$script_array = array( 'jquery' );

		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->is_plan( 'premium', true ) ) {
				$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
				if ( 'pwddm-branding' === $tab ) {
					// add media script.
					wp_enqueue_media();

					// add color picker script and media.
					$script_array = array( 'jquery', 'wp-color-picker' );
				}
			}
		}
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/pwddm-admin.js', $script_array, $this->version, false );
		wp_localize_script( $this->plugin_name, 'pwddm_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_localize_script( $this->plugin_name, 'pwddm_nonce', array( 'nonce' => esc_js( wp_create_nonce( 'pwddm-nonce' ) ) ) );

	}


	/**
	 * The function that handles ajax requests.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function pwddm_ajax() {
		$pwddm_data_type  = ( isset( $_POST['pwddm_data_type'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_data_type'] ) ) : '';
		$pwddm_obj_id     = ( isset( $_POST['pwddm_obj_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_obj_id'] ) ) : '';
		$pwddm_service    = ( isset( $_POST['pwddm_service'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_service'] ) ) : '';
		$pwddm_manager_id = ( isset( $_POST['pwddm_manager_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_manager_id'] ) ) : '';
		$pwddm_driver_id  = ( isset( $_POST['pwddm_driver_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_driver_id'] ) ) : '';
		$result           = 0;

		 /**
		 * Security check.
		 */
		 if ( ! isset( $_POST['pwddm_wpnonce'] )
		 || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pwddm_wpnonce'] ) ), 'pwddm-nonce' ) )   {
				$error = esc_js( __( 'Security Check Failure - This alert may occur when you are logged in as an administrator and as a delivery driver on the same browser and the same device. If you want to work on both panels please try to work with two different browsers.', 'pwddm' ) );
				if ( 'json' === $pwddm_data_type ) {
					echo "{\"result\":\"$result\",\"error\":\"$error\"}";
				} else {
					echo '<div class=\'alert alert-danger alert-dismissible fade show\'>' . $error . '<button type=\'button\' class=\'close\' data-dismiss=\'alert\' aria-label=\'Close\'><span aria-hidden=\'true\'>&times;</span></button></div>';
				}
				exit;
		 } 
		 

		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->can_use_premium_code() ) {

				// Plan route service.
				if ( 'pwddm_plain_route' === $pwddm_service ) {
						$route                   = new PWDDM_Route();
						$origin_map_address      = ( isset( $_POST['pwddm_origin_map_address'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_origin_map_address'] ) ) : '';
						$destination_map_address = ( isset( $_POST['pwddm_destination_map_address'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_destination_map_address'] ) ) : '';
						echo $route->pwddm_plain_route__premium_only( $pwddm_driver_id, $origin_map_address, $destination_map_address );
				}

				/* Save route */
				if ( 'pwddm_save_route' === $pwddm_service ) {
					$route                   = new PWDDM_Route();
					$origin_map_address      = ( isset( $_POST['pwddm_origin_map_address'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_origin_map_address'] ) ) : '';
					$origin_address          = ( isset( $_POST['pwddm_origin_address'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_origin_address'] ) ) : '';
					$destination_map_address = ( isset( $_POST['pwddm_destination_map_address'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_destination_map_address'] ) ) : '';
					$destination_address     = ( isset( $_POST['pwddm_destination_address'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_destination_address'] ) ) : '';
					$route_array             = array(
						'origin_map_address'      => $origin_map_address,
						'origin_address'          => $origin_address,
						'destination_map_address' => $destination_map_address,
						'destination_address'     => $destination_address,
						'date_created'            => date_i18n( 'Y-m-d H:i:s' ),
					);

					$orders_list = ( isset( $_POST['pwddm_orders_list'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_orders_list'] ) ) : '';
					echo $route->pwddm_save_route__premium_only( $pwddm_driver_id, $orders_list, $route_array );
				}

				/* drivers routes service */
				if ( 'pwddm_drivers_routes' === $pwddm_service ) {
					$route = new pwddm_Route();
					echo $route->pwddm_drivers_routes__premium_only( $pwddm_manager_id );
				}

				/* set geocode service */
				if ( 'pwddm_set_order_geocode' === $pwddm_service ) {
					$route = new PWDDM_Route();
					$route->set_store_geocode();
					echo $route->set_order_geocode_service__premium_only();
				}

				/* Orders service */
				if ( 'pwddm_orders' === $pwddm_service ) {
					$orders = new PWDDM_Orders();
					// echo $orders->pwddm_orders_json__premium_only( $pwddm_manager_id );
					echo $orders->pwddm_orders_route__premium_only( $pwddm_manager_id );
				}
			}
		}

		/* Update order */
		if ( 'pwddm_update_order' === $pwddm_service ) {
			$pwddm_orders_status  = ( isset( $_POST['pwddm_orders_status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_orders_status'] ) ) : '';
			$pwddm_assign_drivers = ( isset( $_POST['pwddm_assign_drivers'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_assign_drivers'] ) ) : '';
			$order                = new pwddm_Order();
			echo $order->pwddm_order_update( $pwddm_obj_id, $pwddm_manager_id, $pwddm_orders_status, $pwddm_assign_drivers );
		}

		/* Login manager service */
		if ( 'pwddm_login' === $pwddm_service ) {
			$login = new pwddm_Login();
			echo $login->pwddm_login_manager();
		}

		/* Send reset password link */
		if ( 'pwddm_forgot_password' === $pwddm_service ) {
			$password = new pwddm_Password();
			echo $password->pwddm_reset_password();
		}

		/* Create a new password*/
		if ( 'pwddm_newpassword' === $pwddm_service ) {
			$password = new pwddm_Password();
			echo $password->pwddm_new_password();
		}

		/*
		Log out manager.
		*/
		if ( 'pwddm_logout' === $pwddm_service ) {
			pwddm_Login::pwddm_logout();
		}

		/*
		New driver.
		*/
		if ( 'pwddm_new_driver' === $pwddm_service ) {
			$driver = new pwddm_Driver();
			echo $driver->pwddm_create_new_driver_service();
		}

		/*
		Edit driver.
		*/
		if ( 'pwddm_edit_driver' === $pwddm_service ) {
			$driver = new pwddm_Driver();
			echo $driver->pwddm_edit_driver_service();
		}

		/*
		Edit settings.
		*/
		if ( 'pwddm_edit_settings' === $pwddm_service ) {
			$manager = new pwddm_Manger();
			echo $manager->pwddm_edit_settings_service();
		}

		/*
		Set manager account status.
		*/
		if ( 'pwddm_account_status' === $pwddm_service ) {

			$user = wp_get_current_user();

			// Check if user is an administrator.
			if ( in_array( 'administrator', (array) $user->roles, true ) ) {
				$user = get_user_by( 'id', $pwddm_manager_id );

				// Check if user has a manager role.
				if ( in_array( pwddm_manager_role(), (array) $user->roles, true ) ) {
					$status = ( isset( $_POST['pwddm_account_status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_account_status'] ) ) : '';
					update_user_meta( $pwddm_manager_id, 'pwddm_manager_account', $status );
					$result = 1;
				}
				echo esc_html( $result );
			}
		}

		/*
		Set driver account status.
		*/
		if ( 'pwddm_driver_account_status' === $pwddm_service ) {

			$user = wp_get_current_user();

			// Switch to manager user if administrator is logged in.
			if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $pwddm_manager_id ) {
				$user = get_user_by( 'id', $pwddm_manager_id );
			}

			// Check if user has a manager role.
			if ( in_array( pwddm_manager_role(), (array) $user->roles, true ) ) {

				// Check if driver has a driver role.
				$user = get_user_by( 'id', $pwddm_driver_id );
				if ( in_array( 'driver', (array) $user->roles, true ) ) {

					$status = ( isset( $_POST['pwddm_status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_status'] ) ) : '';
					update_user_meta( $pwddm_driver_id, 'lddfw_driver_account', $status );
					$result = 1;
				}
			}

			echo esc_html( $result );
		}

		/*
		Set driver claim permission.
		*/
		if ( 'pwddm_claim_permission' === $pwddm_service ) {

			$user = wp_get_current_user();

			// Switch to manager user if administrator is logged in.
			if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $pwddm_manager_id ) {
				$user = get_user_by( 'id', $pwddm_manager_id );
			}

			// Check if user has a manager role.
			if ( in_array( pwddm_manager_role(), (array) $user->roles, true ) ) {

				// Check if driver has a driver role.
				$user = get_user_by( 'id', $pwddm_driver_id );
				if ( in_array( 'driver', (array) $user->roles, true ) ) {

					$status = ( isset( $_POST['pwddm_status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_status'] ) ) : '';
					update_user_meta( $pwddm_driver_id, 'lddfw_driver_claim', $status );
					$result = 1;
				}
			}

			echo esc_html( $result );
		}

		/*
		Set driver availability.
		*/
		if ( 'pwddm_availability' === $pwddm_service ) {
			$user = wp_get_current_user();

			// Switch to manager user if administrator is logged in.
			if ( in_array( 'administrator', (array) $user->roles, true ) && '' !== $pwddm_manager_id ) {
				$user = get_user_by( 'id', $pwddm_manager_id );
			}

			// Check if user has a manager role.
			if ( in_array( pwddm_manager_role(), (array) $user->roles, true ) ) {

				// Check if driver has a driver role.
				$user = get_user_by( 'id', $pwddm_driver_id );
				if ( in_array( 'driver', (array) $user->roles, true ) ) {

					$status = ( isset( $_POST['pwddm_status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_status'] ) ) : '';
					update_user_meta( $pwddm_driver_id, 'lddfw_driver_availability', $status );
					$result = 1;
				}
			}

			echo esc_html( $result );
		}

		// Set logo service.
		if ( 'pwddm_set_image' === $pwddm_service ) {
			$pwddm_image_id = ( isset( $_POST['pwddm_image_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_image_id'] ) ) : '';
			if ( '' !== $pwddm_image_id ) {
				$image = wp_get_attachment_image( filter_input( INPUT_POST, 'pwddm_image_id', FILTER_VALIDATE_INT ), 'medium', false, array() );
				$data  = array(
					'image' => $image,
				);
				wp_send_json_success( $data );
			} else {
				wp_send_json_error();
			}
		}

		exit;
	}

	/**
	 * Plugin register settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function pwddm_settings_init() {
		 // Get settings tab.
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		register_setting( 'pwddm', 'pwddm_manager_page' );
		register_setting( 'pwddm-branding', 'pwddm_branding_logo' );

		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->can_use_premium_code() ) {
				// Branding.
				register_setting( 'pwddm-branding', 'pwddm_branding_background' );
				register_setting( 'pwddm-branding', 'pwddm_branding_text_color' );
				register_setting( 'pwddm-branding', 'pwddm_branding_button_color' );
				register_setting( 'pwddm-branding', 'pwddm_branding_button_background' );
				register_setting( 'pwddm-branding', 'pwddm_branding_title' );
				register_setting( 'pwddm-branding', 'pwddm_branding_subtitle' );
			}
		}

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'pwddm_admin_notices' ) );

		if ( '' === $tab ) {

			// General Settings.
			add_settings_section(
				'pwddm_setting_section',
				'',
				'',
				'pwddm'
			);

			add_settings_field(
				'pwddm_manager_page',
				__( 'Drivers manager panel', 'pwddm' ),
				array( $this, 'pwddm_manager_page' ),
				'pwddm',
				'pwddm_setting_section'
			);

			if ( pwddm_is_free() ) {

				add_settings_field(
					'pwddm_premium_features',
					__( 'Premium main features', 'pwddm' ),
					array( $this, 'pwddm_premium_features' ),
					'pwddm',
					'pwddm_setting_section'
				);

			}
		}
		if ( 'pwddm-branding' === $tab ) {

			add_settings_section(
				'pwddm_branding',
				__( 'Manager initial screen', 'pwddm' ),
				'',
				'pwddm-branding'
			);

			add_settings_field(
				'pwddm_branding_logo',
				__( 'Logo', 'pwddm' ),
				array( $this, 'pwddm_branding_logo' ),
				'pwddm-branding',
				'pwddm_branding'
			);
			add_settings_field(
				'pwddm_branding_title',
				__( 'Title', 'pwddm' ),
				array( $this, 'pwddm_branding_title' ),
				'pwddm-branding',
				'pwddm_branding'
			);

			add_settings_field(
				'pwddm_branding_subtitle',
				__( 'Subtitle', 'pwddm' ),
				array( $this, 'pwddm_branding_subtitle' ),
				'pwddm-branding',
				'pwddm_branding'
			);

			add_settings_field(
				'pwddm_branding_background',
				__( 'Page background', 'pwddm' ),
				array( $this, 'pwddm_branding_background' ),
				'pwddm-branding',
				'pwddm_branding'
			);

			add_settings_field(
				'pwddm_branding_text_color',
				__( 'Text color', 'pwddm' ),
				array( $this, 'pwddm_branding_text_color' ),
				'pwddm-branding',
				'pwddm_branding'
			);
			add_settings_field(
				'pwddm_branding_button_color',
				__( 'Button text color', 'pwddm' ),
				array( $this, 'pwddm_branding_button_color' ),
				'pwddm-branding',
				'pwddm_branding'
			);
			add_settings_field(
				'pwddm_branding_button_background',
				__( 'Button background', 'pwddm' ),
				array( $this, 'pwddm_branding_button_background' ),
				'pwddm-branding',
				'pwddm_branding'
			);

		}
	}


	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function pwddm_settings() {
		// Get the active tab from the $_GET param.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		$tabs = $this->pwddm_settings_tabs();

		// Default variables.
		$settings_title = esc_html( __( 'General Settings', 'pwddm' ) );
		foreach ( $tabs as $tab ) {
			if ( $current_tab === $tab['slug'] ) {
				$settings_title = $tab['title'];
				break;
			}
		}

		?>
		<div class="wrap">
			<form action='options.php' method='post'>
				<h1 class="wp-heading-inline"><?php echo esc_html( $settings_title ); ?></h1>
				<?php
				 echo self::pwddm_admin_plugin_bar();

				if ( 1 < count( $tabs ) ) {
					?>
								<nav class="nav-tab-wrapper">
						  <?php
							foreach ( $tabs as $tab ) {
								$url = ( '' !== $tab['slug'] ) ? 'admin.php?page=pwddm-settings&tab=' . esc_attr( $tab['slug'] ) : 'admin.php?page=pwddm-settings';
								echo '<a href="' . esc_html( admin_url( $url ) ) . '" class="nav-tab ' . ( $current_tab === $tab['slug'] ? 'nav-tab-active' : '' ) . '">' . esc_html( $tab['label'] ) . '</a>';
							}
							?>
								</nav>
							<?php
				}

				echo '<hr class="wp-header-end">';

				foreach ( $tabs as $tab ) {
					if ( '' === $current_tab ) {
						settings_fields( 'pwddm' );
						do_settings_sections( 'pwddm' );
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
	 * Admin plugin bar.
	 *
	 * @since 1.1.0
	 * @return statement
	 */
	static function pwddm_admin_plugin_bar() {
		return '<div class="pwddm_admin_bar">' . esc_html( __( 'Developed by', 'pwddm' ) ) . ' <a href="https://powerfulwp.com/" target="_blank">PowerfulWP</a> | <a href="https://powerfulwp.com/delivery-drivers-manager/" target="_blank" >' . esc_html( __( 'Premium', 'pwddm' ) ) . '</a> | <a href="https://powerfulwp.com/docs/delivery-drivers-manager/" target="_blank" >' . esc_html( __( 'Documents', 'pwddm' ) ) . '</a></div>';
	}

	/**
	 * Users list columns
	 *
	 * @return statement
	 */
	public function pwddm_users_list_columns( $column ) {
		if ( isset( $_GET['role'] ) && pwddm_manager_role() === $_GET['role'] ) {
			$column['pwddm_manager_account'] = __( 'Drivers account', 'pwddm' );
		}
		if ( isset( $_GET['role'] ) && 'driver' === $_GET['role'] ) {
			$column['pwddm_driver_manager'] = __( 'Delivery manager', 'pwddm' );
		}
		return $column;
	}

	/**
	 * Users list columns raw
	 *
	 * @return statement
	 */
	public function pwddm_users_list_columns_raw( $val, $column_name, $user_id ) {
		$manager_account_icon = '';
		$manager_account      = get_user_meta( $user_id, 'pwddm_manager_account', true );
		$manager_id           = get_user_meta( $user_id, 'pwddm_manager', true );
		$user                 = get_userdata( $manager_id );
		$manager_name         = ( ! empty( $user ) ) ? $user->display_name : '';

		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->can_use_premium_code() ) {
				/**
				 * Driver status icons and counters.
				 */
				if ( '1' === $manager_account ) {
					$manager_account_icon = '<a href="#" class="pwddm_account_icon pwddm_icon pwddm_active" manager_id="' . esc_attr( $user_id ) . '" ><i class="pwddm-toggle-on"></i></a>';
				} else {
					$manager_account_icon = '<a href="#" class="pwddm_account_icon pwddm_icon" manager_id="' . esc_attr( $user_id ) . '" ><i class="pwddm-toggle-off"></i></a>';
				}
			}
		}
		switch ( $column_name ) {
			case 'pwddm_manager_account':
				return pwddm_admin_premium_feature( $manager_account_icon );
			case 'pwddm_driver_manager':
				return $manager_name;
			default:
		}

		return $val;
	}


	/**
	 * Save user fields
	 *
	 * @since 1.0.0
	 * @param int $user_id user id.
	 */
	public function pwddm_user_fields_save( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$nonce_key = 'pwddm_nonce_user';

		if ( ! isset( $_REQUEST[ $nonce_key ] ) ) {
			die( 'Failed security check' );
		}
 
		$retrieved_nonce = sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_key ] ) );
		if ( ! wp_verify_nonce( $retrieved_nonce, basename( __FILE__ ) ) ) {
			die( 'Failed security check' );
		}
		 

		$user_meta  = get_userdata( $user_id );
		$user_roles = $user_meta->roles;

		// Update manager settings.
		if ( in_array( pwddm_manager_role(), (array) $user_roles, true ) ) {
			$pwddm_manager_account = ( isset( $_POST['pwddm_manager_account'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_manager_account'] ) ) : '';
			update_user_meta( $user_id, 'pwddm_manager_account', $pwddm_manager_account );

			$pwddm_manager_drivers = ( isset( $_POST['pwddm_manager_drivers'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_manager_drivers'] ) ) : '';
			update_user_meta( $user_id, 'pwddm_manager_drivers', $pwddm_manager_drivers );
		}

		// Update driver manager.
		if ( in_array( 'driver', (array) $user_roles, true ) ) {
			$pwddm_manager = ( isset( $_POST['pwddm_manager'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_manager'] ) ) : '';
			update_user_meta( $user_id, 'pwddm_manager', $pwddm_manager );
		}

	}

	/**
	 * Get driver fields
	 *
	 * @since 1.0.0
	 * @param object $user user data object.
	 */
	public function pwddm_driver_fields( $user ) {
		wp_nonce_field( basename( __FILE__ ), 'pwddm_nonce_user' );
		?>
		<?php
		if ( in_array( 'driver', (array) $user->roles, true ) ) {
			$manager       = new pwddm_Manger();
			$pwddm_manager = get_user_meta( $user->ID, 'pwddm_manager', true );
			?>
			<table class="form-table">
				<tr>
					<th><label for="pwddm_manager"><?php echo esc_html( __( 'Driver Manager', 'pwddm' ) ); ?></label></th>
					<td>
						 <?php
							echo $manager->pwddm_manager_selectbox( $pwddm_manager, 'pwddm_manager' );
							?>
						<p class="pwddm_description"><?php echo esc_html( __( 'Select the driver manager.', 'pwddm' ) ); ?></p>
					</td>
				</tr>
			</table>
			<?php
		}
	}

	/**
	 * Get user fields
	 *
	 * @since 1.0.0
	 * @param object $user user data object.
	 */
	public function pwddm_user_fields( $user ) {
		wp_nonce_field( basename( __FILE__ ), 'pwddm_nonce_user' );
		?>
		<?php
		if ( in_array( pwddm_manager_role(), (array) $user->roles, true ) ) {
			$pwddm_manager_drivers = get_user_meta( $user->ID, 'pwddm_manager_drivers', true );
			?>
		<h3><?php echo esc_html( __( 'Delivery Drivers Manager', 'pwddm' ) ); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="pwddm_manager_account"><?php echo esc_html( __( 'Manager account status', 'pwddm' ) ); ?></label></th>
					<td>
						<select name="pwddm_manager_account" id="pwddm_manager_account">
							<option value="0"><?php echo esc_html( __( 'Not active', 'pwddm' ) ); ?></option>
							<?php $selected = get_user_meta( $user->ID, 'pwddm_manager_account', true ) === '1' ? 'selected' : ''; ?>
							<option <?php echo esc_attr( $selected ); ?> value="1"><?php echo esc_html( __( 'Active', 'pwddm' ) ); ?></option>
						</select>
						<p class="pwddm_description"><?php echo esc_html( __( 'Only managers with active accounts can access the delivery drivers manager.', 'pwddm' ) ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="pwddm_manager_drivers"><?php echo esc_html( __( 'Manager drivers', 'pwddm' ) ); ?></label></th>
					<td>
						<select name="pwddm_manager_drivers" id="pwddm_manager_drivers">
							<option value="0"><?php echo esc_html( __( 'Admin drivers only', 'pwddm' ) ); ?></option>
							<?php
							if ( pwddm_fs()->is__premium_only() ) {
								if ( pwddm_fs()->is_plan( 'premium', true ) ) {
									?>
										<option <?php echo selected( esc_attr( $pwddm_manager_drivers ), '1' ); ?> value="1"><?php echo esc_html( __( 'Manager & Admin drivers', 'pwddm' ) ); ?></option>
										<option <?php echo selected( esc_attr( $pwddm_manager_drivers ), '2' ); ?> value="2"><?php echo esc_html( __( 'Manager drivers only', 'pwddm' ) ); ?></option>
									<?php
								}
							}
							?>
						</select>
						<p class="pwddm_description"><?php echo esc_html( __( 'Allow delivery manager to work with drivers.', 'pwddm' ) ); ?></p>
					</td>
				</tr>
			</table>
			<?php
		}
	}

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function pwddm_manager_page() {
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
		<select name='pwddm_manager_page'>
			<?php
			if ( ! empty( $pages ) ) {
				foreach ( $pages as $page ) {
					$page_id    = $page->ID;
					$page_title = $page->post_title;
					?>
					<option value="<?php echo esc_attr( $page_id ); ?>" <?php selected( esc_attr( get_option( 'pwddm_manager_page', '' ) ), $page_id ); ?>><?php echo esc_html( $page_title ); ?></option>
					<?php
				}
			}
			?>
		</select>
		<p class="pwddm_description" id="pwddm-gooogle-api-key-description">
			<?php
			echo '<div class="driver_app">
				<p>
					<b><a target="_blank" href="' . pwddm_manager_page_url( '' ) . '">' . pwddm_manager_page_url( '' ) . '</a></b><br>' .
				sprintf( esc_html( __( 'The link above is the delivery driver\'s manager url.', 'pwddm' ) ), '<br>', '<br>' ) .
				'
				</p>
			</div>';
			?>
		</p>
		<?php
	}

	/**
	 * Admin notices function.
	 *
	 * @since 1.0.0
	 */
	public function pwddm_admin_notices() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<div class="notice notice-info is-dismissible">
          			<p>' . esc_html( __( 'Local delivery drivers for WooCommerce is a WooCommerce add-on, you must activate a WooCommerce on your site.', 'pwddm' ) ) . '</p>
		 		  </div>';
		}
	}

	/**
	 * Set order commission
	 *
	 * @since 1.0.0
	 */
	public function pwddm_set_order_commission( $commission, $order ) {
		 $driver_id = $order->get_meta( 'lddfw_driverid' );
		$seller_id  = get_user_meta( $driver_id, 'pwddm_manager', true );
		// if the driver works for the seller only we set the seller commision.
		if ( '' !== $seller_id ) {
			// set driver commission.
			$order_commission = $order->get_meta( 'lddfw_driver_commission' );
			if ( '' === $order_commission ) {
				$pwddm_driver_commission_type  = get_user_meta( $seller_id, 'pwddm_driver_commission_type', true );
				$pwddm_driver_commission_value = get_user_meta( $seller_id, 'pwddm_driver_commission_value', true );
				if ( '' !== $pwddm_driver_commission_type && '' !== $pwddm_driver_commission_value ) {

					if ( 'fixed' === $pwddm_driver_commission_type ) {
						$commission = $pwddm_driver_commission_value;
					}

					if ( 'delivery_percentage' === $pwddm_driver_commission_type ) {
						$commission = $order->get_shipping_total() * $pwddm_driver_commission_value / 100;
					}

					if ( 'order_percentage' === $pwddm_driver_commission_type ) {
						$order_total = $order->get_total();
						$refund      = $order->get_total_refunded();
						if ( '' !== $refund ) {
							$order_total = $order_total - $refund;
						}
						$commission = $order_total * $pwddm_driver_commission_value / 100;
					}
				}
			}
		}
		return $commission;
	}

	/**
	 * Settings tabs
	 *
	 * @since 1.0.0
	 */
	public function pwddm_settings_tabs() {

			 // Tabs array.
			 $tabs = array(
				 array(
					 'slug'  => '',
					 'label' => esc_html( __( 'General settings', 'pwddm' ) ),
					 'title' => esc_html( __( 'General settings', 'pwddm' ) ),
					 'url'   => '?page=pwddm-settings',
				 ),
				 array(
					 'slug'  => 'pwddm-branding',
					 'label' => esc_html( __( 'Branding', 'pwddm' ) ),
					 'title' => esc_html( __( 'Branding', 'pwddm' ) ),
					 'url'   => '?page=pwddm-settings&tab=pwddm-branding',
				 ),
			 );

			 return $tabs;

	}

	/**
	 * Getting driver seller
	 *
	 * @since 1.0.0
	 */
	public function pwddm_get_driver_seller( $driver_id ) {
		$seller_id = get_user_meta( $driver_id, 'pwddm_manager', true );
		if ( '' === $seller_id ) {
			$pwddm_user       = wp_get_current_user();
			$pwddm_manager_id = $pwddm_user->ID;
			$seller_drivers   = get_user_meta( $pwddm_manager_id, 'pwddm_manager_drivers', true );
			if ( '0' === $seller_drivers || '1' === $seller_drivers ) {
				$seller_id = $pwddm_manager_id;
			}
		}
		return strval( $seller_id );
	}

	/**
	 * Assign driver permission
	 *
	 * @param int $order_seller_id seller id.
	 * @param int $driver_seller_id seller id.
	 * @return statement
	 */
	public function pwddm_assign_driver_permission( $order_seller_id, $driver_seller_id ) {

		// Admin order and driver.
		if ( '' === $order_seller_id && '' === $driver_seller_id ) {
			return true;
		}

		 // Seller order.
		if ( '' !== $order_seller_id ) {
			// Seller drivers permission.
			$seller_drivers = get_user_meta( $order_seller_id, 'pwddm_manager_drivers', true );

			if ( '1' === $seller_drivers && ( '' === $driver_seller_id || $driver_seller_id === $order_seller_id ) ) {
				// Seller allow to assign his drivers and the admin drivers.
				return true;
			} elseif ( '2' === $seller_drivers && $driver_seller_id === $order_seller_id ) {
				// Seller allow to assign his drivers only.
				return true;
			} else {
				if ( '' === $driver_seller_id && '2' !== $seller_drivers ) {
					// Seller allow to assign admin drivers only.
					return true;
				}
			}
		}
		return false;
	}


	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function pwddm_branding_title() {
		echo pwddm_admin_premium_feature( '' );
		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->is_plan( 'premium', true ) ) {
				?>
		<input type='text' class='regular-text' placeholder='<?php echo esc_attr( __( 'WELCOME', 'pwddm' ) ); ?>' name='pwddm_branding_title' value='<?php echo esc_attr( get_option( 'pwddm_branding_title', '' ) ); ?>'>
				<?php
			}
		}
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function pwddm_branding_subtitle() {
		echo pwddm_admin_premium_feature( '' );
		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->is_plan( 'premium', true ) ) {
				?>
		<input type='text' class='regular-text' placeholder='<?php echo esc_attr( __( 'To the vendor delivery drivers manager', 'pwddm' ) ); ?>' name='pwddm_branding_subtitle' value='<?php echo esc_attr( get_option( 'pwddm_branding_subtitle', '' ) ); ?>'>
				<?php
			}
		}
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function pwddm_branding_logo() {
		echo pwddm_admin_premium_feature( '' );
		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->is_plan( 'premium', true ) ) {
				$image_id = get_option( 'pwddm_branding_logo', '' );
				$image    = '';
				if ( intval( $image_id ) > 0 ) {
					$image = wp_get_attachment_image_src( $image_id, 'medium' )[0];
					if ( '' !== $image ) {
						$image = '<img src="' . $image . '">';
					}
				}

				echo '<div id="pwddm_branding_logo_preview" class="pwddm_media_preview">' . $image . '</div>';
				?>
				<input type="hidden" name="pwddm_branding_logo" id="pwddm_branding_logo" value="<?php echo esc_attr( $image_id ); ?>" class="regular-text" />
				<input type='button' class="button-primary pwddm_media_manager" value="<?php esc_attr_e( 'Select an image', 'pwddm' ); ?>" data="pwddm_branding_logo"   id="pwddm_branding_media_manager"/>
				<input type='button' class="button-secondary pwddm_media_delete" data="pwddm_branding_logo" value="<?php esc_attr_e( 'Delete image', 'pwddm' ); ?>" id="pwddm_branding_media_delete"/>

				<?php
			}
		}
	}
	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function pwddm_branding_background() {
		echo pwddm_admin_premium_feature( '' );
		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->is_plan( 'premium', true ) ) {
				?>
		<input type='text' class='pwddm-color-picker' data-default-color="#fff" name='pwddm_branding_background' value='<?php echo esc_attr( get_option( 'pwddm_branding_background', '' ) ); ?>'>
				<?php
			}
		}
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function pwddm_branding_text_color() {
		echo pwddm_admin_premium_feature( '' );
		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->is_plan( 'premium', true ) ) {
				?>
		<input type='text' class='pwddm-color-picker' data-default-color="#011627" name='pwddm_branding_text_color' value='<?php echo esc_attr( get_option( 'pwddm_branding_text_color', '' ) ); ?>'>
				<?php
			}
		}
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function pwddm_branding_button_color() {
		echo pwddm_admin_premium_feature( '' );
		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->is_plan( 'premium', true ) ) {
				?>
		<input type='text' class='pwddm-color-picker' data-default-color="#fff" name='pwddm_branding_button_color' value='<?php echo esc_attr( get_option( 'pwddm_branding_button_color', '' ) ); ?>'>
				<?php
			}
		}
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function pwddm_branding_button_background() {
		echo pwddm_admin_premium_feature( '' );
		if ( pwddm_fs()->is__premium_only() ) {
			if ( pwddm_fs()->is_plan( 'premium', true ) ) {
				?>
		<input type='text' class='pwddm-color-picker' data-default-color="#0a58ca" name='pwddm_branding_button_background' value='<?php echo esc_attr( get_option( 'pwddm_branding_button_background', '' ) ); ?>'>
				<?php
			}
		}
	}

	/**
	 * Plugin settings input.
	 *
	 * @since 1.1.2
	 */
	public function pwddm_premium_features() {

		echo pwddm_admin_premium_feature( '' ) . esc_html( __( 'Enable Drivers Manager to work with his own drivers.', 'pwddm' ) ) . '<br><br>';
		echo pwddm_admin_premium_feature( '' ) . esc_html( __( 'Enable Drivers Manager to view the drivers routes.', 'pwddm' ) ) . '<br><br>';
		echo pwddm_admin_premium_feature( '' ) . esc_html( __( 'Enable Drivers Manager to bulk update orders.', 'pwddm' ) ) . '<br><br>';
		echo pwddm_admin_premium_feature( '' ) . esc_html( __( 'Enable full dashboard for Admin and Drivers Manager.', 'pwddm' ) ) . '<br><br>';
		echo pwddm_admin_premium_feature( '' ) . esc_html( __( 'Enable full reports for Drivers Manager.', 'pwddm' ) ) . '<br><br>';

	}



	/**
	 * Plugin submenu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function pwddm_admin_menu() {
		 // Add menu to main menu.
		 add_menu_page( esc_html( __( 'Drivers Manager Settings', 'pwddm' ) ), esc_html( __( 'Drivers Manager', 'pwddm' ) ), 'edit_pages', 'pwddm-dashboard', array( &$this, 'pwddm_dashboard' ), 'dashicons-location', 56 );
		 add_submenu_page( 'pwddm-dashboard', esc_html( __( 'Dashboard', 'pwddm' ) ), esc_html( __( 'Dashboard', 'pwddm' ) ), 'edit_pages', 'pwddm-dashboard', array( &$this, 'pwddm_dashboard' ) );
		 add_submenu_page( 'pwddm-dashboard', esc_html( __( 'Settings', 'pwddm' ) ), esc_html( __( 'Settings', 'pwddm' ) ), 'edit_pages', 'pwddm-settings', array( &$this, 'pwddm_settings' ) );
	}

	/**
	 * Plugin dashboard.
	 *
	 * @since 1.0.0
	 */
	public function pwddm_dashboard() {
		$dashboard = new pwddm_Reports();
		echo $dashboard->admin_screen_dashboard();
	}


}
