<?php

/**
 * @package     PublishPress\Checklists
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */


namespace PublishPress\ChecklistsPro\Accessibility\Requirement;

use PublishPress\Checklists\Core\Requirement\Base_simple;

defined('ABSPATH') or die('No direct script access allowed.');

class TableHeader extends Base_simple
{

    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'table_header';

    /**
     * The name of the group, used for the tabs
     * 
     * @var string
     */
    public $group = 'accessibility';

    /**
     * @var int
     */
    public $position = 140;

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        $this->lang['label']                = __('Tables have a header row', 'publishpress-checklists-pro');
        $this->lang['label_settings']       = __('Tables have a header row', 'publishpress-checklists-pro');
    }

    /**
     * Returns the current status of the requirement.
     *
     * @param \stdClass $post
     * @param mixed $option_value
     *
     * @return bool
     */
    public function get_current_status($post, $option_value)
    {
        $content = (property_exists($post, 'post_content') && is_string($post->post_content)) ? $post->post_content : '';

        // If there is no content, the requirement is met by default.
        if (empty(trim($content))) {
            return true;
        }

        // Regex to find all table elements. 's' modifier for dot to match newline, 'i' for case-insensitive.
        $table_regex = '/<table[^>]*>(.*?)<\/table>/si';
        preg_match_all($table_regex, $content, $table_matches, PREG_SET_ORDER);

        // If no tables are found, the requirement is met by default.
        if (empty($table_matches)) {
            return true;
        }

        // Regex to check for <th> within a table. 'i' for case-insensitive.
        $th_regex = '/<th[^>]*>/i';

        foreach ($table_matches as $table_match) {
            $table_content = $table_match[1]; // Content within the <table> tags

            // Check if the current table_content contains at least one <th> tag.
            if (!preg_match($th_regex, $table_content)) {
                // If any table is found without a <th>, the requirement fails.
                return false;
            }
        }

        // If all tables found have at least one <th> element, the requirement is met.
        return true;
    }
    
}
