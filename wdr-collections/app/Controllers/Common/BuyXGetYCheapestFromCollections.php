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

namespace WDR_COL\App\Controllers\Common;

defined('ABSPATH') or exit;

class BuyXGetYCheapestFromCollections
{
    protected static $type = 'cheapest_from_collections';
    protected static $discount_type_key = 'buy_x_get_y_cheapest_from_collections_discount';

    protected static $cheapest_items = array();
    protected static $cheapest_products = array();
    protected static $calculated_discounts = array();

    use \WDRPro\App\Rules\CheapestCommon;

    /**
     * Initialize
     * */
    public static function init()
    {
        self::hooks();
    }

    /**
     * Add hooks
     * */
    protected static function hooks(){
        add_filter('advanced_woo_discount_rules_discounts_of_each_rule', array(__CLASS__, 'setDiscountValue'), 10, 9);
        add_filter('advanced_woo_discount_rules_calculated_discounts_of_each_rule', array(__CLASS__, 'setCalculatedDiscountValue'), 10, 6);
        add_filter('advanced_woo_discount_rules_calculated_discounts_of_each_rule_for_ajax_price', array(__CLASS__, 'setCalculatedDiscountValue'), 10, 6);
        add_filter('advanced_woo_discount_rules_has_any_discount', array(__CLASS__, 'getAdjustment'), 10, 2);
        add_filter('advanced_woo_discount_rules_process_discount_for_product_which_do_not_matched_filters', array(__CLASS__, 'applyDiscountForNonMatchedFilterProduct'), 10, 4);
    }

    /**
     * Load type with data
     * */
    public static function getType($matched_rule){
        return array(
            'type' => self::$type,
            'collection_ids' => $matched_rule->collections
        );
    }

    /**
     * check the rule has product discount
     * @return bool
     */
    public static function hasDiscount($rule)
    {
        if (isset($rule->buy_x_get_y_adjustments)) {
            if (!empty($rule->buy_x_get_y_adjustments) && $rule->buy_x_get_y_adjustments != '{}' && $rule->buy_x_get_y_adjustments != '[]') {
                $rule_data = json_decode($rule->buy_x_get_y_adjustments);
                if(!empty($rule_data)){
                    if(isset($rule_data->type) && $rule_data->type == "bxgy_collection"){
                        if(isset($rule_data->mode) && in_array($rule_data->mode, array('cheapest', 'highest'))){
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
