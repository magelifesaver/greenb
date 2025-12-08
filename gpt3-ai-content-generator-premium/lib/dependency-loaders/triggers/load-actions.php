<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/dependency-loaders/triggers/load-actions.php
// Status: MODIFIED

namespace WPAICG\Lib\DependencyLoaders\Triggers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Loads trigger action classes.
 */
function load_actions(): void {
    if (!defined('WPAICG_LIB_DIR')) {
        return;
    }
    $actions_base_path = WPAICG_LIB_DIR . 'chat/triggers/actions/';

    $action_files = [
        'AIPKit_Placeholder_Processor.php',
        'AIPKit_Action_Bot_Reply.php',
        'AIPKit_Action_Inject_Context.php',
        'AIPKit_Action_Block_Message.php',
        'AIPKit_Action_Call_Webhook.php',
        'AIPKit_Action_Store_Form_Submission.php',
        'AIPKit_Action_Display_Form.php', // ADDED
        // Note: AIPKit_Action_Set_Variable.php is not listed in the original file, assuming it's loaded elsewhere or not yet implemented for Pro in this structure.
        // If it were to be loaded here from chat/triggers/actions/, it would follow the same pattern.
    ];

    foreach ($action_files as $file) {
        $full_path = $actions_base_path . $file;
        if (file_exists($full_path)) {
            $class_name_from_file = str_replace('.php', '', $file);
            $full_class_name = '\\WPAICG\\Lib\\Chat\\Triggers\\Actions\\' . $class_name_from_file;
            if (!class_exists($full_class_name)) {
                require_once $full_path;
            }
        }
    }
}