<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\ShopSubscription\Original;

use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\DefaultColumnFactory;

class OrderItems extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new WC\Subscriptions\Export\ShopSubscription\OrderItems();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\ShopOrder\Product('shop_subscription');
    }

}