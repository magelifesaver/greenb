<?php

// File: classes/autogpt/cron/queuer/helpers/build-index-item-config.php

namespace WPAICG\AutoGPT\Cron\Queuer\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Constructs the item_config array for a content indexing task.
 *
 * @param array $task_config The configuration array of the parent task.
 * @return array The specific configuration for the queue item.
 */
function build_index_item_config_logic(array $task_config): array
{
    return [
        'target_store_id' => $task_config['target_store_id'] ?? '',
        'target_store_provider' => $task_config['target_store_provider'] ?? 'openai',
        'embedding_provider' => $task_config['embedding_provider'] ?? null,
        'embedding_model'    => $task_config['embedding_model'] ?? null,
    ];
}
