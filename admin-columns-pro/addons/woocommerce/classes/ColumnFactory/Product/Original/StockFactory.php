<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product\Original;

use AC\Setting\Config;
use ACA\WC\Editing;
use ACA\WC\Export;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;
use ACP\Column\DefaultColumnFactory;
use ACP\Sorting\Type\DataType;

class StockFactory extends DefaultColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Product\Stock();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Product\Stock();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\Stock();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta('_stock', new DataType(DataType::NUMERIC));
    }

}