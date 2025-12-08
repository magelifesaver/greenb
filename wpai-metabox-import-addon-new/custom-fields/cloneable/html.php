<?php
/**
 * @var array $field
 * @var \Wpai\AddonAPI\PMXI_Addon_Field $field_class
 * @var int $row_index
 */

use function Wpai\AddonAPI\view;

$switcher_id = str_replace(['[', ']'], ['-', ''], $html_name);
$current_mode = (!empty($field_value['mode'])) ? $field_value['mode'] : 'fixed';
?>
<div class="pmxi-cloneable-mode pmxi-switcher">
    <div class="pmxi-switcher-radio-group">
        <label class="pmxi-switcher-radio-item">
            <input type="radio" id="<?php echo $switcher_id; ?>_fixed" class="switcher" name="<?php echo esc_attr($html_name); ?>[mode]" value="fixed" <?php echo ($current_mode == 'fixed') ? 'checked="checked"' : ''; ?> />
            <span>Fixed</span>
        </label>

        <label class="pmxi-switcher-radio-item">
            <input type="radio" id="<?php echo $switcher_id; ?>_xml" class="switcher" name="<?php echo esc_attr($html_name); ?>[mode]" value="variable-xml" <?php echo ($current_mode == 'variable-xml') ? 'checked="checked"' : ''; ?> />
            <span>Variable (XML)</span>
        </label>

        <label class="pmxi-switcher-radio-item">
            <input type="radio" id="<?php echo $switcher_id; ?>_csv" class="switcher" name="<?php echo esc_attr($html_name); ?>[mode]" value="variable-csv" <?php echo ($current_mode == 'variable-csv') ? 'checked="checked"' : ''; ?> />
            <span>Variable (CSV)</span>
        </label>
    </div>

    <div class="pmxi-switcher-target switcher-target-<?php echo $switcher_id; ?>_fixed">
    </div>

    <div class="pmxi-switcher-target switcher-target-<?php echo $switcher_id; ?>_xml">
        <p>
            <?php printf(__("For each %s do ..."), '<input type="text" name="' . $html_name . '[foreach]" value="' . ((empty($field_value["foreach"])) ? '' : $field_value["foreach"]) . '" class="pmxi-repeater-foreach widefat rad4"/>'); ?>
            <a href="https://www.wpallimport.com/documentation/import-meta-box-cloneable-repeater-fields/" target="_blank"><?php _e('(documentation)', 'wp_all_import_metabox_add_on'); ?></a>
        </p>
    </div>

    <div class="pmxi-switcher-target switcher-target-<?php echo $switcher_id; ?>_csv">
        <p>
            <?php printf(__("Separator Character %s"), '<input type="text" name="' . $html_name . '[separator]" value="' . ((empty($field_value["separator"])) ? '|' : $field_value["separator"]) . '" class="pmxi-variable-separator small widefat rad4"/>'); ?>
            <a href="#help" class="wpallimport-help" style="top: -1px;" title="<?php _e('Use this option when importing a CSV file with a column or columns that contains the repeating data, separated by separators. For example, if you had a repeater with two fields - image URL and caption, and your CSV file had two columns, image URL and caption, with values like \'url1,url2,url3\' and \'caption1,caption2,caption3\', use this option and specify a comma as the separator.', 'wp_all_import_metabox_add_on') ?>">?</a>
        </p>
    </div>
</div>

<div class="pmmi-cloneable">
    <div class="pmmi-cloneable-rows">
        <?php
        if (!empty($field_value['rows'])) {
            foreach ($field_value['rows'] as $row_index => $row) {
                view(
                    'row-template',
                    [
                        'field' => $field,
                        'field_class' => $field_class,
                        'row_index' => $row_index
                    ],
                    null,
                    true,
                    __DIR__ . '/'
                );
            }
        }
        ?>
    </div>

    <div class="pmmi-cloneable-actions">
        <button class="pmmi-cloneable-button pmmi-cloneable-add-row button button-primary" type="button">
            <?php echo $field['args']['add_button_label']; ?>
        </button>
    </div>

    <template class="pmmi-cloneable-template">
        <?php
        view(
            'row-template',
            [
                'field' => $field,
                'field_class' => $field_class,
                'row_index' => '__index__'
            ],
            null,
            true,
            __DIR__ . '/'
        );
        ?>
    </template>
</div>
