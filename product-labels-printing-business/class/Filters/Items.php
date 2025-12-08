<?php

namespace UkrSolution\ProductLabelsPrinting\Filters;

class Items
{
    public $filterName = "barcodes_products_after_hook";
    public $sortItemsFilterName = "barcodes_products_sort_items_hook";

    public function CheckItemsResult(&$items, $settings, $isUseApi = false)
    {
        $ids = isset($settings["productsIds"]) ? $settings["productsIds"] : array();

        foreach ($items as &$item) {
            if (is_array($item)) {
                $post = get_post($item["ID"]);
                $item = apply_filters($this->filterName, $item, $settings);
            }
        }

        foreach ($ids as $id) {
            foreach ($items as &$item) {
                if (is_array($item) && (int)$item["ID"] === (int)$id) {
                    continue 2;
                }
            }

            if ($isUseApi) {
                $emptyItem = array(
                    'ID' => $id,
                    'parentId' => '',
                    'image' => '',
                    'svgContent' => '',
                    'post_image' => '',
                    'lineBarcode' => '',
                    'fieldLine1' => '',
                    'fieldLine2' => '',
                    'fieldLine3' => '',
                    'fieldLine4' => '',
                    'format' => $settings['format'],
                    'replacements' => array()
                );

                $items[] = apply_filters($this->filterName, $emptyItem, $settings);
            }
        }

        if (count($ids) === 0) {
            foreach ($items as &$item) {
                if (is_array($item)) {
                    $item = apply_filters($this->filterName, $item, $settings);
                }
            }
        }

    }

    public function sortItemsResult(&$items)
    {
        $items = apply_filters($this->sortItemsFilterName, $items);
    }
}
