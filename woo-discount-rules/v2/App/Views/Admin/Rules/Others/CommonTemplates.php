<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/**
 * filter selector
 * condition selector
 * discount selector
 */
?>

<div id="templates" style="display: none;">
    <div class="wdr-icon-remove">
        <div class="wdr-btn-remove wdr_filter_remove">
            <span class="dashicons dashicons-no-alt remove-current-row"></span>
        </div>
    </div>
    <?php $wdr_product_filters = $base->getProductFilterTypes(); ?>
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
                            } ?>><?php esc_html_e($value['label'], 'woo-discount-rules');//phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText  ?></option><?php
                        } ?>
                        </optgroup><?php
                    }
                } ?>
            </select>
        </div>
    </div>
    <?php $wdr_product_filter_templates = $base->getFilterTemplatesContent();
    if (isset($wdr_product_filter_templates) && !empty($wdr_product_filter_templates)) {
        foreach ($wdr_product_filter_templates as $wdr_filter_template) {
            echo wp_kses($wdr_filter_template,[
               'div' => [
                    'class' => [],
                    'id' => [],
                ],
                'span' => [
                    'class' => [],
                ],
                'select' => [
                    'name' => [],
                    'class' => [],
                    'multiple' => [],
                    'data-placeholder' => [],
                    'data-action' => [],
                    'tabindex' => [],
                    'style' => [],
                    'data-list' => [],
                    'data-field' => [],
                    'data-taxonomy' => [],
                ],
                'option' => [
                    'value' => [],
                    'selected' => [],
                ],
            ]);
        }
    }
    $wdr_product_conditions = $base->getProductConditionsTypes();
    ?>
    <div class="wdr-build-condition-type">
        <div class="wdr-condition-type">
            <select name="conditions[{i}][type]" class="wdr-product-condition-type awdr-left-align"><?php
                if (isset($wdr_product_conditions) && !empty($wdr_product_conditions)) {
                    foreach ($wdr_product_conditions as $wdr_condition_key => $wdr_condition_value) {
                        ?>
                        <optgroup label="<?php echo esc_attr($wdr_condition_key); ?>"><?php
                        foreach ($wdr_condition_value as $key => $value) {
                            ?>
                            <option class="<?php echo ( $key == 'cart_item_product_onsale') ? 'wdr-hide awdr-free-shipping-special-condition' : ''; ?>"
                            <?php
                            if(isset($value['enable']) && $value['enable'] === false){
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
                            } ?>><?php esc_html_e($value['label'], 'woo-discount-rules');//phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?></option><?php
                        } ?>
                        </optgroup><?php
                    }
                } ?>
            </select>
            <span class="wdr_desc_text awdr-clear-both"><?php esc_html_e('Condition Type', 'woo-discount-rules'); ?></span>
        </div>
    </div>
    <?php $wdr_product_conditions_templates = $base->getConditionsTemplatesContent();
    if (isset($wdr_product_conditions_templates) && !empty($wdr_product_conditions_templates)) {
        foreach ($wdr_product_conditions_templates as $wdr_conditions_template) {
            echo wp_kses($wdr_conditions_template,[
		        'div' => [
			        'class' => [],
			        'id' => [],
                    'style' => [],
		        ],
		        'span' => [
			        'class' => [],
			        'style' => [],
		        ],
		        'select' => [
			        'name' => [],
			        'class' => [],
                    'id' => [],
                    'aria-hidden'=>[],
			        'multiple' => [],
			        'data-placeholder' => [],
			        'data-action' => [],
			        'tabindex' => [],
			        'style' => [],
			        'data-list' => [],
			        'data-field' => [],
			        'data-taxonomy' => [],
		        ],
		        'option' => [
			        'style' => [],
			        'value' => [],
			        'selected' => [],
		        ],
                'input' => [
                    'name' => [],
                    'type' => [],
                    'class' => [],
                    'value' => [],
                    'placeholder' => [],
                    'min' => [],
                    'max' => [],
                    'autocomplete' => [],
                    'data-class' => [],
                    'data-field' => [],
                    'style' => [],
                ],
                'optgroup' => [
                    'label' => []
                ]
	        ]);
        }
    }
    $render_saved_condition = false;
    include'SubtotalPromotion.php';
    include'QuantityPromotion.php'; ?>
</div>