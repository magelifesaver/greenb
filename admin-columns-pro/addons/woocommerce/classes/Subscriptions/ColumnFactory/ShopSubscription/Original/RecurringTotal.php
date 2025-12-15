<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\ShopSubscription\Original;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter\StripTags;
use ACA\WC;
use ACP;
use ACP\Column\DefaultColumnFactory;

class RecurringTotal extends DefaultColumnFactory
{

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\ShopOrder\Total();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\FormatterCollection(
            new FormatterCollection([
                new WC\Value\Formatter\OrderSubscription\FormattedOrderTotal(),
                new StripTags(),
            ])
        );
    }

}