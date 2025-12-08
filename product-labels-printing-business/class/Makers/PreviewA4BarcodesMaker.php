<?php
namespace UkrSolution\ProductLabelsPrinting\Makers;

use stdClass;
use UkrSolution\ProductLabelsPrinting\Helpers\Variables;

class PreviewA4BarcodesMaker extends A4BarcodesMaker
{
    protected $previewData = array();
    protected $algorithm = 'C128';

    public function __construct($data, $type = '')
    {
        parent::__construct($data, $type);

        $config = require Variables::$A4B_PLUGIN_BASE_PATH . 'config/config.php';
        $this->previewData = 'shipping' === $this->data['dummy'] ? $config['shippingPreviewData'] : $config['productPreviewData'];
        $this->previewData['replacements']['main_product_image_url']['main_product_image_url'] = Variables::$A4B_PLUGIN_BASE_URL . 'assets/img/product-img1.png';
        $this->algorithm = $this->data['format'];
        $this->activeTemplate = new StdClass();
        $this->activeTemplate->template = $this->data['template'];
    }

    protected function getItems()
    {
        $items[] = (object) array('ID' => $this->previewData['ID']);
        $this->items = $items;
    }

    protected function getFileOptions($post, $algorithm)
    {
        $quantity = 1;
        $thumbnailUrl = Variables::$A4B_PLUGIN_BASE_URL . 'assets/img/product-img1.png';
        $lineBarcode = in_array($this->data['format'], array('EAN13')) ? "0190198457325" : $this->previewData['code'];

        return array(
            'quantity' => $quantity,
            'post_image' => $thumbnailUrl,
            'lineBarcode' => $lineBarcode, 
            'fieldLine1' => $this->previewData['line1'], 
            'fieldLine2' => $this->previewData['line2'], 
            'fieldLine3' => $this->previewData['line3'], 
            'fieldLine4' => $this->previewData['line4'], 
            'algorithm' => $this->algorithm, 
            'showName' => $this->showName, 
            'showLine3' => $this->showLine3, 
            'showLine4' => $this->showLine4,
            'replacements' => $this->getTemplateReplacements($post, $this->templateShortcodesArgs),
        );
    }

    protected function getTemplateReplacements($post, $shortcodesArgs)
    {
        $replacements = new \ArrayObject();

        if (!empty($this->prodListTemplate)) {
            $replacements[$this->prodListShortcode] = $this->getProdListHtml();
        }

        foreach ($shortcodesArgs as $shortCode => $args) {
            $replacements[$shortCode] = $this->getShortcodeFieldValue($args);
        }

        return $replacements;
    }

    protected function getShortcodeFieldValue($args)
    {
        $result = '';
        $args['value'] = isset($args['value']) ? $args['value'] : '';

        $argsValues = explode('|', $args['value']);

        foreach ($argsValues as $argsValue) {
            $text = isset($this->previewData['replacements'][$args['type']][$argsValue])
                ? $this->previewData['replacements'][$args['type']][$argsValue]
                : $args['value'];

            if (!empty($text)) {
                $texts[] = $text;
                $result = implode(' ', $texts);

                if (!empty($args['before'])) {
                    $result = $args['before'] . $result;
                }

                if (!empty($args['after'])) {
                    $result = $result . $args['after'];
                }

                break;
            }
        }

        $result = $this->convertValueToBarcodeImageUrlIfNeed($field, $result);

        return $result;
    }

    protected function getProdListHtml()
    {
        $prodListHtml = '';
        $testProductsNum = preg_match('/test-products=(\d+)/', $this->prodListShortcode, $matches) ? $matches[1] : 1;

        for ($i = 0; $i < $testProductsNum; $i++) {
            $productHtml = $this->prodListTemplate;

            foreach ($this->prodListShortcodesArgs as $shortCode => $args) {
                $args['qty'] = rand(1, 99);
                $productHtml = str_replace($shortCode, $this->getShortcodeFieldValue($args), $productHtml);
            }

            $prodListHtml .= $productHtml;
        }

        return $prodListHtml;
    }
}
