<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order\Original;

use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\DefaultColumnFactory;

class BillingAddressFactory extends DefaultColumnFactory
{

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\Order\Address\FullAddress(
            new WC\Type\AddressType(WC\Type\AddressType::BILLING)
        );
    }

}