<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopCoupon\Original;

use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\DefaultColumnFactory;

class CouponFactory extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new WC\Export\ShopCoupon\Coupon();
    }

}