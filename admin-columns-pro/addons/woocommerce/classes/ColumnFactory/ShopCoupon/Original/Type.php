<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopCoupon\Original;

use AC\Setting\Config;
use ACA\WC\Editing;
use ACA\WC\Export;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;
use ACP\Column\DefaultColumnFactory;

class Type extends DefaultColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ShopCoupon\Type();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\ShopCoupon\Type();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ShopCoupon\Type($this->get_coupon_types());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\ShopCoupon\Type();
    }

    private function get_coupon_types(): array
    {
        return wc_get_coupon_types();
    }

}