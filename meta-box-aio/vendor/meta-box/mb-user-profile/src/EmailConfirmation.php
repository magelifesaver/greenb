<?php
namespace MetaBox\UserProfile;

use WP_Error;

class EmailConfirmation {
	public function __construct() {
		add_action( 'template_redirect', [ $this, 'confirm_user' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );

		add_action( 'edit_user_profile', [ $this, 'check_user_edit_page' ] );
		add_action( 'wp_ajax_send_confirmation_email', [ $this, 'send_confirmation_email_ajax' ] );
		add_filter( 'wp_authenticate_user', [$this, 'check_user_confirmed'] );
	}

	public function enqueue() {
		$screen = get_current_screen();
		if ( 'user-edit' !== $screen->base ) {
			return;
		}
		$user_id                = rwmb_request()->filter_get( 'user_id', FILTER_SANITIZE_NUMBER_INT );
		$user_confirmation_code = get_user_meta( $user_id, 'mbup_confirmation_code', true );
		if ( ! $user_confirmation_code ) {
			return;
		}

		wp_enqueue_script( 'mbup-admin', MBUP_URL . 'assets/user-profile-admin.js', [ 'jquery' ], MBUP_VER, true );
		wp_localize_script( 'mbup-admin', 'MBUP_ADMIN', array(
			'nonce'   => wp_create_nonce( 'sent_confirm_email' ),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		) );
	}

	public function send_confirmation_email( $user_id, $user_email, $username ) {
		// Add confirmation_code to the user meta
		$confirm_code = md5( uniqid() );
		update_user_meta( $user_id, 'mbup_confirmation_code', $confirm_code );

		// Setup email
		$headers     = 'Content-type: text/html';
		$subject     = '[' . get_bloginfo( 'name' ) . '] ' . __( 'Account confirmation', 'mb-user-profile' );
		$attachments = [];

		// Get and update email template
		$email_message = $this->get_mail_template( [
			'username'     => $username,
			'confirm_link' => add_query_arg( 'mbup-code', $confirm_code, home_url( '/' ) ),
		] );

		// Send mail with confirmation_code
		return wp_mail( $user_email, $subject, $email_message, $headers, $attachments );
	}

	public function send_confirmation_email_ajax() {
		check_ajax_referer( 'sent_confirm_email' );

		$request  = rwmb_request();
		$user_id  = $request->filter_post( 'uid', FILTER_VALIDATE_INT );
		$email    = (string) $request->filter_post( 'email', FILTER_SANITIZE_EMAIL );
		$username = (string) $request->post( 'username' );

		if ( empty( $email ) || empty( $user_id ) ) {
			wp_send_json_error( __( 'Account unavailable', 'mb-user-profile' ) );
		}

		if ( $this->send_confirmation_email( $user_id, $email, $username ) ) {
			wp_send_json_success( __( 'Email confirmation is sent successfully.', 'mb-user-profile' ) );
		}
		wp_send_json_error( __( 'Unable to send email. Please check your email setting.', 'mb-user-profile' ) );
	}

	private function get_mail_template( $input ) {
		ob_start();
		$template_loader = new TemplateLoader;
		$template_loader->set_template_data( $input )->get_template_part( 'confirmation-email' );

		return ob_get_clean();
	}

	public function confirm_user() {
		$confirm_code = (string) rwmb_request()->get( 'mbup-code' );
		if ( ! $confirm_code ) {
			return;
		}

		$user_exists = get_users( [
			'meta_key'   => 'mbup_confirmation_code',
			'meta_value' => $confirm_code,
		] );

		if ( ! $user_exists ) {
			if ( Settings::get( 'email_confirmation_error_page' ) ) {
				wp_safe_redirect( get_permalink( Settings::get( 'email_confirmation_error_page' ) ) );
				die;
			}

			$message = __( 'Your account is already confirmed.', 'mb-user-profile' );
		} else {
			delete_user_meta( $user_exists[0]->ID, 'mbup_confirmation_code' );

			if ( Settings::get( 'email_confirmation_success_page' ) ) {
				wp_safe_redirect( get_permalink( Settings::get( 'email_confirmation_success_page' ) ) );
				die;
			}

			$message = __( 'Your account is confirmed successfully.', 'mb-user-profile' );
		}

		// Translators: %s - Homepage URL.
		$message .= sprintf( '<p><a href="%s">%s</a></p>', home_url( '/' ), __( 'Back to homepage', 'mb-user-profile' ) ) . '</div>';

		wp_die( wp_kses_post( $message ) );
	}

	public function check_user_confirmed( $user ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$user_confirm_code = get_user_meta( $user->ID, 'mbup_confirmation_code', true );
		if ( $user_confirm_code ) {
			return new WP_Error( 'not-confirmed', __( 'Your account is not confirmed yet. Please, check your email.', 'mb-user-profile' ), [ 'status' => 401 ] );
		}

		return $user;
	}

	public function check_user_edit_page( $user ) {
		$user_confirmation_code = get_user_meta( $user->ID, 'mbup_confirmation_code', true );
		if ( ! $user_confirmation_code ) {
			return;
		}
		$title     = __( 'Email Confirmation', 'mb-user-profile' );
		$btn_title = __( 'Resend', 'mb-user-profile' );

		$content = sprintf( '<button type="button" data-uid="%s" data-email="%s" data-username="%s" class="button button-secondary mbup-confirm-btn">%s</button>', $user->ID, $user->user_email, $user->user_login, $btn_title );
		echo sprintf( '<table class="form-table"><tbody><tr class="user-description-wrap">
							<th><label for="description">%s</label></th>
							<td><div id="mbup-confirm-section">%s</div></td>
						</tr></tbody></table>',
		$title, $content );
	}
}
