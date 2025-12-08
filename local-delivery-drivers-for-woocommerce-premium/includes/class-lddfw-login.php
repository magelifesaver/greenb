<?php
/**
 * Driver Login.
 *
 * All the login functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */

/**
 * Driver Login.
 *
 * All the login functions.
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Login {

	/**
	 * Drivers logout.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function lddfw_logout() {

		// Set availability off.
		$user = wp_get_current_user();
		$user_id = $user->ID;
		$user = new WP_User( $user_id, '', get_current_blog_id() );

		if ( in_array( 'driver', (array) $user->roles, true ) ) {
			$driver_id = $user->ID;
			update_user_meta( $driver_id, 'lddfw_driver_availability', '0' );
		}

		wp_logout();
		header( 'Location: ' . lddfw_drivers_page_url( '' ) );
		exit;
	}
	/**
	 * Drivers login page.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function lddfw_login_screen() {
		// Login page.
		$html = '<div class="lddfw_page" id="lddfw_login" style="display:none;">
				<div class="container-fluid lddfw_cover">
					<div class="row">
						<div class="col-12">
						<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="sign-in-alt" class="svg-inline--fa fa-sign-in-alt fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M416 448h-84c-6.6 0-12-5.4-12-12v-40c0-6.6 5.4-12 12-12h84c17.7 0 32-14.3 32-32V160c0-17.7-14.3-32-32-32h-84c-6.6 0-12-5.4-12-12V76c0-6.6 5.4-12 12-12h84c53 0 96 43 96 96v192c0 53-43 96-96 96zm-47-201L201 79c-15-15-41-4.5-41 17v96H24c-13.3 0-24 10.7-24 24v96c0 13.3 10.7 24 24 24h136v96c0 21.5 26 32 41 17l168-168c9.3-9.4 9.3-24.6 0-34z"></path></svg>
						</div>
					</div>
				</div>
				<div class="container">
					<div class="row">
						<div class="col-12">
							<h1>' . esc_html( __( 'Login', 'lddfw' ) ) . '</h1>
							<p>' . esc_html( __( 'Enter your details below to continue.', 'lddfw' ) ) . '</p>
							<form method="post" name="lddfw_login_frm" id="lddfw_login_frm" action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" nextpage="' . lddfw_drivers_page_url( 'lddfw_screen=dashboard' ) . '">
							<div class="lddfw_alert_wrap"></div>

							<input type="text" autocapitalize=off class="form-control form-control-lg"  autocomplete="username" placeholder="' . esc_attr( __( 'Email', 'lddfw' ) ) . '" name="lddfw_login_email" id="lddfw_login_email"  value="">
								<input type="password" autocomplete="current-password" autocapitalize=off class="form-control form-control-lg" placeholder="' . esc_attr( __( 'Password', 'lddfw' ) ) . '" name="lddfw_login_password" id="lddfw_login_password" value="">
								<button class="lddfw_submit_btn btn btn-lg btn-primary btn-block" type="submit">
								' . esc_html( __( 'Login', 'lddfw' ) ) . '
								</button>
								<button style="display:none" class="lddfw_loading_btn btn-lg btn btn-block btn-primary" type="button" disabled>
								<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
								' . esc_html( __( 'Loading', 'lddfw' ) ) . '
								</button>
								<a href="#" id="lddfw_forgot_password_link">' . esc_html( __( 'Forgot password?', 'lddfw' ) ) . '</a>
							</form>
						</div>';
		if ( lddfw_fs()->is__premium_only() ) {
			if ( lddfw_fs()->is_plan( 'premium', true ) ) {
				// Delivery driver application link.
				if ( '1' === get_option( 'lddfw_driver_application', '' ) ) {
					$html .= '<div class="col-12 text-center"><a href="#" id="lddfw_application_link">' . esc_html( __( 'New Driver? Apply for Delivery Driver.', 'lddfw' ) ) . '</a></div>';
				}
			}
		}

		$html .= '</div>
				</div>
				</div>
				';
		return $html;
	}


	/**
	 * User login.
	 *
	 * @param object $user user object.
	 * @param string $password password.
	 * @since 1.5.0
	 */
	public static function lddfw_user_login( $user, $password ) {
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
		// Perform the login.
		$user = wp_signon( $creds, is_ssl() );
	}

	/**
	 * Drivers login.
	 *
	 * @since 1.0.0
	 * @return json
	 */
	public function lddfw_login_driver() {
		$error  = '';
		$result = '0';
		// Security check.
		if ( isset( $_POST['lddfw_wpnonce'] ) ) {
			 
				if ( isset( $_POST['lddfw_login_email'] ) ) {
					$email = sanitize_email( wp_unslash( $_POST['lddfw_login_email'] ) );
				}
				if ( isset( $_POST['lddfw_login_password'] ) ) {
					$password = sanitize_text_field( wp_unslash( $_POST['lddfw_login_password'] ) );
				}

				// Check for empty fields.
				if ( empty( $email ) ) {
					// No email.
					$error = __( 'The email field is empty.', 'lddfw' );
				} else {
					if ( empty( $password ) ) {
						// No password.
						$error = __( 'The password field is empty.', 'lddfw' );
					} else {
						if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
							// Invalid Email.
							$error = __( 'The email is invalid.', 'lddfw' );
						} else {
							// Check if user exists in WordPress database.
							$user = get_user_by( 'email', $email );

							// Bad email.
							if ( ! $user ) {
								$error = __( 'Either the email or password you entered is invalid.', 'lddfw' );
							} else {

								$user_id = $user->ID;
								$user = new WP_User( $user_id, '', get_current_blog_id() );

								// Check password.
								if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
									// Bad password.
									$error = __( 'Either the email or password you entered is invalid.', 'lddfw' );
								} else {
									if ( ! in_array( 'driver', (array) $user->roles, true ) ) {
										$error = __( 'You are not a registered delivery driver.', 'lddfw' );
									} else {
											$lddfw_driver_account = get_user_meta( $user->ID, 'lddfw_driver_account', true );
										if ( '1' !== $lddfw_driver_account ) {
											$error = __( 'Your account is not active, please contact the dispatch center.', 'lddfw' );
										} else {
											$this->lddfw_user_login( $user, $password );
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
			return "{\"result\":\"$result\",\"error\":\"$error\"}";
	}
}
