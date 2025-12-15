<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\DefaultColumnFactory;
use ACP\Editing;
use ACP\Export;
use ACP\Search;
use ACP\Sorting;

class Name extends DefaultColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\User\FullName();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Model\User\FullName();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\User\Name(['first_name', 'last_name']);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\FullName();
    }

}