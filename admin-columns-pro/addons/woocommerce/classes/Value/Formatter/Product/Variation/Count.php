<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product\Variation;

use AC\Setting\CollectionFormatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class Count implements CollectionFormatter
{

    public function format(ValueCollection $collection)
    {
        $count = $collection->count();

        return new Value(
            $collection->get_id(),
            sprintf(_n('%d variation', '%d variations', $count, 'codepress-admin-columns'), $count)
        );
    }

}