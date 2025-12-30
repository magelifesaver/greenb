<?php
/**
 * Plugin Name: Admin Page Notes
 * Plugin URI: https://wordpress.org/plugins/admin-page-notes/
 * Description: Gives administrators the ability to add page notes to certain pages that will prominently display special instructions for all users editing those pages.
 * Version: 2.1.8
 * Author: Anadar Professional Services
 * Author URI: https://www.anadarservices.com
 * Requires at least: 5.7
 * Tested up to: 6.6.2
 * Requires PHP: 7.4
 * Text Domain: gb-page-notes
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {

    require_once(plugin_dir_path(__FILE__) . 'admin/page-notes-admin.php');
    add_action('plugins_loaded', array('Page_Notes_Admin', 'get_instance'));
    add_filter('manage_posts_columns', 'add_notes_column', 5);
    add_filter('manage_pages_columns', 'add_notes_column', 5);

    function add_notes_column($cols)
    {
        $cols['admin_notes'] = __('Notes');
        return $cols;
    }

    add_action('manage_posts_custom_column', 'new_display_admin_notes_column', 5, 2);
    add_action('manage_pages_custom_column', 'new_display_admin_notes_column', 5, 2);

    function new_display_admin_notes_column($col, $id)
    {
        switch ($col) {
            case 'admin_notes':
                echo implode(' ', array_slice(explode(' ', get_post_meta($id, 'gb_admin_note', true)), 0, 15));;
                break;
        }
    }


    // This function will add custom links to the admin page
    function customize_links_on_plugin_list($links)
    {
        // Add your custom links as array elements
        $donations_link = '<a href="https://anadarservices.com/donations/">Donations</a>';
        $support_link = '<a href="https://wordpress.org/support/plugin/admin-page-notes/">Support</a>';

        // Add your links at the beginning of the array
        array_unshift($links, $donations_link, $support_link);

        return $links;
    }


    add_filter('plugin_action_links_admin-page-notes/admin-page-notes.php', 'customize_links_on_plugin_list');


    function admin_notes_register_settings() {
        register_setting('admin_notes_option_groups', 'display_notes_pages');
        register_setting('admin_notes_option_groups', 'display_notes_posts');
    }

    add_action('admin_init', 'admin_notes_register_settings');

    function admin_notes_settings_page() {
        add_options_page('Admin Notes Display', 'Admin Page Notes', 'manage_options', 'admin-notes', 'admin_notes_settings_page_html');
    }

    add_action('admin_menu', 'admin_notes_settings_page');

    function admin_notes_settings_page_html() {
        include plugin_dir_path(__FILE__).'admin/views/admin-settings.php';
    }

}
