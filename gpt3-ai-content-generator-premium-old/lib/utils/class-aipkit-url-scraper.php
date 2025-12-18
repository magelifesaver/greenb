<?php

namespace WPAICG\Lib\Utils;

use DOMDocument;
use DOMXPath;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Url_Scraper
 * Utility class for scraping the main content from a given URL.
 */
class AIPKit_Url_Scraper
{
    /**
     * Scrapes the main textual content from a URL.
     *
     * @param string $url The URL to scrape.
     * @return string|WP_Error The scraped and cleaned text content, or a WP_Error on failure.
     */
    public function scrape(string $url): string|WP_Error
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', __('The provided URL is not valid.', 'gpt3-ai-content-generator'));
        }

        $response = wp_remote_get($url, [
            'timeout' => 20, // 20-second timeout
            'user-agent' => 'Mozilla/5.0 (compatible; AIPKitBot/1.0; +https://aipower.org/bot)',
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('fetch_failed', __('Failed to fetch URL: ', 'gpt3-ai-content-generator') . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return new WP_Error('empty_body', __('The URL returned an empty response body.', 'gpt3-ai-content-generator'));
        }

        // Suppress errors from malformed HTML
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $body);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);

        // Find main content container
        $main_content_node = $xpath->query('//main | //article | //*[@role="main"]')->item(0);

        if (!$main_content_node) {
            // Fallback to body if no specific main container is found
            $main_content_node = $xpath->query('//body')->item(0);
        }

        if (!$main_content_node) {
            return new WP_Error('no_content_found', __('Could not identify the main content area of the page.', 'gpt3-ai-content-generator'));
        }

        // Remove unwanted elements
        $elements_to_remove_queries = [
            '//nav', '//header', '//footer', '//script', '//style', '//aside',
            '//*[contains(@class, "sidebar")]', '//*[contains(@class, "comment")]',
            '//*[contains(@id, "sidebar")]', '//*[contains(@id, "comment")]',
        ];

        foreach ($elements_to_remove_queries as $query) {
            $nodes_to_remove = $xpath->query($query, $main_content_node);
            foreach ($nodes_to_remove as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        $text = $main_content_node->textContent;

        // Clean up the text
        $text = preg_replace('/\s+/', ' ', $text); // Collapse multiple whitespace characters
        $text = trim($text);

        if (empty($text)) {
            return new WP_Error('no_text_extracted', __('Could not extract any meaningful text from the main content area.', 'gpt3-ai-content-generator'));
        }

        // Limit length to a reasonable size for context
        $max_length = 15000; // Approx 3000-4000 tokens
        if (mb_strlen($text) > $max_length) {
            $text = mb_substr($text, 0, $max_length) . '... [content truncated]';
        }

        return $text;
    }
}