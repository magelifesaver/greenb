<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\ShopSubscription\Original;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter\Count;
use ACA\WC;
use ACP;
use ACP\Column\DefaultColumnFactory;

class Orders extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\FormatterCollection(
            new FormatterCollection([
                new WC\Value\Formatter\OrderSubscription\RelatedOrderIds(),
                new Count(),
            ])
        );
    }

}