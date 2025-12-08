<?php
class WPSunshine_Address_Autocomplete {

	protected static $_instance = null;
	private $prefix             = 'wps_aa_';
	private $options;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {

		$this->includes();
		$this->init_hooks();

	}

	private function includes() {

		if ( is_admin() ) {
			include_once WPS_AA_PATH . 'includes/admin/class-options.php';
			include_once WPS_AA_PATH . 'includes/admin/promos.php';
		}

	}

	private function init_hooks() {

		add_action( 'wp_head', array( $this, 'frontend_js' ), 9999 );

	}

	public function get_instances() {
		$instances = get_option( 'wps_aa_instances' );
		if ( empty( $instances ) && is_admin() ) { // Set a default instance in the admin
			$key       = $this->generate_key();
			$instances = array(
				$key => array(
					'label'             => '',
					'page'              => '',
					'init'              => '',
					'allowed_countries' => array(),
					'fields'            => array(),
				),
			);
		}
		return apply_filters( 'wps_aa_instances', $instances );
	}

	public function get_options( $force = false ) {
		if ( empty( $this->options ) || $force ) {
			$this->options = get_option( $this->prefix . 'options' );
		}
		return apply_filters( $this->prefix . 'options', $this->options );
	}

	public function get_option( $key ) {
		if ( ! empty( $this->options[ $key ] ) ) {
			return $this->options[ $key ];
		}
		return false;
	}

	private function available_instances( $page_id = '' ) {

		if ( empty( $page_id ) ) {
			$page_id = get_queried_object_id();
		}

		$instances = $this->get_instances();
		if ( empty( $instances ) ) {
			return false;
		}

		// Check for available instances to show on the current page
		$available_instances = array();
		foreach ( $instances as $instance ) {
			if ( ! empty( $instance['page'] ) ) {
				if ( is_array( $instance['page'] ) ) {
					if ( ! in_array( $page_id, $instance['page'] ) ) {
						continue;
					}
				} elseif ( $page_id != $instance['page'] ) {
					continue;
				}
			}
			$available_instances[] = $instance;
		}

		return $available_instances;

	}

	public function get_available_place_components() {
		$place_components = array(
			'5'  => array(
				'key'   => 'country',
				'label' => 'Country',
			),
			'10' => array(
				'key'   => 'address1',
				'label' => 'Address 1',
			),
			'20' => array(
				'key'   => 'address2',
				'label' => 'Address 2',
			),
			'30' => array(
				'key'   => 'locality',
				'label' => 'Locality (City)',
			),
			'40' => array(
				'key'   => 'administrative_area_level_1',
				'label' => 'Administrative Area Level 1 (State/Region/Province)',
			),
			'50' => array(
				'key'   => 'postal_code',
				'label' => 'Postal Code',
			),
		);
		$place_components = apply_filters( 'wps_aa_place_components', $place_components );
		ksort( $place_components );
		return $place_components;
	}

	public function frontend_js() {

		if ( is_admin() ) {
			return;
		}

		$available_instances = $this->available_instances();
		$google_api_key      = get_option( 'wps_aa_google_api_key' );
		$language            = get_option( 'wps_aa_language' );
		if ( ! empty( $available_instances ) ) {

			$instances_data = array();
			foreach ( $available_instances as $instance ) {

				if ( empty( $instance['init'] ) ) {
					// If there is no init, no bother going any further - this is a bare minimum
					continue;
				}

				$fields = array();
				foreach ( $instance['fields'] as $field ) {
					if ( ! empty( $field['selector'] ) && ! empty( $field['data'] ) ) {
						$fields[] = array(
							'selector' => $field['selector'],
							'data'     => $field['data'],
						);
					}
				}
				$instances_data[] = array(
					'init'              => $instance['init'],
					'allowed_countries' => ( ! empty( $instance['allowed_countries'] ) ) ? $instance['allowed_countries'] : '',
					'fields'            => $fields,
					'delay'             => ( ! empty( $instance['delay'] ) ) ? $instance['delay'] : '',
				);

			}

			if ( ! empty( $instances_data ) && apply_filters( 'wps_aa_load_scripts', true ) ) {

				// Build Google Maps API URL
				$google_maps_url = 'https://maps.googleapis.com/maps/api/js?key=' . sanitize_text_field( $google_api_key );

				// Add language parameter if set
				if ( ! empty( $language ) ) {
					$google_maps_url .= '&language=' . sanitize_text_field( $language );
				}

				// Add the rest of the URL parameters
				$google_maps_url .= '&callback=wps_aa&libraries=places&v=beta&loading=async';
				// $google_maps_url .= '&libraries=places';

				wp_enqueue_script(
					'wps-aa-google-maps',
					$google_maps_url,
					'',
					'',
					array(
						'strategy'  => 'async',
						'in_footer' => true,
					)
				);

				wp_enqueue_script( 'wps-aa-frontend', WPS_AA_URL . 'assets/js/frontend.js', array( 'jquery', 'wps-aa-google-maps' ), ( WPS_AA_PREMIUM_VERSION ) ? WPS_AA_PREMIUM_VERSION : WPS_AA_VERSION, true );
				$results_title = get_option( 'wps_aa_results_title' );
				$args          = array(
					'instances'     => $instances_data,
					'results_title' => $results_title,
				);
				wp_localize_script( 'wps-aa-frontend', 'wps_aa_vars', $args );
			}
		}

	}

	public function generate_key() {
		return md5( uniqid( time() ) );
	}

}
