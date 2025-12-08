<?php
namespace MetaBox\UserProfile\Integrations\Bricks;

use MetaBox\UserProfile\Forms\Factory;

class ProfileForm extends \Bricks\Element {
	public $category = 'meta-box';
	public $name     = 'mbup_profile_form';
	public $icon     = 'fa-regular fa-rectangle-list';

	public function get_label() {
		return esc_html__( 'Profile Form', 'mb-user-profile' );
	}

	public function set_controls() {

		$this->controls['id'] = [
			'tab'         => 'content',
			'type'        => 'text',
			'label'       => esc_html__( 'ID', 'mb-user-profile' ),
			'description' => esc_html__( 'Field group ID(s) created for users, separated by commas. Optional. Leave blank to show the default registration form.', 'mb-user-profile' ),
		];

		$this->controls['user_id'] = [
			'tab'         => 'content',
			'type'        => 'text',
			'label'       => esc_html__( 'User ID', 'mb-user-profile' ),
			'description' => esc_html__( 'User ID, whose info will be edited. If not specified, the current user ID is used.', 'mb-user-profile' ),
		];

		$this->controls['redirect'] = [
			'tab'         => 'content',
			'type'        => 'text',
			'label'       => esc_html__( 'Redirect URL', 'mb-user-profile' ),
			'description' => esc_html__( 'Redirect URL, to which users will be redirected after successful submission.', 'mb-user-profile' ),
		];

		$this->controls['form_id'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Form ID', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'ID (HTML attribute) of the form.', 'mb-user-profile' ),
			'default'     => 'profile-form',
		];

		$this->controls['label_title'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Title', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'Label for the title of the form. Default empty.', 'mb-user-profile' ),
		];

		$this->controls['label_password'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Password field label', 'mb-user-profile' ),
			'type'        => 'text',
			'description' => esc_html__( 'Label for the password input field.', 'mb-user-profile' ),
			'default'     => __( 'New Password', 'mb-user-profile' ),
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
			'default'     => __( 'Submit', 'mb-user-profile' ),
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
			'description' => esc_html__( 'Confirmation message if the form submission is successful.', 'mb-user-profile' ),
			'default'     => __( 'Your information has been successfully submitted. Thank you.', 'mb-user-profile' ),
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

		$this->controls['recaptcha_key'] = [
			'tab'         => 'content',
			'type'        => 'text',
			'label'       => esc_html__( 'reCaptcha key', 'mb-user-profile' ),
			'description' => esc_html__( 'Google reCaptcha site key (version 3). Optional.', 'mb-user-profile' ),
		];

		$this->controls['recaptcha_secret'] =
			[
				'tab'         => 'content',
				'type'        => 'text',
				'label'       => esc_html__( 'reCaptcha secret', 'mb-user-profile' ),
				'description' => esc_html__( 'Google reCaptcha secret key (version 3). Optional.', 'mb-user-profile' ),
			];

	}

	public function render() {
		$settings = $this->settings;
		$form     = Factory::make( [
			'id'                => $settings['id'] ?? '',
			'user_id'           => $settings['user_id'] ?? get_current_user_id(),
			'redirect'          => $settings['redirect'] ?? '',
			'form_id'           => $settings['form_id'] ?? '',
			'recaptcha_key'     => $settings['recaptcha_key'] ?? '',
			'recaptcha_secret'  => $settings['recaptcha_secret'] ?? '',
			'label_title'       => $settings['label_title'] ?? '',
			'label_password'    => $settings['label_password'] ?? '',
			'label_password2'   => $settings['label_password2'] ?? '',
			'label_submit'      => $settings['label_submit'] ?? '',
			'id_password'       => $settings['id_password'] ?? '',
			'id_password2'      => $settings['id_password2'] ?? '',
			'id_submit'         => $settings['id_submit'] ?? '',
			'confirmation'      => $settings['confirmation'] ?? '',
			'password_strength' => $settings['password_strength'] ?? false,
		], 'info' );
		if ( empty( $form ) ) {
			return '';
		}
		wp_enqueue_style( 'mbup', MBUP_URL . 'assets/user-profile.css', [], MBUP_VER );
		echo "<div {$this->render_attributes( '_root' )}>";
		$form->render();
		echo '</div>';
	}
}
