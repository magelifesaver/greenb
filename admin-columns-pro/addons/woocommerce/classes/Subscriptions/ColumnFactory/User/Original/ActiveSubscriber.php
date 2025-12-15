<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\User\Original;

use AC\Setting\Config;
use ACA\WC;
use ACA\WC\Subscriptions;
use ACP;
use ACP\Column\DefaultColumnFactory;

class ActiveSubscriber extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Subscriptions\Export\UserSubscription\ActiveSubscriber();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\UserSubscription\ActiveSubscriber();
    }

}