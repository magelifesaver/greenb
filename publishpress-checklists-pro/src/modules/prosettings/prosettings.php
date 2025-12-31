<?php

use PublishPress\Checklists\Core\Legacy\LegacyPlugin;
use PublishPress\Checklists\Core\Legacy\Module;
use PublishPress\ChecklistsPro\Factory;
use PublishPress\ChecklistsPro\HooksAbstract as PPCHPROHooksAbstract;
use PublishPress\ChecklistsPro\DuplicateChecklist\DebugDuplicateAdmin;
use PublishPress\ChecklistsPro\DuplicateChecklist\DebugDuplicateHandler;
use PublishPress\WordPressEDDLicense\Container as EDDContainer;
use PublishPress\WordPressEDDLicense\Setting\Field\License_key;
use WPPF\Plugin\ServicesAbstract;
use WPPF\WP\HooksHandlerInterface;
use WPPF\WP\SettingsHandlerInterface;

/**
 * @package     PublishPress\ChecklistsPro
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

/**
 * Class class PPCH_ProSettings extends Module
 *
 * @todo Refactor this module and all the modules system to use DI.
 */
#[\AllowDynamicProperties]
class PPCH_ProSettings extends Module
{
    const OPTIONS_GROUP_NAME = 'publishpress_checklists_settings_options';

    const LICENSE_STATUS_VALID = 'valid';

    const LICENSE_STATUS_INVALID = 'invalid';

    const METADATA_TAXONOMY = 'pp_prosettings_meta';

    const METADATA_POSTMETA_KEY = "_pp_prosettings_meta";

    const SETTINGS_SLUG = 'pp-prosettings-prosettings';

    public $module_name = 'prosettings';

    /**
     * Instance for the module
     *
     * @var stdClass
     */
    public $module;

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
     * @var SettingsHandlerInterface
     */
    private $settingsHandler;

    /**
     * @var EDDContainer
     */
    private $eddConnector;

    /**
     * @var string
     */
    private $licenseKey;

    /**
     * @var string
     */
    private $licenseStatus;

    /**
     * @var \Alledia\EDD_SL_Plugin_Updater
     */
    private $updateManager;

    /**
     * Construct the PPCH_WooCommerce class
     *
     * @todo: Fix to inject the dependencies in the constructor as params.
     */
    public function __construct()
    {
        $container = Factory::getContainer();

        $this->legacyPlugin    = $container->get(ServicesAbstract::LEGACY_PLUGIN);
        $this->hooksHandler    = $container->get(ServicesAbstract::HOOKS_HANDLER);
        $this->pluginFile      = $container->get(ServicesAbstract::PLUGIN_FILE);
        $this->pluginVersion   = $container->get(ServicesAbstract::PLUGIN_VERSION);
        $this->settingsHandler = $container->get(ServicesAbstract::SETTINGS_HANDLER);
        $this->eddConnector    = $container->get(ServicesAbstract::EDD_CONNECTOR);
        $this->licenseKey      = $container->get(ServicesAbstract::LICENSE_KEY);
        $this->licenseStatus   = $container->get(ServicesAbstract::LICENSE_STATUS);
        $this->updateManager   = $this->eddConnector['update_manager'];

        $this->module_url = $this->getModuleUrl(__FILE__);

        // Register the module with PublishPress
        $args = [
            'title'           => esc_html__('Pro Settings', 'publishpress-checklists-pro'),
            'module_url'      => $this->module_url,
            'icon_class'      => 'dashicons dashicons-feedback',
            'slug'            => 'prosettings',
            'default_options' => [
                'enabled'        => 'on',
                'license_key'    => '',
                'license_status' => '',
                'duplicate_checklist_enabled' => 'on',
                'debug_duplicate_feature' => 'off',
                'show_checklists_column' => 'on',
            ],
            'options_page'    => false,
            'autoload'        => true,
        ];

        // Apply a filter to the default options
        $args['default_options'] = $this->hooksHandler->applyFilters(
            PPCHPROHooksAbstract::FILTER_WOOCOMMERCE_DEFAULT_OPTIONS,
            $args['default_options']
        );

        $this->module = $this->legacyPlugin->register_module($this->module_name, $args);

        add_filter('publishpress_checklists_settings_tabs', [$this, 'settings_tab'], 20);
    }

