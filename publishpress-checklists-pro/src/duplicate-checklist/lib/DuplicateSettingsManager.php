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
 * Class DuplicateSettingsManager
 * 
 * Manages settings for duplicate requirements
 */
class DuplicateSettingsManager
{
    /**
     * Copy all settings from original requirement to duplicate
     *
     * @param object $options
     * @param string $original_name
     * @param string $duplicate_name
     * @param string $post_type
     */
    public function copyRequirementSettings($options, $original_name, $duplicate_name, $post_type)
    {
        $setting_suffixes = RequirementMappingData::SETTING_SUFFIXES;
        
        foreach ($setting_suffixes as $suffix) {
            $original_key = $original_name . $suffix;
            $duplicate_key = $duplicate_name . $suffix;

            if (isset($options->{$original_key})) {
                // Copy the entire setting structure
                $options->{$duplicate_key} = $this->deepCopy($options->{$original_key});

                // Ensure the duplicate is enabled for the current post type
                if ($suffix === '_rule') {
                    $this->ensurePostTypeEnabled($options, $duplicate_key, $post_type);
                }
            }
        }
    }

    /**
     * Ensure the duplicate requirement is enabled for the post type
     *
     * @param object $options
     * @param string $duplicate_key
     * @param string $post_type
     */
    private function ensurePostTypeEnabled($options, $duplicate_key, $post_type)
    {
        // Handle both array and object formats
        if (is_array($options->{$duplicate_key})) {
            $original_status = $options->{$duplicate_key}[$post_type] ?? 'undefined';
            if (!isset($options->{$duplicate_key}[$post_type]) ||
                $options->{$duplicate_key}[$post_type] === 'disabled' ||
                $options->{$duplicate_key}[$post_type] === 'off') {
                // For disabled/off originals, set to recommended
                $options->{$duplicate_key}[$post_type] = 'recommended';
            }
        } elseif (is_object($options->{$duplicate_key})) {
            $original_status = $options->{$duplicate_key}->{$post_type} ?? 'undefined';
            if (!isset($options->{$duplicate_key}->{$post_type}) ||
                $options->{$duplicate_key}->{$post_type} === 'disabled' ||
                $options->{$duplicate_key}->{$post_type} === 'off') {
                // For disabled/off originals, set to recommended
                $options->{$duplicate_key}->{$post_type} = 'recommended';
            }
        }
    }

    /**
     * Remove all settings for a duplicate requirement
     *
     * @param object $options
     * @param string $duplicate_name
     */
    public function removeDuplicateSettings($options, $duplicate_name)
    {
        // Create a snapshot of keys to avoid issues when unsetting
        $existing_keys = array_keys((array) $options);

        foreach ($existing_keys as $key) {
            // Remove any key that starts with the duplicate name
            if (strpos($key, $duplicate_name) === 0) {
                unset($options->{$key});
            }
        }
    }

    /**
     * Store dynamic class mapping for requirement registration
     *
     * @param string $duplicate_name
     * @param string $class_name
     * @param string $original_class
     */
    public function storeDynamicClassMapping($duplicate_name, $class_name, $original_class)
    {
        $mappings = get_option('ppc_duplicate_class_mappings', []);

        $mappings[$duplicate_name] = [
            'class_name' => $class_name,
            'original_class' => $original_class,
            'created_at' => current_time('mysql')
        ];

        update_option('ppc_duplicate_class_mappings', $mappings);
    }

    /**
     * Remove class mapping for a duplicate requirement
     *
     * @param string $duplicate_name
     * @return bool
     */
    public function removeClassMapping($duplicate_name)
    {
        $mappings = get_option('ppc_duplicate_class_mappings', []);
        
        if (isset($mappings[$duplicate_name])) {
            unset($mappings[$duplicate_name]);
            return update_option('ppc_duplicate_class_mappings', $mappings);
        }
        
        return false;
    }

    /**
     * Get class mapping for a duplicate requirement
     *
     * @param string $duplicate_name
     * @return array|null
     */
    public function getClassMapping($duplicate_name)
    {
        $mappings = get_option('ppc_duplicate_class_mappings', []);
        return $mappings[$duplicate_name] ?? null;
    }

    /**
     * Get all class mappings
     *
     * @return array
     */
    public function getAllClassMappings()
    {
        return get_option('ppc_duplicate_class_mappings', []);
    }

    /**
     * Deep copy an object or array with recursion protection
     *
     * @param mixed $data
     * @param int $depth Current recursion depth
     * @param int $max_depth Maximum allowed recursion depth
     * @return mixed
     */
    private function deepCopy($data, $depth = 0, $max_depth = 10)
    {
        // Prevent infinite recursion
        if ($depth > $max_depth) {
            return $data;
        }

        if (is_object($data)) {
            return clone $data;
        } elseif (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->deepCopy($value, $depth + 1, $max_depth);
            }
            return $result;
        } else {
            return $data;
        }
    }

    /**
     * Clean up orphaned class mappings (mappings without corresponding duplicate requirements)
     *
     * @return array
     */
    public function cleanupOrphanedMappings()
    {
        $options = get_option('publishpress_checklists_checklists_options', new \stdClass());
        $mappings = get_option('ppc_duplicate_class_mappings', []);
        
        $active_duplicates = [];
        $removed_mappings = [];

        // Find all active duplicate requirements
        foreach ($options as $key => $value) {
            if (strpos($key, '_duplicate_') !== false && strpos($key, '_rule') !== false) {
                $requirement_name = str_replace('_rule', '', $key);
                $active_duplicates[] = $requirement_name;
            }
        }

        // Find and remove orphaned mappings
        foreach ($mappings as $mapping_name => $mapping_data) {
            if (!in_array($mapping_name, $active_duplicates)) {
                unset($mappings[$mapping_name]);
                $removed_mappings[] = $mapping_name;
            }
        }

        // Save cleaned mappings
        if (!empty($removed_mappings)) {
            update_option('ppc_duplicate_class_mappings', $mappings);
        }

        return [
            'active_duplicates' => $active_duplicates,
            'removed_mappings' => $removed_mappings,
            'mappings_saved' => !empty($removed_mappings)
        ];
    }
}
