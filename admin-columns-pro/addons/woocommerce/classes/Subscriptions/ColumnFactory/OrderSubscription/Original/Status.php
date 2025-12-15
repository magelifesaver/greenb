<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\OrderSubscription\Original;

use AC\Setting\Config;
use ACA\WC;
use ACA\WC\Subscriptions;
use ACP;
use ACP\Column\DefaultColumnFactory;

class Status extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Subscriptions\Export\OrderSubscription\Status();
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Select(wcs_get_subscription_statuses()),
            new WC\Editing\Storage\OrderSubscription\Status()
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\Order\Status(wcs_get_subscription_statuses());
    }

}