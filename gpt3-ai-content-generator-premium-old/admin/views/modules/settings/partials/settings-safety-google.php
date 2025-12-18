<?php

/**
 * Partial: Google Safety Settings
 */
if (!defined('ABSPATH')) exit;

// Use the new GoogleSettingsHandler
use WPAICG\Core\Providers\Google\GoogleSettingsHandler;

// Variables required: $current_provider (from settings/index.php)
// Fetch settings using the handler
$safety_settings = [];
$category_thresholds = [];
if (class_exists(GoogleSettingsHandler::class)) {
    $safety_settings = GoogleSettingsHandler::get_safety_settings();
    foreach ($safety_settings as $setting) {
        if (isset($setting['category'], $setting['threshold'])) {
            $category_thresholds[$setting['category']] = $setting['threshold'];
        }
    }
} else {
    // Error logged by parent settings/index.php, prevent further rendering if handler is missing
    return;
}

$safety_thresholds_map = array(
    'BLOCK_NONE'             => 'Block None',
    'BLOCK_LOW_AND_ABOVE'    => 'Block Few',
    'BLOCK_MEDIUM_AND_ABOVE' => 'Block Some',
    'BLOCK_ONLY_HIGH'        => 'Block Most',
);

// Define the order of safety categories for consistent layout
$safety_categories_ordered = [
    'HARM_CATEGORY_HARASSMENT' => __('Harassment', 'gpt3-ai-content-generator'),
    'HARM_CATEGORY_HATE_SPEECH' => __('Hate Speech', 'gpt3-ai-content-generator'),
    'HARM_CATEGORY_SEXUALLY_EXPLICIT' => __('Sexually Explicit', 'gpt3-ai-content-generator'),
    'HARM_CATEGORY_DANGEROUS_CONTENT' => __('Dangerous Content', 'gpt3-ai-content-generator'),
    'HARM_CATEGORY_CIVIC_INTEGRITY' => __('Civic Integrity', 'gpt3-ai-content-generator'),
];

?>
<div
    class="aipkit_settings-section aipkit_model_field" <?php // Keep aipkit_model_field for general provider toggle logic ?>
    id="aipkit_safety_settings_accordion" <?php // ** RESTORED ID for JS compatibility ** ?>
    style="display: <?php echo ($current_provider === 'Google') ? 'block' : 'none'; ?>;"
    data-provider-setting="Google" <?php // Added for clarity, though JS targets ID mainly for this ?>
>
    <?php // Accordion structure removed, fields are now direct children or in form-rows ?>

    <!-- Row 1 for Safety Settings (3 fields) -->
    <div class="aipkit_form-row">
        <?php
        $count = 0;
        foreach ($safety_categories_ordered as $category_key => $category_label):
            if ($count >= 3) break; // Only first 3 in this row
            $current_threshold = $category_thresholds[$category_key] ?? 'BLOCK_NONE';
            $input_name_short = strtolower(str_replace('HARM_CATEGORY_', '', $category_key));
            $input_id = 'aipkit_safety_' . $input_name_short;
        ?>
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($category_label); ?></label>
                <select id="<?php echo esc_attr($input_id); ?>" name="safety_<?php echo esc_attr($input_name_short); ?>" class="aipkit_form-input aipkit_autosave_trigger">
                    <?php foreach ($safety_thresholds_map as $tKey => $tLabel): ?>
                        <option value="<?php echo esc_attr($tKey); ?>" <?php selected($current_threshold, $tKey); ?>>
                            <?php echo esc_html($tLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php
            $count++;
        endforeach;
        ?>
    </div>

    <!-- Row 2 for Safety Settings (remaining 2 fields) -->
    <div class="aipkit_form-row">
        <?php
        $count = 0;
        $rendered_in_row1 = 0;
        foreach ($safety_categories_ordered as $category_key => $category_label):
            if ($rendered_in_row1 < 3) { $rendered_in_row1++; continue; } // Skip first 3
            if ($count >= 2) break; // Only next 2 in this row
            $current_threshold = $category_thresholds[$category_key] ?? 'BLOCK_NONE';
            $input_name_short = strtolower(str_replace('HARM_CATEGORY_', '', $category_key));
            $input_id = 'aipkit_safety_' . $input_name_short;
        ?>
            <div class="aipkit_form-group aipkit_form-col">
                <label class="aipkit_form-label" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($category_label); ?></label>
                <select id="<?php echo esc_attr($input_id); ?>" name="safety_<?php echo esc_attr($input_name_short); ?>" class="aipkit_form-input aipkit_autosave_trigger">
                    <?php foreach ($safety_thresholds_map as $tKey => $tLabel): ?>
                        <option value="<?php echo esc_attr($tKey); ?>" <?php selected($current_threshold, $tKey); ?>>
                            <?php echo esc_html($tLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php
            $count++;
        endforeach;
        // Add an empty column if only one item in the last row for alignment (if desired, not strictly needed with flex:1)
        if ($count === 1) {
             echo '<div class="aipkit_form-col"></div>'; // Placeholder for alignment if only one item was in the last row
        }
        ?>
    </div>
</div>