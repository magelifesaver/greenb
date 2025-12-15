<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Setting\Formatter;
use AC\Type\Value;

class FilterByCustomerLink implements Formatter
{

    public function format(Value $value)
    {
        return $value->with_value(
            ac_helper()->html->link(add_query_arg('_customer_user', $value->get_id()), (string)$value->get_value())
        );
    }

}