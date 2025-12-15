<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\DefaultColumnFactory;
use ACP\Export;
use ACP\Sorting;

class Posts extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Model\User\Posts();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\PostCount(['post'], ['publish', 'private']);
    }

}