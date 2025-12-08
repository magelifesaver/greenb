<?php
namespace UkrSolution\ProductLabelsPrinting;

use UkrSolution\ProductLabelsPrinting\Makers\AtumInventoryPoA4BarcodesMaker;
use UkrSolution\ProductLabelsPrinting\BarcodeTemplates\BarcodeTemplatesController;
use UkrSolution\ProductLabelsPrinting\Filters\Items;
use UkrSolution\ProductLabelsPrinting\Helpers\Variables;

class AtumInventoryPo
{
    private $isHookUsed = false;
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
            'atumPoIds',
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
            'atumPoIds' => 'required|array|bail', 
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

        $postsBarcodesGenerator = new AtumInventoryPoA4BarcodesMaker($data);
        $result = $postsBarcodesGenerator->make();

        $isUseApi = isset($post["isUseApi"]) && (int)$post["isUseApi"] === 1 ? true : false;
        $Items = new Items();
        $Items->CheckItemsResult($result["listItems"], $data, $isUseApi);

        uswbg_a4bJsonResponse($result);
    }

    public function addImportButton()
    {
        include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/products/import-atum-products.php';
    }

    public function addOrderItemsImport($orderItemId)
    {
        global $post_type;

        try {
            if ($post_type === 'atum_purchase_order' && is_admin()) {
                $itemId = $orderItemId;

                include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/orders/import-atum-order-item-checkbox.php';
            }
        } catch (\Throwable $th) {
        }
    }

    public function orderItemActionButton($order)
    {
        try {
            if ($this->isHookUsed) return;

            $this->isHookUsed = true;
            $orderId = $order->get_id();
            include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/orders/import-atum-order-items.php';
        } catch (\Throwable $th) {
        }
    }
}
