<?php

/**
 * @package     PublishPress\Checklists
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */


namespace PublishPress\ChecklistsPro\AudioCount\Requirement;

use PublishPress\Checklists\Core\Requirement\Base_counter;

defined('ABSPATH') or die('No direct script access allowed.');

class AudioCount extends Base_counter
{

    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'audio_count';

    /**
     * The name of the group, used for the tabs
     * 
     * @var string
     */
    public $group = 'audio_video';

    /**
     * @var int
     */
    public $position = 109;

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        $this->lang['label']                = __('Number of audio items in content', 'publishpress-checklists-pro');
        $this->lang['label_settings']       = __('Number of audio items in content', 'publishpress-checklists-pro');
        $this->lang['label_min_singular']   = __('Minimum of %d audio items in content', 'publishpress-checklists-pro');
        $this->lang['label_min_plural']     = __('Minimum of %d audio items in content', 'publishpress-checklists-pro');
        $this->lang['label_max_singular']   = __('Maximum of %d audio items in content', 'publishpress-checklists-pro');
        $this->lang['label_max_plural']     = __('Maximum of %d audio items in content', 'publishpress-checklists-pro');
        $this->lang['label_exact_singular'] = __('%d audio items in content', 'publishpress-checklists-pro');
        $this->lang['label_exact_plural']   = __('%d audio items in content', 'publishpress-checklists-pro');
        $this->lang['label_between']        = __('Between %d and %d audio items in content', 'publishpress-checklists-pro');
    }

    /**
     * Count audio items in content.
     * Supports HTML <audio> tags and WordPress [audio] shortcodes.
     *
     * @param string $content
     * @return int
     */
    private function count_audios($content)
    {
        $content = is_string($content) ? $content : '';

        // Count <audio> HTML tags
        preg_match_all('/<audio\s[^>]*>(?:.*?)<\/audio>/is', $content, $htmlAudioMatches);
        $count = count($htmlAudioMatches[0]);

        // Count WordPress [audio] shortcodes
        preg_match_all('/\[audio[^\]]*\]/i', $content, $shortcodeMatches);
        $count += count($shortcodeMatches[0]);

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
        $audio_count = $this->count_audios($content);

        $min = $option_value[0];
        $max = $option_value[1];

        // Check if audio count is within the specified range
        return ($audio_count >= $min) && ($max == 0 || $audio_count <= $max);
    }
}
