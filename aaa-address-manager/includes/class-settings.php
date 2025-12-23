<?php
/**
 * Settings page for AAA Address Manager.
 *
 * This component registers a settings page under Settings → “AAA Address Manager”.
 * It stores a Google Geocode API key and a flag to enable Sunshine Autocomplete.
 * Other modules access these options via get_option('aaa_adbc_settings').
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_ADBC_Settings {
    /**
     * Hook into WordPress admin to register the settings page and settings.
     */
    public static function init() : void {
        add_action( 'admin_menu', [ __CLASS__, 'register_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        // Add a Settings link to the plugin row. This appears on both the bulk
        // verification and order backfill plugin entries.
        $hook = plugin_basename( dirname( __DIR__, 1 ) . '/aaa-address-manager.php' );
        add_filter( 'plugin_action_links_' . $hook, [ __CLASS__, 'add_settings_link' ] );
    }

    /**
     * Register our single option group and settings array. The settings array
     * contains two keys: google_api_key and use_sunshine.
     */
    public static function register_settings() : void {
        register_setting( 'aaa_adbc_settings_group', 'aaa_adbc_settings', [
            'type'              => 'array',
            'sanitize_callback' => [ __CLASS__, 'sanitize' ],
            'default'           => [ 'google_api_key' => '', 'use_sunshine' => '0' ],
        ] );
    }

    /**
     * Sanitize the settings before saving.
     *
     * @param array $options Raw option values.
     * @return array Sanitised option values.
     */
    public static function sanitize( $options ) : array {
        $opts = [];
        $opts['google_api_key'] = isset( $options['google_api_key'] ) ? sanitize_text_field( $options['google_api_key'] ) : '';
        $opts['use_sunshine']   = ! empty( $options['use_sunshine'] ) ? '1' : '0';
        return $opts;
    }

    /**
     * Register the options page under the Settings menu.
     */
    public static function register_page() : void {
        add_options_page(
            'AAA Address Manager Settings',
            'AAA Address Manager',
            'manage_options',
            'aaa-address-manager-settings',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Render the settings page. Shows input fields for the Google API key and
     * Sunshine integration toggle.
     */
    public static function render_page() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $opts = get_option( 'aaa_adbc_settings', [ 'google_api_key' => '', 'use_sunshine' => '0' ] );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'AAA Address Manager Settings', 'aaa-address-manager' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'aaa_adbc_settings_group' );
                ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="aaa_am_google_api_key"><?php esc_html_e( 'Google Geocode API Key', 'aaa-address-manager' ); ?></label></th>
                        <td>
                            <input type="text" class="regular-text" id="aaa_am_google_api_key" name="aaa_adbc_settings[google_api_key]" value="<?php echo esc_attr( $opts['google_api_key'] ); ?>" />
                            <p class="description">
                                <?php esc_html_e( 'Enter your Google Geocode API key. Leave blank to use a wp-config constant or a default.', 'aaa-address-manager' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Use Sunshine Autocomplete', 'aaa-address-manager' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="aaa_adbc_settings[use_sunshine]" value="1" <?php checked( '1', $opts['use_sunshine'] ); ?> />
                                <?php esc_html_e( 'Enable Sunshine Autocomplete integration', 'aaa-address-manager' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'If enabled, coordinates will be fetched via the aaa_adbc_geocode filter (e.g. provided by Sunshine).', 'aaa-address-manager' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Add a settings link on the plugin overview row.
     *
     * @param array $links Existing plugin action links.
     * @return array Modified links.
     */
    public static function add_settings_link( array $links ) : array {
        $url     = admin_url( 'options-general.php?page=aaa-address-manager-settings' );
        $links[] = '<a href="' . esc_url( $url ) . '">' . __( 'Settings' ) . '</a>';
        return $links;
    }
}