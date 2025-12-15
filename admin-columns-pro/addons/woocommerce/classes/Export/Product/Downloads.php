<?php

namespace ACA\WC\Export\Product;

use ACP;

class Downloads implements ACP\Export\Service
{

    public function get_value($id): string
    {
        $product = wc_get_product($id);

        if ( ! $product) {
            return '';
        }

        $values = [];

        foreach ($product->get_downloads() as $download) {
            $values[] = $download->get_file();
        }

        return implode(', ', $values);
    }

}