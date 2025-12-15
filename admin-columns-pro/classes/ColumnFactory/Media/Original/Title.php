<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\DefaultColumnFactory;
use ACP\Editing;
use ACP\Export;
use ACP\Search;

class Title extends DefaultColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Media\Title();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Model\Media\Title();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\Title();
    }

}