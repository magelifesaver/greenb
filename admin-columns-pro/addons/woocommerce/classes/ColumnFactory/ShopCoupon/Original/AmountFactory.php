<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopCoupon\Original;

use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\DefaultColumnFactory;
use ACP\Sorting\Type\DataType;

class AmountFactory extends DefaultColumnFactory
{

    private const META_KEY = 'coupon_amount';

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new WC\Editing\ShopCoupon\Amount();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new WC\Export\ShopCoupon\Amount();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Decimal(self::META_KEY);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY, new DataType(DataType::NUMERIC));
    }

}