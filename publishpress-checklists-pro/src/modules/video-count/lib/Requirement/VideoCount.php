<?php

/**
 * @package     PublishPress\Checklists
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */


namespace PublishPress\ChecklistsPro\VideoCount\Requirement;

use PublishPress\Checklists\Core\Requirement\Base_counter;

defined('ABSPATH') or die('No direct script access allowed.');

class VideoCount extends Base_counter
{

    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'video_count';

    /**
     * The name of the group, used for the tabs
     * 
     * @var string
     */
    public $group = 'audio_video';

    /**
     * @var int
     */
    public $position = 111;

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        $this->lang['label']                = __('Number of video items in content', 'publishpress-checklists-pro');
        $this->lang['label_settings']       = __('Number of video items in content', 'publishpress-checklists-pro');
        $this->lang['label_min_singular']   = __('Minimum of %d video items in content', 'publishpress-checklists-pro');
        $this->lang['label_min_plural']     = __('Minimum of %d video items in content', 'publishpress-checklists-pro');
        $this->lang['label_max_singular']   = __('Maximum of %d video items in content', 'publishpress-checklists-pro');
        $this->lang['label_max_plural']     = __('Maximum of %d video items in content', 'publishpress-checklists-pro');
        $this->lang['label_exact_singular'] = __('%d video items in content', 'publishpress-checklists-pro');
        $this->lang['label_exact_plural']   = __('%d video items in content', 'publishpress-checklists-pro');
        $this->lang['label_between']        = __('Between %d and %d video items in content', 'publishpress-checklists-pro');
    }

    /**
     * Count videos in content
     *
     * @param string $content
     * @return int
     */
    private function count_videos($content)
    {
        $content = is_string($content) ? $content : '';

        $patterns = [
            '/<video[\s\S]*?<\/video>/is',

            '/<iframe[^>]+src\s*=\s*["\'][^"\']*(?:youtube\.com|youtu\.be|player\.vimeo\.com|vimeo\.com|dailymotion\.com)[^"\']*["\'][^>]*>[\s\S]*?<\/iframe>/is',

            '/<figure[^>]+wp-block-embed[^>]*is-provider-(?:youtube|vimeo)[^>]*>[\s\S]*?<\/figure>/is',

            '/<a[^>]+href\s*=\s*["\'][^"\']+\.(?:mp4|mov|webm|m4v|ogg|ogv)(?:\?[^"\']*)?["\'][^>]*>/i',
        ];

        $count = 0;
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            $count += count($matches[0]);
        }

        return $count;
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
        $video_count = $this->count_videos($content);

        $min = $option_value[0];
        $max = $option_value[1];

        // Check if video count is within the specified range
        return ($video_count >= $min) && ($max == 0 || $video_count <= $max);
    }
}
