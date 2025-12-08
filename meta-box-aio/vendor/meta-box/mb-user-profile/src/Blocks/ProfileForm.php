<?php
namespace  MetaBox\UserProfile\Blocks;

use  MetaBox\UserProfile\Forms\Factory;

class ProfileForm {
	use DataTrait;

	public function __construct() {
		add_action( 'init', [ $this, 'register_block' ], 99 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'admin_enqueue' ], 99 );
	}

	public function register_block() {
		register_block_type( MBUP_DIR . '/blocks/profile-form/build', [
			'render_callback' => [ $this, 'render_block' ],
		] );
	}

	public function admin_enqueue(): void {
		$data = $this->get_data( [ 'rwmb-user-register', 'rwmb-user-login', 'rwmb-user-lost-password', 'rwmb-user-reset-password' ] );

		wp_localize_script(
			'meta-box-profile-form-editor-script',
			'mbupProfileData',
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
			'id'                => $id,
			'user_id'           => $attributes['user_id'] ?: get_current_user_id(),
			'redirect'          => $attributes['redirect'],
			'form_id'           => $attributes['form_id'],
			'recaptcha_key'     => $attributes['recaptcha_key'],
			'recaptcha_secret'  => $attributes['recaptcha_secret'],
			'label_title'       => $attributes['label_title'],
			'label_password'    => $attributes['label_password'],
			'label_password2'   => $attributes['label_password2'],
			'label_submit'      => $attributes['label_submit'],
			'id_password'       => $attributes['id_password'],
			'id_password2'      => $attributes['id_password2'],
			'id_submit'         => $attributes['id_submit'],
			'confirmation'      => $attributes['confirmation'],
			'password_strength' => $attributes['password_strength'],
		], 'info' );
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
