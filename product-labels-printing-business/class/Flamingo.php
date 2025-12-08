<?php
namespace UkrSolution\ProductLabelsPrinting;

use UkrSolution\ProductLabelsPrinting\BarcodeTemplates\BarcodeTemplatesController;
use UkrSolution\ProductLabelsPrinting\Filters\Items;
use UkrSolution\ProductLabelsPrinting\Helpers\Variables;
use UkrSolution\ProductLabelsPrinting\Makers\FlamingoA4BarcodesMaker;

class Flamingo
{
    public function addImportButton($args)
    {
        global $post_type;

        $page = isset($_GET["page"]) ? sanitize_key($_GET["page"]) : "";

        if ($page === 'flamingo_inbound' && is_admin()) {
            include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/contact-form-7/flamingo-import-messages-button.php';
        }

        return $args;
    }

    public function getBarcodes()
    {
        Request::ajaxRequestAccess();

        if (!current_user_can('read')) {
            wp_die();
        }

        $customTemplatesController = new BarcodeTemplatesController();
        $activeTemplate = $customTemplatesController->getActiveTemplate();


        $post = array();
        foreach (array('format', 'isUseApi', 'lineSeparator1', 'lineSeparator2', 'lineSeparator3', 'lineSeparator4', 'profileId') as $key) {
            if (isset($_POST[$key])) {
                $post[$key] = sanitize_text_field($_POST[$key]);
            }
        }

        foreach (array(
                'messagesIds',
                'lineBarcode',
                'fieldLine1',
                'fieldLine2',
                'fieldLine3',
                'fieldLine4',
                'fieldSepLine1',
                'fieldSepLine2',
                'fieldSepLine3',
                'fieldSepLine4',
            ) as $key
        ) {
            if (isset($_POST[$key])) {
                $post[$key] = USWBG_a4bRecursiveSanitizeTextField($_POST[$key]);
            }
        }

        $validationRules = array(
            'format' => 'required',
            'messagesIds' => 'required|array|bail', 
            'lineBarcode' => $activeTemplate->code_match ? 'array' : 'required|array',
            'fieldLine1' => 'array',
            'fieldLine2' => 'array',
            'fieldLine3' => 'array',
            'fieldLine4' => 'array',
            'fieldSepLine1' => 'array',
            'fieldSepLine2' => 'array',
            'fieldSepLine3' => 'array',
            'fieldSepLine4' => 'array',
            'lineSeparator1' => 'string',
            'lineSeparator2' => 'string',
            'lineSeparator3' => 'string',
            'lineSeparator4' => 'string',
            'profileId' => 'numeric',
        );

        $data = Validator::create($post, $validationRules, true)->validate();

        $postsBarcodesGenerator = new FlamingoA4BarcodesMaker($data);
        $result = $postsBarcodesGenerator->make();

        $isUseApi = isset($post["isUseApi"]) && (int)$post["isUseApi"] === 1 ? true : false;
        $Items = new Items();
        $Items->CheckItemsResult($result["listItems"], $data, $isUseApi);

        uswbg_a4bJsonResponse($result);
    }

    public function countProductsByCustomField()
    {
        global $wpdb;

        $post = array();
        foreach (array('postType', 'field') as $key) {
            if (isset($_POST[$key])) {
                $post[$key] = sanitize_text_field($_POST[$key]);
            }
        }

        $validationOptions = array(
            'field' => 'required|string',
            'postType' => 'string',
        );

        $data = Validator::create($post, $validationOptions, true)->validate();

        $fields = array_map('trim', explode(',', $data['field']));
        $postType = isset($data['postType']) ? $data['postType'] : "";

        $counters = array();
        foreach ($fields as $field) {
            $fieldKey = $field;

            if (empty($field)) {
                continue;
            } elseif (0 === strpos($field, 'product.')) {
                $field = substr($field, 8); 
            } elseif (0 === strpos($field, 'variation.')) {
                $field = substr($field, 10); 
            }

            if ($postType === "import-orders") {
                $postTypes = "'shop_order'";
            } else {
                $postTypes = "'product_variation', 'product'";
            }

            $response = $wpdb->get_row(
                $wpdb->prepare(
                    "
                SELECT COUNT(DISTINCT p.`ID`) as 'count'
                FROM `{$wpdb->prefix}postmeta` AS pm, `{$wpdb->prefix}posts` AS p
                WHERE pm.`meta_key` = BINARY %s
                AND pm.`post_id` = p.`ID`
                AND p.`post_type` IN($postTypes)
                ",
                    array($field)
                )
            );

            $counters[$fieldKey] = $response->count;
        }

        uswbg_a4bJsonResponse(array('counters' => $counters));
    }
}
