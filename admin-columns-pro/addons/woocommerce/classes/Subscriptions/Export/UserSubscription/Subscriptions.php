<?php

namespace ACA\WC\Subscriptions\Export\UserSubscription;

use ACP;
use WCS_Customer_Store;

class Subscriptions implements ACP\Export\Service
{

    public function get_value($id): string
    {
        $subscription_ids = WCS_Customer_Store::instance()->get_users_subscription_ids($id);

        return implode(', ', $subscription_ids);
    }

}