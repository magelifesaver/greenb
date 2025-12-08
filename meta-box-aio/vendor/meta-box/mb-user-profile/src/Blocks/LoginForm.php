<?php
namespace  MetaBox\UserProfile\Blocks;

use  MetaBox\UserProfile\Helper;
use  MetaBox\UserProfile\Forms\Factory;

class LoginForm {
	public function __construct() {
		add_action( 'init', [ $this, 'register_block' ], 99 );
	}

	public function register_block() {
		register_block_type( MBUP_DIR . '/blocks/login-form/build', [
			'render_callback' => [ $this, 'render_block' ],
		] );
	}

	public function render_block( $attributes ): string {
		$form = Factory::make( [
			'redirect'            => $attributes['redirect'],
			'form_id'             => $attributes['form_id'],
			'recaptcha_key'       => $attributes['recaptcha_key'],
			'recaptcha_secret'    => $attributes['recaptcha_secret'],
			'label_title'         => $attributes['label_title'],
			'label_username'      => $attributes['label_username'],
			'label_password'      => $attributes['label_password'],
			'label_remember'      => $attributes['label_remember'],
			'label_lost_password' => $attributes['label_lost_password'],
			'label_submit'        => $attributes['label_submit'],
			'id_username'         => $attributes['id_username'],
			'id_password'         => $attributes['id_password'],
			'id_remember'         => $attributes['id_remember'],
			'id_submit'           => $attributes['id_submit'],
			'confirmation'        => $attributes['confirmation'],
			'value_username'      => $attributes['value_username'],
			'value_remember'      => Helper::convert_boolean( $attributes['value_remember'] ),
			'password_strength'   => $attributes['password_strength'],
		], 'login' );
		if ( empty( $form ) ) {
			return '';
		}
		wp_enqueue_style( 'mbup', MBUP_URL . 'assets/user-profile.css', [], MBUP_VER );

		$wrapper_attributes = get_block_wrapper_attributes();

		ob_start();
		$form->render();

		return sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, ob_get_clean() );
	}
}
