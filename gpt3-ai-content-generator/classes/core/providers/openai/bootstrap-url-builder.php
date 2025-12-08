<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/bootstrap-url-builder.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load the method logic file
require_once __DIR__ . '/build.php';

/**
 * Handles building API URLs specific to the OpenAI provider.
 * Original logic for methods is now in separate files within the 'Methods' namespace.
 */
class OpenAIUrlBuilder {

    const MODERATION_ENDPOINT = '/moderations';
    const SPEECH_ENDPOINT = '/audio/speech';
    const TRANSCRIPTION_ENDPOINT = '/audio/transcriptions';
    const IMAGES_ENDPOINT = '/images/generations';
    const FILES_ENDPOINT = '/files';
    const EMBEDDINGS_ENDPOINT = '/embeddings';
    const VECTOR_STORES_ENDPOINT = '/vector_stores';

    public static function build(string $operation, array $params): string|WP_Error {
        return \WPAICG\Core\Providers\OpenAI\Methods\build_logic_for_url_builder($operation, $params);
    }
}