<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/template-manager/calculate-schedule-datetime.php

namespace WPAICG\ContentWriter\TemplateManagerMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
* Calculates a MySQL DATETIME string from date and time parts.
*
* @param string $date_str The date string (Y-m-d).
* @param string $time_str The time string (H:i).
* @return string|null The formatted datetime string or null if input is invalid.
*/
function calculate_schedule_datetime_logic(string $date_str, string $time_str): ?string
{
    if (empty($date_str) || empty($time_str)) {
        return null;
    }
    try {
        $datetime = new \DateTime("{$date_str} {$time_str}", wp_timezone());
        return $datetime->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
        return null;
    }
}
