<?php

namespace UkrSolution\ProductLabelsPrinting;

use UkrSolution\ProductLabelsPrinting\Helpers\Variables;
use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;

class MetaBoxes
{
    public function productPage()
    {
        $screens = ['post', 'product'];
        foreach ($screens as $screen) {
            add_meta_box('barcode-generator-id', 'Barcode', array($this, "productView"), $screen, 'side');
        }
    }

    public function orderPage()
    {
        $screens = ['post', 'shop_order', 'woocommerce_page_wc-orders'];

        foreach ($screens as $screen) {
            add_meta_box('barcode-generator-id' . $screen, 'Barcode', array($this, "orderView"), $screen, 'side');
        }
    }

    public function orderPagePrint()
    {
        $screens = ['shop_order', 'woocommerce_page_wc-orders'];
        foreach ($screens as $screen) {
            add_meta_box('barcode-print-order-create', __('Print labels', 'wpbcu-barcode-generator'), array($this, "orderViewPrint"), $screen, 'side', 'high');
        }

    }

    public function atumPOPagePrint()
    {
        $screens = ['atum_purchase_order'];
        foreach ($screens as $screen) {
            add_meta_box('barcode-print-atum-po-create', __('Order Label', 'wpbcu-barcode-generator'), array($this, "atumPoViewPrint"), $screen, 'side', 'high');
        }

    }

    public function productView($post)
    {
        try {
            global $post, $wpdb;

            $tableShortcodes = $wpdb->prefix . Database::$tableShortcodes;

            $params = UserSettings::getJsonSectionOption('adminProductPageParams', 'product', 1);

            if (!$params || !isset($params['width']) || !$params['width']) return;

            $sid = isset($params['shortcode']) ? $params['shortcode'] : null;
            $width = isset($params['width']) ? $params['width'] : null;
            $height = isset($params['height']) ? $params['height'] : null;

            if ($sid) {
                $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$tableShortcodes}` WHERE `id` = '%d' AND `type` = %s;", $sid, "product"));
            } else {
                $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$tableShortcodes}` WHERE `is_default` = 1 AND `type` = %s;", "product"));
            }


            if ($data && $width && $height) {
                $shortcode = str_replace("id=XXXX", "id={$post->ID} width={$width}px height={$height}px ", $data->shortcode);
                include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/meta-boxes/product-meta-box.php';
            }

            $generatorFieldType = UserSettings::getOption('generatorFieldType', '');

            if ($generatorFieldType === "custom") {
                $generatorCustomField = UserSettings::getOption('generatorCustomField', '');
                $key = $generatorCustomField;

                $generatorCustomFieldValue = get_post_meta($post->ID, $key, true);

                if (!$generatorCustomFieldValue && $post->post_status === "auto-draft") {
                    $productsModel = new Products();
                    $generatorCustomFieldValue = $productsModel->getCodeForNewProduct();
                }

                include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/meta-boxes/product-code-meta-box.php';
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function orderView($post)
    {
        try {
            global $post, $theorder, $wpdb;

            $tableShortcodes = $wpdb->prefix . Database::$tableShortcodes;

            $params = UserSettings::getJsonSectionOption('adminOrderPageParams', 'order', 1);

            if (!$params || !isset($params['width']) || !$params['width']) return;

            $sid = isset($params['shortcode']) ? $params['shortcode'] : null;
            $width = isset($params['width']) ? $params['width'] : null;
            $height = isset($params['height']) ? $params['height'] : null;

            if ($sid) {
                $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$tableShortcodes}` WHERE `id` = '%d' AND `type` = %s;", $sid, "order"));
            } else {
                $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$tableShortcodes}` WHERE `is_default` = 1 AND `type` = %s;", "order"));
            }

            if ($data && $width && $height) {
                if ($post && $post->ID) {
                    $orderId = $post->ID;
                } else if ($theorder) {
                    $orderId = $theorder->get_id();
                }

                                $shortcode = str_replace("id=XXXX", "id={$orderId} width={$width}px height={$height}px ", $data->shortcode);
                include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/meta-boxes/order-meta-box.php';
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function orderViewPrint($post)
    {
        try {
            global $post, $theorder;

            if ($post instanceof \WP_Post) {
                $thepostid = $post->ID;
            } elseif (!empty($theorder)) {
                $thepostid = $theorder->get_id();
            } else {
                $thepostid = null;
            }

            if ($thepostid) {
                $orderId = $thepostid;

                echo '<div style="display: flex; align-items: center;">';
                echo '<div>';
                include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/orders/import-order.php';
                echo '</div>';
                echo '<div>';
                include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/orders/import-order-all-items.php';
                echo '</div>';
                echo '</div>';
            }
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function atumPoViewPrint($post)
    {
        try {
            global $post;

            if ($post) {
                $orderId = $post->ID;

                include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/orders/import-atum-po.php';
            }
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function saveProductPage($post_id)
    {
        $generatorFieldType = UserSettings::getOption('generatorFieldType', '');

        if (isset($_POST["usbdGeneratorCustomFieldValue"]) && $generatorFieldType === "custom") {
            $value = sanitize_text_field($_POST["usbdGeneratorCustomFieldValue"]);
            $generatorCustomField = UserSettings::getOption('generatorCustomField', '');

            update_post_meta($post_id, $generatorCustomField, $value);
        }
    }
}
