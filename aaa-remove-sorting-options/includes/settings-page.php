<?php
/**
 * Admin page markup for AAA Remove Sorting Options.
 *
 * This file contains the menu registration and the function that outputs the
 * settings page HTML. It reads existing settings via the helper functions
 * and builds a table of controls for each page type. Splitting this into
 * its own file helps keep each script concise and easy to maintain.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/helpers.php';

/**
 * Add the Catalog Sort settings page under the Settings menu. This callback
 * registers a new submenu page pointing to our render function.
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

/**
 * Render the settings page. The form posts to the WordPress options API and
 * uses the registered settings group to handle submission. A table lays out
 * each context with toggles for enabling/disabling, checkboxes for allowed
 * sort keys and a select element for choosing the default key.
 */
function aaa_rso_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $page_types       = aaa_rso_get_page_types();
    $available_keys   = aaa_rso_get_available_sort_keys();
    $settings         = aaa_rso_get_settings();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Catalog Sorting Settings (AAA)', 'aaa-rso' ); ?></h1>
        <p><?php esc_html_e( 'Configure the WooCommerce catalog sorting dropdown for each page type.', 'aaa-rso' ); ?></p>
        <form method="post" action="options.php">
            <?php
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
                                <?php foreach ( $available_keys as $key => $text ) : ?>
                                    <label style="display:block; margin-bottom:2px;">
                                        <input type="checkbox" name="aaa_rso_settings[<?php echo esc_attr( $page ); ?>][options][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $opts, true ) ); ?> />
                                        <?php echo esc_html( $text ); ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <select name="aaa_rso_settings[<?php echo esc_attr( $page ); ?>][default]">
                                    <?php foreach ( $available_keys as $key => $text ) : ?>
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
        <p><strong><?php esc_html_e( 'Note:', 'aaa-rso' ); ?></strong> <?php esc_html_e( 'If a page type is disabled or has no options selected, the sorting dropdown will not be shown on that page.', 'aaa-rso' ); ?></p>
    </div>
    <?php
}