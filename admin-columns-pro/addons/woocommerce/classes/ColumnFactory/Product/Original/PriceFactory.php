<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product\Original;

use AC\Setting\Config;
use ACA\WC\Editing\Product\Price;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;
use ACP\Column\DefaultColumnFactory;

class PriceFactory extends DefaultColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Price();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        $include_tax = 'yes' === get_option('woocommerce_prices_include_tax');
        $display_tax = 'incl' === get_option('woocommerce_tax_display_shop');

        if ($include_tax && ! $display_tax) {
            return null;
        }
        if ( ! $include_tax && $display_tax) {
            return null;
        }

        return new ACP\Search\Comparison\Meta\Decimal('_price');
    }

}