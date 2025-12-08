<?php
namespace MetaBox\UserProfile\Integrations\Elementor;

use Elementor\Controls_Manager;
use MetaBox\UserProfile\Forms\Factory;

class ProfileForm extends \Elementor\Widget_Base {

	public function get_name() {
		return 'mbup_profile_form';
	}

	public function get_title() {
		return esc_html__( 'Profile Form', 'mb-user-profile' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return [ 'metabox' ];
	}

	public function get_keywords() {
		return [ 'profile', 'form' ];
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
			'user_id',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => esc_html__( 'User ID', 'mb-user-profile' ),
				'description' => esc_html__( 'User ID, whose info will be edited. If not specified, the current user ID is used.', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'redirect',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => esc_html__( 'Redirect URL', 'mb-user-profile' ),
				'description' => esc_html__( 'Redirect URL, to which users will be redirected after successful submission.', 'mb-user-profile' ),
			]
		);

		$this->add_control(
			'form_id',
			[
				'label'       => esc_html__( 'Form ID', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'ID (HTML attribute) of the form.', 'mb-user-profile' ),
				'default'     => 'profile-form',
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
			'label_password',
			[
				'label'       => esc_html__( 'Password field label', 'mb-user-profile' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Label for the password input field.', 'mb-user-profile' ),
				'default'     => __( 'New Password', 'mb-user-profile' ),
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
				'default'     => __( 'Submit', 'mb-user-profile' ),
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
				'description' => esc_html__( 'Confirmation message if the form submission is successful.', 'mb-user-profile' ),
				'default'     => __( 'Your information has been successfully submitted. Thank you.', 'mb-user-profile' ),
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
			'id'                => $settings['group_ids'],
			'user_id'           => $settings['user_id'] ?: get_current_user_id(),
			'redirect'          => $settings['redirect'],
			'form_id'           => $settings['form_id'],
			'recaptcha_key'     => $settings['recaptcha_key'],
			'recaptcha_secret'  => $settings['recaptcha_secret'],
			'label_title'       => $settings['label_title'],
			'label_password'    => $settings['label_password'],
			'label_password2'   => $settings['label_password2'],
			'label_submit'      => $settings['label_submit'],
			'id_password'       => $settings['id_password'],
			'id_password2'      => $settings['id_password2'],
			'id_submit'         => $settings['id_submit'],
			'confirmation'      => $settings['confirmation'],
			'password_strength' => $settings['password_strength'] ?: false,
		], 'info' );
		if ( empty( $form ) ) {
			return '';
		}
		wp_enqueue_style( 'mbup', MBUP_URL . 'assets/user-profile.css', [], MBUP_VER );

		$form->render();
	}
}
