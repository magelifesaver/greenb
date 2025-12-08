<?php
namespace MetaBox\UserProfile;

class User {
	public $user_id;
	public $config;
	public $error;

	public function __construct( $config = [] ) {
		$this->config = $config;
	}

	public function save() {
		do_action( 'rwmb_profile_before_save_user', $this );

		if ( $this->user_id ) {
			$this->update();
		} else {
			$this->create();
		}

		do_action( 'rwmb_profile_after_save_user', $this );

		return $this->user_id;
	}

	private function update() {
		$data = apply_filters( 'rwmb_profile_update_user_data', [], $this->config );

		// Do not update user data, only trigger an action for Meta Box to update custom fields.
		if ( empty( $data ) ) {
			$old_user_data = get_userdata( $this->user_id );
			if ( ! $old_user_data ) {
				$this->error->add( 'invalid-id', __( 'Invalid user ID.', 'mb-user-profile' ) );
				return;
			}
			do_action( 'profile_update', $this->user_id, $old_user_data, array_merge( $data, [ 'ID' => $this->user_id ] ) );
			return;
		}

		// Update user data.
		$data['ID'] = $this->user_id;
		if ( isset( $data['user_pass'] ) && isset( $data['user_pass2'] ) && $data['user_pass'] !== $data['user_pass2'] ) {
			$this->error->add( 'passwords-not-match', __( 'Passwords do not match.', 'mb-user-profile' ) );
			return;
		}
		unset( $data['user_pass2'] );

		$result = wp_update_user( $data );
		if ( is_wp_error( $result ) ) {
			$this->error = $result;
		}
	}

	private function create() {
		$data = apply_filters( 'rwmb_profile_insert_user_data', [], $this->config );
		if ( isset( $data['user_email'] ) && ! is_email( $data['user_email'] ) ) {
			$this->error->add( 'invalid-email', __( 'Invalid email.', 'mb-user-profile' ) );
			return;
		}

		if ( isset( $data['user_email'] ) && email_exists( $data['user_email'] ) ) {
			$this->error->add( 'email-exists', __( 'Your email already exists.', 'mb-user-profile' ) );
			return;
		}

		if ( isset( $this->config['email_as_username'] ) && 'true' === $this->config['email_as_username'] && isset( $data['user_email'] ) ) {
			$data['user_login'] = $data['user_email'];
		}

		if ( isset( $data['user_login'] ) && username_exists( $data['user_login'] ) ) {
			$this->error->add( 'username-exists', __( 'Your username already exists.', 'mb-user-profile' ) );
			return;
		}
		if ( isset( $data['user_pass'] ) && isset( $data['user_pass2'] ) && $data['user_pass'] !== $data['user_pass2'] ) {
			$this->error->add( 'passwords-not-match', __( 'Passwords do not match.', 'mb-user-profile' ) );
			return;
		}
		unset( $data['user_pass2'] );

		$result = wp_insert_user( $data );
		if ( is_wp_error( $result ) ) {
			$this->error = $result;
			return;
		}

		$this->user_id = $result;

		if ( $this->config['role'] ) {
			$user = get_userdata( $this->user_id );

			if ( $this->config['append_role'] === 'true' ) {
				$user->add_role( $this->config['role'] );
			} else {
				$user->set_role( $this->config['role'] );
			}
		}

		if ( Settings::get( 'force_password_change' ) ) {
			add_user_meta( $this->user_id, 'mbup_force_password_change', 1 );
		}

		// Check if sent email confirmation.
		$email_confirmation = isset( $this->config['email_confirmation'] ) && 'true' === $this->config['email_confirmation'];
		if ( $email_confirmation ) {
			$email = new EmailConfirmation;
			$email->send_confirmation_email( $result, $data['user_email'], $data['user_login'] );
		}

		$auto_login_enabled = isset( $this->config['auto_login'] ) && 'true' === $this->config['auto_login'];
		if ( $auto_login_enabled && ! $email_confirmation ) {
			// Set the current user and auth cookie.
			wp_set_current_user( $this->user_id );
			wp_set_auth_cookie( $this->user_id );

			// Trigger wp_login action.
			$user = get_user_by( 'id', $this->user_id );
			if ( $user ) {
				do_action( 'wp_login', $user->user_login, $user );
			}
		}

	}
}
