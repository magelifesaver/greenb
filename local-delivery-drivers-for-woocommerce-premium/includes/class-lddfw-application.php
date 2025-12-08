<?php
/**
 * Fired during plugin activation
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Application {

	/**
	 * Sending the application.
	 *
	 * @since 1.0.0
	 * @return json
	 */
	public function lddfw_application_send__premium_only() {
		$error  = '';
		$result = '0';

		if ( '1' !== get_option( 'lddfw_driver_application', '' ) ) {
			$error = __( 'The delivery driver application is not enabled.', 'lddfw' );
		} else {
			// Security check.
			if ( isset( $_POST['lddfw_wpnonce'] ) ) {

					$email     = '';
					$phone     = '';
					$full_name = '';
					$message   = '';

				if ( isset( $_POST['lddfw_application_email'] ) ) {
					$email = sanitize_email( wp_unslash( $_POST['lddfw_application_email'] ) );
				}
				if ( isset( $_POST['lddfw_application_phone'] ) ) {
					$phone = sanitize_text_field( wp_unslash( $_POST['lddfw_application_phone'] ) );
				}
				if ( isset( $_POST['lddfw_application_fullname'] ) ) {
					$full_name = sanitize_text_field( wp_unslash( $_POST['lddfw_application_fullname'] ) );
				}
				if ( isset( $_POST['lddfw_application_message'] ) ) {
					$message = sanitize_text_field( wp_unslash( $_POST['lddfw_application_message'] ) );
				}

					// Check for empty fields.
				if ( empty( $full_name ) ) {
					// No full name.
					$error = __( 'The full name field is empty.', 'lddfw' );
				} else {
					if ( empty( $email ) ) {
						// No email.
						$error = __( 'The email field is empty.', 'lddfw' );
					} else {
						if ( empty( $phone ) ) {
							// No phone.
							$error = __( 'The phone field is empty.', 'lddfw' );
						} else {
							if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
								// Invalid email.
								$error = __( 'Email is invalid.', 'lddfw' );
							} else {
								$message = __( 'Delivery Driver Application', 'lddfw' ) . ":\n" . $full_name . "\n" . $phone . "\n" . $email . "\n" . $message;
								$mail    = wp_mail( get_bloginfo( 'admin_email' ), __( 'New Delivery Driver Application', 'lddfw' ), $message, '', '' );
								if ( true === $mail ) {
									$result = '1';
								} else {
									$error = __( 'A server error occurred and your email was not sent', 'lddfw' );
								}
							}
						}
					}
				}
			}
			return "{\"result\":\"$result\",\"error\":\"$error\"}";
		}
	}

	/**
	 * Application thank you page.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function lddfw_application_thankyou_screen__premium_only() {
		if ( '1' === get_option( 'lddfw_driver_application', '' ) ) {
			$html = '<div class="lddfw_page" id="lddfw_application_thankyou" style="display:none;">
					<div class="container-fluid lddfw_cover">
						<div class="row">
							<div class="col-12">
							<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="check-circle" class="svg-inline--fa fa-check-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg>
							</div>
						</div>
					</div>
					<div class="container">
						<div class="row">
							<div class="col-12">
								<h1>' . esc_html( __( 'Your application has been sent successfully.', 'lddfw' ) ) . '</h1>
								<p>' . esc_html( __( 'We will contact you shortly.', 'lddfw' ) ) . '</p>
								<a href="#" class="lddfw_back_to_login_link">
								<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="chevron-left" class="svg-inline--fa fa-chevron-left fa-w-10" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M34.52 239.03L228.87 44.69c9.37-9.37 24.57-9.37 33.94 0l22.67 22.67c9.36 9.36 9.37 24.52.04 33.9L131.49 256l154.02 154.75c9.34 9.38 9.32 24.54-.04 33.9l-22.67 22.67c-9.37 9.37-24.57 9.37-33.94 0L34.52 272.97c-9.37-9.37-9.37-24.57 0-33.94z"></path></svg> ' . esc_html( __( 'Back to login', 'lddfw' ) ) . '</a>
							</div>
						</div>
					</div>
					</div>
					';
			return $html;
		}
	}

	/**
	 * Driver application page.
	 *
	 * @since 1.0.0
	 * @return html
	 */
	public function lddfw_application_screen__premium_only() {
		if ( '1' === get_option( 'lddfw_driver_application', '' ) ) {
			// Application form.
			$html = '<div class="lddfw_page" id="lddfw_application" style="display:none;">
					<div class="container-fluid lddfw_cover">
						<div class="row">
							<div class="col-12">
							<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="envelope" class="svg-inline--fa fa-envelope fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M464 64H48C21.49 64 0 85.49 0 112v288c0 26.51 21.49 48 48 48h416c26.51 0 48-21.49 48-48V112c0-26.51-21.49-48-48-48zm0 48v40.805c-22.422 18.259-58.168 46.651-134.587 106.49-16.841 13.247-50.201 45.072-73.413 44.701-23.208.375-56.579-31.459-73.413-44.701C106.18 199.465 70.425 171.067 48 152.805V112h416zM48 400V214.398c22.914 18.251 55.409 43.862 104.938 82.646 21.857 17.205 60.134 55.186 103.062 54.955 42.717.231 80.509-37.199 103.053-54.947 49.528-38.783 82.032-64.401 104.947-82.653V400H48z"></path></svg>
							</div>
						</div>
					</div>
					<div class="container">
						<div class="row">
							<div class="col-12">
								<h1>' . esc_html( __( 'Driver Application', 'lddfw' ) ) . '</h1>
								<p>' . esc_html( __( 'Please fill out the form below, and we will contact you shortly.', 'lddfw' ) ) . '</p>
								<form method="post" name="lddfw_application_frm" id="lddfw_application_frm" action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" >
									<div class="lddfw_alert_wrap"></div>
									<input type="text" class="form-control form-control-lg" required placeholder="' . esc_attr( __( 'Full name', 'lddfw' ) ) . '" name="lddfw_full_name" id="lddfw_application_fullname" value="">
									<input type="tel" class="form-control form-control-lg" required placeholder="' . esc_attr( __( 'Phone', 'lddfw' ) ) . '"  name="lddfw_phone" id="lddfw_application_phone" value="">
									<input type="email"  class="form-control form-control-lg" required placeholder="' . esc_attr( __( 'Email', 'lddfw' ) ) . '" name="lddfw_email" id="lddfw_application_email" value="">
									<textarea class="form-control form-control-lg" id="lddfw_application_message" placeholder="' . esc_textarea( __( 'Message', 'lddfw' ) ) . '"></textarea>
									<button class="lddfw_submit_btn btn btn-lg btn-primary btn-block" type="submit">
									' . esc_html( __( 'Send', 'lddfw' ) ) . '
									</button>
									<button style="display:none" class="lddfw_loading_btn btn-lg btn btn-block btn-primary" type="button" disabled>
									<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
									' . esc_html( __( 'Loading', 'lddfw' ) ) . '
									</button>
									<a href="#" class="lddfw_back_to_login_link">
									<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="chevron-left" class="svg-inline--fa fa-chevron-left fa-w-10" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M34.52 239.03L228.87 44.69c9.37-9.37 24.57-9.37 33.94 0l22.67 22.67c9.36 9.36 9.37 24.52.04 33.9L131.49 256l154.02 154.75c9.34 9.38 9.32 24.54-.04 33.9l-22.67 22.67c-9.37 9.37-24.57 9.37-33.94 0L34.52 272.97c-9.37-9.37-9.37-24.57 0-33.94z"></path></svg> ' . esc_html( __( 'Back to login', 'lddfw' ) ) . '</a>
								</form>
							</div>
						</div>
					</div>
				</div>';
			return $html;
		}
	}
}
