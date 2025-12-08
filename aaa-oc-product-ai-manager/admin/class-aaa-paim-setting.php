<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/admin/class-aaa-paim-setting.php
 * Purpose: Settings router with simple tab UI; Part 1 uses the Attribute Sets tab.
 * Version: 0.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_PAIM_DEBUG_SETTINGS' ) ) { define( 'AAA_PAIM_DEBUG_SETTINGS', true ); }

class AAA_Paim_Setting {
	public static function init() {}

	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'aaa-paim' ) );
		}
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'attributeset';
		$tabs = [
			'attributeset' => __( 'Attribute Sets', 'aaa-paim' ),
			'global'   => __( 'Global', 'aaa-paim' ),
			'products' => __( 'Products', 'aaa-paim' ),

		];
		echo '<div class="wrap"><h1>' . esc_html__( 'AAA Product AI Manager', 'aaa-paim' ) . '</h1>';
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $id => $label ) {
			$active = $tab === $id ? ' nav-tab-active' : '';
			$url = admin_url( 'admin.php?page=aaa-paim&tab=' . $id );
			echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . esc_attr( $active ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</h2>';

		switch ( $tab ) {
			case 'global':
				AAA_Paim_Tab_Global::render();
				break;

			case 'products':
				AAA_Paim_Tab_Products::render();
				break;

			case 'attributeset':
			default:
				AAA_Paim_Tab_Attribute_Set::render();
				break;
		}
		echo '</div>';
	}
}
