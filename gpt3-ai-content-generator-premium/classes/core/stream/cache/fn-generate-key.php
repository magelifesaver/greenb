<?php
// File: classes/core/stream/cache/fn-generate-key.php

namespace WPAICG\Core\Stream\Cache;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Generates a unique cache key.
 *
 * @return string The generated cache key.
 */
function generate_key_logic(): string {
    return 'aipkit_sse_' . wp_generate_password(32, false, false);
}