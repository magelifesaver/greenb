<?php
namespace MetaBox\UserProfile\Integrations\Elementor;

use Elementor\Controls_Manager;
use MetaBox\UserProfile\Forms\Factory;

class RegistrationForm extends \Elementor\Widget_Base {

	public function get_name() {
		return 'mbup_registration_form';
	}

	public function get_title() {
		return esc_html__( 'Registration Form', 'mb-user-profile' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return [ 'metabox' ];
	}

	public function get_keywords() {
		return [ 'registration', 'form' ];
	}

	public function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Content', 'mb-user-profile' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'group_ids',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => esc_html__( 'ID', 'mb-user-profile' ),
				'description' => esc_html__( 'Field group ID(s) created for users, separated by commas. Optional. Leave blank to show the default registration form.', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'redirect',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => esc_html__( 'Redirect URL', 'mb-user-profile' ),
				'description' => esc_html__( 'Redirect URL, to which users will be redirected after a successful registration.', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'form_id',
			[
				'label'       => esc_html__( 'Form ID', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'ID (HTML attribute) of the form.', 'mb-user-profile' ),
				'default'     => 'register-form',
			]
		);

		$this->add_control(
			'label_title',
			[
				'label'       => esc_html__( 'Title', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Label for the title of the form. Default empty.', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'label_username',
			[
				'label'       => esc_html__( 'Username field label', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Label for the username input field.', 'mb-user-profile' ),
				'default'     => __( 'Username', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'label_email',
			[
				'label'       => esc_html__( 'Email field label', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Label for the email input field.', 'mb-user-profile' ),
				'default'     => __( 'Email', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'label_password',
			[
				'label'       => esc_html__( 'Password field label', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Label for the password input field.', 'mb-user-profile' ),
				'default'     => __( 'Password', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'label_password2',
			[
				'label'       => esc_html__( 'Confirm password field label', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Label for the confirm password input field.', 'mb-user-profile' ),
				'default'     => __( 'Confirm Password', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'label_submit',
			[
				'label'       => esc_html__( 'Submit button text', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Label for the submit button.', 'mb-user-profile' ),
				'default'     => __( 'Register', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'id_username',
			[
				'label'       => esc_html__( 'Username field ID', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'ID (HTML attribute) of the username input field.', 'mb-user-profile' ),
				'default'     => 'user_login',
			]
		);

		$this->add_control(
			'id_email',
			[
				'label'       => esc_html__( 'Email field ID', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'ID (HTML attribute) of the email input field.', 'mb-user-profile' ),
				'default'     => 'user_email',
			]
		);

		$this->add_control(
			'id_password',
			[
				'label'       => esc_html__( 'Password field ID', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'ID (HTML attribute) of the password input field.', 'mb-user-profile' ),
				'default'     => 'user_pass',
			]
		);

		$this->add_control(
			'id_password2',
			[
				'label'       => esc_html__( 'Confirm password field ID', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'ID (HTML attribute) of the confirm password input field.', 'mb-user-profile' ),
				'default'     => 'user_pass2',
			]
		);

		$this->add_control(
			'id_submit',
			[
				'label'       => esc_html__( 'Submit button ID', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'ID (HTML attribute) of the submit button.', 'mb-user-profile' ),
				'default'     => 'submit',
			]
		);

		$this->add_control(
			'confirmation',
			[
				'label'       => esc_html__( 'Confirmation text', 'mb-user-profile' ),
				'type'        => Controls_Manager::WYSIWYG,
				'description' => esc_html__( 'Confirmation message if registration is successful.', 'mb-user-profile' ),
				'default'     => __( 'Your account has been created successfully.', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'email_confirmation',
			[
				'label'     => esc_html__( 'Send confirmation email', 'mb-user-profile' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'True', 'mb-user-profile' ),
				'label_off' => esc_html__( 'False', 'mb-user-profile' ),
				'default'   => 'no',
			]
		);

		$this->add_control(
			'password_strength',
			[
				'type'        => Controls_Manager::SELECT,
				'label'       => esc_html__( 'Password strength', 'mb-user-profile' ),
				'options'     => [
					''          => esc_html__( '---', 'mb-user-profile' ),
					'strong'    => esc_html__( 'Strong', 'mb-user-profile' ),
					'medium'    => esc_html__( 'Medium', 'mb-user-profile' ),
					'weak'      => esc_html__( 'Weak', 'mb-user-profile' ),
					'very-weak' => esc_html__( 'Very weak', 'mb-user-profile' ),
				],
				'description' => esc_html__( 'Set the required password strength.', 'mb-user-profile' ),
				'default'     => 'strong',
			]
		);

		$this->add_control(
			'email_as_username',
			[
				'label'       => esc_html__( 'Use email for username', 'mb-user-profile' ),
				'type'        => Controls_Manager::SWITCHER,
				'label_on'    => esc_html__( 'True', 'mb-user-profile' ),
				'label_off'   => esc_html__( 'False', 'mb-user-profile' ),
				'description' => esc_html__( 'Use email for username. If this param is true, then the username field will disappear.', 'mb-user-profile' ),
				'default'     => 'no',
			]
		);

		$this->add_control(
			'show_if_user_can',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => esc_html__( 'Always show', 'mb-user-profile' ),
				'description' => esc_html__( 'Always show the form if the current user has the proper capability. Should be a WordPress capability. Useful if admins want to register for other people.', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'role',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => esc_html__( 'Role for the new user', 'mb-user-profile' ),
				'description' => esc_html__( 'Role for the new user. If append_role is set to true, then the new role is appended, so users will have 2 roles: the default roles set by WordPress and this role. Default empty.', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'append_role',
			[
				'label'       => esc_html__( 'Append role', 'mb-user-profile' ),
				'type'        => Controls_Manager::SWITCHER,
				'label_on'    => esc_html__( 'True', 'mb-user-profile' ),
				'label_off'   => esc_html__( 'False', 'mb-user-profile' ),
				'description' => esc_html__( 'Whether to append the role to users instead of setting only one role for users.', 'mb-user-profile' ),
				'default'     => 'no',
			]
		);

		$this->add_control(
			'recaptcha_key',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => esc_html__( 'reCaptcha key', 'mb-user-profile' ),
				'description' => esc_html__( 'Google reCaptcha site key (version 3). Optional.', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'recaptcha_secret',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => esc_html__( 'reCaptcha secret', 'mb-user-profile' ),
				'description' => esc_html__( 'Google reCaptcha secret key (version 3). Optional.', 'mb-user-profile' ),
			]
		);

		$this->end_controls_section();
	}

	public function render() {
		$settings = $this->get_settings_for_display();
		$form     = Factory::make( [
			'id'                 => $settings['group_ids'],
			'redirect'           => $settings['redirect'],
			'form_id'            => $settings['form_id'],
			'recaptcha_key'      => $settings['recaptcha_key'],
			'recaptcha_secret'   => $settings['recaptcha_secret'],
			'label_title'        => $settings['label_title'],
			'label_username'     => $settings['label_username'],
			'label_email'        => $settings['label_email'],
			'label_password'     => $settings['label_password'],
			'label_password2'    => $settings['label_password2'],
			'label_submit'       => $settings['label_submit'],
			'id_username'        => $settings['id_username'],
			'id_email'           => $settings['id_email'],
			'id_password'        => $settings['id_password'],
			'id_password2'       => $settings['id_password2'],
			'id_submit'          => $settings['id_submit'],
			'confirmation'       => $settings['confirmation'],
			'email_confirmation' => ( $settings['email_confirmation'] === 'yes' ) ? true : false,
			'password_strength'  => $settings['password_strength'] ?: false,
			'email_as_username'  => ( $settings['email_as_username'] === 'yes' ) ? true : false,
			'show_if_user_can'   => $settings['show_if_user_can'],
			'role'               => $settings['role'],
			'append_role'        => ( $settings['append_role'] === 'yes' ) ? true : false,
		], 'register' );
		if ( empty( $form ) ) {
			return '';
		}
		wp_enqueue_style( 'mbup', MBUP_URL . 'assets/user-profile.css', [], MBUP_VER );

		$form->render();
	}
}
