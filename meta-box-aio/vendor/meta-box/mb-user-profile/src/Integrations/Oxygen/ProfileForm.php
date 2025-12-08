<?php
namespace MetaBox\UserProfile\Integrations\Oxygen;

use MetaBox\UserProfile\Forms\Factory;

class ProfileForm extends \OxyEl {

	public function slug() {
		return 'mbup_profile_form';
	}

	public function name() {
		return esc_html__( 'Profile Form', 'mb-user-profile' );
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
				'slug'        => 'user_id',
				'type'        => 'textfield',
				'name'        => esc_html__( 'User ID', 'mb-user-profile' ),
				'description' => esc_html__( 'User ID, whose info will be edited. If not specified, the current user ID is used.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'redirect',
				'type'        => 'textfield',
				'name'        => esc_html__( 'Redirect URL', 'mb-user-profile' ),
				'description' => esc_html__( 'Redirect URL, to which users will be redirected after successful submission.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'slug'        => 'form_id',
				'name'        => esc_html__( 'Form ID', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'ID (HTML attribute) of the form.', 'mb-user-profile' ),
				'default'     => 'profile-form',
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
				'slug'        => 'name_password',
				'name'        => esc_html__( 'Password field name', 'mb-user-profile' ),
				'type'        => 'textfield',
				'description' => esc_html__( 'name for the password input field.', 'mb-user-profile' ),
				'default'     => __( 'New Password', 'mb-user-profile' ),
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
				'default'     => __( 'Submit', 'mb-user-profile' ),
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
				'description' => esc_html__( 'Confirmation message if the form submission is successful.', 'mb-user-profile' ),
				'default'     => __( 'Your information has been successfully submitted. Thank you.', 'mb-user-profile' ),
			]
		);

		$this->addOptionControl(
			[
				'type'        => 'dropdown',
				'name'        => esc_html__( 'Password strength', 'mb-user-profile' ),
				'slug'        => 'password_strength',
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
				'type'        => 'textfield',
				'slug'        => 'captcha_key',
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
			'id'                => $settings['meta_box_id'] ?? '',
			'user_id'           => $settings['user_id'] ?? get_current_user_id(),
			'redirect'          => $settings['redirect'] ?? '',
			'form_id'           => $settings['form_id'] ?? '',
			'recaptcha_key'     => $settings['recaptcha_key'] ?? '',
			'recaptcha_secret'  => $settings['recaptcha_secret'] ?? '',
			'label_title'       => $settings['name_title'] ?? '',
			'label_password'    => $settings['name_password'] ?? '',
			'label_password2'   => $settings['name_password2'] ?? '',
			'label_submit'      => $settings['name_submit'] ?? '',
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

		$form->render();
	}
}
