<?php
namespace MetaBox\UserProfile\Integrations\Bricks;

class Register {
	public function __construct() {
		add_action( 'init', [ $this, 'register' ], 99 );
	}

	public function register() {
		if ( ! defined( 'BRICKS_VERSION' ) ) {
			return;
		}

		$elements = [
			__DIR__ . '/LoginForm.php',
			__DIR__ . '/RegistrationForm.php',
			__DIR__ . '/ProfileForm.php',
		];

		foreach ( $elements as $element ) {
			\Bricks\Elements::register_element( $element );
		}
	}
}
