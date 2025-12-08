<?php
if (!defined('ABSPATH')) {
    exit;
}
$class_name = '';
if(isset($get_buyx_gety_types) && $get_buyx_gety_types == 'bxgy_all'){
    $class_name = 'awdr-bygy-all';
}else if(isset($get_buyx_gety_types) && ($get_buyx_gety_types == 'bxgy_product' || $get_buyx_gety_types == 'bxgy_category')){
    $class_name = 'awdr-bygy-cat-products';
}
?>
<div class="wdr-discount-group buyx_gety_individual_range" data-index="<?php echo esc_attr($buyx_gety_index); ?>">
    <div class="range_setter_inner">
        <div class="wdr-buyx-gety-discount-main">
            <div class="wdr-buyx-gety-discount-inner wdr-input-filed-hight" style="border-bottom:1px solid #ddd">
                <div class="dashicons dashicons-menu bxgy-icon awdr-sortable-handle <?php echo esc_attr($class_name);?>"></div>
               <fieldset>
                   <legend><?php esc_html_e('Buy Quantity', 'woo-discount-rules-pro'); ?></legend>
                <div class="awdr-buyx-gety-min">
                    <input type="number" name="buyx_gety_adjustments[ranges][<?php echo esc_attr($buyx_gety_index); ?>][from]"
                           class="awdr-buyx-gety-number-box awdr_value_selector awdr_next_value bxgy-min"
                           placeholder="<?php esc_attr_e('Min Quantity', 'woo-discount-rules-pro'); ?>" min="0" step="any"
                           value="<?php echo (isset($buyx_gety_adjustment->from) && !empty($buyx_gety_adjustment->from)) ? esc_attr($buyx_gety_adjustment->from) : '1'; ?>"
                    >
                    <span class="wdr_desc_text"><?php echo (isset($buyx_gety_adjustment->recursive) && !empty($buyx_gety_adjustment->recursive)) ? __('Quantity', 'woo-discount-rules-pro') : __('Minimum Quantity', 'woo-discount-rules-pro'); ?></span>
                </div>

                <div class="awdr-buyx-gety-max" style="<?php echo (isset($buyx_gety_adjustment->recursive) && !empty($buyx_gety_adjustment->recursive)) ? 'display:none' : ''; ?>">
                    <input type="number" name="buyx_gety_adjustments[ranges][<?php echo esc_attr($buyx_gety_index); ?>][to]"
                           class="awdr-buyx-gety-number-box awdr_value_selector awdr_auto_add_value bxgy-max"
                           placeholder="<?php esc_attr_e('Max Quantity', 'woo-discount-rules-pro'); ?>" min="0" step="any"
                           value="<?php
                           if(isset($buyx_gety_adjustment->to) && !empty($buyx_gety_adjustment->to)){
                               $buyx_gety_adjustment_to = $buyx_gety_adjustment->to;
                           }elseif(isset($buyx_gety_adjustment->from) && isset($buyx_gety_adjustment->to) && !empty($buyx_gety_adjustment->from) && empty($buyx_gety_adjustment->to)){
                               $buyx_gety_adjustment_to = '';
                           }else{
                               $buyx_gety_adjustment_to = 1;
                           }
                           echo esc_attr($buyx_gety_adjustment_to);
                           ?>"
                    >
                    <span class="wdr_desc_text"><?php esc_html_e('Maximum Quantity', 'woo-discount-rules-pro'); ?></span>
                </div>
               </fieldset>
                <fieldset>
                <legend><?php esc_html_e('Get Quantity', 'woo-discount-rules-pro'); ?></legend>
                <div class="awdr-buyx-gety-product wdr-select-filed-hight wdr-search-box bxgy_product"
                     style="vertical-align: bottom;<?php echo ($get_buyx_gety_types != 'bxgy_product') ? 'display: none;' : '' ?>">
                    <?php
                    global $sitepress;
                    $check_wpml_language = $wpml_language_conflict = false;
                    $rule_language = $conflict_products = array();
                    if(is_object($sitepress) && method_exists($sitepress, 'get_current_language')){
                        if(isset($rule->rule) && !empty($rule->rule->rule_language)){
                            $rule_language = json_decode($rule->rule->rule_language);
                            if(!empty($rule_language) && !empty($buyx_gety_adjustment->products)){
                                $check_wpml_language = true;
                            }
                        }
                    }
                    ?>
                    <select multiple
                            class="bxgy-product-selector"
                            data-list="products"
                            data-field="autocomplete"
                            data-placeholder="<?php esc_attr_e('Select Product', 'woo-discount-rules-pro') ?>"
                            name="buyx_gety_adjustments[ranges][<?php echo esc_attr($buyx_gety_index); ?>][products][]"><?php
                        if (isset($buyx_gety_adjustment->products) && !empty($buyx_gety_adjustment->products)) {
                            $item_name = '';
                            foreach ($buyx_gety_adjustment->products as $product_id) {
                                $item_name = '#'.$product_id.' '.\WDRPro\App\Helpers\CoreMethodCheck::getTitleOfProduct($product_id);
                                if($check_wpml_language){
                                    $post_language_information = apply_filters( 'wpml_post_language_details', NULL, $product_id);
                                    if(isset($post_language_information['language_code'])){
                                        if(!in_array($post_language_information['language_code'], $rule_language)){
                                            $conflict_products[] = $item_name;
                                        }
                                    }
                                }
                                if ($item_name != '') { ?>
                                    <option value="<?php echo esc_attr($product_id); ?>"
                                            selected><?php echo esc_html($item_name); ?></option><?php
                                }
                            }
                        }
                        ?>
                    </select>
                    <span class="wdr_desc_text"><?php esc_html_e('Product', 'woo-discount-rules-pro'); ?></span>
                </div>
                <div class="awdr-buyx-gety-category wdr-select-filed-hight wdr-cart-search_box bxgy_category"
                     style="vertical-align: bottom;min-width: 250px; <?php echo ($get_buyx_gety_types != 'bxgy_category') ? 'display: none;' : '' ?>">
                    <?php $values = isset($buyx_gety_adjustment->categories) ? $buyx_gety_adjustment->categories : array(); ?>
                    <select multiple
                            class="bxgy-category-selector"
                            data-list="product_category"
                            data-field="autocomplete"
                            data-placeholder="<?php esc_attr_e('Search Categories', 'woo-discount-rules-pro'); ?>"
                            name="buyx_gety_adjustments[ranges][<?php echo esc_attr($buyx_gety_index); ?>][categories][]"><?php
                        if ($values) {
                            $item_name = '';
                            $taxonomies = apply_filters('advanced_woo_discount_rules_category_taxonomies', array('product_cat'));
                            if(!is_array($taxonomies)){
                                $taxonomies = array('product_cat');
                            }
                            foreach ($values as $value) {
                                foreach ($taxonomies as $taxonomy){
                                    $term_name = get_term_by('id', $value, $taxonomy);
                                    if (!empty($term_name)) {
                                        $parant_name = '';
                                        if(isset($term_name->parent) && !empty($term_name->parent)){
                                            if (function_exists('get_the_category_by_ID')) {
                                                $parant_names = get_the_category_by_ID((int)$term_name->parent);
                                                $parant_name = $parant_names . ' -> ';
                                            }
                                        }
                                        $item_name = $parant_name.$term_name->name; ?>
                                        <option value="<?php echo esc_attr($value); ?>"
                                                selected><?php echo esc_html($item_name); ?></option><?php
                                    }
                                }
                            }
                        }
                        ?>
                    </select>
                    <span class="wdr_desc_text awdr-clear-both "><?php esc_html_e('Select categories', 'woo-discount-rules-pro'); ?></span>
                </div>

                <?php do_action('advanced_woo_discount_rules_bxgy_discount_get_y_section', $buyx_gety_index, $get_buyx_gety_types, (isset($buyx_gety_adjustment) ? $buyx_gety_adjustment : null)); ?>

                <div class="awdr-buyx-gety-free-qty">
                    <input type="number"
                           name="buyx_gety_adjustments[ranges][<?php echo esc_attr($buyx_gety_index); ?>][free_qty]"
                           class="awdr-buyx-gety-number-box awdr_value_selector bxgy-qty"
                           placeholder="<?php esc_attr_e('Free Quantity', 'woo-discount-rules-pro'); ?>" min="1" step="any"
                           value="<?php echo (isset($buyx_gety_adjustment->free_qty) && !empty($buyx_gety_adjustment->free_qty)) ? esc_attr($buyx_gety_adjustment->free_qty) : '1'; ?>"
                    >
                    <span class="wdr_desc_text"><?php esc_html_e('Free Quantity', 'woo-discount-rules-pro'); ?></span>
                </div>
                    <?php
                    if(!empty($conflict_products)){
                        ?>
                        <div class="awdr-buyx-gety-product wdr-select-filed-hight wdr-search-box bxgy_product"
                             style="vertical-align: bottom;<?php echo ($get_buyx_gety_types != 'bxgy_product') ? 'display: none;' : '' ?>">
                            <div class="notice notice-warning">
                                <p class="notice-warning_product"><?php esc_html_e('Following products might not get discount as you have chosen from different language', 'woo-discount-rules-pro'); ?></p>
                                <?php
                                foreach ($conflict_products as $conflict_product){
                                    ?>
                                    <div class="notice-warning_product"><?php echo esc_html($conflict_product); ?></div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </fieldset>
                <div class="awdr-buyx-gety-option wdr-select-filed-hight">
                    <select name="buyx_gety_adjustments[ranges][<?php echo esc_attr($buyx_gety_index); ?>][free_type]"
                            class="awdr-bogo-discount-type buyx_gety_discount_select"
                            data-parent="awdr-buyx-gety-option"
                            data-siblings="awdr-gety-value">
                        <option value="free_product" <?php echo (isset($buyx_gety_adjustment->free_type) && $buyx_gety_adjustment->free_type == 'free_product') ? 'selected' : ''; ?>><?php esc_html_e('Free', 'woo-discount-rules-pro') ?></option>
                        <option value="percentage" <?php echo (isset($buyx_gety_adjustment->free_type) && $buyx_gety_adjustment->free_type == 'percentage') ? 'selected' : ''; ?>><?php esc_html_e('Percentage discount', 'woo-discount-rules-pro') ?></option>
                        <option value="flat" <?php echo (isset($buyx_gety_adjustment->free_type) && $buyx_gety_adjustment->free_type == 'flat') ? 'selected' : ''; ?>><?php esc_html_e('Fixed discount', 'woo-discount-rules-pro') ?></option>
                    </select>
                    <span class="wdr_desc_text"><?php esc_html_e('Discount type ', 'woo-discount-rules-pro'); ?></span>
                </div>
                <div class="awdr-gety-value"
                     style="<?php echo (isset($buyx_gety_adjustment->free_type) && $buyx_gety_adjustment->free_type != 'free_product') ? '' : 'display: none;'; ?>">
                    <input type="number"
                           name="buyx_gety_adjustments[ranges][<?php echo esc_attr($buyx_gety_index); ?>][free_value]"
                           class="awdr-buyx-gety-number-box awdr_value_selector bxgy-val"
                           placeholder="<?php esc_attr_e('Value', 'woo-discount-rules-pro'); ?>" min="0" step="any"
                           value="<?php echo (isset($buyx_gety_adjustment->free_value) && !empty($buyx_gety_adjustment->free_value)) ? esc_attr($buyx_gety_adjustment->free_value) : ''; ?>"
                    >
                    <span class="wdr_desc_text"><?php echo (isset($buyx_gety_adjustment->free_type) && $buyx_gety_adjustment->free_type == 'flat') ? esc_html__('Discount value ', 'woo-discount-rules-pro') : esc_html__('Discount percentage ', 'woo-discount-rules-pro'); ?></span>
                </div>
                <div class="awdr-buyx-gety-recursive">
                    <?php
                        $is_enabled_rtl = \WDRPro\App\Helpers\CoreMethodCheck::isRTLEnable();?>
                        <div class="<?php echo (!$is_enabled_rtl) ? 'page__toggle' : ''; ?>">
                            <label class="<?php echo (!$is_enabled_rtl) ? 'toggle' : ''; ?>">
                                <input class="<?php echo (!$is_enabled_rtl) ? 'toggle__input' : ''; ?> awdr-bogo-recurcive" type="checkbox" style="<?php echo ($is_enabled_rtl) ? 'height: 18px !important;' : ''; ?>"
                                       name="buyx_gety_adjustments[ranges][<?php echo esc_attr($buyx_gety_index); ?>][recursive]"
                                       data-recursive-row="buyx_gety_individual_range"
                                       data-recursive-parent="awdr-buyx-gety-recursive"
                                       data-hide-add-range="hide_gety_recursive"
                                       data-bogo-max-range="awdr-buyx-gety-max"
                                       data-bogo-min-range="awdr-buyx-gety-min"
                                       data-bogo-border="wdr-buyx-gety-discount-inner"
                                       data-ranges-row-parent="awdr_bogo_main"
                                       value="1" <?php echo (isset($buyx_gety_adjustment->recursive) && !empty($buyx_gety_adjustment->recursive)) ? 'checked' : ''; ?>>
                                <span class="<?php echo (!$is_enabled_rtl) ? 'toggle__label' : ''; ?>">
                                    <span class="<?php echo (!$is_enabled_rtl) ? 'toggle__text' : ''; ?>"><?php esc_html_e('Recursive?', 'woo-discount-rules-pro'); ?></span>
                                </span>
                            </label>
                        </div>
                </div>
                <div class="wdr-btn-remove" style="vertical-align: middle;">
                                                    <span class="dashicons dashicons-no-alt wdr_discount_remove"
                                                          data-rmdiv="bulk_range_group"></span>
                </div>
            </div>
        </div>
    </div>
</div>