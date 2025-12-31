<?php
/**
 * Renders the [aaa_account_portal] shortcode. Shows login and lost password
 * forms, toggled via tabs. The forms submit over AJAX to endpoints defined in
 * AAA_ACP_Ajax and display inline messages without page refresh. When logged
 * in, a simple message is displayed instead.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_ACP_Shortcode {

    /**
     * Register the shortcode.
     */
    public static function init() {
        add_shortcode( 'aaa_account_portal', [ __CLASS__, 'render' ] );
    }

    /**
     * Render the shortcode output.
     *
     * @param array $atts Shortcode attributes. Supports `register_url` override.
     * @return string HTML output of the portal.
     */
    public static function render( $atts ) {
        // Load scripts/styles. They are registered by AAA_ACP_Assets::init().
        wp_enqueue_style( 'aaa-acp' );
        wp_enqueue_script( 'aaa-acp' );

        // If user is already logged in, display a simple message.
        if ( is_user_logged_in() ) {
            $message = '<div class="aaa-acp">You are already logged in.</div>';
            return $message;
        }

        // Merge default attributes with user provided ones. `register` controls
        // whether to display the register link (1 = show, 0 = hide).
        $defaults = [
            'register_url' => '',
            'register'     => '1',
            // Colour attributes for per‑instance overrides. Accept hex codes (3/4/6/8 digits) or 'transparent'.
            'primary_color'          => '',
            'accent_color'           => '',
            'text_color'             => '',
            'bg_color'               => '',
            'label_color'            => '',
            'link_color'             => '',
            'button_bg_color'        => '',
            'button_text_color'      => '',
            'button_hover_bg_color'  => '',
            'button_hover_text_color' => '',
        ];
        $atts     = shortcode_atts( $defaults, $atts, 'aaa_account_portal' );

        // Pull register URL from settings if not supplied.
        $settings = get_option( 'aaa_acp_settings', [] );
        $reg_url  = ! empty( $atts['register_url'] ) ? $atts['register_url'] : ( isset( $settings['register_url'] ) ? $settings['register_url'] : '/registration/' );
        $reg_url  = esc_url( $reg_url );

        // Determine if the register link should be shown. Accepts 0/1 or true/false.
        $show_register = true;
        if ( isset( $atts['register'] ) ) {
            $val = strtolower( (string) $atts['register'] );
            if ( in_array( $val, [ '0', 'false', 'no' ], true ) ) {
                $show_register = false;
            }
        }

        // Build inline CSS variables from colour attributes. Only accept allowed values.
        $style_vars = [];
        $attr_to_var = [
            'primary_color'          => '--aaa-acp-primary',
            'accent_color'           => '--aaa-acp-accent',
            'text_color'             => '--aaa-acp-text',
            'bg_color'               => '--aaa-acp-bg',
            'label_color'            => '--aaa-acp-label',
            'link_color'             => '--aaa-acp-link',
            'button_bg_color'        => '--aaa-acp-button-bg',
            'button_text_color'      => '--aaa-acp-button-text',
            'button_hover_bg_color'  => '--aaa-acp-button-hover-bg',
            'button_hover_text_color' => '--aaa-acp-button-hover-text',
        ];
        foreach ( $attr_to_var as $attr_name => $var_name ) {
            if ( ! empty( $atts[ $attr_name ] ) ) {
                $value = trim( (string) $atts[ $attr_name ] );
                // Accept 'transparent' and hex codes (with or without #) of length 3/4/6/8.
                $lower = strtolower( $value );
                if ( $lower === 'transparent' || preg_match( '/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
                    // Normalize hex to ensure leading # when necessary (except transparent).
                    if ( $lower !== 'transparent' && $value[0] !== '#' ) {
                        $value = '#' . $value;
                    }
                    $style_vars[] = $var_name . ':' . $value;
                }
            }
        }
        $style_attr = '';
        if ( ! empty( $style_vars ) ) {
            $style_attr = ' style="' . esc_attr( implode( '; ', $style_vars ) ) . '"';
        }

        ob_start();
        ?>
        <div class="aaa-acp"<?php echo $style_attr; ?>>
            <div class="aaa-acp-msg" style="display:none;"></div>
            <!-- Login form -->
            <form class="aaa-acp-form" data-form="login">
                <label>Email or Username</label>
                <input type="text" name="login" autocomplete="username" required />
                <label>Password</label>
                <input type="password" name="password" autocomplete="current-password" required />
                <label class="aaa-acp-remember">
                    <input type="checkbox" name="remember" value="1" /> Remember me
                </label>
                <a href="#" class="aaa-acp-forgot" data-forgot="lost">Forgot Password?</a>
                <button type="submit" class="aaa-acp-submit">Log In</button>
            </form>
            <!-- Lost password form -->
            <form class="aaa-acp-form" data-form="lost" style="display:none;">
                <label>Email or Username</label>
                <input type="text" name="login" autocomplete="username" required />
                <button type="submit" class="aaa-acp-submit">Get New Password</button>
                <a class="aaa-acp-back" href="#" data-back="login">Back to Login</a>
            </form>
            <?php if ( $show_register ) : ?>
            <div class="aaa-acp-register">
                <a href="<?php echo esc_url( $reg_url ); ?>">Register Here</a>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}