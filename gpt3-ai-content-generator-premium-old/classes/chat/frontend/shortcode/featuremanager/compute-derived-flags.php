<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/featuremanager/compute-derived-flags.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\Shortcode\FeatureManagerMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Computes derived feature flags based on already determined flags.
 *
 * @param array $current_flags An array of already computed flags.
 *                             Expected keys: 'popup_enabled', 'enable_fullscreen',
 *                                            'enable_download', 'sidebar_ui_enabled'.
 * @return array An array containing derived flags, e.g., 'show_header'.
 */
function compute_derived_flags_logic(array $current_flags): array {
    $derived_flags = [];

    $derived_flags['show_header'] = ($current_flags['popup_enabled'] ?? false) ||
                                  ($current_flags['enable_fullscreen'] ?? false) ||
                                  ($current_flags['enable_download'] ?? false) ||
                                  ($current_flags['sidebar_ui_enabled'] ?? false);
    return $derived_flags;
}