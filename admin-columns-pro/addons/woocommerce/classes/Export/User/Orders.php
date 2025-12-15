<?php

namespace ACA\WC\Export\User;

use ACP;

class Orders implements ACP\Export\Service
{

    private $order_status;

    public function __construct(array $order_status = [])
    {
        $this->order_status = $order_status;
    }

    public function get_value($id): string
    {
        $args = [
            'customer_id' => $id,
            'limit'       => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'return'      => 'ids',
        ];

        if ( ! empty($this->order_status)) {
            $args['status'] = $this->order_status;
        }

        $orders = wc_get_orders($args);

        return implode(', ', $orders);
    }

}