    /**
     * Initialize the module. Conditionally loads if the module is enabled
     */
    public function init()
    {
        $this->setHooks();

        //Add "Checklists" column to posts list table.
        $settings = (array) get_option(self::OPTIONS_GROUP_NAME, []);
        $isShowColumnEnabled = !isset($settings['show_checklists_column']) || 'on' === $settings['show_checklists_column'];

        if ($isShowColumnEnabled) {
            $selected_post_types = $this->getSelectedPostTypes();
            foreach ($selected_post_types as $post_type => $label) {
                add_filter("manage_{$post_type}_posts_columns", [$this, 'add_checklists_column'], 20);
                add_action("manage_{$post_type}_posts_custom_column", [$this, 'render_checklists_column'], 10, 2);
            }
        }
    }

    private function setHooks()
    {
        $this->hooksHandler->addAction(
            PPCHPROHooksAbstract::ACTION_CHECKLISTS_REGISTER_SETTINGS,
            [$this, 'registerSettings']
        );
        $this->hooksHandler->addAction(
            PPCHPROHooksAbstract::ACTION_ADMIN_ENQUEUE_SCRIPTS,
            [$this, 'enqueueAdminScripts']
        );
        $this->hooksHandler->addAction(
            PPCHPROHooksAbstract::ACTION_ADMIN_ENQUEUE_SCRIPTS,
            [$this, 'enqueueAdminScripts']
        );

        $this->hooksHandler->addFilter(
            PPCHPROHooksAbstract::FILTER_VALIDATE_MODULE_SETTINGS,
            [$this, 'validateModuleSettings']
        );
        
    }

    /**
     * Enqueue scripts and stylesheets for the admin pages.
     */
    public function enqueueAdminScripts()
    {
        wp_enqueue_style(
            'ppch_prosettings_admin',
            plugins_url('/src/modules/prosettings/assets/css/admin.css', $this->pluginFile),
            [],
            $this->pluginVersion
        );
    }

    public function registerSettings()
    {
        add_settings_section(
            self::OPTIONS_GROUP_NAME . '_license',
            __return_false(),
            [$this, 'settings_section_license'],
            self::OPTIONS_GROUP_NAME
        );

        $this->settingsHandler->addField(
            'license_key',
            esc_html__('License key:', 'publishpress-checklists-pro'),
            [$this, 'settingsLicenseKeyOption'],
            self::OPTIONS_GROUP_NAME,
            self::OPTIONS_GROUP_NAME . '_license'
        );
        $this->settingsHandler->addField(
            'show_checklists_column',
            esc_html__('Show Checklists column in post lists:', 'publishpress-checklists-pro'),
            [$this, 'settings_show_checklists_column_option'],
            self::OPTIONS_GROUP_NAME,
            self::OPTIONS_GROUP_NAME . '_general'
        );
        $this->settingsHandler->addField(
            'status_filter_enabled',
            esc_html__('Enable Status Filter:', 'publishpress-checklists-pro'),
            [$this, 'settingsStatusFilterOption'],
            self::OPTIONS_GROUP_NAME,
            self::OPTIONS_GROUP_NAME . '_general'
        );

        $this->settingsHandler->addField(
            'duplicate_checklist_enabled',
            esc_html__('Enable Duplicate Checklists', 'publishpress-checklists-pro'),
            [$this, 'settingsDuplicateChecklistOption'],
            self::OPTIONS_GROUP_NAME,
            self::OPTIONS_GROUP_NAME . '_general'
        );

        $this->settingsHandler->addField(
            'debug_duplicate_feature',
            esc_html__('Debug Duplicate Feature:', 'publishpress-checklists-pro'),
            [$this, 'settingsDebugDuplicateOption'],
            'publishpress_checklists_settings_options', // Attach to the Tools tab rendered by the free module
            'publishpress_checklists_settings_options_tools'
        );

        if (self::isDebugDuplicateFeatureEnabled()) {
            add_settings_section(
                self::OPTIONS_GROUP_NAME . '_debug_duplicate',
                __return_false(),
                [$this, 'settings_section_debug_duplicate'],
                self::OPTIONS_GROUP_NAME
            );

            $this->settingsHandler->addField(
                'debug_duplicate_content',
                '',
                [$this, 'settingsDebugDuplicateContent'],
                self::OPTIONS_GROUP_NAME,
                self::OPTIONS_GROUP_NAME . '_debug_duplicate'
            );
        }
    }

