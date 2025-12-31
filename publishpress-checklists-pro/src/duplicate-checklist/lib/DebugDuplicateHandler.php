<?php

namespace PublishPress\ChecklistsPro\DuplicateChecklist;

use PublishPress\ChecklistsPro\DuplicateChecklist\DuplicateHandler;

/**
 * Handles debug tooling for the duplicate checklist feature.
 *
 * @package PublishPress\ChecklistsPro\DuplicateChecklist
 */
class DebugDuplicateHandler
{
    /**
     * @var DuplicateHandler
     */
    private $duplicateHandler;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->duplicateHandler = new DuplicateHandler();
    }

    /**
     * Get current duplicate requirements in a user-friendly format
     * 
     * @return array
     */
    public function getCurrentDuplicates()
    {
        $options = get_option('publishpress_checklists_checklists_options', new \stdClass());
        $duplicates = [];
        $all_duplicate_keys = [];
        
        // Find all keys that contain '_duplicate_'
        foreach ($options as $key => $value) {
            if (strpos($key, '_duplicate_') !== false) {
                $all_duplicate_keys[$key] = $value;
                
                if (strpos($key, '_rule') !== false) {
                    $requirement_name = str_replace('_rule', '', $key);
                    $duplicates[] = [
                        'name' => $requirement_name,
                        'display_name' => $this->formatDisplayName($requirement_name),
                        'status' => 'Active'
                    ];
                }
            }
        }

        // Get class mappings
        $mappings = get_option('ppc_duplicate_class_mappings', []);

        return [
            'duplicates' => $duplicates,
            'all_keys' => $all_duplicate_keys,
            'mappings' => $mappings,
            'total_count' => count($duplicates)
        ];
    }

    /**
     * Clean up a specific duplicate requirement
     * 
     * @param string $duplicate_name
     * @return array
     */
    public function cleanupDuplicate($duplicate_name)
    {
        if (empty($duplicate_name)) {
            return [
                'success' => false,
                'message' => 'Duplicate name is required'
            ];
        }

        try {
            $result = $this->duplicateHandler->forceCleanupDuplicate($duplicate_name);
            return [
                'success' => true,
                'message' => 'Duplicate cleaned up successfully',
                'details' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error cleaning up duplicate: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clean up orphaned class mappings
     * 
     * Class mappings store the relationship between duplicate requirement names and their PHP class names.
     * These mappings are essential for the system to know which PHP class to instantiate for each duplicate.
     * When duplicates are deleted improperly, these mappings can become orphaned (no matching duplicate exists).
     * 
     * @return array
     */
    public function cleanupOrphanedMappings()
    {
        try {
            $result = $this->duplicateHandler->cleanupOrphanedMappings();
            return [
                'success' => true,
                'message' => 'Orphaned PHP class mappings cleaned up successfully',
                'details' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error cleaning up orphaned class mappings: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format display name for duplicate requirements
     * 
     * @param string $name
     * @return string
     */
    private function formatDisplayName($name)
    {
        // Convert snake_case to Title Case
        $formatted = str_replace('_', ' ', $name);
        $formatted = ucwords($formatted);
        
        // Handle common abbreviations
        $formatted = str_replace('Duplicate', '(Duplicate)', $formatted);
        
        return $formatted;
    }
}
