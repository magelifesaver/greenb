<?php
namespace MetaBox\UserProfile\Integrations\Bricks;

use MetaBox\UserProfile\Forms\Factory;

class RegistrationForm extends \Bricks\Element {
	public $category = 'meta-box';
	public $name     = 'mbup_registration_form';
	public $icon     = 'fa-regular fa-rectangle-list';


	public function get_label() {
		return esc_html__( 'Registration Form', 'mb-user-profile' );
	}

	public function set_controls() {

		$this->controls['id'] = [
			'tab'         => 'content',
			'type'        => 'text',
			'label'       => esc_html__( 'ID', 'mb-user-profile' ),
			'description' => esc_html__( 'Field group ID(s) created for users, separated by commas. Optional. Leave blank to show the default registration form.', 'mb-user-profile' ),
		];

		$this->controls['redirect'] = [
			'tab'         => 'content',
			'type'        => 'text',
			'label'       => esc_html__( 'Redirect URL', 'mb-user-profile' ),
			'description' => esc_html__( 'Redirect URL, to which users will be redirected after a successful registration.', 'mb-user-profile' ),
		];

		$this->controls['form_id'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Form ID', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'ID (HTML attribute) of the form.', 'mb-user-profile' ),
			'default'     => 'register-form',
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
			'default'     => __( 'Username', 'mb-user-profile' ),
		];

		$this->controls['label_email'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Email field label', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'Label for the email input field.', 'mb-user-profile' ),
			'default'     => __( 'Email', 'mb-user-profile' ),
		];

		$this->controls['label_password'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Password field label', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'Label for the password input field.', 'mb-user-profile' ),
			'default'     => __( 'Password', 'mb-user-profile' ),
		];

		$this->controls['label_password2'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Confirm password field label', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'Label for the confirm password input field.', 'mb-user-profile' ),
			'default'     => __( 'Confirm Password', 'mb-user-profile' ),
		];

		$this->controls['label_submit'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Submit button text', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'Label for the submit button.', 'mb-user-profile' ),
			'default'     => __( 'Register', 'mb-user-profile' ),
		];

		$this->controls['id_username'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Username field ID', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'ID (HTML attribute) of the username input field.', 'mb-user-profile' ),
			'default'     => 'user_login',
		];

		$this->controls['id_email'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Email field ID', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'ID (HTML attribute) of the email input field.', 'mb-user-profile' ),
			'default'     => 'user_email',
		];

		$this->controls['id_password'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Password field ID', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'ID (HTML attribute) of the password input field.', 'mb-user-profile' ),
			'default'     => 'user_pass',
		];

		$this->controls['id_password2'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Confirm password field ID', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'ID (HTML attribute) of the confirm password input field.', 'mb-user-profile' ),
			'default'     => 'user_pass2',
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
			'description' => esc_html__( 'Confirmation message if registration is successful.', 'mb-user-profile' ),
			'default'     => __( 'Your account has been created successfully.', 'mb-user-profile' ),
		];

		$this->controls['email_confirmation'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Send confirmation email', 'mb-user-profile' ),
			'type'    => 'checkbox',
			'default' => false,
		];

		$this->controls['password_strength'] = [
			'tab'         => 'content',
			'type'        => 'select',
			'label'       => esc_html__( 'Password strength', 'mb-user-profile' ),
			'options'     => [
				'strong'    => esc_html__( 'Strong', 'mb-user-profile' ),
				'medium'    => esc_html__( 'Medium', 'mb-user-profile' ),
				'weak'      => esc_html__( 'Weak', 'mb-user-profile' ),
				'very-weak' => esc_html__( 'Very weak', 'mb-user-profile' ),
			],
			'description' => esc_html__( 'Set the required password strength.', 'mb-user-profile' ),
			'default'     => 'strong',
		];

			$this->controls['email_as_username'] = [
				'tab'         => 'content',
				'label'       => esc_html__( 'Use email for username', 'mb-user-profile' ),
				'type'        => 'checkbox',
				'description' => esc_html__( 'Use email for username. If this param is true, then the username field will disappear.', 'mb-user-profile' ),
				'default'     => false,
			];

			$this->controls['show_if_user_can'] = [
				'tab'         => 'content',
				'type'        => 'text',
				'label'       => esc_html__( 'Always show', 'mb-user-profile' ),
				'description' => esc_html__( 'Always show the form if the current user has the proper capability. Should be a WordPress capability. Useful if admins want to register for other people.', 'mb-user-profile' ),
			];

			$this->controls['role'] = [
				'tab'         => 'content',
				'type'        => 'text',
				'label'       => esc_html__( 'Role for the new user', 'mb-user-profile' ),
				'description' => esc_html__( 'Role for the new user. If append_role is set to true, then the new role is appended, so users will have 2 roles: the default roles set by WordPress and this role. Default empty.', 'mb-user-profile' ),
			];

			$this->controls['append_role'] = [
				'tab'         => 'content',
				'label'       => esc_html__( 'Append role', 'mb-user-profile' ),
				'type'        => 'checkbox',
				'description' => esc_html__( 'Whether to append the role to users instead of setting only one role for users.', 'mb-user-profile' ),
				'default'     => false,
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
			'id'                 => $settings['id'] ?? '',
			'redirect'           => $settings['redirect'] ?? '',
			'form_id'            => $settings['form_id'],
			'recaptcha_key'      => $settings['recaptcha_key'] ?? '',
			'recaptcha_secret'   => $settings['recaptcha_secret'] ?? '',
			'label_title'        => $settings['label_title'] ?? '',
			'label_username'     => $settings['label_username'] ?? '',
			'label_email'        => $settings['label_email'] ?? '',
			'label_password'     => $settings['label_password'] ?? '',
			'label_password2'    => $settings['label_password2'] ?? '',
			'label_submit'       => $settings['label_submit'] ?? '',
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
		echo "<div {$this->render_attributes( '_root' )}>";
		$form->render();
		echo '</div>';
	}
}
