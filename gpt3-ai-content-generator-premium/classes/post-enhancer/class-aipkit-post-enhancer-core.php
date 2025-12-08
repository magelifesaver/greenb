<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/post-enhancer/class-aipkit-post-enhancer-core.php
// Status: MODIFIED
// I have updated the `add_row_actions` method to include a "Generate Tags" link and the `init_hooks` method to register the new AJAX actions for generating and updating tags.

namespace WPAICG\PostEnhancer;

use WPAICG\aipkit_dashboard; // To check if addon is active
use WPAICG\AIPKit_Role_Manager; // To check module access permissions
use WPAICG\Utils\AIPKit_Admin_Header_Action_Buttons; // Shared header button injector

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Core class for the Content Enhancer module.
 * Handles hooking into WordPress actions and filters.
 * REVISED: Changed row actions to use a dropdown/popover.
 * REVISED: Activated Meta Description link.
 */
class Core
{
    public const ADDON_KEY = 'ai_post_enhancer';

    /**
     * Registers hooks.
     */
    public function init_hooks()
    {
        // Load AJAX handler only if the addon is active
        if (aipkit_dashboard::is_addon_active(self::ADDON_KEY)) {
            // Ensure AJAX handler class is loaded
            $ajax_handler_path = WPAICG_PLUGIN_DIR . 'classes/post-enhancer/class-aipkit-post-enhancer-ajax.php';
            if (file_exists($ajax_handler_path)) {
                require_once $ajax_handler_path;
                // Register AJAX actions if class exists
                if (class_exists('\\WPAICG\\PostEnhancer\\AjaxHandler')) {
                    $ajax_handler = new AjaxHandler();
                    // Title actions
                    add_action('wp_ajax_aipkit_generate_title_suggestions', [$ajax_handler, 'generate_title_suggestions']);
                    add_action('wp_ajax_aipkit_update_post_title', [$ajax_handler, 'update_post_title']);
                    // Excerpt actions
                    add_action('wp_ajax_aipkit_generate_excerpt_suggestions', [$ajax_handler, 'generate_excerpt_suggestions']);
                    add_action('wp_ajax_aipkit_update_post_excerpt', [$ajax_handler, 'update_post_excerpt']);
                    // Meta Description actions
                    add_action('wp_ajax_aipkit_generate_meta_suggestions', [$ajax_handler, 'generate_meta_suggestions']);
                    add_action('wp_ajax_aipkit_update_post_meta_desc', [$ajax_handler, 'update_post_meta_desc']);
                    // --- NEW: Add Tags hooks ---
                    add_action('wp_ajax_aipkit_generate_tags_suggestions', [$ajax_handler, 'generate_tags_suggestions']);
                    add_action('wp_ajax_aipkit_update_post_tags', [$ajax_handler, 'update_post_tags']);
                    // --- END NEW ---
                    // --- NEW: Add Bulk Process hook ---
                    add_action('wp_ajax_aipkit_bulk_process_single_post', [$ajax_handler, 'ajax_bulk_process_single_post']);
                    // --- NEW: Add individual field processing hooks ---
                    add_action('wp_ajax_aipkit_bulk_process_single_field', [$ajax_handler, 'ajax_bulk_process_single_field']);
                    add_action('wp_ajax_aipkit_bulk_update_seo_slug', [$ajax_handler, 'ajax_bulk_update_seo_slug']);
                    // --- END NEW ---
                    // --- ADDED: Register new text processing hook ---
                    add_action('wp_ajax_aipkit_process_enhancer_text', [$ajax_handler, 'ajax_process_enhancer_text']);
                    // --- END ADDED ---
                    // --- NEW: Add hooks for Actions CRUD ---
                    add_action('wp_ajax_aipkit_get_enhancer_actions', [$ajax_handler, 'ajax_get_enhancer_actions']);
                    add_action('wp_ajax_aipkit_save_enhancer_action', [$ajax_handler, 'ajax_save_enhancer_action']);
                    add_action('wp_ajax_aipkit_delete_enhancer_action', [$ajax_handler, 'ajax_delete_enhancer_action']);
                    // --- END NEW ---
                }
            }

            // --- MODIFICATION: Dynamically support all public post types by default ---
            $public_post_types = get_post_types(['public' => true]);
            unset($public_post_types['attachment']);
            $post_types = apply_filters('aipkit_post_enhancer_post_types', array_keys($public_post_types));
            // --- END MODIFICATION ---
            foreach ($post_types as $post_type) {
                add_filter("{$post_type}_row_actions", [$this, 'add_row_actions'], 10, 2);
            }
            // Register Content Assistant button via shared utility
            AIPKit_Admin_Header_Action_Buttons::register_button('aipkit_bulk_enhance_btn', 'Content Assistant');

            $aipkit_options = get_option('aipkit_options', []);
            $enhancer_editor_integration_enabled = $aipkit_options['enhancer_settings']['editor_integration'] ?? '1';

            if ($enhancer_editor_integration_enabled === '1') {
                // --- NEW: Add hooks for TinyMCE button ---
                add_action('admin_init', [$this, 'setup_tinymce_button']);
                // --- ADDED: Add hooks for Block Editor button ---
                add_action('admin_init', [$this, 'setup_block_editor_button']);
                // --- END ADDED ---
            }
        }
    }

