<?php
// File: lib/views/chatbot/partials/triggers/trigger-builder-main.php
// NEW FILE

/**
 * Partial: Chatbot Trigger Builder UI
 *
 * This file provides the HTML structure for the trigger builder.
 * JavaScript will be used to make this UI dynamic.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables available from parent script (accordion-triggers.php):
// $bot_id, $triggers_json (the raw JSON string of triggers for this bot)

// Ensure schema for JS is available
$trigger_schemas_path = WPAICG_LIB_DIR . 'schemas/triggers/trigger-schemas.php';
$trigger_schemas = file_exists($trigger_schemas_path) ? require $trigger_schemas_path : [];

?>
<div class="aipkit_trigger_builder_container" id="aipkit_trigger_builder_<?php echo esc_attr($bot_id); ?>" data-bot-id="<?php echo esc_attr($bot_id); ?>">
    <?php // This hidden input stores the raw JSON data that JS will load initially ?>
    <input type="hidden" class="aipkit_trigger_raw_data_source" value="<?php echo esc_attr($triggers_json); ?>">
    <?php // This hidden input makes the schemas available to JS ?>
    <input type="hidden" class="aipkit_trigger_schemas_source" data-schemas="<?php echo esc_attr(wp_json_encode($trigger_schemas)); ?>">

    <div class="aipkit_trigger_list_actions">
        <button type="button" class="aipkit_btn aipkit_btn-secondary aipkit_trigger_add_new_btn">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Add New Trigger', 'gpt3-ai-content-generator'); ?>
        </button>
    </div>

    <div class="aipkit_trigger_list_container" id="aipkit_trigger_list_for_<?php echo esc_attr($bot_id); ?>">
        <p class="aipkit_trigger_list_empty_message" style="text-align:center; padding:15px; color: #777; font-style:italic;">
            <?php
            // Ensure $triggers_json is set and trim it; default to '[]' if not set or empty,
            // which represents an empty list of triggers.
            $current_triggers_data_for_check = isset($triggers_json) ? trim($triggers_json) : '';
            if ($current_triggers_data_for_check === '') {
                $current_triggers_data_for_check = '[]'; // Treat completely empty string as empty list
            }
            $triggers_array = json_decode($current_triggers_data_for_check, true);

            // Check if decoding failed (null) or resulted in an empty array.
            if ($triggers_array === null || empty($triggers_array)) {
                esc_html_e('No triggers configured yet. Click "Add New Trigger" above to create one.', 'gpt3-ai-content-generator');
            } else {
                esc_html_e('Loading triggers...', 'gpt3-ai-content-generator');
            }
            ?>
        </p>
    </div>

    <!-- Hidden template for a single trigger editor -->
    <template class="aipkit_trigger_editor_template">
        <div class="aipkit_trigger_item">
            <div class="aipkit_trigger_item_header">
                <h5 class="aipkit_trigger_item_title">
                    <span class="aipkit_trigger_item_name_display"></span>
                    <span class="aipkit_trigger_item_id_display"></span>
                </h5>
                <div class="aipkit_trigger_item_actions">
                    <label class="aipkit_trigger_is_active_toggle aipkit_checkbox-label" title="<?php esc_attr_e('Toggle Active Status', 'gpt3-ai-content-generator'); ?>">
                        <input type="checkbox" class="aipkit_trigger_is_active_checkbox">
                        <span><?php esc_html_e('Active', 'gpt3-ai-content-generator'); ?></span>
                    </label>
                    <button type="button" class="aipkit_btn aipkit_btn-icon aipkit_trigger_edit_btn" title="<?php esc_attr_e('Edit Trigger', 'gpt3-ai-content-generator'); ?>"><span class="dashicons dashicons-edit"></span></button>
                    <button type="button" class="aipkit_btn aipkit_btn-icon aipkit_trigger_delete_btn" title="<?php esc_attr_e('Delete Trigger', 'gpt3-ai-content-generator'); ?>"><span class="dashicons dashicons-trash"></span></button>
                </div>
            </div>
            <div class="aipkit_trigger_item_content" style="display:none;">
                <!-- Form fields will be dynamically built here by JS -->
            </div>
        </div>
    </template>

    <!-- Hidden template for a condition row -->
    <template class="aipkit_condition_row_template">
        <div class="aipkit_condition_row">
            <div class="aipkit_condition_inputs">
                <select class="aipkit_form-input aipkit_condition_type_select"></select>
                <select class="aipkit_form-input aipkit_condition_field_select"></select>
                <select class="aipkit_form-input aipkit_condition_operator_select"></select>
                <input type="text" class="aipkit_form-input aipkit_condition_value_input" placeholder="<?php esc_attr_e('Value', 'gpt3-ai-content-generator'); ?>">
            </div>
            <button type="button" class="aipkit_btn aipkit_btn-icon aipkit_condition_remove_btn" title="<?php esc_attr_e('Remove Condition', 'gpt3-ai-content-generator'); ?>"><span class="dashicons dashicons-minus"></span></button>
        </div>
    </template>
</div>
