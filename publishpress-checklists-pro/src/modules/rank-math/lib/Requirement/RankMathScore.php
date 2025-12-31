<?php

/**
 * @package     PublishPress\Checklists
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */


namespace PublishPress\ChecklistsPro\RankMath\Requirement;

use PublishPress\Checklists\Core\Requirement\Base_counter;

defined('ABSPATH') or die('No direct script access allowed.');

class RankMathScore extends Base_counter
{


    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'rank_math_score';

    /**
     * The name of the group, used for the tabs
     * 
     * @var string
     */
    public $group = 'rank_math';

    /**
     * @var int
     */
    public $position = 141;

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        $this->lang['label']                = __('Rank Math SEO Score', 'publishpress-checklists-pro');
        $this->lang['label_settings']       = __('Rank Math SEO Score', 'publishpress-checklists-pro');
        $this->lang['label_min_singular']   = __('Minimum Rank Math SEO score: %d', 'publishpress-checklists-pro');
        $this->lang['label_min_plural']     = __('Minimum Rank Math SEO score: %d', 'publishpress-checklists-pro');
        $this->lang['label_max_singular']   = __('Maximum Rank Math SEO score: %d', 'publishpress-checklists-pro');
        $this->lang['label_max_plural']     = __('Maximum Rank Math SEO score: %d', 'publishpress-checklists-pro');
        $this->lang['label_exact_singular'] = __('Exact Rank Math SEO score: %d', 'publishpress-checklists-pro');
        $this->lang['label_exact_plural']   = __('Exact Rank Math SEO score: %d', 'publishpress-checklists-pro');
        $this->lang['label_between']        = __('Rank Math SEO scores between %d and %d', 'publishpress-checklists-pro');
    }

    /**
     * Get the Rank Math SEO score for the post
     *
     * @param int $post_id
     * @return int
     */
    private function count_rank_math_score($post_id)
    {
        // Get the Rank Math score from post meta
        $score = get_post_meta($post_id, 'rank_math_seo_score', true);
        
        // Return 0 if no score is found, otherwise return the integer value
        return empty($score) ? 0 : intval($score);
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
        $rank_math_score = $this->count_rank_math_score($post->ID);

        $min = $option_value[0];
        $max = $option_value[1];

        // Check if the SEO score is within the specified range
        return ($rank_math_score >= $min) && ($max == 0 || $rank_math_score <= $max);
    }

    
}
