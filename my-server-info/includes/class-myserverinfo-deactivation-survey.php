<?php
/**
 * Deactivation survey 
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'MyServerInfo_Deactivation_Survey' ) ) {

class MyServerInfo_Deactivation_Survey {

    const VER = '1.0.0';


    const WEBAPP_URL = 'https://script.google.com/macros/s/AKfycbzgYxc_m7GB5_1esy-CJfopICWbsBx5afY6OHYfCnExRoQHemVd1ln4n2sj74fFF7mi/exec';

    /** Cached plugin version */
    private static $cached_version = null;

    /** Main plugin file path and basename */
    protected static $plugin_file;
    protected static $plugin_basename;

    public static function init( $plugin_file ) {
        self::$plugin_file     = $plugin_file;
        self::$plugin_basename = plugin_basename( $plugin_file );

        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'admin_footer-plugins.php', [ __CLASS__, 'render_modal' ] );
        add_action( 'wp_ajax_myserverinfo_submit_survey', [ __CLASS__, 'ajax_submit' ] );
    }

    /* ================= Plugin version ================= */

    private static function get_plugin_version() {
        if ( null !== self::$cached_version ) {
            return self::$cached_version;
        }

        if ( defined( 'MSI_PLUGIN_VERSION' ) ) {
            return self::$cached_version = (string) MSI_PLUGIN_VERSION;
        }
        if ( defined( 'MY_SERVER_INFO_VERSION' ) ) {
            return self::$cached_version = (string) MY_SERVER_INFO_VERSION;
        }

        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $data = get_plugin_data( self::$plugin_file, false, false );
        if ( ! empty( $data['Version'] ) ) {
            return self::$cached_version = (string) $data['Version'];
        }

        return self::$cached_version = 'unknown';
    }

    /* ================= Assets ================= */

    public static function enqueue_assets( $hook ) {
        if ( 'plugins.php' !== $hook ) {
            return;
        }

        wp_register_style(
            'myserverinfo-deactivate-survey',
            plugins_url( 'assets/deactivate-survey.css', self::$plugin_file ),
            [],
            self::VER
        );
        wp_enqueue_style( 'myserverinfo-deactivate-survey' );

        wp_register_script(
            'myserverinfo-deactivate-survey',
            plugins_url( 'assets/deactivate-survey.js', self::$plugin_file ),
            [ 'jquery' ],
            self::VER,
            true
        );

        wp_localize_script(
            'myserverinfo-deactivate-survey',
            'MyServerInfoSurvey',
            [
                'nonce'          => wp_create_nonce( 'myserverinfo_survey_nonce' ),
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'pluginBasename' => self::$plugin_basename,
                'i18n'           => [
                    'modalTitle'    => __( 'Please tell us why you are deactivating the plugin?', 'my-server-info' ),
                    'submit'        => __( 'Send & Deactivate', 'my-server-info' ),
                    'skip'          => __( 'Just Deactivate', 'my-server-info' ),
                    'cancel'        => __( 'Cancel', 'my-server-info' ),
                    'errorNoReason' => __( 'Please select a reason.', 'my-server-info' ),
                ],
            ]
        );

        wp_enqueue_script( 'myserverinfo-deactivate-survey' );
    }

    public static function render_modal() {
        ?>
        <div id="myserverinfo-survey-modal"
             class="msi-survey-backdrop"
             style="display:none"
             role="dialog"
             aria-modal="true"
             aria-labelledby="msi-survey-title">
            <div class="msi-survey-modal">
                <h2 id="msi-survey-title">
                    <?php esc_html_e( 'Please tell us why you are deactivating the plugin?', 'my-server-info' ); ?>
                </h2>

                <div class="msi-survey-body">

                    <!-- Missing features -->
                    <div class="msi-survey-option" data-reason="Missing features">
                        <label class="msi-survey-label">
                            <input type="radio"
                                   name="msi_reason"
                                   id="msi-reason-features"
                                   value="Missing features" />
                            <?php esc_html_e( 'Missing features', 'my-server-info' ); ?>
                        </label>
                        <p class="msi-survey-hint">
                            <?php esc_html_e(
                                'Tell us which features you\'re missing and we\'ll do our best to add them in a future update. (optional)',
                                'my-server-info'
                            ); ?>
                        </p>
                        <textarea
                            id="msi-details-features"
                            class="msi-survey-details"
                            rows="3"
                            placeholder="<?php esc_attr_e(
                                'Example: more detailed server stats, email alerts, custom thresholds…',
                                'my-server-info'
                            ); ?>"
                        ></textarea>
                    </div>

                    <!-- Bugs / issues -->
                    <div class="msi-survey-option" data-reason="Bugs / issues">
                        <label class="msi-survey-label">
                            <input type="radio"
                                   name="msi_reason"
                                   id="msi-reason-bugs"
                                   value="Bugs / issues" />
                            <?php esc_html_e( 'Bugs / issues', 'my-server-info' ); ?>
                        </label>
                        <p class="msi-survey-hint">
                            <?php esc_html_e(
                                'Spotted a bug or something broken? Add a quick note so we can fix it in the next release. (optional)',
                                'my-server-info'
                            ); ?>
                        </p>
                        <textarea
                            id="msi-details-bugs"
                            class="msi-survey-details"
                            rows="3"
                            placeholder="<?php esc_attr_e(
                                'Example: wrong CPU usage, JS error in console, layout issues…',
                                'my-server-info'
                            ); ?>"
                        ></textarea>
                    </div>

                    <!-- No longer needed -->
                    <div class="msi-survey-option" data-reason="I don’t need the plugin anymore">
                        <label class="msi-survey-label">
                            <input type="radio"
                                   name="msi_reason"
                                   id="msi-reason-noneed"
                                   value="I don’t need the plugin anymore" />
                            <?php esc_html_e( 'I don’t need the plugin anymore', 'my-server-info' ); ?>
                        </label>
                        <p class="msi-survey-hint">
                            <?php esc_html_e(
                                'Let us know why you don’t need it anymore – it really helps us understand real-world usage. (optional)',
                                'my-server-info'
                            ); ?>
                        </p>
                        <textarea
                            id="msi-details-noneed"
                            class="msi-survey-details"
                            rows="3"
                            placeholder="<?php esc_attr_e(
                                'Example: switched hosting, using another plugin now, project closed…',
                                'my-server-info'
                            ); ?>"
                        ></textarea>
                    </div>

                    <p class="msi-survey-note">
                        <?php esc_html_e(
                            'Your feedback is anonymous and helps us improve the plugin.',
                            'my-server-info'
                        ); ?>
                    </p>
                    <p class="msi-survey-error" id="msi-error" style="display:none;"></p>
                </div>

                <div class="msi-survey-actions">
                    <button class="button button-primary" id="msi-submit"></button>
                    <button class="button" id="msi-skip"></button>
                    <button class="button button-link" id="msi-cancel"></button>
                </div>
            </div>
        </div>
        <?php
    }

    /* ================= AJAX → Web App ================= */

    public static function ajax_submit() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
        }

        check_ajax_referer( 'myserverinfo_survey_nonce', 'nonce' );

        $reason  = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';
        $details = isset( $_POST['details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['details'] ) ) : '';

        if ( '' === $reason ) {
            wp_send_json_error( [ 'message' => 'empty_reason' ], 400 );
        }

        $payload = [
            'reason'         => $reason,
            'details'        => $details,
            'plugin_version' => self::get_plugin_version(),
            'site_url'       => home_url( '/' ),
            'wp_version'     => get_bloginfo( 'version' ),
            'php_version'    => PHP_VERSION,
            'locale'         => get_locale(),
        ];

        $resp = wp_remote_post(
            self::WEBAPP_URL,
            [
                'timeout'     => 15,
                'redirection' => 3,
                'blocking'    => true,
                'headers'     => [
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                    'User-Agent'   => 'MyServerInfo/1.0.0 (WordPress)',
                    'Accept'       => 'application/json,text/plain,*/*',
                ],
                'body'        => $payload,
            ]
        );

        if ( is_wp_error( $resp ) ) {
            wp_send_json_error( [ 'message' => $resp->get_error_message() ], 500 );
        }

        $code = (int) wp_remote_retrieve_response_code( $resp );

        if ( $code >= 200 && $code < 400 ) {
            wp_send_json_success( [ 'ok' => true ] );
        }

        wp_send_json_error( [ 'message' => 'bad_status_' . $code ], 500 );
    }
}

}