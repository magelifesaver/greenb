<?php

if ( ! defined( 'WPS_AA_VERSION' ) ) {

	define( 'WPS_AA_VERSION', '1.2.2' );
	define( 'WPS_AA_PLUGIN_FILE', __FILE__ );
	define( 'WPS_AA_PATH', plugin_dir_path( __FILE__ ) );
	define( 'WPS_AA_URL', plugin_dir_url( __FILE__ ) );

	include_once WPS_AA_PATH . '/includes/class-aa.php';

	function WPS_AA() {
		return WPSunshine_Address_Autocomplete::instance();
	}

	// Get the confetti launcher primed and loaded!
	add_action( 'plugins_loaded', 'wps_aa_load_me' );
	function wps_aa_load_me() {
		$GLOBALS['wps_aa'] = WPS_AA();
	}
}
