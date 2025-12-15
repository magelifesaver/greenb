<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Setting\Formatter;
use AC\Type\Value;

class OrderTotal implements Formatter
{

    public function format(Value $value): Value
    {
        $total = $this->get_order_total($value->get_id());

        if ( ! $total) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($total);
    }

    private function get_order_total(int $post_id): ?string
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "
                SELECT SUM( product_net_revenue )
                FROM {$wpdb->prefix}wc_order_product_lookup
                WHERE product_id = %d             
            ",
            $post_id
        );

        return $wpdb->get_var($sql);
    }

}