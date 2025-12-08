<?php
/**
 * Password page.
 *
 * All the Password functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */

/**
 * Password page.
 *
 * All the Password functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */
class PWDDM_Password {



	/**
	 * Reset password
	 *
	 * @since 1.0.0
	 * @return json
	 */
	public function pwddm_reset_password() {
		$error  = '';
		$result = '0';
		if ( isset( $_POST['pwddm_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['pwddm_wpnonce'] ) );
			if ( ! wp_verify_nonce( $nonce, 'pwddm-nonce' ) ) {
				$error = __( 'Security Check Failure - This alert may occur when you are logged in as an administrator and as a delivery driver on the same browser and the same device. If you want to work on both panels please try to work with two different browsers.', 'pwddm' );
			} else {
				if ( isset( $_POST['pwddm_user_email'] ) ) {
					$email = sanitize_text_field( wp_unslash( $_POST['pwddm_user_email'] ) );
				}
				if ( empty( $email ) ) {
					// email is empty.
					$error = __( 'Email field is empty.', 'pwddm' );
				} else {
					// email is invalid.
					if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
						$error = __( 'The email you entered is invalid.', 'pwddm' );
					} else {
						// Check if user exists in WordPress database.
						$user = get_user_by( 'email', $email );
						if ( ! $user ) {
							// user not founded.
							$error = __( 'The email you entered was not found.', 'pwddm' );
						} else {
							if ( ! in_array( pwddm_manager_role(), (array) $user->roles, true ) ) {
								// user is not driver.
								$error = __( 'You are not a registered delivery driver.', 'pwddm' );
							} else {
								$user_login = $user->user_login;
								$reset_url  = pwddm_manager_page_url( 'pwddm_screen=resetpassword&pwddm_reset_key=' . get_password_reset_key( $user ) . '&pwddm_reset_login=' . rawurlencode( $user_login ) );

								// email content.
								$message = __( 'Someone requested that the password be reset for the following account:', 'pwddm' ) . "\r\n\r\n";
								/* translators: %s: email */
								$message .= sprintf( __( 'Email: %s', 'pwddm' ), $email ) . "\r\n\r\n";
								$message .= __( 'If this was a mistake, just ignore this email and nothing will happen.', 'pwddm' ) . "\r\n\r\n";
								$message .= __( 'To reset your password, visit the following address:', 'pwddm' ) . "\r\n\r\n";
								$message .= '<' . $reset_url . ">\r\n";

								// send email.
								$mail = wp_mail( $email, __( 'Password reset', 'pwddm' ), $message, '', '' );
								if ( false === $mail ) {
									$error  = __( 'An error occurred while sending mail.', 'pwddm' );
									$result = '0';
								} else {
									$result = '1';
								}
							}
						}
					}
				}
			}
		}
		return "{\"result\":\"$result\",\"error\":\"$error\"}";
	}
	/**
	 * New password
	 *
	 * @since 1.0.0
	 * @return json
	 */
	public function pwddm_new_password() {
		if ( isset( $_POST['pwddm_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['pwddm_wpnonce'] ) );
			if ( ! wp_verify_nonce( $nonce, 'pwddm-nonce' ) ) {
				$error = __( 'Security Check Failure - This alert may occur when you are logged in as an administrator and as a delivery driver on the same browser and the same device. If you want to work on both panels please try to work with two different browsers.', 'pwddm' );
			} else {
				$new_password     = ( isset( $_POST['pwddm_new_password'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_new_password'] ) ) : '';
				$confirm_password = ( isset( $_POST['pwddm_confirm_password'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_confirm_password'] ) ) : '';
				$reset_key        = ( isset( $_POST['pwddm_reset_key'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_reset_key'] ) ) : '';
				$reset_login      = ( isset( $_POST['pwddm_reset_login'] ) ) ? sanitize_text_field( wp_unslash( $_POST['pwddm_reset_login'] ) ) : '';

				$result = '0';
				$error  = '';
				if ( empty( $new_password ) ) {
					// new password is empty.
					$error = __( 'The new password field is empty.', 'pwddm' );
				} else {
					if ( empty( $confirm_password ) ) {
						// confirm password is empty.
						$error = __( 'The confirm password field is empty.', 'pwddm' );
					} else {
						if ( $new_password !== $confirm_password ) {
							// Passwords not match.
							$error = __( 'New password and confirm password do not match.', 'pwddm' );
						} else {
							$new_password     = wp_unslash( $new_password );
							$confirm_password = wp_unslash( $confirm_password );

							$user = WC_Shortcode_My_Account::check_password_reset_key( $reset_key, $reset_login );
							if ( $user instanceof WP_User ) {
								WC_Shortcode_My_Account::reset_password( $user, $new_password );
								$result = '1';
							} else {
								$error = __( 'This key is invalid or has already been used. Please reset your password again if needed.', 'pwddm' );
							}
						}
					}
				}
			}
		}
		return "{\"result\":\"$result\",\"error\":\"$error\"}";
	}
	/**
	 * Forgot password screen
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function pwddm_forgot_password_screen() {
		// forgot password page.
		$html = '<div class="pwddm_page" id="pwddm_forgot_password" style="display:none;">
		<div class="container-fluid pwddm_cover">
			<div class="row">
				<div class="col-12">
				<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="key" class="svg-inline--fa fa-key fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M512 176.001C512 273.203 433.202 352 336 352c-11.22 0-22.19-1.062-32.827-3.069l-24.012 27.014A23.999 23.999 0 0 1 261.223 384H224v40c0 13.255-10.745 24-24 24h-40v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24v-78.059c0-6.365 2.529-12.47 7.029-16.971l161.802-161.802C163.108 213.814 160 195.271 160 176 160 78.798 238.797.001 335.999 0 433.488-.001 512 78.511 512 176.001zM336 128c0 26.51 21.49 48 48 48s48-21.49 48-48-21.49-48-48-48-48 21.49-48 48z"></path></svg>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="row">
				<div class="col-12">
					<h1>' . esc_html( __( 'Forgot your password?', 'pwddm' ) ) . '</h1>
					<p>' . esc_html( __( "Enter your email, and we'll email you a link to change your password.", 'pwddm' ) ) . '</p>
					<form method="post" name="pwddm_forgot_password_frm" id="pwddm_forgot_password_frm" action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" nextpage="' . pwddm_manager_page_url( 'pwddm_screen=dashboard' ) . '">
					<div class="pwddm_alert_wrap"></div>
					<input type="text" autocapitalize="off" class="form-control form-control-lg" placeholder="' . esc_attr( __( 'Email', 'pwddm' ) ) . '" name="pwddm_user_email" id="pwddm_user_email" value="">
					<div class="d-grid gap-2 col-12 col-md-6 mx-auto">	
					<button class="pwddm_submit_btn btn btn-primary btn-lg btn-block" type="submit">
						' . esc_html( __( 'Send', 'pwddm' ) ) . '
						</button>
						<button style="display:none" class="pwddm_loading_btn btn-lg btn btn-block btn-primary" type="button" disabled>
						<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
						' . esc_html( __( 'Loading', 'pwddm' ) ) . '
						</button>
						</div>
						<a href="#" class="pwddm_back_to_login_link">
						<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="chevron-left" class="svg-inline--fa fa-chevron-left fa-w-10" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M34.52 239.03L228.87 44.69c9.37-9.37 24.57-9.37 33.94 0l22.67 22.67c9.36 9.36 9.37 24.52.04 33.9L131.49 256l154.02 154.75c9.34 9.38 9.32 24.54-.04 33.9l-22.67 22.67c-9.37 9.37-24.57 9.37-33.94 0L34.52 272.97c-9.37-9.37-9.37-24.57 0-33.94z"></path></svg> ' . esc_html( __( 'Back to login', 'pwddm' ) ) . '</a>
					</form>
				</div>
			</div>
		</div>
		</div>
		';
		return $html;
	}




	/**
	 * New password created screen
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function pwddm_new_password_created_screen() {
		// forgot password email sent.
		$html = '<div class="pwddm_page" id="pwddm_new_password_created" style="display:none;">
<div class="container-fluid pwddm_cover">
	<div class="row">
		<div class="col-12">
		<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="check-circle" class="svg-inline--fa fa-check-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg>
		</div>
	</div>
</div>
<div class="container">
	<div class="row">
		<div class="col-12">
			<h1>' . esc_html( __( 'Your password has been changed successfully.', 'pwddm' ) ) . '</h1>
			<p>' . esc_html( __( 'Please click on the login button to login with your new password', 'pwddm' ) ) . '</p>
			<button id="pwddm_login_button" class="btn btn-lg btn-primary btn-block" type="button">
				' . esc_html( __( 'Login', 'pwddm' ) ) . '
			</button>
		</div>
	</div>
</div>
</div>
';
		return $html;
	}

	/**
	 * Forgot password email sent screen
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function pwddm_forgot_password_email_sent_screen() {
		// forgot password email sent.
		$html = '<div class="pwddm_page" id="pwddm_forgot_password_email_sent" style="display:none;">
		<div class="container-fluid pwddm_cover">
			<div class="row">
				<div class="col-12">
				<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="paper-plane" class="svg-inline--fa fa-paper-plane fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M440 6.5L24 246.4c-34.4 19.9-31.1 70.8 5.7 85.9L144 379.6V464c0 46.4 59.2 65.5 86.6 28.6l43.8-59.1 111.9 46.2c5.9 2.4 12.1 3.6 18.3 3.6 8.2 0 16.3-2.1 23.6-6.2 12.8-7.2 21.6-20 23.9-34.5l59.4-387.2c6.1-40.1-36.9-68.8-71.5-48.9zM192 464v-64.6l36.6 15.1L192 464zm212.6-28.7l-153.8-63.5L391 169.5c10.7-15.5-9.5-33.5-23.7-21.2L155.8 332.6 48 288 464 48l-59.4 387.3z"></path></svg>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="row">
				<div class="col-12">
					<h1>' . esc_html( __( 'Reset password', 'pwddm' ) ) . '</h1>
					<p>' . esc_html( __( 'A password reset link was sent. Click the link in the email to create a new password. If you do not receive an email within 5 minutes, please click on the resend email button below.', 'pwddm' ) ) . '</p>
					<button id="pwddm_resend_button" class="btn btn-lg btn-primary btn-block" type="button">
						' . esc_html( __( 'Resend email', 'pwddm' ) ) . '
					</button>
					<a href="#" id="pwddm_back_to_forgot_password_link">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="chevron-left" class="svg-inline--fa fa-chevron-left fa-w-10" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M34.52 239.03L228.87 44.69c9.37-9.37 24.57-9.37 33.94 0l22.67 22.67c9.36 9.36 9.37 24.52.04 33.9L131.49 256l154.02 154.75c9.34 9.38 9.32 24.54-.04 33.9l-22.67 22.67c-9.37 9.37-24.57 9.37-33.94 0L34.52 272.97c-9.37-9.37-9.37-24.57 0-33.94z"></path></svg> ' . esc_html( __( 'Back to forgot password', 'pwddm' ) ) . '</a>
				</div>
			</div>
		</div>
		</div>
		';
		return $html;
	}

	/**
	 * Create password screen
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function pwddm_create_password_screen() {
		// show delivery driver homepage.
		global $pwddm_screen, $pwddm_reset_key, $pwddm_reset_login;

		$style_password_reset = 'style="display:none"';

		if ( 'resetpassword' === $pwddm_screen ) {

			$style_password_reset = 'style="display:block"';
		}
		// New password.
		$html = '<div class="pwddm_page" id="pwddm_create_new_password" ' . $style_password_reset . '>
		<div class="container-fluid pwddm_cover">
			<div class="row">
				<div class="col-12">
				<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="key" class="svg-inline--fa fa-key fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M512 176.001C512 273.203 433.202 352 336 352c-11.22 0-22.19-1.062-32.827-3.069l-24.012 27.014A23.999 23.999 0 0 1 261.223 384H224v40c0 13.255-10.745 24-24 24h-40v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24v-78.059c0-6.365 2.529-12.47 7.029-16.971l161.802-161.802C163.108 213.814 160 195.271 160 176 160 78.798 238.797.001 335.999 0 433.488-.001 512 78.511 512 176.001zM336 128c0 26.51 21.49 48 48 48s48-21.49 48-48-21.49-48-48-48-48 21.49-48 48z"></path></svg>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="row">
				<div class="col-12">
					<form method="post" name="pwddm_new_password_frm" id="pwddm_new_password_frm" action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" nextpage="' . pwddm_manager_page_url( 'pwddm_screen=dashboard' ) . '">
					<h1>' . esc_html( __( 'Create a new password.', 'pwddm' ) ) . '</h1>
					<div class="pwddm_alert_wrap"></div>
					<input type="text" autocapitalize=off class="form-control form-control-lg" placeholder="' . __( 'New password', 'pwddm' ) . '" name="pwddm_new_password"  id="pwddm_new_password" value="">
					<input type="text" autocapitalize=off class="form-control form-control-lg" placeholder="' . __( 'Confirm password', 'pwddm' ) . '" name="pwddm_confirm_password" id="pwddm_confirm_password" value="">
					<input type="hidden" id="pwddm_reset_key" name="pwddm_reset_key" value="' . $pwddm_reset_key . '">
					<input type="hidden" id="pwddm_reset_login" name="pwddm_reset_login" value="' . $pwddm_reset_login . '">
					<div class="d-grid gap-2 col-12 col-md-6 mx-auto">
					<button class="pwddm_submit_btn btn btn-lg btn-primary btn-block" type="submit">
					' . esc_html( __( 'Send', 'pwddm' ) ) . '
					</button>
					<button style="display:none" class="pwddm_loading_btn btn btn-lg btn-block btn-primary" type="button" disabled>
					<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
					' . esc_html( __( 'Send', 'pwddm' ) ) . '
					</button>
					</div>
					<div class="pwddm_links">
					<a href="#" id="pwddm_new_password_reset_link">
					<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="chevron-left" class="svg-inline--fa fa-chevron-left fa-w-10" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M34.52 239.03L228.87 44.69c9.37-9.37 24.57-9.37 33.94 0l22.67 22.67c9.36 9.36 9.37 24.52.04 33.9L131.49 256l154.02 154.75c9.34 9.38 9.32 24.54-.04 33.9l-22.67 22.67c-9.37 9.37-24.57 9.37-33.94 0L34.52 272.97c-9.37-9.37-9.37-24.57 0-33.94z"></path></svg> ' . esc_html( __( 'Back to forgot password', 'pwddm' ) ) . '</a>
					  </div>
					</form>
				</div>
			</div>
		</div>
		</div>
		';
		return $html;
	}
}
