<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/class-wp-ai-content-generator-deactivator.php
// Status: MODIFIED

namespace WPAICG;

// --- MODIFIED: Use new Token Manager namespace ---
use WPAICG\Core\TokenManager\AIPKit_Token_Manager;
// --- END MODIFICATION ---
use WPAICG\Core\Stream\Cache\AIPKit_SSE_Message_Cache;
use WPAICG\AutoGPT\Cron\AIPKit_Automated_Task_Scheduler;
use WPAICG\AutoGPT\Cron\AIPKit_Automated_Task_Event_Processor;
use WPAICG\Chat\Storage\LogCronManager; // NEW: For unscheduling

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Fired during plugin deactivation.
 */
class WP_AI_Content_Generator_Deactivator
{
    public static function deactivate()
    {
        // --- MODIFIED: Use new Token Manager namespace ---
        if (class_exists('\\WPAICG\\Core\\TokenManager\\AIPKit_Token_Manager')) {
            AIPKit_Token_Manager::unschedule_token_reset_event();
        }
        // --- END MODIFICATION ---

        if (class_exists('\\WPAICG\\Core\\Stream\\Cache\\AIPKit_SSE_Message_Cache')) {
            AIPKit_SSE_Message_Cache::unschedule_cleanup_event();
        }

        // NEW: Unschedule log pruning cron
        if (class_exists('\\WPAICG\\Chat\\Storage\\LogCronManager')) {
            LogCronManager::unschedule_event();
        }

        $automated_task_scheduler_path = WPAICG_PLUGIN_DIR . 'classes/autogpt/cron/class-aipkit-automated-task-scheduler.php';
        $automated_task_event_processor_path = WPAICG_PLUGIN_DIR . 'classes/autogpt/cron/class-aipkit-automated-task-event-processor.php';

        if (file_exists($automated_task_scheduler_path) && !class_exists(\WPAICG\AutoGPT\Cron\AIPKit_Automated_Task_Scheduler::class)) {
            require_once $automated_task_scheduler_path;
        }
        if (file_exists($automated_task_event_processor_path) && !class_exists(\WPAICG\AutoGPT\Cron\AIPKit_Automated_Task_Event_Processor::class)) {
            require_once $automated_task_event_processor_path;
        }

        if (class_exists('\\WPAICG\\AutoGPT\\Cron\\AIPKit_Automated_Task_Scheduler') && class_exists('\\WPAICG\\AutoGPT\\Cron\\AIPKit_Automated_Task_Event_Processor')) {
            AIPKit_Automated_Task_Scheduler::clear_all_task_events();
            wp_clear_scheduled_hook(AIPKit_Automated_Task_Event_Processor::MAIN_CRON_HOOK);
        }
    }
}