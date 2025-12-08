<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/init-stream/merge-ai-params.php
// Status: MODIFIED

namespace WPAICG\ContentWriter\Ajax\Actions\InitStream;

use WPAICG\AIPKIT_AI_Settings;
use WPAICG\ContentWriter\Ajax\Actions\Shared;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/../shared/prepare-ai-params.php';

/**
 * Merges global AI settings with form-specific parameter overrides.
 * This is specific to the stream initializer, which needs the fully merged
 * parameters before caching.
 *
 * @param array $settings The sanitized settings from the request.
 * @return array The final merged AI parameters.
 */
function merge_ai_params_logic(array $settings): array
{
    $ai_params_from_form = Shared\prepare_ai_params_logic($settings);
    $global_ai_params = AIPKIT_AI_Settings::get_ai_parameters();
    return array_merge($global_ai_params, $ai_params_from_form);
}