    public function settingsLicenseKeyOption()
    {
        $container = Factory::getContainer();

        $value  = isset($container[ServicesAbstract::LICENSE_KEY]) ? $container[ServicesAbstract::LICENSE_KEY] : '';
        $status = isset($container[ServicesAbstract::LICENSE_STATUS]) ? $container[ServicesAbstract::LICENSE_STATUS] : self::LICENSE_STATUS_INVALID;

        echo new License_key(
            [
                'options_group_name' => self::OPTIONS_GROUP_NAME,
                'name' => 'license_key',
                'id' => self::OPTIONS_GROUP_NAME . '_license_key',
                'value' => $value,
                'class' => '',
                'license_status' => $status,
                'link_more_info' => '',
            ]
        );
    }

    public function settingsStatusFilterOption()
    {
        $id = self::OPTIONS_GROUP_NAME . '_status_filter_enabled';
        $options = (array) get_option(self::OPTIONS_GROUP_NAME, []);
        $value = isset($options['status_filter_enabled']) ? $options['status_filter_enabled'] : 'on';
        echo '<label for="' . $id . '">';
        echo '<input id="' . $id . '" name="' . self::OPTIONS_GROUP_NAME . '[status_filter_enabled]"';
        checked($value, 'on');
        echo ' type="checkbox" value="on" /></label>';
        echo '&nbsp;&nbsp;&nbsp;' . esc_html__(
            'This allows tasks to be disabled for specific statuses such as "Draft" or "Published".',
            'publishpress-checklists-pro'
        );
    }

    public function settingsDuplicateChecklistOption()
    {
        $id = self::OPTIONS_GROUP_NAME . '_duplicate_checklist_enabled';
        $options = (array) get_option(self::OPTIONS_GROUP_NAME, []);
        $value = isset($options['duplicate_checklist_enabled']) ? $options['duplicate_checklist_enabled'] : 'on';
        echo '<label for="' . $id . '">';
        echo '<input id="' . $id . '" name="' . self::OPTIONS_GROUP_NAME . '[duplicate_checklist_enabled]"';
        checked($value, 'on');
        echo ' type="checkbox" value="on" /></label>';
        echo '&nbsp;&nbsp;&nbsp;' . esc_html__(
            'This allows users to duplicate existing checklist tasks.',
            'publishpress-checklists-pro'
        );
    }

    public function settingsDebugDuplicateOption()
    {
        $optionsGroup = self::OPTIONS_GROUP_NAME;
        $id = $optionsGroup . '_debug_duplicate_feature';
        $options = (array) get_option($optionsGroup, []);
        $value = isset($options['debug_duplicate_feature']) ? $options['debug_duplicate_feature'] : 'off';
        echo '<label for="' . esc_attr($id) . '">';
        echo '<input type="hidden" name="' . esc_attr($optionsGroup) . '[debug_duplicate_feature]" value="off" />';
        echo '<input id="' . esc_attr($id) . '" name="' . esc_attr($optionsGroup) . '[debug_duplicate_feature]" type="checkbox" value="on" ' . checked($value, 'on', false) . ' />';
        echo '</label>';
        echo '&nbsp;&nbsp;&nbsp;' . esc_html__(
            'Enable debugging tools for duplicate functionality.',
            'publishpress-checklists-pro'
        );
    }

