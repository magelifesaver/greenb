<?php

namespace ACA\WC\Export\ShopOrder;

use ACP;

class IsCustomer implements ACP\Export\Service
{

    public function get_value($id): string
    {
        $customer_id = get_post_meta($id, '_customer_user', true);

        return $customer_id !== 0;
    }

}