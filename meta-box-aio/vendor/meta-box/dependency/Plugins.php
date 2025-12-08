<?php
namespace MetaBox\Dependency;

class Plugins {
	private $name;
	private $plugins;
	private $strings;

	public function __construct( $name, $plugins, $strings ) {
		$this->name    = $name;
		$this->plugins = $plugins;
		$this->strings = array_merge( [
			// Translators: %1$s - the plugin name, %2$s - extensions, %3$s - action.
			'message'  => '%1$s requires %2$s to function correctly. %3$s.',
			'activate' => 'Activate now',
		], $strings );

		add_action( 'admin_notices', [ $this, 'add_admin_notice' ] );
	}

	public function add_admin_notice() {
		$required = [];
		foreach ( $this->plugins as $plugin ) {
			if ( isset( $plugin['class'] ) && ! class_exists( $plugin['class'] ) ) {
				$required[] = $plugin['name'];
			}
			if ( isset( $plugin['function'] ) && ! function_exists( $plugin['function'] ) ) {
				$required[] = $plugin['name'];
			}
		}

		if ( empty( $required ) ) {
			return;
		}

		$url = wp_nonce_url( admin_url( 'admin.php?page=meta-box-aio' ), 'redirect-plugin_meta-box-aio' );
		printf(
			'<div class="notice notice-error"><p>' . esc_html( $this->strings['message'] ) . '</p></div>',
			'<strong>' . esc_html( $this->name ) . '</strong>',
			'<strong>' . esc_html( implode( ', ', $required ) ) . '</strong>',
			'<a href="' . esc_url( $url ) . '">' . esc_html( $this->strings['activate'] ) . '</a>'
		);
	}
}