<?php
/*
Plugin Name: WP All Import - Meta Box Add-On
Plugin URI: http://www.wpallimport.com/
Description: Import to Meta Box Meta Fields. Requires WP All Import & Meta Box.
Text Domain: wp_all_import_metabox_add_on
Version: 1.0.1
Requires PHP: 7.4
Author: Soflyy
*/

namespace Wpai\Metabox;

const PMMI_VERSION = '1.0.1';
define('PMMI_ROOT_DIR', str_replace('\\', '/', dirname(__FILE__)));
define('PMMI_ROOT_URL', rtrim(plugin_dir_url(__FILE__), '/'));

add_action('plugins_loaded', 'Wpai\Metabox\pmmi_load_plugin');
add_action('init', 'Wpai\Metabox\pmmi_load_plugin_textdomain', 10);
//add_action('after_plugin_row_'.plugin_basename(__FILE__), 'Wpai\Metabox\pmmi_plugins_page_notice', 10, 3);

function pmmi_load_plugin() {
    if (!class_exists('PMXI_Plugin')) {
        add_action('admin_notices', '\Wpai\Metabox\pmmi_display_missing_dependency_notice');
        return;
    }

	if (!class_exists('\Wpai\AddonAPI\PMXI_Addon_Base') || version_compare(PMXI_VERSION, '4.9.0', '<')) {
		add_action('admin_notices', '\Wpai\Metabox\pmmi_display_outdated_dependency_notice');
		return;
	}

    // Load dependencies
    require PMMI_ROOT_DIR . '/classes/autoload.php';

    // Register the addon
    new PMMI_Metabox_Addon();
}

function pmmi_load_plugin_textdomain() {
    load_plugin_textdomain('wp_all_import_metabox_add_on', false, dirname(plugin_basename(__FILE__)) . '/i18n/languages');
}

function pmmi_display_missing_dependency_notice() {
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_name = $plugin_data['Name'];
?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                // translators: %s: plugin name
                __('<b>%s</b>: WP All Import Pro must be installed: <a href="https://www.wpallimport.com/" target="_blank">https://www.wpallimport.com/</a>', 'wp_all_import_metabox_add_on'),
                $plugin_name
            );
            ?>
        </p>
    </div>
<?php
}

function pmmi_display_outdated_dependency_notice() {
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_name = $plugin_data['Name'];
?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                // translators: %s: plugin name
                __('<b>%s</b>: WP All Import must be updated to version 4.9.0 or higher.', 'wp_all_import_metabox_add_on'),
                $plugin_name
            );
            ?>
        </p>
    </div>
<?php
}

function pmmi_plugins_page_notice($plugin_file, $plugin_data, $status) {
	$message = "This add-on is currently in beta. We are working on adding support for a few of the lesser used fields. Future updates may break existing imports and exports that use the add-on, but we will try to avoid it.";
	echo '<tr class="plugin-update-tr active"><td colspan="4" class="plugin-update colspanchange"><div class="notice inline notice-warning notice-alt"><p>'. $message.'</p></div></td></tr>';
}
