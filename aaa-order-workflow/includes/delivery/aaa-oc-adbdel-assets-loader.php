<?php
/**
 * Delivery Assets Loader — aaa-oc-adbdel-assets-loader.php
 *
 * Registers/enqueues admin + frontend assets for Delivery module.
 * Uses filemtime for cache-busting in dev.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'AAA_OC_ADBDEL_VERSION' ) ) {
	define( 'AAA_OC_ADBDEL_VERSION', '0.1.0-dev' );
}

if ( ! class_exists( 'AAA_OC_AdbDel_Assets' ) ) :

final class AAA_OC_AdbDel_Assets {

	/** @var string */
	private $base_dir;

	/** @var string */
	private $base_url;

	public function __construct( $base_dir = null, $base_url = null ) {
		$this->base_dir = $base_dir ? rtrim( $base_dir, '/\\' ) . '/' : plugin_dir_path( __FILE__ );
		$this->base_url = $base_url ? rtrim( $base_url, '/\\' ) . '/' : plugin_dir_url( __FILE__ );
	}

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
	}

	public function enqueue_admin( $hook ) {
		// Load only on our tools page or Woo screens.
		$load = ( false !== strpos( $hook, 'woocommerce' ) ) || ( isset( $_GET['page'] ) && 'aaa-oc-adbdel-tools' === $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $load ) {
			return;
		}

		$this->register_script_style(
			'aaa-oc-adbdel-admin',
			'assets/delivery/admin.js',
			'assets/delivery/admin.css'
		);

		wp_enqueue_script( 'aaa-oc-adbdel-admin' );
		wp_enqueue_style( 'aaa-oc-adbdel-admin' );

		wp_localize_script( 'aaa-oc-adbdel-admin', 'AAA_OC_ADBDEL', array(
			'version'  => AAA_OC_ADBDEL_VERSION,
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'aaa_oc_adbdel' ),
		) );
	}

	public function enqueue_frontend() {
		$this->register_script_style(
			'aaa-oc-adbdel-frontend',
			'assets/delivery/frontend.js',
			'assets/delivery/frontend.css'
		);

		// Enqueue only if Delivery checkout fields are present.
		if ( is_checkout() ) {
			wp_enqueue_script( 'aaa-oc-adbdel-frontend' );
			wp_enqueue_style( 'aaa-oc-adbdel-frontend' );
		}
	}

	private function register_script_style( $handle, $rel_js, $rel_css ) {
		$js  = $this->base_url . ltrim( $rel_js, '/\\' );
		$css = $this->base_url . ltrim( $rel_css, '/\\' );

		$js_path  = $this->base_dir . ltrim( $rel_js, '/\\' );
		$css_path = $this->base_dir . ltrim( $rel_css, '/\\' );

		$ver_js  = file_exists( $js_path )  ? filemtime( $js_path )  : AAA_OC_ADBDEL_VERSION;
		$ver_css = file_exists( $css_path ) ? filemtime( $css_path ) : AAA_OC_ADBDEL_VERSION;

		wp_register_script( $handle, $js, array( 'jquery' ), $ver_js, true );
		wp_register_style(  $handle, $css, array(), $ver_css );
	}
}

// Auto-boot when included.
add_action( 'plugins_loaded', static function () {
	$assets = new \AAA_OC_AdbDel_Assets();
	$assets->init();
} );

endif; // class_exists
