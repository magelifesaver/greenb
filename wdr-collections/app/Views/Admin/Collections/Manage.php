<?php defined('ABSPATH') or exit ?>

<div style="overflow:auto">
    <div class="awdr-container"><br/>
        <?php
        if (isset($wdr_404_found) && !empty($wdr_404_found)){
            echo "<h2 style='color: red;'>" . esc_html($wdr_404_found) . "</h2>";
        } else { ?>
            <form id="wdr-save-collection" name="collection_generator">
            <div class="wdr-sticky-header" id="ruleHeader">
                <div class="wdr-enable-rule">
                    <div class="wdr-field-title" style="width: 45%">
                        <input class="wdr-title" type="text" name="title" placeholder="<?php echo esc_attr(__('Collection Title', 'wdr-collections')); ?>"
                               value="<?php echo esc_attr($collection->getTitle()); ?>"><!--awdr-clear-both-->
                    </div>
                    <?php
                    if (isset($collection_id) && !empty($collection_id)) { ?>
                        <span class="wdr_desc_text awdr_valide_date_in_desc">
                        <?php esc_html_e('#Collection ID: ', 'wdr-collections'); ?><b><?php echo esc_html($collection_id); ?></b>
                        </span><?php
                    } ?>
                    <div class="awdr-common-save">
                        <button type="submit" class="btn btn-primary wdr_save_collection">
                            <?php _e('Save', 'wdr-collections'); ?></button>
                        <button type="button" class="btn btn-success wdr_save_close_collection">
                            <?php _e('Save & Close', 'wdr-collections'); ?></button>
                        <a href="<?php echo esc_url(admin_url("admin.php?" . http_build_query(array('page' => WDR_SLUG, 'tab' => 'collections')))); ?>"
                           class="btn btn-danger" style="text-decoration: none">
                            <?php _e('Cancel', 'wdr-collections'); ?></a>
                    </div>
                </div>
                <!-- ------------------------Collection Filter Section Start------------------------ -->
                <div class="wdr-rule-filters-and-options-con awdr-filter-section">
                    <div class="wdr-rule-menu">
                        <h2 class="awdr-filter-heading"><?php _e("Filter", 'wdr-collections'); ?></h2>
                        <div class="awdr-filter-content">
                            <p><?php _e("Choose which <b>gets</b> discount (products/categories/attributes/SKU and so on )", 'wdr-collections'); ?></p>
                            <p><?php _e("Note : You can also exclude products/categories.", 'wdr-collections'); ?></p>
                        </div>
                    </div>
                    <div class="wdr-rule-options-con">
                        <div id="wdr-save-rule" name="rule_generator">
                            <input type="hidden" name="action" value="wdr_col_ajax">
                            <input type="hidden" name="method" value="save_collection">
                            <input type="hidden" name="collection_type" value="filter">
                            <input type="hidden" name="awdr_nonce" value="<?php echo esc_attr(\Wdr\App\Helpers\Helper::create_nonce('wdr_ajax_save_collection')); ?>">
                            <input type="hidden" name="wdr_save_close" value="">
                            <div id="rule_template">
                                <?php include 'Filters/Main.php'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ------------------------Collection Filter Section End-------------------------- -->
            </div>
            <input type="hidden" name="wdr_ajax_select2" value="<?php echo esc_attr(\Wdr\App\Helpers\Helper::create_nonce('wdr_ajax_select2')); ?>">
            </form><?php

        }?>
    </div>
</div>

<div class="awdr-default-template" style="display: none;">
    <?php include "Others/CommonTemplates.php"; ?>
</div>


