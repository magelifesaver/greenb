<p class="form-row form-field form-row-full">
    <label for="<?php echo esc_attr("v_{$this->fieldName}[{$variation->ID}]"); ?>"><?php echo esc_html($this->fieldLabel); ?></label> &nbsp;
    <span style="display: block;">
        <input type="text" class="input-text" style="max-width: 48%;" name="<?php echo esc_attr("v_{$this->fieldName}[{$variation->ID}]"); ?>" id="<?php echo esc_attr("v_{$this->fieldName}[{$variation->ID}]"); ?>" value="<?php echo esc_attr($value); ?>">
    </span>
</p>
