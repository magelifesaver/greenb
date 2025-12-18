<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/cron/event-processor/trigger/module/url-task-generator.php
// Status: NEW FILE

namespace WPAICG\AutoGPT\Cron\EventProcessor\Trigger\Modules;

use WPAICG\aipkit_dashboard;
use WPAICG\Lib\Utils\AIPKit_Url_Scraper;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates items to be queued by scraping a list of URLs.
 *
 * @param int $task_id The ID of the task.
 * @param array $task_config The configuration of the task.
 * @return array|WP_Error An array containing 'topics' and 'contexts' or a WP_Error on failure.
 */
function url_mode_generate_items_logic(int $task_id, array $task_config): array|WP_Error
{
    if (!aipkit_dashboard::is_pro_plan() || !class_exists(AIPKit_Url_Scraper::class)) {
        return new WP_Error('url_feature_unavailable', __('URL scraping is a Pro feature or its components are missing.', 'gpt3-ai-content-generator'), ['status' => 403]);
    }

    $topics = [];
    $contexts = [];
    $url_list = array_filter(array_map('trim', explode("\n", $task_config['url_list'] ?? '')));

    if (empty($url_list)) {
        return new WP_Error('no_urls_provided', __('No URLs were provided for scraping.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    $scraper = new AIPKit_Url_Scraper();

    foreach ($url_list as $url) {
        $scraped_content = $scraper->scrape($url);
        if (!is_wp_error($scraped_content)) {
            $topics[] = ['title' => $url, 'link' => $url]; // Mimic RSS item structure
            $contexts[$url] = $scraped_content;
        }
    }

    return ['topics' => $topics, 'contexts' => $contexts];
}
