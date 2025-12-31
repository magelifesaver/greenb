<?php

/**
 * @package     PublishPress\ChecklistsPro
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\ChecklistsPro\DuplicateChecklist;

/**
 * Class DynamicClassGenerator
 * 
 * Generates dynamic PHP classes for duplicate requirements
 */
class DynamicClassGenerator
{
    /**
     * @var DuplicateNameGenerator
     */
    private $nameGenerator;
    
    /**
     * @var DuplicateSettingsManager
     */
    private $settingsManager;
    
    public function __construct()
    {
        $this->nameGenerator = new DuplicateNameGenerator();
        $this->settingsManager = new DuplicateSettingsManager();
    }
    
    /**
     * Create a dynamic requirement class for the duplicate
     *
     * @param string $original_name
     * @param string $duplicate_name
     */
    public function createDynamicRequirementClass($original_name, $duplicate_name)
    {
        // Get the original requirement class
        $original_class = RequirementMappingData::getClassName($original_name);
        
        if (!$original_class) {
            return;
        }

        // Create dynamic class name
        $class_name = 'DuplicateRequirement_' . str_replace(['_', '-'], '', ucwords($duplicate_name, '_-'));

        // Store the class mapping for later use
        $this->settingsManager->storeDynamicClassMapping($duplicate_name, $class_name, $original_class);
    }

