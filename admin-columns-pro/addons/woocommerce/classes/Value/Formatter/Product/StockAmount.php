<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class StockAmount extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        if ($product->is_type('variable')) {
            $quantity = $this->get_total_variation_amount($product);
            if ( ! $quantity) {
                throw ValueNotFoundException::from_id($value->get_id());
            }

            $dashicon = ac_helper()->icon->dashicon([
                'icon'    => 'info-outline',
                'tooltip' => 'from variations',
            ]);
            $quantity .= ' ' . $dashicon;
        } else {
            $quantity = $product->get_stock_quantity();
        }

        if ( ! $quantity) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            $quantity
        );
    }

    private function get_total_variation_amount(WC_Product $product): int
    {
        $total_stock = 0;

        foreach ($product->get_children() as $child_id) {
            $variation = wc_get_product($child_id);
            if ($variation->managing_stock()) {
                $total_stock += max(0, (int)$variation->get_stock_quantity());
            }
        }

        return $total_stock;
    }

}