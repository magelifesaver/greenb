<?php

/**
 * @package     PublishPress\Checklists
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */


namespace PublishPress\ChecklistsPro\ImageCount\Requirement;

use PublishPress\Checklists\Core\Requirement\Base_counter;

defined('ABSPATH') or die('No direct script access allowed.');

class ImageCount extends Base_counter
{

    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'image_count';

    /**
     * The name of the group, used for the tabs
     * 
     * @var string
     */
    public $group = 'images';

    /**
     * @var int
     */
    public $position = 101;

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        $this->lang['label']                = __('Number of images in content', 'publishpress-checklists-pro');
        $this->lang['label_settings']       = __('Number of images in content', 'publishpress-checklists-pro');
        $this->lang['label_min_singular']   = __('Minimum of %d image in content', 'publishpress-checklists-pro');
        $this->lang['label_min_plural']     = __('Minimum of %d images in content', 'publishpress-checklists-pro');
        $this->lang['label_max_singular']   = __('Maximum of %d image in content', 'publishpress-checklists-pro');
        $this->lang['label_max_plural']     = __('Maximum of %d images in content', 'publishpress-checklists-pro');
        $this->lang['label_exact_singular'] = __('%d image in content', 'publishpress-checklists-pro');
        $this->lang['label_exact_plural']   = __('%d images in content', 'publishpress-checklists-pro');
        $this->lang['label_between']        = __('Between %d and %d images in content', 'publishpress-checklists-pro');
    }

    /**
     * Count images in content
     *
     * @param string $content
     * @return int
     */
    private function count_images($content)
    {
        $content = is_string($content) ? $content : '';
        preg_match_all('/<img\s[^>]*>/i', $content, $matches);
        return count($matches[0]);
    }

    /**
     * Returns the current status of the requirement.
     *
     * @param stdClass $post
     * @param mixed $option_value
     *
     * @return mixed
     */
    public function get_current_status($post, $option_value)
    {
        $content = (property_exists($post, 'post_content') && is_string($post->post_content)) ? $post->post_content : '';
        $image_count = $this->count_images($content);

        $min = $option_value[0];
        $max = $option_value[1];

        // Check if image count is within the specified range
        return ($image_count >= $min) && ($max == 0 || $image_count <= $max);
    }
}
