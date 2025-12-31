<?php

/**
 * @package     PublishPress\Checklists
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */


namespace PublishPress\ChecklistsPro\NoHeadingTags\Requirement;

use PublishPress\Checklists\Core\Requirement\Base_multiple;

defined('ABSPATH') or die('No direct script access allowed.');

class NoHeadingTags extends Base_multiple
{
    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'no_heading_tags';

    /**
     * The name of the group, used for the tabs
     *
     * @var string
     */
    public $group = 'content';

    /**
     * @var int
     */
    public $position = 108;

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        $this->lang['label']                  = __('Avoid using %s tags in content', 'publishpress-checklists-pro');
        $this->lang['label_settings']         = __('Avoid heading tags in content', 'publishpress-checklists-pro');
        $this->lang['label_option_description'] = __('Content should not contain any of the selected heading tags.', 'publishpress-checklists-pro');
    }

    /**
     * Get the list of options to display in the settings field
     *
     * @return array
     */
    protected function get_setting_drop_down_labels()
    {
        return [
            'h1' => __('H1', 'publishpress-checklists-pro'),
            'h2' => __('H2', 'publishpress-checklists-pro'),
            'h3' => __('H3', 'publishpress-checklists-pro'),
            'h4' => __('H4', 'publishpress-checklists-pro'),
            'h5' => __('H5', 'publishpress-checklists-pro'),
            'h6' => __('H6', 'publishpress-checklists-pro'),
        ];
    }

    /**
     * Check if content contains any of the prohibited heading tags
     *
     * @param string $content
     * @param array $prohibited_tags
     * @return bool
     */
    private function check_for_prohibited_tags($content, $prohibited_tags)
    {
        $content = is_string($content) ? $content : '';
        if (empty($prohibited_tags)) {
            return true;
        }

        foreach ($prohibited_tags as $tag) {
            $pattern = '/<' . $tag . '[^>]*>.*?<\/' . $tag . '>/is';
            if (preg_match($pattern, $content)) {
                return false;
            }
        }

        return true;
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
        if (empty($option_value)) {
            return true;
        }

        $content = (property_exists($post, 'post_content') && is_string($post->post_content)) ? $post->post_content : '';
        return $this->check_for_prohibited_tags($content, $option_value);
    }

    /**
     * Add the requirement to the list to be displayed in the meta box.
     *
     * @param array $requirements
     * @param \stdClass $post
     *
     * @return array
     */
    public function filter_requirements_list($requirements, $post)
    {
        // Check if it is a compatible post type. If not, ignore this requirement.
        if ($post->post_type !== $this->post_type) {
            return $requirements;
        }

        $requirements = parent::filter_requirements_list($requirements, $post);

        // If not enabled or requirement not set, bypass the method
        if (!isset($requirements[$this->name])) {
            return $requirements;
        }

        // Option names
        $option_name_multiple = $this->name . '_' . $this->field_name;

        // Get the value
        $option_value = array();
        if (isset($this->module->options->{$option_name_multiple}[$this->post_type])) {
            $option_value = $this->module->options->{$option_name_multiple}[$this->post_type];
        }

        if (!empty($option_value)) {
            // Get the heading tag labels
            $labels = $this->get_setting_drop_down_labels();
            $selected_labels = [];

            foreach ($option_value as $tag) {
                if (isset($labels[$tag])) {
                    $selected_labels[] = $labels[$tag];
                }
            }

            if (!empty($selected_labels)) {
                $formatted_tags = implode(', ', $selected_labels);
                $requirements[$this->name]['label'] = sprintf($this->lang['label'], $formatted_tags);
            }
        }

        return $requirements;
    }
}
