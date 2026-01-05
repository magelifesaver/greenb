<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AAA_API_Lokey_Settings {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_aaa_api_lokey_regenerate', array( __CLASS__, 'handle_regenerate' ) );
	}

	public static function admin_menu() {
		add_options_page(
			'AAA API Lokey',
			'AAA API Lokey',
			'manage_woocommerce',
			'aaa-api-lokey',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_settings() {
		register_setting( 'aaa_api_lokey', AAA_API_LOKEY_OPTION, array( __CLASS__, 'sanitize' ) );
	}

	public static function sanitize( $input ) {
		$out = get_option( AAA_API_LOKEY_OPTION, array() );
		$out = is_array( $out ) ? $out : array();
		$in  = is_array( $input ) ? $input : array();

		$out['brand_taxonomy'] = isset( $in['brand_taxonomy'] ) ? sanitize_key( $in['brand_taxonomy'] ) : '';

		if ( isset( $in['namespaces'] ) ) {
			$raw = array_filter( array_map( 'trim', explode( "\n", (string) $in['namespaces'] ) ) );
			$out['namespaces'] = array_values( array_unique( array_map( 'sanitize_text_field', $raw ) ) );
		}

		if ( empty( $out['api_key'] ) ) {
			$out['api_key'] = wp_generate_password( 40, false, false );
		}

		return $out;
	}

	public static function handle_regenerate() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'aaa_api_lokey_regenerate' );
		$settings           = get_option( AAA_API_LOKEY_OPTION, array() );
		$settings           = is_array( $settings ) ? $settings : array();
		$settings['api_key'] = wp_generate_password( 40, false, false );
		update_option( AAA_API_LOKEY_OPTION, $settings );
		wp_safe_redirect( admin_url( 'options-general.php?page=aaa-api-lokey&regenerated=1' ) );
		exit;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$settings   = get_option( AAA_API_LOKEY_OPTION, array() );
		$settings   = is_array( $settings ) ? $settings : array();
		$api_key    = isset( $settings['api_key'] ) ? (string) $settings['api_key'] : '';
		$brand_tax  = isset( $settings['brand_taxonomy'] ) ? (string) $settings['brand_taxonomy'] : '';
		$namespaces = isset( $settings['namespaces'] ) && is_array( $settings['namespaces'] ) ? implode( "\n", $settings['namespaces'] ) : "aaa/v1\naaa-api-lokey/v1";

		echo '<div class="wrap">';
		echo '<h1>AAA API Lokey</h1>';

		if ( isset( $_GET['regenerated'] ) ) {
			echo '<div class="notice notice-success"><p>API key regenerated.</p></div>';
		}

		echo '<h2 class="title">API Key</h2>';
		echo '<p>Send this key as <code>Authorization: Bearer ...</code> or <code>X-AAA-API-Key</code>.</p>';
		echo '<input type="text" class="regular-text" readonly value="' . esc_attr( $api_key ) . '" />';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:10px;">';
		echo '<input type="hidden" name="action" value="aaa_api_lokey_regenerate" />';
		wp_nonce_field( 'aaa_api_lokey_regenerate' );
		submit_button( 'Regenerate API Key', 'secondary', 'submit', false );
		echo '</form>';

		echo '<hr />';
		echo '<form method="post" action="options.php">';
		settings_fields( 'aaa_api_lokey' );

		echo '<table class="form-table" role="presentation">';
		echo '<tr><th scope="row"><label for="brand_taxonomy">Brand taxonomy (optional)</label></th><td>';
		echo '<input name="' . esc_attr( AAA_API_LOKEY_OPTION ) . '[brand_taxonomy]" id="brand_taxonomy" type="text" class="regular-text" value="' . esc_attr( $brand_tax ) . '" />';
		echo '<p class="description">Examples: <code>product_brand</code>, <code>pwb-brand</code>. Leave blank to disable brand filter.</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row"><label for="namespaces">REST namespaces</label></th><td>';
		echo '<textarea name="' . esc_attr( AAA_API_LOKEY_OPTION ) . '[namespaces]" id="namespaces" rows="3" class="large-text code">' . esc_textarea( $namespaces ) . '</textarea>';
		echo '<p class="description">One per line. Useful if you already configured an OpenAI Action to a specific namespace.</p>';
		echo '</td></tr>';
		echo '</table>';

		submit_button();
		echo '</form>';
		echo '</div>';
	}
}
