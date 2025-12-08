<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/admin/tabs/aaa-paim-global.php
 * Purpose: Global settings (OpenAI API key, enable, debug) + Verify API button.
 * Version: 0.4.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_PAIM_DEBUG_TABGLOBAL' ) ) { define( 'AAA_PAIM_DEBUG_TABGLOBAL', true ); }

class AAA_Paim_Tab_Global {

	public static function render() {
		if ( isset( $_POST['aaa_paim_save_global'] ) ) {
			AAA_Paim_Options::save_from_post();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'aaa-paim' ) . '</p></div>';
		}

		$enabled = (bool) AAA_Paim_Options::get( 'enabled', 0 );
		$debug   = (bool) AAA_Paim_Options::get( 'debug', 1 );
		$mask    = AAA_Paim_Options::get( 'openai_api_key', '' ) ? '********' : '';

		echo '<h2>' . esc_html__( 'Global Settings', 'aaa-paim' ) . '</h2>';
		echo '<form method="post" action="">';
		wp_nonce_field( 'aaa_paim_save_global', 'aaa_paim_nonce' );

		echo '<table class="form-table"><tbody>';

		echo '<tr><th>' . esc_html__( 'Enable PAIM', 'aaa-paim' ) . '</th><td>';
		echo '<label><input type="checkbox" name="paim_enabled" value="1" ' . checked( $enabled, true, false ) . '> ' . esc_html__( 'Enable plugin features', 'aaa-paim' ) . '</label>';
		echo '</td></tr>';

		echo '<tr><th>' . esc_html__( 'Debug', 'aaa-paim' ) . '</th><td>';
		echo '<label><input type="checkbox" name="paim_debug" value="1" ' . checked( $debug, true, false ) . '> ' . esc_html__( 'Verbose logging to debug.log', 'aaa-paim' ) . '</label>';
		echo '</td></tr>';

$saved_key = (string) AAA_Paim_Options::get( 'openai_api_key', '' );

echo '<tr><th><label for="aaa_paim_openai_api_key">' . esc_html__( 'OpenAI API Key', 'aaa-paim' ) . '</label></th><td>';

// Show the key on screen (plain text). If you prefer masked-by-default + toggle, see note below.
echo '<input type="text" id="aaa_paim_openai_api_key" name="openai_api_key" class="regular-text" value="' . esc_attr( $saved_key ) . '" placeholder="sk-..."> ';

echo '<button type="button" class="button" id="aaa-paim-verify-api">' . esc_html__( 'Verify API', 'aaa-paim' ) . '</button> ';
echo '<span id="aaa-paim-verify-result" style="margin-left:8px;"></span>';

echo '<p class="description" style="margin-top:6px;">' .
     esc_html__( 'The current key is visible in the field above. Leave it unchanged to keep it, or paste a new one and click Save.', 'aaa-paim' ) .
     ' ' .
     esc_html__( 'Use “Verify API” to test the saved key or the value you enter here.', 'aaa-paim' ) .
     '</p>';

echo '</td></tr>';
		echo '<tr><th>' . esc_html__( 'Web Search (AI Sources)', 'aaa-paim' ) . '</th><td>';
		$on  = (bool) AAA_Paim_Options::get( 'web_search_enabled', 1 );
		$prov= (string) AAA_Paim_Options::get( 'web_search_provider', 'serpapi' );
		$mask = AAA_Paim_Options::get( 'web_search_api_key', '' ) ? '********' : '';
		$allow = (string) AAA_Paim_Options::get( 'web_search_allow', 'weedmaps.com, leafly.com' );

		echo '<p><label><input type="checkbox" name="paim_web_search" value="1" ' . checked( $on, true, false ) . '> ' . esc_html__( 'Enable web search for sources (no manual URLs needed)', 'aaa-paim' ) . '</label></p>';

		echo '<p><label>' . esc_html__( 'Provider', 'aaa-paim' ) . ' ';
		echo '<select name="paim_web_provider">';
		echo '<option value="serpapi"' . selected( $prov, 'serpapi', false ) . '>SerpAPI</option>';
		echo '<option value="bing"'    . selected( $prov, 'bing',    false ) . '>Bing Web Search</option>';
		echo '</select></label></p>';

		echo '<p><label>' . esc_html__( 'Search API Key', 'aaa-paim' ) . ' ';
		echo '<input type="password" name="paim_web_api_key" class="regular-text" value="" placeholder="' . esc_attr( $mask ? $mask : '••••••' ) . '"></label></p>';

		echo '<p><label>' . esc_html__( 'Domain allow-list (optional, comma-separated)', 'aaa-paim' ) . '<br>';
		echo '<input type="text" name="paim_web_allow" class="regular-text" value="' . esc_attr( $allow ) . '" placeholder="brand.com, weedmaps.com, leafly.com"></label></p>';

		echo '<p class="description">' . esc_html__( 'When enabled, the AI runner will search the web for sources and use the top pages (filtered by allowed domains if provided).', 'aaa-paim' ) . '</p>';

		echo '</td></tr>';

		echo '</tbody></table>';

		echo '<p class="submit"><button class="button button-primary" name="aaa_paim_save_global" value="1">' . esc_html__( 'Save Settings', 'aaa-paim' ) . '</button></p>';
		echo '</form>';
	}
}
