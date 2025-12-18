<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/ajax/migration/analysis/get-old-custom-prompts.php
// Status: NEW FILE

namespace WPAICG\Admin\Ajax\Migration\Analysis;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gets details about old custom prompts from the options table.
 *
 * @return array ['count' => int, 'prompts' => array, 'summary' => string, 'details' => array]
 */
function get_old_custom_prompts_logic(): array
{
    $prompts = [];
    $count = 0;

    // AutoGPT / Bulk Content Writer Prompt
    $autogpt_prompt = get_option('wpaicg_custom_prompt_auto', '');
    if (!empty($autogpt_prompt)) {
        $prompts['autogpt'] = [
            'label' => __('AutoGPT / Content Writer Prompt', 'gpt3-ai-content-generator'),
            'value' => $autogpt_prompt
        ];
        $count++;
    }

    // Custom Image Prompt
    $image_prompt = get_option('wpaicg_custom_image_prompt', '');
    if (!empty($image_prompt)) {
        $prompts['image'] = [
            'label' => __('In-Content Image Prompt', 'gpt3-ai-content-generator'),
            'value' => $image_prompt
        ];
        $count++;
    }

    // Custom Featured Image Prompt
    $featured_image_prompt = get_option('wpaicg_custom_featured_image_prompt', '');
    if (!empty($featured_image_prompt)) {
        $prompts['featured_image'] = [
            'label' => __('Featured Image Prompt', 'gpt3-ai-content-generator'),
            'value' => $featured_image_prompt
        ];
        $count++;
    }

    return [
        'count' => $count,
        'prompts' => $prompts,
        /* translators: %d is the number of custom prompts found */
        'summary' => sprintf(_n('%d custom prompt found.', '%d custom prompts found.', $count, 'gpt3-ai-content-generator'), $count),
        'details' => array_keys($prompts) // just for logging maybe
    ];
}