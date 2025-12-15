<?php

namespace ACA\WC\Subscriptions\Export\ShopSubscription;

use ACP;

class Status implements ACP\Export\Service
{

    public function get_value($id): string
    {
        return wcs_get_subscription($id)->get_status();
    }

}