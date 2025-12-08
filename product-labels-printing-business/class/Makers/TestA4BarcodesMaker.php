<?php
namespace UkrSolution\ProductLabelsPrinting\Makers;

use UkrSolution\ProductLabelsPrinting\Filters\Items;
use UkrSolution\ProductLabelsPrinting\Helpers\Variables;

class TestA4BarcodesMaker extends A4BarcodesMaker
{
    protected function getItems()
    {
        $simpleTest = isset($_POST['simpleTest']) && 'true' === sanitize_key($_POST['simpleTest']);
        $config = require Variables::$A4B_PLUGIN_BASE_PATH . 'config/config.php';
        $testBarcodesSettings = $config['testBarcodes'];
        $items = array();

        if (!$simpleTest) {
            foreach ($testBarcodesSettings['algorithms'] as $algorithm => $codes) {
                $algorithmName = 'DATAMATRIX' !== $algorithm ? $algorithm : 'DMATRIX';

                $items[] = array(
                    'algorithm' => $algorithm,
                    'line1' => $codes['long'],
                    'line2' => $algorithmName . ' ' . $testBarcodesSettings['names']['long'],
                    'line3' => '',
                    'line4' => '',
                );
                $items[] = array(
                    'algorithm' => $algorithm,
                    'line1' => $codes['long'],
                    'line2' => $algorithmName . ' ' . $testBarcodesSettings['names']['long'],
                    'line3' => $testBarcodesSettings['texts1']['long'],
                    'line4' => $testBarcodesSettings['texts2']['long'],
                );
                $items[] = array(
                    'algorithm' => $algorithm,
                    'line1' => $codes['long'],
                    'line2' => '',
                    'line3' => '',
                    'line4' => '',
                );
                $items[] = array(
                    'algorithm' => $algorithm,
                    'line1' => $codes['long'],
                    'line2' => '',
                    'line3' => $testBarcodesSettings['texts1']['long'],
                    'line4' => $testBarcodesSettings['texts2']['long'],
                );
                $items[] = array(
                    'algorithm' => $algorithm,
                    'line1' => $codes['short'],
                    'line2' => $algorithmName . ' ' . $testBarcodesSettings['names']['short'],
                    'line3' => $testBarcodesSettings['texts1']['short'],
                    'line4' => '',
                );
                $items[] = array(
                    'algorithm' => $algorithm,
                    'line1' => $codes['short'],
                    'line2' => '',
                    'line3' => '',
                    'line4' => $testBarcodesSettings['texts2']['short'],
                );
            }
        }

        $this->items = $items;

        $itemsFilter = new Items();
        $itemsFilter->sortItemsResult($this->items);
    }

    protected function getFileOptions($itemData, $algorithm)
    {
        return array(
            'quantity' => 1,
            'post_image' => null,
            'algorithm' => $itemData['algorithm'],
            'lineBarcode' => $itemData['lineBarcode'],
            'fieldLine1' => $itemData['line1'],
            'fieldLine2' => $itemData['line2'],
            'fieldLine3' => $itemData['line3'],
            'fieldLine4' => $itemData['line4'],
            'replacements' => $this->getTemplateReplacements($itemData, $this->templateShortcodesArgs),
        );
    }

    protected function getTemplateReplacements($item, $shortcodesArgs)
    {
        $replacements = array();

        foreach ($shortcodesArgs as $shortCode => $args) {
            $replacements[$shortCode] = '';
        }

        return $replacements;
    }
}
