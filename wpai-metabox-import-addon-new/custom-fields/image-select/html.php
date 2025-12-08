<div class="pmmi-img-select">
    <?php foreach ($field['choices'] as $choice) { ?>
        <label class="pmmi-img-select-item">
            <img class="pmmi-img-select-item__img" src="<?php echo $choice['label']; ?>" />
            <input class="pmmi-img-select-item__input" name="<?php echo esc_attr($html_name); ?>" aria-labelledby="<?php echo esc_attr($html_name); ?>-label" type="radio" value="<?php echo $choice['value']; ?>" <?php echo isset($field_value) && $field_value == $choice['value'] ? 'checked="checked"' : ''; ?> />
        </label>
    <?php } ?>
</div>