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

namespace WDR_COL\App\Models;

use Wdr\App\Helpers\Rule;
use Wdr\App\Models\DBTable;

defined('ABSPATH') or exit;

class Collections extends Model
{
    /**
     * Table name and output type
     *
     * @var string
     */
    const TABLE_NAME = 'wdr_collections', OUTPUT_TYPE = OBJECT;

    /**
     * To store quires results
     *
     * @var array
     */
    protected static $collections;

    /**
     * Create the table
     *
     * @return void
     */
    public function create()
    {
        $query = "CREATE TABLE {table} (
		        `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `type` varchar(100) NOT NULL,
                `conditions` text NOT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_on` datetime	 DEFAULT NULL,
                `modified_by` int(11) DEFAULT NULL,
                `modified_on` datetime	 DEFAULT NULL,
                PRIMARY KEY (`id`)
			) {charset_collate};";
        self::execDBQuery($query);
    }

    /**
     * Alter the table
     *
     * @return void
     */
    public function alter()
    {
        // silence is golden
    }

    /**
     * Get all available collections
     *
     * @param int|array|null $id_or_ids
     * @param array|null $columns
     * @param bool $cache
     */
    public static function get($id_or_ids = null, $columns = null, $cache = true)
    {
        if (is_numeric($id_or_ids)) {
            $id = $id_or_ids;
            if ($cache && isset(self::$collections[$id])) {
                return self::$collections[$id];
            }
            return self::$collections[$id] = self::getRow(['id' => $id], ['%d'], $columns);
        } else {
            $where_query = '';
            $cache_key = 'all';
            if (is_array($id_or_ids)) {
                $collection_ids = array_map('absint', $id_or_ids);
                $collection_ids = implode(",", $collection_ids);
                $where_query = "WHERE id IN ({$collection_ids})";
                $cache_key = md5($collection_ids);
            }
            if ($cache && isset(self::$collections[$cache_key])) {
                return self::$collections[$cache_key];
            }
            $fields = self::prepareFieldsQuery($columns);
            $query = "SELECT $fields FROM {table} $where_query";
            return self::$collections[$cache_key] = self::getResults($query);
        }
    }

    /**
     * Get title.
     *
     * @param int $id
     * @return string|false
     */
    public static function getTitle($id)
    {
        $row = self::getRow(['id' => $id], ['%d'], ['title']);
        return $row && isset($row->title) ? $row->title : false;
    }

    /**
     * Save collection
     * @param $format
     * @param $values
     * @param null $collection_id
     * @return int|null
     */
    public static function save($format, $values, $collection_id = NULL)
    {
        if (!empty($collection_id)) {
            $collection_id = intval($collection_id);
            self::updateById($collection_id, $values, $format);
        } else {
            $collection_id = self::insert($values, $format);
        }

        if ($collection_id) {
            $linked_rule_ids = self::getCollectionLinkedRules($collection_id);
            if (!empty($linked_rule_ids)) {
                $rules = DBTable::getRules($linked_rule_ids);
                $conditions = isset($values['conditions']) ? $values['conditions'] : null;
                self::updateRulesData($rules, $collection_id, $conditions);
            }
        }

        return $collection_id;
    }

    /**
     * Update Rule Data
     *
     * @param array $rules_data
     * @param int $collection_id
     * @param array $new_conditions
     */
    public static function updateRulesData($rules_data, $collection_id, $new_conditions)
    {
        if (!empty($rules_data) && !empty($new_conditions)) {
            foreach ($rules_data as $rule_data) {
                $rule = new Rule($rule_data);
                $additional_data = $rule->getAdditionalRuleData();
                if (!empty($additional_data) && isset($additional_data['collections'])) {
                    foreach ($additional_data['collections'] as $id => $conditions) {
                        if ($id == $collection_id) {
                            $additional_data['collections'][$id] = json_decode($new_conditions, true);
                        }
                    }
                    DBTable::updateRuleAdditionalData($rule->getId(), json_encode($additional_data));
                }
            }
        }
    }

    /**
     * Get Collection linked Rules (ids)
     *
     * @param $collection_id
     * @return array
     */
    public static function getCollectionLinkedRules($collection_id)
    {
        $collection_id = intval($collection_id);
        if ($collection_id) {
            $query = "SELECT rule_id FROM {table} WHERE collection_id = {$collection_id}";
            $results = RuleCollections::getResults($query);
            if (is_array($results) && !empty($results)) {
                return array_map(function ($row) { return isset($row->rule_id) ? $row->rule_id : 0; }, $results);
            }
        }
        return array();
    }

    /**
     * Update Rule linked Collections (ids)
     *
     * @param int $rule_id
     * @param array $collection_ids
     * @return bool
     */
    public static function updateRuleLinkedCollections($rule_id, $collection_ids)
    {
        if (!is_array($collection_ids)) {
            return false;
        }

        RuleCollections::delete(['rule_id' => (int) $rule_id], ['%d']);
        foreach ($collection_ids as $collection_id) {
            RuleCollections::insert(['rule_id' => (int) $rule_id, 'collection_id' => (int) $collection_id], ['%d', '%d']);
        }
        return true;
    }

    /**
     * Delete Rule/s linked Collections (ids)
     *
     * @param int|array $rule_id_or_ids
     * @return bool
     */
    public static function deleteRuleLinkedCollections($rule_id_or_ids)
    {
        if (is_array($rule_id_or_ids)) {
            $rule_ids = array_map('absint', $rule_id_or_ids);
            $rule_ids = implode(",", $rule_ids);
            RuleCollections::execQuery("DELETE FROM {table} WHERE rule_id IN ({$rule_ids})");
        } else {
            RuleCollections::delete(['rule_id' => (int) $rule_id_or_ids], ['%d']);
        }
        return true;
    }
}