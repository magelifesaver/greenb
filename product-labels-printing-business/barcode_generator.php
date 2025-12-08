<?php
/*
Plugin Name: Barcode Label Printing for WooCommerce and others plugins - (business)
Description: Create and Print barcodes on label sheets using a printer. Best tool to make inventory of your stock.
Text Domain: wpbcu-barcode-generator
Version: 3.4.12
Author: UkrSolution
Plugin URI: https://www.ukrsolution.com/Joomla/A4-BarCode-Generator-For-Wordpress
Author URI: http://www.ukrsolution.com
License: GPL2
WC requires at least: 4.0.0
-WC tested up to: 10.1.*
 */


if (!defined('ABSPATH')) {
    exit;
}


if (function_exists("wp_get_upload_dir")) {
    try {
        $dir = wp_get_upload_dir();
        $pathUpload = $dir["basedir"]  . '/product-labels-printing/';
        $pathApi = $dir["basedir"]  . '/product-labels-printing/settings.php';

        if (!file_exists($pathUpload)) {
            wp_mkdir_p($pathUpload);
        }

        if (!file_exists($pathApi) && function_exists("file_put_contents")) {
            @file_put_contents($pathApi, "<?php\n\$BarcodePrinting_Settings = array(\n\t// Load plugin and JS files on the frontend\n\t\"load_on_frontend\" => false,\n\t// Load  on specified pages, if array empty - will be loaded on all front-end pages\n\t\"load_on_pages_reg_exp\" => array()\n);");
        }

        if (file_exists($pathApi)) {
            include_once($pathApi);
        }
    } catch (\Throwable $th) {
    }
}


define('A4B_PLUGIN_BASE_NAME', plugin_basename(__FILE__));

global $BarcodePrinting_Settings;

require_once plugin_dir_path(__FILE__) . 'constants.php';

require_once __DIR__ . '/lib/autoload.php';

$load_on_frontend = false;
$load_on_pages_reg_exp = array();

if (isset($BarcodePrinting_Settings) && is_array($BarcodePrinting_Settings) && isset($BarcodePrinting_Settings['load_on_frontend'])) {
    $load_on_frontend = $BarcodePrinting_Settings['load_on_frontend'];
    $load_on_pages_reg_exp = isset($BarcodePrinting_Settings['load_on_pages_reg_exp']) ? $BarcodePrinting_Settings['load_on_pages_reg_exp'] : array();
}


$load_on_url = false;

if (preg_match('/\/barcodes-print\/(.*?)\/(.*?)\/(.*?).svg(.*?)?$/', $_SERVER["REQUEST_URI"])) {
    $load_on_url = true;
}

if (preg_match('/\/barcode-scanner-front/', $_SERVER["REQUEST_URI"])) {
    $load_on_url = true;
}

if ($load_on_frontend && count($load_on_pages_reg_exp) == 0) {
}
else if ($load_on_frontend) {
    $load_on_frontend = false;

    foreach ($load_on_pages_reg_exp as $regexp) {
        if (preg_match($regexp, $_SERVER["REQUEST_URI"])) {
            $load_on_frontend = true;
            break;
        }
    }
}





$ProductLabelsPrintingFrontend = new UkrSolution\ProductLabelsPrinting\Frontend();

