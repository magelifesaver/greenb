<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\ShopSubscription\Original;

use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\DefaultColumnFactory;

class Status extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new WC\Subscriptions\Export\ShopSubscription\Status();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\Status('shop_subscription');
    }

}