<?php
namespace MetaBox\UserProfile\Integrations\Oxygen;

use MetaBox\UserProfile\Forms\Factory;

class RegistrationForm extends \OxyEl {

	public function slug() {
		return 'mbup_registration_form';
	}

	public function name() {
		return esc_html__( 'Registration Form', 'mb-user-profile' );
	}

	public function button_place() {
		return 'meta-box::mb-user-profile';
	}

	public function controls() {

		$this->addOptionControl(
			[
				'slug'        => 'meta_box_id',
				'type'        => 'textfield',
				'name'        => esc_html__( 'ID', 'mb-user-profile' ),
				'description' => esc_html__( 'Field group ID(s) created for users, separated by commas. Optional. Leave blank to show the default registration form.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'redirect',
				'type'        => 'textfield',
				'name'        => esc_html__( 'Redirect URL', 'mb-user-profile' ),
				'description' => esc_html__( 'Redirect URL, to which users will be redirected after a successful registration.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'form_id',
				'name'        => esc_html__( 'Form ID', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'ID (HTML attribute) of the form.', 'mb-user-profile' ),
				'default'     => 'register-form',
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'name_title',
				'name'        => esc_html__( 'Title', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'name for the title of the form. Default empty.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'name_username',
				'name'        => esc_html__( 'Username field name', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'name for the username input field.', 'mb-user-profile' ),
				'default'     => __( 'Username', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'name_email',
				'name'        => esc_html__( 'Email field name', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'name for the email input field.', 'mb-user-profile' ),
				'default'     => __( 'Email', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'name_password',
				'name'        => esc_html__( 'Password field name', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'name for the password input field.', 'mb-user-profile' ),
				'default'     => __( 'Password', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'name_password2',
				'name'        => esc_html__( 'Confirm password field name', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'name for the confirm password input field.', 'mb-user-profile' ),
				'default'     => __( 'Confirm Password', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'name_submit',
				'name'        => esc_html__( 'Submit button text', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'name for the submit button.', 'mb-user-profile' ),
				'default'     => __( 'Register', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'id_username',
				'name'        => esc_html__( 'Username field ID', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'ID (HTML attribute) of the username input field.', 'mb-user-profile' ),
				'default'     => 'user_login',
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'id_email',
				'name'        => esc_html__( 'Email field ID', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'ID (HTML attribute) of the email input field.', 'mb-user-profile' ),
				'default'     => 'user_email',
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'id_password',
				'name'        => esc_html__( 'Password field ID', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'ID (HTML attribute) of the password input field.', 'mb-user-profile' ),
				'default'     => 'user_pass',
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'id_password2',
				'name'        => esc_html__( 'Confirm password field ID', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'ID (HTML attribute) of the confirm password input field.', 'mb-user-profile' ),
				'default'     => 'user_pass2',
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'id_submit',
				'name'        => esc_html__( 'Submit button ID', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'ID (HTML attribute) of the submit button.', 'mb-user-profile' ),
				'default'     => 'submit',
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'confirmation',
				'name'        => esc_html__( 'Confirmation text', 'mb-user-profile' ),
				'type'        => 'content',
				'description' => esc_html__( 'Confirmation message if registration is successful.', 'mb-user-profile' ),
				'default'     => __( 'Your account has been created successfully.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'name'    => esc_html__( 'Send confirmation email', 'mb-user-profile' ),
				'slug'    => 'email_confirmation',
				'type'    => 'buttons-list',
				'value'   => [
					true  => esc_html__( 'True', 'mb-user-profile' ),
					false => esc_html__( 'False', 'mb-user-profile' ),
				],
				'default' => false,
			]
		);

		$this->addOptionControl(
			[
				'type'        => 'dropdown',
				'slug'        => 'password_strength',
				'name'        => esc_html__( 'Password strength', 'mb-user-profile' ),
				'value'       => [
					false       => esc_html__( '---', 'mb-user-profile' ),
					'strong'    => esc_html__( 'Strong', 'mb-user-profile' ),
					'medium'    => esc_html__( 'Medium', 'mb-user-profile' ),
					'weak'      => esc_html__( 'Weak', 'mb-user-profile' ),
					'very-weak' => esc_html__( 'Very weak', 'mb-user-profile' ),
				],
				'description' => esc_html__( 'Set the required password strength.', 'mb-user-profile' ),
				'default'     => 'strong',
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Use email for username', 'mb-user-profile' ),
				'type'        => 'buttons-list',
				'slug'        => 'email_as_username',
				'value'       => [
					true  => esc_html__( 'True', 'mb-user-profile' ),
					false => esc_html__( 'False', 'mb-user-profile' ),
				],
				'default'     => false,
				'description' => esc_html__( 'Use email for username. If this param is true, then the username field will disappear.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'type'        => 'textfield',
				'slug'        => 'show_if_user_can',
				'name'        => esc_html__( 'Always show', 'mb-user-profile' ),
				'description' => esc_html__( 'Always show the form if the current user has the proper capability. Should be a WordPress capability. Useful if admins want to register for other people.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'type'        => 'textfield',
				'slug'        => 'role',
				'name'        => esc_html__( 'Role for the new user', 'mb-user-profile' ),
				'description' => esc_html__( 'Role for the new user. If append_role is set to true, then the new role is appended, so users will have 2 roles: the default roles set by WordPress and this role. Default empty.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'name'        => esc_html__( 'Append role', 'mb-user-profile' ),
				'type'        => 'buttons-list',
				'slug'        => 'append_role',
				'value'       => [
					true  => esc_html__( 'True', 'mb-user-profile' ),
					false => esc_html__( 'False', 'mb-user-profile' ),
				],
				'default'     => false,
				'description' => esc_html__( 'Whether to append the role to users instead of setting only one role for users.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'type'        => 'textfield',
				'slug'        => 'captcha_key',
				'name'        => esc_html__( 'reCaptcha key', 'mb-user-profile' ),
				'description' => esc_html__( 'Google reCaptcha site key (version 3). Optional.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'type'        => 'textfield',
				'slug'        => 'captcha_secret',
				'name'        => esc_html__( 'reCaptcha secret', 'mb-user-profile' ),
				'description' => esc_html__( 'Google reCaptcha secret key (version 3). Optional.', 'mb-user-profile' ),
			]
		);

	}

	public function render( $settings ) {
		$form = Factory::make( [
			'id'                 => $settings['meta_box_id'] ?? '',
			'redirect'           => $settings['redirect'] ?? '',
			'form_id'            => $settings['form_id'] ?? '',
			'recaptcha_key'      => $settings['recaptcha_key'] ?? '',
			'recaptcha_secret'   => $settings['recaptcha_secret'] ?? '',
			'label_title'        => $settings['name_title'] ?? '',
			'label_username'     => $settings['name_username'] ?? '',
			'label_email'        => $settings['name_email'] ?? '',
			'label_password'     => $settings['name_password'] ?? '',
			'label_password2'    => $settings['name_password2'] ?? '',
			'label_submit'       => $settings['name_submit'] ?? '',
			'id_username'        => $settings['id_username'] ?? '',
			'id_email'           => $settings['id_email'] ?? '',
			'id_password'        => $settings['id_password'] ?? '',
			'id_password2'       => $settings['id_password2'] ?? '',
			'id_submit'          => $settings['id_submit'] ?? '',
			'confirmation'       => $settings['confirmation'] ?? '',
			'email_confirmation' => $settings['email_confirmation'] ?? false,
			'password_strength'  => $settings['password_strength'] ?? false,
			'email_as_username'  => $settings['email_as_username'] ?? false,
			'show_if_user_can'   => $settings['show_if_user_can'] ?? '',
			'role'               => $settings['role'] ?? '',
			'append_role'        => $settings['append_role'] ?? false,
		], 'register' );
		if ( empty( $form ) ) {
			return '';
		}
		wp_enqueue_style( 'mbup', MBUP_URL . 'assets/user-profile.css', [], MBUP_VER );
		$form->render();
	}
}
