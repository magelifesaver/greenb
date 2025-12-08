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

namespace WDR_COL\App\Helpers;

use stdClass;
use Wdr\App\Helpers\Rule;
use Wdr\App\Helpers\Woocommerce;
use WDR_COL\App\Models\Collections;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Collection
{
    public $collection, $available_conditions;
    public static $woocommerce_helper, $filters = array();

    /**
     * @param $collection_data
     * @param $available_conditions
     * @return $this
     */
    function __construct($collection_data = array(), $available_conditions = array())
    {
        if (!empty($collection_data)) {
            $this->collection = $collection_data;
            //$this->available_conditions = $available_conditions;
            self::$woocommerce_helper = (empty(self::$woocommerce_helper)) ? new Woocommerce() : self::$woocommerce_helper;
        }
        return $this;
    }

    /**
     * To get all collections
     * @return array
     */
    function getAllCollections()
    {
        $available_collections = Collections::get();
        return $this->getCollectionObject($available_collections);
    }

    /**
     * To convert collections to collection object
     * @param $collections
     * @return array
     */
    function getCollectionObject($collections)
    {
        $collection_list = array();
        if (!empty($collections)) {
            if (is_array($collections)) {
                foreach ($collections as $collection) {
                    $collection_obj = new self($collection);
                    $collection_id = $collection_obj->getId();
                    $collection_list[$collection_id] = $collection_obj;
                }
            } else {
                $collection_list = new self($collections);
            }
        }
        return $collection_list;
    }

    /**
     * set the default collection obj
     * @return stdClass
     */
    function defaultCollectionObj()
    {
        //Todo: change default object if any modification happen in table structure
        $obj = new stdClass();
        $obj->id = NULL;
        $obj->title = '';
        $obj->type = NULL;
        $obj->conditions = NULL;
        return $obj;
    }

    /**
     * Get the collection ID
     * @return int|null
     */
    function getId()
    {
        if (isset($this->collection->id)) {
            return $this->collection->id;
        }
        return NULL;
    }

    /**
     * Get all collections and set object
     * @param $collection_ids array
     * @return array
     */
    function getCollections($collection_ids = null)
    {
        $collections = Collections::get($collection_ids);
        return $this->getCollectionObject($collections);
    }

    /**
     * Get particular and set object
     * @param $collection_id int
     * @return array
     */
    function getCollection($collection_id)
    {
        $collection = Collections::get((int) $collection_id);
        if (empty($collection)) {
            $collection = $this->defaultCollectionObj();
        }
        return $this->getCollectionObject($collection);
    }

    /**
     * Collection title
     * @return string|null
     */
    function getTitle()
    {
        if (isset($this->collection->title)) {
            return $this->collection->title;
        }
        return NULL;
    }

    /**
     * Get collection type
     * @return string
     */
    function getType()
    {
        if (isset($this->collection->type)) {
            return $this->collection->type;
        }
        return null;
    }

    /**
     * Get collection created by
     * @return int
     */
    function getCollectionCreatedBy()
    {
        if (isset($this->collection->created_by)) {
            return $this->collection->created_by;
        }
        return false;
    }

    /**
     * Get collection created on
     * @return int
     */
    function getCollectionCreatedOn()
    {
        if (isset($this->collection->created_on)) {
            return $this->collection->created_on;
        }
        return false;
    }

    /**
     * Get collection modified by
     * @return int
     */
    function getCollectionModifiedBy()
    {
        if (isset($this->collection->modified_by)) {
            return $this->collection->modified_by;
        }
        return false;
    }

    /**
     * Get collection modified on
     * @return int
     */
    function getCollectionModifiedOn()
    {
        if (isset($this->collection->modified_on)) {
            return $this->collection->modified_on;
        }
        return false;
    }

    /**
     * Check if the rule has filter
     * @return bool
     */
    function hasFilter()
    {
        if (isset($this->collection->type) && $this->collection->type == 'filter' && isset($this->collection->conditions)) {
            if (empty($this->collection->conditions) || $this->collection->conditions == '{}' || $this->collection->conditions == '[]') {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the collection filter
     * @return array|object|false
     */
    function getFilter()
    {
        if ($this->hasFilter()) {
            $conditions = json_decode($this->collection->conditions);
            if (is_object($conditions) && isset($conditions->rule)) {
                return $conditions->rule;
            }
        }
        return false;
    }

    /**
     * Get the collection relationship
     * @param string|bool $default
     * @return string
     */
    function getRelationship($default = 'and')
    {
        if (isset($this->collection->conditions)) {
            if (!empty($this->collection->conditions) && $this->collection->conditions != '{}' && $this->collection->conditions != '[]') {
                $conditions = json_decode($this->collection->conditions);
                if (is_object($conditions) && isset($conditions->relationship)) {
                    return (string) $conditions->relationship;
                }
            }
        }
        return $default;
    }

    /**
     * Get the collection filter from conditions
     * @param object $conditions
     * @return array|object|false
     */
    function getFilterFromConditions($conditions)
    {
        if (isset($conditions->rule) && !empty($conditions->rule)) {
            return $conditions->rule;
        }
        return false;
    }

    /**
     * Get the collection filter from conditions
     * @param object $conditions
     * @return string|false
     */
    function getRelationshipFromConditions($conditions)
    {
        if (isset($conditions->relationship) && !empty($conditions->relationship)) {
            return $conditions->relationship;
        }
        return false;
    }

    /**
     * save collection
     * @param $post
     * @return int|null
     */
    function save($post)
    {
        $rule = new Rule();
        //$current_time = current_time('mysql', true);
        $current_date_time = '';
        if (function_exists('current_time')) {
            $current_time = current_time('timestamp');
            $current_date_time = date('Y-m-d H:i:s', $current_time);
        }
        $current_user = get_current_user_id();
        $collection_id = intval($rule->getFromArray($post, 'edit_collection', NULL));
        $title = $rule->getFromArray($post, 'title', esc_html__('Untitled Collection', 'wdr-collections'));
        $title = Rule::validateHtmlBeforeSave($title);
        $collection_type = $rule->getFromArray($post, 'collection_type', 'filter');
        $collection_conditions = [
            'relationship' => $rule->getFromArray($post, 'condition_relationship', 'and'),
            'rule' => $rule->getFromArray($post, 'filters', array()),
        ];
        $rule_title = (empty($title)) ? esc_html__('Untitled Collection', 'wdr-collections') : $title;
        $arg = array(
            'title' => sanitize_text_field($rule_title),
            'type' => $collection_type,
            'conditions' => json_encode($collection_conditions),
        );
        if (!empty($collection_id)) {
            $arg['modified_by'] = intval($current_user);
            $arg['modified_on'] = esc_sql($current_date_time);
            $column_format = array('%s', '%s', '%s', '%d', '%s');
        }else{
            $arg['created_by'] = intval($current_user);
            $arg['created_on'] = esc_sql($current_date_time);
            $arg['modified_by'] = intval($current_user);
            $arg['modified_on'] = esc_sql($current_date_time);
            $column_format = array('%s', '%s', '%s', '%d', '%s', '%d', '%s');
        }
        $arg = apply_filters( 'advanced_woo_discount_rules_before_save_collection_column', $arg, $collection_id, $post);

        $collection_id = Collections::save($column_format, $arg, $collection_id);
        if($collection_id){
            do_action('advanced_woo_discount_rules_after_save_collection', $collection_id, $post, $arg);
        }
        return $collection_id;
    }
}