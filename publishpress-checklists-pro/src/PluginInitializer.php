<?php

/**
 * @package     PublishPress\ChecklistsPro
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\ChecklistsPro;

use PublishPress\ChecklistsPro\HooksAbstract as PPCHPROHooksAbstract;
use WPPF\Plugin\PluginInitializerInterface;
use WPPF\WP\HooksAbstract as WPHooksAbstract;
use WPPF\WP\HooksHandlerInterface;
use WPPF\WP\PluginsHandlerInterface;
use WPPF\WP\TranslatorInterface;

#[\AllowDynamicProperties]
class PluginInitializer implements PluginInitializerInterface
{
    const FREE_PLUGIN_NAME = 'publishpress-checklists';

    /**
     * @var HooksHandlerInterface
     */
    private $hooksHandler;

    /**
     * @var PluginsHandlerInterface
     */
    private $pluginsHandler;

    /**
     * @var string
     */
    private $modulesDirPath;

    /**
     * @inheritDoc
     */
    public function __construct(
        HooksHandlerInterface $hooksHandler,
        PluginsHandlerInterface $pluginsHandler,
        TranslatorInterface $translator,
        $modulesDirPath
    ) {
        $this->hooksHandler   = $hooksHandler;
        $this->pluginsHandler = $pluginsHandler;
        $this->translator     = $translator;
        $this->modulesDirPath = $modulesDirPath;
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->setHooks();

        // Load StatusFilter module
        $this->loadStatusFilterModule();

        // Load Duplicate Checklist module
        $this->loadDuplicateChecklistModule();
    }

    /**
     * Load the Status Filter module
     */
    private function loadStatusFilterModule()
    {
        // Only load status filter if enabled in Pro settings
        $options = (array) get_option('publishpress_checklists_settings_options', []);
        if (isset($options['status_filter_enabled']) && $options['status_filter_enabled'] !== 'on') {
            return;
        }
        if (file_exists(__DIR__ . '/status-filter/StatusFilter.php')) {
            require_once __DIR__ . '/status-filter/StatusFilter.php';

            if (class_exists('PublishPress\\ChecklistsPro\\StatusFilter\\StatusFilter')) {
                new \PublishPress\ChecklistsPro\StatusFilter\StatusFilter();
            }
        }
    }

    /**
     * Load the Duplicate Checklist module
     */
    private function loadDuplicateChecklistModule()
    {
        // Check if duplicate checklist feature is enabled in settings
        $options = (array) get_option('publishpress_checklists_settings_options', []);
        $duplicateEnabled = isset($options['duplicate_checklist_enabled']) ? $options['duplicate_checklist_enabled'] === 'on' : true; // Default to enabled
        
        if (!$duplicateEnabled) {
            return;
        }

        if (file_exists(__DIR__ . '/duplicate-checklist/duplicate-checklist.php')) {
            require_once __DIR__ . '/duplicate-checklist/duplicate-checklist.php';

            if (class_exists('PPCH_Duplicate_Checklist')) {
                new \PPCH_Duplicate_Checklist();
            }
        }
    }

    private function setHooks()
    {
        $this->hooksHandler->addAction(WPHooksAbstract::ACTION_PLUGINS_LOADED, [$this, 'loadTextDomain']);
        $this->hooksHandler->addFilter(PPCHPROHooksAbstract::FILTER_MODULES_DIRS, [$this, 'filterModulesDirs']);
    }

    /**
     * Load the text domain.
     */
    public function loadTextDomain()
    {
        load_plugin_textdomain(
            'publishpress-checklists-pro',
            false,
            plugin_basename(PPCHPRO_PLUGIN_DIR_PATH) . '/languages/'
        );
    }

    /**
     * @param array $dirs
     *
     * @return array
     */
    public function filterModulesDirs($dirs)
    {
        $dirs['advanced-custom-fields'] = rtrim($this->modulesDirPath, DIRECTORY_SEPARATOR);
        $dirs['featured-image-size']    = rtrim($this->modulesDirPath, DIRECTORY_SEPARATOR);
        $dirs['image-count']            = rtrim($this->modulesDirPath, DIRECTORY_SEPARATOR);
        $dirs['prosettings']            = rtrim($this->modulesDirPath, DIRECTORY_SEPARATOR);
        $dirs['woocommerce']            = rtrim($this->modulesDirPath, DIRECTORY_SEPARATOR);
        $dirs['all-in-one-seo']         = rtrim($this->modulesDirPath, DIRECTORY_SEPARATOR);
        $dirs['rank-math']              = rtrim($this->modulesDirPath, DIRECTORY_SEPARATOR);
        $dirs['approved-by-user']       = rtrim($this->modulesDirPath, DIRECTORY_SEPARATOR);
        $dirs['no-heading-tags']        = rtrim($this->modulesDirPath, DIRECTORY_SEPARATOR);
        $dirs['accessibility']          = rtrim($this->modulesDirPath, DIRECTORY_SEPARATOR);
        $dirs['publish-time']           = rtrim($this->modulesDirPath, DIRECTORY_SEPARATOR);
        $dirs['audio-count']            = rtrim($this->modulesDirPath, DIRECTORY_SEPARATOR);
        $dirs['video-count']            = rtrim($this->modulesDirPath, DIRECTORY_SEPARATOR);
        $dirs['duplicate-checklist']    = rtrim(dirname($this->modulesDirPath), DIRECTORY_SEPARATOR) . '/src';

        return $dirs;
    }
}
