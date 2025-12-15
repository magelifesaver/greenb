<?php

namespace ACA\WC\Subscriptions\Export\ShopSubscription;

use ACP;
use WC_Order_Item_Product;

class OrderItems implements ACP\Export\Service
{

    public function get_value($id): string
    {
        $subscription = wcs_get_subscription($id);

        $values = [];

        foreach ($subscription->get_items() as $item) {
            if ( ! $item instanceof WC_Order_Item_Product) {
                continue;
            }

            $data = $item->get_data();
            $quantity = $data['quantity'] ?? null;

            $value = sprintf('%s (%d)', $item->get_name(), $item->get_product_id());

            if ($quantity && $quantity > 1) {
                $value = sprintf('%sx %s', $quantity, $value);
            }

            $values[] = $value;
        }

        return implode(', ', $values);
    }

}