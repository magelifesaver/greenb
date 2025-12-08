<?php
namespace MetaBox\Dependency;

class MetaBox {
	private $name;
	private $strings;

	public function __construct( $name, $strings ) {
		$this->name    = $name;
		$this->strings = array_merge( [
			// Translators: %1$s - the plugin name, %2$s - Meta Box, %3$s - action.
			'message'  => '%1$s requires %2$s to function correctly. %3$s.',
			'install'  => 'Install now',
			'activate' => 'Activate now',
		], $strings );

		add_action( 'admin_notices', [ $this, 'add_admin_notice' ] );
	}

	public function add_admin_notice() {
		if ( ! current_user_can( 'activate_plugins' ) || defined( 'RWMB_VER' ) ) {
			return;
		}

		// Required plugin.
		$name = 'Meta Box';
		$slug = 'meta-box';
		$file = "$slug/$slug.php";

		$plugins      = get_plugins();
		$is_installed = isset( $plugins[ $file ] );
		$install_url  = wp_nonce_url( admin_url( "update.php?action=install-plugin&plugin=$slug" ), "install-plugin_$slug" );
		$activate_url = wp_nonce_url( admin_url( "plugins.php?action=activate&amp;plugin=$file" ), "activate-plugin_$file" );
		$action_url   = $is_installed ? $activate_url : $install_url;
		$action       = $is_installed ? $this->strings['activate'] : $this->strings['install'];

		printf(
			'<div class="notice notice-error"><p>' . esc_html( $this->strings['message'] ) . '</p></div>',
			'<strong>' . esc_html( $this->name ) . '</strong>',
			'<strong>' . esc_html( $name ) . '</strong>',
			'<a href="' . esc_url( $action_url ) . '">' . esc_html( $action ) . '</a>'
		);
	}
}