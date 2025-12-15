<?php

declare(strict_types=1);

namespace ACA\GravityForms\ColumnFactory\Entry;

use AC\Setting\Config;
use ACA\GravityForms\Export;
use ACA\GravityForms\Search;
use ACP;

class StarredFactory extends ACP\Column\DefaultColumnFactory
{

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Entry\Starred();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Model\Entry\EntryProperty('is_starred');
    }

}