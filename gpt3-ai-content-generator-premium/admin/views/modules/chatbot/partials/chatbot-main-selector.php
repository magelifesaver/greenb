<?php
/**
 * Partial: Chatbot Main Tab Selector (REVISED)
 *
 * Renders the simplified main navigation for the Chatbot module, featuring a single dropdown for all bots
 * and a dedicated "New Bot" button.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Variables passed from parent (chatbot/index.php):
// $default_bot_entry, $other_bots_entries, $initial_active_bot_id,
// $initial_active_bot_name, $create_new_active_class

// Combine all bots into one list for the dropdown, with the default bot first.
$all_bots_to_list = [];
if ($default_bot_entry) {
    $all_bots_to_list[] = $default_bot_entry;
}
$all_bots_to_list = array_merge($all_bots_to_list, $other_bots_entries);

?>
<div class="aipkit_tabs aipkit_chatbot_main_selector" id="aipkit_chatbot_main_selector_container">
    <!-- Group left-side controls -->
    <div class="aipkit_chatbot_selector_left_group">
        <!-- "New Bot" Button (Always First) -->
        <button
            type="button"
            class="aipkit_btn aipkit_tab_button aipkit_create-new-button <?php echo esc_attr($create_new_active_class); ?>"
            data-tab="chatbot-create-new"
            data-tab-type="create"
            title="<?php esc_attr_e('Create a new chatbot', 'gpt3-ai-content-generator'); ?>"
        >
            <span class="dashicons dashicons-plus-alt2 aipkit_tab_icon"></span>
            <?php esc_html_e('New Bot', 'gpt3-ai-content-generator'); ?>
        </button>

        <!-- Bot Selection Dropdown (Only shown if there are bots) -->
        <?php if (!empty($all_bots_to_list)): ?>
            <div class="aipkit_tab_dropdown_container">
                <button
                    type="button"
                    class="aipkit_btn aipkit_tab_button aipkit_tab_dropdown_trigger"
                    title="<?php esc_attr_e('Select a bot to edit', 'gpt3-ai-content-generator'); ?>"
                >
                    <span class="aipkit_active_bot_name_display"><?php echo esc_html($initial_active_bot_name); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2" style="margin-left: 5px;"></span>
                </button>
                <div class="aipkit_tab_dropdown_menu" style="display: none;">
                    <?php
                    foreach ($all_bots_to_list as $bot_entry_loop) {
                        $bot_post   = $bot_entry_loop['post'];
                        $bot_id     = $bot_post->ID;
                        $bot_name   = esc_html($bot_post->post_title);
                        $is_default = ($default_bot_entry && $bot_id === $default_bot_entry['post']->ID);

                        // Determine if the current item in the loop should be marked as active
                        $item_active_class = (!$create_new_active_class && $initial_active_bot_id === $bot_id) ? 'aipkit_active' : '';
                        ?>
                        <div
                            class="aipkit_tab_dropdown_item <?php echo esc_attr($item_active_class); ?>"
                            data-tab="chatbot-<?php echo esc_attr($bot_id); ?>"
                            data-bot-id="<?php echo esc_attr($bot_id); ?>"
                            data-tab-type="bot"
                            title="<?php echo 
                            /* translators: %s is the bot name */
                            esc_attr(sprintf(__('Edit settings for %s', 'gpt3-ai-content-generator'), esc_html($bot_name))); ?>"
                        >
                            <?php if ($is_default): ?>
                                <span class="dashicons dashicons-star-filled aipkit_tab_icon" style="color: #fbc02d;" title="<?php esc_attr_e('Default Bot', 'gpt3-ai-content-generator'); ?>"></span>
                            <?php else: ?>
                                <span class="dashicons dashicons-format-chat aipkit_tab_icon"></span>
                            <?php endif; ?>
                            
                            <span class="aipkit_tab_name_display"><?php echo esc_html($bot_name); ?></span>
                            
                            <?php if (!$is_default): // Do not allow renaming the default bot?>
                                <span class="aipkit_tab_edit_container" style="display: none;"></span>
                                <span class="aipkit_tab_edit_btn" title="<?php esc_attr_e('Rename Chatbot', 'gpt3-ai-content-generator'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </span>
                                <span class="aipkit_tab_rename_status"></span>
                            <?php endif; ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tools dropdown: moved from header right; styled like selection dropdown -->
        <div class="aipkit_tab_dropdown_container aipkit_actions_dropdown_container" id="aipkit_chatbot_tools_dropdown">
            <button
                type="button"
                class="aipkit_btn aipkit_tab_button aipkit_tab_dropdown_trigger"
                title="<?php esc_attr_e('Tools', 'gpt3-ai-content-generator'); ?>"
                aria-haspopup="true"
                aria-expanded="false"
            >
                <span class="aipkit_tools_label"><?php esc_html_e('Tools', 'gpt3-ai-content-generator'); ?></span>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </button>
            <div class="aipkit_tab_dropdown_menu" style="display: none;">
                <button type="button" class="aipkit_btn-as-link aipkit_header_reset_btn"><?php esc_html_e('Reset', 'gpt3-ai-content-generator'); ?></button>
                <button type="button" class="aipkit_btn-as-link aipkit_header_delete_btn"><?php esc_html_e('Delete', 'gpt3-ai-content-generator'); ?></button>
            </div>
        </div>

        <!-- Shared confirmation/status area for Tools actions -->
        <div id="aipkit_header_action_feedback" class="aipkit_tab_action_feedback"></div>

        <!-- Shortcode Pill -->
        <div
            id="aipkit_chatbot_shortcode_pill_container"
            style="display: <?php echo $initial_active_bot_id ? 'inline-flex' : 'none'; ?>;"
        >
            <?php if ($initial_active_bot_id): ?>
                <div
                    class="aipkit_shortcode_pill"
                    id="aipkit_chatbot_shortcode_pill"
                    onclick="window.aipkit_copyShortcode('[aipkit_chatbot id=<?php echo esc_attr($initial_active_bot_id); ?>]', this)"
                    title="<?php esc_attr_e('Click to copy shortcode', 'gpt3-ai-content-generator'); ?>"
                    data-bot-id="<?php echo esc_attr($initial_active_bot_id); ?>"
                >
                    <span class="dashicons dashicons-admin-page"></span>
                    <span class="aipkit_shortcode_text">[aipkit_chatbot id=<?php echo esc_attr($initial_active_bot_id); ?>]</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- NEW: Global Save Status Container -->
        <div class="aipkit_save_status_container" id="aipkit_chatbot_global_save_status_container"></div>
    </div>
</div><!-- /.aipkit_chatbot_main_selector -->
