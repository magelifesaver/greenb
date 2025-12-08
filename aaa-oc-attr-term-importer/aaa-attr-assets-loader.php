<?php
/**
 * File: /wp-content/plugins/aaa-attr-term-importer/aaa-attr-assets-loader.php
 * Purpose: Enqueue admin assets for the Attribute Term Importer screen
 * Version: 0.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_Attr_Assets_Loader {
	public static function init() {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
	}

	public static function enqueue( $hook ) {
		// Only load on: Tools → Attribute Term Importer
		if ( 'tools_page_aaa-attr-term-importer' !== $hook ) {
			return;
		}

		$rel_js  = 'assets/js/aaa-attr-term-importer.js';
		$js_file = trailingslashit( AAA_ATTR_IMP_DIR ) . $rel_js;

		$ver = defined( 'AAA_ATTR_IMP_VERSION' ) ? AAA_ATTR_IMP_VERSION : '0.0.0';
		if ( file_exists( $js_file ) ) {
			$ver .= '.' . filemtime( $js_file );
		}

		wp_enqueue_script(
			'aaa-attr-term-importer',
			plugins_url( $rel_js, __FILE__ ),
			[ 'jquery' ],
			$ver,
			true
		);
	}
}
AAA_Attr_Assets_Loader::init();
