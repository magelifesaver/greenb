<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\OrderSubscription;

use AC\Exception\ValueNotFoundException;
use AC\Setting\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class RelatedOrderIds implements Formatter
{

    public function format(Value $value)
    {
        $subscription = wcs_get_subscription($value->get_id());

        if ( ! $subscription) {
            ValueNotFoundException::from_id($value->get_id());
        }

        $ids = $subscription->get_related_orders();

        if (empty($ids)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $ids);
    }

}