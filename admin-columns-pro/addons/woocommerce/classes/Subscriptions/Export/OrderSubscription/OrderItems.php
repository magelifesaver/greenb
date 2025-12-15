<?php

namespace ACA\WC\Subscriptions\Export\OrderSubscription;

use ACP;
use WC_Order_Item_Product;

class OrderItems implements ACP\Export\Service
{

    public function get_value($id): string
    {
        $order = wcs_get_subscription($id);
        $items = [];

        foreach ($order->get_items() as $item) {
            if ($item instanceof WC_Order_Item_Product) {
                $items[] = sprintf('%dx %s', $item->get_quantity(), $item->get_name());
            }
        }

        return implode(',', $items);
    }

}