<?php
/*
Plugin Name: WP All Export - Meta Box Add-On
Plugin URI: https://www.wpallimport.com/export-wordpress
Description: Export data from Meta Box Meta Fields.
Text Domain: wp_all_export_metabox_add_on
Version: 1.0.0
Requires PHP: 7.4
Author: Soflyy
*/

namespace Wpae\Metabox;

const PMME_VERSION = '1.0.0';
define('PMME_ROOT_DIR', str_replace('\\', '/', dirname(__FILE__)));
define('PMME_ROOT_URL', rtrim(plugin_dir_url(__FILE__), '/'));

add_action('plugins_loaded', 'Wpae\Metabox\pmme_load_plugin');
add_action('init', 'Wpae\Metabox\pmme_load_plugin_textdomain', 10);
//add_action('after_plugin_row_'.plugin_basename(__FILE__), 'Wpae\Metabox\pmme_plugins_page_notice', 10, 3);

function pmme_load_plugin() {
    if (!class_exists('PMXE_Plugin')) {
        add_action('admin_notices', '\Wpae\Metabox\pmme_display_missing_dependency_notice');
        return;
    }

    if (!class_exists('\Wpae\AddonAPI\PMXE_Addon_Base')) {
        add_action('admin_notices', '\Wpae\Metabox\pmme_display_outdated_dependency_notice');
        return;
    }

    // Load dependencies
    require PMME_ROOT_DIR . '/classes/autoload.php';

    // Register the addon
    new PMME_Metabox_Addon();
}

function pmme_load_plugin_textdomain() {
    load_plugin_textdomain('wp_all_export_metabox_add_on', false, dirname(plugin_basename(__FILE__)) . '/i18n/languages');
}

function pmme_display_missing_dependency_notice() {
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_name = $plugin_data['Name'];
?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                // translators: %s: plugin name
                __('<b>%s</b>: WP All Export must be installed. Free edition of WP All Export at <a href="http://wordpress.org/plugins/wp-all-export/" target="_blank">http://wordpress.org/plugins/wp-all-export/</a> and the paid edition at <a href="http://www.wpallimport.com/">http://www.wpallimport.com/</a>', 'wp_all_export_metabox_add_on'),
                $plugin_name
            );
            ?>
        </p>
    </div>
<?php
}

function pmme_display_outdated_dependency_notice() {
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_name = $plugin_data['Name'];
?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                // translators: %s: plugin name
                __('<b>%s</b>: WP All Export Pro must be updated to version 1.9.1 or higher.', 'wp_all_export_metabox_add_on'),
                $plugin_name
            );
            ?>
        </p>
    </div>
<?php
}

function pmme_plugins_page_notice($plugin_file, $plugin_data, $status) {
	$message = "This add-on is currently in beta. We are working on adding support for a few of the lesser used fields. Future updates may break existing imports and exports that use the add-on, but we will try to avoid it.";
	echo '<tr class="plugin-update-tr active"><td colspan="4" class="plugin-update colspanchange"><div class="notice inline notice-warning notice-alt"><p>'. $message.'</p></div></td></tr>';
}
