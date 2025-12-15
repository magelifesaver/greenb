<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Comment\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\DefaultColumnFactory;
use ACP\Export;
use ACP\Search;
use ACP\Sorting;

class Response extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Model\Comment\Response();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Comment\Response();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Comment\Post();
    }

}