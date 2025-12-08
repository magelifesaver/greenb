<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/analysis/get-old-woocommerce-prompts.php
// Status: NEW FILE

namespace WPAICG\Admin\Ajax\Migration\Analysis;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gets details about old WooCommerce custom prompts from the options table.
 *
 * @return array ['count' => int, 'prompts' => array, 'summary' => string, 'details' => array]
 */
function get_old_woocommerce_prompts_logic(): array
{
    $prompts = [];
    $count = 0;
    $details = [];

    $woo_prompts_options = [
        'wpaicg_woo_custom_prompt_title' => __('WooCommerce Title Prompt', 'gpt3-ai-content-generator'),
        'wpaicg_woo_custom_prompt_short' => __('WooCommerce Short Description Prompt', 'gpt3-ai-content-generator'),
        'wpaicg_woo_custom_prompt_description' => __('WooCommerce Full Description Prompt', 'gpt3-ai-content-generator'),
        'wpaicg_woo_custom_prompt_meta' => __('WooCommerce Meta Description Prompt', 'gpt3-ai-content-generator'),
        'wpaicg_woo_custom_prompt_keywords' => __('WooCommerce Tags Prompt', 'gpt3-ai-content-generator'),
        'wpaicg_woo_custom_prompt_focus_keyword' => __('WooCommerce Focus Keyword Prompt', 'gpt3-ai-content-generator'),
    ];

    foreach ($woo_prompts_options as $option_name => $label) {
        $prompt_value = get_option($option_name, '');
        if (!empty($prompt_value)) {
            $prompts[$option_name] = [
                'label' => $label,
                'value' => wp_unslash($prompt_value)
            ];
            $count++;
            $details[] = 'WooCommerce Prompt found: ' . $label;
        }
    }
    /* translators: %d is the number of custom WooCommerce prompts found */
    $summary = sprintf(_n('%d custom WooCommerce prompt found.', '%d custom WooCommerce prompts found.', $count, 'gpt3-ai-content-generator'), $count);

    return [
        'count' => $count,
        'prompts' => $prompts,
        'summary' => $summary,
        'details' => $details
    ];
}
