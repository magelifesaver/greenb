<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/includes/hook-registrars/class-module-initializer-hooks-registrar.php
// Status: MODIFIED

namespace WPAICG\Includes\HookRegistrars;

use WPAICG\Chat\Initializer as ChatInitializer;
use WPAICG\AutoGPT\AIPKit_Automated_Task_Cron;
use WPAICG\AIForms\AIPKit_AI_Form_Initializer;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Registers hooks for modules that have their own initializers or self-registering cron jobs.
 * MODIFIED: Calls AIPKit_Automated_Task_Cron::init() statically.
 */
class Module_Initializer_Hooks_Registrar {

    public static function register(
        // ChatInitializer uses static methods, so an instance isn't passed
        // ?AIPKit_Automated_Task_Cron $automated_task_cron // No longer pass instance
        // AIFormInitializer will also use static methods, so no instance needed here
    ) {
        // Chat Initializer
        if (class_exists(ChatInitializer::class) && method_exists(ChatInitializer::class, 'register_hooks')) { // Added method_exists check for safety
            ChatInitializer::register_hooks();
        }

        // Automated Task Cron - Called Statically
        if (class_exists(AIPKit_Automated_Task_Cron::class) && method_exists(AIPKit_Automated_Task_Cron::class, 'init')) {
            AIPKit_Automated_Task_Cron::init(); // Call statically
        }

        // AI Forms Initializer
        if (class_exists(AIPKit_AI_Form_Initializer::class) && method_exists(AIPKit_AI_Form_Initializer::class, 'register_hooks')) {
            AIPKit_AI_Form_Initializer::register_hooks();
        }
    }
}