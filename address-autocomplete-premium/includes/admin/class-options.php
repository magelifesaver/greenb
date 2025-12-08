<?php
class WPSunshine_Address_Autocomplete_Options_Premium extends WPSunshine_Address_Autocomplete_Options {

	protected function includes() {

		if ( is_admin() ) {
			include_once WPS_AA_PREMIUM_PATH . '/includes/admin/class-updater.php';
		}

	}

	protected function init_hooks() {

		if ( wps_aa_is_premium() ) {
			remove_action( 'wps_aa_options_before', 'wps_aa_options_after_upgrade', 10 );
			add_action( 'wps_aa_instances_nav', array( $this, 'instances_nav' ) );
			add_action( 'admin_init', array( $this, 'add_instance' ) );
			add_action( 'admin_init', array( $this, 'delete_instance' ) );
			add_action( 'admin_init', array( $this, 'save_options' ) );
			// TODO: Weekly license check
		}

		// Header links
		add_action( 'wps_aa_header_links', array( $this, 'header_links' ) );

		add_filter( 'wps_aa_tabs', array( $this, 'tabs' ) );
		add_action( 'wps_aa_options_tab_license', array( $this, 'license_tab' ) );
		add_action( 'wps_aa_options_tab_addons', array( $this, 'addons_tab' ) );

		add_action( 'admin_init', array( $this, 'updater' ) );
		add_action( 'admin_init', array( $this, 'license_activate' ) );
		add_action( 'admin_init', array( $this, 'license_deactivate' ) );

	}

	public function header_links( $links ) {
		unset( $links['upgrade'] );
		$links['ticket'] = array(
			'url'   => 'https://wpsunshine.com/support/ticket',
			'label' => 'Support TIcket',
		);
		return $links;
	}


	public function updater() {
		$license_key = trim( get_option( 'wps_aa_license' ) );
		// setup the updater
		$updater = new WPS_Address_Autocomplete_SL_Plugin_Updater(
			WPS_AA_PREMIUM_STORE_URL,
			WPS_AA_PREMIUM_PLUGIN_FILE,
			array(
				'version' => WPS_AA_PREMIUM_VERSION,        // current version number
				'license' => $license_key,  // license key (used get_option above to retrieve from DB)
				'item_id' => WPS_AA_PREMIUM_PRODUCT_ID, // id of this plugin
				'author'  => 'WP Sunshine',  // author of this plugin
				'beta'    => false,                // set to true if you wish customers to receive update notifications of beta releases
			)
		);
	}

	public function tabs( $tabs ) {
		$tabs['addons']  = __( 'Addons', 'address-autocomplete-anything' );
		$tabs['license'] = __( 'License', 'address-autocomplete-anything' );
		return $tabs;
	}

