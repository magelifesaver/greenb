<?php

/**
 * @package     PublishPress\Checklists
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */


namespace PublishPress\ChecklistsPro\PublishTime\Requirement;

use PublishPress\Checklists\Core\Requirement\Base_simple;

defined('ABSPATH') or die('No direct script access allowed.');

class PublishTimeFuture extends Base_simple
{

    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'publish_time_future';

    /**
     * The name of the group, used for the tabs
     * 
     * @var string
     */
    public $group = 'publish_date_time';

    /**
     * @var int
     */
    public $position = 107;

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        $this->lang['label']          = __('Publish time should be in the future', 'publishpress-checklists-pro');
        $this->lang['label_settings'] = __('Publish time should be in the future', 'publishpress-checklists-pro');
    }

    /**
     * Returns the current status of the requirement.
     *
     * @param stdClass $post
     * @param mixed $option_value
     *
     * @return bool
     */
    public function get_current_status($post, $option_value)
    {
        // Get the post's scheduled date in the site's timezone
        $post_date = get_post_datetime($post);
        
        if (empty($post_date)) {
            return false;
        }
        
        // Get current time in the site's timezone
        $current_time = current_datetime();
        
        // Check if the post date is in the future
        return $post_date > $current_time;
    }
}
