<?php
namespace UkrSolution\ProductLabelsPrinting;

use UkrSolution\ProductLabelsPrinting\Makers\WoocommerceCouponsA4BarcodesMaker;
use UkrSolution\ProductLabelsPrinting\BarcodeTemplates\BarcodeTemplatesController;
use UkrSolution\ProductLabelsPrinting\Filters\Items;
use UkrSolution\ProductLabelsPrinting\Helpers\Variables;

class WoocemmerceCoupons
{
    public function addImportButton($args)
    {
        global $post_type;

        $postType = isset($_GET["post_type"]) ? sanitize_key($_GET["post_type"]) : "";

        if ($postType === 'shop_coupon' && is_admin()) {
            include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/woocommerce-coupons/wc-coupons-import-messages-button.php';
        }

        return $args;
    }

    public function getBarcodes()
    {
        Request::ajaxRequestAccess();

        if (!current_user_can('read')) {
            wp_die();
        }

        global $current_user;
        $customTemplatesController = new BarcodeTemplatesController();
        $activeTemplate = $customTemplatesController->getActiveTemplate();

        $post = array();
        foreach (array('format', 'isUseApi', 'lineSeparator1', 'lineSeparator2', 'lineSeparator3', 'lineSeparator4', 'profileId', 'page') as $key) {
            if (isset($_POST[$key])) {
                $post[$key] = sanitize_text_field($_POST[$key]);
            }
        }

        foreach (array(
            'couponsIds',
            'lineBarcode',
            'fieldLine1',
            'fieldLine2',
            'fieldLine3',
            'fieldLine4',
            'fieldSepLine1',
            'fieldSepLine2',
            'fieldSepLine3',
            'fieldSepLine4',
        ) as $key) {
            if (isset($_POST[$key])) {
                $post[$key] = USWBG_a4bRecursiveSanitizeTextField($_POST[$key]);
            }
        }

        $validationRules = array(
            'format' => 'required',
            'couponsIds' => 'required|array|bail', 
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
            'page' => 'string',
            'profileId' => 'numeric',
        );

        $data = Validator::create($post, $validationRules, true)->validate();

        $postsBarcodesGenerator = new WoocommerceCouponsA4BarcodesMaker($data);
        $result = $postsBarcodesGenerator->make();

        $isUseApi = isset($post["isUseApi"]) && (int)$post["isUseApi"] === 1 ? true : false;
        $Items = new Items();
        $Items->CheckItemsResult($result["listItems"], $data, $isUseApi);

        if ($current_user && isset($data['page']) && $data['page'] === "template-editor") {
            if (isset($data['couponsIds']) && count($data['couponsIds'])) {

                \update_user_meta($current_user->ID, "usplp_product_preview", $data['couponsIds'][0]);
            }
        }

        uswbg_a4bJsonResponse($result);
    }
}
