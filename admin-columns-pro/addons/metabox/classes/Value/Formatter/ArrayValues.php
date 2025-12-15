<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Type\Value;

class ArrayValues implements AC\Setting\Formatter
{

    public function format(Value $value)
    {
        if ( ! is_array($value->get_value())) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $collection = new AC\Type\ValueCollection($value->get_id());

        foreach ($value->get_value() as $_value) {
            $collection->add(new Value($value->get_id(), $_value));
        }

        return $collection;
    }

}