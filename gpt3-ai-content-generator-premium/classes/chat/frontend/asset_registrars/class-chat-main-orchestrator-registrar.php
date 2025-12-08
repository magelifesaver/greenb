<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-main-orchestrator-registrar.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_Main_Orchestrator_Registrar
{
    public static function register(string $version, string $public_chat_js_url, array $dependencies): void
    {
        $main_ui_handle = 'aipkit-chat-ui-main';
        $public_init_handle = 'aipkit-chat-public-init';

        // Ensure all passed dependencies are unique (some might be registered by multiple sub-registrars if not careful with handle names)
        $unique_dependencies = array_values(array_filter(array_unique($dependencies)));

        // --- MODIFICATION: Explicitly add 'aipkit-chat-event-attach-event-listeners' as a dependency for main UI ---
        $main_ui_dependencies = $unique_dependencies;
        if (isset($dependencies['attach-event-listeners']) && !in_array($dependencies['attach-event-listeners'], $main_ui_dependencies, true)) {
            $main_ui_dependencies[] = $dependencies['attach-event-listeners'];
        } elseif (!in_array('aipkit-chat-event-attach-event-listeners', $main_ui_dependencies, true) && wp_script_is('aipkit-chat-event-attach-event-listeners', 'registered')) {
            // Fallback if the key wasn't in $dependencies but the script is registered
            $main_ui_dependencies[] = 'aipkit-chat-event-attach-event-listeners';
        }
        // --- END MODIFICATION ---


        // Register the main orchestrator chat-ui-main.js
        // It now depends on all its components (actions, DOM utils, event utils, feature handlers, state init, stream message, popup handlers)
        // AND the main event listener attacher.
        wp_register_script($main_ui_handle, $public_chat_js_url . 'chat-ui-main.js', array_values(array_filter(array_unique($main_ui_dependencies))), $version, true);

        // Register the public initializer script, which depends on the main orchestrator and other core scripts
        $init_deps = array_merge(
            // --- MODIFIED: Added 'aipkit-markdown-initiate' as a dependency for chat-init.js ---
            // aipkit_markdown-it is already a dependency of aipkit-markdown-initiate
            [$main_ui_handle, 'aipkit-markdown-initiate', 'aipkit-chat-ui-apply-custom-theme'],
            // --- END MODIFICATION ---
            $unique_dependencies // Pass all originally collected dependencies too
        );
        if (wp_script_is('markdown-it-highlight', 'registered')) {
            $init_deps[] = 'markdown-it-highlight';
        }
        if (wp_script_is('aipkit-public-stt', 'registered')) {
            $init_deps[] = 'aipkit-public-stt';
        }
        wp_register_script($public_init_handle, $public_chat_js_url . 'chat-init.js', array_values(array_filter(array_unique($init_deps))), $version, true);
    }
}
