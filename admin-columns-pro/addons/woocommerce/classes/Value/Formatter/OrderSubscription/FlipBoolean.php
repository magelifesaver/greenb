<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\OrderSubscription;

use AC\Setting\Formatter;
use AC\Type\Value;

class FlipBoolean implements Formatter
{

    public function format(Value $value)
    {
        return $value->with_value(! $value->get_value());
    }

}