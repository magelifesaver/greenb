<?php
namespace MetaBox\UserProfile\Integrations\Oxygen;

use MetaBox\UserProfile\Forms\Factory;

class LoginForm extends  \OxyEl {

	public function slug() {
		return 'mbup_login_form';
	}

	public function name() {
		return esc_html__( 'Login Form', 'mb-user-profile' );
	}

	public function button_place() {
		return 'meta-box::mb-user-profile';
	}

	public function controls() {

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Redirect URL', 'mb-user-profile' ),
				'type'        => 'textfield',
				'slug'        => 'redirect',
				'description' => esc_html__( 'Redirect URL, to which users will be redirected after successful login.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Form ID', 'mb-user-profile' ),
				'type'        => 'textfield',
				'slug'        => 'form_id',
				'description' => esc_html__( 'ID (HTML attribute) of the form.', 'mb-user-profile' ),
				'default'     => 'login-form',
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Title', 'mb-user-profile' ),
				'type'        => 'textfield',
				'slug'        => 'name_title',
				'description' => esc_html__( 'name for the title of the form. Default empty.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Username field name', 'mb-user-profile' ),
				'type'        => 'textfield',
				'slug'        => 'name_username',
				'description' => esc_html__( 'name for the username input field.', 'mb-user-profile' ),
				'default'     => __( 'Username or Email Address', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Password field name', 'mb-user-profile' ),
				'type'        => 'textfield',
				'slug'        => 'name_password',
				'description' => esc_html__( 'name for the password input field.', 'mb-user-profile' ),
				'default'     => __( 'Password', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Remember checkbox name', 'mb-user-profile' ),
				'type'        => 'textfield',
				'slug'        => 'name_remember',
				'description' => esc_html__( 'name for the remember checkbox field.', 'mb-user-profile' ),
				'default'     => __( 'Remember Me', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Lost password field name', 'mb-user-profile' ),
				'type'        => 'textfield',
				'slug'        => 'name_lost_password',
				'description' => esc_html__( 'name for the lost password link.', 'mb-user-profile' ),
				'default'     => __( 'Lost Password?', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Submit button text', 'mb-user-profile' ),
				'type'        => 'textfield',
				'slug'        => 'name_submit',
				'description' => esc_html__( 'name for the submit button.', 'mb-user-profile' ),
				'default'     => __( 'Log In', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Username field ID', 'mb-user-profile' ),
				'type'        => 'textfield',
				'slug'        => 'id_username',
				'description' => esc_html__( 'ID (HTML attribute) of the username input field.', 'mb-user-profile' ),
				'default'     => 'user_login',
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Password field ID', 'mb-user-profile' ),
				'type'        => 'textfield',
				'slug'        => 'id_password',
				'description' => esc_html__( 'ID (HTML attribute) of the password input field.', 'mb-user-profile' ),
				'default'     => 'user_pass',
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Remember checkbox ID', 'mb-user-profile' ),
				'type'        => 'textfield',
				'slug'        => 'id_remember',
				'description' => esc_html__( 'ID (HTML attribute) of the remember checkbox field.', 'mb-user-profile' ),
				'default'     => 'remember',
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Submit button ID', 'mb-user-profile' ),
				'type'        => 'textfield',
				'slug'        => 'id_submit',
				'description' => esc_html__( 'ID (HTML attribute) of the submit button.', 'mb-user-profile' ),
				'default'     => 'submit',
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Confirmation text', 'mb-user-profile' ),
				'type'        => 'content',
				'slug'        => 'confirmation',
				'description' => esc_html__( 'Confirmation message if login is successful.', 'mb-user-profile' ),
				'default'     => __( 'You are now logged in.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Default username value', 'mb-user-profile' ),
				'type'        => 'textfield',
				'slug'        => 'value_username',
				'description' => esc_html__( 'Default value for the username field.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'name'    => esc_html__( 'Default remember', 'mb-user-profile' ),
				'type'    => 'buttons-list',
				'slug'    => 'value_remember',
				'value'   => [
					true  => esc_html__( 'True', 'mb-user-profile' ),
					false => esc_html__( 'False', 'mb-user-profile' ),
				],
				'default' => false,
			]
		);

		$this->addOptionControl(
			[
				'type'        => 'textfield',
				'slug'        => 'recaptcha_key',
				'name'        => esc_html__( 'reCaptcha key', 'mb-user-profile' ),
				'description' => esc_html__( 'Google reCaptcha site key (version 3). Optional.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'type'        => 'textfield',
				'slug'        => 'recaptcha_secret',
				'name'        => esc_html__( 'reCaptcha secret', 'mb-user-profile' ),
				'description' => esc_html__( 'Google reCaptcha secret key (version 3). Optional.', 'mb-user-profile' ),
			]
		);
	}

	public function render( $settings ) {
		$form = Factory::make( [
			'redirect'            => $settings['redirect'] ?? '',
			'form_id'             => $settings['form_id'] ?? '',
			'recaptcha_key'       => $settings['recaptcha_key'] ?? '',
			'recaptcha_secret'    => $settings['recaptcha_secret'] ?? '',
			'label_title'         => $settings['name_title'] ?? '',
			'label_username'      => $settings['name_username'] ?? '',
			'label_password'      => $settings['name_password'] ?? '',
			'label_remember'      => $settings['name_remember'] ?? '',
			'label_lost_password' => $settings['name_lost_password'] ?? '',
			'label_submit'        => $settings['name_submit'] ?? '',
			'id_username'         => $settings['id_username'] ?? '',
			'id_password'         => $settings['id_password'] ?? '',
			'id_remember'         => $settings['id_remember'] ?? '',
			'id_submit'           => $settings['id_submit'] ?? '',
			'confirmation'        => $settings['confirmation'] ?? '',
			'value_username'      => $settings['value_username'] ?? '',
			'value_remember'      => $settings['value_remember'] ?? false,
		], 'login' );
		if ( empty( $form ) ) {
			return '';
		}
		wp_enqueue_style( 'mbup', MBUP_URL . 'assets/user-profile.css', [], MBUP_VER );
		$form->render();
	}
}
