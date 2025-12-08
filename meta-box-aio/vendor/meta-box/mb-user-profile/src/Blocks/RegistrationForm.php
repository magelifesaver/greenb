<?php
namespace  MetaBox\UserProfile\Blocks;

use  MetaBox\UserProfile\Helper;
use  MetaBox\UserProfile\Forms\Factory;

class RegistrationForm {
	use DataTrait;

	public function __construct() {
		add_action( 'init', [ $this, 'register_block' ], 99 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'admin_enqueue' ], 99 );
	}

	public function register_block() {
		register_block_type( MBUP_DIR . '/blocks/registration-form/build', [
			'render_callback' => [ $this, 'render_block' ],
		] );
	}

	public function admin_enqueue(): void {
		$data = $this->get_data();

		wp_localize_script(
			'meta-box-registration-form-editor-script',
			'mbupRegisterData',
			$data
		);
	}

	public function render_block( $attributes ): string {
		$id = $attributes['id'] ?? [];
		if ( empty( $id ) && ! empty( $attributes['meta_box_id'] ) ) {
			$id = $attributes['meta_box_id'];
		}
		$id = is_array( $id ) ? implode( ',', $id ) : $id;

		$form = Factory::make( [
			'id'                 => $id,
			'redirect'           => $attributes['redirect'],
			'form_id'            => $attributes['form_id'],
			'recaptcha_key'      => $attributes['recaptcha_key'],
			'recaptcha_secret'   => $attributes['recaptcha_secret'],
			'label_title'        => $attributes['label_title'],
			'label_username'     => $attributes['label_username'],
			'label_email'        => $attributes['label_email'],
			'label_password'     => $attributes['label_password'],
			'label_password2'    => $attributes['label_password2'],
			'label_submit'       => $attributes['label_submit'],
			'id_username'        => $attributes['id_username'],
			'id_email'           => $attributes['id_email'],
			'id_password'        => $attributes['id_password'],
			'id_password2'       => $attributes['id_password2'],
			'id_submit'          => $attributes['id_submit'],
			'confirmation'       => $attributes['confirmation'],
			'email_confirmation' => Helper::convert_boolean( $attributes['email_confirmation'] ),
			'password_strength'  => $attributes['password_strength'],
			'email_as_username'  => Helper::convert_boolean( $attributes['email_as_username'] ),
			'show_if_user_can'   => $attributes['show_if_user_can'],
			'role'               => $attributes['role'],
			'append_role'        => Helper::convert_boolean( $attributes['append_role'] ),
			'auto_login'         => Helper::convert_boolean( $attributes['auto_login'] ),
		], 'register' );
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
