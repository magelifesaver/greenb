<?php

namespace UkrSolution\ProductLabelsPrinting;

use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;

class Integration
{
    private $dokan = null;
    private $userId = null;

    public function init()
    {

        $this->dokan();

    }

    private function dokan()
    {
        $pluginStatus = is_plugin_active('dokan-lite/dokan.php');
        $this->userId = function_exists("get_current_user_id") ? get_current_user_id() : null;

        if (!$pluginStatus || !$this->userId) {
            return;
        }

        $this->dokan = new Dokan();


        add_action('dokan_product_list_table_after_status_table_header', array($this->dokan, 'addFrontendImportButton'));

        $optionStatus = UserSettings::getOption('dokanFronProductPage', '0');

        if (!$optionStatus) {
            return;
        }

        if (function_exists("ProductLabelPrintingAppScriptsFooter")) {
            add_filter("woocommerce_product_meta_end", array($this, 'woocommerce_product_page_hook'));

            ProductLabelPrintingAppScriptsFooter();
        }
    }

    public function woocommerce_product_page_hook()
    {
        global $post;

        if ($post && isset($post->post_author) && $post->post_author == $this->userId) {
            $vendorStatus = get_user_meta($this->userId, "dokan_enable_selling", true);

            echo '<div><br/><button type="button"
                class="barcodes-import-single-product"
                data-action-type="products"
                data-post-id="' . esc_js($post->ID) . '"
                data-post-status="' . esc_js($post->post_status) . '"
                data-is-excluded="0"
                title="Product Label"
                onclick="window.barcodesImportIdsType=\'simple\'; window.barcodesImportIds=[' . esc_js($post->ID) . '];">
                ' . esc_html__("Print label", "wpbcu-barcode-generator") . '
            </button></div>';
        }
    }

    private function wc_pdf_ips()
    {
        $wc_pdf_ips = UserSettings::getoption('wc_pdf_ips_status', false);

        if (!$wc_pdf_ips) return;

        $params = UserSettings::getJsonSectionOption('wc_pdf_ips_order_hook_params', 'order');

        $status = isset($params['status']) ? $params['status'] : null;
        $sid = isset($params['shortcode']) ? $params['shortcode'] : null;
        $width = isset($params['width']) ? $params['width'] : null;
        $height = isset($params['height']) ? $params['height'] : null;
        $position = isset($params['position']) ? $params['position'] : null;


        if ($status && $sid && $position) {
            add_action($position, function ($type, $order) use ($width, $height, $sid) {
                $this->runShortcode($order->get_id(), "order", $sid, $width, $height);
            }, 10, 2);
        }

        $params = UserSettings::getJsonSectionOption('wc_pdf_ips_product_hook_params', 'order');

        $status = isset($params['status']) ? $params['status'] : null;
        $sid = isset($params['shortcode']) ? $params['shortcode'] : null;
        $width = isset($params['width']) ? $params['width'] : null;
        $height = isset($params['height']) ? $params['height'] : null;
        $position = isset($params['position']) ? $params['position'] : null;

        if ($status && $sid) {
            add_action("wpo_wcpdf_after_item_meta", function ($type, $item, $order) use ($width, $height, $sid) {
                if (isset($item["item_id"]) && $item["item_id"]) {
                    $this->runShortcode($item["item_id"], "product", $sid, $width, $height, $order->get_id());
                } else if ($item["variation_id"]) {
                    $this->runShortcode($item["variation_id"], "variation", $sid, $width, $height);
                } else if ($item["product_id"]) {
                    $this->runShortcode($item["product_id"], "product", $sid, $width, $height);
                }
            }, 10, 3);
        }
    }

    private function runShortcode($id, $type, $shortcodeId, $width, $height, $orderId = null)
    {
        if (!$id || !$shortcodeId) {
            return;
        }

        echo "<div>";
        if ($orderId) {
            echo do_shortcode("[barcode id=" . $id . " shortcode=" . $shortcodeId . " width=" . $width . " height=" . $height . " _oid=" . $orderId . "]");
        } else {
            echo do_shortcode("[barcode id=" . $id . " shortcode=" . $shortcodeId . " width=" . $width . " height=" . $height . "]");
        }
        echo "</div>";
    }
}
