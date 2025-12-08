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
use WDR_COL\App\Helpers\Collection;
use WDR_COL\App\Models\Collections;

defined('ABSPATH') or exit;

class Filters
{
    /**
     * Filter types
     *
     * @return array
     */
    public static function filterTypes() {
        return [
            'filter_collections' => array(
                'label' => __('Filter collections', 'wdr-collections'),
                'group' => __('Collections', 'wdr-collections'),
                'template' => WDR_COL_PLUGIN_PATH . 'app/Views/Admin/Filters/Collections.php',
            )
        ];
    }

    /**
     * Add filters
     */
    public static function addFilter($filter_types)
    {
        return array_merge($filter_types, self::filterTypes());
    }

    /**
     * To load filter fields
     *
     * @param object $rule
     * @param object $filter
     * @param int $filter_row_count
     * @return bool
     */
    public static function loadFilterFields($rule, $filter, $filter_row_count)
    {
        if (isset($filter->type) && $filter->type == "products") {
            return false;
        }

        $data['rule'] = $rule;
        $data['filter'] = isset($filter) ? $filter : null;
        $data['woocommerce_helper'] = new \Wdr\App\Helpers\Woocommerce();
        $data['filter_row_count'] = $filter_row_count;
        if (isset($data['filter']->type)) {
            $filter_types = self::filterTypes();
            $filter_type = $data['filter']->type;
            $filters = array_keys($filter_types);
            if (in_array($filter_type, $filters)) {
                if (isset($filter_types[$filter_type]['template'])) {
                    $template = new \Wdr\App\Helpers\Template();
                    $template->setPath($filter_types[$filter_type]['template']);
                    $template->setData($data);
                    $template->display();
                }
            }
        }
        return false;
    }

    /**
     * To store Collections data (copy) in Rule Additional data
     *
     * @param array $rule_additional
     * @param array $post
     * @param object $rule
     * @param string|int $rule_id
     * @param array $rule_filters
     * @param array $rule_conditions
     * @return array
     */
    public static function storeCollectionsCopyInRule($rule_additional, $post, $rule, $rule_id, $rule_filters, $rule_conditions)
    {
        $rule_collection_ids = array();
        if (!empty($rule_filters)) {
            foreach ($rule_filters as $filter) {
                if ($rule->getFilterType($filter) == 'filter_collections') {
                    $rule_collection_ids = array_merge($rule_collection_ids, (array) $rule->getFilterOptionValue($filter));
                }
            }
        }

        if (isset($post['buyx_gety_adjustments']) && isset($post['buyx_gety_adjustments']['type']) && $post['buyx_gety_adjustments']['type'] == 'bxgy_collection') {
            if (!empty($post['buyx_gety_adjustments']['ranges']) && is_array($post['buyx_gety_adjustments']['ranges'])) {
                foreach ($post['buyx_gety_adjustments']['ranges'] as $range) {
                    if (!empty($range['collections']) && is_array($range['collections'])) {
                        $rule_collection_ids = array_merge($rule_collection_ids, $range['collections']);
                    }
                }
            }
        }

        if (!empty($rule_collection_ids)) {
            $rule_collections = array();
            $collections = Collections::get(array_unique($rule_collection_ids));
            foreach ($collections as $collection) {
                if (isset($collection->id) && isset($collection->conditions) && !isset($rule_collections[$collection->id])) {
                    $rule_collections[$collection->id] = json_decode($collection->conditions);
                }
            }
            $rule_additional['collections'] = $rule_collections;
        } else {
            $rule_additional['collections'] = array();
        }
        return $rule_additional;
    }

    /**
     * Update Rule-Collections index
     *
     * @param $rule_id
     * @param $post
     * @param $arg
     * @param $rule_additional
     * @return void
     */
    public static function updateRuleCollectionsIndex($rule_id, $post, $arg, $rule_additional)
    {
        if (isset($rule_additional['collections'])) {
            $rule_collection_ids = array_keys($rule_additional['collections']);
            Collections::updateRuleLinkedCollections($rule_id, $rule_collection_ids);
        }
    }

    /**
     * Remove Rule-Collections index
     *
     * @param int|array $rule_id_or_ids
     */
    public static function deleteRuleCollectionsIndex($rule_id_or_ids)
    {
        Collections::deleteRuleLinkedCollections($rule_id_or_ids);
    }

    /**
     * Load extra data to filter helper
     */
    public static function loadExtraFilterData($data, $rule)
    {
        $additional_data = $rule->getAdditionalRuleData(false);
        if (isset($additional_data->collections)) {
            $data['collections'] = $additional_data->collections;
        }
        return $data;
    }

    /**
     * Use change filter process
     */
    public static function changeFilterProcess($status, $product, $type, $method, $values, $extra_data)
    {
        if ($type == 'filter_collections' && $method == 'exclude') {
            $status = true;
        } elseif (isset($extra_data['use_valid_filter'])) {
            $status = $extra_data['use_valid_filter'];
        }
        return $status;
    }

    /**
     * Compare with Collections
     */
    public static function compareWithCollections($processing_result, $product, $type, $method, $values, $sale_badge, $product_table, $extra_data)
    {
        if ($type == 'filter_collections' && isset($extra_data['collections'])) {
            $filter_helper = new Filter();
            $collection_helper = new Collection();
            $collections = (array) $extra_data['collections'];
            $collections_passed = 0;
            foreach ($values as $id) {
                if (!isset($collections[$id])) {
                    return false;
                }
                $conditions = $collections[$id];
                $filters = $collection_helper->getFilterFromConditions($conditions);
                $relationship = $collection_helper->getRelationshipFromConditions($conditions);
                if (!empty($filters) && !empty($relationship)) {
                    $filters_passed = 0;
                    $filters_count = count((array) $filters);
                    foreach ($filters as $filter) {
                        if (true === $filter_helper->matchFilters($product, array($filter), $sale_badge, $product_table, ['use_valid_filter' => true])) {
                            $filters_passed++;
                        }
                        if ($relationship == 'or' && $filters_passed == 1) {
                            $collections_passed++;
                            break;
                        }
                        if ($relationship == 'and' && $filters_passed == $filters_count) {
                            $collections_passed++;
                            break;
                        }
                    }
                }
            }

            if ($collections_passed > 0) {
                return $method == 'include' ? true : false;
            } else {
                return $method == 'exclude' ? true : false;
            }
        }
        return $processing_result;
    }
}