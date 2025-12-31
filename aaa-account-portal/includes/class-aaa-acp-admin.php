<?php
/**
 * Defines the settings page for the AAA Account Portal. Allows administrators
 * to customise the registration URL and colours used in the portal. This
 * class encapsulates all admin‑related logic to keep files lean and organised.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_ACP_Admin {

    /**
     * Register admin hooks.
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    /**
     * Add a submenu page under Settings.
     */
    public static function add_menu() {
        add_options_page(
            'Account Portal Settings',
            'Account Portal',
            'manage_options',
            'aaa-acp-settings',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Register settings for this plugin. Options are stored as a single array.
     */
    public static function register_settings() {
        register_setting( 'aaa_acp_settings_group', 'aaa_acp_settings', [ __CLASS__, 'sanitize' ] );
    }

    /**
     * Sanitize settings input. Ensures colours are valid hex values and the
     * register URL is safe. Provides fallback defaults for missing fields.
     *
     * @param array $input Raw input from form.
     * @return array Sanitised values.
     */
    public static function sanitize( $input ) {
        $options   = get_option( 'aaa_acp_settings', [] );
        $options   = is_array( $options ) ? $options : [];
        // Define default values for all supported keys. New keys can be left empty to indicate fallback.
        $defaults  = [
            'register_url'  => '/registration/',
            'primary_color' => '#006400',
            'accent_color'  => '#fed700',
            'text_color'    => '#000000',
            'bg_color'             => '',
            'label_color'          => '',
            'link_color'           => '',
            'button_bg_color'      => '',
            'button_text_color'    => '',
            'button_hover_bg_color' => '',
            'button_hover_text_color' => '',
        ];
        // Merge defaults with existing so missing values get defaulted.
        $options = array_merge( $defaults, $options );

        if ( isset( $input['register_url'] ) ) {
            $options['register_url'] = esc_url_raw( $input['register_url'] );
        }
        // List of all colour settings to validate. If a value is blank, reset to empty string for fallback.
        $colour_keys = [
            'primary_color',
            'accent_color',
            'text_color',
            'bg_color',
            'label_color',
            'link_color',
            'button_bg_color',
            'button_text_color',
            'button_hover_bg_color',
            'button_hover_text_color',
        ];
        foreach ( $colour_keys as $key ) {
            if ( array_key_exists( $key, $input ) ) {
                $colour = trim( $input[ $key ] );
                if ( $colour === '' ) {
                    // Allow clearing the colour to fall back to defaults.
                    $options[ $key ] = '';
                } else {
                    $colour_lower = strtolower( $colour );
                    // Accept 'transparent' as a valid value (for backgrounds)
                    if ( $colour_lower === 'transparent' ) {
                        $options[ $key ] = 'transparent';
                    // Accept 3, 4, 6 or 8 digit hex codes with optional leading #
                    } elseif ( preg_match( '/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $colour ) ) {
                        $options[ $key ] = ( $colour[0] === '#' ? $colour : '#' . $colour );
                    }
                    // Otherwise ignore invalid values and retain previous setting/fallback
                }
            }
        }
        return $options;
    }

    /**
     * Render the settings page.
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $options = get_option( 'aaa_acp_settings', [] );
        $defaults = [
            'register_url'  => '/registration/',
            'primary_color' => '#006400',
            'accent_color'  => '#fed700',
            'text_color'    => '#000000',
            'bg_color'             => '',
            'label_color'          => '',
            'link_color'           => '',
            'button_bg_color'      => '',
            'button_text_color'    => '',
            'button_hover_bg_color' => '',
            'button_hover_text_color' => '',
        ];
        $options = array_merge( $defaults, (array) $options );
        ?>
        <div class="wrap">
            <h1>Account Portal Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'aaa_acp_settings_group' );
                do_settings_sections( 'aaa_acp_settings_group' );
                ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="aaa_acp_register_url">Registration URL</label></th>
                        <td><input name="aaa_acp_settings[register_url]" id="aaa_acp_register_url" type="text" class="regular-text" value="<?php echo esc_attr( $options['register_url'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="aaa_acp_primary_color">Primary Colour</label></th>
                        <td><input name="aaa_acp_settings[primary_color]" id="aaa_acp_primary_color" type="text" class="regular-text" value="<?php echo esc_attr( $options['primary_color'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="aaa_acp_accent_color">Accent Colour</label></th>
                        <td><input name="aaa_acp_settings[accent_color]" id="aaa_acp_accent_color" type="text" class="regular-text" value="<?php echo esc_attr( $options['accent_color'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="aaa_acp_text_color">Text Colour</label></th>
                        <td><input name="aaa_acp_settings[text_color]" id="aaa_acp_text_color" type="text" class="regular-text" value="<?php echo esc_attr( $options['text_color'] ); ?>" /></td>
                    </tr>
                <tr>
                    <th scope="row"><label for="aaa_acp_bg_color">Form Background</label></th>
                    <td><input name="aaa_acp_settings[bg_color]" id="aaa_acp_bg_color" type="text" class="regular-text" value="<?php echo esc_attr( $options['bg_color'] ); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="aaa_acp_label_color">Label Text Colour</label></th>
                    <td><input name="aaa_acp_settings[label_color]" id="aaa_acp_label_color" type="text" class="regular-text" value="<?php echo esc_attr( $options['label_color'] ); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="aaa_acp_link_color">Link &amp; Remember Text Colour</label></th>
                    <td><input name="aaa_acp_settings[link_color]" id="aaa_acp_link_color" type="text" class="regular-text" value="<?php echo esc_attr( $options['link_color'] ); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="aaa_acp_button_bg_color">Button Background Colour</label></th>
                    <td><input name="aaa_acp_settings[button_bg_color]" id="aaa_acp_button_bg_color" type="text" class="regular-text" value="<?php echo esc_attr( $options['button_bg_color'] ); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="aaa_acp_button_text_color">Button Text Colour</label></th>
                    <td><input name="aaa_acp_settings[button_text_color]" id="aaa_acp_button_text_color" type="text" class="regular-text" value="<?php echo esc_attr( $options['button_text_color'] ); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="aaa_acp_button_hover_bg_color">Button Hover Background</label></th>
                    <td><input name="aaa_acp_settings[button_hover_bg_color]" id="aaa_acp_button_hover_bg_color" type="text" class="regular-text" value="<?php echo esc_attr( $options['button_hover_bg_color'] ); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="aaa_acp_button_hover_text_color">Button Hover Text</label></th>
                    <td><input name="aaa_acp_settings[button_hover_text_color]" id="aaa_acp_button_hover_text_color" type="text" class="regular-text" value="<?php echo esc_attr( $options['button_hover_text_color'] ); ?>" /></td>
                </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Enter HEX values (with or without <code>#</code>) for colours.  Primary, accent and text colours still control the core palette for tabs and the default button.  Use the additional fields above to override specific elements:
            <br>– <strong>Form Background</strong> changes the login form panel background.
            <br>– <strong>Label Text Colour</strong> applies to the “Email” and “Password” labels.
            <br>– <strong>Link &amp; Remember Text Colour</strong> colours the “Remember me”, “Forgot Password?” and “Back to Login” links.
            <br>– <strong>Button Background/Text</strong> set the normal button colours; hover fields apply on mouse over.  Leave any field blank to fall back to your theme defaults or the core palette.</p>
        </div>
        <?php
    }
}