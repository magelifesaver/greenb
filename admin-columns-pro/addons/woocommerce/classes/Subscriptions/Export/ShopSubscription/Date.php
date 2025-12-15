<?php

namespace ACA\WC\Subscriptions\Export\ShopSubscription;

use ACP;

class Date implements ACP\Export\Service
{

    private $date_type;

    public function __construct(string $date_type)
    {
        $this->date_type = $date_type;
    }

    public function get_value($id): string
    {
        $subscription = wcs_get_subscription($id);
        $date = $subscription->get_date($this->date_type);

        return $date ?: '';
    }

}