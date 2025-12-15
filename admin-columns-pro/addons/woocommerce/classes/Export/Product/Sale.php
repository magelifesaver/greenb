<?php

namespace ACA\WC\Export\Product;

use ACP;
use WC_Product;

class Sale implements ACP\Export\Service
{

    public function is_scheduled(WC_Product $product)
    {
        return $product->get_date_on_sale_from() || $product->get_date_on_sale_to();
    }

    public function get_value($id): string
    {
        $product = wc_get_product($id);

        if ($this->is_scheduled($product)) {
            $date_from = $product->get_date_on_sale_from('edit') ? $product->get_date_on_sale_from('edit')->format(
                'Y-m-d'
            ) : null;
            $date_to = $product->get_date_on_sale_to('edit') ? $product->get_date_on_sale_to('edit')->format(
                'Y-m-d'
            ) : null;

            if ($date_from && $date_to) {
                return sprintf('%s / %s', $date_from, $date_to);
            }

            if ($date_from) {
                return _x('From', 'Product on sale from (date)', 'codepress-admin-columns') . ' ' . $date_from;
            }

            if ($date_to) {
                return _x('Until', 'Product on sale from (date)', 'codepress-admin-columns') . ' ' . $date_to;
            }
        }

        return $product->is_on_sale('edit');
    }

}