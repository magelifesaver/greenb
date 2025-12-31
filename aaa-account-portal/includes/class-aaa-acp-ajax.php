<?php
/**
 * Defines AJAX actions for login and lost password. Uses nonce for security and
 * returns JSON responses. This class remains under 150 lines to fit the
 * wide‑and‑thin design.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_ACP_Ajax {

    /**
     * Register AJAX hooks for logged‑out users.
     */
    public static function init() {
        add_action( 'wp_ajax_nopriv_aaa_acp_login', [ __CLASS__, 'handle_login' ] );
        add_action( 'wp_ajax_nopriv_aaa_acp_lost_password', [ __CLASS__, 'handle_lost_password' ] );
    }

    /**
     * Verify nonce helper. Sends error JSON if nonce fails.
     */
    private static function verify_nonce() {
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'aaa_acp_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed. Please refresh and try again.' ] );
        }
    }

    /**
     * Common success response wrapper.
     *
     * @param array $data Additional data for the response.
     */
    private static function success( $data = [] ) {
        wp_send_json_success( $data );
    }

    /**
     * Common error response wrapper. Accepts a string or WP_Error.
     *
     * @param mixed $error Error message or WP_Error.
     */
    private static function error( $error ) {
        $message = is_wp_error( $error ) ? $error->get_error_message() : $error;
        wp_send_json_error( [ 'message' => $message ] );
    }

    /**
     * Handle user login via AJAX.
     */
    public static function handle_login() {
        self::verify_nonce();

        $login    = isset( $_POST['login'] )    ? sanitize_text_field( wp_unslash( $_POST['login'] ) )    : '';
        $password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
        $remember = ! empty( $_POST['remember'] );

        if ( '' === $login || '' === $password ) {
            self::error( 'Please enter your username/email and password.' );
        }

        $user = wp_signon( [
            'user_login'    => $login,
            'user_password' => $password,
            'remember'      => $remember,
        ], is_ssl() );

        if ( is_wp_error( $user ) ) {
            self::error( $user );
        }

        // Successful login: instruct JS to reload the page so cookies are set.
        self::success( [ 'action' => 'reload' ] );
    }

    /**
     * Handle lost password (forgot password) via AJAX.
     */
    public static function handle_lost_password() {
        self::verify_nonce();

        $login = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
        if ( '' === $login ) {
            self::error( 'Please provide your email address or username.' );
        }

        // Set expected POST variable for core password retrieval.
        $_POST['user_login'] = $login;

        $result = retrieve_password();
        if ( is_wp_error( $result ) ) {
            self::error( $result );
        }

        // Always return a generic success message, even if email address is unknown.
        self::success( [ 'message' => 'Check your email for the password reset link.' ] );
    }
}