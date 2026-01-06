<?php
/**
 * Admin settings page for the AAA Remove Sorting Options plugin.
 *
 * This file defines a single options page under the Settings menu where
 * administrators can control which sort options are available on each page
 * type and choose a default ordering. It uses the WordPress Settings API
 * for data sanitisation and storage. The UI is intentionally simple:
 * enabling/disabling sorting, a list of checkboxes for each available option,
 * and a select box to choose the default.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Make sure helpers are available.
require_once __DIR__ . '/helpers.php';

if ( ! function_exists( 'aaa_rso_admin_menu' ) ) {
    /**
     * Adds the settings page to the WordPress admin menu. The page appears under
     * the Settings top‑level menu and is labelled “Catalog Sort (AAA)”. The
     * callback renders the settings form defined below.
     */
    function aaa_rso_admin_menu() {
        add_options_page(
            __( 'Catalog Sort (AAA)', 'aaa-rso' ),
            __( 'Catalog Sort (AAA)', 'aaa-rso' ),
            'manage_options',
            'aaa-remove-sorting-options',
            'aaa_rso_settings_page'
        );
    }
    add_action( 'admin_menu', 'aaa_rso_admin_menu' );
}

if ( ! function_exists( 'aaa_rso_register_settings' ) ) {
    /**
     * Registers our settings group and sanitisation callback. All fields in the
     * settings form post to this group. Sanitisation ensures only known sort
     * keys are stored and that a sensible default exists when options are
     * disabled.
     */
    function aaa_rso_register_settings() {
        register_setting( 'aaa_rso_settings_group', 'aaa_rso_settings', 'aaa_rso_sanitize_settings' );
    }
    add_action( 'admin_init', 'aaa_rso_register_settings' );
}

if ( ! function_exists( 'aaa_rso_sanitize_settings' ) ) {
    /**
     * Sanitises the submitted settings for all page types. Each context is
     * normalised so that missing values fall back to defaults and only
     * recognised options are stored. If no options are selected for a context,
     * the enabled flag is forced to false so that the dropdown is hidden.
     *
     * @param array<string,mixed> $input Raw input from the settings form.
     * @return array Cleaned settings array.
     */
    function aaa_rso_sanitize_settings( $input ) {
        $clean = array();
        $page_types = aaa_rso_get_page_types();
        $available_options = array_keys( aaa_rso_get_available_sort_keys() );
        foreach ( $page_types as $page => $label ) {
            $section = isset( $input[ $page ] ) ? $input[ $page ] : array();
            $clean[ $page ] = array(
                'enabled' => isset( $section['enabled'] ) ? (bool) $section['enabled'] : false,
                'default' => 'menu_order',
                'options' => array(),
            );
            // Collect valid options.
            if ( isset( $section['options'] ) && is_array( $section['options'] ) ) {
                foreach ( $section['options'] as $opt ) {
                    $opt = sanitize_text_field( $opt );
                    if ( in_array( $opt, $available_options, true ) ) {
                        $clean[ $page ]['options'][] = $opt;
                    }
                }
            }
            // Determine default. Use sanitised field if provided and valid.
            if ( isset( $section['default'] ) && in_array( $section['default'], $available_options, true ) ) {
                $clean[ $page ]['default'] = sanitize_text_field( $section['default'] );
            }
            // If no options selected, disable the dropdown.
            if ( empty( $clean[ $page ]['options'] ) ) {
                $clean[ $page ]['enabled'] = false;
            } else {
                // Ensure the default exists in the selected options. Otherwise pick the first.
                if ( ! in_array( $clean[ $page ]['default'], $clean[ $page ]['options'], true ) ) {
                    $clean[ $page ]['default'] = $clean[ $page ]['options'][0];
                }
            }
        }
        return $clean;
    }
}

if ( ! function_exists( 'aaa_rso_settings_page' ) ) {
    /**
     * Renders the settings page HTML. Displays a table with one row per page
     * type and columns for enabling the dropdown, choosing which options are
     * available and selecting the default sort. The form posts back to the
     * WordPress options API handled by register_setting().
     */
    function aaa_rso_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $page_types = aaa_rso_get_page_types();
        $available_options = aaa_rso_get_available_sort_keys();
        $settings = aaa_rso_get_settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Catalog Sorting Settings (AAA)', 'aaa-rso' ); ?></h1>
            <p><?php esc_html_e( 'Configure the WooCommerce catalog sorting dropdown for each page type. Unchecking all options hides the dropdown on that page. The default option will be used when a customer hasn’t selected a sorting preference.', 'aaa-rso' ); ?></p>
            <form method="post" action="options.php">
                <?php
                // Output nonce, option group and fields for proper save.
                settings_fields( 'aaa_rso_settings_group' );
                ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Page type', 'aaa-rso' ); ?></th>
                            <th><?php esc_html_e( 'Enable sorting', 'aaa-rso' ); ?></th>
                            <th><?php esc_html_e( 'Allowed options', 'aaa-rso' ); ?></th>
                            <th><?php esc_html_e( 'Default option', 'aaa-rso' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $page_types as $page => $label ) :
                            $conf    = isset( $settings[ $page ] ) ? $settings[ $page ] : array();
                            $enabled = ! empty( $conf['enabled'] );
                            $opts    = isset( $conf['options'] ) ? (array) $conf['options'] : array();
                            $default = isset( $conf['default'] ) ? $conf['default'] : 'menu_order';
                            ?>
                            <tr>
                                <th scope="row">
                                    <?php echo esc_html( $label ); ?>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="aaa_rso_settings[<?php echo esc_attr( $page ); ?>][enabled]" value="1" <?php checked( $enabled ); ?> />
                                        <?php esc_html_e( 'Enabled', 'aaa-rso' ); ?>
                                    </label>
                                </td>
                                <td>
                                    <?php foreach ( $available_options as $key => $text ) : ?>
                                        <label style="display:block; margin-bottom:2px;">
                                            <input type="checkbox" name="aaa_rso_settings[<?php echo esc_attr( $page ); ?>][options][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $opts, true ) ); ?> />
                                            <?php echo esc_html( $text ); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <select name="aaa_rso_settings[<?php echo esc_attr( $page ); ?>][default]">
                                        <?php foreach ( $available_options as $key => $text ) : ?>
                                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $default, $key ); ?>>
                                                <?php echo esc_html( $text ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong><?php esc_html_e( 'Tip:', 'aaa-rso' ); ?></strong> <?php esc_html_e( 'If a context is disabled or has no options selected, the sorting dropdown will not appear on that page.', 'aaa-rso' ); ?></p>
        </div>
        <?php
    }
}