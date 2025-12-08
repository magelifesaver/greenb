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

namespace WDR_COL\App\Controllers\Admin;

defined('ABSPATH') or exit;

use Wdr\App\Helpers\Helper;
use Wdr\App\Helpers\Rule;
use Wdr\App\Models\DBTable;
use WDR_COL\App\Helpers\Collection;
use WDR_COL\App\Helpers\Validation;
use WDR_COL\App\Controllers\Controller;
use WDR_COL\App\Models\Collections;

class Ajax extends Controller
{
    /**
     * Authenticated (logged in) users access methods.
     *
     * @var array
     */
    public $ajax_methods = [
        'save_collection',
        'delete_collection',
        'filter_collections',
        'get_collection_linked_rules',
    ];

    /**
     * Guest (not logged in) users access methods.
     *
     * @var array
     */
    public $ajax_nopriv_methods = [
        // define methods as array value
    ];

    /**
     * Ajax request handler for logged-in users.
     *
     * @return void
     */
    public function auth()
    {
        $method = $this->app->request->get('method', '', 'post', 'text');
        if (in_array($method, $this->ajax_methods) && method_exists($this, $method)) {
            wp_send_json_success($this->$method());
        } else {
            wp_send_json_error(esc_html__("Method not exists.", 'wdr-collections'));
        }
    }

    /**
     * Ajax request handler for logged-out users.
     *
     * @return void
     */
    public function guest()
    {
        $method = $this->app->request->get('method', '', 'post', 'text');
        if (in_array($method, $this->ajax_nopriv_methods) && method_exists($this, $method)) {
            wp_send_json_success($this->$method());
        } else {
            wp_send_json_error(esc_html__("Method not exists.", 'wdr-collections'));
        }
    }

    /**
     * Backend actions
     */
    public function backendActions($actions) {
        return array_merge($actions, array('wdr_col_ajax'));
    }

    /**
     * Search filter collections
     *
     * @return array
     */
    public function filter_collections()
    {
        Helper::validateRequest('wdr_ajax_select2');
        $query = $this->app->request->get('query', '');
        $query = Helper::filterSelect2SearchQuery($query);
        $query = Collections::db()->esc_like($query);
        $limit = 20;
        $collections = Collections::getResults("SELECT id, title FROM {table} WHERE title LIKE '%{$query}%' LIMIT {$limit}");
        return array_map(function ($collection) {
            return array(
                'id' => (string) $collection->id,
                'text' => '#' . $collection->id . ' ' . $collection->title,
            );
        }, $collections);
    }

    /**
     * save collection
     */
    private function save_collection()
    {
        Helper::validateRequest('wdr_ajax_save_collection');
        $validation_result = Validation::validateCollections($_POST);
        if ($validation_result === true) {
            $post = $this->app->request->post;
            $collection_helper = new Collection();
            $post['title'] = (isset($_POST['title'])) ? stripslashes(sanitize_text_field($_POST['title'])) : '';
            $collection_id = $collection_helper->save($post);
            if (isset($collection_id['coupon_exists'])) {
                $coupon_message = $collection_id['coupon_exists'];
                wp_send_json_error(array('coupon_message' => $coupon_message));
                die;
            }
            $redirect_url = false;
            if (!empty($this->app->request->get('wdr_save_close', ''))) {
                $redirect_url = admin_url("admin.php?" . http_build_query(array('page' => WDR_SLUG, 'tab' => 'collections')));
            } elseif (empty($this->app->request->get('edit_collection', ''))) {
                $redirect_url = admin_url("admin.php?" . http_build_query(array('page' => WDR_SLUG, 'tab' => 'collections', 'task' => 'view', 'id' => $collection_id)));
            }
            wp_send_json_success(array('collection_id' => $collection_id, 'redirect' => $redirect_url));
        } else {
            wp_send_json_error($validation_result);
        }
    }

    /**
     * Delete collection
     */
    private function delete_collection()
    {
        $deleted = 'failed';
        $row_id = $this->app->request->get('rowid', '');
        $row_id = intval($row_id);
        if (!empty($row_id)) {
            Helper::validateRequest('wdr_ajax_delete_collection'.$row_id);
            $deleted = Collections::deleteById($row_id);
        }
        wp_send_json($deleted);
    }

    /**
     * Return rule ids with title
     */
    private function get_collection_linked_rules()
    {
        Helper::validateRequest('wdr_ajax_get_collection_linked_rules');
        $linked_rules = [];
        $row_id = $this->app->request->get('rowid', '');
        $row_id = intval($row_id);
        if (!empty($row_id)) {
            $linked_rule_ids = Collections::getCollectionLinkedRules($row_id);
            foreach ($linked_rule_ids as $rule_id) {
                $rule_data = DBTable::getRules($rule_id, null, null, false);
                $rule_helper = new Rule($rule_data);
                $linked_rules[$rule_id] = $rule_helper->getTitle();
            }
        }
        wp_send_json(['linked_rules' => $linked_rules]);
    }
}