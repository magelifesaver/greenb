<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/featuremanager/get-addon-status.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\Shortcode\FeatureManagerMethods;

use WPAICG\aipkit_dashboard;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves the status of relevant addons and the user's plan.
 *
 * @return array An array containing addon statuses:
 *               'starters_addon_active', 'pdf_addon_active', 'tts_addon_active', 'is_pro_plan'.
 */
function get_addon_status_logic(): array {
    $starters_addon_active = false;
    $pdf_addon_active      = false;
    $tts_addon_active      = false;
    $is_pro_plan           = false;

    if (class_exists(aipkit_dashboard::class)) {
        $starters_addon_active = aipkit_dashboard::is_addon_active('conversation_starters');
        $pdf_addon_active      = aipkit_dashboard::is_addon_active('pdf_download');
        $tts_addon_active      = aipkit_dashboard::is_addon_active('voice_playback');
        $is_pro_plan           = aipkit_dashboard::is_pro_plan();
    }

    return [
        'starters_addon_active' => $starters_addon_active,
        'pdf_addon_active'      => $pdf_addon_active,
        'tts_addon_active'      => $tts_addon_active,
        'is_pro_plan'           => $is_pro_plan,
    ];
}