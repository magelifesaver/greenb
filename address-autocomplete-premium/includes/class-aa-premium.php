<?php
/**
 * Main premium plugin class
 *
 * @package WPSunshine_Address_Autocomplete
 */
class WPSunshine_Address_Autocomplete_Premium {

	/**
	 * Single instance of the class
	 *
	 * @var WPSunshine_Address_Autocomplete_Premium|null
	 */
	protected static $_instance = null;

	/**
	 * Main instance
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @return WPSunshine_Address_Autocomplete_Premium
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes() {

		include_once WPS_AA_PREMIUM_PATH . '/includes/addons/woocommerce.php';
		include_once WPS_AA_PREMIUM_PATH . '/includes/addons/gravity-forms.php';
		include_once WPS_AA_PREMIUM_PATH . '/includes/addons/lifterlms.php';
		include_once WPS_AA_PREMIUM_PATH . '/includes/addons/paidmembershipspro.php';
		include_once WPS_AA_PREMIUM_PATH . '/includes/addons/edd.php';

		if ( is_admin() ) {
			include_once WPS_AA_PREMIUM_PATH . '/includes/admin/class-options.php';
		}

	}

	private function init_hooks() {

		add_action( 'admin_init', array( $this, 'deactivate_free_version' ) );

		if ( wps_aa_is_premium() ) {
			add_filter( 'wps_aa_place_components', array( $this, 'place_components' ) );
		}

	}

	public function deactivate_free_version() {
		if ( is_admin() && is_plugin_active( 'address-autocomplete-anything/address-autocomplete.php' ) ) {
			add_action( 'admin_notices', array( $this, 'deactivate_free_version_notice' ) );
		}
	}

	public function deactivate_free_version_notice() {
		?>
	   <div class="notice notice-warning">
		   <p><?php echo sprintf( __( '<a href="%s">Click here to deactivate the free version of the Address Autocomplete Anything plugin</a>. You can then safely delete the free version as well without losing any settings or data.', 'address-autocomplete-anything' ), wp_nonce_url( 'plugins.php?action=deactivate&plugin=address-autocomplete-anything%2Faddress-autocomplete.php&plugin_status=all&paged=1&s', 'deactivate-plugin_address-autocomplete-anything/address-autocomplete.php' ) ); ?></p>
	   </div>
		<?php
	}

	public function place_components( $fields ) {
		$fields[6]   = array(
			'key'      => 'street_number',
			'label'    => 'Street Number',
			'selector' => '',
			'format'   => '',
		);
		$fields[7]   = array(
			'key'      => 'route',
			'label'    => 'Route (Street Name)',
			'selector' => '',
			'format'   => '',
		);
		$fields[27]  = array(
			'key'      => 'name',
			'label'    => 'Name',
			'selector' => '',
			'format'   => '',
		);
		$fields[31]  = array(
			'key'      => 'sublocality',
			'label'    => 'Sub Locality',
			'selector' => '',
			'format'   => '',
		);
		$fields[32]  = array(
			'key'      => 'sublocality_level_1',
			'label'    => 'Sub Locality Level 1',
			'selector' => '',
			'format'   => '',
		);
		$fields[33]  = array(
			'key'      => 'sublocality_level_2',
			'label'    => 'Sub Locality Level 2',
			'selector' => '',
			'format'   => '',
		);
		$fields[34]  = array(
			'key'      => 'sublocality_level_3',
			'label'    => 'Sub Locality Level 3',
			'selector' => '',
			'format'   => '',
		);
		$fields[35]  = array(
			'key'      => 'sublocality_level_4',
			'label'    => 'Sub Locality Level 4',
			'selector' => '',
			'format'   => '',
		);
		$fields[36]  = array(
			'key'      => 'sublocality_level_5',
			'label'    => 'Sub Locality Level 5',
			'selector' => '',
			'format'   => '',
		);
		$fields[36]  = array(
			'key'      => 'subpremise',
			'label'    => 'Subpremise (Apartment, unit, etc)',
			'selector' => '',
			'format'   => '',
		);
		$fields[41]  = array(
			'key'      => 'administrative_area_level_2',
			'label'    => 'Administrative Area Level 2',
			'selector' => '',
			'format'   => '',
		);
		$fields[42]  = array(
			'key'      => 'administrative_area_level_3',
			'label'    => 'Administrative Area Level 3',
			'selector' => '',
			'format'   => '',
		);
		$fields[43]  = array(
			'key'      => 'administrative_area_level_4',
			'label'    => 'Administrative Area Level 4',
			'selector' => '',
			'format'   => '',
		);
		$fields[44]  = array(
			'key'      => 'administrative_area_level_5',
			'label'    => 'Administrative Area Level 5',
			'selector' => '',
			'format'   => '',
		);
		$fields[45]  = array(
			'key'      => 'neighborhood',
			'label'    => 'Neighborhood',
			'selector' => '',
			'format'   => '',
		);
		$fields[200] = array(
			'key'        => 'lat',
			'label'      => 'Latitude',
			'selector'   => '',
			'short_long' => false,
		);
		$fields[201] = array(
			'key'        => 'lng',
			'label'      => 'Longitude',
			'selector'   => '',
			'short_long' => false,
		);
		return $fields;
	}

}
?>
