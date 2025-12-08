<?php

// File: classes/chat/core/ai-service/generate-response/load-instruction-manager.php
// Status: NEW FILE

namespace WPAICG\Chat\Core\AIService\GenerateResponse;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Ensures the AIPKit_Instruction_Manager class is loaded.
 *
 * @return true|WP_Error True if loaded, WP_Error otherwise.
 */
function load_instruction_manager_logic(): bool|WP_Error
{
    if (!class_exists(\WPAICG\Core\AIPKit_Instruction_Manager::class)) {
        $manager_path = WPAICG_PLUGIN_DIR . 'classes/core/class-aipkit-instruction-manager.php';
        if (file_exists($manager_path)) {
            require_once $manager_path;
        } else {
            return new WP_Error('internal_error_im_load', 'Instruction processing component missing (load logic).');
        }
    }
    if (!class_exists(\WPAICG\Core\AIPKit_Instruction_Manager::class)) { // Double check after require_once
        return new WP_Error('internal_error_im_not_loaded', 'InstructionManager class still not available after attempting load.');
    }
    return true;
}
