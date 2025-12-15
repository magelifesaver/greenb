<?php

namespace ACA\WC\Export\Product;

use ACP;
use WC_Product;

class Attributes implements ACP\Export\Service
{

    private function get_delimiter(): string
    {
        return defined('WC_DELIMITER') && WC_DELIMITER
            ? (string)WC_DELIMITER
            : ' | ';
    }

    public function get_value($id): string
    {
        $product = wc_get_product($id);

        if ( ! $product instanceof WC_Product) {
            return '';
        }

        $attributes = [];

        foreach ($product->get_attributes() as $attribute) {
            if ($attribute->is_taxonomy()) {
                $label = wc_attribute_label($attribute->get_name(), $product);
                $options = wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
            } else {
                $label = $attribute->get_name();
                $options = $attribute->get_options();
            }

            $attributes[] = $label . ': ' . implode(' ' . $this->get_delimiter() . ' ', $options);
        }

        if ( ! $attributes) {
            return '';
        }

        return implode(', ', $attributes);
    }

}