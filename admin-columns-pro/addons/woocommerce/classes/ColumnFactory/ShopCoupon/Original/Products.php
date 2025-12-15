<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopCoupon\Original;

use AC\Setting\Config;
use ACA\WC\Export;
use ACA\WC\Search;
use ACP;
use ACP\Column\DefaultColumnFactory;

class Products extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\ShopCoupon\Products();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ShopCoupon\Products('product_ids');
    }

}