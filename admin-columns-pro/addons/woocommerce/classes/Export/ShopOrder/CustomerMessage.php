<?php

namespace ACA\WC\Export\ShopOrder;

use ACP;

class CustomerMessage implements ACP\Export\Service
{

    public function get_value($id): string
    {
        return wc_get_order($id)->get_customer_note();
    }

}