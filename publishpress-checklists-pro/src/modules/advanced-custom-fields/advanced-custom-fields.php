<?php

use Alledia\EDD_SL_Plugin_Updater;
use PublishPress\Checklists\Core\Legacy\LegacyPlugin;
use PublishPress\Checklists\Core\Legacy\Module;
use PublishPress\Checklists\Core\Utils\FieldsTabs;
use PublishPress\ChecklistsPro\Factory;
use PublishPress\ChecklistsPro\HooksAbstract as PPCHPROHooksAbstract;
use PublishPress\ChecklistsPro\AdvancedCustomFields\Requirement\Base_simple;
use PublishPress\ChecklistsPro\AdvancedCustomFields\Requirement\Base_counter;
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
 * Class PPCH_Advanced_Custom_Fields
 *
 * @todo Refactor this module and all the modules system to use DI.
 */
#[\AllowDynamicProperties]
class PPCH_Advanced_Custom_Fields extends Module
{
    const METADATA_TAXONOMY = 'pp_acf_meta';

    const METADATA_POSTMETA_KEY = "_pp_acf_meta";

    const SETTINGS_SLUG = 'pp-acf-settings';

    const POST_META_PREFIX = 'pp_acf_custom_item_';

    const POST_TYPE_SUPPORT = ['acf-field-group'];

    public $module_name = 'advanced_custom_fields';

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
     * @var string
     */
    private $active_acf_fields;

    /**
     * Construct the PPCH_Advanced_Custom_Fields class
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
                'Advanced Custom Fields Support',
                'publishpress-checklists-pro'
            ),
            'short_description'    => esc_html__(
                'Define tasks that must be complete before a product is published',
                'publishpress-checklists-pro'
            ),
            'extended_description' => esc_html__(
                'Define tasks that must be complete before a product is published',
                'publishpress-checklists-pro'
            ),
            'module_url'           => $this->module_url,
            'icon_class'           => 'dashicons dashicons-feedback',
            'slug'                 => 'advanced-custom-fields',
            'default_options'      => [
                'enabled' => 'on',
            ],
            'options_page'         => false,
            'autoload'             => true,
        ];

        // Apply a filter to the default options
        $args['default_options'] = $this->hooksHandler->applyFilters(
            PPCHPROHooksAbstract::FILTER_WOOCOMMERCE_DEFAULT_OPTIONS,
            $args['default_options']
        );

        $this->module = $this->legacyPlugin->register_module($this->module_name, $args);

        $this->hooksHandler->addAction(PPCHPROHooksAbstract::ACTION_CHECKLIST_LOAD_ADDONS, [$this, 'actionLoadAddons']);
        $this->post_types = Util::isACFActivated() ? $this->list_active_acf_fields() : [];
    }

    /**
     * Initialize the module. Conditionally loads if the module is enabled
     */
    public function init()
    {
        $this->hooksHandler->addAction(WPHooksAbstract::ACTION_ADMIN_INIT, [$this, 'loadUpdater']);
        $this->addFieldTabs();
        add_filter('publishpress_checklists_filter_field_tabs', [$this, 'filterFieldTabs'], 10, 2);
        add_filter(
            'publishpress_checklists_supported_module_post_types',
            function ( $post_types ) {
                unset( $post_types['acf-post-type'] );
                unset( $post_types['acf-field-group'] );
                unset( $post_types['acf-field'] );
                return $post_types;
            }
        );
    }

    public function filterFieldTabs($postTypes, $allPostTypes)
    {
        // First ensure the ACF tab is registered
        $this->addFieldTabs();
        
        // Only add the ACF tab to post types that have ACF fields
        if (Util::isACFActivated()) {
            foreach ($postTypes as $key => $postType) {
                // Skip special post types like ACF itself
                if (in_array($key, ['acf-field-group'])) {
                    continue;
                }
                
                // Only add the tab if this post type has ACF fields
                if (isset($this->post_types[$key]) && !empty($this->post_types[$key]) && !isset($postTypes[$key]['advanced-custom-fields'])) {
                    // Get all field tabs
                    $fieldsTabs = FieldsTabs::getInstance();
                    $allTabs = $fieldsTabs->getFieldsTabs();
                    
                    // Check if the advanced-custom-fields tab exists
                    if (isset($allTabs['advanced-custom-fields'])) {
                        // Add it to this post type's tabs
                        $postTypes[$key]['advanced-custom-fields'] = $allTabs['advanced-custom-fields'];
                    } else {
                        // Create a basic tab definition if it doesn't exist
                        $postTypes[$key]['advanced-custom-fields'] = [
                            'label' => esc_html__('ACF', 'publishpress-checklists-pro'),
                            'icon' => 'dashicons dashicons-welcome-widgets-menus'
                        ];
                    }
                }
            }
        }

        return $postTypes;
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
        $scriptHandle = 'pp-acf-admin';

        wp_enqueue_script(
            $scriptHandle,
            plugins_url('/src/modules/advanced-custom-fields/assets/js/meta-box.js', $this->pluginFile),
            ['jquery', 'pp-checklists-requirements'],
            $this->pluginVersion,
            true
        );

        $localized_data = $this->hooksHandler->applyFilters(PPCHPROHooksAbstract::FILTER_ACF_LOCALIZED_DATA, []);
        wp_localize_script(
            $scriptHandle,
            'PPCH_Acf',
            $localized_data
        );
    }

