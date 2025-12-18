<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/asset_registrars/class-chat-init-state-asset-registrar.php

namespace WPAICG\Chat\Frontend\AssetRegistrars;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Chat_Init_State_Asset_Registrar {
    public static function register(string $version, string $public_chat_js_url, array $dependencies = []): array {
        $public_chat_init_js_url = $public_chat_js_url . 'init/';
        $public_chat_state_js_url = $public_chat_js_url . 'state/';
        $handles = [
            'init-display-initial-message' => 'aipkit-chat-init-display-initial-message',
            'init-finalize-setup' => 'aipkit-chat-init-finalize-setup',
            'state-init-state' => 'aipkit-chat-state-init-state',
        ];

        // --- MODIFIED: Dependencies correctly mapped from $dependencies array ---
        $dep_append_message = $dependencies['dom-append-message'] ?? null;
        $dep_gen_id = $dependencies['generate-client-message-id'] ?? null;
        $dep_scroll_to_bottom = $dependencies['dom-scroll-to-bottom'] ?? null;
        $dep_action_set_button_state = $dependencies['action-set-button-state'] ?? null;
        $dep_util_auto_resize = $dependencies['auto-resize-textarea'] ?? null;
        // --- END MODIFICATION ---

        wp_register_script($handles['init-display-initial-message'], $public_chat_init_js_url . 'display-initial-message.js', array_filter([$dep_append_message, $dep_gen_id]), $version, true);
        wp_register_script($handles['init-finalize-setup'], $public_chat_init_js_url . 'finalize-setup.js', array_filter([$dep_action_set_button_state, $dep_util_auto_resize, $dep_scroll_to_bottom]), $version, true);
        wp_register_script($handles['state-init-state'], $public_chat_state_js_url . 'init-state.js', [], $version, true);
        
        return $handles;
    }
}