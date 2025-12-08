<?php

// File: classes/core/stream/vector/build-context/check-prerequisites.php
// Status: NEW FILE

namespace WPAICG\Core\Stream\Vector\BuildContext;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Checks the prerequisites for building vector search context.
 *
 * @param bool $vector_store_enabled
 * @param string $user_message
 * @param \WPAICG\Core\AIPKit_AI_Caller|null $ai_caller
 * @param \WPAICG\Vector\AIPKit_Vector_Store_Manager|null $vector_store_manager
 * @return bool True if prerequisites are met, false otherwise.
 */
function check_prerequisites_logic(
    bool $vector_store_enabled,
    string $user_message,
    ?\WPAICG\Core\AIPKit_AI_Caller $ai_caller,
    ?\WPAICG\Vector\AIPKit_Vector_Store_Manager $vector_store_manager
): bool {
    if (!$vector_store_enabled || empty($user_message)) {
        return false;
    }

    if (!$ai_caller || !$vector_store_manager) {
        return false;
    }
    return true;
}
