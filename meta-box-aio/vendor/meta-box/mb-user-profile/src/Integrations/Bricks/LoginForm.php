<?php
namespace MetaBox\UserProfile\Integrations\Bricks;

use MetaBox\UserProfile\Forms\Factory;

class LoginForm extends \Bricks\Element {
	public $category = 'meta-box';
	public $name     = 'mbup_login_form';
	public $icon     = 'fa-regular fa-rectangle-list';

	public function get_label() {
		return esc_html__( 'Login Form', 'mb-user-profile' );
	}

	public function set_controls() {

		$this->controls['redirect'] = [
			'tab'         => 'content',
			'type'        => 'text',
			'label'       => esc_html__( 'Redirect URL', 'mb-user-profile' ),
			'description' => esc_html__( 'Redirect URL, to which users will be redirected after successful login.', 'mb-user-profile' ),
		];

		$this->controls['form_id'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Form ID', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'ID (HTML attribute) of the form.', 'mb-user-profile' ),
			'default'     => 'login-form',
		];

		$this->controls['label_title'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Title', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'Label for the title of the form. Default empty.', 'mb-user-profile' ),
		];

		$this->controls['label_username'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Username field label', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'Label for the username input field.', 'mb-user-profile' ),
			'default'     => __( 'Username or Email Address', 'mb-user-profile' ),
		];

		$this->controls['label_password'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Password field label', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'Label for the password input field.', 'mb-user-profile' ),
			'default'     => __( 'Password', 'mb-user-profile' ),
		];

		$this->controls['label_remember'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Remember checkbox label', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'Label for the remember checkbox field.', 'mb-user-profile' ),
			'default'     => __( 'Remember Me', 'mb-user-profile' ),
		];

		$this->controls['label_lost_password'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Lost password field label', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'Label for the lost password link.', 'mb-user-profile' ),
			'default'     => __( 'Lost Password?', 'mb-user-profile' ),
		];

		$this->controls['label_submit'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Submit button text', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'Label for the submit button.', 'mb-user-profile' ),
			'default'     => __( 'Log In', 'mb-user-profile' ),
		];

		$this->controls['id_username'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Username field ID', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'ID (HTML attribute) of the username input field.', 'mb-user-profile' ),
			'default'     => 'user_login',
		];

		$this->controls['id_password'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Password field ID', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'ID (HTML attribute) of the password input field.', 'mb-user-profile' ),
			'default'     => 'user_pass',
		];

		$this->controls['id_remember'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Remember checkbox ID', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'ID (HTML attribute) of the remember checkbox field.', 'mb-user-profile' ),
			'default'     => 'remember',
		];

		$this->controls['id_submit'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Submit button ID', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'ID (HTML attribute) of the submit button.', 'mb-user-profile' ),
			'default'     => 'submit',
		];


		$this->controls['confirmation'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Confirmation text', 'mb-user-profile' ),
			'type'        => 'textarea',
			'description' => esc_html__( 'Confirmation message if login is successful.', 'mb-user-profile' ),
			'default'     => __( 'You are now logged in.', 'mb-user-profile' ),
		];

		$this->controls['value_username'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Default username value', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'Default value for the username field.', 'mb-user-profile' ),
		];

		$this->controls['value_remember'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Default remember', 'mb-user-profile' ),
			'type'    => 'checkbox',
			'default' => false,
		];

		$this->controls['recaptcha_key'] = [
			'tab'         => 'content',
			'type'        => 'text',
			'label'       => esc_html__( 'reCaptcha key', 'mb-user-profile' ),
			'description' => esc_html__( 'Google reCaptcha site key (version 3). Optional.', 'mb-user-profile' ),
		];

		$this->controls['recaptcha_secret'] = [
			'tab'         => 'content',
			'type'        => 'text',
			'label'       => esc_html__( 'reCaptcha secret', 'mb-user-profile' ),
			'description' => esc_html__( 'Google reCaptcha secret key (version 3). Optional.', 'mb-user-profile' ),
		];

	}

	public function render() {
		$settings = $this->settings;
		$form     = Factory::make( [
			'redirect'            => $settings['redirect'] ?? '',
			'form_id'             => $settings['form_id'] ?? '',
			'recaptcha_key'       => $settings['recaptcha_key'] ?? '',
			'recaptcha_secret'    => $settings['recaptcha_secret'] ?? '',
			'label_title'         => $settings['label_title'] ?? '',
			'label_username'      => $settings['label_username'] ?? '',
			'label_password'      => $settings['label_password'] ?? '',
			'label_remember'      => $settings['label_remember'] ?? '',
			'label_lost_password' => $settings['label_lost_password'] ?? '',
			'label_submit'        => $settings['label_submit'] ?? '',
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
		echo "<div {$this->render_attributes( '_root' )}>";
		$form->render();
		echo '</div>';
	}
}
