<?php
namespace UkrSolution\ProductLabelsPrinting\Makers;

use UkrSolution\ProductLabelsPrinting\Filters\Items;

class ManualA4BarcodesMaker extends A4BarcodesMaker
{
    protected function getItems()
    {
        $this->items = $this->data['fields'];

        $itemsFilter = new Items();
        $itemsFilter->sortItemsResult($this->items);
    }

    protected function getFileOptions($itemData, $algorithm)
    {
        return array(
            'quantity' => 1,
            'post_image' => null,
            'lineBarcode' => isset($itemData['lineBarcode']['value']) ? $itemData['lineBarcode']['value'] : '', 
            'fieldLine1' => isset($itemData['line1']['value']) ? $itemData['line1']['value'] : '', 
            'fieldLine2' => isset($itemData['line2']['value']) ? $itemData['line2']['value'] : '', 
            'fieldLine3' => isset($itemData['line3']['value']) ? $itemData['line3']['value'] : '', 
            'fieldLine4' => isset($itemData['line4']['value']) ? $itemData['line4']['value'] : '', 
            'algorithm' => $algorithm, 
            'showName' => $this->showName,
            'showLine3' => $this->showLine3,
            'showLine4' => $this->showLine4,
            'replacements' => $this->getTemplateReplacements($itemData, $this->templateShortcodesArgs),
        );
    }

    protected function getTemplateReplacements($item, $shortcodesArgs)
    {
        $replacements = new \ArrayObject();

        foreach ($shortcodesArgs as $shortCode => $args) {
            $replacements[$shortCode] = '';
        }

        return $replacements;
    }
}
