<?php

/**
 * @package     PublishPress\Checklists
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */


namespace PublishPress\ChecklistsPro\PublishTime\Requirement;

use PublishPress\Checklists\Core\Requirement\Base_time;

defined('ABSPATH') or die('No direct script access allowed.');

class PublishTimeExact extends Base_time
{
    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'publish_time_exact';

    /**
     * The name of the group, used for the tabs
     *
     * @var string
     */
    public $group = 'publish_date_time';

    /**
     * Position/order in the checklist group
     *
     * @var int
     */
    public $position = 161;

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        // Get the configured time value
        $option_name_time = $this->name . '_value';
        $time_value = '';
        if (isset($this->module->options->{$option_name_time}[$this->post_type])) {
            $time_value = $this->module->options->{$option_name_time}[$this->post_type];
            // Convert 24-hour format to 12-hour format with AM/PM
            if ($time_value) {
                $timestamp = strtotime($time_value);
                $time_value = date('h:i A', $timestamp);
            }
        }

        $this->lang['label']          = sprintf(
            __('Publish time should be at %s', 'publishpress-checklists-pro'),
            $time_value ?: __('(not set)', 'publishpress-checklists-pro')
        );
        $this->lang['label_settings'] = __('Publish time should be at a specific time', 'publishpress-checklists-pro');
    }

    /**
     * Checks if the requirement is complete for the given post.
     *
     * @param int $post_id
     *
     * @return bool
     */
    public function is_complete($post_id)
    {
        // Use the parent logic: valid time present in configured field
        return parent::is_complete($post_id);
    }

    /**
     * Include configured time value in localized requirements array.
     *
     * @param array $requirements
     * @param stdClass $post
     * @return array
     */
    public function filter_requirements_list($requirements, $post)
    {
        if ($post->post_type !== $this->post_type) {
            return $requirements;
        }
        // Call parent to register basic requirement
        $requirements = parent::filter_requirements_list($requirements, $post);
        if (isset($requirements[$this->name])) {
            $option_name_value = $this->name . '_value';
            $configured_time = '';
            if (isset($this->module->options->{$option_name_value}[$this->post_type])) {
                $configured_time = $this->module->options->{$option_name_value}[$this->post_type];
            }
            // Pass time value as array for JS
            $requirements[$this->name]['value'] = [$configured_time];
        }
        return $requirements;
    }
}
