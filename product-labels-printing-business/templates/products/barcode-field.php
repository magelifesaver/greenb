<p class="form-field <?php echo esc_attr($this->fieldName.'_field'); ?>">
    <label for="<?php echo esc_attr($this->fieldName); ?>"><?php echo esc_html($this->fieldLabel); ?></label>
    <input type="text" class="short" style="" name="<?php echo esc_attr($this->fieldName); ?>" id="<?php echo esc_attr($this->fieldName); ?>" value="<?php echo esc_attr($value); ?>">
</p>
