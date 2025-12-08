<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/save-post/prepare-scheduled-post.php
// Status: NEW FILE

namespace WPAICG\ContentWriter\Ajax\Actions\SavePost;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modifies the post array for scheduling if a future date/time is provided for a 'publish' status.
 *
 * @param array &$postarr Reference to the post array for wp_insert_post.
 * @param array $data The sanitized post data.
 * @return void
 */
function prepare_scheduled_post_logic(array &$postarr, array $data): void
{
    if ($data['post_status'] === 'publish' && !empty($data['schedule_date']) && !empty($data['schedule_time'])) {
        $schedule_datetime_str = $data['schedule_date'] . ' ' . $data['schedule_time'] . ':00';
        $schedule_timestamp_gmt = get_gmt_from_date($schedule_datetime_str);
        $current_timestamp_gmt = current_time('timestamp', true);

        if (strtotime($schedule_timestamp_gmt) > $current_timestamp_gmt) {
            $postarr['post_status'] = 'future';
            $postarr['post_date'] = get_date_from_gmt($schedule_timestamp_gmt, 'Y-m-d H:i:s');
            $postarr['post_date_gmt'] = $schedule_timestamp_gmt;
        }
    }
}
