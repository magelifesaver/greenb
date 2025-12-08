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

use Wdr\App\Helpers\Filter;

defined('ABSPATH') or exit;

class BXGY
{
    /**
     * To add discount type option.
     *
     * @hooked advanced_woo_discount_rules_after_bxgy_discount_type_options
     */
    public function addDiscountType($selected_type)
    {
        ?>
        <option value="bxgy_collection" <?php if ($selected_type == 'bxgy_collection') { echo 'selected'; } ?>>
            <?php esc_html_e('Buy X Get Y - Collections', 'wdr-collections') ?>
        </option>
        <?php
    }

    /**
     * To load collection select section.
     *
     * @hooked advanced_woo_discount_rules_bxgy_discount_get_y_section
     */
    public function loadCollectionsSelect($buyx_gety_index, $get_buyx_gety_types, $buyx_gety_adjustment)
    {
        ?>
        <div class="awdr-buyx-gety-collection wdr-select-filed-hight wdr-cart-search_box bxgy_collection"
             style="vertical-align: bottom;min-width: 250px; <?php echo ($get_buyx_gety_types != 'bxgy_collection') ? 'display: none;' : '' ?>">
            <?php $values = isset($buyx_gety_adjustment->collections) ? $buyx_gety_adjustment->collections : array(); ?>
            <select class="bxgy-collection-selector wdr_col_bxgy_select2" multiple
                    data-list="filter_collections"
                    data-placeholder="<?php esc_attr_e('Search Collections', 'wdr-collections'); ?>"
                    name="buyx_gety_adjustments[ranges][<?php echo esc_attr($buyx_gety_index); ?>][collections][]"><?php
                if ($values) {
                    foreach ($values as $value) {
                        ?>
                        <option value="<?php echo esc_attr($value); ?>" selected>
                            <?php echo esc_html(\WDR_COL\App\Models\Collections::getTitle($value)); ?>
                        </option>
                        <?php
                    }
                }
                ?>
            </select>
            <span class="wdr_desc_text awdr-clear-both "><?php esc_html_e('Select Collections', 'wdr-collections'); ?></span>
        </div>
        <?php
    }

    /**
     * Check is filter passed.
     */
    public function isFilterPassed($filter_passed, $rule, $product, $get_y_type, $get_y_ranges)
    {
        if ($get_y_type == 'bxgy_collection') {
            if (!empty($get_y_ranges)) {
                foreach ($get_y_ranges as $range) {
                    $collection_ids = isset($range->collections) ? $range->collections : array();
                    if (self::isCollectionsPassed($rule, $product, $collection_ids)) {
                        $filter_passed = true;
                        break;
                    }
                }
            }
        }
        return $filter_passed;
    }

    /**
     * Check valid cart item to apply the cheapest discount.
     */
    public function isValidCartItem($is_valid_item, $product, $type_and_values, $rule, $cart_item)
    {
        if (isset($type_and_values) && isset($type_and_values['type']) && $type_and_values['type'] == 'cheapest_from_collections') {
            if (isset($cart_item['data']) && is_object($cart_item['data'])) {
                $product = $cart_item['data'];
            }
            return self::isCollectionsPassed($rule, $product, $type_and_values['collection_ids']);
        }
        return $is_valid_item;
    }

    /**
     * Check is collections passed.
     *
     * @return bool
     */
    private static function isCollectionsPassed($rule, $product, $collection_ids)
    {
        $extra_data = Filters::loadExtraFilterData(array(), $rule);
        if (isset($extra_data['collections'])) {
            $filters = array(array('type' => 'filter_collections', 'method' => 'include', 'value' => $collection_ids));
            return (new Filter())->matchFilters($product, $filters,false, false, $extra_data);
        }
        return false;
    }
}