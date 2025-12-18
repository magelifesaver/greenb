<?php

// File: classes/core/stream/vector/build-context/normalize-embedding-provider.php
// Status: NEW FILE

namespace WPAICG\Core\Stream\Vector\BuildContext;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Normalizes the embedding provider key to a standard name.
 *
 * @param string $embedding_provider_key The key from settings (e.g., 'openai', 'google').
 * @return string The normalized provider name (e.g., 'OpenAI', 'Google').
 */
function normalize_embedding_provider_logic(string $embedding_provider_key): string
{
    $provider_map = ['openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure'];
    return $provider_map[strtolower($embedding_provider_key)] ?? ucfirst($embedding_provider_key);
}
