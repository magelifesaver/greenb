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
 * Class AIOHeadlineScore
 * 
 * Implements a requirement to check the All in One SEO Headline Analyzer score for a post
 * Note: This only works in the Block Editor (Gutenberg)
 */
class AIOHeadlineScore extends Base_counter
{
    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'all_in_one_seo_headline_score';

    /**
     * The name of the group, used for the tabs
     * 
     * @var string
     */
    public $group = 'all_in_one_seo';

    /**
     * @var int
     */
     public $position = 150;

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        $this->lang['label']                = __('All in One SEO Headline Score', 'publishpress-checklists-pro');
        $this->lang['label_settings']       = __('All in One SEO Headline Score', 'publishpress-checklists-pro');
        $this->lang['label_min_singular']   = __('Minimum All in One SEO Headline Score: %d', 'publishpress-checklists-pro');
        $this->lang['label_min_plural']     = __('Minimum All in One SEO Headline Score: %d', 'publishpress-checklists-pro');
        $this->lang['label_max_singular']   = __('Maximum All in One SEO Headline Score: %d', 'publishpress-checklists-pro');
        $this->lang['label_max_plural']     = __('Maximum All in One SEO Headline Score: %d', 'publishpress-checklists-pro');
        $this->lang['label_exact_singular'] = __('Exact All in One SEO Headline Score: %d', 'publishpress-checklists-pro');
        $this->lang['label_exact_plural']   = __('Exact All in One SEO Headline Score: %d', 'publishpress-checklists-pro');
        $this->lang['label_between']        = __('All in One SEO Headline Score between %d and %d', 'publishpress-checklists-pro');
    }

    /**
     * Returns the current status of the requirement.
     * 
     * Note: The headline score is not stored in the database and is only available via JavaScript.
     * The actual validation happens in the JavaScript file that monitors the headline score element.
     *
     * @param \stdClass $post
     * @param mixed $option_value
     *
     * @return mixed
     */
    public function get_current_status($post, $option_value)
    {
        // Since the headline score is only available via JavaScript and not stored in the database,
        // we'll return false here and let the JavaScript handle the validation.
        // This is because the score is calculated in real-time by the All in One SEO plugin.
        return false;
    }
}
