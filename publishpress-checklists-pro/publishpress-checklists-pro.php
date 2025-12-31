<?php

/**
 * Plugin Name: PublishPress Checklists Pro
 * Plugin URI:  https://publishpress.com/
 * Version: 2.24.1
 * Description: With PublishPress Checklists, you can choose publishing requirements for your content.
 * Author:      PublishPress
 * Author URI:  https://publishpress.com
 * Text Domain: publishpress-checklists-pro
 * Domain Path: /languages
 * Requires at least: 5.5
 * Requires PHP: 7.2.5
 *
 * PublishPress Checklists Pro plugin.
 *
 * @link        https://publishpress.com/checklists/
 * @package     PublishPress\ChecklistsPro
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 *
 * @publishpress-checklists-pro
 */

use PublishPress\Checklists\Core\Autoloader;
use PublishPress\ChecklistsPro\PluginServiceProvider;
use WPPF\Plugin\DIContainer;
use WPPF\Plugin\ServicesAbstract;

global $wp_version;

$min_php_version = '7.2.5';
$min_wp_version  = '5.5';

// If the PHP or WP version is not compatible, terminate the plugin execution.
$invalid_php_version = version_compare(phpversion(), $min_php_version, '<');
$invalid_wp_version = version_compare($wp_version, $min_wp_version, '<');

if ($invalid_php_version || $invalid_wp_version) {
    return;
}

if (! defined('PPCHPRO_LOADED')) {
    if (! defined('PPCHPRO_LIB_VENDOR_PATH')) {
        define('PPCHPRO_LIB_VENDOR_PATH', __DIR__ . '/lib/vendor');
    }

    if (! defined('PUBLISHPRESS_CHECKLISTS_PRO_VENDOR_PATH')) {
        /**
         * @deprecated 2.9.0 Use PPCHPRO_LIB_VENDOR_PATH instead.
         */
        define('PUBLISHPRESS_CHECKLISTS_PRO_VENDOR_PATH', PPCHPRO_LIB_VENDOR_PATH);
    }

    $instanceProtectionIncPath = PPCHPRO_LIB_VENDOR_PATH . '/publishpress/instance-protection/include.php';
    if (is_file($instanceProtectionIncPath) && is_readable($instanceProtectionIncPath)) {
        require_once $instanceProtectionIncPath;
    }

    if (class_exists('PublishPressInstanceProtection\\Config')) {
        $pluginCheckerConfig = new PublishPressInstanceProtection\Config();
        $pluginCheckerConfig->pluginSlug = 'publishpress-checklists-pro';
        $pluginCheckerConfig->pluginName = 'PublishPress Checklists Pro';
        $pluginCheckerConfig->isProPlugin = true;
        $pluginCheckerConfig->freePluginName = 'PublishPress Checklists';

        $pluginChecker = new PublishPressInstanceProtection\InstanceChecker($pluginCheckerConfig);
    }

    $autoloadFilePath = PPCHPRO_LIB_VENDOR_PATH . '/autoload.php';
    if (
        ! class_exists('ComposerAutoloaderInitPPChecklistsPro')
        && is_file($autoloadFilePath)
        && is_readable($autoloadFilePath)
    ) {
        require_once $autoloadFilePath;
    }

    if (! defined('PUBLISHPRESS_CHECKLISTS_SKIP_VERSION_NOTICES')) {
        define('PUBLISHPRESS_CHECKLISTS_SKIP_VERSION_NOTICES', true);
    }

    if (! class_exists('PublishPress\\Checklists\\Core\\Autoloader')) {
        require_once PPCHPRO_LIB_VENDOR_PATH . '/publishpress/publishpress-checklists/core/Autoloader.php';
    }

    // Initialize the free plugin.
    require_once PPCHPRO_LIB_VENDOR_PATH . '/publishpress/publishpress-checklists/publishpress-checklists.php';

    // Activation
    register_activation_hook(__FILE__, function () {
        $role = get_role('administrator');
        if ($role && !$role->has_cap('manage_checklists')) {
            $role->add_cap('manage_checklists');
        }

        foreach (wp_roles()->roles as $role_name => $role_info) {
            $role = get_role($role_name);
            if ($role && $role->has_cap('manage_options') && !$role->has_cap('manage_checklists')) {
                $role->add_cap('manage_checklists');
            }
        }

        update_option('ppch_activated', true);
    });

    add_action('plugins_loaded', function () {
        if (!defined('PPCHPRO_VERSION')) {
            define('PPCHPRO_VERSION', '2.24.1');

            define('PPCHPRO_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));
            define('PPCHPRO_ITEM_ID', 6465);

            if (! defined('WPPF_FRAMEWORK_LOADED')) {
                require_once __DIR__ . '/lib/wppf/includes.php';
            }

            Autoloader::register();
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\FeaturedImageSize\\', __DIR__ . '/src/modules/featured-image-size/lib/');
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\ImageCount\\', __DIR__ . '/src/modules/image-count/lib/');
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\AudioCount\\', __DIR__ . '/src/modules/audio-count/lib/');
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\VideoCount\\', __DIR__ . '/src/modules/video-count/lib/');
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\WooCommerce\\', __DIR__ . '/src/modules/woocommerce/lib/');
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\AllInOneSeo\\', __DIR__ . '/src/modules/all-in-one-seo/lib/');
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\AdvancedCustomFields\\', __DIR__ . '/src/modules/advanced-custom-fields/lib/');
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\RankMath\\', __DIR__ . '/src/modules/rank-math/lib/');
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\ApprovedByUser\\', __DIR__ . '/src/modules/approved-by-user/lib/');
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\NoHeadingTags\\', __DIR__ . '/src/modules/no-heading-tags/lib/');
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\Accessibility\\', __DIR__ . '/src/modules/accessibility/lib/');
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\PublishTime\\', __DIR__ . '/src/modules/publish-time/lib/');
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\DuplicateChecklist\\', __DIR__ . '/src/duplicate-checklist/lib/');
            Autoloader::addNamespace('PublishPress\\ChecklistsPro\\', __DIR__ . '/src/');

            $container = new DIContainer();
            $container->register(new PluginServiceProvider());

            $pluginInitializer = $container->get(ServicesAbstract::PLUGIN_INITIALIZER);
            $pluginInitializer->init();

            define('PPCHPRO_LOADED', 1);
        }
    }, -9);
}
