<?php
namespace MetaBox\UserProfile\Integrations\Elementor;

use Elementor\Controls_Manager;
use MetaBox\UserProfile\Forms\Factory;

class LoginForm extends \Elementor\Widget_Base {

	public function get_name() {
		return 'mbup_login_form';
	}

	public function get_title() {
		return esc_html__( 'Login Form', 'mb-user-profile' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return [ 'metabox' ];
	}

	public function get_keywords() {
		return [ 'login', 'form' ];
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
			'redirect',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => esc_html__( 'Redirect URL', 'mb-user-profile' ),
				'description' => esc_html__( 'Redirect URL, to which users will be redirected after successful login.', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'form_id',
			[
				'label'       => esc_html__( 'Form ID', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'ID (HTML attribute) of the form.', 'mb-user-profile' ),
				'default'     => 'login-form',
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
				'default'     => __( 'Username or Email Address', 'mb-user-profile' ),
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
			'label_remember',
			[
				'label'       => esc_html__( 'Remember checkbox label', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Label for the remember checkbox field.', 'mb-user-profile' ),
				'default'     => __( 'Remember Me', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'label_lost_password',
			[
				'label'       => esc_html__( 'Lost password field label', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Label for the lost password link.', 'mb-user-profile' ),
				'default'     => __( 'Lost Password?', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'label_submit',
			[
				'label'       => esc_html__( 'Submit button text', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Label for the submit button.', 'mb-user-profile' ),
				'default'     => __( 'Log In', 'mb-user-profile' ),
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
			'id_password',
			[
				'label'       => esc_html__( 'Password field ID', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'ID (HTML attribute) of the password input field.', 'mb-user-profile' ),
				'default'     => 'user_pass',
			]
		);

		$this->add_control(
			'id_remember',
			[
				'label'       => esc_html__( 'Remember checkbox ID', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'ID (HTML attribute) of the remember checkbox field.', 'mb-user-profile' ),
				'default'     => 'remember',
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
				'description' => esc_html__( 'Confirmation message if login is successful.', 'mb-user-profile' ),
				'default'     => __( 'You are now logged in.', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'value_username',
			[
				'label'       => esc_html__( 'Default username value', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Default value for the username field.', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'value_remember',
			[
				'label'        => esc_html__( 'Default remember', 'mb-user-profile' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'True', 'mb-user-profile' ),
				'label_off'    => esc_html__( 'False', 'mb-user-profile' ),
				'default'      => 'no',
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
			'redirect'            => $settings['redirect'],
			'form_id'             => $settings['form_id'],
			'recaptcha_key'       => $settings['recaptcha_key'],
			'recaptcha_secret'    => $settings['recaptcha_secret'],
			'label_title'         => $settings['label_title'],
			'label_username'      => $settings['label_username'],
			'label_password'      => $settings['label_password'],
			'label_remember'      => $settings['label_remember'],
			'label_lost_password' => $settings['label_lost_password'],
			'label_submit'        => $settings['label_submit'],
			'id_username'         => $settings['id_username'],
			'id_password'         => $settings['id_password'],
			'id_remember'         => $settings['id_remember'],
			'id_submit'           => $settings['id_submit'],
			'confirmation'        => $settings['confirmation'],
			'value_username'      => $settings['value_username'],
			'value_remember'      => ( $settings['value_remember'] === 'yes' ) ? true : false,
		], 'login' );
		if ( empty( $form ) ) {
			return '';
		}
		wp_enqueue_style( 'mbup', MBUP_URL . 'assets/user-profile.css', [], MBUP_VER );
		$form->render();
	}
}
