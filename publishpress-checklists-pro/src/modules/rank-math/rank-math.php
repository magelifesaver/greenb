<?php

use Alledia\EDD_SL_Plugin_Updater;
use PublishPress\Checklists\Core\Legacy\LegacyPlugin;
use PublishPress\Checklists\Core\Legacy\Module;
use PublishPress\Checklists\Core\Utils\FieldsTabs;
use PublishPress\ChecklistsPro\Factory;
use PublishPress\ChecklistsPro\HooksAbstract as PPCHPROHooksAbstract;
use PublishPress\ChecklistsPro\RankMath\Requirement\RankMathScore;
use WPPF\Plugin\ServicesAbstract;
use WPPF\WP\HooksAbstract as WPHooksAbstract;
use WPPF\WP\HooksHandlerInterface;
use PublishPress\Checklists\Core\Legacy\Util;

/**
 * @package     PublishPress\ChecklistsPro
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

/**
 * Class PPCH_Rank_Math
 *
 * @todo Refactor this module and all the modules system to use DI.
 */
#[\AllowDynamicProperties]
class PPCH_Rank_Math extends Module
{
    const METADATA_TAXONOMY = 'pp_rank_math_meta';

    const METADATA_POSTMETA_KEY = "_pp_rank_math_meta";

    const SETTINGS_SLUG = 'pp-rank-math-prosettings';

    const POST_META_PREFIX = 'pp_rank_math_custom_item_';

    public $module_name = 'rank-math'; 

    /**
     * Instance for the module
     *
     * @var stdClass
     */
    public $module;

    /**
     * List of requirements, filled with instances of requirement classes.
     * The list is indexed by post types.
     *
     * @var array
     */
    private $requirements = [];

    /**
     * List of post types which supports checklist
     *
     * @var array
     */
    private $post_types = [];

    /**
     * @var LegacyPlugin
     */
    private $legacyPlugin;

    /**
     * @var HooksHandlerInterface
     */
    private $hooksHandler;

    /**
     * @var string
     */
    private $pluginFile;

    /**
     * @var string
     */
    private $pluginVersion;

    /**
     * Construct the PPCH_Rank_Math class
     *
     * @todo: Fix to inject the dependencies in the constructor as params.
     */
    public function __construct()
    {
        $container = Factory::getContainer();

        $this->legacyPlugin  = $container->get(ServicesAbstract::LEGACY_PLUGIN);
        $this->hooksHandler  = $container->get(ServicesAbstract::HOOKS_HANDLER);
        $this->pluginFile    = $container->get(ServicesAbstract::PLUGIN_FILE);
        $this->pluginVersion = $container->get(ServicesAbstract::PLUGIN_VERSION);

        $this->module_url = $this->getModuleUrl(__FILE__);

        

        // Register the module with PublishPress
        $args = [
            'title'                => esc_html__(
                'Rank Math SEO',
                'publishpress-checklists-pro'
            ),
            'short_description'    => esc_html__(
                'Define tasks that verify Rank Math SEO items',
                'publishpress-checklists-pro'
            ),
            'extended_description' => esc_html__(
                'Define tasks that verify Rank Math SEO items',
                'publishpress-checklists-pro'
            ),
            'module_url'           => $this->module_url,
            'icon_class'           => 'dashicons dashicons-feedback',
            'slug'                 => 'rank-math',
            'default_options'      => [
                'enabled' => 'on',
            ],
            'options_page'         => false,
            'autoload'             => true,
        ];

        // Apply a filter to the default options
        $args['default_options'] = $this->hooksHandler->applyFilters(
            PPCHPROHooksAbstract::FILTER_RANK_MATH_DEFAULT_OPTIONS,
            $args['default_options']
        );

        $this->module = $this->legacyPlugin->register_module($this->module_name, $args);

        if ($this->module) {
            $this->legacyPlugin->{$this->module_name} = $this;
        }

        $this->hooksHandler->addAction(PPCHPROHooksAbstract::ACTION_CHECKLIST_LOAD_ADDONS, [$this, 'actionLoadAddons']);
    }

    /**
     * Initialize the module. Conditionally loads if the module is enabled
     */
    public function init()
    {
        $this->hooksHandler->addAction(WPHooksAbstract::ACTION_ADMIN_INIT, [$this, 'loadUpdater']);
        $this->addFieldTabs();
    }

    /**
     * Action triggered before load requirements. We use this
     * to load the filters.
     */
    public function actionLoadAddons()
    {
        $this->hooksHandler->addFilter(
            PPCHPROHooksAbstract::FILTER_POST_TYPE_REQUIREMENTS,
            [$this, 'filterPostTypeRequirements'],
            10,
            2
        );
        $this->hooksHandler->addAction(
            PPCHPROHooksAbstract::ACTION_CHECKLIST_ENQUEUE_SCRIPTS,
            [$this, 'enqueueAdminScripts']
        );
    }

    /**
     * Load default editorial metadata the first time the module is loaded
     *
     * @since 0.7
     */
    public function install() {}

    /**
     * Upgrade our data in case we need to
     *
     * @since 0.7
     */
    public function upgrade($previous_version) {}

    /**
     * Enqueue scripts and stylesheets for the admin pages.
     */
    public function enqueueAdminScripts()
    {
        $scriptHandle = 'pp-rank-math-admin';

        wp_enqueue_script(
            $scriptHandle,
            plugins_url('/src/modules/rank-math/assets/js/meta-box.js', $this->pluginFile),
            ['jquery', 'pp-checklists-requirements'],
            $this->pluginVersion,
            true
        );

        $localized_data = $this->hooksHandler->applyFilters(PPCHPROHooksAbstract::FILTER_LOCALIZED_DATA, []);
        wp_localize_script(
            $scriptHandle,
            'PPCH_Rank_Math',
            $localized_data
        );
    }

    

    /**
     * Set the requirements list for the given post type
     *
     * @param array $requirements
     *
     * @return array
     */
    public function filterPostTypeRequirements($requirements)
    {
        if (Util::isRankMathActivated()) {
            $requirements[] = RankMathScore::class;
        }

        return $requirements;
    }
   
    /**
     * @return EDD_SL_Plugin_Updater
     */
    public function loadUpdater()
    {
        $container = Factory::getContainer();

        return $container->get(ServicesAbstract::EDD_CONNECTOR)['update_manager'];
    }

    /**
     * Add the field tabs and assigns them to the class property.
     */
    public function addFieldTabs()
    {
        if (!Util::isRankMathActivated()) {
            return;
        }

        // Get the singleton instance
        $fieldsTabs = FieldsTabs::getInstance();

        // Add the tabs
        $fieldsTabs->addTab(
            'rank_math',
            esc_html__('Rank Math SEO', 'publishpress-checklists-pro'),
            'pp-checklists-tab-custom-icon',
            '<svg viewBox="0 0 462.03 462.03" xmlns="http://www.w3.org/2000/svg" width="20">
                <g fill="#655997">
                    <path d="m462 234.84-76.17 3.43 13.43 21-127 81.18-126-52.93-146.26 60.97 10.14 24.34 136.1-56.71 128.57 54 138.69-88.61 13.43 21z"/>
                    <path d="m54.1 312.78 92.18-38.41 4.49 1.89v-54.58H54.1zm210.9-223.57v235.05l7.26 3 89.43-57.05v-181zm-105.44 190.79 96.67 40.62v-165.19h-96.67z"/>
                </g>
            </svg>',
            'custom'
        );
    }
}
