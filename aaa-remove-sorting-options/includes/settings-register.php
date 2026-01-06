<?php
/**
 * Settings registration and sanitisation callbacks for AAA Remove Sorting Options.
 *
 * This file registers the plugin’s settings with WordPress and defines the
 * sanitisation routine that cleans and normalises the submitted values. By
 * separating these concerns into their own file we keep each component under
 * 150 lines as per the user’s architectural preferences.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/helpers.php';

/**
 * Register the settings group and sanitisation callback. All fields in the
 * settings form post to the option named aaa_rso_settings. The sanitisation
 * callback ensures that only valid sort keys are saved and that missing
 * selections fall back to safe defaults.
 */
function aaa_rso_register_settings() {
    register_setting( 'aaa_rso_settings_group', 'aaa_rso_settings', 'aaa_rso_sanitize_settings' );
}
add_action( 'admin_init', 'aaa_rso_register_settings' );

/**
 * Sanitise the settings for each page type. If no options are selected the
 * dropdown is disabled. Otherwise the default value must be present among
 * selected options; if not, the first option is chosen. All keys are
 * sanitised and unrecognised values are discarded.
 *
 * @param array<string,mixed> $input Raw submitted options.
 * @return array Cleaned settings.
 */
function aaa_rso_sanitize_settings( $input ) {
    $clean            = array();
    $page_types       = aaa_rso_get_page_types();
    $available_keys   = array_keys( aaa_rso_get_available_sort_keys() );
    foreach ( $page_types as $page => $label ) {
        $section = isset( $input[ $page ] ) ? $input[ $page ] : array();
        // Start with defaults off.
        $clean[ $page ] = array(
            'enabled' => isset( $section['enabled'] ) ? (bool) $section['enabled'] : false,
            'default' => 'menu_order',
            'options' => array(),
        );
        // Parse enabled options.
        if ( isset( $section['options'] ) && is_array( $section['options'] ) ) {
            foreach ( $section['options'] as $opt ) {
                $opt = sanitize_text_field( $opt );
                if ( in_array( $opt, $available_keys, true ) ) {
                    $clean[ $page ]['options'][] = $opt;
                }
            }
        }
        // Parse the default sort if provided.
        if ( isset( $section['default'] ) && in_array( $section['default'], $available_keys, true ) ) {
            $clean[ $page ]['default'] = sanitize_text_field( $section['default'] );
        }
        // Disable dropdown if no options selected.
        if ( empty( $clean[ $page ]['options'] ) ) {
            $clean[ $page ]['enabled'] = false;
        } else {
            // Ensure default lies within allowed options.
            if ( ! in_array( $clean[ $page ]['default'], $clean[ $page ]['options'], true ) ) {
                $clean[ $page ]['default'] = $clean[ $page ]['options'][0];
            }
        }
    }
    return $clean;
}