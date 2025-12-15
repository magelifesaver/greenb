<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\DefaultColumnFactory;

class OrderDate extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\Post\Date();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\Date\PostDate('shop_order');
    }

}