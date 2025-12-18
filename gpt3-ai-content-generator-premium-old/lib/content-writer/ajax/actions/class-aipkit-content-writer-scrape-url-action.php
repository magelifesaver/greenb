<?php

namespace WPAICG\ContentWriter\Ajax\Actions;

use WPAICG\ContentWriter\Ajax\AIPKit_Content_Writer_Base_Ajax_Action;
use WPAICG\Lib\Utils\AIPKit_Url_Scraper;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the AJAX action for scraping a URL for the Content Writer.
 */
class AIPKit_Content_Writer_Scrape_Url_Action extends AIPKit_Content_Writer_Base_Ajax_Action
{
    public function handle()
    {
        // --- MODIFICATION: Manual permission and flexible nonce check ---
        if (!\WPAICG\AIPKit_Role_Manager::user_can_access_module('content-writer') && !\WPAICG\AIPKit_Role_Manager::user_can_access_module('autogpt')) {
            $this->send_wp_error(new WP_Error('permission_denied', __('You do not have permission to use this feature.', 'gpt3-ai-content-generator'), ['status' => 403]));
            return;
        }
        $nonce = isset($_POST['_ajax_nonce']) ? sanitize_key(wp_unslash($_POST['_ajax_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'aipkit_content_writer_nonce') && !wp_verify_nonce($nonce, 'aipkit_nonce') && !wp_verify_nonce($nonce, 'aipkit_automated_tasks_manage_nonce')) {
            $this->send_wp_error(new WP_Error('nonce_failure', __('Security check failed.', 'gpt3-ai-content-generator'), ['status' => 403]));
            return;
        }
        // --- END MODIFICATION ---

        if (!class_exists('\WPAICG\Lib\Utils\AIPKit_Url_Scraper')) {
            $this->send_wp_error(new WP_Error('missing_scraper', __('The URL scraping component is unavailable. This is a Pro feature.', 'gpt3-ai-content-generator')), 500);
            return;
        }

        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';

        if (empty($url)) {
            $this->send_wp_error(new WP_Error('missing_url', __('Please provide a URL to scrape.', 'gpt3-ai-content-generator')), 400);
            return;
        }

        $scraper = new AIPKit_Url_Scraper();
        $result = $scraper->scrape($url);

        if (is_wp_error($result)) {
            $this->send_wp_error($result);
        } else {
            wp_send_json_success(['scraped_text' => $result]);
        }
    }
}
