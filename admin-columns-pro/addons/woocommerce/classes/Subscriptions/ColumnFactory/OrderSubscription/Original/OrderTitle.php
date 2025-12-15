<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\OrderSubscription\Original;

use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\DefaultColumnFactory;

class OrderTitle extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new WC\Export\Order\OrderTitle();
    }

}