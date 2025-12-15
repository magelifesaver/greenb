<?php

namespace ACA\WC\Subscriptions\Export\OrderSubscription;

use ACP;

class StartDate implements ACP\Export\Service
{

    public function get_value($id): string
    {
        return wcs_get_subscription($id)->get_date('start_date');
    }

}