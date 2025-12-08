<?php
/**
 * Manager Login.
 *
 * All the login functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */

/**
 * Manager Login.
 *
 * All the login functions.
 *
 * @package    PWDDM
 * @subpackage PWDDM/includes
 * @author     powerfulwp <cs@powerfulwp.com>
 */
class PWDDM_Login {

	/**
	 * Manager logout.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function pwddm_logout() {
		wp_logout();
		header( 'Location: ' . pwddm_manager_page_url( '' ) );
		exit;
	}
	/**
	 * Manager login page.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function pwddm_login_screen() {
		// Login page.
		$html = '<div class="pwddm_page" id="pwddm_login" style="display:none;">
				<div class="container-fluid pwddm_cover">
					<div class="row">
						<div class="col-12">
						<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="sign-in-alt" class="svg-inline--fa fa-sign-in-alt fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M416 448h-84c-6.6 0-12-5.4-12-12v-40c0-6.6 5.4-12 12-12h84c17.7 0 32-14.3 32-32V160c0-17.7-14.3-32-32-32h-84c-6.6 0-12-5.4-12-12V76c0-6.6 5.4-12 12-12h84c53 0 96 43 96 96v192c0 53-43 96-96 96zm-47-201L201 79c-15-15-41-4.5-41 17v96H24c-13.3 0-24 10.7-24 24v96c0 13.3 10.7 24 24 24h136v96c0 21.5 26 32 41 17l168-168c9.3-9.4 9.3-24.6 0-34z"></path></svg>
						</div>
					</div>
				</div>
				<div class="container">
					<div class="row">
						<div class="col-12">
							<h1>' . esc_html( __( 'Login', 'pwddm' ) ) . '</h1>
							<p>' . esc_html( __( 'Enter your details below to continue.', 'pwddm' ) ) . '</p>
							<form method="post" name="pwddm_login_frm" id="pwddm_login_frm" action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" nextpage="' . pwddm_manager_page_url( 'pwddm_screen=dashboard' ) . '">
							<div class="pwddm_alert_wrap"></div>

							<input type="text" autocapitalize=off class="form-control form-control-lg"  autocomplete="username" placeholder="' . esc_attr( __( 'Email', 'pwddm' ) ) . '" name="pwddm_login_email" id="pwddm_login_email"  value="">
								<input type="password" autocomplete="current-password" autocapitalize=off class="form-control form-control-lg" placeholder="' . esc_attr( __( 'Password', 'pwddm' ) ) . '" name="pwddm_login_password" id="pwddm_login_password" value="">
								<div class="d-grid gap-2 col-12 col-md-6 mx-auto">
								<button class="pwddm_submit_btn btn btn-lg btn-primary btn-block" type="submit">
								' . esc_html( __( 'Login', 'pwddm' ) ) . '
								</button>
								<button style="display:none" class="pwddm_loading_btn btn-lg btn btn-block btn-primary" type="button" disabled>
								<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
								' . esc_html( __( 'Loading', 'pwddm' ) ) . '
								</button>
								</div>
								<a href="#" id="pwddm_forgot_password_link">' . esc_html( __( 'Forgot password?', 'pwddm' ) ) . '</a>
							</form>
						</div>';

		$html .= '</div>
				</div>
				</div>
				';
		return $html;
	}

	/**
	 * Manager login.
	 *
	 * @since 1.0.0
	 * @return json
	 */
	public function pwddm_login_manager() {
		$error  = '';
		$result = '0';
		// Security check.
		if ( isset( $_POST['pwddm_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['pwddm_wpnonce'] ) );
			if ( ! wp_verify_nonce( $nonce, 'pwddm-nonce' ) ) {
				$error = __( 'Security Check Failure - This alert may occur when you are logged in as an administrator and as a manager on the same browser and the same device. If you want to work on both panels please try to work with two different browsers.', 'pwddm' );
			} else {
				if ( isset( $_POST['pwddm_login_email'] ) ) {
					$email = sanitize_text_field( wp_unslash( $_POST['pwddm_login_email'] ) );
				}
				if ( isset( $_POST['pwddm_login_password'] ) ) {
					$password = sanitize_text_field( wp_unslash( $_POST['pwddm_login_password'] ) );
				}

				// Check for empty fields.
				if ( empty( $email ) ) {
					// No email.
					$error = __( 'The email field is empty.', 'pwddm' );
				} else {
					if ( empty( $password ) ) {
						// No password.
						$error = __( 'The password field is empty.', 'pwddm' );
					} else {
						if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
							// Invalid Email.
							$error = __( 'The email is invalid.', 'pwddm' );
						} else {
							// Check if user exists in WordPress database.
							$user = get_user_by( 'email', $email );

							// Bad email.
							if ( ! $user ) {
								$error = __( 'Either the email or password you entered is invalid.', 'pwddm' );
							} else {
								// Check password.
								if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
									// Bad password.
									$error = __( 'Either the email or password you entered is invalid.', 'pwddm' );
								} else {
									if ( ! in_array( pwddm_manager_role(), (array) $user->roles, true ) ) {
										$error = __( 'You are not a registered delivery manager.', 'pwddm' );
									} else {
											$pwddm_manager_account = get_user_meta( $user->ID, 'pwddm_manager_account', true );
										if ( '1' !== $pwddm_manager_account ) {
											$error = __( 'Your account is not active, please contact your administrator.', 'pwddm' );
										} else {
											$user_login             = $user->user_login;
											$creds                  = array();
											$creds['user_login']    = $user_login;
											$creds['user_password'] = $password;
											$creds['remember']      = true;
											$user                   = wp_signon( $creds, false );
											$user_id                = $user->ID;
											wp_set_current_user( $user_id, $user_login );
											wp_set_auth_cookie( $user_id, true, false );
											do_action( 'wp_login', $user_login, $user );
											$error  = '';
											$result = '1';
										}
									}
								}
							}
						}
					}
				}
			}
		}
			return "{\"result\":\"$result\",\"error\":\"$error\"}";
	}
}
