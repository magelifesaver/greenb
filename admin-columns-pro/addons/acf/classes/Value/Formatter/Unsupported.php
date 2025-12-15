<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Setting\Formatter;
use AC\Type\Value;

class Unsupported implements Formatter
{

    public function format(Value $value)
    {
        $raw = $value->get_value();

        if (is_array($raw)) {
            return $value->with_value(ac_helper()->array->implode_recursive(__(', '), $raw));
        }

        return $value;
    }

}