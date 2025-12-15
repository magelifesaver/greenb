<?php

namespace ACA\WC\Subscriptions\Export\OrderSubscription;

use ACP;

class Total implements ACP\Export\Service
{

    public function get_value($id): string
    {
        return strip_tags(wcs_get_subscription($id)->get_formatted_order_total());
    }

}