	public function license_tab() {
		$license      = get_option( 'wps_aa_license' );
		$license_data = get_option( 'wps_aa_license_data' );

		$button      = '<button type="submit" class="button-secondary">' . __( 'Activate License', 'address-autocomplete-anything' ) . '</button>';
		$description = '';

		if ( ! empty( $license_data ) ) {

			if ( false === $license_data->success ) {

				switch ( $license_data->error ) {

					case 'expired':
						$message = sprintf(
							__( 'Your license key expired on %s' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;

					case 'disabled':
					case 'revoked':
						$message = __( 'Your license key has been disabled' );
						break;

					case 'missing':
						$message = __( 'Invalid license' );
						break;

					case 'invalid':
					case 'site_inactive':
						$message = __( 'Your license is not active for this URL.' );
						break;

					case 'item_name_mismatch':
						$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), WPS_CONFETTI_PREMIUM_PRODUCT_NAME );
						break;

					case 'no_activations_left':
						$message = __( 'Your license key has reached its activation limit.' );
						break;

					default:
						$message = __( 'An error occurred, please try again.' );
						break;
				}

				$description = '<p class="description" style="font-weight: bold; color: red;">' . $message . '</p>';

			} else {

				if ( $license_data->expires == 'lifetime' ) {
					$description = '<p class="description"><span style="font-weight: bold; color: green;">' . __( 'Congrats! You have a special lifetime license that never expires', 'address-autocomplete-anything' ) . '</p>';
				} else {
					$expiration  = date( get_option( 'date_format' ), strtotime( $license_data->expires ) );
					$description = '<p class="description"><span style="font-weight: bold; color: green;">' . __( 'Your license is active!', 'address-autocomplete-anything' ) . '</span> ' . __( 'Expires', 'address-autocomplete-anything' ) . ': ' . $expiration . '</p>';
				}
				$url    = admin_url( 'options-general.php?page=wps_aa&tab=license' );
				$url    = wp_nonce_url( $url, 'wps_aa_deactivate_license', 'wps_aa_deactivate_license' );
				$button = '<a href="' . esc_url( $url ) . '" class="button-secondary">' . __( 'Deactivate license', 'address-autocomplete-anything' ) . '</a>';

			}
		}

		?>
		<table class="form-table">
			<tr>
				<th><?php _e( 'License Key', 'address-autocomplete-anything' ); ?></th>
				<td>
					<p><input type="text" name="wps_aa_license" value="<?php echo esc_attr( $license ); ?>" size="40" /> <?php echo wp_kses_post( $button ); ?></p>
					<?php echo $description; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	public function addons_tab() {

		if ( ! wps_aa_is_premium() ) {
			?>
				<p><a href="<?php echo admin_url( 'options-general.php?page=wps_aa&tab=license' ); ?>">Enter your license key</a> to activate add-on integrations</p>
				<style>#wps-options-submit { display: none; }</style>
			<?php
			return;
		}

		$available_addons = apply_filters( 'wps_aa_addons', array() );
		$addons           = get_option( 'wps_aa_addons' );
		if ( empty( $addons ) ) {
			$addons = array();
		}
		?>
		<table class="form-table">
			<tr>
				<th><?php _e( 'Integrations', 'address-autocomplete-anything' ); ?></th>
				<td>
					<?php foreach ( $available_addons as $key => $label ) { ?>
						<label><input type="checkbox" name="wps_aa_addons[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( true, in_array( $key, $addons ) ); ?> /> <?php echo esc_html( $label ); ?></label><br />
					<?php } ?>
					<span class="wps-description"><?php _e( 'Check the areas you want to automatically enable address autocomplete', 'address-autocomplete-anything' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Additional Integrations', 'address-autocomplete-anything' ); ?></th>
				<td>
					<p>
						<strong>&check; Gravity Forms</strong> <a href="https://wpsunshine.com/documentation/how-to-enable-address-autocomplete-on-gravity-forms/?utm_source=plugin&utm_medium=link&utm_campaign=doc" target="_blank">Learn more</a>
					</p>
				</td>
			</tr>
		</table>
		<?php

	}

	public function license_activate() {

		// listen for our activate button to be clicked
		if ( isset( $_POST['wps_aa_options'] ) && isset( $_POST['wps_aa_license'] ) ) {

			// run a quick security check
			if ( ! wp_verify_nonce( $_POST['wps_aa_options'], 'wps_aa_options' ) ) {
				return;
			}

			$orig_license = get_option( 'wps_aa_license' );

			$license = sanitize_text_field( $_POST['wps_aa_license'] );
			update_option( 'wps_aa_license', $license, 'no' );

			$license_data = get_option( 'wps_aa_license_data' );
			if ( empty( $license_data ) || $license_data->license == 'valid' || $orig_license != $license ) {

				// data to send in our API request
				$api_params = array(
					'edd_action' => 'activate_license',
					'license'    => $license,
					'item_name'  => urlencode( WPS_AA_PREMIUM_PRODUCT_NAME ), // the name of our product in EDD
					'url'        => home_url(),
				);

				// Call the custom API.
				$response = wp_remote_post(
					WPS_AA_PREMIUM_STORE_URL,
					array(
						'timeout'   => 15,
						'sslverify' => false,
						'body'      => $api_params,
					)
				);

				// make sure the response came back okay
				if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

					if ( is_wp_error( $response ) ) {
						$message = $response->get_error_message();
					} else {
						$message = __( 'An error occurred, please try again.' );
					}

					$this->add_notice( $message, 'error' );

					delete_option( 'wps_aa_license_data' );

				} else {
					$license_data = json_decode( wp_remote_retrieve_body( $response ) );
					$result       = update_option( 'wps_aa_license_data', $license_data, 'no' );
				}
			}
		}
	}

	public function license_deactivate() {

		// run a quick security check
		if ( ! isset( $_GET['wps_aa_deactivate_license'] ) || ! wp_verify_nonce( $_GET['wps_aa_deactivate_license'], 'wps_aa_deactivate_license' ) ) {
			return;
		}

		$license = get_option( 'wps_aa_license' );

		$api_params = array(
			'edd_action'  => 'deactivate_license',
			'license'     => $license,
			'item_name'   => urlencode( WPS_AA_PREMIUM_PRODUCT_NAME ), // the name of our product in EDD
			'url'         => home_url(),
			'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		);

		// Call the custom API.
		$response = wp_remote_post(
			WPS_AA_PREMIUM_STORE_URL,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			)
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.' );
			}

			$this->add_notice( __( 'License could not be deactivated', 'address-autocomplete-anything' ), 'error' );
			return;

		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if ( $license_data->license == 'deactivated' ) {
			delete_option( 'wps_aa_license_data' );
			$this->add_notice( __( 'License deactivated', 'address-autocomplete-anything' ), 'success' );
		}

	}


	public function instances_nav( $instances ) {
		?>
		<nav id="wps-aa-instances-nav">
			<ul>
			<?php
			$i = 0;
			foreach ( $instances as $key => $instance ) {
				$i++;
				$active = ( ( isset( $_GET['instance'] ) && $_GET['instance'] == $key ) || ( empty( $_GET['instance'] ) && $i == 1 ) ) ? true : false;
				echo '<li' . ( ( $active ) ? ' class="active"' : '' ) . '>';
				echo '<a href="' . admin_url( 'options-general.php?page=wps_aa&tab=settings&instance=' . $key ) . '" class="wps-aa-tab">';
				if ( ! empty( $instance['label'] ) ) {
					echo $instance['label'];
				} else {
					echo sprintf( __( 'Instance #%s', 'address-autocomplete-anything' ), $i );
				}
				echo '</a>';
				$delete_url = admin_url( 'options-general.php?tab=settings&instance=' . $key );
				$delete_url = wp_nonce_url( $delete_url, 'wps_aa_delete_instance', 'wps_aa_delete_instance' );
				echo '<a href="' . $delete_url . '" class="wps-aa-delete">&times;</a>';
				echo '</li>';
			}
			$add_url = admin_url( 'options-general.php' );
			$add_url = wp_nonce_url( $add_url, 'wps_aa_add_instance', 'wps_aa_add_instance' );
			echo '<li><a href="' . $add_url . '" id="wps-aa-add-instance" class="wps-aa-tab">' . __( '+ Add', 'address-autocomplete-anything' ) . '</a></li>';
			?>
			</ul>
		</nav>
		<?php
	}

	public function add_instance() {
		if ( ! isset( $_GET['wps_aa_add_instance'] ) || ! wp_verify_nonce( $_GET['wps_aa_add_instance'], 'wps_aa_add_instance' ) ) {
			return;
		}

		// Add a default instance to the existing
		$instances         = WPS_AA()->get_instances();
		$key               = WPS_AA()->generate_key();
		$instances[ $key ] = array(
			'label'             => '',
			'page'              => '',
			'init'              => '',
			'allowed_countries' => '',
			'fields'            => array(),
		);
		update_option( 'wps_aa_instances', $instances );
		wp_redirect( admin_url( 'options-general.php?page=wps_aa&tab=settings&instance=' . $key ) );
		exit;

	}

	public function delete_instance() {
		if ( ! isset( $_GET['wps_aa_delete_instance'] ) || ! wp_verify_nonce( $_GET['wps_aa_delete_instance'], 'wps_aa_delete_instance' ) ) {
			return;
		}

		if ( ! isset( $_GET['instance'] ) ) {
			return false;
		}

		// Add a default instance to the existing
		$instances = WPS_AA()->get_instances();
		foreach ( $instances as $key => $instance ) {
			if ( $key == $_GET['instance'] ) {
				unset( $instances[ $key ] );
				update_option( 'wps_aa_instances', $instances );
				wp_redirect( admin_url( 'options-general.php?page=wps_aa&tab=settings' ) );
				exit;
			}
		}

	}

	public function save_options() {

		if ( ! isset( $_GET['tab'] ) || $_GET['tab'] != 'addons' || ! isset( $_POST['wps_aa_options'] ) || ! wp_verify_nonce( $_POST['wps_aa_options'], 'wps_aa_options' ) ) {
			return;
		}

		$addons = array();
		if ( isset( $_POST['wps_aa_addons'] ) ) {
			foreach ( $_POST['wps_aa_addons'] as $addon ) {
				$addons[] = sanitize_key( $addon );
			}
		}
		update_option( 'wps_aa_addons', $addons, 'no' );

	}

}

$wps_aa_options_premium = new WPSunshine_Address_Autocomplete_Options_Premium();
