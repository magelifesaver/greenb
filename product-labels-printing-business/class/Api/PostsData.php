<?php

namespace UkrSolution\ProductLabelsPrinting\Api;

use UkrSolution\ProductLabelsPrinting\Models\PostsUtils;
use UkrSolution\ProductLabelsPrinting\Request;
use UkrSolution\ProductLabelsPrinting\Validator;

class PostsData
{
    public function getIdsBulkList()
    {
        Request::ajaxRequestAccess();

        if (!current_user_can('read')) {
            wp_die();
        }

        $validationRules = array('list' => 'array', 'raw' => 'complexCodeValue', 'fieldSource' => 'string');
        $post = array();
        $result = array();

        if (isset($_POST['list'])) {
            $post['list'] = USWBG_a4bRecursiveSanitizeTextField($_POST['list']);
        }
        if (isset($_POST['raw'])) {
            $post['raw'] = sanitize_textarea_field($_POST['raw']);
        }
        if (isset($_POST['fieldSource'])) {
            $post['fieldSource'] = sanitize_text_field($_POST['fieldSource']);
        } else {
            $post['fieldSource'] = '';
        }

        $data = Validator::create($post, $validationRules, true)->validate();

        update_user_meta(get_current_user_id(), 'a4b_bulk_list_raw', $data['raw']);

        $postsUtils = new PostsUtils();
        foreach ($data['list'] as &$postField) {
            unset($postField['success']);
            $result[] = $postsUtils->getPostIdByField($postField, $data['fieldSource']);
        }

        uswbg_a4bJsonResponse($result);
    }
}
