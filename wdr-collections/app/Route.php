<?php
/**
 * Woo Discount Rules: Collections
 *
 * @package   wdr-collections
 * @author    Anantharaj B <anantharaj@flycart.org>
 * @copyright 2022 Flycart
 * @license   GPL-3.0-or-later
 * @link      https://flycart.org
 */

namespace WDR_COL\App;

defined('ABSPATH') or exit;

class Route extends Core
{
    /**
     * Plugin hooks
     */
    protected static function hooks()
    {
        if (is_admin()) {
            $ajax = new Controllers\Admin\Ajax();
            add_action('wp_ajax_wdr_col_ajax', [$ajax, 'auth']);
            add_filter('advanced_woo_discount_rules_backend_end_ajax_actions', [$ajax, 'backendActions']);

            $page = new Controllers\Admin\Page();
            add_filter('advanced_woo_discount_rules_page_tabs', [$page, 'addTab']);
            add_action('admin_init', [$page, 'loadAssets']);
        }

        $filter = new Controllers\Common\Filters();

        // to load custom filter and it's views
        add_filter('advanced_woo_discount_rules_filters', [$filter, 'addFilter'], 100);
        add_action('advanced_woo_discount_rules_admin_filter_fields', [$filter, 'loadFilterFields'], 100, 3);

        // to store and maintain a copy of the collections in rule additional data
        add_filter('advanced_woo_discount_rules_update_additional_data_before_save_rule', [$filter, 'storeCollectionsCopyInRule'], 100, 6);
        add_action('advanced_woo_discount_rules_after_save_rule', [$filter, 'updateRuleCollectionsIndex'], 100, 4);
        add_action('advanced_woo_discount_rules_after_delete_rule', [$filter, 'deleteRuleCollectionsIndex'], 100, 1);
        add_action('advanced_woo_discount_rules_after_delete_rules', [$filter, 'deleteRuleCollectionsIndex'], 100, 1);

        // to make collection based filter pass
        add_filter('advanced_woo_discount_rules_is_valid_filter_type', [$filter, 'changeFilterProcess'], 100, 6);
        add_filter('advanced_woo_discount_rules_load_custom_filter_data', [$filter, 'loadExtraFilterData'], 100, 2);
        add_filter('advanced_woo_discount_rules_process_custom_filter', [$filter, 'compareWithCollections'], 100, 8);

        $bxgy = new Controllers\Common\BXGY();
        if (is_admin()) {
            add_action('advanced_woo_discount_rules_after_bxgy_discount_type_options', [$bxgy, 'addDiscountType']);
            add_action('advanced_woo_discount_rules_bxgy_discount_get_y_section', [$bxgy, 'loadCollectionsSelect'], 10, 3);
        }
        add_filter('advanced_woo_discount_rules_is_buy_x_get_y_discount_filter_passed', [$bxgy, 'isFilterPassed'], 10, 5);
        add_filter('advanced_woo_discount_rules_is_valid_cheapest_cart_item_based_on_discount_type', [$bxgy, 'isValidCartItem'], 10, 5);

        Controllers\Common\BuyXGetYCheapestFromCollections::init();
    }
}