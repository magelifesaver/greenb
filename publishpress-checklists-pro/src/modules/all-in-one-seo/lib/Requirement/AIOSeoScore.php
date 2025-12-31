<?php

/**
 * @package     PublishPress\Checklists
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\ChecklistsPro\AllInOneSeo\Requirement;

use PublishPress\Checklists\Core\Requirement\Base_counter;

defined('ABSPATH') or die('No direct script access allowed.');

/**
 * Class AIOSeoScore
 * 
 * Implements a requirement to check the All in One SEO score for a post
 */
class AIOSeoScore extends Base_counter
{
    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'all_in_one_seo_score';

    /**
     * The name of the group, used for the tabs
     * 
     * @var string
     */
    public $group = 'all_in_one_seo';

    /**
     * @var int
     */
    public $position = 149;

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        $this->lang['label']                = __('All in One SEO Score', 'publishpress-checklists-pro');
        $this->lang['label_settings']       = __('All in One SEO Score', 'publishpress-checklists-pro');
        $this->lang['label_min_singular']   = __('Minimum All in One SEO score: %d', 'publishpress-checklists-pro');
        $this->lang['label_min_plural']     = __('Minimum All in One SEO score: %d', 'publishpress-checklists-pro');
        $this->lang['label_max_singular']   = __('Maximum All in One SEO score: %d', 'publishpress-checklists-pro');
        $this->lang['label_max_plural']     = __('Maximum All in One SEO score: %d', 'publishpress-checklists-pro');
        $this->lang['label_exact_singular'] = __('Exact All in One SEO score: %d', 'publishpress-checklists-pro');
        $this->lang['label_exact_plural']   = __('Exact All in One SEO score: %d', 'publishpress-checklists-pro');
        $this->lang['label_between']        = __('All in One SEO Score between %d and %d', 'publishpress-checklists-pro');
    }

    /**
     * Get the All in One SEO score for the post
     *
     * @param int $post_id
     * @return int
     */
    private function get_all_in_one_seo_score($post_id)
    {
        global $wpdb;
        
        // Get the table prefix
        $table_prefix = $wpdb->prefix;
        
        // Query the All in One SEO score from the database
        $score = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT seo_score FROM {$table_prefix}aioseo_posts WHERE post_id = %d",
                $post_id
            )
        );
        
        // Return 0 if no score is found, otherwise return the integer value
        return empty($score) ? 0 : intval($score);
    }

    /**
     * Returns the current status of the requirement.
     *
     * @param \stdClass $post
     * @param mixed $option_value
     *
     * @return mixed
     */
    public function get_current_status($post, $option_value)
    {
        $seo_score = $this->get_all_in_one_seo_score($post->ID);

        $min = $option_value[0];
        $max = $option_value[1];

        // Check if the SEO score is within the specified range
        return ($seo_score >= $min) && ($max == 0 || $seo_score <= $max);
    }
}
