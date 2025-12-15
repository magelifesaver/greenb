<?php

namespace ACA\WC\Export\Product;

use ACP;
use WC_Product;

class Attribute implements ACP\Export\Service
{

    private string $attribute_key;

    public function __construct(string $attribute_key)
    {
        $this->attribute_key = $attribute_key;
    }

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

        $attribute = $product->get_attribute($this->attribute_key);

        if ($attribute->is_taxonomy()) {
            $label = wc_attribute_label($attribute->get_name(), $product);
            $options = wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
        } else {
            $label = $attribute->get_name();
            $options = $attribute->get_options();
        }

        return $label . ': ' . implode(' ' . $this->get_delimiter() . ' ', $options);
    }

}