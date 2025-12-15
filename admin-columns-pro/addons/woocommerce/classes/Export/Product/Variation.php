<?php

namespace ACA\WC\Export\Product;

use AC\Type\Value;
use ACA\WC\Value\Formatter\Product\VariationsCollection;
use ACP;

class Variation implements ACP\Export\Service
{

    public function get_value($id): string
    {
        $collection = (new VariationsCollection())->format(new Value((int)$id));

        $values = [];

        foreach ($collection as $item) {
            $values[] = (string)$item;
        }

        return implode(', ', $values);
    }

}