    /**
     * Create a dynamic class for a duplicate requirement
     *
     * @param string $duplicate_name
     * @param array $class_mappings
     * @return string|false
     */
    public function createDynamicClass($duplicate_name, $class_mappings)
    {
        if (!isset($class_mappings[$duplicate_name])) {
            return false;
        }

        $mapping = $class_mappings[$duplicate_name];
        $original_class = $mapping['original_class'];
        $dynamic_class_name = $mapping['class_name'];

        // Check if class already exists
        if (class_exists($dynamic_class_name)) {
            return $dynamic_class_name;
        }

        // Check if original class exists
        if (!class_exists($original_class)) {
            return false;
        }

        // Create the dynamic class
        $class_code = $this->generateDynamicClassCode($dynamic_class_name, $original_class, $duplicate_name);
        
        try {
            eval($class_code);
            
            if (class_exists($dynamic_class_name)) {
                return $dynamic_class_name;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        } catch (\ParseError $e) {
            return false;
        }
    }

    /**
     * Generate PHP code for a dynamic requirement class
     *
     * @param string $class_name
     * @param string $original_class
     * @param string $duplicate_name
     * @return string
     */
    private function generateDynamicClassCode($class_name, $original_class, $duplicate_name)
    {
        $original_name = $this->nameGenerator->getOriginalNameFromDuplicate($duplicate_name);

        // Get the group from the original class
        $group = RequirementMappingData::getGroup($original_name);

        // Generate a unique position for the duplicate
        $unique_position = $this->nameGenerator->generateUniquePosition($duplicate_name);

        // Escape the class name properly and ensure it's valid
        $safe_class_name = preg_replace('/[^a-zA-Z0-9_]/', '', $class_name);
        
        // Check if this is a parametrized requirement (like Taxonomies_count)
        $is_parametrized = ($original_class === '\\PublishPress\\Checklists\\Core\\Requirement\\Taxonomies_count');
        
        // Generate different code for parametrized requirements
        if ($is_parametrized) {
            return $this->generateParametrizedClassCode($safe_class_name, $original_class, $duplicate_name, $group, $unique_position, $original_name);
        }
        
        return "class {$safe_class_name} extends {$original_class}
{
    public \$name = '{$duplicate_name}';
    public \$group = '{$group}';
    public \$position = {$unique_position};

    public function __construct(\$module, \$post_type)
    {
        parent::__construct(\$module, \$post_type);
        \$this->name = '{$duplicate_name}';
        \$this->group = '{$group}';
        \$this->position = {$unique_position};
    }

    public function init_language()
    {
        parent::init_language();

        // Generate numbered title from duplicate name
        \$duplicate_number = \$this->extractDuplicateNumber('{$duplicate_name}');
        
        // Get the original title from the parent class or generate from name
        \$original_title = '';
        if (isset(\$this->lang['label']) && !empty(\$this->lang['label']) && \$this->lang['label'] !== 'Requirement') {
            \$original_title = \$this->lang['label'];
        } elseif (isset(\$this->lang['label_settings']) && !empty(\$this->lang['label_settings']) && \$this->lang['label_settings'] !== 'Requirement') {
            \$original_title = \$this->lang['label_settings'];
        } else {
            // Generate title from original requirement name
            \$original_name = \$this->getOriginalNameFromDuplicate('{$duplicate_name}');
            \$original_title = \$this->generateTitleFromName(\$original_name);
        }
        
        \$duplicate_number = \$this->extractDuplicateNumber('{$duplicate_name}');

        // Handle label_settings (for admin UI) - use original label_settings when available
        if (isset(\$this->lang['label_settings'])) {
            \$original_name = \$this->getOriginalNameFromDuplicate('{$duplicate_name}');
            
            // For publish_time_exact, use the original label_settings directly
            if (\$original_name === 'publish_time_exact') {
                \$settings_title = \$this->lang['label_settings'];
            } else {
                \$settings_title = \$this->cleanupTitlePlaceholders(\$original_title, \$original_name);
            }
            
            \$this->lang['label_settings'] = \$settings_title . ' (' . \$duplicate_number . ')';
        }
        
        // Handle label (for editor/frontend) - keep placeholders for dynamic replacement
        if (isset(\$this->lang['label'])) {
            \$this->lang['label'] = \$original_title . ' (' . \$duplicate_number . ')';
        }
    }
    
    private function getOriginalNameFromDuplicate(\$duplicate_name)
    {
        \$parts = explode('_duplicate_', \$duplicate_name);
        return isset(\$parts[0]) ? \$parts[0] : \$duplicate_name;
    }

    private function extractDuplicateNumber(\$duplicate_name)
    {
        \$parts = explode('_duplicate_', \$duplicate_name);
        return isset(\$parts[1]) ? \$parts[1] : '2';
    }
    
    private function generateTitleFromName(\$requirement_name)
    {
        // Convert requirement name to readable title
        \$title_mapping = " . var_export(RequirementMappingData::TITLE_MAPPING, true) . ";
        
        if (isset(\$title_mapping[\$requirement_name])) {
            return \$title_mapping[\$requirement_name];
        }
        
        // Fallback: convert underscores to spaces and capitalize
        return ucwords(str_replace('_', ' ', \$requirement_name));
    }
    
    private function cleanupTitlePlaceholders(\$title, \$requirement_name)
    {
        // Handle specific placeholders for different requirements
        if (strpos(\$title, '%s') !== false) {
            switch (\$requirement_name) {
                case 'approved_by':
                    return str_replace('%s', 'a user in this role', \$title);
                case 'publish_time_exact':
                    return 'Publish time should be at a specific time';
                default:
                    // For other requirements, remove the placeholder
                    return str_replace('%s', '', \$title);
            }
        }
        
        return \$title;
    }
}";
    }
    
    /**
     * Generate PHP code for a parametrized dynamic requirement class
     *
     * @param string $class_name
     * @param string $original_class
     * @param string $duplicate_name
     * @param string $group
     * @param int $unique_position
     * @param string $original_name
     * @return string
     */
    private function generateParametrizedClassCode($class_name, $original_class, $duplicate_name, $group, $unique_position, $original_name)
    {
        // Extract taxonomy name from the original requirement name (e.g., 'product_cat_count' -> 'product_cat')
        $taxonomy_name = preg_replace('/_count$/', '', $original_name);
        
        return "class {$class_name} extends {$original_class} implements \\PublishPress\\Checklists\\Core\\Requirement\\Interface_parametrized
{
    public \$name = '{$duplicate_name}';
    public \$group = '{$group}';
    public \$position = {$unique_position};
    private \$original_taxonomy_name = '{$taxonomy_name}';

    public function __construct(\$module, \$post_type)
    {
        parent::__construct(\$module, \$post_type);
        \$this->name = '{$duplicate_name}';
        \$this->group = '{$group}';
        \$this->position = {$unique_position};
    }
    
    public function set_params(\$params)
    {
        // Call parent to set up taxonomy object
        parent::set_params(\$params);
        
        // Override the name to use our duplicate name
        \$this->name = '{$duplicate_name}';
    }

    public function init_language()
    {
        // Call parent to set up base language strings
        parent::init_language();
        
        // Add duplicate number to the labels
        \$duplicate_number = \$this->extractDuplicateNumber('{$duplicate_name}');
        
        if (\$this->taxonomy) {
            \$label = \$this->taxonomy->labels->name;
            \$singular_label = \$this->taxonomy->labels->singular_name;
            
            \$this->lang['label_settings'] = __('Number of ', 'publishpress-checklists') . \$label . ' (' . \$duplicate_number . ')';
            \$this->lang['label_min_singular'] = __('Minimum of %d ', 'publishpress-checklists') . \$singular_label . ' (' . \$duplicate_number . ')';
            \$this->lang['label_min_plural'] = __('Minimum of %d ', 'publishpress-checklists') . \$label . ' (' . \$duplicate_number . ')';
            \$this->lang['label_max_singular'] = __('Maximum of %d ', 'publishpress-checklists') . \$singular_label . ' (' . \$duplicate_number . ')';
            \$this->lang['label_max_plural'] = __('Maximum of %d ', 'publishpress-checklists') . \$label . ' (' . \$duplicate_number . ')';
            \$this->lang['label_exact_singular'] = __('%d ', 'publishpress-checklists') . \$singular_label . ' (' . \$duplicate_number . ')';
            \$this->lang['label_exact_plural'] = __('%d ', 'publishpress-checklists') . \$label . ' (' . \$duplicate_number . ')';
            \$this->lang['label_between'] = __('Between %d and %d ', 'publishpress-checklists') . \$label . ' (' . \$duplicate_number . ')';
        }
    }
    
    private function extractDuplicateNumber(\$duplicate_name)
    {
        \$parts = explode('_duplicate_', \$duplicate_name);
        return isset(\$parts[1]) ? \$parts[1] : '2';
    }
}";
    }

    /**
     * Get duplicated requirements for a specific post type
     *
     * @param string $post_type
     * @return array Array of requirement class names
     */
    public function getDuplicatedRequirements($post_type)
    {
        $duplicated_requirements = [];
        $options = get_option('publishpress_checklists_checklists_options', new \stdClass());
        $class_mappings = $this->settingsManager->getAllClassMappings();

        if (!is_object($options)) {
            return $duplicated_requirements;
        }

        // Find all duplicate requirements that support this post type
        foreach ($options as $key => $value) {
            if (strpos($key, '_duplicate_') !== false && strpos($key, '_rule') !== false) {
                $requirement_name = str_replace('_rule', '', $key);

                // Check if this post type is supported by this duplicate (has a rule entry)
                if (isset($value[$post_type])) {
                    // Create dynamic class for this duplicate (regardless of enabled/disabled status)
                    // The UI will show it and users can configure it as needed
                    $class_name = $this->createDynamicClass($requirement_name, $class_mappings);
                    if ($class_name) {
                        $duplicated_requirements[] = $class_name;
                    }
                }
            }
        }

        return $duplicated_requirements;
    }
}
