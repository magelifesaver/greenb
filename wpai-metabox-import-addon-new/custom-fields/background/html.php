<?php $html_id = str_replace(['[', ']'], ['_', ''], $html_name); ?>

<div class="pmmi-background-input input">
    <div class="input">
        <label><?php _e('Background Color', 'wp_all_import_plugin'); ?> </label>
        <input placeholder="#000" type="text" name="<?php echo esc_attr($html_name); ?>[color]" value="<?php echo (!is_array($field_value)) ? esc_attr($field_value) : esc_attr($field_value['color']); ?>" style="width:228px;" />
    </div>

    <div class="input">
        <label><?php _e('Background Image', 'wp_all_import_plugin'); ?> </label>
        <input placeholder="http://example.com/images/image-1.jpg" type="text" name="<?php echo esc_attr($html_name); ?>[image]" value="<?php echo (!is_array($field_value)) ? esc_attr($field_value) : esc_attr($field_value['image']); ?>" />
    </div>

    <div class="pmxi-addon-subfields pmmi-background-input-subfields">
        <div class="input">
            <input type="text" id="<?php echo $html_id; ?>_repeat" name="<?php echo esc_attr($html_name); ?>[repeat]" value="<?php echo (!is_array($field_value)) ? esc_attr($field_value) : esc_attr($field_value['repeat']); ?>" />
            <label for="<?php echo $html_id; ?>_repeat">
                <?php _e('Repeat', 'wp_all_import_plugin') ?>
                <a href="#help" class="wpallimport-help" style="top: -1px;" title="<?php _e('Possible values: repeat, repeat-x, repeat-y, no-repeat', 'wp_all_import_plugin') ?>">?</a>
            </label>
        </div>

        <div class="input">
            <input type="text" id="<?php echo $html_id; ?>_position" name="<?php echo esc_attr($html_name); ?>[position]" value="<?php echo (!is_array($field_value)) ? esc_attr($field_value) : esc_attr($field_value['position']); ?>" />
            <label for="<?php echo $html_id; ?>_position">
                <?php _e('Position', 'wp_all_import_plugin') ?>
                <a href="#help" class="wpallimport-help" style="top: -1px;" title="<?php _e('Possible values: any combination of a horizontal and vertical value, e.g. left top or percentage values, e.g. 50% 50%', 'wp_all_import_plugin') ?>">?</a>
            </label>
        </div>

        <div class="input">
            <input type="text" id="<?php echo $html_id; ?>_attachment" name="<?php echo esc_attr($html_name); ?>[attachment]" value="<?php echo (!is_array($field_value)) ? esc_attr($field_value) : esc_attr($field_value['attachment']); ?>" />
            <label for="<?php echo $html_id; ?>_attachment">
                <?php _e('Attachment', 'wp_all_import_plugin') ?>
                <a href="#help" class="wpallimport-help" style="top: -1px;" title="<?php _e('Possible values: scroll, fixed, local, initial, inherit', 'wp_all_import_plugin') ?>">?</a>
            </label>
        </div>

        <div class="input">
            <input type="text" id="<?php echo $html_id; ?>_size" name="<?php echo esc_attr($html_name); ?>[size]" value="<?php echo (!is_array($field_value)) ? esc_attr($field_value) : esc_attr($field_value['size']); ?>" />
            <label for="<?php echo $html_id; ?>_size">
                <?php _e('Size', 'wp_all_import_plugin') ?>
                <a href="#help" class="wpallimport-help" style="top: -1px;" title="<?php esc_html_e('Possible values: auto, cover, contain, initial, inherit or any combination of width and height values, e.g. 50px 50px', 'wp_all_import_plugin') ?>">?</a>
            </label>
        </div>
    </div>
</div>