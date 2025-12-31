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
 * Class DuplicateNameGenerator
 * 
 * Handles generation of unique names for duplicate requirements
 */
class DuplicateNameGenerator
{
    /**
     * Generate a unique name for the duplicate requirement using numbered format
     *
     * @param string $original_name
     * @return string
     */
    public function generateDuplicateName($original_name)
    {
        $options = get_option('publishpress_checklists_checklists_options', new \stdClass());

        $next_number = $this->getNextDuplicateNumber($original_name, $options);
        $base_name = $original_name . '_duplicate_' . $next_number;

        $duplicate_name = $base_name;
        $counter = 1;

        while (isset($options->{$duplicate_name . '_rule'})) {
            $duplicate_name = $base_name . '_' . $counter;
            $counter++;
        }

        return $duplicate_name;
    }

    /**
     * Get the next available number for duplicating a requirement
     *
     * @param string $original_name
     * @param object $options
     * @return int
     */
    private function getNextDuplicateNumber($original_name, $options)
    {
        $existing_numbers = [];

        // Find all existing duplicates of this requirement
        foreach ($options as $key => $value) {
            if (strpos($key, $original_name . '_duplicate_') === 0 && strpos($key, '_rule') !== false) {
                // Extract the duplicate identifier
                $duplicate_name = str_replace('_rule', '', $key);
                $duplicate_part = str_replace($original_name . '_duplicate_', '', $duplicate_name);

                // Check if it's a number
                if (is_numeric($duplicate_part)) {
                    $existing_numbers[] = (int) $duplicate_part;
                }
            }
        }

        // Find the next available number starting from 2
        $next_number = 2;
        while (in_array($next_number, $existing_numbers)) {
            $next_number++;
        }

        return $next_number;
    }

    /**
     * Extract the duplicate number from a duplicate name
     *
     * @param string $duplicate_name
     * @return string
     */
    public function extractDuplicateNumber($duplicate_name)
    {
        // Extract the number from the duplicate name
        // Format: original_name_duplicate_2
        $parts = explode('_duplicate_', $duplicate_name);
        if (isset($parts[1])) {
            return $parts[1];
        }

        return '2'; // Default fallback
    }

    /**
     * Get original requirement name from duplicate name
     *
     * @param string $duplicate_name
     * @return string
     */
    public function getOriginalNameFromDuplicate($duplicate_name)
    {
        $parts = explode('_duplicate_', $duplicate_name);
        return isset($parts[0]) ? $parts[0] : $duplicate_name;
    }

    /**
     * Generate a user-friendly display name for the duplicate
     *
     * @param string $original_name
     * @param string $duplicate_name
     * @return string
     */
    public function generateDisplayName($original_name, $duplicate_name)
    {
        // Get the base title
        $base_title = RequirementMappingData::getTitle($original_name);

        // Extract the duplicate number
        $duplicate_number = $this->extractDuplicateNumber($duplicate_name);

        // Return the user-friendly display name
        return $base_title . ' (' . $duplicate_number . ')';
    }

    /**
     * Generate a unique position for a duplicate requirement
     *
     * @param string $duplicate_name
     * @return int
     */
    public function generateUniquePosition($duplicate_name)
    {
        // Get the original requirement's position
        $original_name = $this->getOriginalNameFromDuplicate($duplicate_name);
        $original_position = RequirementMappingData::getPosition($original_name);

        // Generate a unique position based on the duplicate name hash
        // This ensures duplicates have different positions but are still grouped near the original
        $hash = crc32($duplicate_name);
        $offset = abs($hash % 1000) + 1; // Generate offset between 1-1000

        return $original_position + $offset;
    }
}
