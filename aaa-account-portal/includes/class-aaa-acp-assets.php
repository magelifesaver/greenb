<?php
/**
 * Handles front‑end and admin asset registration for the AAA Account Portal.
 *
 * This class is intentionally concise and focused on loading styles and
 * scripts. It also injects colour variables based off of saved settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_ACP_Assets {

    /**
     * Hook into WordPress.
     */
    public static function init() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend' ] );
    }

    /**
     * Enqueue CSS/JS for the front‑end. Pulls colours from settings and
     * outputs inline CSS variables to allow easy theming.
     */
    public static function enqueue_frontend() {
        // Register and enqueue the stylesheet.
        $style_handle = 'aaa-acp';
        $style_path   = plugins_url( '../assets/css/aaa-acp.css', __FILE__ );
        wp_register_style( $style_handle, $style_path, [], AAA_ACP_VERSION );
        wp_enqueue_style( $style_handle );

        // Register and enqueue the script. jQuery is a dependency.
        $script_handle = 'aaa-acp';
        $script_path   = plugins_url( '../assets/js/aaa-acp.js', __FILE__ );
        wp_register_script( $script_handle, $script_path, [ 'jquery' ], AAA_ACP_VERSION, true );
        wp_enqueue_script( $script_handle );

        // Localise script with AJAX info.
        wp_localize_script( $script_handle, 'AAA_ACP', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'aaa_acp_nonce' ),
            'debug'    => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
        ] );

        // Inject dynamic CSS variables based off settings. If no settings exist,
        // defaults are provided here to match the Lokey Delivery colour palette.
        $settings = get_option( 'aaa_acp_settings', [] );
        // Sanitize base palette colours using WordPress helper. If invalid, fall back to default.
        $primary = isset( $settings['primary_color'] ) && sanitize_hex_color( $settings['primary_color'] ) ? sanitize_hex_color( $settings['primary_color'] ) : '#006400';
        $accent  = isset( $settings['accent_color'] )  && sanitize_hex_color( $settings['accent_color'] )  ? sanitize_hex_color( $settings['accent_color'] )  : '#fed700';
        $text    = isset( $settings['text_color'] )    && sanitize_hex_color( $settings['text_color'] )    ? sanitize_hex_color( $settings['text_color'] )    : '#000000';

        // Additional optional overrides (can be blank).  Accept 'transparent' and 4/8 digit hex codes in addition to standard 3/6.
        $bg = '';
        if ( isset( $settings['bg_color'] ) ) {
            $val = trim( (string) $settings['bg_color'] );
            if ( $val !== '' ) {
                $lower = strtolower( $val );
                if ( $lower === 'transparent' || preg_match( '/^#(?:[0-9a-fA-F]{4}|[0-9a-fA-F]{8})$/', $val ) ) {
                    $bg = $val;
                } else {
                    $tmp = sanitize_hex_color( $val );
                    $bg = $tmp ? $tmp : '';
                }
            }
        }
        $label = '';
        if ( isset( $settings['label_color'] ) ) {
            $val = trim( (string) $settings['label_color'] );
            if ( $val !== '' ) {
                $lower = strtolower( $val );
                if ( $lower === 'transparent' || preg_match( '/^#(?:[0-9a-fA-F]{4}|[0-9a-fA-F]{8})$/', $val ) ) {
                    $label = $val;
                } else {
                    $tmp = sanitize_hex_color( $val );
                    $label = $tmp ? $tmp : '';
                }
            }
        }
        $link = '';
        if ( isset( $settings['link_color'] ) ) {
            $val = trim( (string) $settings['link_color'] );
            if ( $val !== '' ) {
                $lower = strtolower( $val );
                if ( $lower === 'transparent' || preg_match( '/^#(?:[0-9a-fA-F]{4}|[0-9a-fA-F]{8})$/', $val ) ) {
                    $link = $val;
                } else {
                    $tmp = sanitize_hex_color( $val );
                    $link = $tmp ? $tmp : '';
                }
            }
        }
        $btn_bg = '';
        if ( isset( $settings['button_bg_color'] ) ) {
            $val = trim( (string) $settings['button_bg_color'] );
            if ( $val !== '' ) {
                $lower = strtolower( $val );
                if ( $lower === 'transparent' || preg_match( '/^#(?:[0-9a-fA-F]{4}|[0-9a-fA-F]{8})$/', $val ) ) {
                    $btn_bg = $val;
                } else {
                    $tmp = sanitize_hex_color( $val );
                    $btn_bg = $tmp ? $tmp : '';
                }
            }
        }
        $btn_text = '';
        if ( isset( $settings['button_text_color'] ) ) {
            $val = trim( (string) $settings['button_text_color'] );
            if ( $val !== '' ) {
                $lower = strtolower( $val );
                if ( $lower === 'transparent' || preg_match( '/^#(?:[0-9a-fA-F]{4}|[0-9a-fA-F]{8})$/', $val ) ) {
                    $btn_text = $val;
                } else {
                    $tmp = sanitize_hex_color( $val );
                    $btn_text = $tmp ? $tmp : '';
                }
            }
        }
        $btn_hover_bg = '';
        if ( isset( $settings['button_hover_bg_color'] ) ) {
            $val = trim( (string) $settings['button_hover_bg_color'] );
            if ( $val !== '' ) {
                $lower = strtolower( $val );
                if ( $lower === 'transparent' || preg_match( '/^#(?:[0-9a-fA-F]{4}|[0-9a-fA-F]{8})$/', $val ) ) {
                    $btn_hover_bg = $val;
                } else {
                    $tmp = sanitize_hex_color( $val );
                    $btn_hover_bg = $tmp ? $tmp : '';
                }
            }
        }
        $btn_hover_text = '';
        if ( isset( $settings['button_hover_text_color'] ) ) {
            $val = trim( (string) $settings['button_hover_text_color'] );
            if ( $val !== '' ) {
                $lower = strtolower( $val );
                if ( $lower === 'transparent' || preg_match( '/^#(?:[0-9a-fA-F]{4}|[0-9a-fA-F]{8})$/', $val ) ) {
                    $btn_hover_text = $val;
                } else {
                    $tmp = sanitize_hex_color( $val );
                    $btn_hover_text = $tmp ? $tmp : '';
                }
            }
        }

        $inline_css  = ':root {';
        $inline_css .= '--aaa-acp-primary:' . $primary . ';';
        $inline_css .= '--aaa-acp-accent:'  . $accent  . ';';
        $inline_css .= '--aaa-acp-text:'    . $text    . ';';
        // Append optional overrides only when a valid colour has been provided.
        if ( $bg )          { $inline_css .= '--aaa-acp-bg:'           . $bg           . ';'; }
        if ( $label )       { $inline_css .= '--aaa-acp-label:'        . $label        . ';'; }
        if ( $link )        { $inline_css .= '--aaa-acp-link:'         . $link         . ';'; }
        if ( $btn_bg )      { $inline_css .= '--aaa-acp-button-bg:'     . $btn_bg       . ';'; }
        if ( $btn_text )    { $inline_css .= '--aaa-acp-button-text:'   . $btn_text     . ';'; }
        if ( $btn_hover_bg )   { $inline_css .= '--aaa-acp-button-hover-bg:'   . $btn_hover_bg   . ';'; }
        if ( $btn_hover_text ) { $inline_css .= '--aaa-acp-button-hover-text:' . $btn_hover_text . ';'; }
        $inline_css .= '}';

        wp_add_inline_style( $style_handle, $inline_css );
    }
}