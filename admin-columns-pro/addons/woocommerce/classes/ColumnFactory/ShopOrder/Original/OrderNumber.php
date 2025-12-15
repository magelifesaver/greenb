<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder\Original;

use AC\Setting\Config;
use ACA\WC\Export;
use ACP;
use ACP\Column\DefaultColumnFactory;

class OrderNumber extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\ShopOrder\Order();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\ID();
    }

}