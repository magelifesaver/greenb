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
 * Class DuplicateHandler
 * 
 * Main orchestrator for duplicating checklist requirements
 */
class DuplicateHandler
{
    /**
     * @var DuplicateNameGenerator
     */
    private $nameGenerator;
    
    /**
     * @var DuplicateSettingsManager
     */
    private $settingsManager;
    
    /**
     * @var DynamicClassGenerator
     */
    private $classGenerator;
    
    public function __construct()
    {
        $this->nameGenerator = new DuplicateNameGenerator();
        $this->settingsManager = new DuplicateSettingsManager();
        $this->classGenerator = new DynamicClassGenerator();
    }
    /**
     * Duplicate a requirement with all its settings
     *
     * @param string $original_name The original requirement name
     * @param string $post_type The post type
     * @return array Result array with success status and data
     */
    public function duplicateRequirement($original_name, $post_type)
    {
        try {
            // Prevent duplicating already duplicated requirements
            if (strpos($original_name, '_duplicate_') !== false) {
                return [
                    'success' => false,
                    'message' => 'Cannot duplicate an already duplicated requirement'
                ];
            }

            // Generate unique name for the duplicate
            $duplicate_name = $this->nameGenerator->generateDuplicateName($original_name);


            $options = get_option('publishpress_checklists_checklists_options', new \stdClass());

            if (!is_object($options)) {
                $options = new \stdClass();
            }

            // Copy all settings from original to duplicate
            $this->settingsManager->copyRequirementSettings($options, $original_name, $duplicate_name, $post_type);

            // Save the updated options
            $save_result = update_option('publishpress_checklists_checklists_options', $options);

            // Create the dynamic requirement class
            $this->classGenerator->createDynamicRequirementClass($original_name, $duplicate_name);

            // Generate user-friendly display name
            $display_name = $this->nameGenerator->generateDisplayName($original_name, $duplicate_name);

            return [
                'success' => true,
                'duplicate_name' => $duplicate_name,
                'display_name' => $display_name,
                'original_name' => $original_name,
                'message' => 'Requirement duplicated successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error duplicating requirement: ' . $e->getMessage()
            ];
        }
    }


    /**
     * Get duplicated requirements for a specific post type
     *
     * @param string $post_type
     * @return array Array of requirement class names
     */
    public function getDuplicatedRequirements($post_type)
    {
        return $this->classGenerator->getDuplicatedRequirements($post_type);
    }

    /**
     * Delete a duplicate requirement
     *
     * @param string $duplicate_name The duplicate requirement name
     * @return array Result array with success status and data
     */
    public function deleteDuplicateRequirement($duplicate_name)
    {
        try {
            // Get current options (bypass cache)
            wp_cache_delete('publishpress_checklists_checklists_options', 'options');
            $options = get_option('publishpress_checklists_checklists_options', new \stdClass());
            
            if (!is_object($options)) {
                $options = new \stdClass();
            }

            // Remove all settings for this duplicate
            $this->settingsManager->removeDuplicateSettings($options, $duplicate_name);

            // Force save the updated options (bypass cache)
            wp_cache_delete('publishpress_checklists_checklists_options', 'options');
            $options_saved = update_option('publishpress_checklists_checklists_options', $options);

            // Remove from class mappings
            $mapping_removed = $this->settingsManager->removeClassMapping($duplicate_name);

            return [
                'success' => true,
                'message' => 'Duplicate requirement deleted successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error deleting duplicate requirement: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Debug method to check what was created
     *
     * @param string $duplicate_name
     * @return array
     */
    public function debugDuplicate($duplicate_name)
    {
        $options = get_option('publishpress_checklists_checklists_options', new \stdClass());
        $mapping = $this->settingsManager->getClassMapping($duplicate_name);

        $debug_info = [
            'duplicate_name' => $duplicate_name,
            'options_found' => [],
            'class_mapping' => $mapping,
        ];

        // Find all options related to this duplicate
        $existing_keys = array_keys((array) $options);
        foreach ($existing_keys as $key) {
            if (strpos($key, $duplicate_name) === 0) {
                $debug_info['options_found'][$key] = $options->{$key};
            }
        }

        return $debug_info;
    }

    /**
     * Debug method to check all duplicates and their status
     *
     * @return array
     */
    public function debugAllDuplicates()
    {
        $options = get_option('publishpress_checklists_checklists_options', new \stdClass());
        $mappings = $this->settingsManager->getAllClassMappings();

        $debug_info = [
            'total_options' => count((array) $options),
            'total_mappings' => count($mappings),
            'duplicates_found' => [],
            'class_mappings' => $mappings,
            'all_duplicate_keys' => [],
        ];

        // Find all keys that contain '_duplicate_'
        foreach ($options as $key => $value) {
            if (strpos($key, '_duplicate_') !== false) {
                $debug_info['all_duplicate_keys'][$key] = $value;
                
                if (strpos($key, '_rule') !== false) {
                    $requirement_name = str_replace('_rule', '', $key);
                    $debug_info['duplicates_found'][$requirement_name] = [
                        'rule' => $value,
                        'has_mapping' => isset($mappings[$requirement_name]),
                    ];
                }
            }
        }

        return $debug_info;
    }

    /**
     * Force cleanup of all duplicate-related data for a specific duplicate
     *
     * @param string $duplicate_name
     * @return array
     */
    public function forceCleanupDuplicate($duplicate_name)
    {
        $options = get_option('publishpress_checklists_checklists_options', new \stdClass());
        
        $removed_keys = [];

        // Remove from options
        $existing_keys = array_keys((array) $options);
        foreach ($existing_keys as $key) {
            if (strpos($key, $duplicate_name) === 0) {
                unset($options->{$key});
                $removed_keys[] = $key;
            }
        }

        // Save options
        $options_saved = update_option('publishpress_checklists_checklists_options', $options);
        
        // Remove class mapping
        $mappings_saved = $this->settingsManager->removeClassMapping($duplicate_name);

        return [
            'removed_keys' => $removed_keys,
            'removed_mappings' => $mappings_saved ? [$duplicate_name] : [],
            'options_saved' => $options_saved,
            'mappings_saved' => $mappings_saved,
        ];
    }

    /**
     * Clean up orphaned class mappings (mappings without corresponding duplicate requirements)
     *
     * @return array
     */
    public function cleanupOrphanedMappings()
    {
        return $this->settingsManager->cleanupOrphanedMappings();
    }
}
