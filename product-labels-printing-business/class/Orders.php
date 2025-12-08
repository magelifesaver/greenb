<?php

namespace UkrSolution\ProductLabelsPrinting;

use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;
use UkrSolution\ProductLabelsPrinting\Helpers\Variables;

class Orders
{
    public function addImportButton($post_type)
    {
        if (!empty($post_type) && $post_type === 'shop_order') {
            include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/orders/import-products-button.php';

            include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/orders/import-orders-button.php';
        }
    }

    public function addOrderItemsImport($orderItemId)
    {
        global $post_type, $theorder;

        try {
            if (
                (
                    (!empty($post_type) && $post_type === 'shop_order')
                    || !empty($theorder)
                )
                && is_admin()
            ) {
                $orderItem = new \WC_Order_Item_Product($orderItemId);

                if ($orderItem->get_product_id() || $orderItem->get_variation_id()) {
                    $itemId = $orderItem->get_id();
                    include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/orders/import-order-item-checkbox.php';
                }
            }
        } catch (\Throwable $th) {
        }
    }

    public function addOrderItemsBarcode($orderItemId, $item)
    {
    }

    public function orderItemActionButton($order)
    {
        try {
            $orderId = $order->get_id();
            include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/orders/import-order-items.php';
        } catch (\Throwable $th) {
        }
    }

    public function woocommerce_admin_order_preview_get_order_details($data)
    {
        global $wpdb;

        try {
            $tableShortcodes = $wpdb->prefix . Database::$tableShortcodes;

            $params = UserSettings::getJsonSectionOption('adminOrderPreviewParams', 'order', 1);

            if (!$params || !isset($params['width']) || !$params['width']) return;

            $sid = isset($params['shortcode']) ? $params['shortcode'] : null;
            $width = isset($params['width']) ? $params['width'] : null;
            $height = isset($params['height']) ? $params['height'] : null;

            if ($sid) {
                $shortcodeData = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$tableShortcodes}` WHERE `id` = '%d' AND `type` = %s;", $sid, "order"));
            } else {
                $shortcodeData = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$tableShortcodes}` WHERE `is_default` = 1 AND `type` = %s;", "order"));
            }

            if ($data["data"] && $data["data"]["id"] && $shortcodeData) {
                $shortcode = str_replace("id=XXXX", "id={$data["data"]["id"]} width={$width}px height={$height}px ", $shortcodeData->shortcode);
                ob_start();
                require Variables::$A4B_PLUGIN_BASE_PATH . 'templates/orders/preview.php';
                $file = ob_get_clean();
                $data["actions_html"] .= $file;
            }
        } catch (\Throwable $th) {
        }

        return $data;
    }
}