    // (REMOVED) Legacy filters-bar button hook & method eliminated; header button handled by shared utility.


    /**
     * Adds the "✍️ AI Enhance" dropdown action to post row actions.
     * REVISED: Now adds a single trigger with a dropdown.
     * REVISED: Activated Meta Description link.
     *
     * @param array $actions Existing actions.
     * @param \WP_Post $post The post object.
     * @return array Modified actions.
     */
    public function add_row_actions($actions, $post)
    {
        // Check if addon is active and user has permission for the module AND can edit this post
        if (
            aipkit_dashboard::is_addon_active(self::ADDON_KEY) &&
            AIPKit_Role_Manager::user_can_access_module(self::ADDON_KEY) &&
            current_user_can('edit_post', $post->ID)
        ) {
            // --- Main Enhancer Action with Dropdown ---
            $actions['aipkit_ai_enhance'] = sprintf(
                '<div class="aipkit_enhancer_action">
                    <a href="#" class="aipkit_enhancer_trigger" aria-label="%s">✍️ %s</a>
                    <div class="aipkit_enhancer_popover">
                        <div class="aipkit_enhancer_group">
                            <div class="aipkit_enhancer_group_title">🔤 %s</div>
                            <ul>
                                <li class="aipkit_enhancer_item" data-action-type="title" data-post-id="%d">%s</li>
                                <li class="aipkit_enhancer_item" data-action-type="excerpt" data-post-id="%d">%s</li>
                            </ul>
                        </div>
                        <div class="aipkit_enhancer_group">
                            <div class="aipkit_enhancer_group_title">📈 %s</div>
                            <ul>
                                <li class="aipkit_enhancer_item" data-action-type="meta" data-post-id="%d">%s</li>
                                <li class="aipkit_enhancer_item" data-action-type="tags" data-post-id="%d">%s</li>
                            </ul>
                        </div>
                    </div>
                 </div>',
                esc_attr__('Update content using AI', 'gpt3-ai-content-generator'), // aria-label for trigger
                esc_html__('Assistant', 'gpt3-ai-content-generator'), // Link text for trigger
                esc_html__('Text Tools', 'gpt3-ai-content-generator'), // Group Title
                esc_attr($post->ID),
                esc_html__('Generate Title', 'gpt3-ai-content-generator'),
                esc_attr($post->ID),
                esc_html__('Generate Excerpt', 'gpt3-ai-content-generator'),
                esc_html__('SEO Tools', 'gpt3-ai-content-generator'), // Group Title
                esc_attr($post->ID),
                esc_html__('Generate Meta Desc', 'gpt3-ai-content-generator'),
                esc_attr($post->ID),
                esc_html__('Generate Tags', 'gpt3-ai-content-generator')
            );
        }
        return $actions;
    }

    // --- NEW: TinyMCE Integration Methods ---
    /**
     * Set up hooks for TinyMCE button if user has permission.
     * Hooked to `admin_init`.
     */
    public function setup_tinymce_button()
    {
        if (
            AIPKit_Role_Manager::user_can_access_module(self::ADDON_KEY) &&
            (current_user_can('edit_posts') || current_user_can('edit_pages')) &&
            get_user_option('rich_editing') === 'true'
        ) {
            add_filter('mce_buttons', [$this, 'register_tinymce_button']);
            add_filter('mce_external_plugins', [$this, 'add_tinymce_plugin']);
        }
    }

    /**
     * Register the button with TinyMCE.
     * @param array $buttons The array of existing buttons.
     * @return array The modified array of buttons.
     */
    public function register_tinymce_button($buttons)
    {
        array_push($buttons, 'aipkit_assistant_button');
        return $buttons;
    }

    /**
     * Add the JavaScript plugin to TinyMCE.
     * @param array $plugin_array The array of external plugins.
     * @return array The modified array of external plugins.
     */
    public function add_tinymce_plugin($plugin_array)
    {
        $plugin_array['aipkit_assistant'] = WPAICG_PLUGIN_URL . 'dist/js/admin-enhancer-tinymce.bundle.js';
        return $plugin_array;
    }
    // --- END NEW ---

    // --- ADDED: Block Editor Integration Methods ---
    /**
     * Set up hooks for Block Editor button if user has permission.
     * Hooked to `admin_init`.
     */
    public function setup_block_editor_button()
    {
        if (
            AIPKit_Role_Manager::user_can_access_module(self::ADDON_KEY) &&
            (current_user_can('edit_posts') || current_user_can('edit_pages'))
        ) {
            add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_plugin_script']);
        }
    }

    /**
     * Enqueue the JavaScript for the Block Editor plugin.
     */
    public function enqueue_block_editor_plugin_script()
    {
        $screen = get_current_screen();
        if (!$screen || !method_exists($screen, 'is_block_editor') || !$screen->is_block_editor()) {
            return;
        }

        $script_handle = 'aipkit-enhancer-block-editor';
        $script_path = 'dist/js/admin-enhancer-block-editor.bundle.js';

        $dependencies = ['wp-i18n', 'wp-element', 'wp-rich-text', 'wp-components', 'wp-block-editor', 'wp-data']; // Added wp-data
        $version = WPAICG_VERSION;

        wp_enqueue_script(
            $script_handle,
            WPAICG_PLUGIN_URL . $script_path,
            $dependencies,
            $version,
            true // in footer
        );
    }
    // --- END ADDED ---
}
