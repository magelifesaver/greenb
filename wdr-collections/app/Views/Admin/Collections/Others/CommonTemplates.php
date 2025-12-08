<?php defined('ABSPATH') or exit ?>

<div id="templates" style="display: none;">
    <div class="wdr-icon-remove">
        <div class="wdr-btn-remove wdr_filter_remove">
            <span class="dashicons dashicons-no-alt remove-current-row"></span>
        </div>
    </div>
    <?php $wdr_product_filters = $base->getProductFilterTypes();
        if (isset($wdr_product_filters['Product']['all_products'])) {
            unset($wdr_product_filters['Product']['all_products']);
        }
        if (isset($wdr_product_filters['Collections'])) {
            unset($wdr_product_filters['Collections']);
        }
    ?>
    <div class="wdr-build-filter-type">
        <div class="wdr-filter-type">
            <select name="filters[{i}][type]" class="wdr-product-filter-type"><?php
                if (isset($wdr_product_filters) && !empty($wdr_product_filters)) {
                    foreach ($wdr_product_filters as $wdr_filter_key => $wdr_filter_value) {
                        ?>
                        <optgroup label="<?php echo esc_attr($wdr_filter_key); ?>"><?php
                        foreach ($wdr_filter_value as $key => $value) {
                            ?>
                            <option
                            <?php
                            if(isset($value['active']) && $value['active'] == false){
                                ?>
                                disabled="disabled"
                                <?php
                            } else {
                                ?>
                                value="<?php echo esc_attr($key); ?>"
                                <?php
                            }
                            ?>
                             <?php if ($key == 'products') {
                                echo 'selected';
                            } ?>><?php _e($value['label'], 'wdr-collections'); ?></option><?php
                        } ?>
                        </optgroup><?php
                    }
                } ?>
            </select>
        </div>
    </div>
    <?php $wdr_product_filter_templates = $base->getFilterTemplatesContent();
    if (isset($wdr_product_filter_templates) && !empty($wdr_product_filter_templates)) {
        if (isset($wdr_product_filter_templates['all_products'])) {
            unset($wdr_product_filter_templates['all_products']);
        }
        if (isset($wdr_product_filter_templates['filter_collections'])) {
            unset($wdr_product_filter_templates['filter_collections']);
        }
        foreach ($wdr_product_filter_templates as $wdr_filter_template) {
            echo $wdr_filter_template;
        }
    }
    ?>
</div>