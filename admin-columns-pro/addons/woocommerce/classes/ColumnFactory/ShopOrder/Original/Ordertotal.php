<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\DefaultColumnFactory;

class Ordertotal extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\Post\Meta('_order_total');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Decimal('_order_total');
    }

}