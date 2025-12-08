<?php
// File: classes/core/providers/google/format-google-model-list.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

use WPAICG\Core\Providers\GoogleProviderStrategy; 

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_google_model_list private method of GoogleProviderStrategy.
 *
 * @param GoogleProviderStrategy $strategyInstance The instance of the strategy class (unused in static context but kept for consistency).
 * @param array $raw_models The array of raw model data from the API.
 * @return array Formatted list [['id' => ..., 'name' => ...]].
 */
function format_google_model_list_logic(GoogleProviderStrategy $strategyInstance, array $raw_models): array {
    
    $formatted = [];
    foreach ($raw_models as $model) {
        if (!is_array($model)) continue;
        $mId = $model['name'] ?? null;
        if (!empty($mId)) {
            $cleanId = (strpos($mId, 'models/') === 0) ? substr($mId, 7) : $mId;
            $supportedMethods = $model['supportedGenerationMethods'] ?? [];
            
            $formatted[] = [
                'id'       => $cleanId,
                'name'     => $model['displayName'] ?? $cleanId,
                'version'  => $model['version'] ?? '',
                'supportedGenerationMethods' => $supportedMethods,
            ];
        }
    }
    return $formatted;
}