    /**
    * Displays the checkbox to enable or disable the Checklists column in post lists.
    *
    * @param array $args
    */
    public function settings_show_checklists_column_option($args = [])
    {
        $id = self::OPTIONS_GROUP_NAME . '_show_checklists_column';
        $options = (array) get_option(self::OPTIONS_GROUP_NAME, []);
        $value = isset($options['show_checklists_column']) ? $options['show_checklists_column'] : 'on';
        echo '<label for="' . $id . '">';
        echo '<input id="' . $id . '" name="' . self::OPTIONS_GROUP_NAME . '[show_checklists_column]"';
        checked($value, 'on');
        echo ' type="checkbox" value="on" /></label>';
        echo '&nbsp;&nbsp;&nbsp;' . esc_html__(
            'Add a Checklists column to the Posts screen showing how many requirements are complete.',
            'publishpress-checklists-pro'
        );
        
    }

    /**
     * Add "Checklists" column header to posts list table.
     */
    public function add_checklists_column($columns)
    {
        $new_columns = [];
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            //Append after date column
            if ($key === 'date') {
                $new_columns['checklists'] = esc_html__('Checklists', 'publishpress-checklists-pro');
            }
        }
        return $new_columns;
    }

    /**
     * Render the "Checklists" column content.
     */
    public function render_checklists_column($column, $post_id)
    {
        if ($column !== 'checklists') {
            return;
        }
        $requirements = apply_filters('publishpress_checklists_requirement_list', [], get_post($post_id));
        $total = count($requirements);
        $passed = 0;
        foreach ($requirements as $req) {
            if (!empty($req['status'])) {
                $passed++;
            }
        }
        if ($total > 0) {
            // Calculate completion percentage
            $percentage = round(($passed / $total) * 100);
            
            // Determine color: green when all items pass, red otherwise
            if ($percentage >= 100) {
                $color = '#66bb6a';
                $background = 'rgba(102, 187, 106, 0.15)';
            } else {
                $color = '#ef5350';
                $background = 'rgba(239, 83, 80, 0.15)';
            }
            
            // Size of the circular progress indicator
            $size = 28;
            $stroke_width = 3;
            $radius = ($size - $stroke_width) / 2;
            $circumference = 2 * M_PI * $radius;
            $dash_array = $circumference;
            $dash_offset = $circumference - ($percentage / 100 * $circumference);
            
            // SVG for circular progress indicator
            $svg = '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" style="transform: rotate(-90deg);">';
            $svg .= '<circle cx="' . ($size/2) . '" cy="' . ($size/2) . '" r="' . $radius . '" fill="' . $background . '" stroke="#e0e0e0" stroke-width="' . $stroke_width . '"></circle>';
            $svg .= '<circle cx="' . ($size/2) . '" cy="' . ($size/2) . '" r="' . $radius . '" fill="none" stroke="' . $color . '" stroke-width="' . $stroke_width . '" stroke-dasharray="' . $dash_array . '" stroke-dashoffset="' . $dash_offset . '" stroke-linecap="round"></circle>';
            $svg .= '</svg>';
            
            // Output a indicator with circular progress and text
            echo '<div class="pp-checklist-indicator" style="display: flex; align-items: center; gap: 8px;">';
            echo $svg;
            echo '<span style="font-weight: 500; color: ' . esc_attr($color) . ';">' . esc_html("{$passed}/{$total}") . '</span>';
            echo '</div>';
        } else {
            // Display a message when no checklists are activated
            echo '<div class="pp-checklist-indicator" style="display: flex; align-items: center; gap: 8px;">';
            echo '<span style="color: #999; font-style: italic;">' . esc_html__('No checklists', 'publishpress-checklists-pro') . '</span>';
            echo '</div>';
        }
    }

    protected function getSelectedPostTypes()
    {
        $legacyPlugin  = Factory::getLegacyPlugin();
        $postTypeSlugs = $this->getPostTypesForModule($legacyPlugin->settings->module);
        $postTypes     = [];

        foreach ($postTypeSlugs as $slug) {
            $postType = get_post_type_object($slug);
            if (is_object($postType)) {
                // Need to overide the value to prevent user confusion
                if ($slug === 'acf-field-group') $postType->label = 'ACF';
                $postTypes[$slug] = $postType->label;
            }
        }

        return $postTypes;
    }

    public function validateModuleSettings($options)
    {
        if (isset($options['license_key'])) {
            if ($this->licenseKey !== $options['license_key'] || empty($this->licenseStatus) || $this->licenseStatus !== self::LICENSE_STATUS_VALID) {
                $options['license_status'] = $this->validateLicenseKey($options['license_key']);
            }
        }

        // Handle checkbox states - if not present in POST, it means unchecked
        $options['status_filter_enabled'] = isset($options['status_filter_enabled']) ? 'on' : 'off';
        $options['duplicate_checklist_enabled'] = isset($options['duplicate_checklist_enabled']) ? 'on' : 'off';
        $options['debug_duplicate_feature'] = isset($options['debug_duplicate_feature']) && $options['debug_duplicate_feature'] === 'on' ? 'on' : 'off';
        $options['show_checklists_column'] = isset($options['show_checklists_column']) ? 'on' : 'off';

        return $options;
    }

    public function validateLicenseKey($licenseKey)
    {
        $licenseManager = $this->eddConnector['license_manager'];

        return $licenseManager->validate_license_key($licenseKey, PPCHPRO_ITEM_ID);
    }

    private function hasValidLicenseKeySet()
    {
        $container = Factory::getContainer();

        return !empty($container->get(ServicesAbstract::LICENSE_KEY)) && $container->get(
                ServicesAbstract::LICENSE_STATUS
            ) === self::LICENSE_STATUS_VALID;
    }

    /**
     * @param array $tabs
     *
     * @return array
     */
    public function settings_tab($tabs)
    {

        $tabs = array_merge(
            $tabs,
            [
                '#ppch-tab-license'    => esc_html__('License', 'publishpress-checklists'),
            ]
        );

        // Add Debug Duplicate tab if debug duplicate feature is enabled
        if (self::isDebugDuplicateFeatureEnabled()) {
            $tabs['#ppch-tab-debug-duplicate'] = esc_html__('Debug Duplicate', 'publishpress-checklists-pro');
        }

        return $tabs;
    }

    public function settings_section_license()
    {
        echo '<input type="hidden" id="ppch-tab-license" />';
    }


    public function settings_section_debug_duplicate()
    {
        echo '<input type="hidden" id="ppch-tab-debug-duplicate" />';
    }

    public function settingsDebugDuplicateContent()
    {
        // Include the debug duplicate admin classes
        if (!class_exists('\\PublishPress\\ChecklistsPro\\DuplicateChecklist\\DebugDuplicateHandler')) {
            require_once dirname(__DIR__, 2) . '/duplicate-checklist/lib/DebugDuplicateHandler.php';
        }
        if (!class_exists('\\PublishPress\\ChecklistsPro\\DuplicateChecklist\\DebugDuplicateAdmin')) {
            require_once dirname(__DIR__, 2) . '/duplicate-checklist/lib/DebugDuplicateAdmin.php';
        }

        $debugHandler = new DebugDuplicateHandler();
        $debugDuplicateAdmin = new DebugDuplicateAdmin($debugHandler);

        $debugDuplicateAdmin->maybeHandleRequest($_POST);
        $debugDuplicateAdmin->render();
    }

    /**
     * Check if duplicate checklists feature is enabled
     *
     * @return bool
     */
    public static function isDuplicateChecklistEnabled()
    {
        $options = (array) get_option(self::OPTIONS_GROUP_NAME, []);
        return isset($options['duplicate_checklist_enabled']) && $options['duplicate_checklist_enabled'] === 'on';
    }

    /**
     * Check if debug duplicate feature is enabled
     *
     * @return bool
     */
    public static function isDebugDuplicateFeatureEnabled()
    {
        $options = (array) get_option(self::OPTIONS_GROUP_NAME, []);
        return isset($options['debug_duplicate_feature']) && $options['debug_duplicate_feature'] === 'on';
    }
}
