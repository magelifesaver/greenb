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

class HeadingInHierarchy extends Base_simple
{

    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'heading_in_hierarchy';

    /**
     * The name of the group, used for the tabs
     * 
     * @var string
     */
    public $group = 'accessibility';

    /**
     * @var int
     */
    public $position = 142;

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        $this->lang['label']                = __('H1, H2, H3 etc tags are used in logical order', 'publishpress-checklists-pro');
        $this->lang['label_settings']       = __('H1, H2, H3 etc tags are used in logical order', 'publishpress-checklists-pro');
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

        // Regex to find all heading tags (h1 to h6) and capture the tag name (e.g., h1, h2).
        $heading_regex = '/<(h[1-6])[^>]*>.*?<\/\1>/si';
        preg_match_all($heading_regex, $content, $matches, PREG_SET_ORDER);

        // If no headings are found, the requirement is met by default.
        if (empty($matches)) {
            return true;
        }

        $previous_level = 0;

        foreach ($matches as $match) {
            // $match[1] will contain 'h1', 'h2', etc.
            // Extract the number from the tag name.
            $current_level = (int) substr($match[1], 1);

            if ($previous_level === 0) {
                // This is the first heading encountered.
                $previous_level = $current_level;
            } else {
                // Check if the current heading level skips more than one level from the previous.
                if ($current_level > $previous_level + 1) {
                    // A skip in heading level is detected (e.g., h2 to h4).
                    return false; // Requirement fails.
                }
                $previous_level = $current_level;
            }
        }

        // If the loop completes without finding any skips, the hierarchy is correct.
        return true;
    }
    
}
