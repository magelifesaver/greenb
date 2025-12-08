<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/bootstrap-conversation-helper.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI;

use WPAICG\AIPKit_Providers; // For dependency check within the logic file

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load the method logic file
require_once __DIR__ . '/prepare-parameters-and-history.php';

/**
 * Helper class to manage OpenAI stateful conversation parameters and history.
 * Original logic for methods is now in separate files within the 'Methods' namespace.
 */
class OpenAIStatefulConversationHelper {

    public static function prepare_parameters_and_history(
        array $ai_params,
        array $history,
        array $bot_settings,
        ?string $frontend_previous_openai_response_id
    ): array {
        return \WPAICG\Core\Providers\OpenAI\Methods\prepare_parameters_and_history_logic($ai_params, $history, $bot_settings, $frontend_previous_openai_response_id);
    }
}