    /**
     * Set the requirements list for the given post type
     *
     * @param array $requirements
     * @param string $postType
     *
     * @return array
     */
    public function filterPostTypeRequirements($requirements, $postType)
    {
        $classes = [];
        // Only add ACF fields if ACF is activated and we have fields for this post type
        if (Util::isACFActivated() && isset($this->post_types[$postType])) {
            // Only process fields for the current post type
            foreach ($this->post_types[$postType] as $value) {
                if (!isset($value['key']) || !isset($value['label']) || !isset($value['name'])) {
                    continue;
                }

                $classInstance = Base_simple::class;
                if (isset($value['type']) && in_array($value['type'], ['text', 'textarea'])) {
                    $classInstance = Base_counter::class;
                }

                $classes[] = maybe_serialize(
                    [
                        'class'  => $classInstance,
                        'params' => [
                            'post_type' => $postType,
                            'key'       => $value['key'],
                            'label'     => $value['label'],
                            'name'      => $value['name'],
                            'required'  => $value['required'],
                            'type'      => $value['type'],
                            'maxlength' => isset($value['maxlength']) ? $value['maxlength'] : null,
                            'group'     => 'advanced-custom-fields', 
                        ],
                    ]
                );
            }
        }


        if (!empty($classes)) {
            $requirements = array_merge($requirements, $classes);
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
        if (!Util::isACFActivated()) {
            return;
        }
        // Get the singleton instance
        $fieldsTabs = FieldsTabs::getInstance();

        // Add the tabs
        $fieldsTabs->addTab(
            'advanced-custom-fields',
            esc_html__('ACF', 'publishpress-checklists-pro'),
            'dashicons dashicons-welcome-widgets-menus',
            null,
            null
        );
    }

    // Get active ACF fields and their associated post types
    private function list_active_acf_fields()
    {
        // Ensure ACF is active
        if (!function_exists('acf_get_field_groups')) {
            return [];
        }

        // Get all field groups
        $field_groups = acf_get_field_groups();
        $active_fields = [];

        // Get all registered post types
        $post_types = get_post_types(['public' => true], 'objects');
        $post_type_names = array_map(function($post_type) {
            return $post_type->name;
        }, $post_types);

        // Iterate through each field group
        foreach ($field_groups as $group) {
            // Track which post types this field group applies to
            $applies_to_post_types = [];

            // Check each location rule group
            if (isset($group['location']) && is_array($group['location'])) {
                foreach ($group['location'] as $rule_group) {
                    if (!is_array($rule_group)) {
                        continue;
                    }

                    foreach ($rule_group as $rule) {
                        if (!is_array($rule) || !isset($rule['param']) || !isset($rule['operator']) || !isset($rule['value'])) {
                            continue;
                        }

                        // Only process post_type rules with == operator
                        if ($rule['param'] === 'post_type' && $rule['operator'] === '==' && in_array($rule['value'], $post_type_names)) {
                            $applies_to_post_types[] = $rule['value'];
                        }
                    }
                }
            }

            // Remove duplicates
            $applies_to_post_types = array_unique($applies_to_post_types);

            // Only process field groups that have explicit post type assignments
            // Do NOT default to all post types if none are found
            if (empty($applies_to_post_types)) {
                continue; // Skip this field group entirely
            }

            // Get fields for the current group
            $fields = acf_get_fields($group['key']) ?: [];

            // Only proceed if we have fields
            if (empty($fields)) {
                continue;
            }

            // Add fields to each applicable post type
            foreach ($applies_to_post_types as $post_type) {
                if (!isset($active_fields[$post_type])) {
                    $active_fields[$post_type] = [];
                }

                foreach ($fields as $field) {
                    $active_fields[$post_type][] = $field;
                }
            }
        }

        // Return the list of active fields organized by post type
        return $active_fields;
    }
}
