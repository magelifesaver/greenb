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
 * Class DuplicateIntegration
 * 
 * Handles the integration of duplicated requirements with the checklist system
 */
class DuplicateIntegration
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
        $this->init();
    }

    /**
     * Initialize the integration
     */
    public function init()
    {

        add_filter('publishpress_checklists_post_type_requirements', [$this, 'addDuplicatedRequirements'], 20, 2);
    }

    /**
     * Add duplicated requirements to the post type requirements list
     *
     * @param array $requirements Current requirements
     * @param string $post_type Post type
     * @return array Modified requirements with duplicates
     */
    public function addDuplicatedRequirements($requirements, $post_type)
    {
        try {
            // Get duplicated requirements for this post type
            $duplicated_requirements = $this->duplicateHandler->getDuplicatedRequirements($post_type);
            
            if (!empty($duplicated_requirements)) {

                foreach ($duplicated_requirements as $req_class) {
                    // Check if this is a parametrized requirement (like custom taxonomy)
                    if ($this->isParametrizedRequirement($req_class)) {
                        // Extract the taxonomy name from the duplicate
                        $taxonomy_name = $this->extractTaxonomyFromDuplicate($req_class);
                        
                        if ($taxonomy_name) {
                            // Add as serialized parametrized requirement
                            $requirements[] = maybe_serialize([
                                'class'  => $req_class,
                                'params' => [
                                    'post_type' => $post_type,
                                    'taxonomy'  => $taxonomy_name,
                                ],
                            ]);
                        }
                    } else {
                        // Add as regular class name
                        $requirements[] = $req_class;
                    }
                }
            }
            
        } catch (\Exception $e) {
            error_log("Error adding duplicated requirements: " . $e->getMessage());
        }

        return $requirements;
    }
    
    /**
     * Check if a requirement class is parametrized
     *
     * @param string $class_name
     * @return bool
     */
    private function isParametrizedRequirement($class_name)
    {
        // Check if the class implements Interface_parametrized
        if (class_exists($class_name)) {
            $interfaces = class_implements($class_name);
            return isset($interfaces['PublishPress\\Checklists\\Core\\Requirement\\Interface_parametrized']);
        }
        return false;
    }
    
    /**
     * Extract taxonomy name from duplicated parametrized requirement
     *
     * @param string $class_name
     * @return string|null
     */
    private function extractTaxonomyFromDuplicate($class_name)
    {
        // We need to get the taxonomy name from the duplicate name stored in our mappings
        $mappings = get_option('ppc_duplicate_class_mappings', []);
        
        // Find the duplicate name by matching the class name
        foreach ($mappings as $duplicate_name => $mapping_data) {
            if (isset($mapping_data['class_name']) && $mapping_data['class_name'] === $class_name) {
                // Extract taxonomy from duplicate name pattern: {taxonomy}_count_duplicate_X
                if (preg_match('/^(.+)_count_duplicate_\d+$/', $duplicate_name, $matches)) {
                    $taxonomy_name = $matches[1];
                    
                    // Verify this taxonomy exists
                    if (taxonomy_exists($taxonomy_name)) {
                        return $taxonomy_name;
                    }
                }
                break;
            }
        }
        
        return null;
    }
}