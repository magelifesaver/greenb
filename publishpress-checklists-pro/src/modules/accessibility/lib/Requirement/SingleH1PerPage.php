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

class SingleH1PerPage extends Base_simple
{

    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'single_h1_per_page';

    /**
     * The name of the group, used for the tabs
     * 
     * @var string
     */
    public $group = 'accessibility';

    /**
     * @var int
     */
    public $position = 143;

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        $this->lang['label']                = __('Only one H1 tag in content', 'publishpress-checklists-pro');
        $this->lang['label_settings']       = __('Only one H1 tag in content', 'publishpress-checklists-pro');
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

        // If there is no content, the requirement is met by default (0 H1s).
        if (empty(trim($content))) {
            return true;
        }

        // Regex to find all <h1> tags. 'i' for case-insensitive.
        // We only need to count them, not their content.
        $h1_regex = '/<h1[^>]*>.*?<\/h1>/i';
        $h1_count = preg_match_all($h1_regex, $content);

        // The requirement is met if there are 0 or 1 H1 tags.
        // It fails if there are 2 or more H1 tags.
        if ($h1_count <= 1) {
            return true;
        } else {
            // error_log('PublishPress Checklists Pro: Found ' . $h1_count . ' H1 tags. Only one is allowed per page.');
            return false;
        }
    }
    
}