if (is_admin() || $load_on_frontend == true || $load_on_url == true) {

    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    if (!is_plugin_active(plugin_basename(__FILE__))) {
        $activePlugins = is_multisite() ? get_site_option('active_sitewide_plugins') : get_option('active_plugins');
        foreach ($activePlugins as $sitewideActivePlugin => $activePlugin) {
            if ($activePlugin === "a4-barcode-generator/barcode_generator.php") {
                @deactivate_plugins($activePlugin);
                @activate_plugin(plugin_basename(__FILE__));
                return;
            }

            if (false !== strpos($activePlugin, 'a4-barcode-generator-print') || false !== strpos($sitewideActivePlugin, 'a4-barcode-generator')) {
                die(esc_html__('Please deactivate any other version of the plugin before you can activate this one.', 'wpbcu-barcode-generator'));
            }
        }
    }

    if (version_compare(phpversion(), '5.6.0', '<')) {
        $errorMsg = '<span>' . 'Barcode Printer - The minimum PHP version required for this plugin is 5.6.0.  Please update PHP version.' . '</span>';

        if (isset($_GET['action']) && 'error_scrape' == sanitize_key($_GET['action'])) {
            die('<span>' . esc_html($errorMsg) . '</span>');
        } else {
            trigger_error(esc_html($errorMsg), E_USER_ERROR);
        }
    }

    add_action('init', 'a4b_check_print_page');
    if (!function_exists('a4b_check_print_page')) {
        function a4b_check_print_page()
        {
            if (isset($_GET['page']) && 'wpbcu-barcode-generator-print' == sanitize_key($_GET['page'])) {
                UkrSolution\ProductLabelsPrinting\PrintPage::displayPrint();
            }
        }
    }

    require_once __DIR__ . '/class/functions.php';

    try {
        if (is_admin()) {
            UkrSolution\ProductLabelsPrinting\Database::checkTables();
        }
    } catch (\Throwable $th) {
    }

    register_activation_hook(__FILE__, function ($network_wide) {
        UkrSolution\ProductLabelsPrinting\Database::setupTables($network_wide);
    });

    add_action('wpmu_new_blog', function ($blog_id, $user_id, $domain, $path, $site_id, $meta) {
        $A4B_PLUGIN_BASE_NAME = A4B_PLUGIN_BASE_NAME;
        if (is_plugin_active_for_network($A4B_PLUGIN_BASE_NAME)) {
            switch_to_blog($blog_id);
            UkrSolution\ProductLabelsPrinting\Database::createTables();
            restore_current_blog();
        }
    }, 10, 6);

    add_action('plugins_loaded', function () {

        $pluginName = UkrSolution\ProductLabelsPrinting\Helpers\Variables::$A4B_PLUGIN_BASE_NAME;

        $lastVersion = get_option("active-print-barcodes-version", "");
        if ($lastVersion !== "3.4.12") {
            UkrSolution\ProductLabelsPrinting\Database::createTables();
            update_option("active-print-barcodes-version", "3.4.12");
        }
    });

    add_action('init', function () {
        $pluginRelPath = basename(dirname(__FILE__)) . '/languages';
        load_plugin_textdomain('wpbcu-barcode-generator', false, $pluginRelPath);
    });

    add_action('admin_notices', function () {
        uswbg_a4bShowNotices();
    });

    add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {

        $url = get_admin_url() . "admin.php?page=wpbcu-barcode-settings";
        $settings_link = '<a href="' . $url . '">' . __('Settings', 'wpbcu-barcode-generator') . '</a>';
        $links[] = $settings_link;

        $url = get_admin_url() . "admin.php?page=wpbcu-barcode-templates-edit";
        $settings_link = '<a href="' . $url . '">' . __('Label templates', 'wpbcu-barcode-generator') . '</a>';
        $links[] = $settings_link;



        return $links;
    });


    add_action('admin_init', function () {
        if (defined('DOING_AJAX') && isset($_POST['action']) && false !== strpos(sanitize_key($_POST['action']), 'a4barcode_')) {
            if (version_compare(phpversion(), '7.1', '>=')) {
                ini_set('precision', 17);
                ini_set('serialize_precision', -1);
            }
        }
    }, 10, 2);

    add_action('admin_init', 'uswbg_a4bOldPostInitialization');


    $ProductLabelsPrintingCore = new UkrSolution\ProductLabelsPrinting\Core();

    if (function_exists("wp_get_upload_dir")) {
        try {
            $dir = wp_get_upload_dir();
            $pathUpload = $dir["basedir"]  . '/product-labels-printing/';
            $pathApi = $dir["basedir"]  . '/product-labels-printing/api.php';
            $pathExample = $dir["basedir"]  . '/product-labels-printing/api-example.php';

            if (!file_exists($pathUpload)) {
                wp_mkdir_p($pathUpload);
            }

            if (file_exists($pathApi)) {
                include_once($pathApi);
            }
            else if (function_exists("copy") && file_exists(__DIR__ . "/api-example.php") && !file_exists($pathExample)) {
                copy(__DIR__ . "/api-example.php", $pathExample);
            }
        } catch (\Throwable $th) {
        }
    }


    if (file_exists(get_stylesheet_directory() . '/product-labels-printing/api.php')) {
        try {
            include_once(get_stylesheet_directory() . '/product-labels-printing/api.php');
        } catch (\Throwable $th) {
        }
    }
}
